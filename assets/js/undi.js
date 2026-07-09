(function ($) {
  'use strict';

  var CSRF = document.getElementById('csrf').value;
  var JAWATAN = [];          // data dari server
  var pilihan = {};          // { position_id: candidate_id }
  var JUM = 0;               // jumlah jawatan

  (function () {
    var el = document.getElementById('baki');
    if (!el) return;
    var tamat = new Date(el.dataset.tamat.replace(' ', 'T')).getTime();
    var p = function (n) { return (n < 10 ? '0' : '') + n; };
    function tik() {
      var j = tamat - Date.now();
      if (j <= 0) {
        el.textContent = 'TAMAT';
        Swal.fire({
          icon: 'warning', title: 'Tempoh Tamat',
          text: 'Masa undian telah tamat. Anda akan dialihkan.',
          allowOutsideClick: false, confirmButtonColor: '#4860a8'
        }).then(function () { location.href = 'index.php'; });
        clearInterval(t);
        return;
      }
      var h = Math.floor(j / 3600000), m = Math.floor((j % 3600000) / 60000), s = Math.floor((j % 60000) / 1000);
      el.textContent = p(h) + ':' + p(m) + ':' + p(s);
      if (j < 300000) el.classList.add('warn');  // < 5 minit
    }
    tik(); var t = setInterval(tik, 1000);
  })();

  fetch('api/get_candidates.php', { headers: { 'Accept': 'application/json' } })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      if (!j.ok) {
        if (j.tutup) { location.href = 'index.php'; return; }
        gagalMuat(j.mesej || 'Gagal memuatkan calon.');
        return;
      }
      JAWATAN = j.jawatan;
      JUM = JAWATAN.length;
      binaBorang();
    })
    .catch(function () { gagalMuat('Gangguan sambungan semasa memuatkan calon.'); });

  function gagalMuat(msg) {
    document.getElementById('borang').innerHTML =
      '<div class="note" style="background:rgba(214,69,69,.08);border-color:rgba(214,69,69,.3);color:#b23">' +
      msg + ' <a href="index.php" style="color:#b23;font-weight:700">Kembali</a></div>';
  }

  function binaBorang() {
    var A = JAWATAN.filter(function (x) { return x.kategori === 'A'; });
    var B = JAWATAN.filter(function (x) { return x.kategori === 'B'; });
    var html = '';

    html += seksyen('Kategori A', 'Jawatan Utama',
      'Calon terdiri daripada kakitangan gred 9 ke atas dari semua cawangan.', A, 1);
    html += seksyen('Kategori B', 'Ahli Jawatankuasa',
      'Calon terdiri daripada kakitangan cawangan anda sahaja.', B, A.length + 1);

    document.getElementById('borang').innerHTML = html;

    JAWATAN.forEach(function (j) {
      var $sel = $('#sel_' + j.id);
      $sel.select2({
        placeholder: 'Pilih calon…',
        allowClear: true,
        width: '100%',
        language: {
          noResults: function () { return 'Tiada calon dijumpai'; },
          searching: function () { return 'Mencari…'; }
        },
        templateResult: fmtOption
      });
      $sel.on('change', function () {
        var val = this.value ? parseInt(this.value, 10) : null;
        if (val) pilihan[j.id] = val; else delete pilihan[j.id];
        $('#pos_' + j.id).toggleClass('filled', !!val);
        kemasDisable();
        kemasProgress();
      });
    });

    document.getElementById('progressBar').style.display = 'block';
    document.getElementById('btnHantar').addEventListener('click', semakHantar);
    kemasProgress();
  }

  function seksyen(tag, tajuk, desc, senarai, mulaNo) {
    var h = '<section class="sect">' +
      '<span class="sect__tag">' + tag + '</span>' +
      '<h2 class="sect__title">' + tajuk + '</h2>' +
      '<p class="sect__desc">' + desc + '</p></section>';
    senarai.forEach(function (j, i) {
      h += kadJawatan(j, mulaNo + i);
    });
    return h;
  }

  function kadJawatan(j, no) {
    var opts = '<option></option>';
    j.calon.forEach(function (c) {
      opts += '<option value="' + c.id + '" data-gred="' + esc(c.gred) + '">' +
              esc(c.nama) + '</option>';
    });
    var kosong = j.calon.length === 0
      ? '<p class="field__hint" style="color:#b23">Tiada calon layak bagi jawatan ini.</p>' : '';
    return '<div class="pos" id="pos_' + j.id + '">' +
      '<div class="pos__head">' +
        '<span class="pos__no">' + no + '</span>' +
        '<span class="pos__nama">' + esc(j.nama) + '</span>' +
        '<svg class="pos__check" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
      '</div>' +
      '<select id="sel_' + j.id + '" data-pos="' + j.id + '">' + opts + '</select>' +
      kosong +
    '</div>';
  }


  function fmtOption(o) {
    if (!o.id) return o.text;
    var g = $(o.element).data('gred');
    var $r = $('<span>' + o.text + (g ? ' <span class="opt-gred">(' + g + ')</span>' : '') + '</span>');
    return $r;
  }

  function kemasDisable() {
    var dipilih = {};  // candidate_id -> position_id pemilik
    Object.keys(pilihan).forEach(function (pid) { dipilih[pilihan[pid]] = pid; });

    JAWATAN.forEach(function (j) {
      var sel = document.getElementById('sel_' + j.id);
      var berubah = false;
      Array.prototype.forEach.call(sel.options, function (opt) {
        if (!opt.value) return;
        var cid = parseInt(opt.value, 10);
        var patutDisable = dipilih[cid] !== undefined && dipilih[cid] != j.id;
        if (opt.disabled !== patutDisable) { opt.disabled = patutDisable; berubah = true; }
      });
      if (berubah) $('#sel_' + j.id).trigger('change.select2');
    });
  }

  function kemasProgress() {
    var n = Object.keys(pilihan).length;
    document.getElementById('pgCount').textContent = n;
    document.getElementById('pgFill').style.width = (n / JUM * 100) + '%';
    document.getElementById('btnHantar').disabled = (n !== JUM);
  }

  function semakHantar() {
    if (Object.keys(pilihan).length !== JUM) return;

    var rows = '';
    JAWATAN.forEach(function (j) {
      var cid = pilihan[j.id];
      var c = j.calon.find(function (x) { return x.id === cid; });
      rows += '<tr><td style="text-align:left;color:#5a6b86;padding:5px 8px">' + esc(j.nama) +
              '</td><td style="text-align:left;font-weight:700;color:#16223d;padding:5px 8px">' +
              esc(c ? c.nama : '-') + '</td></tr>';
    });

    Swal.fire({
      title: 'Sahkan Undian Anda',
      html: '<div style="max-height:50vh;overflow:auto"><table style="width:100%;border-collapse:collapse;font-size:.85rem">' +
            rows + '</table></div>' +
            '<p style="margin:14px 0 0;font-size:.8rem;color:#b23">Undian tidak boleh diubah selepas dihantar.</p>',
      showCancelButton: true,
      confirmButtonText: 'Ya, Hantar Undian',
      cancelButtonText: 'Semak Semula',
      confirmButtonColor: '#1f9d68',
      cancelButtonColor: '#7a8699',
      width: 520,
      reverseButtons: true
    }).then(function (res) {
      if (res.isConfirmed) hantar();
    });
  }

  function hantar() {
    Swal.fire({ title: 'Menghantar…', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });

    fetch('api/submit_vote.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf: CSRF, pilihan: pilihan })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j.ok) {
          Swal.fire({
            icon: 'success', title: 'Undian Berjaya',
            text: j.mesej, confirmButtonText: 'Selesai',
            confirmButtonColor: '#d8a818', allowOutsideClick: false
          }).then(function () { location.href = 'index.php'; });
        } else {
          Swal.fire({
            icon: (j.sudah || j.tutup) ? 'info' : 'error',
            title: 'Tidak Berjaya', text: j.mesej,
            confirmButtonColor: '#4860a8'
          }).then(function () { if (j.sudah || j.tutup) location.href = 'index.php'; });
        }
      })
      .catch(function () {
        Swal.fire({ icon: 'error', title: 'Ralat Sambungan', text: 'Sila cuba hantar semula.', confirmButtonColor: '#4860a8' });
      });
  }

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

})(jQuery);
