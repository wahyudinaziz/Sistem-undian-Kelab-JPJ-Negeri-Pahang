<?php
require __DIR__ . '/../config/admin_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['ok' => false, 'mesej' => 'Kaedah tidak dibenarkan.'], 405);
}
if (!csrfSah(inp('csrf'))) {
    jsonOut(['ok' => false, 'mesej' => 'Sesi tamat. Sila muat semula halaman.'], 419);
}

function sahTarikh($v) {
    $d = DateTime::createFromFormat('Y-m-d H:i:s', (string)$v);
    return ($d && $d->format('Y-m-d H:i:s') === $v) ? $d : null;
}
$mula  = sahTarikh(inp('tarikh_mula'));
$tamat = sahTarikh(inp('tarikh_tamat'));

if (!$mula || !$tamat) {
    jsonOut(['ok' => false, 'mesej' => 'Format tarikh tidak sah.'], 422);
}
if ($tamat <= $mula) {
    jsonOut(['ok' => false, 'mesej' => 'Tarikh tamat mesti selepas tarikh mula.'], 422);
}

$lock  = (int) inp('status_manual_lock') === 1 ? 1 : 0;
$papar = (int) inp('hasil_dizahirkan') === 1 ? 1 : 0;

$u = $pdo->prepare(
    "UPDATE ksk_undi_settings
        SET tarikh_mula = ?, tarikh_tamat = ?, status_manual_lock = ?, hasil_dizahirkan = ?
      WHERE id = 1"
);
$u->execute([$mula->format('Y-m-d H:i:s'), $tamat->format('Y-m-d H:i:s'), $lock, $papar]);

jsonOut(['ok' => true, 'mesej' => 'Tetapan undian telah dikemas kini.']);