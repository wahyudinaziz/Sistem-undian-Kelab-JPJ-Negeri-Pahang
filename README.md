# Sistem Undian Atas Talian — KSK JPJ Negeri Pahang 2026

Sistem undian atas talian rasmi untuk **Pemilihan Ahli Jawatankuasa Kelab Sukan & Kebajikan (KSK) JPJ Negeri Pahang**. Dibangunkan dengan **PHP (PDO) + MySQL**, antara muka **mobile-first**, dan reka bentuk mementingkan **keselamatan** serta **kerahsiaan undi**.

> Projek oleh **[wahyudin.dev](https://wahyudin.dev/)** untuk Kelab Sukan & Kebajikan JPJ Negeri Pahang



![PHP](https://img.shields.io/badge/PHP-PDO-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-latin1-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
![Lesen](https://img.shields.io/badge/Lesen-CC%20BY--NC%204.0-lightgrey)

---

## Kandungan

- [Kegunaan](#-kegunaan)
- [Ciri Utama](#-ciri-utama)
- [Susunan Teknologi](#-susunan-teknologi)
- [Struktur Projek](#-struktur-projek)
- [Prasyarat](#-prasyarat)
- [Pemasangan](#-pemasangan)
- [Konfigurasi Pangkalan Data](#-konfigurasi-pangkalan-data)
- [Flow Kegunaan](#-flow-kegunaan)
- [Keselamatan & Kerahsiaan](#-keselamatan--kerahsiaan)
- [Lesen](#-lesen)

---

## Kegunaan

Sistem ini membolehkan kakitangan JPJ Negeri Pahang mengundi calon bagi **10 jawatan** dalam dua kategori:

| Kategori | Jawatan | Syarat Calon |
|----------|---------|--------------|
| **A** | Pengerusi, Naib Pengerusi | Gred **9 ke atas**, semua cawangan |
| **B** | Setiausaha, Bendahari, AJK Kebajikan, AJK Rekreasi, AJK Sukan, AJK Agama, AJK Seni Budaya, AJK Ekonomi | Kakitangan **cawangan sendiri** sahaja |

**Prinsip teras:**
- Setiap pengundi hanya boleh mengundi **sekali sahaja**.
- Pengundi **tidak boleh** mengundi diri sendiri.
- Seorang calon **tidak boleh** dipilih untuk lebih dari satu jawatan (dalam sesi pengundi yang sama).
- **Undian adalah SULIT** — tiada kaitan boleh dibuat antara pengundi dan pilihan mereka.

---

## Ciri Utama

- **Mobile-first** — serasi semua pelayar & OS (Android, iOS, desktop)
- **Countdown masa nyata** dengan auto-lock apabila tempoh tamat
- **Select2** untuk pemilihan calon yang mudah dicari
- **Cross-disable** — calon yang telah dipilih di-disable di dropdown lain
- **Panel pentadbir** — tetapan tempoh, kunci manual, zahirkan keputusan
- **Keputusan** — 5 tertinggi setiap jawatan; papar semua nama jika seri
- **Export Excel** (.xlsx) berbilang tab dengan statistik & pecahan cawangan
- **Header keselamatan** penuh (CSP, HSTS, CSRF, XSS protection)
- **Semua pustaka self-host** — tiada pergantungan CDN luar

---

## Susunan Teknologi

| Lapisan | Teknologi |
|---------|-----------|
| Backend | PHP 7.4+ (PDO), MySQL / MariaDB |
| Frontend | Bootstrap 5.3.8, Select2 4.0.13, SweetAlert2 11.x, jQuery 3.7.1 |
| Export | Penjana `.xlsx` tulen (ZipArchive + OOXML) — **tanpa Composer** |
| Pelayan | Apache / LiteSpeed (cPanel) |

---

## Struktur Projek

```
undi2026/
├── index.php                 # Skrin masuk pengundi (No. MyKad + countdown)
├── undi.php                  # Borang undian (10 Select2)
├── .htaccess                 # Keselamatan + paksa HTTPS (root)
├── download_vendor.sh        # Skrip muat turun pustaka self-host
│
├── config/
│   ├── db.php                # Sambungan PDO + header + fungsi bantuan
│   ├── admin_guard.php       # Pelindung sesi pentadbir
│   └── .htaccess             # Tolak semua akses web ke folder ini
│
├── api/
│   ├── check_mykad.php       # Semak kelayakan pengundi
│   ├── get_candidates.php    # Senarai calon ikut kategori/cawangan
│   ├── submit_vote.php       # Rekod undian (transaction)
│   ├── admin_login.php       # Log masuk pentadbir
│   ├── get_results.php       # Statistik + keputusan
│   ├── save_settings.php     # Simpan tetapan undian
│   ├── export_excel.php      # Jana laporan .xlsx
│   └── index.php             # 403 (halang listing)
│
├── admin/
│   ├── index.php             # Redirect ke login/dashboard
│   ├── login.php             # Skrin log masuk
│   ├── dashboard.php         # Papan pemuka (statistik, tetapan, keputusan)
│   └── logout.php
│
├── assets/
│   ├── css/                  # app.css, undi.css, admin.css
│   ├── js/                   # index.js, undi.js, admin_login.js, dashboard.js
│   ├── img/                  # logo.png, og-image.jpg
│   └── vendor/               # Pustaka self-host (diisi oleh download_vendor.sh)
│
├── logs/                     # Log ralat PHP (dilindungi .htaccess)
│
└── database/
    └── schema.sql            # Skema penuh semua table
```

---

## Prasyarat

- PHP **7.4** atau lebih tinggi (dengan sambungan `pdo_mysql`, `zip`, `mbstring`)
- MySQL **5.7+** atau MariaDB **10.x**
- Pelayan web Apache atau LiteSpeed dengan `mod_rewrite` & `mod_headers`
- SSL/HTTPS pada domain

---

## Pemasangan

### 1. Klon repositori
```bash
git clone https://github.com/<username>/<repo>.git
cd <repo>
```

### 2. Import skema pangkalan data
Import `database/schema.sql` melalui **phpMyAdmin** atau CLI:
```bash
mysql -u <user> -p <nama_database> < database/schema.sql
```

### 3. Muat turun pustaka self-host
```bash
bash download_vendor.sh
```
Skrip ini memuat turun Bootstrap, jQuery, Select2 & SweetAlert2 ke `assets/vendor/`.

### 4. Konfigurasi sambungan pangkalan data
Edit fail **`config/db.php`** (lihat bahagian di bawah).

### 5. Naikkan ke pelayan
Muat naik keseluruhan folder ke direktori sasaran (cth `/undi2026`).
Pastikan fail tersembunyi (`.htaccess`) **turut dimuat naik** — hidupkan *"show hidden files"* dalam klien FTP anda.

### 6. Log masuk pentadbir
Akses `.../admin/`, log masuk, kemudian **tetapkan tarikh mula & tamat** undian di halaman Dashboard.

> **Kelayakan pentadbir lalai:** `adminundi` / `@dminKSK!`
> Disarankan menukar kata laluan selepas log masuk kali pertama.

---

## Konfigurasi Pangkalan Data

Buka `config/db.php` dan kemas kini pemalar (constant) berikut mengikut persekitaran pelayan anda:

```php
const DB_HOST = '127.0.0.1';
const DB_NAME = 'DATABASE_NAME'; // TODO: ganti dengan nama DB sebenar
const DB_USER = 'DATABASE_USER'; // TODO: ganti dengan user DB sebenar
const DB_PASS = 'DATABASE_PASS'; // TODO: ganti dengan kata laluan DB sebenar
const DB_CHARSET = 'latin1';
```

| Pemalar | Penerangan |
|---------|------------|
| `DB_HOST` | Alamat pelayan pangkalan data. Biasanya `localhost` untuk hosting cPanel/shared. |
| `DB_NAME` | Nama pangkalan data anda (cth: `iactimsm_staff`). |
| `DB_USER` | Nama pengguna MySQL yang diberi kebenaran akses ke pangkalan data tersebut. |
| `DB_PASS` | Kata laluan bagi pengguna MySQL di atas. |
| `DB_CHARSET` | **Kekalkan `latin1`.** Ia diselaraskan dengan set aksara table `staff` supaya operasi JOIN tidak menghadapi isu *collation mismatch*. |

> **Penting:** `config/db.php` mengandungi kredensial pangkalan data. Fail ini dilindungi daripada akses web oleh `config/.htaccess`, tetapi **pastikan anda tidak mendedahkan kredensial sebenar** apabila berkongsi kod secara awam.

---

## Flow Kegunaan

### Pengundi

```
1. Buka https://your-url/undi2026
   └─ Countdown memaparkan baki masa undian

2. Masukkan No. MyKad (12 digit)
   └─ Sistem menyemak:
      ✓ MyKad wujud dalam rekod kakitangan
      ✓ Status = 2 (aktif/layak)
      ✓ Belum pernah mengundi

3. Skrin undian dipaparkan
   ├─ Kategori A: pilih Pengerusi & Naib Pengerusi (gred 9+)
   └─ Kategori B: pilih 8 AJK (calon cawangan sendiri)
   └─ Calon yang dipilih di-disable di dropdown lain

4. Semak ringkasan pilihan → Sahkan & Hantar

5. Undian direkod (SULIT) + log pengundi disimpan (audit)
   └─ Tidak boleh mengundi lagi
```

### Pentadbir

```
1. Log masuk di .../admin/

2. Papan Pemuka:
   ├─ Statistik: jumlah mengundi, kadar penyertaan, status
   ├─ Tetapan:  tarikh mula/tamat, kunci manual, zahirkan keputusan
   └─ Keputusan: 5 tertinggi setiap jawatan (semua nama jika seri)

3. Export Excel (.xlsx):
   ├─ Tab Statistik    — penyertaan + pecahan cawangan
   ├─ Tab Kategori A   — Pengerusi & Naib Pengerusi
   └─ Tab Kategori B   — 8 jawatan AJK
```

> **Pengendalian seri:** Jika berlaku undian seri, sistem memaparkan **semua nama** yang seri beserta jumlah undian. Undian semula bagi menentukan pemenang akan dibuat secara **manual dalam Mesyuarat Agung Tahunan** akan datang.

---

## Keselamatan & Kerahsiaan

- **Kerahsiaan undi:** Table `ksk_undi_votes` (pilihan sebenar) **berasingan sepenuhnya** daripada `ksk_undi_voter_log` (identiti pengundi) — tiada foreign key atau lajur perantaraan yang menghubungkan kedua-duanya. Susunan rekod undi juga diacak (`shuffle`) untuk menghapuskan sebarang korelasi tertib.
- **Sekali sahaja:** Dikuatkuasakan oleh `UNIQUE(mykad)` pada `voter_log` (turut mengunci *race-condition*).
- **CSRF token** pada semua permintaan POST.
- **Header keselamatan:** CSP (self-host), HSTS, X-Frame-Options, X-Content-Type-Options.
- **Validasi sisi pelayan:** Semua peraturan undian disemak semula di backend (tidak mempercayai input frontend).
- **Kata laluan pentadbir** disimpan sebagai *hash* bcrypt; log masuk mempunyai had cubaan (*rate limit*).
- **Semua pustaka self-host** — tiada kebergantungan CDN luar (mematuhi CSP & SRI).

---

## Lesen

Projek ini dilesenkan di bawah **[Creative Commons Attribution-NonCommercial 4.0 International (CC BY-NC 4.0)](https://creativecommons.org/licenses/by-nc/4.0/)**.

Anda **dibenarkan** untuk:
- **Berkongsi** — menyalin dan mengedar semula bahan ini dalam apa jua medium atau format
- **Menyesuaikan** — menggubah, mengubah suai, dan membina di atas bahan ini

Dengan syarat berikut:
- **Atribusi (BY)** — Anda mesti memberikan kredit yang sewajarnya dan menyatakan sebarang perubahan yang dibuat.
- **Bukan Komersial (NC)** — Anda **tidak boleh** menggunakan bahan ini untuk tujuan komersial.

> Ringkasan ini bukan pengganti lesen penuh. Sila rujuk teks lesen rasmi di pautan di atas.

---

<p align="center">
  <strong>Kelab Sukan &amp; Kebajikan JPJ Negeri Pahang</strong><br>
  Sistem Undian Atas Talian &middot; 2026
</p>

<p align="center">
  Projek oleh <a href="https://wahyudin.dev/"><strong>wahyudin.dev</strong></a> untuk Kelab Sukan &amp; Kebajikan JPJ Negeri Pahang
</p>