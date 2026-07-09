(function () {
  'use strict';

  var CSRF = document.getElementById('csrf').value;

  var box = document.getElementById('countBox');
  if (box) {
    var sasaran = new Date(box.dataset.sasaran.replace(' ', 'T')).getTime();
    var fasa = box.dataset.fasa;
    var buka = box.dataset.buka === '1';
    var grid = document.getElementById('countGrid');
    var closed = document.getElementById('countClosed');
    var label = document.getElementById('countLabel');
    var cell = {
      d: box.querySelector('[data-k="d"]'),
      h: box.querySelector('[data-k="h"]'),
      m: box.querySelector('[data-k="m"]'),
      s: box.querySelector('[data-k="s"]')
    };
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };

    function tutup(msg) {
      grid.style.display = 'none';
      closed.style.display = 'block';
      closed.textContent = msg;
      box.classList.add('count--closed');
      label.textContent = 'Status Undian';
      var i = document.getElementById('mykad');
      var b = document.getElementById('btnMasuk');
      if (i) i.disabled = true;
      if (b) b.disabled = true;
    }

    function tick() {
      var jarak = sasaran - Date.now();
      if (jarak <= 0) {
        if (fasa === 'mula') { location.reload(); }
        else { tutup('Tempoh undian telah tamat.'); clearInterval(timer); }
        return;
      }
      var d = Math.floor(jarak / 86400000);
      var h = Math.floor((jarak % 86400000) / 3600000);
      var m = Math.floor((jarak % 3600000) / 60000);
      var s = Math.floor((jarak % 60000) / 1000);
      cell.d.textContent = pad(d);
      cell.h.textContent = pad(h);
      cell.m.textContent = pad(m);
      cell.s.textContent = pad(s);
    }

    if (!buka && fasa === 'tamat') {
      tutup('Undian tidak dibuka pada masa ini.');
    } else {
      tick();
      var timer = setInterval(tick, 1000);
    }
  }

  var inp = document.getElementById('mykad');
  var btn = document.getElementById('btnMasuk');
  var btnText = document.getElementById('btnText');

  if (inp) {
    inp.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 12);
    });
    inp.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); hantar(); }
    });
  }
  if (btn) btn.addEventListener('click', hantar);

  function memuat(on) {
    if (!btn) return;
    btn.disabled = on;
    btnText.innerHTML = on
      ? '<span class="btn-x__spin"></span> Menyemak…'
      : 'Sahkan &amp; Mula Mengundi';
  }

  function ralat(msg) {
    Swal.fire({ icon: 'error', title: 'Tidak Berjaya', text: msg, confirmButtonColor: '#4860a8' });
  }

  function hantar() {
    var mykad = (inp.value || '').replace(/\D/g, '');
    if (mykad.length !== 12) {
      ralat('No. MyKad mesti tepat 12 digit tanpa sengkang.');
      inp.focus();
      return;
    }
    memuat(true);

    fetch('api/check_mykad.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ mykad: mykad, csrf: CSRF })
    })
      .then(function (r) { return r.json().then(function (j) { return { s: r.status, j: j }; }); })
      .then(function (res) {
        var j = res.j;
        if (j.ok) {
          Swal.fire({
            icon: 'success',
            title: 'Selamat Datang',
            html: '<b>' + (j.nama || '') + '</b><br><span style="color:#5a6b86">Cawangan: ' + (j.cawangan || '-') + '</span>',
            confirmButtonText: 'Teruskan Mengundi',
            confirmButtonColor: '#d8a818',
            allowOutsideClick: false
          }).then(function () { window.location.href = 'undi.php'; });
        } else {
          memuat(false);
          if (j.tutup) { setTimeout(function () { location.reload(); }, 1600); }
          ralat(j.mesej || 'Ralat tidak dijangka.');
        }
      })
      .catch(function () {
        memuat(false);
        ralat('Gangguan sambungan. Sila cuba lagi.');
      });
  }
})();
