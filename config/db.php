<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$__logDir = __DIR__ . '/../logs';
if (!is_dir($__logDir)) { @mkdir($__logDir, 0750, true); }
ini_set('error_log', $__logDir . '/php_error.log');
date_default_timezone_set('Asia/Kuala_Lumpur');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('X-XSS-Protection: 1; mode=block');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
header("Content-Security-Policy: default-src 'self'; "
     . "script-src 'self' 'unsafe-inline'; "
     . "style-src 'self' 'unsafe-inline'; "
     . "img-src 'self' data:; "
     . "font-src 'self' data:; "
     . "connect-src 'self'; "
     . "frame-ancestors 'none'; "
     . "base-uri 'self'; form-action 'self'");

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,    
        'samesite' => 'Strict',
    ]);
    session_name('KSKUNDISESS');
    session_start();
}

const DB_HOST = 'localhost';
const DB_NAME = 'DATABASE_NAME'; // TODO: ganti dengan nama DB sebenar
const DB_USER = 'DATABASE_USER';        // TODO: ganti dengan user DB sebenar
const DB_PASS = 'DATABASE_PASS'; // TODO: ganti dengan kata laluan DB sebenar
const DB_CHARSET = 'latin1';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('DB connect fail: ' . $e->getMessage());
    http_response_code(500);
    exit('Ralat sistem. Sila cuba sebentar lagi.');
}

function jsonBody(): array {
    static $cache = null;
    if ($cache === null) {
        $raw   = file_get_contents('php://input') ?: '';
        $data  = json_decode($raw, true);
        $cache = is_array($data) ? $data : [];
    }
    return $cache;
}

function inp(string $key, $default = null) {
    $body = jsonBody();
    if (array_key_exists($key, $body))  return $body[$key];
    if (isset($_POST[$key]))            return $_POST[$key];
    return $default;
}

function jsonOut(array $payload, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function gredNombor(?string $gred): ?int {
    if ($gred === null) return null;
    $n = preg_replace('/[^0-9]/', '', $gred);
    return ($n === '') ? null : (int) $n;
}

function bersihMykad(?string $v): ?string {
    $v = preg_replace('/\D/', '', $v ?? '');
    return (strlen($v) === 12) ? $v : null;
}

function ipPelawat(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrfToken(): string { return $_SESSION['csrf']; }
function csrfSah(?string $t): bool {
    return is_string($t) && hash_equals($_SESSION['csrf'] ?? '', $t);
}

function statusUndian(PDO $pdo): array {
    $s = $pdo->query("SELECT * FROM ksk_undi_settings WHERE id=1")->fetch();
    if (!$s) return ['buka'=>false, 'sebab'=>'Sistem belum dikonfigurasi.', 'settings'=>[]];

    $kini  = new DateTime('now');
    $mula  = new DateTime($s['tarikh_mula']);
    $tamat = new DateTime($s['tarikh_tamat']);

    if ((int)$s['status_manual_lock'] === 1)
        return ['buka'=>false, 'sebab'=>'Undian ditutup oleh pentadbir.', 'settings'=>$s];
    if ($kini < $mula)
        return ['buka'=>false, 'sebab'=>'Undian belum bermula.', 'settings'=>$s];
    if ($kini > $tamat)
        return ['buka'=>false, 'sebab'=>'Tempoh undian telah tamat.', 'settings'=>$s];

    return ['buka'=>true, 'sebab'=>'', 'settings'=>$s];
}