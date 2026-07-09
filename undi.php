<?php
/**
 * undi.php — Borang undian utama (10 jawatan).
 * Pengundi mesti sudah disahkan (ada $_SESSION['pengundi']) dan undian buka.
 */
require __DIR__ . '/config/db.php';

// Pastikan sesi pengundi sah — jika tidak, hantar balik ke skrin masuk
if (empty($_SESSION['pengundi']['staff_id'])) {
    header('Location: index.php');
    exit;
}
$st = statusUndian($pdo);
if (!$st['buka']) {
    header('Location: index.php');
    exit;
}
$p   = $_SESSION['pengundi'];
$cfg = $st['settings'];
$tamat = $cfg['tarikh_tamat'];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#16223d">
<meta name="robots" content="noindex, nofollow">
<title>Borang Undian — KSK JPJ Negeri Pahang 2026</title>
<link rel="icon" href="assets/img/logo.png">
<link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
<link rel="stylesheet" href="assets/vendor/select2.min.css">
<link rel="stylesheet" href="assets/vendor/sweetalert2.min.css">
<link rel="stylesheet" href="assets/css/app.css">
<link rel="stylesheet" href="assets/css/undi.css">
</head>
<body>

<!-- Bar melekat: identiti pengundi + baki masa -->
<div class="topbar">
  <img src="assets/img/logo.png" alt="KSK" class="topbar__logo">
  <div class="topbar__id">
    <div class="topbar__nama"><?= e($p['nama']) ?></div>
    <div class="topbar__caw">Cawangan: <?= e($p['cawangan']) ?></div>
  </div>
  <div class="topbar__time" id="baki" data-tamat="<?= e($tamat) ?>">--:--:--</div>
</div>

<main class="wrap wrap--wide">

  <header class="brand" style="margin-top:14px">
    <p class="brand__eyebrow">Borang Undian Rasmi</p>
    <h1 class="brand__title" style="font-size:1.2rem">Sila Pilih Calon Bagi Setiap Jawatan</h1>
    <p class="brand__sub">Semua 10 jawatan wajib diisi. Seorang calon hanya untuk satu jawatan.</p>
  </header>

  <!-- Kandungan dijana oleh JS selepas muat calon -->
  <div id="borang">
    <div class="loading-full">
      <span class="btn-x__spin"></span>
      <span>Memuatkan senarai calon…</span>
    </div>
  </div>

  <!-- Progress + hantar (disembunyikan sehingga borang siap) -->
  <div class="progress-x" id="progressBar" style="display:none">
    <div class="progress-x__bar"><div class="progress-x__fill" id="pgFill"></div></div>
    <div class="progress-x__row">
      <div class="progress-x__txt"><b id="pgCount">0</b>/10 dipilih</div>
      <button type="button" class="btn-x btn-x--gold" id="btnHantar" disabled>
        <span id="hantarText">Semak &amp; Hantar Undian</span>
      </button>
    </div>
  </div>

</main>

<input type="hidden" id="csrf" value="<?= e(csrfToken()) ?>">

<script src="assets/vendor/jquery.min.js"></script>
<script src="assets/vendor/select2.min.js"></script>
<script src="assets/vendor/sweetalert2.min.js"></script>
<script src="assets/js/undi.js"></script>
</body>
</html>