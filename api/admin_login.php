<?php
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['ok' => false, 'mesej' => 'Kaedah tidak dibenarkan.'], 405);
}
if (!csrfSah(inp('csrf'))) {
    jsonOut(['ok' => false, 'mesej' => 'Sesi tamat. Sila muat semula halaman.'], 419);
}

$_SESSION['login_try'] = $_SESSION['login_try'] ?? ['n' => 0, 't' => time()];
if (time() - $_SESSION['login_try']['t'] > 300) $_SESSION['login_try'] = ['n' => 0, 't' => time()];
if ($_SESSION['login_try']['n'] >= 5) {
    jsonOut(['ok' => false, 'mesej' => 'Terlalu banyak cubaan. Sila cuba semula selepas beberapa minit.'], 429);
}

$user = trim((string) inp('username'));
$pass = (string) inp('password');

$q = $pdo->prepare("SELECT id, username, password FROM ksk_undi_admin WHERE username = ? LIMIT 1");
$q->execute([$user]);
$adm = $q->fetch();

if (!$adm || !password_verify($pass, $adm['password'])) {
    $_SESSION['login_try']['n']++;
    jsonOut(['ok' => false, 'mesej' => 'Nama pengguna atau kata laluan salah.'], 401);
}

$_SESSION['login_try'] = ['n' => 0, 't' => time()];
session_regenerate_id(true);
$_SESSION['admin'] = ['id' => (int)$adm['id'], 'username' => $adm['username'], 'masa' => time()];
$pdo->prepare("UPDATE ksk_undi_admin SET last_login = NOW() WHERE id = ?")->execute([(int)$adm['id']]);

jsonOut(['ok' => true, 'mesej' => 'Log masuk berjaya.']);