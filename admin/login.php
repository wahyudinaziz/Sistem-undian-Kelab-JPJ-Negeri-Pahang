<?php
require __DIR__ . '/../config/db.php';
if (!empty($_SESSION['admin']['id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#16223d">
<meta name="robots" content="noindex, nofollow">
<title>Log Masuk Pentadbir — Undian KSK</title>
<link rel="icon" href="../assets/img/logo.png">
<link rel="stylesheet" href="../assets/vendor/bootstrap.min.css">
<link rel="stylesheet" href="../assets/vendor/sweetalert2.min.css">
<link rel="stylesheet" href="../assets/css/app.css">
<style>
.pw-wrap { position: relative; }
.pw-toggle {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  background: none; border: none; color: var(--muted); cursor: pointer; padding: 6px;
}
.input-x--left { text-align: left; letter-spacing: normal; font-size: 1rem; }
</style>
</head>
<body>
<main class="wrap" style="max-width:440px">

  <header class="brand rise">
    <img src="../assets/img/logo.png" alt="KSK" class="brand__logo">
    <p class="brand__eyebrow">Panel Pentadbir</p>
    <h1 class="brand__title" style="font-size:1.24rem">Sistem Undian KSK<br>JPJ Negeri Pahang</h1>
    <p class="brand__sub">Log masuk untuk urus keputusan undian</p>
  </header>

  <section class="card-x rise rise-2">
    <div class="field">
      <label class="field__label" for="username">Nama Pengguna</label>
      <input type="text" id="username" class="input-x input-x--left" autocomplete="username" placeholder="Nama pengguna">
    </div>
    <div class="field">
      <label class="field__label" for="password">Kata Laluan</label>
      <div class="pw-wrap">
        <input type="password" id="password" class="input-x input-x--left" autocomplete="current-password" placeholder="Kata laluan" style="padding-right:44px">
        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Papar kata laluan">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>
    <button type="button" id="btnLogin" class="btn-x">
      <span id="btnText">Log Masuk</span>
    </button>
  </section>

  <p class="foot"><a href="../index.php" style="color:var(--steel);text-decoration:none">&larr; Kembali ke halaman undian</a></p>
</main>

<input type="hidden" id="csrf" value="<?= e(csrfToken()) ?>">
<script src="../assets/vendor/sweetalert2.min.js"></script>
<script src="../assets/js/admin_login.js"></script>
</body>
</html>
