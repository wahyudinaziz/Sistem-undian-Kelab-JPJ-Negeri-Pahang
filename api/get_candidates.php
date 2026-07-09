<?php
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['pengundi']['staff_id'])) {
    jsonOut(['ok' => false, 'mesej' => 'Sesi tidak sah. Sila masukkan No. MyKad semula.'], 401);
}

$st = statusUndian($pdo);
if (!$st['buka']) {
    jsonOut(['ok' => false, 'mesej' => $st['sebab'], 'tutup' => true], 403);
}

$diriId   = (int) $_SESSION['pengundi']['staff_id'];
$cawangan = $_SESSION['pengundi']['cawangan'];

$jawatanList = $pdo->query(
    "SELECT id, kod_jawatan, nama_jawatan, kategori, min_gred, scope_cawangan
       FROM ksk_undi_positions
      ORDER BY susunan ASC"
)->fetchAll();

$qa = $pdo->prepare(
    "SELECT id, nama, gred, jawatan, cawangan
       FROM staff
      WHERE status = 2 AND id <> ?
      ORDER BY nama ASC"
);
$qa->execute([$diriId]);
$poolA = $qa->fetchAll();

$qb = $pdo->prepare(
    "SELECT id, nama, gred, jawatan
       FROM staff
      WHERE status = 2 AND cawangan = ? AND id <> ?
      ORDER BY nama ASC"
);
$qb->execute([$cawangan, $diriId]);
$poolB = $qb->fetchAll();

$out = [];
foreach ($jawatanList as $j) {
    $calon = [];

    if ($j['kategori'] === 'A') {
        $min = (int) $j['min_gred'];
        foreach ($poolA as $s) {
            $g = gredNombor($s['gred']);       
            if ($g !== null && $g >= $min) {
                $calon[] = [
                    'id'      => (int) $s['id'],
                    'nama'    => $s['nama'],
                    'gred'    => $s['gred'],
                    'jawatan' => $s['jawatan'],
                ];
            }
        }
    } else { 
        foreach ($poolB as $s) {
            $calon[] = [
                'id'      => (int) $s['id'],
                'nama'    => $s['nama'],
                'gred'    => $s['gred'],
                'jawatan' => $s['jawatan'],
            ];
        }
    }

    $out[] = [
        'id'       => (int) $j['id'],
        'kod'      => $j['kod_jawatan'],
        'nama'     => $j['nama_jawatan'],
        'kategori' => $j['kategori'],
        'calon'    => $calon,
    ];
}

jsonOut([
    'ok'       => true,
    'pengundi' => [
        'nama'     => $_SESSION['pengundi']['nama'],
        'cawangan' => $cawangan,
    ],
    'jawatan'  => $out,
]);
