<?php
require __DIR__ . '/config/db.php';

$st  = statusUndian($pdo);
$cfg = $st['settings'];
$buka = $st['buka'];

$kini = new DateTime('now');
if (!empty($cfg['tarikh_mula']) && $kini < new DateTime($cfg['tarikh_mula'])) {
    $fasa = 'mula';
    $sasaran = $cfg['tarikh_mula'];
} else {
    $fasa = 'tamat';
    $sasaran = $cfg['tarikh_tamat'] ?? $kini->format('Y-m-d H:i:s');
}
$tajuk = $cfg['tajuk_undian'] ?? 'Pemilihan AJK KSK JPJ Negeri Pahang 2026';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#16223d">
<meta name="robots" content="noindex, nofollow">
<title>Undian KSK JPJ Negeri Pahang 2026</title>
<link rel="icon" href="assets/img/logo.png">
<link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
<link rel="stylesheet" href="assets/vendor/sweetalert2.min.css">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<main class="wrap">

  <header class="brand rise">
    <img src="assets/img/logo.png" alt="Logo KSK JPJ Negeri Pahang" class="brand__logo">
    <p class="brand__eyebrow">Kelab Sukan &amp; Kebajikan</p>
    <h1 class="brand__title">Undian Pemilihan AJK<br>KSK JPJ Negeri Pahang</h1>
    <p class="brand__sub">Sesi Pemilihan 2026</p>
  </header>

  <section class="count <?= $buka ? '' : ($fasa==='mula' ? '' : 'count--closed') ?> rise rise-2"
           id="countBox"
           data-sasaran="<?= e($sasaran) ?>"
           data-fasa="<?= e($fasa) ?>"
           data-buka="<?= $buka ? '1' : '0' ?>">
    <div class="count__label" id="countLabel">
      <?= $fasa==='mula' ? 'Undian bermula dalam' : 'Undian ditutup dalam' ?>
    </div>
    <div class="count__grid" id="countGrid">
      <div class="count__cell"><div class="count__num" data-k="d">00</div><span class="count__unit">Hari</span></div>
      <div class="count__cell"><div class="count__num" data-k="h">00</div><span class="count__unit">Jam</span></div>
      <div class="count__cell"><div class="count__num" data-k="m">00</div><span class="count__unit">Minit</span></div>
      <div class="count__cell"><div class="count__num" data-k="s">00</div><span class="count__unit">Saat</span></div>
    </div>
    <div class="count__closed-msg" id="countClosed" style="display:none"></div>
  </section>

  <section class="card-x rise rise-3">
    <div class="field">
      <label class="field__label" for="mykad">No. Kad Pengenalan (MyKad)</label>
      <input type="text" id="mykad" class="input-x" inputmode="numeric"
             autocomplete="off" maxlength="12" placeholder="000000000000"
             <?= $buka ? '' : 'disabled' ?>>
      <p class="field__hint">Masukkan 12 digit tanpa sengkang. Contoh: 900101015500</p>
    </div>

    <button type="button" id="btnMasuk" class="btn-x btn-x--gold" <?= $buka ? '' : 'disabled' ?>>
      <span id="btnText">Sahkan &amp; Mula Mengundi</span>
    </button>
    <!--
    <div class="note">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke-linejoin="round"/>
      </svg>
      
      <span>Undian adalah <b>SULIT</b> dan hanya dibenarkan <b>sekali sahaja</b>. Anda tidak boleh mengundi diri sendiri.</span>
      
    </div>
    -->
  </section>

  <p class="foot">
    &copy; <?= date('Y') ?> <b>Wahyudin Aziz | <a href="https://wahyudin.dev/" target="_blank">wahyudin.dev</a></b><br>
    Untuk KSK JPJ Negeri Pahang
  </p>
</main>

<input type="hidden" id="csrf" value="<?= e(csrfToken()) ?>">

<script src="assets/vendor/sweetalert2.min.js"></script>
<script src="assets/js/index.js"></script>
</body>
</html>