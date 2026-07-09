/* admin_login.js — Log masuk pentadbir */
(function () {
  'use strict';
  var CSRF = document.getElementById('csrf').value;
  var u = document.getElementById('username');
  var p = document.getElementById('password');
  var btn = document.getElementById('btnLogin');
  var btnText = document.getElementById('btnText');

  document.getElementById('pwToggle').addEventListener('click', function () {
    p.type = p.type === 'password' ? 'text' : 'password';
  });
  [u, p].forEach(function (el) {
    el.addEventListener('keydown', function (e) { if (e.key === 'Enter') masuk(); });
  });
  btn.addEventListener('click', masuk);

  function memuat(on) {
    btn.disabled = on;
    btnText.innerHTML = on ? '<span class="btn-x__spin"></span> Menyemak…' : 'Log Masuk';
  }

  function masuk() {
    if (!u.value.trim() || !p.value) {
      Swal.fire({ icon: 'warning', title: 'Tidak Lengkap', text: 'Sila isi nama pengguna dan kata laluan.', confirmButtonColor: '#4860a8' });
      return;
    }
    memuat(true);
    fetch('../api/admin_login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: u.value.trim(), password: p.value, csrf: CSRF })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j.ok) { location.href = 'dashboard.php'; }
        else {
          memuat(false);
          Swal.fire({ icon: 'error', title: 'Gagal', text: j.mesej, confirmButtonColor: '#4860a8' });
        }
      })
      .catch(function () {
        memuat(false);
        Swal.fire({ icon: 'error', title: 'Ralat Sambungan', text: 'Sila cuba lagi.', confirmButtonColor: '#4860a8' });
      });
  }
})();
