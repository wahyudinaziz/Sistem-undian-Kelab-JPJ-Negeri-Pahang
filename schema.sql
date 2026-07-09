-- ============================================================================
-- schema.sql — Skema Pangkalan Data Penuh
-- Sistem Undian Atas Talian KSK JPJ Negeri Pahang 2026
-- ----------------------------------------------------------------------------
-- Repo        : (projek GitHub anda)
-- Database    : iactimsm_staff
-- Charset     : latin1 / latin1_swedish_ci (selaras keseluruhan sistem)
-- ----------------------------------------------------------------------------
-- KANDUNGAN:
--   1. staff              (rujukan — kolum berkaitan sahaja)
--   2. ksk_undi_settings  (tetapan & tempoh undian)
--   3. ksk_undi_positions (10 jawatan: 2 Kategori A + 8 Kategori B)
--   4. ksk_undi_votes     (undian sebenar — anonim)
--   5. ksk_undi_voter_log (log pengundi — audit, berasingan dari undi)
--   6. ksk_undi_admin      (akaun pentadbir tunggal)
--
-- NOTA REKA BENTUK:
--   * Semua table guna latin1 supaya JOIN dengan `staff` tiada isu collation.
--   * `ksk_undi_votes` SENGAJA tiada foreign key ke `ksk_undi_voter_log`
--     — inilah yang menjamin prinsip "undian adalah rahsia".
--   * Script idempotent (IF NOT EXISTS / INSERT IGNORE) — selamat run berulang.
--   * Pada pelayan produksi, table `staff` sudah wujud dengan lebih banyak
--     kolum; `CREATE TABLE IF NOT EXISTS staff` akan dilangkau secara automatik.
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";
SET NAMES latin1;
START TRANSACTION;

-- ----------------------------------------------------------------------------
-- 1. staff — RUJUKAN (kolum berkaitan sistem undian sahaja)
--    Pengundi & calon kedua-duanya diambil dari table ini.
--    status = 2  -> kakitangan aktif & layak
--    gred        -> cth 'KP9','M11','N9','AB10' (nombor diekstrak di PHP)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff` (
  `id`       int(11)      NOT NULL AUTO_INCREMENT,
  `mykad`    varchar(12)  NOT NULL COMMENT 'No. Kad Pengenalan, 12 digit tanpa sengkang',
  `nama`     varchar(150) NOT NULL,
  `gred`     varchar(50)  DEFAULT NULL COMMENT "cth 'KP9','M11','N9'",
  `jawatan`  varchar(255) DEFAULT NULL COMMENT 'Jawatan rasmi JPJ (paparan sahaja)',
  `cawangan` varchar(50)  DEFAULT NULL COMMENT 'Digunakan untuk penapisan calon Kategori B',
  `status`   int(11)      NOT NULL DEFAULT 2 COMMENT '2 = aktif/layak',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mykad` (`mykad`),
  KEY `idx_status` (`status`),
  KEY `idx_cawangan` (`cawangan`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ----------------------------------------------------------------------------
-- 2. ksk_undi_settings — tetapan & tempoh undian (guna 1 baris, id=1)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ksk_undi_settings` (
  `id`                 int(11)      NOT NULL AUTO_INCREMENT,
  `tajuk_undian`       varchar(255) NOT NULL DEFAULT 'Pemilihan AJK Kelab Sukan Dan Kebajikan (KSK) JPJ Negeri Pahang 2026',
  `tarikh_mula`        datetime     NOT NULL,
  `tarikh_tamat`       datetime     NOT NULL,
  `status_manual_lock` tinyint(1)   NOT NULL DEFAULT 0 COMMENT '0=ikut tarikh | 1=admin paksa tutup',
  `hasil_dizahirkan`   tinyint(1)   NOT NULL DEFAULT 0 COMMENT '0=sembunyi keputusan | 1=admin benar papar',
  `updated_at`         datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `ksk_undi_settings` (`id`,`tarikh_mula`,`tarikh_tamat`)
SELECT 1, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY)
WHERE NOT EXISTS (SELECT 1 FROM `ksk_undi_settings` WHERE `id`=1);

-- ----------------------------------------------------------------------------
-- 3. ksk_undi_positions — 10 jawatan tetap
--    min_gred       -> hanya Kategori A (>=9)
--    scope_cawangan -> 1 = calon ditapis ikut cawangan pengundi (Kategori B)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ksk_undi_positions` (
  `id`             int(11)       NOT NULL AUTO_INCREMENT,
  `kod_jawatan`    varchar(50)   NOT NULL,
  `nama_jawatan`   varchar(100)  NOT NULL,
  `kategori`       enum('A','B') NOT NULL,
  `min_gred`       int(11)       DEFAULT NULL,
  `scope_cawangan` tinyint(1)    NOT NULL DEFAULT 0,
  `susunan`        int(11)       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kod_jawatan` (`kod_jawatan`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT IGNORE INTO `ksk_undi_positions`
  (`kod_jawatan`,`nama_jawatan`,`kategori`,`min_gred`,`scope_cawangan`,`susunan`) VALUES
('pengerusi',       'Pengerusi',       'A', 9,    0, 1),
('naib_pengerusi',  'Naib Pengerusi',  'A', 9,    0, 2),
('setiausaha',      'Setiausaha',      'B', NULL, 1, 3),
('bendahari',       'Bendahari',       'B', NULL, 1, 4),
('ajk_kebajikan',   'AJK Kebajikan',   'B', NULL, 1, 5),
('ajk_rekreasi',    'AJK Rekreasi',    'B', NULL, 1, 6),
('ajk_sukan',       'AJK Sukan',       'B', NULL, 1, 7),
('ajk_agama',       'AJK Agama',       'B', NULL, 1, 8),
('ajk_seni_budaya', 'AJK Seni Budaya', 'B', NULL, 1, 9),
('ajk_ekonomi',     'AJK Ekonomi',     'B', NULL, 1, 10);

-- ----------------------------------------------------------------------------
-- 4. ksk_undi_votes — undian SEBENAR (anonim, tiada lajur identiti)
--    Index gabungan mempercepat kiraan GROUP BY keputusan.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ksk_undi_votes` (
  `id`                 int(11)  NOT NULL AUTO_INCREMENT,
  `position_id`        int(11)  NOT NULL COMMENT 'rujuk ksk_undi_positions.id',
  `candidate_staff_id` int(11)  NOT NULL COMMENT 'rujuk staff.id',
  `created_at`         datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kira` (`position_id`,`candidate_staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ----------------------------------------------------------------------------
-- 5. ksk_undi_voter_log — log SIAPA sudah mengundi (audit + elak undi 2x)
--    UNIQUE(mykad) menguatkuasakan "sekali sahaja" & mengunci race-condition.
--    BERASINGAN penuh dari ksk_undi_votes -> kerahsiaan undi terpelihara.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ksk_undi_voter_log` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `mykad`      varchar(12)  NOT NULL,
  `nama`       varchar(100) NOT NULL,
  `cawangan`   varchar(50)  NOT NULL,
  `ip_address` varchar(45)  DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `voted_at`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mykad` (`mykad`),
  KEY `idx_voted_at` (`voted_at`),
  KEY `idx_cawangan` (`cawangan`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ----------------------------------------------------------------------------
-- 6. ksk_undi_admin — akaun pentadbir tunggal
--    Default: kskjpjpahang / kskpahang2026  (bcrypt)
--    DISARANKAN tukar kata laluan selepas log masuk kali pertama.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ksk_undi_admin` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `username`   varchar(50)  NOT NULL,
  `password`   varchar(255) NOT NULL,
  `last_login` datetime     DEFAULT NULL,
  `created_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT IGNORE INTO `ksk_undi_admin` (`username`,`password`) VALUES
('adminundi', '$2a$12$chpm7ykO8.1y6KeeHI/JPuDZAt2uFcgGK8o3ZD1GD1ichoxzohggq');

COMMIT;

-- ============================================================================
-- (PILIHAN) DATA CONTOH UNTUK UJIAN / DEMO
-- ----------------------------------------------------------------------------
-- Buang komen blok di bawah HANYA untuk persekitaran pembangunan/demo.
-- JANGAN jalankan pada pelayan produksi (data staff sebenar sudah wujud).
-- ============================================================================
/*
INSERT IGNORE INTO `staff` (`mykad`,`nama`,`gred`,`jawatan`,`cawangan`,`status`) VALUES
('900101015501','Ahmad Faizal bin Abdullah','M11','Pengarah',          'Kuantan',  2),
('880202025502','Rosli bin Hassan',          'M9', 'Timbalan Pengarah', 'Kuantan',  2),
('910303035503','Zainab binti Omar',         'N11','Penolong Pengarah', 'Temerloh', 2),
('920404045504','Ismail bin Yusof',          'KP9','Pegawai',           'Bentong',  2),
('930505055505','Nurul Ain binti Kamal',     'N9', 'Pembantu Tadbir',   'Kuantan',  2),
('940606065506','Hafiz bin Ramli',           'N11','Pembantu Tadbir',   'Kuantan',  2),
('950707075507','Siti Aminah binti Zakaria', 'N9', 'Pembantu Tadbir',   'Kuantan',  2),
('960808085508','Kamal bin Aziz',            'KP10','Pemandu',          'Raub',     2),
('970909095509','Farah binti Lee',           'AB9','Juruteknik',        'Jerantut', 2),
('981010105510','Lina binti Chong',          'N9', 'Pembantu Tadbir',   'Kuantan',  2);
*/

-- ============================================================================
-- SELESAI.
-- Langkah seterusnya:
--   1. Log masuk admin (kskjpjpahang / kskpahang2026)
--   2. Tetapkan tarikh_mula & tarikh_tamat sebenar di halaman Settings
--   3. Sistem auto-lock selepas tarikh_tamat berlalu
-- ============================================================================
