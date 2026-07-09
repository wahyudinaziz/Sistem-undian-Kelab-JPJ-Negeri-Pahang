<?php
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['ok' => false, 'mesej' => 'Kaedah tidak dibenarkan.'], 405);
}
if (empty($_SESSION['pengundi']['staff_id'])) {
    jsonOut(['ok' => false, 'mesej' => 'Sesi tamat. Sila masukkan No. MyKad semula.'], 401);
}
if (!csrfSah(inp('csrf'))) {
    jsonOut(['ok' => false, 'mesej' => 'Sesi tamat. Sila muat semula halaman.'], 419);
}

$st = statusUndian($pdo);
if (!$st['buka']) {
    jsonOut(['ok' => false, 'mesej' => $st['sebab'], 'tutup' => true], 403);
}

$diriId   = (int) $_SESSION['pengundi']['staff_id'];
$mykad    = $_SESSION['pengundi']['mykad'];
$nama     = $_SESSION['pengundi']['nama'];
$cawangan = $_SESSION['pengundi']['cawangan'];

$pilihanRaw = inp('pilihan');
if (!is_array($pilihanRaw) || !$pilihanRaw) {
    jsonOut(['ok' => false, 'mesej' => 'Tiada pilihan diterima.'], 422);
}
// Tukar kepada [position_id => candidate_id] integer
$pilihan = [];
foreach ($pilihanRaw as $pid => $cid) {
    $pid = (int) $pid;
    $cid = (int) $cid;
    if ($pid > 0 && $cid > 0) $pilihan[$pid] = $cid;
}

$jawatanList = $pdo->query(
    "SELECT id, nama_jawatan, kategori, min_gred, scope_cawangan
       FROM ksk_undi_positions"
)->fetchAll();
$jawatanById = [];
foreach ($jawatanList as $j) $jawatanById[(int)$j['id']] = $j;

if (count($pilihan) !== count($jawatanById)) {
    jsonOut(['ok' => false, 'mesej' => 'Sila lengkapkan undian untuk KESEMUA jawatan.'], 422);
}
foreach ($jawatanById as $pid => $_) {
    if (!isset($pilihan[$pid])) {
        jsonOut(['ok' => false, 'mesej' => 'Terdapat jawatan yang belum diundi.'], 422);
    }
}

if (in_array($diriId, $pilihan, true)) {
    jsonOut(['ok' => false, 'mesej' => 'Anda tidak boleh mengundi diri sendiri.'], 422);
}

if (count(array_unique($pilihan)) !== count($pilihan)) {
    jsonOut(['ok' => false, 'mesej' => 'Seorang calon tidak boleh dipilih untuk lebih dari satu jawatan.'], 422);
}

$ids   = array_values($pilihan);
$ph    = implode(',', array_fill(0, count($ids), '?'));
$qc    = $pdo->prepare("SELECT id, status, gred, cawangan FROM staff WHERE id IN ($ph)");
$qc->execute($ids);
$stafById = [];
foreach ($qc->fetchAll() as $r) $stafById[(int)$r['id']] = $r;

foreach ($pilihan as $pid => $cid) {
    $j = $jawatanById[$pid];
    $s = $stafById[$cid] ?? null;

    if (!$s || (int)$s['status'] !== 2) {
        jsonOut(['ok' => false, 'mesej' => 'Calon tidak sah dikesan. Sila muat semula dan cuba lagi.'], 422);
    }
    if ($j['kategori'] === 'A') {
        $g = gredNombor($s['gred']);
        if ($g === null || $g < (int)$j['min_gred']) {
            jsonOut(['ok' => false, 'mesej' => 'Calon tidak memenuhi syarat gred bagi jawatan ' . $j['nama_jawatan'] . '.'], 422);
        }
    } else {
        if ((int)$j['scope_cawangan'] === 1 && $s['cawangan'] !== $cawangan) {
            jsonOut(['ok' => false, 'mesej' => 'Calon bagi ' . $j['nama_jawatan'] . ' mesti dari cawangan anda.'], 422);
        }
    }
}

try {
    $pdo->beginTransaction();

    $log = $pdo->prepare(
        "INSERT INTO ksk_undi_voter_log (mykad, nama, cawangan, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?)"
    );
    $log->execute([
        $mykad, $nama, $cawangan,
        ipPelawat(),
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);

    $ins = $pdo->prepare(
        "INSERT INTO ksk_undi_votes (position_id, candidate_staff_id) VALUES (?, ?)"
    );
    $rows = [];
    foreach ($pilihan as $pid => $cid) $rows[] = [$pid, $cid];
    shuffle($rows);
    foreach ($rows as $r) $ins->execute($r);

    $pdo->commit();

    unset($_SESSION['pengundi']);
    session_regenerate_id(true);

    jsonOut(['ok' => true, 'mesej' => 'Undian anda telah berjaya direkodkan. Terima kasih.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    if ($e->getCode() === '23000') {
        jsonOut(['ok' => false, 'mesej' => 'Anda telah pun mengundi. Undian hanya dibenarkan sekali sahaja.', 'sudah' => true], 409);
    }
    error_log('submit_vote fail: ' . $e->getMessage());
    jsonOut(['ok' => false, 'mesej' => 'Ralat sistem semasa merekod undian. Sila cuba lagi.'], 500);
}