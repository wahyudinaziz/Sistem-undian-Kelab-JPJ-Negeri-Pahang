(function () {
  'use strict';
  var CSRF = document.getElementById('csrf').value;

  function toLocalInput(dtStr) {
    if (!dtStr) return '';
    return dtStr.replace(' ', 'T').slice(0, 16);
  }
  function fromLocalInput(v) {
    if (!v) return '';
    return v.replace('T', ' ') + (v.length === 16 ? ':00' : '');
  }
  function esc(s) {
    return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function muat() {
    fetch('../api/get_results.php', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json().then(function(j){ return {s:r.status,j:j}; }); })
      .then(function (res) {
        if (res.s === 401 || (res.j && res.j.login)) { location.href = 'login.php'; return; }
        var j = res.j;
        if (!j.ok) { gagal(j.mesej || 'Gagal memuat data.'); return; }
        isiStats(j.stats, j.status);
        isiTetapan(j.settings);
        isiKeputusan(j);
      })
      .catch(function () { gagal('Gangguan sambungan.'); });
  }

  function gagal(msg) {
    document.getElementById('keputusan').innerHTML =
      '<div class="hidden-note" style="color:#b23;border-color:rgba(214,69,69,.4);background:rgba(214,69,69,.06)">' + esc(msg) + '</div>';
  }

  function isiStats(st, status) {
    document.getElementById('sUndi').textContent = st.jumlah_undi;
    document.getElementById('sUndiSub').textContent = st.jumlah_layak + ' kakitangan layak';
    document.getElementById('sPeratus').textContent = st.peratus + '%';
    var el = document.getElementById('sStatus');
    el.textContent = status.buka ? 'DIBUKA' : 'DITUTUP';
    el.className = 'stat__v ' + (status.buka ? 'ok' : 'warn');
    document.getElementById('sStatusSub').textContent = status.teks;
  }

  function isiTetapan(s) {
    document.getElementById('tMula').value = toLocalInput(s.tarikh_mula);
    document.getElementById('tTamat').value = toLocalInput(s.tarikh_tamat);
    document.getElementById('swLock').checked = String(s.status_manual_lock) === '1';
    document.getElementById('swPapar').checked = String(s.hasil_dizahirkan) === '1';
  }

  document.getElementById('btnSimpan').addEventListener('click', function () {
    var btn = this, txt = document.getElementById('simpanText');
    var mula = fromLocalInput(document.getElementById('tMula').value);
    var tamat = fromLocalInput(document.getElementById('tTamat').value);
    if (!mula || !tamat) {
      Swal.fire({ icon:'warning', title:'Tidak Lengkap', text:'Sila isi tarikh mula dan tamat.', confirmButtonColor:'#4860a8' });
      return;
    }
    if (tamat <= mula) {
      Swal.fire({ icon:'warning', title:'Tarikh Tidak Sah', text:'Tarikh tamat mesti selepas tarikh mula.', confirmButtonColor:'#4860a8' });
      return;
    }
    btn.disabled = true; txt.innerHTML = '<span class="btn-x__spin"></span> Menyimpan…';
    fetch('../api/save_settings.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        csrf: CSRF, tarikh_mula: mula, tarikh_tamat: tamat,
        status_manual_lock: document.getElementById('swLock').checked ? 1 : 0,
        hasil_dizahirkan: document.getElementById('swPapar').checked ? 1 : 0
      })
    })
      .then(function(r){ return r.json(); })
      .then(function(j){
        btn.disabled = false; txt.textContent = 'Simpan Tetapan';
        if (j.ok) {
          Swal.fire({ icon:'success', title:'Tersimpan', text:j.mesej, timer:1400, showConfirmButton:false });
          muat();
        } else {
          Swal.fire({ icon:'error', title:'Gagal', text:j.mesej, confirmButtonColor:'#4860a8' });
        }
      })
      .catch(function(){
        btn.disabled = false; txt.textContent = 'Simpan Tetapan';
        Swal.fire({ icon:'error', title:'Ralat Sambungan', text:'Sila cuba lagi.', confirmButtonColor:'#4860a8' });
      });
  });

  function isiKeputusan(j) {
    var box = document.getElementById('keputusan');

    if (!j.settings || String(j.settings.hasil_dizahirkan) !== '1') {
      box.innerHTML = '<div class="hidden-note">Keputusan belum dizahirkan.<br>' +
        'Hidupkan <b>“Zahirkan Keputusan”</b> di bahagian Tetapan untuk melihat keputusan undian.</div>';
      return;
    }
    if (!j.keputusan || !j.keputusan.length) {
      box.innerHTML = '<div class="hidden-note">Tiada data keputusan.</div>';
      return;
    }

    var html = '';
    j.keputusan.forEach(function (p) {
      html += '<div class="res">';
      html += '<div class="res__head">' +
        '<span class="res__kat ' + p.kategori + '">Kategori ' + p.kategori + '</span>' +
        '<span class="res__nama">' + esc(p.nama) + '</span>' +
        (p.seri ? '<span class="res__seri">SERI</span>' : '') +
        '</div>';

      if (!p.calon || !p.calon.length) {
        html += '<div class="res__empty">Tiada undi diterima.</div>';
      } else {
        html += '<ul class="res__list">';
        p.calon.forEach(function (c, i) {
          var cls = 'res__row' + (c.tie ? ' tie' : (i === 0 ? ' top' : ''));
          var rank = c.rank || (i + 1);
          html += '<li class="' + cls + '">' +
            '<span class="res__rank">' + rank + '</span>' +
            '<span class="res__info"><b>' + esc(c.nama) + '</b>' +
              '<span>' + esc(c.cawangan || '') + (c.gred ? ' &middot; ' + esc(c.gred) : '') + '</span></span>' +
            '<span class="res__count">' + c.undi + ' <small>undi</small></span>' +
          '</li>';
        });
        html += '</ul>';
      }
      html += '</div>';
    });
    box.innerHTML = html;
  }

  document.getElementById('btnExport').addEventListener('click', function () {
    window.location.href = '../api/export_excel.php';
  });

  muat();
})();
