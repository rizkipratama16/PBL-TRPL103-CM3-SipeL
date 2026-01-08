-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for data_sipel
CREATE DATABASE IF NOT EXISTS `data_sipel` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `data_sipel`;

-- Dumping structure for table data_sipel.hasil_perhitungan
CREATE TABLE IF NOT EXISTS `hasil_perhitungan` (
  `id_hasil` int NOT NULL AUTO_INCREMENT,
  `id_periode` int NOT NULL,
  `id_wilayah` int DEFAULT NULL,
  `id_calon` int NOT NULL,
  `total_suara` int NOT NULL DEFAULT '0',
  `last_rekap` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_hasil`),
  UNIQUE KEY `uniq_rekap` (`id_periode`,`id_wilayah`,`id_calon`),
  KEY `idx_hasil_calon` (`id_calon`),
  KEY `fk_hasil_wilayah` (`id_wilayah`),
  CONSTRAINT `fk_hasil_calon` FOREIGN KEY (`id_calon`) REFERENCES `kandidat` (`id_calon`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hasil_periode` FOREIGN KEY (`id_periode`) REFERENCES `periode` (`id_periode`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hasil_wilayah` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id_wilayah`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table data_sipel.hasil_perhitungan: ~0 rows (approximately)

-- Dumping structure for table data_sipel.jadwal
CREATE TABLE IF NOT EXISTS `jadwal` (
  `id_jadwal` int NOT NULL AUTO_INCREMENT,
  `id_periode` int NOT NULL,
  `id_wilayah` int DEFAULT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `id_panitia` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `keterangan` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id_jadwal`),
  KEY `idx_jadwal_periode` (`id_periode`),
  KEY `idx_jadwal_wilayah` (`id_wilayah`),
  KEY `idx_jadwal_admin` (`id_panitia`) USING BTREE,
  CONSTRAINT `FK3 id_panitia` FOREIGN KEY (`id_panitia`) REFERENCES `panitia` (`id_panitia`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jadwal_periode` FOREIGN KEY (`id_periode`) REFERENCES `periode` (`id_periode`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_jadwal_wilayah` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id_wilayah`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table data_sipel.jadwal: ~4 rows (approximately)
INSERT INTO `jadwal` (`id_jadwal`, `id_periode`, `id_wilayah`, `tanggal_mulai`, `tanggal_selesai`, `id_panitia`, `keterangan`) VALUES
	(1, 1, 1, '2025-11-30 08:00:00', '2025-12-14 16:00:00', '1', 'Pemilu ketua RT 01 2025'),
	(2, 1, 2, '2025-11-30 08:00:00', '2025-12-14 16:00:00', '2', 'Pemilu ketua RT 02 2025'),
	(3, 1, 3, '2025-11-30 08:00:00', '2026-01-16 00:00:00', '3', 'Pemilu ketua RT 03 2025'),
	(4, 1, 4, '2025-11-30 08:00:00', '2026-01-01 16:00:00', '1', 'Pemilu Ketua RW 01 2025');

-- Dumping structure for table data_sipel.kandidat
CREATE TABLE IF NOT EXISTS `kandidat` (
  `id_calon` int NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `rt` varchar(5) DEFAULT NULL,
  `rw` varchar(5) DEFAULT NULL,
  `foto` varchar(150) DEFAULT NULL,
  `visi` text,
  `misi` text,
  `id_periode` int DEFAULT NULL,
  PRIMARY KEY (`id_calon`),
  KEY `kandidat_periode` (`id_periode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table data_sipel.kandidat: ~12 rows (approximately)
INSERT INTO `kandidat` (`id_calon`, `nama`, `rt`, `rw`, `foto`, `visi`, `misi`, `id_periode`) VALUES
	(1, 'Malio', '01', '01', 'rt01_calon1.jpg', 'Mewujudkan RT 01 yang aman, rukun, dan saling peduli.', 'Mengaktifkan ronda malam dan sistem lapor cepat jika ada kejadian.\r\nMengadakan pertemuan warga rutin untuk musyawarah lingkungan.\r\nMembentuk grup komunikasi RT untuk menyebarkan informasi penting.\r\nMendorong budaya saling kenal dan saling bantu antar tetangga.', 1),
	(2, 'Athala', '01', '01', 'rt01_calon2.jpg', 'Menciptakan RT 01 yang bersih, sehat, dan nyaman dihuni.', 'Menjadwalkan kerja bakti rutin minimal sebulan sekali.\r\nMenata tempat pembuangan sampah dan mengajak warga buang sampah tertib.\r\nMenggalakkan pemilahan sampah sederhana di rumah masing-masing.\r\nBerkoordinasi dengan pihak terkait untuk penyemprotan fogging bila diperlukan', 1),
	(3, 'Alvaro', '01', '01', 'rt01_calon3.jpg', 'Menjadikan RT 01 sebagai lingkungan yang ramah anak dan ramah lansia.', 'Mengadakan kegiatan bermain dan belajar untuk anak-anak.\r\nMembuat program kunjungan atau perhatian khusus untuk warga lansia dan sakit.\r\nMengupayakan area yang lebih aman bagi anak (mengurangi kendaraan ngebut).\r\nMengajak warga ikut serta dalam kegiatan sosial untuk membantu yang membutuhkan.', 1),
	(4, 'Kenzie', '02', '01', 'rt02_calon1.jpg', 'RT 02 yang tertib administrasi dan transparan dalam pengelolaan.', 'Melakukan pendataan ulang warga agar data selalu akurat dan rapi.\r\nMenyusun laporan keuangan RT yang jelas dan dapat diakses warga.\r\nMengumumkan agenda dan hasil rapat RT secara terbuka.\r\nMenyediakan kotak saran untuk menampung aspirasi warga.', 1),
	(5, 'Anand', '02', '01', 'rt02_calon2.jpg', 'Mendorong partisipasi aktif warga dalam setiap kegiatan RT 02.', 'Mengajak warga terlibat di kerja bakti, rapat, dan kegiatan sosial.\r\nMembuat jadwal piket kebersihan atau keamanan yang disepakati bersama.\r\nMemberi apresiasi sederhana bagi warga atau kelompok yang aktif.\r\nMenghidupkan kembali kegiatan keagamaan atau keakraban di lingkungan.', 1),
	(6, 'Arthur', '02', '01', 'rt02_calon3.jpg', 'Menjadikan RT 02 lebih tertata, aman, dan nyaman.', 'Menata parkir kendaraan agar tidak mengganggu akses jalan.\nMengusulkan perbaikan jalan, saluran air, dan fasilitas umum ke pihak terkait.\nMemasang atau memperbaiki penerangan jalan di titik yang masih gelap.\nMembangun koordinasi dengan petugas keamanan lingkungan atau linmas.', 1),
	(7, 'Zafran', '03', '01', 'rt03_calon1.jpg', 'Meningkatkan kekompakan dan rasa kekeluargaan warga RT 03.', 'Mengadakan kegiatan kumpul warga seperti halal bihalal atau makan bersama.\r\nMembentuk panitia kecil untuk mengelola acara hari besar nasional atau keagamaan.\r\nMenyelesaikan masalah antarwarga melalui musyawarah yang adil.\r\nMendorong kolaborasi antarwarga dalam setiap program lingkungan.', 1),
	(8, 'Ziko', '03', '01', 'rt03_calon2.jpg', 'RT 03 yang responsif, cepat tanggap, dan peduli pada warganya.', 'Menyediakan nomor kontak RT yang mudah dihubungi kapan saja.\nMengkoordinasikan bantuan cepat jika ada warga yang kena musibah.\nMengawal setiap laporan warga ke pihak kelurahan atau instansi terkait.\nMenyusun sistem pendataan bantuan sosial yang lebih teratur.', 1),
	(9, 'Valerio', '03', '01', 'rt03_calon3.jpg', 'Menjaga ketertiban dan keamanan RT 03 secara berkelanjutan.', 'Mensosialisasikan aturan jam malam dan ketertiban lingkungan.\nMendorong warga melaporkan tamu menginap atau aktivitas mencurigakan.\nBekerja sama dengan keamanan lingkungan untuk patroli berkala.\nMengedukasi warga tentang pencegahan kriminalitas dan narkoba.', 1),
	(10, 'Aiden', '', '01', 'rw01_calon1.jpg', 'Menyatukan seluruh RT di bawah RW 01 agar lebih kompak dan terkoordinasi.', 'Mengadakan rapat koordinasi rutin dengan para ketua RT.\nMenyusun program RW yang selaras dengan program tiap RT.\nMenjadi penghubung aktif antara RT dan kelurahan.\nMembuat forum komunikasi RW untuk membahas isu bersama.', 1),
	(11, 'Elgard', NULL, '01', 'rw01_calon2.jpg', 'RW 01 yang transparan, terbuka, dan dekat dengan warga.', 'Menyampaikan informasi dari kelurahan dan pemerintah secara jelas ke warga.\nMenyusun laporan kegiatan dan penggunaan dana RW secara berkala.\nMenampung saran dan keluhan warga melalui media yang mudah diakses.\nMengupayakan pelayanan administrasi yang cepat dan tidak berbelit.', 1),
	(12, 'Irsyad', NULL, '01', 'rw01_calon3.jpg', 'Mewujudkan RW 01 yang maju, tertib, dan berdaya saing di tingkat kelurahan.', 'Mengembangkan program pemberdayaan ekonomi warga (UMKM, bazar, pelatihan).\nMengikutsertakan RW dalam lomba atau program tingkat kelurahan/kota.\nMendorong peningkatan fasilitas umum dengan mengajukan proposal resmi.\nMenjaga nama baik lingkungan dengan membangun budaya disiplin dan sopan santun.', 1);

-- Dumping structure for table data_sipel.panitia
CREATE TABLE IF NOT EXISTS `panitia` (
  `id_panitia` varchar(20) NOT NULL,
  `Nama` varchar(100) NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id_panitia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AVG_ROW_LENGTH=11;

-- Dumping data for table data_sipel.panitia: ~6 rows (approximately)
INSERT INTO `panitia` (`id_panitia`, `Nama`, `username`, `password`) VALUES
	('1', 'Rizki Septi Pratama', '4342511068', '4342511068'),
	('2', 'Nur Aini Siti Soleha', '4342511071', '4342511071'),
	('3', 'Remitha Dwi Putri S', '4342511077', '4342511077'),
	('4', 'Sherly Andini', '4342511081', '4342511081'),
	('5', 'rizka', '434251100', '434251100'),
	('6', 'rizkkkkkkkkkk', '434251111', '434251111');

-- Dumping structure for table data_sipel.periode
CREATE TABLE IF NOT EXISTS `periode` (
  `id_periode` int NOT NULL AUTO_INCREMENT,
  `nama_periode` varchar(100) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'nonaktif',
  PRIMARY KEY (`id_periode`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table data_sipel.periode: ~0 rows (approximately)
INSERT INTO `periode` (`id_periode`, `nama_periode`, `tanggal_mulai`, `tanggal_selesai`, `status`) VALUES
	(1, 'Pemilihan RT/RW 2025', '2025-11-30', '2026-01-01', 'aktif');

-- Dumping structure for table data_sipel.rekap_sementara
CREATE TABLE IF NOT EXISTS `rekap_sementara` (
  `id_rekap` int NOT NULL AUTO_INCREMENT,
  `id_periode` int NOT NULL,
  `id_wilayah` int DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rekap`),
  KEY `idx_rekap_periode` (`id_periode`),
  KEY `idx_rekap_wilayah` (`id_wilayah`),
  CONSTRAINT `fk_rekap_periode` FOREIGN KEY (`id_periode`) REFERENCES `periode` (`id_periode`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rekap_wilayah` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id_wilayah`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table data_sipel.rekap_sementara: ~0 rows (approximately)

-- Dumping structure for table data_sipel.token_pemilih
CREATE TABLE IF NOT EXISTS `token_pemilih` (
  `id_token` int NOT NULL AUTO_INCREMENT,
  `nik` varchar(20) NOT NULL,
  `token` varchar(100) NOT NULL,
  `status` enum('belum_dipakai','sudah_dipakai') NOT NULL DEFAULT 'belum_dipakai',
  `dibuat_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dipakai_pada` datetime DEFAULT NULL,
  PRIMARY KEY (`id_token`),
  UNIQUE KEY `uniq_token` (`token`),
  KEY `idx_token_nik` (`nik`),
  CONSTRAINT `fk_token_warga` FOREIGN KEY (`nik`) REFERENCES `warga` (`nik`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table data_sipel.token_pemilih: ~0 rows (approximately)

-- Dumping structure for table data_sipel.voting
CREATE TABLE IF NOT EXISTS `voting` (
  `id_voting` int NOT NULL,
  `nik` varchar(50) NOT NULL DEFAULT '',
  `id_calon` int NOT NULL,
  `id_periode` int NOT NULL,
  `waktu_pilih` datetime NOT NULL,
  PRIMARY KEY (`id_voting`),
  KEY `FK1 id_calon` (`id_calon`),
  KEY `FK2 id_periode` (`id_periode`),
  KEY `FK3 nik` (`nik`),
  CONSTRAINT `FK1 id_calon` FOREIGN KEY (`id_calon`) REFERENCES `kandidat` (`id_calon`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK3 nik` FOREIGN KEY (`nik`) REFERENCES `warga` (`nik`) ON UPDATE CASCADE,
  CONSTRAINT `fk_voting_calon` FOREIGN KEY (`id_calon`) REFERENCES `kandidat` (`id_calon`),
  CONSTRAINT `fk_voting_periode` FOREIGN KEY (`id_periode`) REFERENCES `periode` (`id_periode`),
  CONSTRAINT `fk_voting_warga` FOREIGN KEY (`nik`) REFERENCES `warga` (`nik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table data_sipel.voting: ~9 rows (approximately)
INSERT INTO `voting` (`id_voting`, `nik`, `id_calon`, `id_periode`, `waktu_pilih`) VALUES
	(1, '12345', 1, 1, '2025-12-12 05:17:36'),
	(2, '22', 5, 1, '2025-12-12 13:46:54'),
	(3, '22', 10, 1, '2025-12-12 14:07:05'),
	(4, '33', 10, 1, '2025-12-12 14:07:44'),
	(5, '33', 7, 1, '2025-12-12 14:08:16'),
	(6, '3271010804841680', 3, 1, '2025-12-12 21:09:11'),
	(7, '3271010804841680', 10, 1, '2025-12-12 21:09:46'),
	(8, '3271010309993258', 2, 1, '2025-12-14 14:52:26'),
	(9, '3271010309993258', 11, 1, '2025-12-14 14:52:32');

-- Dumping structure for table data_sipel.voting_log
CREATE TABLE IF NOT EXISTS `voting_log` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `id_voting` int DEFAULT NULL,
  `nik` varchar(20) NOT NULL,
  `id_periode` int NOT NULL,
  `aktivitas` enum('login','generate_token','pilih','ubah_pilihan','hapus','gagal') NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `waktu` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_voting` (`id_voting`),
  KEY `idx_log_nik` (`nik`),
  KEY `idx_log_periode` (`id_periode`),
  CONSTRAINT `fk_log_periode` FOREIGN KEY (`id_periode`) REFERENCES `periode` (`id_periode`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_log_voting` FOREIGN KEY (`id_voting`) REFERENCES `voting` (`id_voting`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_log_warga` FOREIGN KEY (`nik`) REFERENCES `warga` (`nik`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table data_sipel.voting_log: ~0 rows (approximately)

-- Dumping structure for table data_sipel.warga
CREATE TABLE IF NOT EXISTS `warga` (
  `nik` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `nama` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `rt` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `rw` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `alamat` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `password_changed` tinyint(1) NOT NULL DEFAULT '0',
  `status_voting` enum('belum','sudah') NOT NULL DEFAULT 'belum',
  `waktu_voting` datetime DEFAULT NULL,
  `id_wilayah` int DEFAULT NULL,
  PRIMARY KEY (`nik`) USING BTREE,
  KEY `FK1 id_wilayah` (`id_wilayah`),
  CONSTRAINT `FK1 id_wilayah` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id_wilayah`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table data_sipel.warga: ~158 rows (approximately)
INSERT INTO `warga` (`nik`, `nama`, `rt`, `rw`, `alamat`, `password`, `password_hash`, `password_changed`, `status_voting`, `waktu_voting`, `id_wilayah`) VALUES
	('1111111111111111', 'reno', '01', '01', 'griya harmooni', '', '$2y$10$vXKoGpmY4moR7svCquMVcOFkG4lwQhzp8iC1kBzdprFvdfqHzj.z6', 1, 'belum', NULL, 1),
	('12345', 'udin', '01', '01', 'sdrman', '12345', NULL, 0, 'sudah', '2025-12-12 05:17:36', 1),
	('1404191609060001', 'sipel', '01', '01', 'batam', '1404191609060001', '$2y$10$P2MdMwZ7D90ezLPNqYBAz.39tjJ1tXIhvueNByfGXRuOJHa8Y8UVq', 0, 'belum', NULL, 1),
	('1404191609060004', 'rizki septi pratamaa', '03', '01', 'batam', '', '$2y$10$XeZaCZq6UUbTueJbIipPbOeLbxIxyPejgrpbx2F3Dbti00Oy5RmwW', 1, 'belum', NULL, 3),
	('22', 'wawa', '02', '01', 'melati', '', '$2y$10$x2Swf5oDyHto4Tz2d4/mceSMoYdScd9NGp.NdGhlLT0X5q/pvkoMi', 1, 'sudah', '2025-12-12 14:07:05', 1),
	('2222222222222222', 'rina', '01', '01', 'griya harmono', '2222222222222222', '$2y$10$eTlVqCVOCPuMqRBsGyJq9ekL8/gSoQ4eqAPmw92xlKFaiXGl2.oOe', 0, 'belum', NULL, 1),
	('3271010104884366', 'Malio', '01', '01', 'Jl. Melati No. 47', '', '$2y$10$dV6iau/Rkno80wGgFsMM1O9w.ZM2LfIAPJFDj7iiFDhQ0f7kxiBYq', 1, 'belum', NULL, 1),
	('3271010108013482', 'Athala', '01', '01', 'Jl. Melati No. 36', '3271010108013482', NULL, 0, 'belum', NULL, 1),
	('3271010204805718', 'Alvaro', '01', '01', 'Jl. Melati No. 37', '3271010204805718', NULL, 0, 'belum', NULL, 1),
	('3271010209861808', 'Aiden', '01', '01', 'Jl. Melati No. 14', '3271010209861808', NULL, 0, 'belum', NULL, 1),
	('3271010304878280', 'Ahmad Fauzi', '01', '01', 'Jl. Melati No. 5', '3271010304878280', NULL, 0, 'belum', NULL, 1),
	('3271010307021076', 'Indah Permata', '01', '01', 'Jl. Melati No. 10', '3271010307021076', NULL, 0, 'belum', NULL, 1),
	('3271010309993258', 'Kurnia Rahman', '01', '01', 'Jl. Melati No. 31', '', '$2y$10$dm1TG.w6xdlhtxck1X5BTuxOIkuohRmSDUwUHvJIDrti3ZBq9tgzO', 1, 'sudah', '2025-12-14 14:52:32', 1),
	('3271010405803701', 'Priska Valencia', '01', '01', 'Jl. Melati No. 38', '3271010405803701', NULL, 0, 'belum', NULL, 1),
	('3271010501936375', 'Nabila Zahra', '01', '01', 'Jl. Melati No. 26', '3271010501936375', NULL, 0, 'belum', NULL, 1),
	('3271010506979306', 'Lina Agustina', '01', '01', 'Jl. Melati No. 22', '3271010506979306', NULL, 0, 'belum', NULL, 1),
	('3271010510830157', 'Bayu Nugroho', '01', '01', 'Jl. Melati No. 33', '3271010510830157', NULL, 0, 'belum', NULL, 1),
	('3271010606845813', 'Hendra Gunawan', '01', '01', 'Jl. Melati No. 43', '3271010606845813', NULL, 0, 'belum', NULL, 1),
	('3271010804841680', 'Siti Rahmawati', '01', '01', 'Jl. Melati No. 2', '3271010804841680', NULL, 0, 'sudah', '2025-12-12 21:09:46', 1),
	('3271010806019604', 'Maya Sari', '01', '01', 'Jl. Melati No. 24', '3271010806019604', NULL, 0, 'belum', NULL, 1),
	('3271010901042616', 'Fajar Hidayat', '01', '01', 'Jl. Melati No. 9', '3271010901042616', NULL, 0, 'belum', NULL, 1),
	('3271010911946583', 'Hafidz Rahman', '01', '01', 'Jl. Melati No. 27', '3271010911946583', NULL, 0, 'belum', NULL, 1),
	('3271011001992668', 'Ferry Irawan', '01', '01', 'Jl. Melati No. 41', '3271011001992668', NULL, 0, 'belum', NULL, 1),
	('3271011106834213', 'Raka Pratama', '01', '01', 'Jl. Melati No. 49', '3271011106834213', NULL, 0, 'belum', NULL, 1),
	('3271011302030273', 'Rian Firmansyah', '01', '01', 'Jl. Melati No. 15', '3271011302030273', NULL, 0, 'belum', NULL, 1),
	('3271011404820928', 'Sarah Oktaviani', '01', '01', 'Jl. Melati No. 42', '3271011404820928', NULL, 0, 'belum', NULL, 1),
	('3271011404949655', 'Putri Amelia', '01', '01', 'Jl. Melati No. 8', '3271011404949655', NULL, 0, 'belum', NULL, 1),
	('3271011510028733', 'Irfan Maulana', '01', '01', 'Jl. Melati No. 29', '3271011510028733', NULL, 0, 'belum', NULL, 1),
	('3271011605072643', 'Lestari Ayuningtyas', '01', '01', 'Jl. Melati No. 30', '3271011605072643', NULL, 0, 'belum', NULL, 1),
	('3271011709946053', 'Nadya Kartika', '01', '01', 'Jl. Melati No. 34', '3271011709946053', NULL, 0, 'belum', NULL, 1),
	('3271011809938900', 'Joko Susilo', '01', '01', 'Jl. Melati No. 19', '3271011809938900', NULL, 0, 'belum', NULL, 1),
	('3271011810022242', 'Dedi Saputra', '01', '01', 'Jl. Melati No. 11', '3271011810022242', NULL, 0, 'belum', NULL, 1),
	('3271011907810489', 'Dewi Lestari', '01', '01', 'Jl. Melati No. 4', '3271011907810489', NULL, 0, 'belum', NULL, 1),
	('3271011910967073', 'Gilang Ramadhan', '01', '01', 'Jl. Melati No. 25', '3271011910967073', NULL, 0, 'belum', NULL, 1),
	('3271012001973258', 'Nur Aisyah', '01', '01', 'Jl. Melati No. 6', '3271012001973258', NULL, 0, 'belum', NULL, 1),
	('3271012011928150', 'Yoga Pradana', '01', '01', 'Jl. Melati No. 13', '3271012011928150', NULL, 0, 'belum', NULL, 1),
	('3271012102804507', 'Budi Santoso', '01', '01', 'Jl. Melati No. 1', '3271012102804507', NULL, 0, 'belum', NULL, 1),
	('3271012102810931', 'Dimas Aditya', '01', '01', 'Jl. Melati No. 21', '3271012102810931', NULL, 0, 'belum', NULL, 1),
	('3271012103809770', 'Tiara Putri', '01', '01', 'Jl. Melati No. 20', '3271012103809770', NULL, 0, 'belum', NULL, 1),
	('3271012206849446', 'Vina Maharani', '01', '01', 'Jl. Melati No. 46', '3271012206849446', NULL, 0, 'belum', NULL, 1),
	('3271012209824259', 'Arif Setiawan', '01', '01', 'Jl. Melati No. 35', '3271012209824259', NULL, 0, 'belum', NULL, 1),
	('3271012212971425', 'Andi Pratama', '01', '01', 'Jl. Melati No. 3', '3271012212971425', NULL, 0, 'belum', NULL, 1),
	('3271012304848164', 'Rina Marlina', '01', '01', 'Jl. Melati No. 12', '3271012304848164', NULL, 0, 'belum', NULL, 1),
	('3271012307916887', 'Laila Sari', '01', '01', 'Jl. Melati No. 18', '3271012307916887', NULL, 0, 'belum', NULL, 1),
	('3271012311028929', 'Rizky Kurniawan', '01', '01', 'Jl. Melati No. 7', '3271012311028929', NULL, 0, 'belum', NULL, 1),
	('3271012408985262', 'Anton Wijaya', '01', '01', 'Jl. Melati No. 17', '3271012408985262', NULL, 0, 'belum', NULL, 1),
	('3271012409803244', 'Citra Anggraini', '01', '01', 'Jl. Melati No. 50', '3271012409803244', NULL, 0, 'belum', NULL, 1),
	('3271012412004490', 'Silvi Oktaviani', '01', '01', 'Jl. Melati No. 28', '3271012412004490', NULL, 0, 'belum', NULL, 1),
	('3271012501816820', 'Tania Khairunnisa', '01', '01', 'Jl. Melati No. 44', '3271012501816820', NULL, 0, 'belum', NULL, 1),
	('3271012608854665', 'Edo Prakoso', '01', '01', 'Jl. Melati No. 39', '3271012608854665', NULL, 0, 'belum', NULL, 1),
	('3271012612839999', 'Rani Amelia', '01', '01', 'Jl. Melati No. 40', '3271012612839999', NULL, 0, 'belum', NULL, 1),
	('3271012701955718', 'Ilham Saputra', '01', '01', 'Jl. Melati No. 45', '3271012701955718', NULL, 0, 'belum', NULL, 1),
	('3271012705876426', 'Farhan Maulana', '01', '01', 'Jl. Melati No. 23', '3271012705876426', NULL, 0, 'belum', NULL, 1),
	('3271012709979049', 'Wulan Sari', '01', '01', 'Jl. Melati No. 48', '3271012709979049', NULL, 0, 'belum', NULL, 1),
	('3271012807885355', 'Mega Puspitasari', '01', '01', 'Jl. Melati No. 32', '3271012807885355', NULL, 0, 'belum', NULL, 1),
	('3271012906000310', 'Fitri Handayani', '01', '01', 'Jl. Melati No. 16', '3271012906000310', NULL, 0, 'belum', NULL, 1),
	('3271020108991011', 'Kenzie', '02', '01', 'Jl. Kenanga No. 34', '3271020108991011', NULL, 0, 'belum', NULL, 2),
	('3271020109900016', 'Anand', '02', '01', 'Jl. Kenanga No. 23', '3271020109900016', NULL, 0, 'belum', NULL, 2),
	('3271020201915535', 'Arthur', '02', '01', 'Jl. Kenanga No. 46', '3271020201915535', NULL, 0, 'belum', NULL, 2),
	('3271020207986438', 'Elgard', '02', '01', 'Jl. Kenanga No. 5', '3271020207986438', NULL, 0, 'belum', NULL, 2),
	('3271020208883053', 'Priska Valencia', '02', '01', 'Jl. Kenanga No. 38', '3271020208883053', NULL, 0, 'belum', NULL, 2),
	('3271020209844847', 'Rani Amelia', '02', '01', 'Jl. Kenanga No. 40', '3271020209844847', NULL, 0, 'belum', NULL, 2),
	('3271020307815300', 'Rina Marlina', '02', '01', 'Jl. Kenanga No. 12', '3271020307815300', NULL, 0, 'belum', NULL, 2),
	('3271020309806060', 'Dedi Saputra', '02', '01', 'Jl. Kenanga No. 11', '3271020309806060', NULL, 0, 'belum', NULL, 2),
	('3271020501805814', 'Tania Khairunnisa', '02', '01', 'Jl. Kenanga No. 44', '3271020501805814', NULL, 0, 'belum', NULL, 2),
	('3271020506931108', 'Rizky Kurniawan', '02', '01', 'Jl. Kenanga No. 7', '3271020506931108', NULL, 0, 'belum', NULL, 2),
	('3271020604998246', 'Lina Agustina', '02', '01', 'Jl. Kenanga No. 22', '3271020604998246', NULL, 0, 'belum', NULL, 2),
	('3271020607803310', 'Dewi Lestari', '02', '01', 'Jl. Kenanga No. 4', '3271020607803310', NULL, 0, 'belum', NULL, 2),
	('3271020706805128', 'Budi Santoso', '02', '01', 'Jl. Kenanga No. 1', '3271020706805128', NULL, 0, 'belum', NULL, 2),
	('3271020710849707', 'Citra Anggraini', '02', '01', 'Jl. Kenanga No. 50', '3271020710849707', NULL, 0, 'belum', NULL, 2),
	('3271020809954115', 'Reza Pahlevi', '02', '01', 'Jl. Kenanga No. 47', '3271020809954115', NULL, 0, 'belum', NULL, 2),
	('3271020904846483', 'Nabila Zahra', '02', '01', 'Jl. Kenanga No. 26', '3271020904846483', NULL, 0, 'belum', NULL, 2),
	('3271020906862354', 'Dimas Aditya', '02', '01', 'Jl. Kenanga No. 21', '3271020906862354', NULL, 0, 'belum', NULL, 2),
	('3271021209023784', 'Raka Pratama', '02', '01', 'Jl. Kenanga No. 49', '3271021209023784', NULL, 0, 'belum', NULL, 2),
	('3271021301814384', 'Ilham Saputra', '02', '01', 'Jl. Kenanga No. 45', '3271021301814384', NULL, 0, 'belum', NULL, 2),
	('3271021303003295', 'Gilang Ramadhan', '02', '01', 'Jl. Kenanga No. 25', '3271021303003295', NULL, 0, 'belum', NULL, 2),
	('3271021503010538', 'Irfan Maulana', '02', '01', 'Jl. Kenanga No. 29', '3271021503010538', NULL, 0, 'belum', NULL, 2),
	('3271021506958497', 'Ferry Irawan', '02', '01', 'Jl. Kenanga No. 41', '3271021506958497', NULL, 0, 'belum', NULL, 2),
	('3271021506978766', 'Rian Firmansyah', '02', '01', 'Jl. Kenanga No. 15', '3271021506978766', NULL, 0, 'belum', NULL, 2),
	('3271021604959337', 'Kurnia Rahman', '02', '01', 'Jl. Kenanga No. 31', '3271021604959337', NULL, 0, 'belum', NULL, 2),
	('3271021609007467', 'Wulan Sari', '02', '01', 'Jl. Kenanga No. 48', '3271021609007467', NULL, 0, 'belum', NULL, 2),
	('3271021609998464', 'Olivia Maharani', '02', '01', 'Jl. Kenanga No. 36', '3271021609998464', NULL, 0, 'belum', NULL, 2),
	('3271021611000256', 'Dani Ramdani', '02', '01', 'Jl. Kenanga No. 37', '3271021611000256', NULL, 0, 'belum', NULL, 2),
	('3271021612897557', 'Tiara Putri', '02', '01', 'Jl. Kenanga No. 20', '3271021612897557', NULL, 0, 'belum', NULL, 2),
	('3271021807872961', 'Fajar Hidayat', '02', '01', 'Jl. Kenanga No. 9', '3271021807872961', NULL, 0, 'belum', NULL, 2),
	('3271022005817084', 'Siti Rahmawati', '02', '01', 'Jl. Kenanga No. 2', '3271022005817084', NULL, 0, 'belum', NULL, 2),
	('3271022009805577', 'Mega Puspitasari', '02', '01', 'Jl. Kenanga No. 32', '3271022009805577', NULL, 0, 'belum', NULL, 2),
	('3271022010874543', 'Andi Pratama', '02', '01', 'Jl. Kenanga No. 3', '3271022010874543', NULL, 0, 'belum', NULL, 2),
	('3271022101000145', 'Sarah Oktaviani', '02', '01', 'Jl. Kenanga No. 42', '3271022101000145', NULL, 0, 'belum', NULL, 2),
	('3271022109023143', 'Hafidz Rahman', '02', '01', 'Jl. Kenanga No. 27', '3271022109023143', NULL, 0, 'belum', NULL, 2),
	('3271022110943494', 'Edo Prakoso', '02', '01', 'Jl. Kenanga No. 39', '3271022110943494', NULL, 0, 'belum', NULL, 2),
	('3271022305800571', 'Nur Aisyah', '02', '01', 'Jl. Kenanga No. 6', '3271022305800571', NULL, 0, 'belum', NULL, 2),
	('3271022306005824', 'Bayu Nugroho', '02', '01', 'Jl. Kenanga No. 33', '3271022306005824', NULL, 0, 'belum', NULL, 2),
	('3271022310950117', 'Arif Setiawan', '02', '01', 'Jl. Kenanga No. 35', '3271022310950117', NULL, 0, 'belum', NULL, 2),
	('3271022505820481', 'Yoga Pradana', '02', '01', 'Jl. Kenanga No. 13', '3271022505820481', NULL, 0, 'belum', NULL, 2),
	('3271022507858313', 'Lestari Ayuningtyas', '02', '01', 'Jl. Kenanga No. 30', '3271022507858313', NULL, 0, 'belum', NULL, 2),
	('3271022605937887', 'Joko Susilo', '02', '01', 'Jl. Kenanga No. 19', '3271022605937887', NULL, 0, 'belum', NULL, 2),
	('3271022606821531', 'Indah Permata', '02', '01', 'Jl. Kenanga No. 10', '3271022606821531', NULL, 0, 'belum', NULL, 2),
	('3271022607821180', 'Silvi Oktaviani', '02', '01', 'Jl. Kenanga No. 28', '3271022607821180', NULL, 0, 'belum', NULL, 2),
	('3271022607882774', 'Putri Amelia', '02', '01', 'Jl. Kenanga No. 8', '3271022607882774', NULL, 0, 'belum', NULL, 2),
	('3271022707983066', 'Fitri Handayani', '02', '01', 'Jl. Kenanga No. 16', '3271022707983066', NULL, 0, 'belum', NULL, 2),
	('3271022805965695', 'Anton Wijaya', '02', '01', 'Jl. Kenanga No. 17', '3271022805965695', NULL, 0, 'belum', NULL, 2),
	('3271022903898981', 'Maya Sari', '02', '01', 'Jl. Kenanga No. 24', '3271022903898981', NULL, 0, 'belum', NULL, 2),
	('3271022910905976', 'Hendra Gunawan', '02', '01', 'Jl. Kenanga No. 43', '3271022910905976', NULL, 0, 'belum', NULL, 2),
	('3271022912976002', 'Ayu Wulandari', '02', '01', 'Jl. Kenanga No. 14', '3271022912976002', NULL, 0, 'belum', NULL, 2),
	('3271023003010949', 'Laila Sari', '02', '01', 'Jl. Kenanga No. 18', '3271023003010949', NULL, 0, 'belum', NULL, 2),
	('3271030209817225', 'Zafran', '03', '01', 'Jl. Anggrek No. 4', '3271030209817225', NULL, 0, 'belum', NULL, 3),
	('3271030306817757', 'Ziko', '03', '01', 'Jl. Anggrek No. 34', '3271030306817757', NULL, 0, 'belum', NULL, 3),
	('3271030309803504', 'Valerio', '03', '01', 'Jl. Anggrek No. 1', '3271030309803504', NULL, 0, 'belum', NULL, 3),
	('3271030506982235', 'Irsyad', '03', '01', 'Jl. Anggrek No. 19', '3271030506982235', NULL, 0, 'belum', NULL, 3),
	('3271030607972610', 'Farhan Maulana', '03', '01', 'Jl. Anggrek No. 23', '3271030607972610', NULL, 0, 'belum', NULL, 3),
	('3271030607995690', 'Anton Wijaya', '03', '01', 'Jl. Anggrek No. 17', '3271030607995690', NULL, 0, 'belum', NULL, 3),
	('3271030702805633', 'Maya Sari', '03', '01', 'Jl. Anggrek No. 24', '3271030702805633', NULL, 0, 'belum', NULL, 3),
	('3271030707016212', 'Ilham Saputra', '03', '01', 'Jl. Anggrek No. 45', '3271030707016212', NULL, 0, 'belum', NULL, 3),
	('3271030709949633', 'Arif Setiawan', '03', '01', 'Jl. Anggrek No. 35', '3271030709949633', NULL, 0, 'belum', NULL, 3),
	('3271030809801795', 'Rani Amelia', '03', '01', 'Jl. Anggrek No. 40', '3271030809801795', NULL, 0, 'belum', NULL, 3),
	('3271030812924623', 'Fitri Handayani', '03', '01', 'Jl. Anggrek No. 16', '3271030812924623', NULL, 0, 'belum', NULL, 3),
	('3271030901824719', 'Fajar Hidayat', '03', '01', 'Jl. Anggrek No. 9', '3271030901824719', NULL, 0, 'belum', NULL, 3),
	('3271030903997885', 'Andi Pratama', '03', '01', 'Jl. Anggrek No. 3', '3271030903997885', NULL, 0, 'belum', NULL, 3),
	('3271031005867044', 'Wulan Sari', '03', '01', 'Jl. Anggrek No. 48', '3271031005867044', NULL, 0, 'belum', NULL, 3),
	('3271031007970007', 'Reza Pahlevi', '03', '01', 'Jl. Anggrek No. 47', '3271031007970007', NULL, 0, 'belum', NULL, 3),
	('3271031010970169', 'Rian Firmansyah', '03', '01', 'Jl. Anggrek No. 15', '3271031010970169', NULL, 0, 'belum', NULL, 3),
	('3271031104846792', 'Laila Sari', '03', '01', 'Jl. Anggrek No. 18', '3271031104846792', NULL, 0, 'belum', NULL, 3),
	('3271031201987403', 'Lina Agustina', '03', '01', 'Jl. Anggrek No. 22', '3271031201987403', NULL, 0, 'belum', NULL, 3),
	('3271031210018201', 'Gilang Ramadhan', '03', '01', 'Jl. Anggrek No. 25', '3271031210018201', NULL, 0, 'belum', NULL, 3),
	('3271031307993952', 'Dimas Aditya', '03', '01', 'Jl. Anggrek No. 21', '3271031307993952', NULL, 0, 'belum', NULL, 3),
	('3271031502990650', 'Irfan Maulana', '03', '01', 'Jl. Anggrek No. 29', '3271031502990650', NULL, 0, 'belum', NULL, 3),
	('3271031505993917', 'Rina Marlina', '03', '01', 'Jl. Anggrek No. 12', '3271031505993917', NULL, 0, 'belum', NULL, 3),
	('3271031508943502', 'Citra Anggraini', '03', '01', 'Jl. Anggrek No. 50', '3271031508943502', NULL, 0, 'belum', NULL, 3),
	('3271031510999328', 'Yoga Pradana', '03', '01', 'Jl. Anggrek No. 13', '3271031510999328', NULL, 0, 'belum', NULL, 3),
	('3271031512905588', 'Indah Permata', '03', '01', 'Jl. Anggrek No. 10', '3271031512905588', NULL, 0, 'belum', NULL, 3),
	('3271031603808733', 'Kurnia Rahman', '03', '01', 'Jl. Anggrek No. 31', '3271031603808733', NULL, 0, 'belum', NULL, 3),
	('3271031702849437', 'Hendra Gunawan', '03', '01', 'Jl. Anggrek No. 43', '3271031702849437', NULL, 0, 'belum', NULL, 3),
	('3271031708800583', 'Silvi Oktaviani', '03', '01', 'Jl. Anggrek No. 28', '3271031708800583', NULL, 0, 'belum', NULL, 3),
	('3271031710868008', 'Lestari Ayuningtyas', '03', '01', 'Jl. Anggrek No. 30', '3271031710868008', NULL, 0, 'belum', NULL, 3),
	('3271031908807269', 'Priska Valencia', '03', '01', 'Jl. Anggrek No. 38', '3271031908807269', NULL, 0, 'belum', NULL, 3),
	('3271031910976264', 'Dani Ramdani', '03', '01', 'Jl. Anggrek No. 37', '3271031910976264', NULL, 0, 'belum', NULL, 3),
	('3271032005807866', 'Putri Amelia', '03', '01', 'Jl. Anggrek No. 8', '3271032005807866', NULL, 0, 'belum', NULL, 3),
	('3271032008974460', 'Tiara Putri', '03', '01', 'Jl. Anggrek No. 20', '3271032008974460', NULL, 0, 'belum', NULL, 3),
	('3271032009983998', 'Dedi Saputra', '03', '01', 'Jl. Anggrek No. 11', '3271032009983998', NULL, 0, 'belum', NULL, 3),
	('3271032101001842', 'Bayu Nugroho', '03', '01', 'Jl. Anggrek No. 33', '3271032101001842', NULL, 0, 'belum', NULL, 3),
	('3271032109867639', 'Olivia Maharani', '03', '01', 'Jl. Anggrek No. 36', '3271032109867639', NULL, 0, 'belum', NULL, 3),
	('3271032206905783', 'Ahmad Fauzi', '03', '01', 'Jl. Anggrek No. 5', '3271032206905783', NULL, 0, 'belum', NULL, 3),
	('3271032209918095', 'Ayu Wulandari', '03', '01', 'Jl. Anggrek No. 14', '3271032209918095', NULL, 0, 'belum', NULL, 3),
	('3271032212859325', 'Vina Maharani', '03', '01', 'Jl. Anggrek No. 46', '3271032212859325', NULL, 0, 'belum', NULL, 3),
	('3271032305972086', 'Tania Khairunnisa', '03', '01', 'Jl. Anggrek No. 44', '3271032305972086', NULL, 0, 'belum', NULL, 3),
	('3271032312809914', 'Sarah Oktaviani', '03', '01', 'Jl. Anggrek No. 42', '3271032312809914', NULL, 0, 'belum', NULL, 3),
	('3271032506027390', 'Hafidz Rahman', '03', '01', 'Jl. Anggrek No. 27', '3271032506027390', NULL, 0, 'belum', NULL, 3),
	('3271032507802317', 'Mega Puspitasari', '03', '01', 'Jl. Anggrek No. 32', '3271032507802317', NULL, 0, 'belum', NULL, 3),
	('3271032604826824', 'Nur Aisyah', '03', '01', 'Jl. Anggrek No. 6', '3271032604826824', NULL, 0, 'belum', NULL, 3),
	('3271032606823309', 'Nabila Zahra', '03', '01', 'Jl. Anggrek No. 26', '3271032606823309', NULL, 0, 'belum', NULL, 3),
	('3271032607803810', 'Edo Prakoso', '03', '01', 'Jl. Anggrek No. 39', '3271032607803810', NULL, 0, 'belum', NULL, 3),
	('3271032610995280', 'Raka Pratama', '03', '01', 'Jl. Anggrek No. 49', '3271032610995280', NULL, 0, 'belum', NULL, 3),
	('3271032804888467', 'Rizky Kurniawan', '03', '01', 'Jl. Anggrek No. 7', '3271032804888467', NULL, 0, 'belum', NULL, 3),
	('3271032805817869', 'Ferry Irawan', '03', '01', 'Jl. Anggrek No. 41', '3271032805817869', NULL, 0, 'belum', NULL, 3),
	('3271032909007845', 'Siti Rahmawati', '03', '01', 'Jl. Anggrek No. 2', '3271032909007845', NULL, 0, 'belum', NULL, 3),
	('33', 'gaga', '03', '01', 'batam', '33', NULL, 0, 'sudah', '2025-12-12 14:08:16', 1),
	('6349632496329463', 'prabowo', '002', '001', 'jawa', '6349632496329463', NULL, 0, 'belum', NULL, 2);

-- Dumping structure for table data_sipel.wilayah
CREATE TABLE IF NOT EXISTS `wilayah` (
  `id_wilayah` int NOT NULL AUTO_INCREMENT,
  `nama_wilayah` varchar(100) NOT NULL,
  `jenis` enum('RT','RW') NOT NULL,
  `rt` varchar(5) DEFAULT NULL,
  `rw` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id_wilayah`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table data_sipel.wilayah: ~4 rows (approximately)
INSERT INTO `wilayah` (`id_wilayah`, `nama_wilayah`, `jenis`, `rt`, `rw`) VALUES
	(1, 'RT 01', 'RT', '01', NULL),
	(2, 'RT 02', 'RT', '02', NULL),
	(3, 'RT 03', 'RT', '03', NULL),
	(4, 'RW 01', 'RW', NULL, '01');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
