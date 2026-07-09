<?php
require __DIR__ . '/../config/admin_guard.php';
$adminNama = $_SESSION['admin']['username'] ?? 'Pentadbir';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#16223d">
<meta name="robots" content="noindex, nofollow">
<title>Papan Pemuka — Undian KSK JPJ Negeri Pahang</title>
<link rel="icon" href="../assets/img/logo.png">
<link rel="stylesheet" href="../assets/vendor/bootstrap.min.css">
<link rel="stylesheet" href="../assets/vendor/sweetalert2.min.css">
<link rel="stylesheet" href="../assets/css/app.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="admbar">
  <img src="../assets/img/logo.png" alt="KSK" class="admbar__logo">
  <div class="admbar__ttl">
    <b>Panel Pentadbir Undian KSK</b>
    <span>Log masuk sebagai <?= e($adminNama) ?></span>
  </div>
  <a href="logout.php" class="admbar__out">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Log Keluar
  </a>
</div>

<main class="wrap wrap--wide" style="padding-top:20px">

  <!-- Statistik -->
  <div class="stats" id="stats">
    <div class="stat"><div class="stat__k">Jumlah Mengundi</div><div class="stat__v" id="sUndi">–</div><div class="stat__s" id="sUndiSub">memuat…</div></div>
    <div class="stat"><div class="stat__k">Kadar Penyertaan</div><div class="stat__v" id="sPeratus">–</div><div class="stat__s">daripada kakitangan layak</div></div>
    <div class="stat"><div class="stat__k">Status Undian</div><div class="stat__v" id="sStatus" style="font-size:1.1rem">–</div><div class="stat__s" id="sStatusSub">memuat…</div></div>
  </div>

  <!-- Tetapan -->
  <div class="ahead">
    <h2>Tetapan Undian</h2><div class="sp"></div>
  </div>
  <section class="card-x">
    <div class="setgrid">
      <div class="field" style="margin:0">
        <label class="field__label" for="tMula">Tarikh &amp; Masa Mula</label>
        <input type="datetime-local" id="tMula" class="set-in">
      </div>
      <div class="field" style="margin:0">
        <label class="field__label" for="tTamat">Tarikh &amp; Masa Tamat</label>
        <input type="datetime-local" id="tTamat" class="set-in">
      </div>
    </div>

    <div class="switch">
      <label class="switch__box"><input type="checkbox" id="swLock"><span class="switch__sl"></span></label>
      <div class="switch__lbl"><b>Kunci Manual (Tutup Paksa)</b><span>Tutup undian serta-merta tanpa mengira tarikh.</span></div>
    </div>
    <div class="switch">
      <label class="switch__box"><input type="checkbox" id="swPapar"><span class="switch__sl"></span></label>
      <div class="switch__lbl"><b>Zahirkan Keputusan</b><span>Benarkan keputusan dipaparkan di panel ini.</span></div>
    </div>

    <button type="button" class="btn-x" id="btnSimpan" style="margin-top:8px">
      <span id="simpanText">Simpan Tetapan</span>
    </button>
  </section>

  <!-- Keputusan -->
  <div class="ahead">
    <h2>Keputusan Undian</h2><div class="sp"></div>
    <button type="button" class="btn-x btn-x--gold" id="btnExport" style="width:auto;padding:11px 16px;font-size:.88rem">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Excel
    </button>
  </div>
  <div id="keputusan">
    <div class="loading-full" style="padding:40px 0"><span class="btn-x__spin" style="border-top-color:var(--steel-2);border-color:rgba(58,84,143,.25)"></span><span>Memuatkan keputusan…</span></div>
  </div>

  <p class="foot">Sistem Undian Atas Talian &middot; KSK JPJ Negeri Pahang 2026</p>
</main>

<input type="hidden" id="csrf" value="<?= e(csrfToken()) ?>">
<script src="../assets/vendor/sweetalert2.min.js"></script>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>
