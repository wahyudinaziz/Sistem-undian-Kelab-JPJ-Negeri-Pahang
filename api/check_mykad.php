<?php
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['ok' => false, 'mesej' => 'Kaedah tidak dibenarkan.'], 405);
}

if (!csrfSah(inp('csrf'))) {
    jsonOut(['ok' => false, 'mesej' => 'Sesi tamat. Sila muat semula halaman.'], 419);
}

$st = statusUndian($pdo);
if (!$st['buka']) {
    jsonOut(['ok' => false, 'mesej' => $st['sebab'], 'tutup' => true], 403);
}

$mykad = bersihMykad(inp('mykad'));
if ($mykad === null) {
    jsonOut(['ok' => false, 'mesej' => 'No. MyKad mesti 12 digit tanpa sengkang.'], 422);
}

$q = $pdo->prepare(
    "SELECT id, nama, cawangan, status
       FROM staff
      WHERE mykad = ?
      LIMIT 1"
);
$q->execute([$mykad]);
$staf = $q->fetch();

if (!$staf) {
    jsonOut(['ok' => false, 'mesej' => 'No. MyKad tidak wujud dalam rekod kakitangan.'], 404);
}
if ((int)$staf['status'] !== 2) {
    jsonOut(['ok' => false, 'mesej' => 'Anda tidak layak mengundi (status tidak aktif).'], 403);
}

$q = $pdo->prepare("SELECT 1 FROM ksk_undi_voter_log WHERE mykad = ? LIMIT 1");
$q->execute([$mykad]);
if ($q->fetchColumn()) {
    jsonOut(['ok' => false, 'mesej' => 'Anda telah pun mengundi. Undian hanya dibenarkan sekali sahaja.', 'sudah' => true], 409);
}

session_regenerate_id(true);
$_SESSION['pengundi'] = [
    'staff_id' => (int)$staf['id'],
    'mykad'    => $mykad,
    'nama'     => $staf['nama'],
    'cawangan' => $staf['cawangan'],
    'sah_pada' => time(),
];

jsonOut([
    'ok'       => true,
    'mesej'    => 'Pengesahan berjaya.',
    'nama'     => $staf['nama'],
    'cawangan' => $staf['cawangan'],
]);
