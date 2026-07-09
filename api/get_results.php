<?php
require __DIR__ . '/../config/admin_guard.php';

$s = $pdo->query("SELECT * FROM ksk_undi_settings WHERE id=1")->fetch() ?: [];
$st = statusUndian($pdo);

$jumlahUndi  = (int) $pdo->query("SELECT COUNT(*) FROM ksk_undi_voter_log")->fetchColumn();
$jumlahLayak = (int) $pdo->query("SELECT COUNT(*) FROM staff WHERE status = 2")->fetchColumn();
$peratus = $jumlahLayak > 0 ? round($jumlahUndi / $jumlahLayak * 100, 1) : 0.0;

$jawatan = $pdo->query(
    "SELECT id, kod_jawatan, nama_jawatan, kategori
       FROM ksk_undi_positions ORDER BY susunan ASC"
)->fetchAll();

$qCount = $pdo->prepare(
    "SELECT v.candidate_staff_id AS sid, COUNT(*) AS undi,
            s.nama, s.cawangan, s.gred
       FROM ksk_undi_votes v
       JOIN staff s ON s.id = v.candidate_staff_id
      WHERE v.position_id = ?
      GROUP BY v.candidate_staff_id
      ORDER BY undi DESC, s.nama ASC"
);

$keputusan = [];
foreach ($jawatan as $j) {
    $qCount->execute([(int)$j['id']]);
    $rows = $qCount->fetchAll();

    $cutoff = null;
    if (count($rows) >= 5) $cutoff = (int) $rows[4]['undi'];

    $papar = [];
    foreach ($rows as $r) {
        if ($cutoff !== null && (int)$r['undi'] < $cutoff) break;
        $papar[] = $r;
    }

    $freq = [];
    foreach ($papar as $r) { $u=(int)$r['undi']; $freq[$u]=($freq[$u]??0)+1; }

    $calon = [];
    $rank = 0; $idx = 0; $prev = null;
    foreach ($papar as $r) {
        $idx++;
        $u = (int) $r['undi'];
        if ($u !== $prev) { $rank = $idx; $prev = $u; }
        $calon[] = [
            'nama'     => $r['nama'],
            'cawangan' => $r['cawangan'],
            'gred'     => $r['gred'],
            'undi'     => $u,
            'rank'     => $rank,
            'tie'      => $freq[$u] > 1,
        ];
    }

    $keputusan[] = [
        'position_id' => (int) $j['id'],
        'kod'         => $j['kod_jawatan'],
        'nama'        => $j['nama_jawatan'],
        'kategori'    => $j['kategori'],
        'seri'        => (bool) array_filter($freq, function($n){ return $n > 1; }),
        'calon'       => $calon,
    ];
}

jsonOut([
    'ok'       => true,
    'settings' => [
        'tajuk_undian'       => $s['tajuk_undian'] ?? '',
        'tarikh_mula'        => $s['tarikh_mula'] ?? '',
        'tarikh_tamat'       => $s['tarikh_tamat'] ?? '',
        'status_manual_lock' => (int)($s['status_manual_lock'] ?? 0),
        'hasil_dizahirkan'   => (int)($s['hasil_dizahirkan'] ?? 0),
    ],
    'status' => ['buka' => $st['buka'], 'teks' => $st['buka'] ? 'Undian sedang dibuka.' : $st['sebab']],
    'stats'  => ['jumlah_undi' => $jumlahUndi, 'jumlah_layak' => $jumlahLayak, 'peratus' => $peratus],
    'keputusan' => $keputusan,
]);