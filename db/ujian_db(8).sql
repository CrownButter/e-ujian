-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 17, 2026 at 01:49 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ujian_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `angkatan`
--

CREATE TABLE `angkatan` (
  `id` int UNSIGNED NOT NULL,
  `tahun_angkatan` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` int DEFAULT '1',
  `nama_angkatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_berakhir` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `angkatan`
--

INSERT INTO `angkatan` (`id`, `tahun_angkatan`, `status`, `nama_angkatan`, `tanggal_mulai`, `tanggal_berakhir`) VALUES
(1, '2026', 1, 'ANGKATAN 59', '2026-07-20', '2027-02-20');

-- --------------------------------------------------------

--
-- Table structure for table `batalyon`
--

CREATE TABLE `batalyon` (
  `id` int UNSIGNED NOT NULL,
  `nama_batalyon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `danyon_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `batalyon`
--

INSERT INTO `batalyon` (`id`, `nama_batalyon`, `danyon_id`) VALUES
(17, 'Sepolwan', 650);

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_ujian`
--

CREATE TABLE `jadwal_ujian` (
  `id` int UNSIGNED NOT NULL,
  `kelas_ujian_id` int UNSIGNED DEFAULT NULL,
  `mata_pelajaran_id` int UNSIGNED NOT NULL COMMENT 'Relasi ke tabel mata_pelajaran.id',
  `angkatan_id` int UNSIGNED NOT NULL COMMENT 'Relasi ke tabel angkatan.id',
  `pembuat_soal_id` int UNSIGNED DEFAULT NULL COMMENT 'Relasi ke pegawai.id (Gadik/Pembuat Soal)',
  `nama_ujian` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_ujian` enum('Pilihan Ganda','Essay','Praktik','Mixed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pilihan Ganda',
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `durasi_menit` int UNSIGNED NOT NULL COMMENT 'Durasi pengerjaan dalam menit',
  `status_ujian` enum('Draft','Published','Closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Draft',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tingkat_kognitif` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jawaban_siswa`
--

CREATE TABLE `jawaban_siswa` (
  `id` bigint UNSIGNED NOT NULL,
  `soal_id` int UNSIGNED NOT NULL,
  `siswa_id` int UNSIGNED NOT NULL,
  `kelas_ujian_id` int UNSIGNED DEFAULT NULL,
  `jawaban_teks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `file_lampiran` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_nilai` decimal(5,2) DEFAULT '0.00' COMMENT 'Akumulasi total nilai (0 - 100)',
  `predikat` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Setara predikat (A, B, C, D)',
  `catatan_umum` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Catatan/evaluasi umum dari penilai',
  `dinilai_by` int UNSIGNED DEFAULT NULL COMMENT 'FK ke pegawai.id (Gadik/Penguji)',
  `dinilai_at` datetime DEFAULT NULL COMMENT 'Waktu penginputan nilai',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jawaban_siswa`
--

INSERT INTO `jawaban_siswa` (`id`, `soal_id`, `siswa_id`, `kelas_ujian_id`, `jawaban_teks`, `file_lampiran`, `total_nilai`, `predikat`, `catatan_umum`, `dinilai_by`, `dinilai_at`, `created_at`, `updated_at`) VALUES
(3, 36, 3811, 10, 'testin lagi', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 00:04:04', '2026-08-17 00:04:04'),
(4, 36, 3812, 10, 'belajar menjawab', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 15:10:53', '2026-08-17 15:10:53'),
(5, 37, 3812, 11, 'jawab 1', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(6, 38, 3812, 11, 'jawab2', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(7, 39, 3812, 11, 'jawab3', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(8, 40, 3812, 11, 'jawab4', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(9, 41, 3812, 11, 'jawab5', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(10, 42, 3812, 11, 'jawab6', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(11, 43, 3812, 11, 'jawab7', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(12, 44, 3812, 11, 'jawab8', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(13, 45, 3812, 11, 'jawab9', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(14, 46, 3812, 11, 'jawab10', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(15, 47, 3812, 11, 'jawab11', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(16, 48, 3812, 11, 'jawab12', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(17, 49, 3812, 11, 'jawab13', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45'),
(18, 50, 3812, 11, 'jawab14', NULL, '0.00', NULL, NULL, NULL, NULL, '2026-08-17 19:25:45', '2026-08-17 19:25:45');

-- --------------------------------------------------------

--
-- Table structure for table `jawaban_siswa_nilai_detail`
--

CREATE TABLE `jawaban_siswa_nilai_detail` (
  `id` bigint UNSIGNED NOT NULL,
  `jawaban_siswa_id` bigint UNSIGNED NOT NULL,
  `rubrik_id` int UNSIGNED NOT NULL,
  `skor` int UNSIGNED NOT NULL COMMENT 'Skor inputan Gadik (misal: 1 - 4)',
  `nilai_dimensi` decimal(5,2) NOT NULL COMMENT 'Hasil perhitungan: (skor / skor_maks) * bobot',
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Catatan penilaian per dimensi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kelas_ujian`
--

CREATE TABLE `kelas_ujian` (
  `id` int UNSIGNED NOT NULL,
  `nama_kelas` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mata_pelajaran_id` int UNSIGNED DEFAULT NULL COMMENT 'Relasi ke mata pelajaran (jika ada)',
  `penguji_id` int UNSIGNED DEFAULT NULL COMMENT 'Relasi ke tabel pegawai (pengor/pengawas/penguji)',
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tanggal` date DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `status_ujian` enum('draf','publis','sedang_ujian') COLLATE utf8mb4_general_ci DEFAULT 'draf',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas_ujian`
--

INSERT INTO `kelas_ujian` (`id`, `nama_kelas`, `mata_pelajaran_id`, `penguji_id`, `deskripsi`, `tanggal`, `jam_mulai`, `jam_selesai`, `status_ujian`, `created_at`) VALUES
(10, 'Kelas GW', 6, 649, 'Pleton ID: 27,28,29', '2026-08-17', '15:00:00', '16:30:00', 'publis', '2026-08-16 07:10:11'),
(11, 'Kelas A', 7, 658, 'Pleton ID: 27,28', '2026-08-17', '19:00:00', '21:30:00', 'publis', '2026-08-16 16:31:10');

-- --------------------------------------------------------

--
-- Table structure for table `kelas_ujian_peserta`
--

CREATE TABLE `kelas_ujian_peserta` (
  `id` int UNSIGNED NOT NULL,
  `kelas_ujian_id` int UNSIGNED NOT NULL,
  `siswa_id` int UNSIGNED NOT NULL COMMENT 'Relasi ke data siswa/peserta',
  `status_pengerjaan` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'selesai',
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'selesai'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas_ujian_peserta`
--

INSERT INTO `kelas_ujian_peserta` (`id`, `kelas_ujian_id`, `siswa_id`, `status_pengerjaan`, `status`) VALUES
(7444, 10, 3811, 'selesai', 'selesai'),
(7445, 10, 3812, 'selesai', 'selesai'),
(7446, 10, 3813, 'selesai', 'selesai'),
(7447, 10, 3814, 'selesai', 'selesai'),
(7448, 10, 3815, 'selesai', 'selesai'),
(7449, 10, 3816, 'selesai', 'selesai'),
(7450, 10, 3817, 'selesai', 'selesai'),
(7451, 10, 3818, 'selesai', 'selesai'),
(7452, 10, 3819, 'selesai', 'selesai'),
(7453, 10, 3820, 'selesai', 'selesai'),
(7454, 10, 3821, 'selesai', 'selesai'),
(7455, 10, 3822, 'selesai', 'selesai'),
(7456, 10, 3823, 'selesai', 'selesai'),
(7457, 10, 3824, 'selesai', 'selesai'),
(7458, 10, 3825, 'selesai', 'selesai'),
(7459, 10, 3826, 'selesai', 'selesai'),
(7460, 10, 3827, 'selesai', 'selesai'),
(7461, 10, 3828, 'selesai', 'selesai'),
(7462, 10, 3829, 'selesai', 'selesai'),
(7463, 10, 3830, 'selesai', 'selesai'),
(7464, 10, 3831, 'selesai', 'selesai'),
(7465, 10, 3832, 'selesai', 'selesai'),
(7466, 10, 3833, 'selesai', 'selesai'),
(7467, 10, 3834, 'selesai', 'selesai'),
(7468, 10, 3835, 'selesai', 'selesai'),
(7469, 10, 3836, 'selesai', 'selesai'),
(7470, 10, 3837, 'selesai', 'selesai'),
(7471, 10, 3838, 'selesai', 'selesai'),
(7472, 10, 3839, 'selesai', 'selesai'),
(7473, 10, 3840, 'selesai', 'selesai'),
(7474, 10, 3841, 'selesai', 'selesai'),
(7475, 10, 3842, 'selesai', 'selesai'),
(7476, 10, 3843, 'selesai', 'selesai'),
(7477, 10, 3844, 'selesai', 'selesai'),
(7478, 10, 3845, 'selesai', 'selesai'),
(7479, 10, 3846, 'selesai', 'selesai'),
(7480, 10, 3847, 'selesai', 'selesai'),
(7481, 10, 3848, 'selesai', 'selesai'),
(7482, 10, 3849, 'selesai', 'selesai'),
(7483, 10, 3850, 'selesai', 'selesai'),
(7484, 10, 3851, 'selesai', 'selesai'),
(7485, 10, 3852, 'selesai', 'selesai'),
(7486, 10, 3853, 'selesai', 'selesai'),
(7487, 10, 3854, 'selesai', 'selesai'),
(7488, 10, 3855, 'selesai', 'selesai'),
(7489, 10, 3856, 'selesai', 'selesai'),
(7490, 10, 3857, 'selesai', 'selesai'),
(7491, 10, 3858, 'selesai', 'selesai'),
(7492, 10, 3859, 'selesai', 'selesai'),
(7493, 10, 3860, 'selesai', 'selesai'),
(7494, 10, 3861, 'selesai', 'selesai'),
(7495, 10, 3862, 'selesai', 'selesai'),
(7496, 10, 3863, 'selesai', 'selesai'),
(7497, 10, 3864, 'selesai', 'selesai'),
(7498, 10, 3865, 'selesai', 'selesai'),
(7499, 10, 3866, 'selesai', 'selesai'),
(7500, 10, 3867, 'selesai', 'selesai'),
(7501, 10, 3868, 'selesai', 'selesai'),
(7502, 10, 3869, 'selesai', 'selesai'),
(7503, 10, 3870, 'selesai', 'selesai'),
(7504, 10, 3871, 'selesai', 'selesai'),
(7505, 10, 3872, 'selesai', 'selesai'),
(7506, 10, 3873, 'selesai', 'selesai'),
(7507, 10, 3874, 'selesai', 'selesai'),
(7508, 10, 3875, 'selesai', 'selesai'),
(7509, 10, 3876, 'selesai', 'selesai'),
(7510, 10, 3877, 'selesai', 'selesai'),
(7511, 10, 3878, 'selesai', 'selesai'),
(7512, 10, 3879, 'selesai', 'selesai'),
(7513, 10, 3880, 'selesai', 'selesai'),
(7514, 10, 3881, 'selesai', 'selesai'),
(7515, 10, 3882, 'selesai', 'selesai'),
(7516, 10, 3883, 'selesai', 'selesai'),
(7517, 10, 3884, 'selesai', 'selesai'),
(7518, 10, 3885, 'selesai', 'selesai'),
(8283, 11, 3811, 'selesai', 'selesai'),
(8284, 11, 3812, 'selesai', 'selesai'),
(8285, 11, 3813, 'selesai', 'selesai'),
(8286, 11, 3814, 'selesai', 'selesai'),
(8287, 11, 3815, 'selesai', 'selesai'),
(8288, 11, 3816, 'selesai', 'selesai'),
(8289, 11, 3817, 'selesai', 'selesai'),
(8290, 11, 3818, 'selesai', 'selesai'),
(8291, 11, 3819, 'selesai', 'selesai'),
(8292, 11, 3820, 'selesai', 'selesai'),
(8293, 11, 3821, 'selesai', 'selesai'),
(8294, 11, 3822, 'selesai', 'selesai'),
(8295, 11, 3823, 'selesai', 'selesai'),
(8296, 11, 3824, 'selesai', 'selesai'),
(8297, 11, 3825, 'selesai', 'selesai'),
(8298, 11, 3826, 'selesai', 'selesai'),
(8299, 11, 3827, 'selesai', 'selesai'),
(8300, 11, 3828, 'selesai', 'selesai'),
(8301, 11, 3829, 'selesai', 'selesai'),
(8302, 11, 3830, 'selesai', 'selesai'),
(8303, 11, 3831, 'selesai', 'selesai'),
(8304, 11, 3832, 'selesai', 'selesai'),
(8305, 11, 3833, 'selesai', 'selesai'),
(8306, 11, 3834, 'selesai', 'selesai'),
(8307, 11, 3835, 'selesai', 'selesai'),
(8308, 11, 3836, 'selesai', 'selesai'),
(8309, 11, 3837, 'selesai', 'selesai'),
(8310, 11, 3838, 'selesai', 'selesai'),
(8311, 11, 3839, 'selesai', 'selesai'),
(8312, 11, 3840, 'selesai', 'selesai'),
(8313, 11, 3841, 'selesai', 'selesai'),
(8314, 11, 3842, 'selesai', 'selesai'),
(8315, 11, 3843, 'selesai', 'selesai'),
(8316, 11, 3844, 'selesai', 'selesai'),
(8317, 11, 3845, 'selesai', 'selesai'),
(8318, 11, 3846, 'selesai', 'selesai'),
(8319, 11, 3847, 'selesai', 'selesai'),
(8320, 11, 3848, 'selesai', 'selesai'),
(8321, 11, 3849, 'selesai', 'selesai'),
(8322, 11, 3850, 'selesai', 'selesai'),
(8323, 11, 3851, 'selesai', 'selesai'),
(8324, 11, 3852, 'selesai', 'selesai'),
(8325, 11, 3853, 'selesai', 'selesai'),
(8326, 11, 3854, 'selesai', 'selesai'),
(8327, 11, 3855, 'selesai', 'selesai'),
(8328, 11, 3856, 'selesai', 'selesai'),
(8329, 11, 3857, 'selesai', 'selesai'),
(8330, 11, 3858, 'selesai', 'selesai'),
(8331, 11, 3859, 'selesai', 'selesai'),
(8332, 11, 3860, 'selesai', 'selesai');

-- --------------------------------------------------------

--
-- Table structure for table `kompi`
--

CREATE TABLE `kompi` (
  `id` int UNSIGNED NOT NULL,
  `batalyon_id` int UNSIGNED NOT NULL,
  `nama_kompi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `danki_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kompi`
--

INSERT INTO `kompi` (`id`, `batalyon_id`, `nama_kompi`, `danki_id`) VALUES
(18, 17, 'DANKI A', 658),
(19, 17, 'DANKI B', 662),
(20, 17, 'DANKI C', 657),
(21, 17, 'DANKI D', 655),
(22, 17, 'DANKI E', 656),
(23, 17, 'DANKI F', 661);

-- --------------------------------------------------------

--
-- Table structure for table `laporan_monitoring_detail`
--

CREATE TABLE `laporan_monitoring_detail` (
  `id` int UNSIGNED NOT NULL,
  `periode_id` int UNSIGNED NOT NULL,
  `pleton` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bidang` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sub_bidang` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `indikator` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `giat_serdik` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `hasil_dicapai` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `giat_pengasuh` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_monitoring_detail`
--

INSERT INTO `laporan_monitoring_detail` (`id`, `periode_id`, `pleton`, `bidang`, `sub_bidang`, `indikator`, `giat_serdik`, `hasil_dicapai`, `giat_pengasuh`, `created_at`) VALUES
(5, 1, '24', 'MENTAL SPIRITUAL', '1.	Religius dan Beriman', 'a.	Melaksanakan ibadah sesuai dengan agama masing – masing \r\nb.	Aktif melaksanakan kegiatan\r\nkeagamaan sesuai agama masing – masing \r\nc.	Bersikap toleransi antar umat beragama\r\n', 'Menjalankan semua ibadah dan ketentuan serta ritual agama yang dianutnya sebagai wujud pengakuan manusia akan kebesaran dan kuasa mutlak kepada Tuhan YME atas dirinya.', 'Terselenggaranya kegiatan pembinaan mental spiritual sesuai dengan rencana kegiatan pengasuhan untuk membentuk insan Bhayangkara yang beriman dan bertaqwa kepada Tuhan Yang Maha Esa.', 'Melaksanakan secara bersama – sama dan mengawasi dalam pelaksanaan kegiatan ibadah', '2026-07-16 05:19:41'),
(6, 1, '24', 'MENTAL SPIRITUAL', '2.	Jujur dan ikhlas', 'a.	Berbicara terus terang apa adanya\r\nb.	Mengakui kesalahan yang dilakukan\r\nc.	Melaporkan kepada pengasuh langsung apabila mendapat teguran maupun tindakan dari pengasuh tidak langsung\r\n', 'Mengatakan dan berbuat baik dan benar dalam segala situasi serta dapat dipercaya, patuh dan setia melaksanakan tugas dan tanggung jawab serta tidak melakukan korupsi, kolusi dan nepotisme dalam segala hal.', 'Tertanamnya kejujuran dan keikhlasan pada diri serdik dan dapat mengimplementasikannya dalam kehidupan sehari – hari. ', 'Memberikan contoh perbuatan dan mengawasi kegiatan peserta didik dalam kehidupan sehari – hari.', '2026-07-16 05:19:41'),
(7, 1, '24', 'MENTAL SPIRITUAL', '3.	Toleransi', 'a.	Bersikap saling menghargai kegiatan perayaan hari – hari  besar keagamaan\r\nb.	Memberikan kesempatan kepada siswa lain untuk melaksanakan kegiatan ibadah sesuai dengan agama masing – masing \r\n', 'Melaksanakan sikap saling menghormati dan menghargai antar sesama manusia, tidak membeda – bedakan suku, ras, agama dan golongan, merasa senasib dan sepenanggungan dan mengakui serta menghargai Hak Asasi Manusia.', 'Terciptanya toleransi antar pemeluk agama  dalam kehidupan serdik.', 'jajal', '2026-07-16 05:19:41'),
(8, 1, '25', 'MENTAL IDEOLOGI', '1.	Pancasila, Tribrata, Catur Prasetya, Kode Etik Polri dan Janji Siswa', 'a.	Hafal dan mampu mengucapkan doktrin – doktrin (Pancasila, Tri Brata, Catur Prasetya, kode etik profesi Polri dan Janji Siswa)\r\nb.	Dapat mengaplikasikan doktrin – doktrin dalam kehidupan di dormitory\r\n', 'Mau bersumpah / berjanji, menjunjung tinggi dan setia serta memahami dan melaksanakan nilai Pancasila, Tri Brata, Catur Prasetya, Kode Etik Polri, Janji Siswa dalam kegiatan kehidupan sehari – hari sebagai peserta didik.', 'Tertanamnya rasa kebanggaan terhadap korps dalam diri serdik dalam setiap kegiatan.', 'Melaksanakan pengawasan dan pengecekan terhadap peserta didik dalam kegiatan kepengasuhan. Contoh : pengucapan dalam kegiatan apel pengasuhan\r\n', '2026-07-16 05:26:00'),
(9, 1, '25', 'MENTAL SPIRITUAL', '1.	Religius dan Beriman', 'a.	Melaksanakan ibadah sesuai dengan agama masing – masing \r\nb.	Aktif melaksanakan kegiatan\r\nkeagamaan sesuai agama masing – masing \r\nc.	Bersikap toleransi antar umat beragama\r\n', 'Menjalankan semua ibadah dan ketentuan serta ritual agama yang dianutnya sebagai wujud pengakuan manusia akan kebesaran dan kuasa mutlak kepada Tuhan YME atas dirinya.', 'Menjalankan semua ibadah dan ketentuan serta ritual agama yang dianutnya sebagai wujud pengakuan manusia akan kebesaran dan kuasa mutlak kepada Tuhan YME atas dirinya.', 'Menjalankan semua ibadah dan ketentuan serta ritual agama yang dianutnya sebagai wujud pengakuan manusia akan kebesaran dan kuasa mutlak kepada Tuhan YME atas dirinya.', '2026-07-16 05:26:00');

-- --------------------------------------------------------

--
-- Table structure for table `mata_pelajaran`
--

CREATE TABLE `mata_pelajaran` (
  `id` int UNSIGNED NOT NULL,
  `nama_mapel` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kode_mapel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mata_pelajaran`
--

INSERT INTO `mata_pelajaran` (`id`, `nama_mapel`, `kode_mapel`) VALUES
(5, 'HUKUM KEPOLISIAN', 'HKP'),
(6, 'PATROLI', 'PTR'),
(7, 'ASTACITA', 'ACT'),
(8, 'LALULINTAS', 'LTS'),
(9, 'BINMAS', 'BMS');

-- --------------------------------------------------------

--
-- Table structure for table `materi_perdupsis`
--

CREATE TABLE `materi_perdupsis` (
  `id` int NOT NULL,
  `judul` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `file_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `materi_perdupsis`
--

INSERT INTO `materi_perdupsis` (`id`, `judul`, `slug`, `deskripsi`, `file_pdf`, `cover_img`, `created_at`) VALUES
(6, 'Sosialisasi Perdupsis', 'sosialisasi-perdupsis', NULL, '1783964824_892ac7b6d8eacf8d40df.pdf', '1783964824_ce43ceefa3cc1fb56163.jpg', '2026-07-14 00:47:04'),
(7, 'PERATURAN KEHIDUPAN SISWA', 'peraturan-kehidupan-siswa', NULL, '1784531435_820eee9b8ab4a019afa0.pdf', 'coverbook.png', '2026-07-20 14:10:35');

-- --------------------------------------------------------

--
-- Table structure for table `materi_sosiometri`
--

CREATE TABLE `materi_sosiometri` (
  `id` int NOT NULL,
  `judul` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `file_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `materi_sosiometri`
--

INSERT INTO `materi_sosiometri` (`id`, `judul`, `slug`, `deskripsi`, `file_pdf`, `cover_img`, `created_at`, `updated_at`) VALUES
(1, 'Sosiometri 1', 'sosiometri-1', NULL, '1783964932_0c53ee3a6771f7f0e36c.pdf', '1783964932_393c7fe097979343bac7.png', '2026-07-14 00:48:52', '2026-07-14 00:48:52');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint UNSIGNED NOT NULL,
  `version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(1, '2026-06-26-190656', 'App\\Database\\Migrations\\CreateRolesTable', 'default', 'App', 1782503081, 1),
(2, '2026-06-26-192356', 'App\\Database\\Migrations\\CreatePangkatTable', 'default', 'App', 1782503081, 1),
(3, '2026-06-26-192449', 'App\\Database\\Migrations\\CreateAngkatanTable', 'default', 'App', 1782503081, 1),
(4, '2026-06-26-192533', 'App\\Database\\Migrations\\CreateBatalyonTable', 'default', 'App', 1782503081, 1),
(5, '2026-06-26-192627', 'App\\Database\\Migrations\\CreateKompiTable', 'default', 'App', 1782503081, 1),
(6, '2026-06-26-192715', 'App\\Database\\Migrations\\CreatePletonTable', 'default', 'App', 1782503081, 1),
(7, '2026-06-26-192842', 'App\\Database\\Migrations\\CreateUsersTable', 'default', 'App', 1782503081, 1),
(8, '2026-06-26-192939', 'App\\Database\\Migrations\\CreateProfilesTable', 'default', 'App', 1782503081, 1),
(9, '2026-06-26-193602', 'App\\Database\\Migrations\\CreateMataPelajaranTable', 'default', 'App', 1782503081, 1),
(10, '2026-06-26-193603', 'App\\Database\\Migrations\\CreateSiswaMapelTable', 'default', 'App', 1782503081, 1),
(11, '2026-06-26-195142', 'App\\Database\\Migrations\\CreatePegawaiTable', 'default', 'App', 1782503585, 2),
(12, '2026-06-26-195206', 'App\\Database\\Migrations\\CreateSiswaTable', 'default', 'App', 1782503585, 2);

-- --------------------------------------------------------

--
-- Table structure for table `monitoring_bidang`
--

CREATE TABLE `monitoring_bidang` (
  `id` int UNSIGNED NOT NULL,
  `kode` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_bidang` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urutan` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monitoring_hasil`
--

CREATE TABLE `monitoring_hasil` (
  `id` bigint UNSIGNED NOT NULL,
  `periode_id` int UNSIGNED NOT NULL,
  `siswa_id` int UNSIGNED NOT NULL,
  `indikator_id` int UNSIGNED NOT NULL,
  `hasil_yang_dicapai` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `catatan_pengasuh` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Baik','Cukup','Kurang') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Baik',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monitoring_indikator`
--

CREATE TABLE `monitoring_indikator` (
  `id` int UNSIGNED NOT NULL,
  `bidang_id` int UNSIGNED NOT NULL,
  `nomor` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `judul` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `indikator` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `giat_serdik` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `hasil_default` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `giat_pengasuh` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `urutan` int DEFAULT NULL,
  `aktif` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monitoring_pengesahan`
--

CREATE TABLE `monitoring_pengesahan` (
  `id` bigint UNSIGNED NOT NULL,
  `periode_id` int UNSIGNED NOT NULL,
  `pleton_id` int UNSIGNED NOT NULL,
  `danpi_id` int UNSIGNED DEFAULT NULL,
  `danton_id` int UNSIGNED DEFAULT NULL,
  `danyon_id` int UNSIGNED DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monitoring_periode`
--

CREATE TABLE `monitoring_periode` (
  `id` int UNSIGNED NOT NULL,
  `angkatan_id` int UNSIGNED NOT NULL,
  `minggu_ke` int NOT NULL,
  `periode_awal` date NOT NULL,
  `periode_akhir` date NOT NULL,
  `status` enum('Draft','Final') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `monitoring_periode`
--

INSERT INTO `monitoring_periode` (`id`, `angkatan_id`, `minggu_ke`, `periode_awal`, `periode_akhir`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-07-16', '2026-07-18', 'Draft', NULL, '2026-07-15 10:34:26', '2026-07-15 10:34:26');

-- --------------------------------------------------------

--
-- Table structure for table `nilai_ujian`
--

CREATE TABLE `nilai_ujian` (
  `id` int NOT NULL,
  `kelas_ujian_id` int NOT NULL,
  `siswa_id` int NOT NULL,
  `nilai_akhir` decimal(5,2) DEFAULT NULL,
  `status_pengerjaan` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Belum Mengerjakan',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status_penilaian` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nilai_ujian`
--

INSERT INTO `nilai_ujian` (`id`, `kelas_ujian_id`, `siswa_id`, `nilai_akhir`, `status_pengerjaan`, `created_at`, `updated_at`, `status_penilaian`) VALUES
(1, 10, 3811, '75.00', 'selesai', '2026-08-17 15:04:56', '2026-08-17 15:19:44', 'sudah'),
(2, 10, 3812, '78.00', 'selesai', '2026-08-17 15:11:22', '2026-08-17 15:11:22', 'sudah'),
(3, 11, 3812, '288.00', 'selesai', '2026-08-17 19:45:59', '2026-08-17 19:45:59', 'sudah'),
(4, 11, 3811, '94.44', 'selesai', '2026-08-17 19:51:22', '2026-08-17 19:51:22', 'sudah');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int UNSIGNED NOT NULL,
  `tujuan_id` int UNSIGNED NOT NULL,
  `pesan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id`, `tujuan_id`, `pesan`, `status`, `created_at`) VALUES
(1, 6, 'Ada penilaian mental baru dari Danki untuk Minggu ke-1', 0, '2026-07-19 07:31:42'),
(2, 6, 'Ada penilaian mental baru dari Danki untuk Minggu ke-1', 0, '2026-07-19 07:35:24'),
(3, 6, 'Ada 1 penilaian mental baru dari Danki untuk Minggu ke-1', 0, '2026-07-19 07:38:50');

-- --------------------------------------------------------

--
-- Table structure for table `pangkat`
--

CREATE TABLE `pangkat` (
  `id` int UNSIGNED NOT NULL,
  `nama_pangkat` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pangkat`
--

INSERT INTO `pangkat` (`id`, `nama_pangkat`) VALUES
(1, 'BHARADA'),
(2, 'BHARATU'),
(3, 'BHARAKA'),
(4, 'ABRIPDA'),
(5, 'ABRIPTU'),
(6, 'ABRIP'),
(7, 'BRIPDA'),
(8, 'BRIPTU'),
(9, 'BRIPKA'),
(10, 'BRIGADIR'),
(11, 'AIPDA'),
(12, 'AIPTU'),
(13, 'IPDA'),
(14, 'IPTU'),
(15, 'AKP'),
(16, 'KOMPOL'),
(17, 'AKBP'),
(18, 'KOMBES POL'),
(19, 'BRIGJEN POL'),
(20, 'IRJEN POL'),
(21, 'KOMJEN POL'),
(22, 'JENDERAL POLISI');

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipe_pegawai` enum('polri','pns') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'polri',
  `nomor_induk` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pangkat_id` int UNSIGNED DEFAULT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role_id` int UNSIGNED DEFAULT NULL,
  `ttd` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pegawai`
--

INSERT INTO `pegawai` (`id`, `user_id`, `nama`, `tipe_pegawai`, `nomor_induk`, `pangkat_id`, `foto`, `role_id`, `ttd`) VALUES
(1, 3, 'Administrator Utama', 'pns', '171011400916', NULL, NULL, 1, NULL),
(649, 4495, 'MOCH. ANDRIANSYAH, S.Pd.', 'polri', '71070150', 17, NULL, 3, NULL),
(650, 4496, 'VERAWATY THAIB, S.I.K., M.Si.', 'polri', '87111359', 17, NULL, 3, NULL),
(652, 4498, 'ERNITA PONGKI, S.H., M.M.', 'polri', '72120024', 16, NULL, 3, NULL),
(653, 4499, 'DENNY INDRIANI, S.Pd.', 'polri', '71120445', 16, NULL, 3, NULL),
(655, 4501, 'KUSNI WARTI NINGSIH, S.H., M.M.', 'polri', '69120059', 16, NULL, 3, NULL),
(656, 4502, 'YOHANA NAWANGSIH, S.H., M.H.', 'polri', '71120253', 16, NULL, 3, NULL),
(657, 4503, 'RIYANTI, S.Sos., M.AP.', 'polri', '77030007', 16, NULL, 3, NULL),
(658, 4504, 'WAHYUNI POLPOKE, S.H., M.M.', 'polri', '74070015', 16, NULL, 3, NULL),
(661, 4507, 'NURNAHANI, S.H.', 'polri', '73070565', 16, NULL, 3, NULL),
(662, 4508, 'TITIN ARLINAH, S.H., M.M.', 'polri', '75010460', 16, NULL, 3, NULL),
(665, 4511, 'YUNITA SARI, S.Pd.', 'polri', '71060116', 15, NULL, 3, NULL),
(666, 4512, 'VISTA ULI PARDOSI, S.H., M.M.', 'polri', '80030298', 15, NULL, 3, NULL),
(667, 4513, 'SILVIA WAHYUNI, S.H., M.H.', 'polri', '81040185', 15, NULL, 4, NULL),
(668, 4514, 'SIWI ARIKESTI, S.H., M.H.', 'polri', '82040232', 15, NULL, 4, NULL),
(669, 4515, 'KUSANTOYO, S.H.', 'polri', '78091060', 15, NULL, 3, NULL),
(670, 4516, 'NURAKHMI RUSDHIYANI, S.Pd., M.Pd.', 'polri', '81010475', 15, NULL, 3, NULL),
(671, 4517, 'WETTA AUSTIEN, S.H., M.Pd.', 'polri', '79030182', 15, NULL, 3, NULL),
(672, 4518, 'ADE IMAN SURYANA, S.Pd., M.Pd.', 'polri', '78050917', 15, NULL, 3, NULL),
(673, 4519, 'MAWAR RUNIASIH, S.H.', 'polri', '84030335', 15, NULL, 3, NULL),
(674, 4520, 'SRI PURWANINGSIH, S.H., M.H.', 'polri', '83090122', 15, NULL, 4, NULL),
(675, 4521, 'RETNO KURNIASIH, S.H., M.H.', 'polri', '84030092', 15, NULL, 4, NULL),
(676, 4522, 'EGA PURWANINGSIH, S.H., M.Si., M.Pd.', 'polri', '84110342', 15, NULL, 4, NULL),
(677, 4523, 'BANGUN ADYI WIBOWO, S.Pd.', 'polri', '80090668', 15, NULL, 4, NULL),
(678, 4524, 'IIS KRISTIYANINGSIH, S.Pd.', 'polri', '82120008', 14, NULL, 4, NULL),
(679, 4525, 'KHODAR ERWANTI, S.Pd., M.Pd.', 'polri', '82010251', 14, NULL, 4, NULL),
(680, 4526, 'HENY RISTOWATI', 'polri', '82040798', 14, NULL, 4, NULL),
(681, 4527, 'OKTAVIA RUMENGAN, S.H.', 'polri', '83100638', 14, NULL, 4, NULL),
(682, 4528, 'SYUPRIADIN, S.H., M.Pd.', 'polri', '89050099', 14, NULL, 4, NULL),
(683, 4529, 'YANTI PANJAITAN, S.H.', 'polri', '81111123', 14, NULL, 4, NULL),
(684, 4530, 'dr. ANGGI ANGELINA PERMATASARI', 'polri', '97111054', NULL, NULL, 3, NULL),
(685, 4531, 'WINDA SUNDARI, S.H., M.Pd.', 'polri', '85011693', 14, NULL, 4, NULL),
(686, 4532, 'ANDI SUKRISONO', 'polri', '81100699', 14, NULL, 4, NULL),
(687, 4533, 'I GUSTI AGUNG CANDRIKA MAHADEWI, S.Ak.', 'polri', '00071085', NULL, NULL, 3, NULL),
(688, 4534, 'ADITYAS RACHMANSYAH, S.Pd.', 'polri', '86110021', NULL, NULL, 3, NULL),
(689, 4535, 'MEIFALINA, S.Pd.', 'polri', '87050007', 13, NULL, 4, NULL),
(690, 4536, 'DWI ENDRO SAPUTRO, S.H.', 'polri', '86061408', NULL, NULL, 3, NULL),
(691, 4537, 'DANIAH ISMI YANTI, S.H., M.Pd.', 'polri', '91080076', NULL, NULL, 3, NULL),
(692, 4538, 'FAJAR DWI ATMOKO, S.Psi., M.Pd.', 'polri', '87101529', 13, NULL, 4, NULL),
(693, 4539, 'LUH PUTU RIRIN P. ARYATI, S.H., M.H.', 'polri', '93010037', 13, NULL, 4, NULL),
(694, 4540, 'NURSANTHI, S.Sos.', 'polri', '85071852', 13, NULL, 4, NULL),
(695, 4541, 'ANGGUN PRAMITHA RIZKI', 'polri', '87071174', 13, NULL, 4, NULL),
(696, 4542, 'NINA ADELINA, S.H., A.Md.Kep., M.Pd.', 'polri', '79080979', 13, NULL, 4, NULL),
(697, 4543, 'DINNY PRATIWI, S.M., M.M.', 'polri', '92060111', 13, NULL, 4, NULL),
(698, 4544, 'AYU NOVITA SARI, S.H.', 'polri', '91110220', NULL, NULL, 3, NULL),
(699, 4545, 'TANTY SUSTYO RIZKY, S.E.', 'polri', '91090252', NULL, NULL, 3, NULL),
(700, 4546, 'SRI WINARSIH', 'polri', '75040431', NULL, NULL, 3, NULL),
(701, 4547, 'SUPRIHADI, S.H.', 'polri', '74040711', NULL, NULL, 3, NULL),
(702, 4548, 'TEUKU RINALDY RASYAH', 'polri', '87110616', NULL, NULL, 3, NULL),
(703, 4549, 'AMELIA SETIANA, A.Md.Farm.', 'polri', '87061542', NULL, NULL, 3, NULL),
(704, 4550, 'EKO ARIYANTO, S.Pd.', 'polri', '91120189', NULL, NULL, 3, NULL),
(705, 4551, 'DWI PUTRI AMALIA, S.Pd.', 'polri', '93030039', NULL, NULL, 3, NULL),
(706, 4552, 'REZKY AYU OKTAVIA, S.Psi., M.M.', 'polri', '93100036', NULL, NULL, 3, NULL),
(707, 4553, 'SILVIA AYU WULANDARI', 'polri', '92080089', NULL, NULL, 3, NULL),
(708, 4554, 'FIRMAN SAPUTRA, S.Pd.', 'polri', '93070466', NULL, NULL, 3, NULL),
(709, 4555, 'FADJAR FAUZI FAHMI, S.E., S.H., M.M.', 'polri', '93110687', NULL, NULL, 3, NULL),
(710, 4556, 'ANNASTASIA GALUH AMBARSARI, S.S.', 'polri', '93060436', NULL, NULL, 3, NULL),
(711, 4557, 'YUSUF NUR WACHID, S.Pd., M.Pd.', 'polri', '94110600', NULL, NULL, 3, NULL),
(712, 4558, 'MUHAMAD BURHANUDIN', 'polri', '94060297', NULL, NULL, 3, NULL),
(713, 4559, 'SORAYA RATUZUUR, S.H.', 'polri', '96060878', NULL, NULL, 3, NULL),
(714, 4560, 'WIDYA RESRIANI,  M.Psi.', 'polri', '96070743', NULL, NULL, 3, NULL),
(715, 4561, 'DELVYAN PUTRI SURYANINGRUM, S.H.', 'polri', '98010487', NULL, NULL, 3, NULL),
(716, 4562, 'NUR AFIFAH, S.H.', 'polri', '96011100', NULL, NULL, 3, NULL),
(717, 4563, 'DEBBIE NATAZHA RUMBEWAS', 'polri', '96120689', NULL, NULL, 3, NULL),
(718, 4564, 'MUHAMMAD FUDJA SYAHDILLAH', 'polri', '99070123', NULL, NULL, 3, NULL),
(719, 4565, 'VANI NOVITA SARI, S.H., M.H.', 'polri', '98110558', NULL, NULL, 3, NULL),
(720, 4566, 'PHRIANINDYA AYU SETYANINGSIH, S.H., M.H.', 'polri', '00050128', NULL, NULL, 3, NULL),
(721, 4567, 'MUHAMMAD ZAINI FAJRI, S.Pd.', 'polri', '98020819', NULL, NULL, 3, NULL),
(722, 4568, 'HANOM CANDA XENA SALSABILA P., S.T.', 'polri', '99060600', NULL, NULL, 3, NULL),
(723, 4569, 'ELSA EFRITHA WIJAYA, S.H.', 'polri', '00010528', NULL, NULL, 3, NULL),
(724, 4570, 'MIKHA YULY PRICILLIA SIRAIT, S.H.', 'polri', '00070390', NULL, NULL, 3, NULL),
(725, 4571, 'RANI GUSMAN, S.H.', 'polri', '00080221', NULL, NULL, 3, NULL),
(726, 4572, 'AZMI SULISTIAWATI, S.H.', 'polri', '01060055', NULL, NULL, 3, NULL),
(727, 4573, 'SITI NURHAJIJAH, S.M.', 'polri', '99111023', NULL, NULL, 3, NULL),
(728, 4574, 'BAGOES MAHENDRA RINALDI, S.E.', 'polri', '99080735', NULL, NULL, 3, NULL),
(729, 4575, 'CINDY DWI PUSPITASARI, S.H.', 'polri', '00030716', NULL, NULL, 3, NULL),
(730, 4576, 'FAJAR SADIKIN', 'polri', '00080545', NULL, NULL, 3, NULL),
(731, 4577, 'RAHMAN NOVRIANTO, S.M.', 'polri', '00110424', NULL, NULL, 3, NULL),
(732, 4578, 'FICKRY HAMDANI', 'polri', '01040297', NULL, NULL, 3, NULL),
(733, 4579, 'SAVIRA AZZARAH MAHARANI', 'polri', '01050329', NULL, NULL, 3, NULL),
(734, 4580, 'AHMAD RISFA RAMDANI', 'polri', '01120145', NULL, NULL, 3, NULL),
(735, 4581, 'DIMAS SENOPATI', 'polri', '02030250', NULL, NULL, 3, NULL),
(736, 4582, 'PARVATI, S.H.', 'polri', '02050269', NULL, NULL, 3, NULL),
(737, 4583, 'ANGGITA ARGININDA PRAMISELLA', 'polri', '02070016', NULL, NULL, 3, NULL),
(738, 4584, 'TATA AUDIA SHIVA', 'polri', '02070078', NULL, NULL, 3, NULL),
(739, 4585, 'VENA GUSTIA SINTUNG, S.H.', 'polri', '02080176', NULL, NULL, 3, NULL),
(740, 4586, 'I GUSTI AYU DEWI PURWANI, A.Md.Keb.', 'polri', '99111021', NULL, NULL, 3, NULL),
(741, 4587, 'NABELA BUNGA PRYHANSAH, S.Psi.', 'polri', '99111043', NULL, NULL, 3, NULL),
(742, 4588, 'DWIYANTI UTAMI', 'polri', '00010852', NULL, NULL, 3, NULL),
(743, 4589, 'AFLY YURITA', 'polri', '01070802', NULL, NULL, 3, NULL),
(744, 4590, 'TIKA MARLIN MARTEN', 'polri', '02030372', NULL, NULL, 3, NULL),
(745, 4591, 'JELITA PUTRI YONANDA', 'polri', '02040613', NULL, NULL, 3, NULL),
(746, 4592, 'KEZIA MICHELLE MANOPODE', 'polri', '02080653', NULL, NULL, 3, NULL),
(747, 4593, 'DEBORA PISTIA TOMPUNUH', 'polri', '03090049', NULL, NULL, 3, NULL),
(748, 4594, 'RUTHFI DHAMAYANTI, A.Md.Kep.', 'polri', '00071078', NULL, NULL, 3, NULL),
(749, 4595, 'KURNIATI, S.Kom.', 'polri', '00041104', NULL, NULL, 3, NULL),
(750, 4596, 'FARHAN DHIVA MAULANA', 'polri', '01090897', NULL, NULL, 3, NULL),
(751, 4597, 'DHYMAS BRAM KAMAJAYA PERSADA', 'polri', '01100947', NULL, NULL, 3, NULL),
(752, 4598, 'PUSPA SEPTIANI', 'polri', '02090907', NULL, NULL, 3, NULL),
(753, 4599, 'RACHEL EMMANUEL MIRANDA PUTONG', 'polri', '02100687', NULL, NULL, 3, NULL),
(754, 4600, 'MEGA PUTRI AULIA', 'polri', '02110548', NULL, NULL, 3, NULL),
(755, 4601, 'MUHAMMAD NUR RIZKY FADILLAH', 'polri', '03070370', NULL, NULL, 3, NULL),
(756, 4602, 'FILLIA DESTYNA', 'polri', '03070827', NULL, NULL, 3, NULL),
(757, 4603, 'SHINTA AURA SUCI', 'polri', '03050675', NULL, NULL, 3, NULL),
(758, 4604, 'SITI NUR\'AINI SYAFIRA NUGRAHA', 'polri', '03070834', NULL, NULL, 3, NULL),
(759, 4605, 'OKTAVIANIE SALSABILA PUTRI SETIADI', 'polri', '03100450', NULL, NULL, 3, NULL),
(760, 4606, 'VENUS LASKA BOUNTY', 'polri', '03120341', NULL, NULL, 3, NULL),
(761, 4607, 'CATHLIEN PUTTURUHU', 'polri', '04050022', NULL, NULL, 3, NULL),
(762, 4608, 'ARSI SARI OTAMPI, A.Md.Keb.', 'polri', '02091593', NULL, NULL, 3, NULL),
(763, 4609, 'ZAFIRA DARA PELU', 'polri', '05010018', NULL, NULL, 3, NULL),
(764, 4610, 'KAYLA TAHIRA ', 'polri', '05060234', NULL, NULL, 3, NULL),
(765, 4611, 'FIRDA AYU NASIROH', 'polri', '02081616', 7, NULL, 4, NULL),
(766, 4612, 'NABILA DWI PUTRI', 'polri', '02101866', NULL, NULL, 3, NULL),
(767, 4613, 'RARA SILVIANA AYUNINGTYAS', 'polri', '03011517', NULL, NULL, 3, NULL),
(768, 4614, 'NASYA ARSITA SETIAWATI', 'polri', '03121248', NULL, NULL, 3, NULL),
(769, 4615, 'GRACESICA VALYA UTAMI', 'polri', '04061087', NULL, NULL, 3, NULL),
(770, 4616, 'NIKITA BAYU VIRGINA', 'polri', '04090731', NULL, NULL, 3, NULL),
(771, 4617, 'GUSTI AYU MADE ANGGITA MAHESWARI', 'polri', '05060306', NULL, NULL, 3, NULL),
(772, 4618, 'IKA SURYANI', 'polri', '04021045', NULL, NULL, 3, NULL),
(773, 4619, 'MUHAMAD TIYO FAJARUDIN', 'polri', '04051474', NULL, NULL, 3, NULL),
(774, 4620, 'NOVA RIZKYA', 'polri', '04110817', NULL, NULL, 3, NULL),
(775, 4621, 'ADITYA PUTRA PRATAMA', 'polri', '05040565', NULL, NULL, 3, NULL),
(776, 4622, 'TRESNA ALIFIA SYAHARANI', 'polri', '04031556', NULL, NULL, 3, NULL),
(777, 4623, 'SEKAR WIDHI TRI AMBARSARI', 'polri', '04051542', NULL, NULL, 3, NULL),
(778, 4624, 'KRISTINA DEWI HANDAYANI', 'polri', '04051544', NULL, NULL, 3, NULL),
(779, 4625, 'ADELIA ARAMIAN SIREGAR', 'polri', '04111080', NULL, NULL, 3, NULL),
(780, 4626, 'TIA NURHIDAYAH', 'polri', '05010882', NULL, NULL, 3, NULL),
(781, 4627, 'SYAFTA PUTRI DIWANDA', 'polri', '05030920', NULL, NULL, 3, NULL),
(782, 4628, 'I GUSTI AYU AGUNG INDIRA PARAMESWARI', 'polri', '05080706', NULL, NULL, 3, NULL),
(783, 4629, 'NI KADEK AYU DWIANGGRAENI', 'polri', '05100603', NULL, NULL, 3, NULL),
(784, 4630, 'ELVARETTA ALYA DANISTYA', 'polri', '06020274', NULL, NULL, 3, NULL),
(785, 4631, 'ALZIRA AYU NABILA', 'polri', '06070236', NULL, NULL, 3, NULL),
(786, 4632, 'VERA ANJELINA, S.Psi.', 'polri', '00031163', NULL, NULL, 3, NULL),
(787, 4633, 'WULAN OCTARI DARAJATI', 'polri', '03101685', NULL, NULL, 3, NULL),
(788, 4634, 'DINDA CHINTA RAMADHITA', 'polri', '03101690', NULL, NULL, 3, NULL),
(789, 4635, 'HABIBAH ANDINI ASLAMIAH', 'polri', '04051598', NULL, NULL, 3, NULL),
(790, 4636, 'AULIA NURUL AZIZAH', 'polri', '06110107', NULL, NULL, 3, NULL),
(791, 4637, 'ZHOVANIS KEISYA IMAWAN', 'polri', '06120188', NULL, NULL, 3, NULL),
(792, 4638, 'BARUNA SINATRYA HARI RADJASA DELMORA', 'polri', '06120181', NULL, NULL, 3, NULL),
(793, 4639, 'NI MADE ANGIRA KHESTA', 'polri', '07020058', NULL, NULL, 3, NULL),
(794, 4640, 'JIHAN HANIFAH', 'polri', '07030069', NULL, NULL, 3, NULL),
(795, 4641, 'PRASCA ANARGYA PUTRA PITONO', 'polri', '07060010', NULL, NULL, 3, NULL),
(796, 4642, 'IKHWAN BUDI PRAYOGA', 'polri', '07080035', NULL, NULL, 3, NULL),
(797, 4643, 'DEFRY NURHAMDANI', 'polri', '07100020', NULL, NULL, 3, NULL),
(798, 4644, 'FARDAN FEBRIAL PUTRA', 'polri', '04011557', NULL, NULL, 3, NULL),
(799, 4645, 'MUHAMMAD  RIDHO AKBAR', 'polri', '04061468', NULL, NULL, 3, NULL),
(800, 5356, 'SRI HASTUTI, S.Pd., M.M.', 'pns', '197007042002122002', NULL, NULL, 3, NULL),
(801, 5357, 'MAHDIN GAJA, S.Ag., M.Ag.', 'pns', '197112122003121003', NULL, NULL, 3, NULL),
(802, 5358, 'TITUK SRI HARTATI, S.Pd., M.Pd.', 'pns', '197405201993032003', NULL, NULL, 3, NULL),
(803, 5359, 'NURLAELAH, S.E., M.Pd.', 'pns', '197104211999032002', NULL, NULL, 3, NULL),
(804, 5360, 'MITA RAMADHITA, S.E.', 'pns', '198111072009122001', NULL, NULL, 3, NULL),
(805, 5361, 'dr. DITA APERTAWA', 'pns', '198706032018012001', NULL, NULL, 3, NULL),
(806, 5362, 'SRI ASTUTI', 'pns', '197101141993032002', NULL, NULL, 3, NULL),
(807, 5363, 'ARIFIN', 'pns', '197104161993101001', NULL, NULL, 3, NULL),
(808, 5364, 'Rr. WIWIT SRI SURATIWI. I.S, A.Md.', 'pns', '198104062006042004', NULL, NULL, 3, NULL),
(809, 5365, 'DEWI TRIYANA, A.Md.Keb', 'pns', '198606242009122002', NULL, NULL, 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `penilaian_mental`
--

CREATE TABLE `penilaian_mental` (
  `id` int UNSIGNED NOT NULL,
  `siswa_id` int UNSIGNED NOT NULL,
  `angkatan_id` int UNSIGNED NOT NULL,
  `minggu_ke` int NOT NULL,
  `hari_ke` int DEFAULT '1',
  `skor_spiritual` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `skor_ideologi` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `skor_kejuangan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `skor_watak` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `skor_kepemimpinan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `jml_skor` int DEFAULT '0',
  `nilai_akhir` decimal(5,2) DEFAULT '0.00',
  `status_danton` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `status_danki` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `status_danyon` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `danton_id` int UNSIGNED DEFAULT NULL,
  `tanggal_input` datetime DEFAULT CURRENT_TIMESTAMP,
  `jml_hsl_pengamatan` decimal(5,2) DEFAULT '0.00',
  `nilai_konversi` decimal(5,2) DEFAULT '0.00',
  `tind_diluar_minus` int DEFAULT '0',
  `tind_diluar_plus` int DEFAULT '0',
  `nilai_akhir_fix` decimal(5,2) DEFAULT '0.00',
  `approved_by_danki_id` int UNSIGNED DEFAULT NULL,
  `approved_by_danki_at` datetime DEFAULT NULL,
  `catatan_danki` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pleton`
--

CREATE TABLE `pleton` (
  `id` int UNSIGNED NOT NULL,
  `kompi_id` int UNSIGNED NOT NULL,
  `nama_pleton` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `danton_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pleton`
--

INSERT INTO `pleton` (`id`, `kompi_id`, `nama_pleton`, `danton_id`) VALUES
(27, 18, 'TON 1A', 81010475),
(28, 18, 'TON 2A', 80090668),
(29, 18, 'TON 3A', 82040798),
(31, 18, 'TON 4A', 81111123),
(32, 18, 'TON 5A', 85071852),
(33, 19, 'TON 1B', 78050917),
(34, 19, 'TON 2B', 82010251),
(35, 19, 'TON 3B', 81040185),
(36, 19, 'TON 4B', 93010037),
(37, 19, 'TON 5B', 87071174),
(38, 20, 'TON 1C', 84030335),
(39, 20, 'TON 2C', 79080979),
(40, 20, 'TON 3C', 79030182),
(41, 20, 'TON 4C', 87101529),
(42, 20, 'TON 5C', 83090122),
(43, 21, 'TON 1D', 78091060),
(44, 21, 'TON 2D', 84030092),
(45, 21, 'TON 3D', 82120008),
(47, 21, 'TON 4D', 85011693),
(48, 21, 'TON 5D', 83100638),
(49, 22, 'TON 1E', 82040232),
(50, 22, 'TON 2E', 80030298),
(51, 22, 'TON 3E', 81100699),
(52, 22, 'TON 4E', 92060111),
(53, 23, 'TON 1F', 71060116),
(54, 23, 'TON 2F', 84110342),
(55, 23, 'TON 3F', 89050099),
(56, 23, 'TON 4F', 87050007);

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pangkat_id` int UNSIGNED DEFAULT NULL,
  `angkatan_id` int UNSIGNED DEFAULT NULL,
  `pleton_id` int UNSIGNED DEFAULT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nosis` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomor_induk` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `user_id`, `nama`, `pangkat_id`, `angkatan_id`, `pleton_id`, `foto`, `nosis`, `nomor_induk`) VALUES
(3, 3, 'Administrator Utama', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int UNSIGNED NOT NULL,
  `nama_role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `nama_role`) VALUES
(1, 'Admin'),
(2, 'Operator'),
(3, 'Gadik'),
(4, 'Danton'),
(5, 'Danki'),
(6, 'Danyon'),
(7, 'Siswa');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nosis` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pleton_id` int UNSIGNED DEFAULT NULL,
  `angkatan_id` int UNSIGNED DEFAULT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role_id` int UNSIGNED DEFAULT '7'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `user_id`, `nama`, `nosis`, `pleton_id`, `angkatan_id`, `foto`, `role_id`) VALUES
(3811, 4646, 'HANA KAMILIYA RAMADHANI', '001', 27, 1, NULL, 7),
(3812, 4647, 'NADYA CANTIKA SYAKIB', '002', 27, 1, NULL, 7),
(3813, 4648, 'ZEFANYA POULINA GERUNGAN', '003', 27, 1, '1784652206_031d6827d71a8deca2cb.png', 7),
(3814, 4649, '\'HANISYAH PRATIWI', '004', 27, 1, NULL, 7),
(3815, 4650, 'FIRAWANDA YUSUF ', '005', 27, 1, NULL, 7),
(3816, 4651, 'CABHICA SAHROTUSITA', '006', 27, 1, NULL, 7),
(3817, 4652, 'CLAUDIA ELIZA TEXEIRA DE FATIMA', '007', 27, 1, NULL, 7),
(3818, 4653, 'GIZKA AZZAHRA AULIA', '008', 27, 1, NULL, 7),
(3819, 4654, 'LILA SANDRA', '009', 27, 1, NULL, 7),
(3820, 4655, 'SALSABILA BINTANG DENAD', '010', 27, 1, NULL, 7),
(3821, 4656, 'ERNIA ROSITA', '011', 27, 1, NULL, 7),
(3822, 4657, 'ALIA TRIDEWANTARI', '012', 27, 1, NULL, 7),
(3823, 4658, 'OLIVIA TULLAH', '013', 27, 1, NULL, 7),
(3824, 4659, 'TARULI OKTAVIANI', '014', 27, 1, NULL, 7),
(3825, 4660, 'NABILLA ARAFANI', '015', 27, 1, NULL, 7),
(3826, 4661, 'ROMA YARTA BR. SITORUS', '016', 27, 1, NULL, 7),
(3827, 4662, '\'SRI RATU AZ-ZAHWA ISTAN SYAKIRA', '017', 27, 1, NULL, 7),
(3828, 4663, 'RENTAH TUKING IKTIYARSIH', '018', 27, 1, NULL, 7),
(3829, 4664, '\'ECHA JEIN PUSUNG', '019', 27, 1, NULL, 7),
(3830, 4665, 'DIANA PUTRI AURELIA', '020', 27, 1, NULL, 7),
(3831, 4666, 'CHILLA KIFANI AMELIA', '021', 27, 1, NULL, 7),
(3832, 4667, 'SINTA AMELYA PUTRI', '022', 27, 1, NULL, 7),
(3833, 4668, '\'NI PUTU NIKEN SAFITRI', '023', 27, 1, NULL, 7),
(3834, 4669, 'ANGGUN TITHA CLARISA DEWI', '024', 27, 1, NULL, 7),
(3835, 4670, 'NOVALYA RAMANDA, A.Md., Gz.', '025', 27, 1, NULL, 7),
(3836, 4671, 'ANDHIEN AUFA KANAAH', '026', 28, 1, NULL, 7),
(3837, 4672, 'DWI E CLAWDIA SIRAIT', '027', 28, 1, NULL, 7),
(3838, 4673, 'NURUL ISTIQOMAH', '028', 28, 1, NULL, 7),
(3839, 4674, 'AUDREY RASCA ZENOBIA', '029', 28, 1, NULL, 7),
(3840, 4675, '\'AZAHRA RASYA', '030', 28, 1, NULL, 7),
(3841, 4676, 'ARSYINIANA MARYDHEA SALSABILA', '031', 28, 1, NULL, 7),
(3842, 4677, 'NAILA ZAIN ZAHABIYAH ', '032', 28, 1, NULL, 7),
(3843, 4678, 'AULIA ALFADILAH TAMIL', '033', 28, 1, NULL, 7),
(3844, 4679, 'GITA RAINA DEWI', '034', 28, 1, NULL, 7),
(3845, 4680, 'YONANDA MITA HARI DYAHWATI', '035', 28, 1, NULL, 7),
(3846, 4681, 'HAYU MAS WRESPATININGSIH', '036', 28, 1, NULL, 7),
(3847, 4682, 'NADINE AURELIA CHANDRA', '037', 28, 1, NULL, 7),
(3848, 4683, 'BINTANG ARUMIZELLA PRASETIYA', '038', 28, 1, NULL, 7),
(3849, 4684, 'ELVINA  SUZASTI HAKIM', '039', 28, 1, NULL, 7),
(3850, 4685, 'DITA AULIA', '040', 28, 1, NULL, 7),
(3851, 4686, 'MARITZA AINUN BALQIS', '041', 28, 1, NULL, 7),
(3852, 4687, 'DEFI NURMARSAWATI', '042', 28, 1, NULL, 7),
(3853, 4688, 'CYNTIA NOVALIN', '043', 28, 1, NULL, 7),
(3854, 4689, 'PUTRI LA SANGADJI', '044', 28, 1, NULL, 7),
(3855, 4690, 'ARINA DEBORA FRANIDA TAKWIN', '045', 28, 1, NULL, 7),
(3856, 4691, '\'RAISYA CAHYA RAMADHANY', '046', 28, 1, NULL, 7),
(3857, 4692, 'KHOSYI HURIN ANGGRAINI', '047', 28, 1, NULL, 7),
(3858, 4693, 'CALISTA AJENG KARIN OKTAVIANI', '048', 28, 1, NULL, 7),
(3859, 4694, 'JENNIE RAISSA SARASWATI', '049', 28, 1, NULL, 7),
(3860, 4695, 'ADILA DESVI RAHAYU', '050', 28, 1, NULL, 7),
(3861, 4696, '\'CHINTYA RUT INOLA SILALAHI', '051', 29, 1, NULL, 7),
(3862, 4697, 'MILASARI RAHAYU', '052', 29, 1, NULL, 7),
(3863, 4698, 'NIA RAMADANI', '053', 29, 1, NULL, 7),
(3864, 4699, '\'GITA APRILA CARISSA', '054', 29, 1, NULL, 7),
(3865, 4700, 'KEMALA HUSADA', '055', 29, 1, NULL, 7),
(3866, 4701, 'DARLENE ALEXA LASSA', '056', 29, 1, NULL, 7),
(3867, 4702, 'INDIKA LIASTI RIZKI', '057', 29, 1, NULL, 7),
(3868, 4703, 'AGNES ROSSALIN GLESSYA WANGGAI', '058', 29, 1, NULL, 7),
(3869, 4704, 'FRISTA RAGILIA', '059', 29, 1, NULL, 7),
(3870, 4705, 'ADELIA DWI PUTRI JULIANTY', '060', 29, 1, NULL, 7),
(3871, 4706, 'NADIRAH NURVANIA SARI', '061', 29, 1, NULL, 7),
(3872, 4707, 'KEYSA AURELIA RAMADHANI ', '062', 29, 1, NULL, 7),
(3873, 4708, 'MARIA LALA FAUSTINA GIRSANG', '063', 29, 1, NULL, 7),
(3874, 4709, 'SALFA ZAHIYYA PUTRI WIBAWA', '064', 29, 1, NULL, 7),
(3875, 4710, 'ELISA SAFITRI', '065', 29, 1, NULL, 7),
(3876, 4711, 'KADEK ADI ASIH', '066', 29, 1, NULL, 7),
(3877, 4712, 'DORCI ANTINA JARUTENAN', '067', 29, 1, NULL, 7),
(3878, 4713, 'BUNGA HARUM DANI', '068', 29, 1, NULL, 7),
(3879, 4714, 'ANGGUN PUTRI TAMI INAKU', '069', 29, 1, NULL, 7),
(3880, 4715, 'JESICA BEAUTY SARAGIH', '070', 29, 1, NULL, 7),
(3881, 4716, '\'NADYNE AURIL MAULIDYA', '071', 29, 1, NULL, 7),
(3882, 4717, 'DIAN KRISNA PUTRI', '072', 29, 1, NULL, 7),
(3883, 4718, 'NANDA OLIVIA BANUREA', '073', 29, 1, NULL, 7),
(3884, 4719, 'NADINE ANASTASIA MANURUNG', '074', 29, 1, NULL, 7),
(3885, 4720, 'ELZA APRILIA ENDIANA, S.Kom.', '075', 29, 1, NULL, 7),
(3886, 4721, 'SHOLIHAH DWI HANDAYANI', '076', 31, 1, NULL, 7),
(3887, 4722, '\'EZZA SEFTRI AZIZAH', '077', 31, 1, NULL, 7),
(3888, 4723, 'JUNITA AULIA ZAHRA', '078', 31, 1, NULL, 7),
(3889, 4724, 'NAJWAH AQILA MUKHLIS', '079', 31, 1, NULL, 7),
(3890, 4725, '\'JIHAN AULIANY PUTRI', '080', 31, 1, NULL, 7),
(3891, 4726, 'ALKHONSA KHAIRUNNISA OANG', '081', 31, 1, NULL, 7),
(3892, 4727, 'ALYSHA NAIRA PUTRI', '082', 31, 1, NULL, 7),
(3893, 4728, 'FEBRILINA SESILIA YARANGGA', '083', 31, 1, NULL, 7),
(3894, 4729, 'ELYZABETH BR MANALU', '084', 31, 1, NULL, 7),
(3895, 4730, 'DITA INDAH CAHYANI', '085', 31, 1, NULL, 7),
(3896, 4731, 'NI KADEK KEISYA MEILANI PUTRI', '086', 31, 1, NULL, 7),
(3897, 4732, 'FADZA SERLY NORITA', '087', 31, 1, NULL, 7),
(3898, 4733, 'NANDA ELYSIA PUTRI', '088', 31, 1, NULL, 7),
(3899, 4734, '\'NABILA WIDYASARI', '089', 31, 1, NULL, 7),
(3900, 4735, 'NADIYA MEILANI', '090', 31, 1, NULL, 7),
(3901, 4736, 'SALSABILLAH BAHRI', '091', 31, 1, NULL, 7),
(3902, 4737, 'JAUZA DELIYA ASAHY', '092', 31, 1, NULL, 7),
(3903, 4738, 'SALSA SYAHFITRI SAENAL', '093', 31, 1, NULL, 7),
(3904, 4739, '\'NADINE SHAKILA JULIANTI PAEMBONAN', '094', 31, 1, NULL, 7),
(3905, 4740, 'NASYWA NASYILLA PUTRI', '095', 31, 1, NULL, 7),
(3906, 4741, 'SALWA ZASKIA NASUTION', '096', 31, 1, NULL, 7),
(3907, 4742, 'DELLYANA ALELENAN UWURATUW', '097', 31, 1, NULL, 7),
(3908, 4743, '\'ANGEL OLIVIA STIVANYA SAA', '098', 31, 1, NULL, 7),
(3909, 4744, 'FITRY AMELYA', '099', 31, 1, NULL, 7),
(3910, 4745, 'HANINDAR SEPTY WIDYANI', '100', 31, 1, NULL, 7),
(3911, 4746, 'NAJWA NUR AQILLAH', '101', 32, 1, NULL, 7),
(3912, 4747, 'MARSYA RIFDHA FADILA', '102', 32, 1, NULL, 7),
(3913, 4748, 'OLIVIA FADLILAH EKA PUTRI', '103', 32, 1, NULL, 7),
(3914, 4749, 'AYUNDHA ADHELIA TARMIZI', '104', 32, 1, NULL, 7),
(3915, 4750, 'DELIMA VEBRINA PERANGIN-ANGIN', '105', 32, 1, NULL, 7),
(3916, 4751, 'SUNARTI ', '106', 32, 1, NULL, 7),
(3917, 4752, '\'HILVA MARTDITYA SUCIATRI', '107', 32, 1, NULL, 7),
(3918, 4753, 'QUISEPINA ANASTASIA T. OLIN', '108', 32, 1, NULL, 7),
(3919, 4754, 'ANISAH DHIYAA AZZAHRA PUTRI', '109', 32, 1, NULL, 7),
(3920, 4755, 'EVIRSYA LAURA SALSABILLA', '110', 32, 1, NULL, 7),
(3921, 4756, '\'RACHELIA PUTRI CHANTICA TAMBUNAN', '111', 32, 1, NULL, 7),
(3922, 4757, 'NI MADE HARDIYANTI APRILIA PUTRI', '112', 32, 1, NULL, 7),
(3923, 4758, 'BUNGA AULIA KARTIKA', '113', 32, 1, NULL, 7),
(3924, 4759, 'CHARINA PUTRI PRATAMA', '114', 32, 1, NULL, 7),
(3925, 4760, '\'GYANINGRUM SALSABILA PUTRI', '115', 32, 1, NULL, 7),
(3926, 4761, 'ANGGITA KIFKA PRATIWI', '116', 32, 1, NULL, 7),
(3927, 4762, 'NUR MAYLAFAYZA ', '117', 32, 1, NULL, 7),
(3928, 4763, 'IHDINA SALSABILA', '118', 32, 1, NULL, 7),
(3929, 4764, 'LISA HARIADI', '119', 32, 1, NULL, 7),
(3930, 4765, 'DESTY ZULIA YUNENGSIH', '120', 32, 1, NULL, 7),
(3931, 4766, '\'BELLA ASTY WEKOILA', '121', 32, 1, NULL, 7),
(3932, 4767, 'IRENE OPHELIA BR SIHOMBING', '122', 32, 1, NULL, 7),
(3933, 4768, 'RAIVA NABILLA MUSTARI MAY, A.Md.Kep.', '123', 32, 1, NULL, 7),
(3934, 4769, 'NAJMA ALIN FARAHIYAH', '124', 32, 1, NULL, 7),
(3935, 4770, 'NAJWA TRI FAZIEDA', '125', 32, 1, NULL, 7),
(3936, 4771, 'RAYLA MARWA SYEIKHA', '126', 33, 1, NULL, 7),
(3937, 4772, 'INDAH PUTRI MUTIARA HASIM', '127', 33, 1, NULL, 7),
(3938, 4773, '\'FHADILLA RIZZA SYAFITRI MARSYA', '128', 33, 1, NULL, 7),
(3939, 4774, 'NUR HALISA', '129', 33, 1, NULL, 7),
(3940, 4775, '\'VIANESTI SEIRA', '130', 33, 1, NULL, 7),
(3941, 4776, 'CHINTYA ANGELA SULU', '131', 33, 1, NULL, 7),
(3942, 4777, 'RANI INTAN PERMATA SARI', '132', 33, 1, NULL, 7),
(3943, 4778, 'BINTAN WIBI LIANO', '133', 33, 1, NULL, 7),
(3944, 4779, 'IDA AYU KIRANA MAHESWARI', '134', 33, 1, NULL, 7),
(3945, 4780, 'RISKA AISYAH APRILIANI', '135', 33, 1, NULL, 7),
(3946, 4781, 'REGITA PRICLLYA WIJAYA', '136', 33, 1, NULL, 7),
(3947, 4782, 'LATIFAH NOVALINA', '137', 33, 1, NULL, 7),
(3948, 4783, '\'ANI WIJAYA', '138', 33, 1, NULL, 7),
(3949, 4784, 'DINDA RAMDANI PUTRI', '139', 33, 1, NULL, 7),
(3950, 4785, 'GENDIS MISA NUGROHO', '140', 33, 1, NULL, 7),
(3951, 4786, 'MANUELLA ANATHANSA PESIRERON', '141', 33, 1, NULL, 7),
(3952, 4787, 'BAIQ DAIVA NURRIZKY', '142', 33, 1, NULL, 7),
(3953, 4788, 'JIHAN PUTRI FAISMA', '143', 33, 1, NULL, 7),
(3954, 4789, '\'ESTIANA RAHAYU', '144', 33, 1, NULL, 7),
(3955, 4790, 'SYAHIRAH DURRATUL AIDA', '145', 33, 1, NULL, 7),
(3956, 4791, 'NATASYA', '146', 33, 1, NULL, 7),
(3957, 4792, 'SONTIARA SIRAIT', '147', 33, 1, NULL, 7),
(3958, 4793, '\'ALICIA CLAUDIA KAFIAR', '148', 33, 1, NULL, 7),
(3959, 4794, 'ZAHRA FANILASARI', '149', 33, 1, NULL, 7),
(3960, 4795, 'AKIYLA AGHNA SYAZAFA', '150', 33, 1, NULL, 7),
(3961, 4796, 'NAJMI ZAHIRAH ', '151', 34, 1, NULL, 7),
(3962, 4797, 'AKIRA HENING LINTANG PRAMAFIKA', '152', 34, 1, NULL, 7),
(3963, 4798, 'NI MADE DWI LESTARI', '153', 34, 1, NULL, 7),
(3964, 4799, '\'NAYLA ASBI', '154', 34, 1, NULL, 7),
(3965, 4800, 'PETRINA', '155', 34, 1, NULL, 7),
(3966, 4801, 'TARA PUTRI UTAMI', '156', 34, 1, NULL, 7),
(3967, 4802, 'NURUL LAILI NASUKI', '157', 34, 1, NULL, 7),
(3968, 4803, 'LUH PUTU SINTYA WULANDARI', '158', 34, 1, NULL, 7),
(3969, 4804, 'EVITA EKA RIANTI', '159', 34, 1, NULL, 7),
(3970, 4805, 'TAHTA AURA AYU BUNGA', '160', 34, 1, NULL, 7),
(3971, 4806, '\'SYAFIRA NUR FIDIA', '161', 34, 1, NULL, 7),
(3972, 4807, 'KEISYA YUNDZIRA EFENDI', '162', 34, 1, NULL, 7),
(3973, 4808, 'RIZKA UMARI', '163', 34, 1, NULL, 7),
(3974, 4809, 'CHELSEA CHELSANY WAHYU PUTRI WARJIYO', '164', 34, 1, NULL, 7),
(3975, 4810, 'FAYLA HARSHINTA AYU LAKSANA', '165', 34, 1, NULL, 7),
(3976, 4811, 'JIHAN FAZILA AZ-ZAHRA. A', '166', 34, 1, NULL, 7),
(3977, 4812, 'REVA LIZA', '167', 34, 1, NULL, 7),
(3978, 4813, 'PUTRI KUANTIN ENOCH', '168', 34, 1, NULL, 7),
(3979, 4814, 'TASYAUL SALSABILA IBRAHIM ', '169', 34, 1, NULL, 7),
(3980, 4815, 'ASIFA SALSABILA PANE', '170', 34, 1, NULL, 7),
(3981, 4816, '\'QWEN MALIKA BINTANG PATIALO', '171', 34, 1, NULL, 7),
(3982, 4817, 'ADINDA GITA AULIA', '172', 34, 1, NULL, 7),
(3983, 4818, 'REYNARA ANGGREFA AZZAELA', '173', 34, 1, NULL, 7),
(3984, 4819, 'KAYLA AZZAHRA RAMADHANI', '174', 34, 1, NULL, 7),
(3985, 4820, 'MARIA ORIANA DWI DAISY', '175', 34, 1, NULL, 7),
(3986, 4821, 'BILQIS ANDINI SITOMPUL', '176', 35, 1, NULL, 7),
(3987, 4822, 'REVALIA DWI AGUSTIN PENDI', '177', 35, 1, NULL, 7),
(3988, 4823, 'CECILIA AYUWENTHI A.', '178', 35, 1, NULL, 7),
(3989, 4824, 'REYHAN HAFIZHA PUTRI SANTOSO', '179', 35, 1, NULL, 7),
(3990, 4825, '\'MUTHIA FADHILAH', '180', 35, 1, NULL, 7),
(3991, 4826, 'NEYSHA RACHMA MAULODYA', '181', 35, 1, NULL, 7),
(3992, 4827, 'KRISDAMAYANTI', '182', 35, 1, NULL, 7),
(3993, 4828, 'ADE LWEIS AZHAR', '183', 35, 1, NULL, 7),
(3994, 4829, 'FRANSINA PUTRI IRIANI BLESYA', '184', 35, 1, NULL, 7),
(3995, 4830, 'SHIFANIA NABILA', '185', 35, 1, NULL, 7),
(3996, 4831, 'BRITNEY ANGELINA JEFFRY', '186', 35, 1, NULL, 7),
(3997, 4832, 'FRILYA SASMITA', '187', 35, 1, NULL, 7),
(3998, 4833, 'RAIHAN SORAYA, S.Tr., Ak.', '188', 35, 1, NULL, 7),
(3999, 4834, 'AULIA RAHMA', '189', 35, 1, NULL, 7),
(4000, 4835, 'ZHINTA SALSABILA BUDILIANIK', '190', 35, 1, NULL, 7),
(4001, 4836, 'WELA SILPA PADWA ', '191', 35, 1, NULL, 7),
(4002, 4837, '\'DIVA AYODYA PRAMESWARI', '192', 35, 1, NULL, 7),
(4003, 4838, 'ARSYIKA ANANDA SUBELA', '193', 35, 1, NULL, 7),
(4004, 4839, 'QAFELI NIKITA, S.P.', '194', 35, 1, NULL, 7),
(4005, 4840, 'SINTA LAURA', '195', 35, 1, NULL, 7),
(4006, 4841, '\'DESSY RENATHA FLORA HAMO', '196', 35, 1, NULL, 7),
(4007, 4842, 'MARSHA BINTANG TRIANDINI', '197', 35, 1, NULL, 7),
(4008, 4843, 'ALFINA DWI DAMAYANTI', '198', 35, 1, NULL, 7),
(4009, 4844, 'NUR KHALISHAH', '199', 35, 1, NULL, 7),
(4010, 4845, 'NI KADEK CINDY ARYANTI', '200', 35, 1, NULL, 7),
(4011, 4846, 'DHEA AMELIA WARDANI', '201', 36, 1, NULL, 7),
(4012, 4847, 'REISHYA RESIFA KANIYA', '202', 36, 1, NULL, 7),
(4013, 4848, 'ALNISYA', '203', 36, 1, NULL, 7),
(4014, 4849, '\'KHALISTA GIA NOPERISHA', '204', 36, 1, NULL, 7),
(4015, 4850, 'NOVITA PUTRI ANGGRAINY ', '205', 36, 1, NULL, 7),
(4016, 4851, 'HASTINA', '206', 36, 1, NULL, 7),
(4017, 4852, 'YULIA MUSTIKA UMAGAPI, S.KOM', '207', 36, 1, NULL, 7),
(4018, 4853, '\'NADITA AURA FITRI', '208', 36, 1, NULL, 7),
(4019, 4854, 'RUFINA CLAUDIA RIBBY NGADHA BADHU', '209', 36, 1, NULL, 7),
(4020, 4855, 'DINDA RAFIKA SUGIANTO, A.Md.Kes.', '210', 36, 1, NULL, 7),
(4021, 4856, 'NI MADE AULIA BUDI PRATIWI', '211', 36, 1, NULL, 7),
(4022, 4857, '\'REFALINA FAZA', '212', 36, 1, NULL, 7),
(4023, 4858, 'BERLIANA IMELDA', '213', 36, 1, NULL, 7),
(4024, 4859, '\'SITI NAZWA NUR AZIZAH', '214', 36, 1, NULL, 7),
(4025, 4860, 'OKTAVIA RISKA DELLA', '215', 36, 1, NULL, 7),
(4026, 4861, 'NAIA ASYARIFAH', '216', 36, 1, NULL, 7),
(4027, 4862, 'FIZA AQILLAH', '217', 36, 1, NULL, 7),
(4028, 4863, 'DEVINA MARGARET WOOF', '218', 36, 1, NULL, 7),
(4029, 4864, 'RAHMIATY P. SUKIR', '219', 36, 1, NULL, 7),
(4030, 4865, 'FEBI OLIVIA BR DEPARI', '220', 36, 1, NULL, 7),
(4031, 4866, 'DIANOVVA AYU SUSANTO', '221', 36, 1, NULL, 7),
(4032, 4867, '\'VIDYA AURIEL SAMATA MOMOLE', '222', 36, 1, NULL, 7),
(4033, 4868, 'FAHMIYA RAHMASARI WIJAYA, S.Pd.', '223', 36, 1, NULL, 7),
(4034, 4869, 'RAMADHINA DWI AZZAHRA', '224', 36, 1, NULL, 7),
(4035, 4870, 'VANIA SELIN MULIANA', '225', 36, 1, NULL, 7),
(4036, 4871, 'INDIRA SALSABILA DRISTA AULIA', '226', 37, 1, NULL, 7),
(4037, 4872, 'SALMAA NABIILAH', '227', 37, 1, NULL, 7),
(4038, 4873, '\'DHILA SALSABILLA', '228', 37, 1, NULL, 7),
(4039, 4874, 'NAURA ALMAQFIRA', '229', 37, 1, NULL, 7),
(4040, 4875, '\'AURA MAWADHAH PIRTANA', '230', 37, 1, NULL, 7),
(4041, 4876, 'ASSELAH TERECITA LUKUAKA', '231', 37, 1, NULL, 7),
(4042, 4877, 'SALSA ANANTA', '232', 37, 1, NULL, 7),
(4043, 4878, 'NI KOMANG ANJELITA PRATIWI', '233', 37, 1, NULL, 7),
(4044, 4879, 'NASYAH JUANA YANTI SITOHANG', '234', 37, 1, NULL, 7),
(4045, 4880, 'MUTIARA ALIEFAH ', '235', 37, 1, NULL, 7),
(4046, 4881, '\'ANURA QURNIA FILAH ', '236', 37, 1, NULL, 7),
(4047, 4882, 'ANGELIQ WYNE TADJONGGA, S.H.', '237', 37, 1, NULL, 7),
(4048, 4883, 'NAFA PUTRI ANDHANI', '238', 37, 1, NULL, 7),
(4049, 4884, 'SHAVALIA SEJATINING PUTRI', '239', 37, 1, NULL, 7),
(4050, 4885, 'RIZQIA NURUL FITRIANA', '240', 37, 1, NULL, 7),
(4051, 4886, 'ANGGI MAULANI', '241', 37, 1, NULL, 7),
(4052, 4887, '\'NUR AZIZAH ALYA ISKANDAR', '242', 37, 1, NULL, 7),
(4053, 4888, 'JIHAN NADIA SHYBA', '243', 37, 1, NULL, 7),
(4054, 4889, 'CHANTIKA CHAIRUNISA', '244', 37, 1, NULL, 7),
(4055, 4890, 'MARSELA INCHE INNA TANGGU', '245', 37, 1, NULL, 7),
(4056, 4891, 'MARICE S F RERI, S.Farm.', '246', 37, 1, NULL, 7),
(4057, 4892, 'LAUDYA MUTIARA AMANDA PUTRI', '247', 37, 1, NULL, 7),
(4058, 4893, 'SILVIANA TRI SUSILOWATI', '248', 37, 1, NULL, 7),
(4059, 4894, 'TANIA MALIYESTA ', '249', 37, 1, NULL, 7),
(4060, 4895, 'RAFEYFA PUTRI ELYANA', '250', 37, 1, NULL, 7),
(4061, 4896, 'SEKARNINGTYAS AULIA MAHESWARI', '251', 38, 1, NULL, 7),
(4062, 4897, 'AGNIA NURSHABANI SAPUTRI', '252', 38, 1, NULL, 7),
(4063, 4898, '\'IKA WARDANI', '253', 38, 1, NULL, 7),
(4064, 4899, 'NURUL CHOFIFAH PATAHUDDIN', '254', 38, 1, NULL, 7),
(4065, 4900, 'PRIYATIN RATNA DUHITA', '255', 38, 1, NULL, 7),
(4066, 4901, 'CESSA ALSYAH DINI', '256', 38, 1, NULL, 7),
(4067, 4902, 'HERLINGGAN RETRANING WEDHARI', '257', 38, 1, NULL, 7),
(4068, 4903, 'NI LUH HAPPY RESTIA DEWI', '258', 38, 1, NULL, 7),
(4069, 4904, 'RAHMI UTAMI KURNIASIH', '259', 38, 1, NULL, 7),
(4070, 4905, 'SINTA HAZNA ZALFANI', '260', 38, 1, NULL, 7),
(4071, 4906, '\'LICA AULIA WULANDARI', '261', 38, 1, NULL, 7),
(4072, 4907, 'AURA DWI MAHARANI', '262', 38, 1, NULL, 7),
(4073, 4908, 'NAYLA MOZA THALITA', '263', 38, 1, NULL, 7),
(4074, 4909, 'ARTHA KI LAVENA', '264', 38, 1, NULL, 7),
(4075, 4910, 'BAIQ TIARA RAHMADANI', '265', 38, 1, NULL, 7),
(4076, 4911, 'FIDELLA CHEALSY ADIATMA', '266', 38, 1, NULL, 7),
(4077, 4912, '\'IRDA RISKY ANANDA', '267', 38, 1, NULL, 7),
(4078, 4913, 'NIKEN SHAHIA NABALYA', '268', 38, 1, NULL, 7),
(4079, 4914, 'KAYLA DITA CALYSTA', '269', 38, 1, NULL, 7),
(4080, 4915, 'KEYSI AULIA', '270', 38, 1, NULL, 7),
(4081, 4916, 'IRYANI RUMBIAK', '271', 38, 1, NULL, 7),
(4082, 4917, 'SINTIA DEWI ANGGRAENI', '272', 38, 1, NULL, 7),
(4083, 4918, 'CHIARA SYALOOMIKA MAELITE', '273', 38, 1, NULL, 7),
(4084, 4919, 'ANISA DWIJASISTA', '274', 38, 1, NULL, 7),
(4085, 4920, 'ANGELA MERICI VIRGINIA HENAN', '275', 38, 1, NULL, 7),
(4086, 4921, 'ZASKIA AURA DIAN MAHARANI', '276', 39, 1, NULL, 7),
(4087, 4922, 'ZAHRA ARIN WULANDARI', '277', 39, 1, NULL, 7),
(4088, 4923, 'DESTIANA ADELIA RAHAYU', '278', 39, 1, NULL, 7),
(4089, 4924, 'NI WAYAN INTAN AYU', '279', 39, 1, NULL, 7),
(4090, 4925, 'NABELLA ARYANI UTAMI', '280', 39, 1, NULL, 7),
(4091, 4926, 'NURSUCI MUAHDA', '281', 39, 1, NULL, 7),
(4092, 4927, 'DETI MELANDA', '282', 39, 1, NULL, 7),
(4093, 4928, 'NAURA JESICARISSA RUSDIYANTO', '283', 39, 1, NULL, 7),
(4094, 4929, 'TENGKU KESYA QAILANDRA', '284', 39, 1, NULL, 7),
(4095, 4930, 'MOZA ZAM ZAUMI', '285', 39, 1, NULL, 7),
(4096, 4931, 'ANISA WULANDARI ', '286', 39, 1, NULL, 7),
(4097, 4932, 'NURHALIZA AFIFAH', '287', 39, 1, NULL, 7),
(4098, 4933, 'KHAIRUNNISA', '288', 39, 1, NULL, 7),
(4099, 4934, 'GHANIA NAIRA AQILA', '289', 39, 1, NULL, 7),
(4100, 4935, 'ISYAH RAMADHANI S', '290', 39, 1, NULL, 7),
(4101, 4936, 'MAILAHANA NURFANI', '291', 39, 1, NULL, 7),
(4102, 4937, 'ZIVANNA HAWA OMEGA ROMER', '292', 39, 1, NULL, 7),
(4103, 4938, 'HUSNIA HUSNY', '293', 39, 1, NULL, 7),
(4104, 4939, 'ANGEL THERESIA PURBA', '294', 39, 1, NULL, 7),
(4105, 4940, '\'ANJHALIE GLORIA MELDA SOKOY', '295', 39, 1, NULL, 7),
(4106, 4941, 'FELDA MEILA ZENTDY RATASYA', '296', 39, 1, NULL, 7),
(4107, 4942, 'NOVITA NUR RAHMADI', '297', 39, 1, NULL, 7),
(4108, 4943, 'HANDAYANI', '298', 39, 1, NULL, 7),
(4109, 4944, 'NOVERENTIWI RAMADHANIFA', '299', 39, 1, NULL, 7),
(4110, 4945, 'ROSA ANTONIA DA CRUZ', '300', 39, 1, NULL, 7),
(4111, 4946, '\'DIAH PITALOKA', '301', 40, 1, NULL, 7),
(4112, 4947, 'SALWA NURUL FADHILA', '302', 40, 1, NULL, 7),
(4113, 4948, 'FITRIANI SALMA', '303', 40, 1, NULL, 7),
(4114, 4949, '\'NAZWA AZZAHRA DWIPANGGA', '304', 40, 1, NULL, 7),
(4115, 4950, 'FARADILLA', '305', 40, 1, NULL, 7),
(4116, 4951, 'SUNDARI SURYANINGRUM, A.Md.Kes', '306', 40, 1, NULL, 7),
(4117, 4952, 'NOBELIZA ZAHARA PUTRI', '307', 40, 1, NULL, 7),
(4118, 4953, 'MONIKA ULIM', '308', 40, 1, NULL, 7),
(4119, 4954, 'MICHAELA AILSA PRAMUDITYA', '309', 40, 1, NULL, 7),
(4120, 4955, 'CLSY AULIA', '310', 40, 1, NULL, 7),
(4121, 4956, 'NI KADEK DWI MELANI SUCI', '311', 40, 1, NULL, 7),
(4122, 4957, 'ARILLY ZHAFIRAH', '312', 40, 1, NULL, 7),
(4123, 4958, ' APRILIA ANNISA ROSALINA ', '313', 40, 1, NULL, 7),
(4124, 4959, '\'ZHASKYA ANANDA HERMAWAN', '314', 40, 1, NULL, 7),
(4125, 4960, 'MAHES DESY CHELSIANI', '315', 40, 1, NULL, 7),
(4126, 4961, 'NAURAH RAMADHANI PIJAR BELLA', '316', 40, 1, NULL, 7),
(4127, 4962, 'ANASTASYA PUSPA NEGARA', '317', 40, 1, NULL, 7),
(4128, 4963, 'ELA HARTANTI', '318', 40, 1, NULL, 7),
(4129, 4964, 'DEWI PRATIWY SUYONO ', '319', 40, 1, NULL, 7),
(4130, 4965, 'MARIA RIRIS GERALDINE NAPITUPULU', '320', 40, 1, NULL, 7),
(4131, 4966, 'MARLIN RUMAYOM, S.Ak.', '321', 40, 1, NULL, 7),
(4132, 4967, 'AILSA NAFISAH KIRANA', '322', 40, 1, NULL, 7),
(4133, 4968, 'DITA CATTLEYA WARPOPOR ', '323', 40, 1, NULL, 7),
(4134, 4969, 'INNES ADITIYA', '324', 40, 1, NULL, 7),
(4135, 4970, 'BETARI SISKA SARASWATI', '325', 40, 1, NULL, 7),
(4136, 4972, 'NAFISTA AULIA HUSNA', '326', 41, 1, NULL, 7),
(4137, 4973, 'DIAN ANNISA NAZHIFAH', '327', 41, 1, NULL, 7),
(4138, 4974, 'JHESIYERA IMANUELLA ATOK', '328', 41, 1, NULL, 7),
(4139, 4975, 'NAYLA RAMADANI', '329', 41, 1, NULL, 7),
(4140, 4976, 'FIRDA AINUN NAFISHA', '330', 41, 1, NULL, 7),
(4141, 4977, 'I DEWA AYU ANGGUN ABELLIA', '331', 41, 1, NULL, 7),
(4142, 4978, 'NAFISA PARSA KRESNAWAFA', '332', 41, 1, NULL, 7),
(4143, 4979, 'KEIZYA YUMNA FHADILLA PUTRI', '333', 41, 1, NULL, 7),
(4144, 4980, '\'NAYLA AULIA SYAPUTRI', '334', 41, 1, NULL, 7),
(4145, 4981, 'INDHYANI ANGEL SAPRYANA', '335', 41, 1, NULL, 7),
(4146, 4982, 'ARTIKA CINTA LAURA', '336', 41, 1, NULL, 7),
(4147, 4983, 'NURHAIDAH ', '337', 41, 1, NULL, 7),
(4148, 4984, 'AYUSTINA DELIA PRIATNA', '338', 41, 1, NULL, 7),
(4149, 4985, 'YALLANI SENASTRI. S', '339', 41, 1, NULL, 7),
(4150, 4986, 'ALDA LIAN RAMLI, S.AK.', '340', 41, 1, NULL, 7),
(4151, 4987, 'MUTIARA RAMADHANI', '341', 41, 1, NULL, 7),
(4152, 4988, 'AYLA FALISHA BR SARAGIH', '342', 41, 1, NULL, 7),
(4153, 4989, 'MAGFIRAH LAIN, S.H.', '343', 41, 1, NULL, 7),
(4154, 4990, '\'NEYNI RIANTI', '344', 41, 1, NULL, 7),
(4155, 4991, 'AULIA DIVA SAFEI', '345', 41, 1, NULL, 7),
(4156, 4992, '\'GISELA MARGARETA SIMANJUNTAK', '346', 41, 1, NULL, 7),
(4157, 4993, '\'LIDYA LESTARI SIAGIAN', '347', 41, 1, NULL, 7),
(4158, 4994, 'DIANNY VIVIN PRAMESTI AYUSITA', '348', 41, 1, NULL, 7),
(4159, 4995, 'MEYLANI TRI RAHAYU', '349', 41, 1, NULL, 7),
(4160, 4996, 'WINTER SAMDODYA', '350', 41, 1, NULL, 7),
(4161, 4997, 'ZEDHA YANAGISAWA', '351', 42, 1, NULL, 7),
(4162, 4998, 'NOVI ANUGRAH SILAEN', '352', 42, 1, NULL, 7),
(4163, 4999, 'YUDHISCA SENIYA PUTRI', '353', 42, 1, NULL, 7),
(4164, 5000, 'MARIA MASI INA SABU', '354', 42, 1, NULL, 7),
(4165, 5001, '\'AMELIA NURUL FAUZIAH', '355', 42, 1, NULL, 7),
(4166, 5002, 'NAJIAH FAUSIAH', '356', 42, 1, NULL, 7),
(4167, 5003, 'CHARISA FITRI AZZAHRA', '357', 42, 1, NULL, 7),
(4168, 5004, 'ADELIA SYALSYAH ARDINA', '358', 42, 1, NULL, 7),
(4169, 5005, 'ELLYSIA AZZAHRA RAMADHANI', '359', 42, 1, NULL, 7),
(4170, 5006, 'MEYVA EKA ALFIYANTI', '360', 42, 1, NULL, 7),
(4171, 5007, 'RAYYA WIRID ANYA GUNARTO', '361', 42, 1, NULL, 7),
(4172, 5008, '\'ALYA NINGTYAS DWI PUTRI', '362', 42, 1, NULL, 7),
(4173, 5009, 'RAFNISA AZZAHRA PUTRI UTAMA ', '363', 42, 1, NULL, 7),
(4174, 5010, 'NI MADE ARI ANTINI, S.Gz', '364', 42, 1, NULL, 7),
(4175, 5011, 'DINI AMELIA FEBRIANI', '365', 42, 1, NULL, 7),
(4176, 5012, 'ZASKIA NAJWA ALIFIA', '366', 42, 1, NULL, 7),
(4177, 5013, 'YOLANDA SELVIANI', '367', 42, 1, NULL, 7),
(4178, 5014, 'NOVA CHIENTYA, S.H', '368', 42, 1, NULL, 7),
(4179, 5015, '\'USWATUN HASANAH ', '369', 42, 1, NULL, 7),
(4180, 5016, 'AMEL BIKA MARFIANI', '370', 42, 1, NULL, 7),
(4181, 5017, 'CELSIA NAIBAHO', '371', 42, 1, NULL, 7),
(4182, 5018, 'ZAHARA ANIDA PURNOMO', '372', 42, 1, NULL, 7),
(4183, 5019, '\'DESSY NATALIA MALLO', '373', 42, 1, NULL, 7),
(4184, 5020, 'AMANDA SALSABILA', '374', 42, 1, NULL, 7),
(4185, 5021, 'SACHOWATUL AGHNIA', '375', 42, 1, NULL, 7),
(4186, 5022, 'LAURA NOVA DIVANDRA', '376', 43, 1, NULL, 7),
(4187, 5023, 'ZEFANYA MEILYANI HUTAHAEAN', '377', 43, 1, NULL, 7),
(4188, 5024, 'HIKMAH APRILIA', '378', 43, 1, NULL, 7),
(4189, 5025, 'AZIDA AHYA FATIHA', '379', 43, 1, NULL, 7),
(4190, 5026, 'ALFINA DAMAYANTI', '380', 43, 1, NULL, 7),
(4191, 5027, 'DHEA SYAFIRA', '381', 43, 1, NULL, 7),
(4192, 5028, 'NUR ANNISA AZZAHRA', '382', 43, 1, NULL, 7),
(4193, 5029, 'MARNI BANOBE', '383', 43, 1, NULL, 7),
(4194, 5030, 'ELISABET FELICIA MARISKA', '384', 43, 1, NULL, 7),
(4195, 5031, 'NATASYA AURELIANA', '385', 43, 1, NULL, 7),
(4196, 5032, 'RANIA NAZIHAH RAMADHANI', '386', 43, 1, NULL, 7),
(4197, 5033, 'MARSYA TRI ANDINI', '387', 43, 1, NULL, 7),
(4198, 5034, 'KEISHA AWANUNTY', '388', 43, 1, NULL, 7),
(4199, 5035, 'BAIQ AURA BENING', '389', 43, 1, NULL, 7),
(4200, 5036, 'PELITA PUTRI BR TAMBA', '390', 43, 1, NULL, 7),
(4201, 5037, '\'NURTAN', '391', 43, 1, NULL, 7),
(4202, 5038, 'AZAHRA PUTRI MAULANA', '392', 43, 1, NULL, 7),
(4203, 5039, 'PUTRI ANDAM DEWI', '393', 43, 1, NULL, 7),
(4204, 5040, 'KHALISAH MEI IVANCAH', '394', 43, 1, NULL, 7),
(4205, 5041, 'WAHYU MAULINI RAHMAWATI', '395', 43, 1, NULL, 7),
(4206, 5042, 'LAILATUL MUFLIKHAH', '396', 43, 1, NULL, 7),
(4207, 5043, 'GISELA AULIA PUTRI YULIANA', '397', 43, 1, NULL, 7),
(4208, 5044, 'IDA AYU MADE SINDY PATIKA KENITEN', '398', 43, 1, NULL, 7),
(4209, 5045, 'SYIFA SALSABILA, S.Ak.', '399', 43, 1, NULL, 7),
(4210, 5046, 'NATASYA NAIBORHU', '400', 43, 1, NULL, 7),
(4211, 5047, 'INTAN MAULIDIYYAH TALHA', '401', 43, 1, NULL, 7),
(4212, 5048, 'AYLA PUTRI HENDRAWIJAYA', '402', 44, 1, NULL, 7),
(4213, 5049, 'NAJWA SYIFA FITRIYANI', '403', 44, 1, NULL, 7),
(4214, 5050, 'RIZKI ASHILA PUTRI', '404', 44, 1, NULL, 7),
(4215, 5051, 'KASIH TESALONIKA BESLAR', '405', 44, 1, NULL, 7),
(4216, 5052, 'NADJUA ARTA FRESILA', '406', 44, 1, NULL, 7),
(4217, 5053, 'ANDI NUR SALSABILAH RUSTAN', '407', 44, 1, NULL, 7),
(4218, 5054, 'LIVRA VILENCIA SELFINCE PEREIRA', '408', 44, 1, NULL, 7),
(4219, 5055, 'VIVI HEPPIYATI', '409', 44, 1, NULL, 7),
(4220, 5056, 'RAYA FIRYAL ATALLA', '410', 44, 1, NULL, 7),
(4221, 5057, 'NI MADE DAIVI PUJADEWANTHI', '411', 44, 1, NULL, 7),
(4222, 5058, 'ARTIKA AYU FAUZIZAH', '412', 44, 1, NULL, 7),
(4223, 5059, '\'MEGGA KIRANA', '413', 44, 1, NULL, 7),
(4224, 5060, 'ANDITA RADISTY', '414', 44, 1, NULL, 7),
(4225, 5061, 'DEA RAHMADINI', '415', 44, 1, NULL, 7),
(4226, 5062, 'YUNITA RACHMAWATI', '416', 44, 1, NULL, 7),
(4227, 5063, 'FATMA SRI HAPSARI', '417', 44, 1, NULL, 7),
(4228, 5064, 'ZAHRA PUTRI AMANY', '418', 44, 1, NULL, 7),
(4229, 5065, 'CHINTYA SAHRANI MUPID BR PURBA', '419', 44, 1, NULL, 7),
(4230, 5066, 'RAHEL ALFONSINA HAMBERI', '420', 44, 1, NULL, 7),
(4231, 5067, 'MUTIARA', '421', 44, 1, NULL, 7),
(4232, 5068, 'CHACA TRI YUKA', '422', 44, 1, NULL, 7),
(4233, 5069, '\'GLADYA CAECILIA SHARALA SERMUMES', '423', 44, 1, NULL, 7),
(4234, 5070, 'NAILLA AINUNNUHA', '424', 44, 1, NULL, 7),
(4235, 5071, 'DHIKA AZZAHRA SYAHPUTRI', '425', 44, 1, NULL, 7),
(4236, 5072, 'KHARISMA MAULIDIYA', '426', 44, 1, NULL, 7),
(4237, 5073, 'ELSADAY TESALONIKA SIDABUTAR', '427', 45, 1, NULL, 7),
(4238, 5074, '\'NADIA ELVARETTA PUTRI MUSTOFA', '428', 45, 1, NULL, 7),
(4239, 5075, 'KEISHA EKA PUTRI', '429', 45, 1, NULL, 7),
(4240, 5076, 'NADINE AGISTA RINDARTA', '430', 45, 1, NULL, 7),
(4241, 5077, 'AYU JESTIKA ODE', '431', 45, 1, NULL, 7),
(4242, 5078, 'EMILIA KURNIA DEWI', '432', 45, 1, NULL, 7),
(4243, 5079, '\'AINUN KHAIRUNISA', '433', 45, 1, NULL, 7),
(4244, 5080, 'MELINDA KEIZYAH MANDAKA', '434', 45, 1, NULL, 7),
(4245, 5081, 'RISCA CALIGENIA FITRIATI', '435', 45, 1, NULL, 7),
(4246, 5082, 'INDAH BRURY', '436', 45, 1, NULL, 7),
(4247, 5083, 'PUTU AYU INDAH PERMATA SARI', '437', 45, 1, NULL, 7),
(4248, 5084, 'NADINE STEPHANIE H. TAMBUNAN', '438', 45, 1, NULL, 7),
(4249, 5085, 'NADYA AZALI WIBAWA ', '439', 45, 1, NULL, 7),
(4250, 5086, '\'PUTRI RYANTI NUGRAHA', '440', 45, 1, NULL, 7),
(4251, 5087, 'HAPSARI NINDYA PUTRI LESTARI', '441', 45, 1, NULL, 7),
(4252, 5088, 'AMELLIA PUTRI PRESILLIA', '442', 45, 1, NULL, 7),
(4253, 5089, 'SEPTRINAILA  AMRU', '443', 45, 1, NULL, 7),
(4254, 5090, 'DEA DESTIAN', '444', 45, 1, NULL, 7),
(4255, 5091, '\'FATIKA SALSABILA', '445', 45, 1, NULL, 7),
(4256, 5092, 'SUTRIA SUPARDIN', '446', 45, 1, NULL, 7),
(4257, 5093, 'KHOFIFAH SALSABILAH HARAHAP', '447', 45, 1, NULL, 7),
(4258, 5094, '\'FARANI PUTRI KULALEIN', '448', 45, 1, NULL, 7),
(4259, 5095, 'SHOFI NIRMA DIANTI PUTRI AJI', '449', 45, 1, NULL, 7),
(4260, 5096, 'SYIFA ARIFAH KUSUMAWIJAYA', '450', 45, 1, NULL, 7),
(4261, 5097, 'PUTRI AMANDA DIAZ VIOLLITA', '451', 45, 1, NULL, 7),
(4262, 5098, 'DIVA IFANA', '452', 47, 1, NULL, 7),
(4263, 5099, '\'ANNISA', '453', 47, 1, NULL, 7),
(4264, 5100, 'RANTI NURPANDANA QAMRAH', '454', 47, 1, NULL, 7),
(4265, 5101, '\'TIARA PUTRI NYDIA', '455', 47, 1, NULL, 7),
(4266, 5102, 'AGISTHA NADIA NUR HIDAYAH', '456', 47, 1, NULL, 7),
(4267, 5103, 'MADE CINTYA PUSPA NINGRUM', '457', 47, 1, NULL, 7),
(4268, 5104, 'FIRDA ZAHROTUL HAYATI', '458', 47, 1, NULL, 7),
(4269, 5105, 'ANANDA KHOIRUN NISA', '459', 47, 1, NULL, 7),
(4270, 5106, '\'ELSYA ELINDA QURRATU\'AIN', '460', 47, 1, NULL, 7),
(4271, 5107, 'GANIA ALFITA NURDIN', '461', 47, 1, NULL, 7),
(4272, 5108, 'RESHINTA PUTRI ASISTYA', '462', 47, 1, NULL, 7),
(4273, 5109, 'SITI AMINA', '463', 47, 1, NULL, 7),
(4274, 5110, 'RATIH LARASWATI DWI CAHYANTI', '464', 47, 1, NULL, 7),
(4275, 5111, 'RECHA ELSA JULIA PUTRI', '465', 47, 1, NULL, 7),
(4276, 5112, 'NANDA ERSA REVALINA', '466', 47, 1, NULL, 7),
(4277, 5113, 'ZAZKIA WINASRI', '467', 47, 1, NULL, 7),
(4278, 5114, '\'CINDY NIRVANA DEWI', '468', 47, 1, NULL, 7),
(4279, 5115, 'ANDI AZMI MAPPIRATU, S.H.', '469', 47, 1, NULL, 7),
(4280, 5116, 'ANNISA INDRIA RAMADHANI', '470', 47, 1, NULL, 7),
(4281, 5117, 'YOSI EVA DAHLIA. S.Ak', '471', 47, 1, NULL, 7),
(4282, 5118, 'AS SHIFA NAZRIN SALSABILA', '472', 47, 1, NULL, 7),
(4283, 5119, '\'FLORIDA SEPTIANA ANGGRAENI WAKARMAMU ', '473', 47, 1, NULL, 7),
(4284, 5120, 'ALFIYA JIHAN INSYIROH', '474', 47, 1, NULL, 7),
(4285, 5121, 'MEI PETRONELA BOYMAU, S.H', '475', 47, 1, NULL, 7),
(4286, 5122, 'EVALINE ROMAIDA', '476', 47, 1, NULL, 7),
(4287, 5123, 'NOVITA MAULIDINA', '477', 48, 1, NULL, 7),
(4288, 5124, 'GENDIS IQLIMA FADILAH FAZA', '478', 48, 1, NULL, 7),
(4289, 5125, 'NI MADE DWI KARTIKA APRIANTI', '479', 48, 1, NULL, 7),
(4290, 5126, 'NAJILAH SUKMATA HADRAMAUD ', '480', 48, 1, NULL, 7),
(4291, 5127, '\'TINA GUSMAYANI', '481', 48, 1, NULL, 7),
(4292, 5128, 'CINTIA AULIA', '482', 48, 1, NULL, 7),
(4293, 5129, 'NINING SUNDARI', '483', 48, 1, NULL, 7),
(4294, 5130, 'NADIA RIZKY PRATIDINA', '484', 48, 1, NULL, 7),
(4295, 5131, 'INTAN ZAHRATUN NUFUS', '485', 48, 1, NULL, 7),
(4296, 5132, 'GRYSELDA AUDREY WIBOWO', '486', 48, 1, NULL, 7),
(4297, 5133, '\'KARTIKA EKA RAHMA', '487', 48, 1, NULL, 7),
(4298, 5134, 'NITA ASMANG', '488', 48, 1, NULL, 7),
(4299, 5135, 'NUTRILA ILEGUS HARVIKTI', '489', 48, 1, NULL, 7),
(4300, 5136, 'FIDYAH ANGGRAINI SAMPULAWA', '490', 48, 1, NULL, 7),
(4301, 5137, 'NI MADE AYU OKA AUNDRI', '491', 48, 1, NULL, 7),
(4302, 5138, 'TIARA DWI PUTRI', '492', 48, 1, NULL, 7),
(4303, 5139, '\'GIVA SULIYANI MANGGALA', '493', 48, 1, NULL, 7),
(4304, 5140, 'INTAN CAHAYA AGAM', '494', 48, 1, NULL, 7),
(4305, 5141, '\'ANDINI PRATIWI', '495', 48, 1, NULL, 7),
(4306, 5142, 'JOICE ADINDA BR SAGALA', '496', 48, 1, NULL, 7),
(4307, 5143, 'NUN CITRA ERRY TANTYA', '497', 48, 1, NULL, 7),
(4308, 5144, 'ALIYA NOVEBRIANTI PUTRI, A.Md.Kep.', '498', 48, 1, NULL, 7),
(4309, 5145, 'DEVINA ALDELA', '499', 48, 1, NULL, 7),
(4310, 5146, 'RAISYA RAMADHANIA PUTRI DESPARTA', '500', 48, 1, NULL, 7),
(4311, 5147, 'KANAYA TABITHA PAMONDOLANG', '501', 48, 1, NULL, 7),
(4312, 5148, 'MEYLA ISNAYA DEWI', '502', 49, 1, NULL, 7),
(4313, 5149, 'LYSNAILA AJENG AFRINAFISA', '503', 49, 1, NULL, 7),
(4314, 5150, 'MARSAA CALYA LAILI', '504', 49, 1, NULL, 7),
(4315, 5151, 'AFELINA MILKHA RUMENGAN', '505', 49, 1, NULL, 7),
(4316, 5152, 'MICHELLI DEBORA LASE', '506', 49, 1, NULL, 7),
(4317, 5153, 'REZKY AMELIA', '507', 49, 1, NULL, 7),
(4318, 5154, 'ALVIANA FAJ\'RIA NINGSIH', '508', 49, 1, NULL, 7),
(4319, 5155, '\'NABILA KLARISA', '509', 49, 1, NULL, 7),
(4320, 5156, 'LAILATUL MAGHFIRO', '510', 49, 1, NULL, 7),
(4321, 5157, 'BRILLIANT FITRIANSYA RAMADANI', '511', 49, 1, NULL, 7),
(4322, 5158, 'NI PUTU ALYA VIRDA YANTI', '512', 49, 1, NULL, 7),
(4323, 5159, 'IKA AULIA ZAHRA', '513', 49, 1, NULL, 7),
(4324, 5160, 'RISKA AMELIA AGUSTINA', '514', 49, 1, NULL, 7),
(4325, 5161, 'RIZKIA DWI AZHEILLA', '515', 49, 1, NULL, 7),
(4326, 5162, 'NOLLA OLYVIA FERENDI', '516', 49, 1, NULL, 7),
(4327, 5163, 'DAFINA FADYLA SARY', '517', 49, 1, NULL, 7),
(4328, 5164, 'NASWA NAENDI ARTARINI', '518', 49, 1, NULL, 7),
(4329, 5165, 'Ns. TASYHA PRISSILLA, S. Kep ', '519', 49, 1, NULL, 7),
(4330, 5166, 'NURUL ATIFA MUQARAMAH', '520', 49, 1, NULL, 7),
(4331, 5167, 'NAZWA SALSABILA BERUH', '521', 49, 1, NULL, 7),
(4332, 5168, 'ASTINA BR GINTING', '522', 49, 1, NULL, 7),
(4333, 5169, '\'FEBIOLA OLIVIN SUEBU ', '523', 49, 1, NULL, 7),
(4334, 5170, 'TIARA HALIZA PRABAWATI', '524', 49, 1, NULL, 7),
(4335, 5171, 'CITRA MAYASARI', '525', 49, 1, NULL, 7),
(4336, 5172, 'CHAIRIS ALEA SALWA KAISZA', '526', 49, 1, NULL, 7),
(4337, 5173, 'THADDEA ARDIYANTI MAHESWARI', '527', 49, 1, NULL, 7),
(4338, 5174, 'KHESYA ALIEN PUTRI CALLISTA', '528', 50, 1, NULL, 7),
(4339, 5175, 'DEWI RAHMAH PUTRI', '529', 50, 1, NULL, 7),
(4340, 5176, 'RYZA MUTIA SALSABILLA', '530', 50, 1, NULL, 7),
(4341, 5177, 'LUH MADE GAYATRI', '531', 50, 1, NULL, 7),
(4342, 5178, 'EYIS NADIN PUTRI MAHYUDIN', '532', 50, 1, NULL, 7),
(4343, 5179, 'KANAYA AKWILA TAMPI', '533', 50, 1, NULL, 7),
(4344, 5180, '\'NAYLA KHAIRANI', '534', 50, 1, NULL, 7),
(4345, 5181, '\'SONYA SUCI AGISTA', '535', 50, 1, NULL, 7),
(4346, 5182, 'QORNELIA FEBRI', '536', 50, 1, NULL, 7),
(4347, 5183, 'YOLENTA YUNITA JEHINUT', '537', 50, 1, NULL, 7),
(4348, 5184, 'DELLA AGUSTINA', '538', 50, 1, NULL, 7),
(4349, 5185, 'SHELVIA MAHARANI, A.Md.Akun.', '539', 50, 1, NULL, 7),
(4350, 5186, 'NI KOMANG TRISNA WIDIANINGSIH', '540', 50, 1, NULL, 7),
(4351, 5187, 'ARLINDA PRASTIWI', '541', 50, 1, NULL, 7),
(4352, 5188, '\'RAZELLA DEASSAFA NINDIA.S', '542', 50, 1, NULL, 7),
(4353, 5189, 'AQILLA SHAFA AZZAHRA', '543', 50, 1, NULL, 7),
(4354, 5190, 'PRISCHA DHESTYANI KARTIKA PUTRI', '544', 50, 1, NULL, 7),
(4355, 5191, 'SEVI NURUL AINI', '545', 50, 1, NULL, 7),
(4356, 5192, 'NAZWA YUSMASYIFA AYURINDRY', '546', 50, 1, NULL, 7),
(4357, 5193, 'ALNI FEBRYANTI', '547', 50, 1, NULL, 7),
(4358, 5194, 'EVA NOVELIA DAMANIK', '548', 50, 1, NULL, 7),
(4359, 5195, 'RIDA KAPANG', '549', 50, 1, NULL, 7),
(4360, 5196, 'SALSABILA PANJAITAN', '550', 50, 1, NULL, 7),
(4361, 5197, 'FIDELYA RACHMA ADINDA', '551', 50, 1, NULL, 7),
(4362, 5198, 'SATIZAH REBRINA BR GINTING', '552', 50, 1, NULL, 7),
(4363, 5199, 'WAHYUNINGSI, S.Ak.', '553', 50, 1, NULL, 7),
(4364, 5200, 'CITRA', '554', 51, 1, NULL, 7),
(4365, 5201, 'TIARA NUR FORTUNA AULIYA', '555', 51, 1, NULL, 7),
(4366, 5202, 'DEVA ANGGRAINI', '556', 51, 1, NULL, 7),
(4367, 5203, '\'FLORA DINATA', '557', 51, 1, NULL, 7),
(4368, 5204, 'NAISYA FARRAS PUTRI ARIYANTO', '558', 51, 1, NULL, 7),
(4369, 5205, 'ZAKIAH AL-LAUZAH', '559', 51, 1, NULL, 7),
(4370, 5206, 'MARIA ERSMILDA CHIN B. NIO', '560', 51, 1, NULL, 7),
(4371, 5207, 'ALICKA RATNA PUTRI AANGGIE', '561', 51, 1, NULL, 7),
(4372, 5208, 'AULIA RAMADHANI', '562', 51, 1, NULL, 7),
(4373, 5209, 'EKA CITRA NURCAHYANI MUIN', '563', 51, 1, NULL, 7),
(4374, 5210, 'VYRDA ZERLINDA AURELLIA', '564', 51, 1, NULL, 7),
(4375, 5211, 'CARIN DWI HARTATI', '565', 51, 1, NULL, 7),
(4376, 5212, 'TIARA MAHARANI HARIANJA', '566', 51, 1, NULL, 7),
(4377, 5213, 'ROSA AMELIYA', '567', 51, 1, NULL, 7),
(4378, 5214, '\'NADIA RAHAYU', '568', 51, 1, NULL, 7),
(4379, 5215, 'AYU ERMAWATI', '569', 51, 1, NULL, 7),
(4380, 5216, 'MUTIARA DAENG BUNGA ', '570', 51, 1, NULL, 7),
(4381, 5217, 'MELI MARTA ROSITA', '571', 51, 1, NULL, 7),
(4382, 5218, 'PUTRI MITCELL MEDELIN TAMATANI', '572', 51, 1, NULL, 7),
(4383, 5219, 'INTAN JUNIARTI.M', '573', 51, 1, NULL, 7),
(4384, 5220, 'TISYA HANIFAH FEBRIANI', '574', 51, 1, NULL, 7),
(4385, 5221, '\'SUCI BINTANG NUR\'AZIZAH', '575', 51, 1, NULL, 7),
(4386, 5222, 'IKEU ROSITA, S.Pd.', '576', 51, 1, NULL, 7),
(4387, 5223, 'NIAN ANISA, S.Pt.', '577', 51, 1, NULL, 7),
(4388, 5224, 'TRI LAURA TIARA AQUIN', '578', 51, 1, NULL, 7),
(4389, 5225, 'CHRISTIN NATALIA SIAHAAN, S.P', '579', 51, 1, NULL, 7),
(4390, 5226, 'ELINDA PUTRI NUR CAHYANI', '580', 52, 1, NULL, 7),
(4391, 5227, 'MUTIARA ANINDYA RAHMADHANI', '581', 52, 1, NULL, 7),
(4392, 5228, 'ELISABETH DELLA ANGGI MAYOH', '582', 52, 1, NULL, 7),
(4393, 5229, 'AISYA CARLA', '583', 52, 1, NULL, 7),
(4394, 5230, '\'FEBIOLA V MOFU', '584', 52, 1, NULL, 7),
(4395, 5231, '\'SALSHA AMALIA', '585', 52, 1, NULL, 7),
(4396, 5232, '\'RATU SHERA CHANTIKA YUDISTIRA', '586', 52, 1, NULL, 7),
(4397, 5233, '\'SAFINA MAIZA PUTRI', '587', 52, 1, NULL, 7),
(4398, 5234, 'A.NIKMAT NUR RIZQI', '588', 52, 1, NULL, 7),
(4399, 5235, 'SINTIA', '589', 52, 1, NULL, 7),
(4400, 5236, 'PUTRI NOVA AMALIA', '590', 52, 1, NULL, 7),
(4401, 5237, 'PUTU AYU DEWI LESTARI', '591', 52, 1, NULL, 7),
(4402, 5238, 'SRI NABILA NUR AZIZAH', '592', 52, 1, NULL, 7),
(4403, 5239, 'SUCIANA YULIANI', '593', 52, 1, NULL, 7),
(4404, 5240, '\'BERLIANA TAMARA', '594', 52, 1, NULL, 7),
(4405, 5241, 'ANDI NAZALYA HUDZAIFAH', '595', 52, 1, NULL, 7),
(4406, 5242, 'NILAM CAHYA PUTRI', '596', 52, 1, NULL, 7),
(4407, 5243, 'INDAH UMI SEFTIANA', '597', 52, 1, NULL, 7),
(4408, 5244, 'SHANDY VANIA PRAYOGO', '598', 52, 1, NULL, 7),
(4409, 5245, 'CAESSA ANGGER PRADITA', '599', 52, 1, NULL, 7),
(4410, 5246, 'DEWI RENATHA SULASMI SIMAMORA', '600', 52, 1, NULL, 7),
(4411, 5247, 'ALIYA RUMAISA ', '601', 52, 1, NULL, 7),
(4412, 5248, 'RUTH TARISA GULTOM. S.Gz', '602', 52, 1, NULL, 7),
(4413, 5249, 'NADYLA TRI WULANDARI', '603', 52, 1, NULL, 7),
(4414, 5250, 'AISYAH SUKMAAYU JATININGSIH', '604', 52, 1, NULL, 7),
(4415, 5251, 'RYU ICHI MOHTAR', '605', 52, 1, NULL, 7),
(4416, 5252, 'NAYSYLA ALFINA FITRIYANTI', '606', 53, 1, NULL, 7),
(4417, 5253, 'NABILAH ZAHRA', '607', 53, 1, NULL, 7),
(4418, 5254, 'NURUL HIDAYAH', '608', 53, 1, NULL, 7),
(4419, 5255, '\'NAZWA ALYAPUTRI SISWOKO', '609', 53, 1, NULL, 7),
(4420, 5256, 'ALINTA NUR AINI', '610', 53, 1, NULL, 7),
(4421, 5257, 'ZIKHRA DWI PUTRI', '611', 53, 1, NULL, 7),
(4422, 5258, 'FIKA RAMADANI', '612', 53, 1, NULL, 7),
(4423, 5259, 'EKA', '613', 53, 1, NULL, 7),
(4424, 5260, 'AMELIA DWI ASTUTININGTYAS', '614', 53, 1, NULL, 7),
(4425, 5261, 'ATHAYA VALENCIA WARASTRI', '615', 53, 1, NULL, 7),
(4426, 5262, 'NI PUTU NADINE ARISTYA DEWI', '616', 53, 1, NULL, 7),
(4427, 5263, 'CINDYA PEBY RONA ULY MANALU', '617', 53, 1, NULL, 7),
(4428, 5264, 'BHARANI SUSI MULYANTI', '618', 53, 1, NULL, 7),
(4429, 5265, '\'BALQYS MAHARANI SETIA PUTRI', '619', 53, 1, NULL, 7),
(4430, 5266, 'SYABILLA HANIE HARINDA', '620', 53, 1, NULL, 7),
(4431, 5267, 'NAISYA ZAHRA KHAYANI', '621', 53, 1, NULL, 7),
(4432, 5268, 'ZASKIA FARAH FADHILAH', '622', 53, 1, NULL, 7),
(4433, 5269, 'OLIVIA TANGNGA', '623', 53, 1, NULL, 7),
(4434, 5270, 'NAMIRAH INTAN SURI', '624', 53, 1, NULL, 7),
(4435, 5271, 'JASYICA GUFTANI', '625', 53, 1, NULL, 7),
(4436, 5272, 'LATIFA NAILA', '626', 53, 1, NULL, 7),
(4437, 5273, 'DEWI MESRA ADIL NDRURU, S. Tr . RMIK', '627', 53, 1, NULL, 7),
(4438, 5274, '\'ROSALIN MERAHABIA', '628', 53, 1, NULL, 7),
(4439, 5275, 'RATASHA AMALIA NABILA', '629', 53, 1, NULL, 7),
(4440, 5276, 'DINDA SAFRIA RAHMA', '630', 53, 1, NULL, 7),
(4441, 5277, 'AQILLA NAFFIZA PRAMUDYAH PUTRI', '631', 53, 1, NULL, 7),
(4442, 5278, 'ROHANNA KARTIKA SARAGI', '632', 54, 1, NULL, 7),
(4443, 5279, 'ZASKIA ANDI RAMADHANI', '633', 54, 1, NULL, 7),
(4444, 5280, 'GEVIYANA SAY HOLAWNA', '634', 54, 1, NULL, 7),
(4445, 5281, 'MARVERICHA MIRACLE TAMALERO', '635', 54, 1, NULL, 7),
(4446, 5282, 'PUTRI AISYAH', '636', 54, 1, NULL, 7),
(4447, 5283, 'SUCIYATI', '637', 54, 1, NULL, 7),
(4448, 5284, 'NADIA VIVIANJANI ACHMAD', '638', 54, 1, NULL, 7),
(4449, 5285, 'ASHYLA SALSABILA', '639', 54, 1, NULL, 7),
(4450, 5286, 'MEILA SABRINA ABDILA RAHMA, S.H.', '640', 54, 1, NULL, 7),
(4451, 5287, 'NOVI CRISTINA', '641', 54, 1, NULL, 7),
(4452, 5288, 'MEI RINA ARTIKA SARI', '642', 54, 1, NULL, 7),
(4453, 5289, 'PHINA HAIRUNNISA PUTRI', '643', 54, 1, NULL, 7),
(4454, 5290, 'ASSYIFA ZAHRA NURRAHMA', '644', 54, 1, NULL, 7),
(4455, 5291, '\'DENAYA SEPTIANITA', '645', 54, 1, NULL, 7),
(4456, 5292, 'DANTI JUNIA GUMANTI', '646', 54, 1, NULL, 7),
(4457, 5293, 'ULYA KARIMAH', '647', 54, 1, NULL, 7),
(4458, 5294, 'NABILA MAHARANI', '648', 54, 1, NULL, 7),
(4459, 5295, 'RICHA AZLIA', '649', 54, 1, NULL, 7),
(4460, 5296, 'DEVINA MAHARANI ', '650', 54, 1, NULL, 7),
(4461, 5297, 'GANIA ALMAGHVIRA ASMAN', '651', 54, 1, NULL, 7),
(4462, 5298, 'NAYSELA AMELIA', '652', 54, 1, NULL, 7),
(4463, 5299, 'DINA FADILAH NIHAYAH', '653', 54, 1, NULL, 7),
(4464, 5300, 'ADINDA NOVITA SARI SIHOMBING', '654', 54, 1, NULL, 7),
(4465, 5301, '\'FIDHI AMRULLAH', '655', 54, 1, NULL, 7),
(4466, 5302, 'THARISYA RIZKINDRA PUTRIE', '656', 54, 1, NULL, 7),
(4467, 5303, 'FIOLA EMYLIA PUTRI HERDIYANTO', '657', 54, 1, NULL, 7),
(4468, 5304, 'DEBORA NIMFA CANDRA', '658', 55, 1, NULL, 7),
(4469, 5305, 'DIAH AYU PUSPITASARI', '659', 55, 1, NULL, 7),
(4470, 5306, '\'REGINA PUTRI AYU KARTINI', '660', 55, 1, NULL, 7),
(4471, 5307, '\'SALSYA HIJRIYANI YANDRA', '661', 55, 1, NULL, 7),
(4472, 5308, 'NUR FADHILAH BASRI', '662', 55, 1, NULL, 7),
(4473, 5309, 'SULISTYAWATI', '663', 55, 1, NULL, 7),
(4474, 5310, 'AFFIFA BELLA FRANITA', '664', 55, 1, NULL, 7),
(4475, 5311, 'FEBIOLA CLAUDIA DASEM', '665', 55, 1, NULL, 7),
(4476, 5312, 'DYAH NINGRUM FITRIANTI', '666', 55, 1, NULL, 7),
(4477, 5313, 'KADEK ARYA DWIPAYANI', '667', 55, 1, NULL, 7),
(4478, 5314, ' NI KADE HANI DWI WARDANI', '668', 55, 1, NULL, 7),
(4479, 5315, 'KEYSA ZAHRATU SHITA', '669', 55, 1, NULL, 7),
(4480, 5316, 'AMANDA MUNER', '670', 55, 1, NULL, 7),
(4481, 5317, '\'MUTIARA DWI MEILANI', '671', 55, 1, NULL, 7),
(4482, 5318, 'SEPTI DIAH AYU PUTRI', '672', 55, 1, NULL, 7),
(4483, 5319, 'NI PUTU VARASANTI PUTRI', '673', 55, 1, NULL, 7),
(4484, 5320, 'ZEHFINA ISMITH GAILEA', '674', 55, 1, NULL, 7),
(4485, 5321, 'ASRI DEWI PRASASTI', '675', 55, 1, NULL, 7),
(4486, 5322, 'MERTI SAIBA', '676', 55, 1, NULL, 7),
(4487, 5323, 'PUTRI MULYANI', '677', 55, 1, NULL, 7),
(4488, 5324, '\'WA ODE NUR HIDAYATI', '678', 55, 1, NULL, 7),
(4489, 5325, 'SYAWWALLY RISFA', '679', 55, 1, NULL, 7),
(4490, 5326, 'AMORY ANGLOLICA BR DAMANIK', '680', 55, 1, NULL, 7),
(4491, 5327, '\'IRMA MIRANDA', '681', 55, 1, NULL, 7),
(4492, 5328, 'SAFNA KAILA SANGGITA', '682', 55, 1, NULL, 7),
(4493, 5329, 'KEYZA NAILAH BANITAWATI', '683', 55, 1, NULL, 7),
(4494, 5330, 'FARAH ALFIYAH NOVIYANTI', '684', 56, 1, NULL, 7),
(4495, 5331, '\'ZAINA PUTRI AISYABAH', '685', 56, 1, NULL, 7),
(4496, 5332, '\'NIA RAMADANI', '686', 56, 1, NULL, 7),
(4497, 5333, 'KAYLA TAKBIRANI AMIRUDDIN', '687', 56, 1, NULL, 7),
(4498, 5334, 'VITRI WIJI UTAMI', '688', 56, 1, NULL, 7),
(4499, 5335, 'AMANDA NATASIA BR TARIGAN', '689', 56, 1, NULL, 7),
(4500, 5336, 'NI LUH SINTYA NINGSIH, S.Agr.', '690', 56, 1, NULL, 7),
(4501, 5337, 'KHANZA OKTA AULIA', '691', 56, 1, NULL, 7),
(4502, 5338, 'AURA CALITA PUJA', '692', 56, 1, NULL, 7),
(4503, 5339, 'NI PUTU FEMMY PARAMITA FEBRIYANTI', '693', 56, 1, NULL, 7),
(4504, 5340, '\'AMELIA SEPTIANI PUTRI', '694', 56, 1, NULL, 7),
(4505, 5341, 'MONALISA PUTRI PATRISIA SIDUAN', '695', 56, 1, NULL, 7),
(4506, 5342, 'SYOFIATUL HANANI', '696', 56, 1, NULL, 7),
(4507, 5343, 'NISRINA SARI', '697', 56, 1, NULL, 7),
(4508, 5344, 'RONA NASWA RUSWINA HASAN', '698', 56, 1, NULL, 7),
(4509, 5345, 'NAZWA ADELIA PUTRI SUTARYA', '699', 56, 1, NULL, 7),
(4510, 5346, 'SYAKILA NAYA KUSUMA', '700', 56, 1, NULL, 7),
(4511, 5347, 'NUR ANIZA', '701', 56, 1, NULL, 7),
(4512, 5348, 'ZULAYKA SABINA ARFIES', '702', 56, 1, NULL, 7),
(4513, 5349, 'RAISA AURA NATASYA', '703', 56, 1, NULL, 7),
(4514, 5350, 'AMELIA ELSHA PORMARA', '704', 56, 1, NULL, 7),
(4515, 5351, 'EVA SEPTIANINGRUM', '705', 56, 1, NULL, 7),
(4516, 5352, 'SYIFA WIDYA CAHYANI', '706', 56, 1, NULL, 7),
(4517, 5353, '\'ESTHI STEVANIE HAMADI ', '707', 56, 1, NULL, 7),
(4518, 5354, 'RASYA PUTRI AURA ANUGERAH', '708', 56, 1, NULL, 7),
(4519, 5355, 'NAILA AUFA NADIYYA', '709', 56, 1, NULL, 7);

-- --------------------------------------------------------

--
-- Table structure for table `siswa_mapel`
--

CREATE TABLE `siswa_mapel` (
  `id` int UNSIGNED NOT NULL,
  `profile_id` int UNSIGNED NOT NULL,
  `mapel_id` int UNSIGNED NOT NULL,
  `nilai` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soal`
--

CREATE TABLE `soal` (
  `id` int UNSIGNED NOT NULL,
  `jadwal_ujian_id` int UNSIGNED NOT NULL COMMENT 'Relasi ke jadwal_ujian.id',
  `pertanyaan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `opsi_a` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `opsi_b` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `opsi_c` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `opsi_d` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `opsi_e` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `kunci_jawaban` enum('A','B','C','D','E') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bobot` int UNSIGNED DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soal_obe`
--

CREATE TABLE `soal_obe` (
  `id` int UNSIGNED NOT NULL,
  `kelas_ujian_id` int UNSIGNED DEFAULT NULL,
  `mapel_id` int DEFAULT NULL,
  `cpmk` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Capaian Pembelajaran Mata Kuliah',
  `cpl` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Capaian Pembelajaran Lulusan',
  `tingkat_taksonomi` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Contoh: C1-C6',
  `bobot_soal` decimal(5,2) DEFAULT '0.00' COMMENT 'Bobot total soal dalam persentase',
  `pertanyaan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rubrik_penilaian` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Panduan/kriteria umum penilaian',
  `created_by` int UNSIGNED DEFAULT NULL COMMENT 'FK ke pegawai.id pembuat soal',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `soal_obe`
--

INSERT INTO `soal_obe` (`id`, `kelas_ujian_id`, `mapel_id`, `cpmk`, `cpl`, `tingkat_taksonomi`, `bobot_soal`, `pertanyaan`, `rubrik_penilaian`, `created_by`, `created_at`, `updated_at`) VALUES
(36, 10, 6, 'CPMK-C1', 'CPL-DEFAULT', 'C1', '0.00', 'Soal satu sam sau', 'jawab dua sama dua', NULL, '2026-08-16 23:26:01', '2026-08-16 23:27:14'),
(37, 11, 7, 'CPMK-C1', 'CPL-DEFAULT', 'C1', '0.00', 'ujian 1', 'jawab', NULL, '2026-08-16 23:31:29', '2026-08-17 16:52:46'),
(38, 11, 7, 'CPMK-C1', 'CPL-DEFAULT', 'C1', '0.00', 'soal 2', 'jwb', NULL, '2026-08-17 16:53:07', '2026-08-17 16:53:07'),
(39, 11, 7, 'CPMK-C1', 'CPL-DEFAULT', 'C1', '0.00', 'soal 3', 'jawab', NULL, '2026-08-17 16:53:25', '2026-08-17 16:53:25'),
(40, 11, 7, 'CPMK-C1', 'CPL-DEFAULT', 'C1', '0.00', 'soal 4', 'jawab', NULL, '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(41, 11, 7, 'CPMK-C1', 'CPL-DEFAULT', 'C1', '0.00', 'soal 5', 'jawab', NULL, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(42, 11, 7, 'CPMK-C1', 'CPL-DEFAULT', 'C1', '0.00', 'soal 6', 'jawab', NULL, '2026-08-17 16:54:31', '2026-08-17 16:54:31'),
(43, 11, 7, 'CPMK-C2', 'CPL-DEFAULT', 'C2', '0.00', 'soal 7', 'jawab', NULL, '2026-08-17 16:55:14', '2026-08-17 16:55:14'),
(44, 11, 7, 'CPMK-C2', 'CPL-DEFAULT', 'C2', '0.00', 'soal 8', 'jawab', NULL, '2026-08-17 16:55:37', '2026-08-17 16:55:37'),
(45, 11, 7, 'CPMK-C2', 'CPL-DEFAULT', 'C2', '0.00', 'soal 9', 'jawab', NULL, '2026-08-17 16:55:53', '2026-08-17 16:55:53'),
(46, 11, 7, 'CPMK-C3', 'CPL-DEFAULT', 'C3', '0.00', 'saol 10', 'jawab', NULL, '2026-08-17 16:56:14', '2026-08-17 16:56:14'),
(47, 11, 7, 'CPMK-C3', 'CPL-DEFAULT', 'C3', '0.00', 'soal 11', 'jawab', NULL, '2026-08-17 16:56:55', '2026-08-17 16:56:55'),
(48, 11, 7, 'CPMK-C4', 'CPL-DEFAULT', 'C4', '0.00', 'soal 12', 'jawab', NULL, '2026-08-17 16:57:11', '2026-08-17 16:57:11'),
(49, 11, 7, 'CPMK-C5', 'CPL-DEFAULT', 'C5', '0.00', 'soal 13', 'jawab', NULL, '2026-08-17 16:57:45', '2026-08-17 16:57:45'),
(50, 11, 7, 'CPMK-C6', 'CPL-DEFAULT', 'C6', '0.00', 'level 6', 'jawab', NULL, '2026-08-17 16:58:02', '2026-08-17 16:58:02');

-- --------------------------------------------------------

--
-- Table structure for table `soal_obe_rubrik`
--

CREATE TABLE `soal_obe_rubrik` (
  `id` int UNSIGNED NOT NULL,
  `soal_id` int UNSIGNED NOT NULL,
  `nama_dimensi` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Contoh: Ketepatan Substansi & Konsep',
  `bobot` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Bobot kriteria (misal: 30, 25, 15)',
  `skor_maksimal` int UNSIGNED NOT NULL DEFAULT '4' COMMENT 'Skala maksimal skor (default 4)',
  `urutan` int UNSIGNED DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role_id` int UNSIGNED NOT NULL,
  `pegawai_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role_id`, `pegawai_id`) VALUES
(3, 'admin', '$2y$10$mvgGbi/Hle8ntrv8p9LpnuWki.w5UMeG.QOV5RdJfpyHIaYnGVbzK', 1, NULL),
(4495, '71070150', '$2y$10$Z/qJzUUHYGzINiVY4DA7y.VY71q/71luGzNlV7TO/4Q1goXOyAiLe', 3, NULL),
(4496, '87111359', '$2y$10$J9kpTnD22ZcuqHOTzxtoS.44x0bAKE9CTEIT0G88lagU04RyqUqQ.', 3, NULL),
(4498, '72120024', '$2y$10$/zpYKZT11uS5CMyY/.9ApOYkYPyqljFezDBVlu2CyNrYvneCp3P9K', 3, NULL),
(4499, '71120445', '$2y$10$qxazlH9CfPFKmb/cX7e2ce5Uh8NNWTtkwZbNoUkD61V3735H54zl2', 3, NULL),
(4501, '69120059', '$2y$10$fK6hzJkXc8ltgfGRr9ENEOOqh6RF2DEEQ9oGm90Rj7VikR.WOCQwy', 3, NULL),
(4502, '71120253', '$2y$10$.MuNMQy8EICUe4VZUWKIPOcisqV6QcnxUrcIJxwLaiNQ8NJeMLo7G', 3, NULL),
(4503, '77030007', '$2y$10$.KIscyc2TyYcdCE1/77HR.bwl5c7OgUFxyr3JyybJz0igMtLVbUV2', 3, NULL),
(4504, '74070015', '$2y$10$rCC1Pa9BkcUck2cLhcM9Nu1vc/4v0sIC.WqpM7ifdMRUS68BATTTa', 3, NULL),
(4507, '73070565', '$2y$10$thdhG0jh9reUn.yJPKhe7.DI3LusSyZHeoYKgCsfcSo/sWul./uvW', 3, NULL),
(4508, '75010460', '$2y$10$su4XDzZ//7Kt0YKIJbyvUO7LJmShJds5.oSaBLt1USeLlcvp14uZi', 3, NULL),
(4511, '71060116', '$2y$10$ty.DRNAOlSnYpINIQKtn2eLbquln2KhI7Pshd1AjkJ0ufYSADUIUC', 3, NULL),
(4512, '80030298', '$2y$10$PwTPfT3n587q3j14C/HuPuy3OqWtFY0EO1lgHGu1Ftm5b2jMewdOu', 3, NULL),
(4513, '81040185', '$2y$10$A6C.DqOrCPlcDy6nfQARxOBG0XdqZCRNq8hkVdp6uqx66nqYIWtdq', 4, NULL),
(4514, '82040232', '$2y$10$IIpTFiv5d7EIAezbcAKfnuK7Rw8ZfcqWxityWjfYgc0Zcj6Cd0IUS', 4, NULL),
(4515, '78091060', '$2y$10$j7hbNmkQNTvYQhsogBPOKeYIAPaTiqp3o5iLKvoYmv9TK5JKfMwUW', 3, NULL),
(4516, '81010475', '$2y$10$hU4QBA/HyygXEVuC6/V8TOk/DmRS93QKne0Ym8qq2We.vfr6alHRy', 3, NULL),
(4517, '79030182', '$2y$10$3dZFwmlbcsfAMkStYVzMJuilUCdyFJmbenLofZdmdQIPnkF/s9SLy', 3, NULL),
(4518, '78050917', '$2y$10$5E/HmvpgkmLJRTSToUOWC.A6XPAyQKPmHJxGQDYKt13brjODLMj0u', 3, NULL),
(4519, '84030335', '$2y$10$Szf.YCek.l06P8PRX.0I3.gr/PfaU3qbd3K9V99ph/8E2agQ3JBta', 3, NULL),
(4520, '83090122', '$2y$10$KxLkJRnWJAv5nVLYXCP8g.OJQp.LXFEXNYCbSi4lDeMuYX.7A5BDG', 4, NULL),
(4521, '84030092', '$2y$10$GSw3uloWLPRS2fuTjogDfuPrTqSlauP9claVGDrWDPN51.HIbirCu', 4, NULL),
(4522, '84110342', '$2y$10$2.sPvvumeNZwHefwYfaPLe94b/c8RlwvCWIrjLSQkgIdrnSkOeXpu', 4, NULL),
(4523, '80090668', '$2y$10$BkQVx2rAXCajQ2V50EQKD.bBUGVc/kQVHeO1/7.EFRnC1dKppHPDu', 4, NULL),
(4524, '82120008', '$2y$10$5S1KaOggEkRtEQ2ZgM8L3.9f/dfUtjYMxtL8DuSPmNhKVObTr8XuW', 4, NULL),
(4525, '82010251', '$2y$10$UgKBnBuGassQZGuZrVh2pOwtfpAVL2sHgpgIuVXkURwZhEu37UdA2', 4, NULL),
(4526, '82040798', '$2y$10$ejGT0ev4HgFzBfsBgHKOgesXQrtWRlPFHUdydKMirRyAMoL3fQYEO', 4, NULL),
(4527, '83100638', '$2y$10$Y81nGb.TwLSycK7M4VCeiuc6c/Ym0iZFAaVN00jrmdj3eeO7SgHHC', 4, NULL),
(4528, '89050099', '$2y$10$MVeNfXQZm576sVrERDfaC.iW2xszjl5.2K7pg4vz8g/mtAnvgCe1W', 4, NULL),
(4529, '81111123', '$2y$10$0HjhKGH04rOWj14ZYsyNJO4L5tnbq13qPumaZWmRJJLlayH4/djeC', 4, NULL),
(4530, '97111054', '$2y$10$jrBIbaYZPzCll7n2IGRDH.zRfpixuyFuJlYxbxOgD44CCnotYcB/O', 3, NULL),
(4531, '85011693', '$2y$10$XHnArH.XRefW018g.GpeHOyfQIln0RB/1KR/aJe9jFiKEnOtZsAxm', 4, NULL),
(4532, '81100699', '$2y$10$MAM7lOdAk57bab0Eo1d0tOOvEzRntbaC3mngxCAzB4O4QJxe7cCT6', 4, NULL),
(4533, '00071085', '$2y$10$JIg.vMxey3UBz8j3LAvrZu037sRuN85BTiE5/b3jrDpBZzOlbVHSm', 3, NULL),
(4534, '86110021', '$2y$10$fBnwQX7Wh86vv.Oqo59zBuxBa5VCvEatBrawYSoQjdOeT9t2nPR/6', 3, NULL),
(4535, '87050007', '$2y$10$HzfYfJ0l43LVhBiXaV3MouWhM1ZgtpOavhuUhprpOFQwNV70ViRxC', 4, NULL),
(4536, '86061408', '$2y$10$AUG5JaNcQa0elFA9EZ./GOn4LeP7/eJWpnysgODhZ6679HGN8TC36', 3, NULL),
(4537, '91080076', '$2y$10$GeGa41ZxA8aJEhQUCloiUeuk0UHPYFm12AgCuZMqd2FTVtiJ3a7pG', 3, NULL),
(4538, '87101529', '$2y$10$z2Ba0faL84nvfFFLyg8p4.EHbJfu6s9QSnAC2Go7buIkz/MHHzGju', 4, NULL),
(4539, '93010037', '$2y$10$D8BIwyEug0ig5GNj.Tdn0uDfGfPulow0o5fNFc8KkFnOkNHSzk3jq', 4, NULL),
(4540, '85071852', '$2y$10$JHW9aMa7TnSFH7dVzqlh.O5qBl9sxTRmK00L.97HzNn0TCVbe3LVC', 4, NULL),
(4541, '87071174', '$2y$10$YVE59DtErNfTtY1oBVcyFusC0sePclHXlZivi8jyshrFbm0Zuwjr6', 4, NULL),
(4542, '79080979', '$2y$10$7xd0ogIYkDWwR9/DGCJSBu.AUxoUo55.UNbBSKDSVOPSN2uF5U7OW', 4, NULL),
(4543, '92060111', '$2y$10$6rm8nVzxC4qNbNyZCUuYE..07LiWBTl5k8W4xheKBCbPXiuFxZdB2', 4, NULL),
(4544, '91110220', '$2y$10$kLuotK.xnciiA.X3QRJmzOjG8t8F6shIQ443ThMC8yBtlFj6nIxfG', 3, NULL),
(4545, '91090252', '$2y$10$YrhlBoXLNVKxTAkw5L92OOGFrnFqPPZ0IcYsNBZBDBDINjvItKUFS', 3, NULL),
(4546, '75040431', '$2y$10$9W0My2u6KGCXImZYlpCzXOo9.Qgjee/uD/fDTz0bbHwZs7Pf5MEJi', 3, NULL),
(4547, '74040711', '$2y$10$noKxYl/neVbWWR4RqClDweknq2p.ZbGCLHmDilbeKOGaC75ZfNgMu', 3, NULL),
(4548, '87110616', '$2y$10$KNzh.ROZlQqHPQvbZAyrrur0fuMiopEbgv/qFT521pM/kEGpBdixS', 3, NULL),
(4549, '87061542', '$2y$10$Xb8HGQdlN5WdT.TKu8sjzeIPGa.5PcsdmffkZYqR8pvJN/eO0e4UG', 3, NULL),
(4550, '91120189', '$2y$10$KsTQLrtlw1nbvKic/rEptu.nSs0m1Z5/om.RxliiapNqGKC1PvPbi', 3, NULL),
(4551, '93030039', '$2y$10$qnm8A6v2bUVjpOuvihVB/ulrxv3Ccst9ZmeYQ1X1HnAiJsYulo376', 3, NULL),
(4552, '93100036', '$2y$10$eL4FZfuggQ3XAo46QSnShOdxpjGSENhSUtNPOvRaHfbFnOG.gQGY.', 3, NULL),
(4553, '92080089', '$2y$10$Euc4yIulPI1dtSAtzWJE1u.lvkKrrUBGcwx24uGAhjswNG0ixl6fG', 3, NULL),
(4554, '93070466', '$2y$10$hNoc8s/46jVj7wC7rKrzFOc6xm8XM.WFluUfJf6zQ/SPwMhK93QVu', 3, NULL),
(4555, '93110687', '$2y$10$Fb3Bcd7ARBwvVMb0ST2gPeWqsn/tHi9aOMLieDXj0Ghajch30RzIq', 3, NULL),
(4556, '93060436', '$2y$10$lFRm9MPPe3yY8A1hrV7SlOiQ7NIt6opbP/IAN21mGSOEFj8TB4S.q', 3, NULL),
(4557, '94110600', '$2y$10$cFqXyLSeJ0JKFhas5FXQe.v1au5MXXcGWvA2B4xZZMhP8Ad1nMy0u', 3, NULL),
(4558, '94060297', '$2y$10$KDBRUYRmFeQ7Rh5Oer6lmurXH2PHZ0qf50n5F7QJPTRGkjMYkLGSu', 3, NULL),
(4559, '96060878', '$2y$10$gyToSsCGIeMwTco1Qe7CJukSED0mK6eSCxLOijT4Ra5yQxyvA.6OO', 3, NULL),
(4560, '96070743', '$2y$10$EE6R3FHQ3qROSF9yB0PWZuRuyzBR5r/cy7wU3DrmwFP3A1jLif/1e', 3, NULL),
(4561, '98010487', '$2y$10$tygMAJ8CBGgj64Wau9biOOD3wXIV0n5z9crV127sGwJafXcRjyKkm', 3, NULL),
(4562, '96011100', '$2y$10$vGDQWabBEwaxOkPusXGsC.s0EiqCIOImYZefMfQzZ.asyvu33I1fS', 3, NULL),
(4563, '96120689', '$2y$10$RNDwiSLjuKllvfvdZTqQxu6TGsijsRNdrpSSVNpu8RlhJJB/CbWQy', 3, NULL),
(4564, '99070123', '$2y$10$08aP3OpNXeR7zNDeVMFpJOob99GeFy6jG7oFXVaCxvdjN.J.spt9.', 3, NULL),
(4565, '98110558', '$2y$10$YjyjxFkUJHbSUd5rrxX3cue8nvKoBir35h9uBTbYoOjwfOuFdeKeq', 3, NULL),
(4566, '00050128', '$2y$10$kKkZOJ142WrzEog1G.sZFeGQbn0cbZzp5frFjOyq3Z9/N597p8yvm', 3, NULL),
(4567, '98020819', '$2y$10$Cnndg6KY.IWG6s2GAED/cuxPpexr801tgaXy.r6WPk9c5pEzhU/dW', 3, NULL),
(4568, '99060600', '$2y$10$iMVKgyGFNrwFqhjO.2MZUuyM1tyje5kERiA6IJpeQIsD6jm4wmbo2', 3, NULL),
(4569, '00010528', '$2y$10$bUs99NgLdlMlbciDWHX0ZuGjV4d0on5GWcyaVDih/X4K8WwNW5UyG', 3, NULL),
(4570, '00070390', '$2y$10$WcF9kcSk4TZ/TRlHXoLLiOVnGy9j4PLhQeiu1tSQB/YRi9mXl0nYS', 3, NULL),
(4571, '00080221', '$2y$10$mHT1gJpgKroxG1mcbVaEwODXa9mjWcyNzoDZ5407ICm/Z8s3MGGHa', 3, NULL),
(4572, '01060055', '$2y$10$CTtMkR3WfIEmZcMnRXWxzOtikiTdrZj/CmGEgBRtUndYwTir98Cmy', 3, NULL),
(4573, '99111023', '$2y$10$/8LiiQcPwves9UcNvpbcTuSLNG72UIuJ3Tee69pBn2nqilscQx/mi', 3, NULL),
(4574, '99080735', '$2y$10$5LmjkNfy48.gKPeXdddJ2OEdgRSngSOR8Gn24QxcRgooEpUE9xlEa', 3, NULL),
(4575, '00030716', '$2y$10$8gRZma2IAPCvvPqJ28yjQuvex1hhnMs7z/9ZZKDUk4eQM4yv64DmO', 3, NULL),
(4576, '00080545', '$2y$10$mML0MFNhAIBqATo8MJ/EHu5UkxsBfB3lNgRk5QP1yHZmwWq3NQHCi', 3, NULL),
(4577, '00110424', '$2y$10$UokRJ7cOb6wFiDqZVajtG.iLScEWXhWu.95A1DUH2E6S51SOWe6O.', 3, NULL),
(4578, '01040297', '$2y$10$goVzhbT8ZRNxvEKd33LKk.eEwIZQyH/KaikMcZIDPlmR6YFaX7hYC', 3, NULL),
(4579, '01050329', '$2y$10$tTfrqLoAgkuxMHGWfa/1sewTCNL53ZFV1sdyz1j/wZFmE54yn5QWW', 3, NULL),
(4580, '01120145', '$2y$10$GcDbWdVSOGkQI41Rvfoyr.2FNw8bNMt4ciXcvcdYDjmkbFNEmQ48a', 3, NULL),
(4581, '02030250', '$2y$10$Th4p8bXC1lfJFcYljM9LSO0KLKbUHSBZJ./AIqtpVz3bq/BzDIwP.', 3, NULL),
(4582, '02050269', '$2y$10$KJfKGIqTfUQWTXlz.mBl.u3spO1tTlNCbxbnbixovc99aAHppLwCK', 3, NULL),
(4583, '02070016', '$2y$10$.p0p82wJ5YIhfgUUitxav.3QWMYiWZ/mMU68Yn66f5weeYkmsXqsO', 3, NULL),
(4584, '02070078', '$2y$10$Y.xVWhQrIEnjV4rWBI5c9OvE1QYSOR8d7SoLNGPnv5V7gvxvQR0Ym', 3, NULL),
(4585, '02080176', '$2y$10$ZQUopPKEQFEJ3quuzgrkpeXXFXpVWO9xeGm6C.kvn9RREK9izSRFu', 3, NULL),
(4586, '99111021', '$2y$10$JMmk5xnI9McqV/YldhXo4ualxTdr9Z9qjDUkc51yiMBBqnwHBYKIC', 3, NULL),
(4587, '99111043', '$2y$10$bCfr1XLfPm0pyUMf3CgEZO3H95Z2XwTgpUNI17naKThUBQ3fokFdG', 3, NULL),
(4588, '00010852', '$2y$10$Z/JKgyCnSgHbXucJKqiAa.aIbbLKKSs4XczdbG0aZuRMlIwCCf4TK', 3, NULL),
(4589, '01070802', '$2y$10$DNJAJNdiLSFfUqRwH/2DJekvKYeaWOIx.lY1Tc406aZBwVrH1Z1qy', 3, NULL),
(4590, '02030372', '$2y$10$1B848F7cnWOXIS1c8lA00.h.Lg5WIDPosUXY4mq1DpJMDmokf8KJW', 3, NULL),
(4591, '02040613', '$2y$10$WcmskNmcbKWk6qUfEUf33usPHM//ZQh2lI0V5Q4hhHYfgC4hoQOlC', 3, NULL),
(4592, '02080653', '$2y$10$5P62cROQF0I31HIYDVoDnuXqOYce1gyfqtlIqzY1oYvVueUQQHqEW', 3, NULL),
(4593, '03090049', '$2y$10$A2mBwtubjaETrBHy6gPsdem1NM217rgUspCA9r9Wh7nJyNcTQXjty', 3, NULL),
(4594, '00071078', '$2y$10$./RtTLnSLo3L1msmTqVlheaAERnY2rfUyGK.ZWx9Qm2ptrZAyyopq', 3, NULL),
(4595, '00041104', '$2y$10$ZP7Hae0G8.shULW13QRbnejPF4vRJcg86pUZhdQNE6JeJ19VD9cbC', 3, NULL),
(4596, '01090897', '$2y$10$ehyZuIkVtwtErSfT5K09MOkTgZ8OpO1M9z5.snZUdvmPDsNx48zza', 3, NULL),
(4597, '01100947', '$2y$10$z7YAFjf0gTJhTP1uyutlYuVPSZp999205902X8t8Dl98jGmGE0Ksq', 3, NULL),
(4598, '02090907', '$2y$10$0JjcaSL/s6O1m1LipBhDmOElxBc4tt.fyogEClIqwbFZP/OqnglyC', 3, NULL),
(4599, '02100687', '$2y$10$Lkl37krnvc/kfBsy/GcyY.R//Qycj.vESzg1C7IdwaWQH88xsTAQO', 3, NULL),
(4600, '02110548', '$2y$10$hAQUF4bPfVEUZkC9a75tL.2gADOiuQcokpBVzuA7sMwKdZF0q8HXm', 3, NULL),
(4601, '03070370', '$2y$10$JsmMB/9NM3jiqvNktsrSLO5dmOJrqvFz8yezmdxct5c/KOrrDnT6e', 3, NULL),
(4602, '03070827', '$2y$10$y6xHr5TZEQfQvoWzD62mlOjRTioC2DxVLJZOz1sNAsxzR2kMOY50u', 3, NULL),
(4603, '03050675', '$2y$10$pUu8A403/KuUZHKx3MRUPucKkxQkIoziyvzQI2tZ4yy9ujdbQ/B7C', 3, NULL),
(4604, '03070834', '$2y$10$uDWIKtaAKJxNm5MKCqcy/OJTB1fErXNkW4YeY0I73.Uj/w6a/3WbC', 3, NULL),
(4605, '03100450', '$2y$10$vSX3lNpVyFz3wW.JlrXQsup1e.tTMX3ta/8gd2H33VhXqLqq0/MIu', 3, NULL),
(4606, '03120341', '$2y$10$/gQnEELigoKOpSjDzxJAWOCN0PxIKIUFsmsRl2VFNbcPChuD5V4ZS', 3, NULL),
(4607, '04050022', '$2y$10$EWJ.uI6ZR9s1wcggqIg0juPO7mxR.blNsv.q8SmfCn8t98TNdkZcm', 3, NULL),
(4608, '02091593', '$2y$10$RNn3eZcKd6o3vP38VE.vkecnvJLt.sY6JVcw6yJ3wgctKIlS4lS5e', 3, NULL),
(4609, '05010018', '$2y$10$WkwFnuk9lzU7siBr2zBbtOf5jgZzxbo1Koklm3acNi0fE63sTT5Du', 3, NULL),
(4610, '05060234', '$2y$10$2YUN4ONJ9tqeUBr7LmUr6.wDoVxkmyHmCZLwAoa3UuaQDaDHxlvqG', 3, NULL),
(4611, '02081616', '$2y$10$Kggj4o0zhht5QQWqWjg9fOadDDkeAZQ1yXUkpCypmR7k8N1OKYwFC', 4, NULL),
(4612, '02101866', '$2y$10$x68qLfuSZYvhRaB/nZv86OPVYd43pNOoL5eNM06FTGQ/mSOhc5edW', 3, NULL),
(4613, '03011517', '$2y$10$oACy9RalBGapTb3fTudsfe0z0AGH.GMfwguArXAM1ObYWIzNTwzZq', 3, NULL),
(4614, '03121248', '$2y$10$AHoGhMl2ye4NK5OffOfUkO1KAmKoCA5rCrPWbiPNEPqdEwcXUjFpS', 3, NULL),
(4615, '04061087', '$2y$10$y2WmdlWAQZNgt4Qstxcf6O9sZvQ2IA27qzqA.ACYnIwwFgP..Bak2', 3, NULL),
(4616, '04090731', '$2y$10$ncKnbska.lCvt/6AYJtWYOAZqYlcDXp3QU3ICV5cGpSJ6cRtLygK6', 3, NULL),
(4617, '05060306', '$2y$10$sXvVXr1QmG3aKJDv9mZoTuWQXjGcBI1zDtrk/dt0xnRIiOYTwdufq', 3, NULL),
(4618, '04021045', '$2y$10$Y2JVuFVZPakMp4wB4PvqDe.waP8z2op47RyOI9UvJVDwRxjD9anqO', 3, NULL),
(4619, '04051474', '$2y$10$a8Fx5qhIiqgbc2NBo0GqFeybxNJcag5BAGAPJKGJKNeZ9S4su4gsm', 3, NULL),
(4620, '04110817', '$2y$10$BN3UxB2ISV5ablix3aoYLebbTg82C4UfR/HzBVLNW.m2VW7VUkn82', 3, NULL),
(4621, '05040565', '$2y$10$LLUWSwktW1q9MlN1v0dHDepE0H6CTJgdCSzx5lVJrBXKlTOZoYQ7e', 3, NULL),
(4622, '04031556', '$2y$10$ZpUY7KeTb2j07WN33HzegOXYhAT8ButQtpwd1uL/7a8kD0YPXOSX2', 3, NULL),
(4623, '04051542', '$2y$10$T03T43lXxrtjljyimy0ZtegcAaA.YRwoxbMQRt8dXDcQW5gjdmK0S', 3, NULL),
(4624, '04051544', '$2y$10$mLnA5Z4QKlpn8QHFZn1m2.C1FGSr7cxDWdapD/T67bXbkzIXYVRgu', 3, NULL),
(4625, '04111080', '$2y$10$U0IeIhoWfOB8iFXrxUHNCuZ.SZprXUTvsEXa3NOlPKJ4dRSLP1qCO', 3, NULL),
(4626, '05010882', '$2y$10$10aFdVqOIBZluuQQdxrv7O/kHkKXjzfEGHcywYSdKPmAmV.YoxVYe', 3, NULL),
(4627, '05030920', '$2y$10$rGHY50xWMkHf0ZH3z9VtZOmqTOInM7f8u7TTlqrdGuwGq9FALrG5.', 3, NULL),
(4628, '05080706', '$2y$10$KbOTGoKFz/Epu7LSubrivuPagggnbP5glgkH.wrXHz0uLeKMkXIbG', 3, NULL),
(4629, '05100603', '$2y$10$esCSl0yTUIu7xXBwTjdDdeR82TcTuicZ9dPl1otmiDH77dg1248g6', 3, NULL),
(4630, '06020274', '$2y$10$mm/tw7teyxBuAzO/ao/qTe8zgIEGqgUqkhN62thiDS0kx7rdbmzP2', 3, NULL),
(4631, '06070236', '$2y$10$w1jn.OYTLYn.0N3h15BSX.mczl.Ahy01GD3zOvTCq3TQ5gXeXTywS', 3, NULL),
(4632, '00031163', '$2y$10$YgvQjRsyRI.VZ0I2o.UPmuHnuv5SbzLbsnqEYqwZUu4Y6Lxy/357G', 3, NULL),
(4633, '03101685', '$2y$10$hav0UgcJafdCJI4ie1UJe.c13rK5QC.rHCMltaELTLYRkPnCM6ngO', 3, NULL),
(4634, '03101690', '$2y$10$0tRQMey9MEG1Zr1m0xaGRuUcZTKV2ghSi6vPNrx6E5QxmcGwZR4NS', 3, NULL),
(4635, '04051598', '$2y$10$dLMqF6ulsYVNRLhkvqhP4OQ.7/UENyTTrLjc.Ovpmqp64vYLdpFgC', 3, NULL),
(4636, '06110107', '$2y$10$bJv3nOENCo00/wp.8INmYeK0yK4dLTduK2fl1zGVuX5h6a847L6le', 3, NULL),
(4637, '06120188', '$2y$10$VcxpgRReL0kp4AWSWja.oOgbQa9zQ.0fmCiqAoGUye7WXogHOx7fe', 3, NULL),
(4638, '06120181', '$2y$10$qLc.J9kL1yRWPYVE6Uxw8ugnTtYR9cdlVXPQbkVMNItfv90vu81D2', 3, NULL),
(4639, '07020058', '$2y$10$O69QDrUK5AV1rJb4J1FGNu2sUYgs2k8H9g.uaabjBd8ih9SfuEyKC', 3, NULL),
(4640, '07030069', '$2y$10$LNBxBzrgQ7gCiLodtWB0N.TtMqQ.BVPxrjWnYKMilsP1EFouyQLTq', 3, NULL),
(4641, '07060010', '$2y$10$RNMEOKlxsWEd6JA4baeqhOOExemI4ZzJKd9ryczTN7suiJzosWQ0i', 3, NULL),
(4642, '07080035', '$2y$10$7zKXm3ofSc.taWnCUxxAne2lS0KNukoDJtOlC6hTQRU7E9Ttgu8CC', 3, NULL),
(4643, '07100020', '$2y$10$9UIStYSZ5nzqGFPh1lR.6eVVrXrNvnpZZ5Qt8MrPP/AFZGEi5tL2C', 3, NULL),
(4644, '04011557', '$2y$10$s1b6VGID.49HicqNpPCgTuXgoLgY/Yp6cQTz5Q5/D2rEBYZ0QzzZu', 3, NULL),
(4645, '04061468', '$2y$10$QKno42j0MhLmdW9NW/SbgezEwp.Zf2fZl4Ymx4y3F6GTz3fn9oJkm', 3, NULL),
(4646, '001', '$2y$10$cpZGwl8kyNMSYPf32G49b.LsCqyDS2sqN/5.wTjx4t/l8FiYyo8h.', 7, NULL),
(4647, '002', '$2y$10$bY8nxgXC.zLTxJzokeYc6ejn0YtNQ8g/WjvJZk5fXvcvCDqSSVUbq', 7, NULL),
(4648, '003', '$2y$10$/Cr4fMPuWAuL8mJxb.Pwiu5C5CTnFN8KX56Fg8VwIN/yAI.7G0rD2', 7, NULL),
(4649, '004', '$2y$10$OgRJzu8XstLCz7gZCFuB5.3hKECB6v8Tyw93WPnozqYp/DFEwO9lS', 7, NULL),
(4650, '005', '$2y$10$N78PVgulKQ7LuAGyUR.s7.ox/iG0qazo0NGjiWJi9jUn6KJYPgzVS', 7, NULL),
(4651, '006', '$2y$10$pDEeQxDXHWptyFvwQW0JoeBxhnX93tqePeoyRXcncl7bti3Nz1N.a', 7, NULL),
(4652, '007', '$2y$10$bSnIv6LUD2S6uLarqwvB/ucsZ6cHTdiMRgSCNh7IAuHDN0zvRxd/a', 7, NULL),
(4653, '008', '$2y$10$76dP.o4/RhY/AATHgXQB3.ZPSdqjnLETXhthJ0ZowW94AWtRrm/Bm', 7, NULL),
(4654, '009', '$2y$10$KZYt44MW9UWYHxVTFkDX3ufnBFvg3hG2ZXt4J312xt8/ERg13tKcy', 7, NULL),
(4655, '010', '$2y$10$Ge0VqEtwX3PR05niNEpuNOhsXU4/RLNtonN0PPxQIXh/P3QIP04PK', 7, NULL),
(4656, '011', '$2y$10$exFuf0ueu95w/FSNeC/rDeBq0cftJ4VFnQO5YSX6i/3JHEiTXo2Du', 7, NULL),
(4657, '012', '$2y$10$ANzykDEaQsKBDeokSLy.5.cnEMIf8sOKXU/ThzoaBs0Vj4QToI58e', 7, NULL),
(4658, '013', '$2y$10$iepuVsBXy4qN5uVaLPPQ.OvaS82Dhk1Lxc4p3ti5kyZacD8TdUfgi', 7, NULL),
(4659, '014', '$2y$10$u4jsbKkiSuRqSc8hgQ/iQenxBHsdeFIFpytyQ/KWmWt/Lo7dbmPPq', 7, NULL),
(4660, '015', '$2y$10$1WlPHeDETZV5F7qp22OBue7PH47evSa3ZX2mk831hafdfWm0OGM5q', 7, NULL),
(4661, '016', '$2y$10$ytez60DvmK23ZCJ2CrDHPeIJHyni74soRW3oM5HbAd1QGq4FRtGWC', 7, NULL),
(4662, '017', '$2y$10$3AdCVfVP9ebfq8z4kqvejea1NLeDe1LqgpXryi5j8mczOXg/T8mnO', 7, NULL),
(4663, '018', '$2y$10$yjGCnP35mB0dDWv5Az0oP.GMXMWZlJ9bzen4bsd5Fp26LYD6pEiju', 7, NULL),
(4664, '019', '$2y$10$KKRRjjVLRQtHRi3vxeCad.Wr15A55lc1amazUXZjmfq3vRU.zCIgO', 7, NULL),
(4665, '020', '$2y$10$fcRffxcvhq7Inb2X2tTUWulSKW1aLgd8H9LnEdIqWFlChiUAVY6RK', 7, NULL),
(4666, '021', '$2y$10$DAKUI89T/Nj6WqV8shawgetU579OAv5GOhe56zGrpfDbZnuKf1d42', 7, NULL),
(4667, '022', '$2y$10$IUpn0ZwFuMtmTUuJ6f2IkesfhpRKbLUtIz6sUs8pyXb1YGgLR9xL2', 7, NULL),
(4668, '023', '$2y$10$JDxFkTg3juhQQXCSfBZRDuYQ5xk2imajDXyuw0Yx7lhUL0bZDRL1C', 7, NULL),
(4669, '024', '$2y$10$d2P7fOPxPHDOUtU6aogYMuyf1xAMqT08FspoXCSbrSikQ9NLWcl5G', 7, NULL),
(4670, '025', '$2y$10$e7BwN/PlnnrIHYAyxYzLCepPy1bmTwP/vthN72j4aUC0yUQ1xbCiW', 7, NULL),
(4671, '026', '$2y$10$aJVQyPSSINd5ykxi1pADkuRbml.Ol7qoP7J5jJq0NQpm6mH4UYWAm', 7, NULL),
(4672, '027', '$2y$10$BQOAjc.QBpQgmwSE5nCAwOFjerjl23L8KOEetMEefs6Wg/luxJkrq', 7, NULL),
(4673, '028', '$2y$10$P0RYqvdj2xwULbqNaEYp5OOPQsFdNx/ZxiccYnSwK.i9JmeMByLmW', 7, NULL),
(4674, '029', '$2y$10$yGfPkH3iu5oDrACNZm859Orp640HZPlzCT5l08.pQFSHwhrsGDYQC', 7, NULL),
(4675, '030', '$2y$10$VlJ5T4lfcBtZ2vk.a6ffduZldG6j3Afmr.l3JdedrnI4KQ21e/evy', 7, NULL),
(4676, '031', '$2y$10$Ef8fGGX/LRlid0smR.g34upaoKYo01wIx0gJDuhzVFozHDe0.eVQm', 7, NULL),
(4677, '032', '$2y$10$lImbSWTtBklbw2wR3JkvU.gbaogaBTwfI0/D.cy1Z9g6KFH4mKxNG', 7, NULL),
(4678, '033', '$2y$10$JkKXgIQxMEedWRjM5p1DKeGpotu1HfvJQ4mC4eyAox1q6Qe5Lxw3O', 7, NULL),
(4679, '034', '$2y$10$NuQBCU9CxJMp9k/IVI4l..7ttu7OVDR4o2mBxgC8dG2BOJbL1SPou', 7, NULL),
(4680, '035', '$2y$10$Q9xSr6d.sOOEstYXH2A6C.XE1OWN1.xzbrM/f7ytgCG4Q.K/rEVZq', 7, NULL),
(4681, '036', '$2y$10$07VyaLSVr7ASbL5fHeLjfeE7r2IVo1XQ89Zu7o2vh9pOGTf35V2TG', 7, NULL),
(4682, '037', '$2y$10$Psv3epPpgY8kQLpo7uoChetmw61Oo75vUdItKVUPbYjYkylrOv7l6', 7, NULL),
(4683, '038', '$2y$10$s8XiNgqUhsZzZAxMsF8eDOOUNgrkB25G2AlLosbsRqApJZuQQn8eW', 7, NULL),
(4684, '039', '$2y$10$s.n29NWB.IC1Mjv/O3azH.G4pOKhvFJz4k7WIcaWGb3vcO8lN1.jO', 7, NULL),
(4685, '040', '$2y$10$9TGZplYpqJlOaZL2hqExpe/mMO53RZvVso1qXtV1Z4XrG0qiSd4cW', 7, NULL),
(4686, '041', '$2y$10$08v9hEfgDm0NTkQ7aWhw6.YRvYnAvxRNBcr26RelDsv0keEWLK4A6', 7, NULL),
(4687, '042', '$2y$10$XF04/7VQPXsH3AjXs6pkoed4zMLnaNddi84.yDLnt1LjY4uCnJcKu', 7, NULL),
(4688, '043', '$2y$10$8GaqqD.dXTu5X4cyBYJoEu7paLHAt341bkBiATiXvITntS31FhAgu', 7, NULL),
(4689, '044', '$2y$10$ybms1vloDhZLHeo9wtvJNuMuLDMi7GV.9yj8QQL1Kz2OeR4RJ10.6', 7, NULL),
(4690, '045', '$2y$10$.mSg/iLXXdnVyx2K69CEvuZs3fpwAQhv4VbbRfL9NGw4BRoKvVykK', 7, NULL),
(4691, '046', '$2y$10$q2tqedFcfrTM2jrPd4SOxORIPKAWPCg/nIg6tIvDwYHnJrgltbgym', 7, NULL),
(4692, '047', '$2y$10$WVhDctaBoxQaKUz7DUJFi.g1QdgBucRSEFLkh/b5pwNLky7BSToi2', 7, NULL),
(4693, '048', '$2y$10$RdAL1McjXiTdKQp2fgSxC.CRjAWSwUt5WULtUEozBv4NP0Sr3osX2', 7, NULL),
(4694, '049', '$2y$10$CYddnTvQ6p/B6yV9IlfM0.5qUGDV54km1SRih6GHtGE6CihGfAJOG', 7, NULL),
(4695, '050', '$2y$10$ZLBiHmh2lP2mqNViw3YSz.Pg.JNl3NTHeIDaqhwg2GM.G0SGdg1XW', 7, NULL),
(4696, '051', '$2y$10$3A1FJq..C/jviXsf7a5hCuE76myImPBCC3hBZmw95AshmiT1vV0VC', 7, NULL),
(4697, '052', '$2y$10$XCALzgSKudpLdth7RPkhK.t6nSYSYtiHBhO5sHNuaZagMGNgwjaAi', 7, NULL),
(4698, '053', '$2y$10$JdFCqyDeHRtn624G9d0wQeUDWYH9ElbK5JOzELpw7VlrwiZYqTHZ6', 7, NULL),
(4699, '054', '$2y$10$3fGN8WOrgr5sGdTn6/0r8u6kuGd1JHjCGB8Jycyu0p60Y.BUPLy2G', 7, NULL),
(4700, '055', '$2y$10$xzUa9M3TyMHHgXl8LfE7g.GBs35Jr22Cus3HHOl6LLCPfA2.bwC7a', 7, NULL),
(4701, '056', '$2y$10$798CxJrhy77Wg5R3sPJnA.AjBReG0d0DmMF8B7YO0YqeEgtQvmW8q', 7, NULL),
(4702, '057', '$2y$10$vVb1GeuJvaWfSpKMx4OS0elPKPu1tR/XJcvTIeFEk713HnCCLQyze', 7, NULL),
(4703, '058', '$2y$10$ZIOA8slFfDr7/opYBg43T.Sn/.8fkynvURGhj4lJg7RunfFD7nuva', 7, NULL),
(4704, '059', '$2y$10$udZUWA/5X8do8AOtVxtFkuQnR3vetcZYToNM08F03Ho01c0W5AYPK', 7, NULL),
(4705, '060', '$2y$10$58H0RjAx9j5topQYg7RLh..46QMrHsQjX5b1WI2jQZRyA/AukchZW', 7, NULL),
(4706, '061', '$2y$10$cznMDlgloFzuzO0VoECmY.R1B9DEJiw5ZwH9uAJtzTai45EX.kKYS', 7, NULL),
(4707, '062', '$2y$10$JwK79SYn5mLwLoqm2OoEFusRsR3zVoP1M5/udby8jArtOk.42xhgq', 7, NULL),
(4708, '063', '$2y$10$EirM/9Tg5EQC3OJuUiVirurVpKWjta7JCdZ.RzXQtXgalIKfbpiBm', 7, NULL),
(4709, '064', '$2y$10$VZqWpvL8WgfuJK5GF8yJA.1KYIM8WaMTVNHPMI6X2IpyYWN83DcXC', 7, NULL),
(4710, '065', '$2y$10$XeD0Y84ZfNrgs8eAlABh8.ropDMNIoL1CFIA0MNH2XSPi5YDQ2m.G', 7, NULL),
(4711, '066', '$2y$10$FEY1jZhMK4mMmUntz8X2NOk980tuqGCgF4kqG1ID05jPV4F9wNIYW', 7, NULL),
(4712, '067', '$2y$10$xcpwk9BhUClGgZdCkDqx..9w5tXv3Ctdw7f4lHYE9BxHPUOS5fATq', 7, NULL),
(4713, '068', '$2y$10$yKiUetV95kOLrOy0BnLBbe3tPPn6Pc388sASDPQT1zYao7FXHrX.i', 7, NULL),
(4714, '069', '$2y$10$IGjpTcZUSE0elZ5.j7V5T.GqixW4X8TzGW4WkNhnH.qbDmlS0ruh.', 7, NULL),
(4715, '070', '$2y$10$HXq1CAoXCwI/p8lqQml9aua.EYR96sQxXNongcjz/GfCOhbDPejUy', 7, NULL),
(4716, '071', '$2y$10$35ced7ekm47oOuzpcteAiuN09.C5IhPD7yxXzCLYEngwA3.Cj53ga', 7, NULL),
(4717, '072', '$2y$10$qt46PnQ2c7EvepAaBq.BAuaB9yiVBHHF68TMHSrg1q/bkXkxkA086', 7, NULL),
(4718, '073', '$2y$10$.d8a.kcPH/aIkTcemuNcbe261i5Y0IOM68J8IpQo9qX5dWHEbkd1e', 7, NULL),
(4719, '074', '$2y$10$J/iXZw7qdhqHZFeMVIDfLuOHoZy1KIp3m5jGj.G4ySVtz8VBdSAFS', 7, NULL),
(4720, '075', '$2y$10$iLlKat733quHCqVVTnz4QO/UyqDxoFODo1u5PRBRYy.0Nrf80cq.6', 7, NULL),
(4721, '076', '$2y$10$BxStTCGOd4oe4l9a1Yc.2uDTBuJEXqTpnvKGP2XiESzLGFM56NpNe', 7, NULL),
(4722, '077', '$2y$10$vqXTnfJgitKI25BHEvTTkO7Q.k0cDMQsv8cTTFLdcf/ewU7A4fixa', 7, NULL),
(4723, '078', '$2y$10$uWSgq5EKj41MQ.WPBUpPje0Qliexafq2LohXXc2fhnuZdbJLioPM2', 7, NULL),
(4724, '079', '$2y$10$0fVxKgGq5Xm6b8eWwdPeo.oCKgiETwG7fbyT13wOLK3hFlc5x4Nye', 7, NULL),
(4725, '080', '$2y$10$MPN7tcmaPE6f1/3g20TW0OrtHgIOfocj1tW6./TOLtOnoXJYAzc9i', 7, NULL),
(4726, '081', '$2y$10$TpHYrgZyVwgAIM6z7YdWXeqjiNiZlMZqFQxL9HT9fS0DcsqfHY7R.', 7, NULL),
(4727, '082', '$2y$10$wSGX4LNw68XdL7hctpYDpe1ZmIw8TYONEZCv/rC.g5VIwstu2zKAe', 7, NULL),
(4728, '083', '$2y$10$uecUoYWJEZKmWOGqDfTtgudx5823e0Jd727NYEMXDrblNrmg7MKn.', 7, NULL),
(4729, '084', '$2y$10$4jEQJmv/BYKkvCUjZT5YuuSEexFl5NKxC0q0z/0ZsAYkbuRP3QBoa', 7, NULL),
(4730, '085', '$2y$10$73z73PFr/8SNpEoYIgbH1uIerY7QXRsqdwb36VgDJuFXMkbWpVWla', 7, NULL),
(4731, '086', '$2y$10$Yzw4PnwS7AetrdwyVNHLOO007Lv4qPPRUZeYSSRlBVjydG/B50V1i', 7, NULL),
(4732, '087', '$2y$10$HTz8hNUIodtSS8i9ufScVOdk9TbxiPIoIWC9RqmFe3pPLIGD.r4im', 7, NULL),
(4733, '088', '$2y$10$NdbCMbF4pULppNWaCcclC.qy88iJdKTvN6yoR/ePNgPFKNWjxCdeC', 7, NULL),
(4734, '089', '$2y$10$w6uuyt5R4EXFRZnYbnbQVOFUs6tKbgs0E.NcmiWOIFJOAE.Q6P8IW', 7, NULL),
(4735, '090', '$2y$10$QAb0fV29RYadTbayUAC1u.ABbDK2ctflN9UYUH2iOoK4S3jVlR2Ly', 7, NULL),
(4736, '091', '$2y$10$ULtCueygxMKhE7Ynjbe6v.d1kKGgmw3TuhoVYHKPAvVjW1aEPxP8a', 7, NULL),
(4737, '092', '$2y$10$x/38PvvU4eNaIzFCepB3c.7LQ/0HIelWhFLBWz57DhIcuEwqZzJsO', 7, NULL),
(4738, '093', '$2y$10$enbdz1X3x4jTrjfOj6TmJujffREX/Gdsu0TA89q8gSpSlZ92xffGG', 7, NULL),
(4739, '094', '$2y$10$EBAuWc0bYaL1qYHWMj8b2uPDmbs/EQ2OaHz0E.gOH3XrJpZaRfjFG', 7, NULL),
(4740, '095', '$2y$10$1tB9KvfkdNEpZM/kt9KI3uO5mtOIhCzeSqBO9iaPW/3wy99Lxiy8y', 7, NULL),
(4741, '096', '$2y$10$pjwEoBuIgUgbDz12.Le1FuZ2.7oRpgyaoZsgFZzrZw.6l0dwKrLD2', 7, NULL),
(4742, '097', '$2y$10$733RUhA9nxQ/qQjRcCPMye6EHmWhvAclk3vLPmoPo1Kjov35im6yq', 7, NULL),
(4743, '098', '$2y$10$qO4at4hTmwx.12UYkgfZ0O6kOyOnzzBqyTTyhGVRrnJISKWL88.n2', 7, NULL),
(4744, '099', '$2y$10$ZF/xFrS5KxyiJqP82HE3SuS25lCVbm1iggLOOHOVPh1oY7mSgeCBC', 7, NULL),
(4745, '100', '$2y$10$LmqgdXHbFqditQQfseKcg.m.dBKvJ/9vJ.5yfyxwH9Tsms9InEGtW', 7, NULL),
(4746, '101', '$2y$10$CMoe0viDglRxkfL1lTsMP.99fIhgzPVruvYy96vIo4U/9leQ6nFse', 7, NULL),
(4747, '102', '$2y$10$xdWCvqK8b/umP2m4nexY.e2WXW4sm0dDp6N3UcDctCVNmJL5sqPRO', 7, NULL),
(4748, '103', '$2y$10$KWlOl95JJzNyGBbRDmBLi.H8gIYes0x86ILWb/a3FhC/Tp1pzyqNe', 7, NULL),
(4749, '104', '$2y$10$qhaTHeNAVz2haVmfEubxruF6T0NhYEdbbFASlwk/VTeh8MBZAZ4sm', 7, NULL),
(4750, '105', '$2y$10$wNcSUuPRQUHFEJscMazxT.6S1cnK81kLvOALuYR1/5ansUDg21ZwW', 7, NULL),
(4751, '106', '$2y$10$efgvUNpUa86dRyZdCnoMI.PsFujVd1bpNWud5.T5r2fxbC6scQvDm', 7, NULL),
(4752, '107', '$2y$10$8fauVSMabaHmZLk8284GXOsTjkZvjn.grBkqfFyoJQNSyWz7LtGEK', 7, NULL),
(4753, '108', '$2y$10$JPJne.71IKZ26bWubO0OQOvre3pgB0ACvh.hPv/Pyo/V2nUdoUt1i', 7, NULL),
(4754, '109', '$2y$10$adUKeRUy18EVqRgiSc1AW.rnUCm8kglxb0o62AV9cUEkunt.V4OOa', 7, NULL),
(4755, '110', '$2y$10$ZmMberEpOc4sNH6EuCRiO.AdScNjcfRP41daTSrFt4IJnheIoVlAO', 7, NULL),
(4756, '111', '$2y$10$MfS8SGFJz5kzO/ZqAvpU6.VbMM.EOYaaaZHVeVFjC02Z022pn0x9q', 7, NULL),
(4757, '112', '$2y$10$hS3jd2/bP5qeqW6NzxBR2eqA.EJru05bR4PEDO6PlYUAzKZbAQvR6', 7, NULL),
(4758, '113', '$2y$10$Bk9Ni49siu5rWF36.Y/iweKccGC8PY8POgXsFZ8CWtBrvTn4nn2iW', 7, NULL),
(4759, '114', '$2y$10$/kd6QlP72qTQUvKiBDNCpu8bClxLSqZKygNkiWRK66e1Zq6rPlpDS', 7, NULL),
(4760, '115', '$2y$10$/5inTWYshfbofUku.m2AHe/yRM1zR.KMwdN/MDUYDqZKsusLdSBYG', 7, NULL),
(4761, '116', '$2y$10$xHVrbJUnQVHmMUyR4rq2buw8WM3AGH8qpRa7nRre2PqTVDm6Lz8wS', 7, NULL),
(4762, '117', '$2y$10$kCW4HTcXzuv2Nz3FVDS04.UEpCJ4y1BVcQjVkrF9YAwZll1zKSxZe', 7, NULL),
(4763, '118', '$2y$10$83x9FvOo3Kf/2cZmxhEWj./L6bE4X7./UuuVBSWm58COrHX10DWC2', 7, NULL),
(4764, '119', '$2y$10$grMrSKbIjrFovIww..ls8eoi7w42OeZ9FwTLD4P4swzw.zhfccduW', 7, NULL),
(4765, '120', '$2y$10$XA/4sP2OYtTD4eCMkvB.w.0UQTg0apIo83wuH7smvec9m8LGruzeW', 7, NULL),
(4766, '121', '$2y$10$rU2n1QW7e.s0C2w7FAwXD.3oO3Xo/82XQe8ZYo3iBe567kKZKvkLG', 7, NULL),
(4767, '122', '$2y$10$sZLQN8VvhQpM2FEflI6xkOKEzTFX4w.mXYx9mPJf6RslpikZlLWKq', 7, NULL),
(4768, '123', '$2y$10$FbBO1czjdSnWJSxVQ6Teo.B9IpcxHi1fjWiakTt4x2wXUgL8c9Nd2', 7, NULL),
(4769, '124', '$2y$10$s8aI07ru7MfLBNNuNFDdU.RqwOENI9p5mKFLn50jOyKHf64nI2uzO', 7, NULL),
(4770, '125', '$2y$10$GZH8YSCJOM7k3zGiERwmDOBTzxjvmIiKG8e8pLQZJXoqFzgYPePNq', 7, NULL),
(4771, '126', '$2y$10$702XIFOIcQy/shdW8Ga62OUcWjiY1UfBgfKV5ddMAUSgEsSXRHg9.', 7, NULL),
(4772, '127', '$2y$10$fIHUfoL8gzwEAIFM3mk.Uu8FUg9woWYAeL7y51uRkK2TON3AvSwBC', 7, NULL),
(4773, '128', '$2y$10$gyc5ZQK.E6qojVyUilcSiOPf5H.yeWYUN31NtbV28JB39WaGW9ZIC', 7, NULL),
(4774, '129', '$2y$10$6ZTW4o34WuImLxESc03/8uavEeWlMXgk/898ikeq.h9HPKTtJ8Wxe', 7, NULL),
(4775, '130', '$2y$10$iYK.PE6FOuS0VtwjiIp69un2f3PXlpNWaztmIn/1gQIBU/hgv0rva', 7, NULL),
(4776, '131', '$2y$10$huPRNuDdOdIvkoUFqVJfgu2jmySoZoT1UQKSWsW/2kLY3KwZHfP5e', 7, NULL),
(4777, '132', '$2y$10$EJAZx2Q9x.oMhD2enh30N.AtJPERzngXkivP8KNXm5JG62ZSfm4lO', 7, NULL),
(4778, '133', '$2y$10$Gpazy/QEfhaEV9/YQAOIme4Sv9riz/cm10NHZP3dN6e.3qmcmK.LW', 7, NULL),
(4779, '134', '$2y$10$IHS.D/o/RNaRMRf/K6Ujh.MyeibeTCCAuCMKITXjqItXtEyKZtuYe', 7, NULL),
(4780, '135', '$2y$10$2b.eeF43ati4h8G4AfboHOlnCBACLFFT23IKuxmbWSV6uzFOt1uR.', 7, NULL),
(4781, '136', '$2y$10$rAV1NVQQd9hRxmcrhHZIHuFCJlBvhvcs348W0NriM.jXp58Gp/VVW', 7, NULL),
(4782, '137', '$2y$10$jgH6tfvljav6XpEir1d95uFQduBcQaX41zyyaN9waG.YsAiU7oodu', 7, NULL),
(4783, '138', '$2y$10$af6ZPRJllNBgv5VZchGCquGANCi6YS76imglJD6l1QlkQlb1kv4Zy', 7, NULL),
(4784, '139', '$2y$10$zTuK7f7MwaTmBCiQcHfdaeYLYNg1BsUa7huidQDvlCj537v1bAcxW', 7, NULL),
(4785, '140', '$2y$10$X/8rjVC5yox6sR6pWyKByuzQyVqj0lmO/OmwEG9cRarUIkeBwyjDu', 7, NULL),
(4786, '141', '$2y$10$thhHzY.bp8pOabTVBITqleTXpfaiIhEDI74xEsUMTTeIWKc5zhl/K', 7, NULL),
(4787, '142', '$2y$10$NB.QftuDQo7B.mccZaQgoO.7AchKyLVa9DanMK4lnRP1VM9OVqueK', 7, NULL),
(4788, '143', '$2y$10$BllOMaqjFPUSqVPkwgxBzuYDuRKJz6/4.6z30FvolZMkbYMbcotj2', 7, NULL),
(4789, '144', '$2y$10$5ke0uPYLH.5zHIkUKZ6uaubDc0pPO6HQtG08QMD.TcLwtnY6brDgS', 7, NULL),
(4790, '145', '$2y$10$Bhf3GN3dxGxiwBjvwopTzu1xJVLQa3vy0BTGyDNXQNvrxuHCAYpKq', 7, NULL),
(4791, '146', '$2y$10$sQ9/B/sqOJldfFB8cqYaPuE055hjeTjsZKrk./5wLu5rDqfUOTUy2', 7, NULL),
(4792, '147', '$2y$10$S8X2Q93adg7eeQ42OBcbV.ggJDKgRqDQheeidxHDCtpAKCudTicvu', 7, NULL),
(4793, '148', '$2y$10$0z5MfxbYPMlj6QLp8hz5m.3gisKnxSkVAHzh84rujrfO0twVA/TG6', 7, NULL),
(4794, '149', '$2y$10$38Dyom2RbJVM0cTGcvKOruhfZdZC9MkHLdg7V.QczeR0UacZfQ8YS', 7, NULL),
(4795, '150', '$2y$10$XVX3fmK5RJfmYGxJMypnh./Vn3Id64/E9Fm63oqvRTC0qpT5DM01S', 7, NULL),
(4796, '151', '$2y$10$nPwGOjyY3HKIQry.Gan8Q.MzzRk6u9e/1xuFPi3nL2SFlhBcNsxua', 7, NULL),
(4797, '152', '$2y$10$3WJ.KCLoYqK3teHAPXygLOFEoBvNcXa/GR25wF/LTf1R3f9U4XbZm', 7, NULL),
(4798, '153', '$2y$10$XOoDnj0ATIAbtyOqj5MjeuJQLiSPQ9Zz3LiAYnm2gWvhZGQpvGsay', 7, NULL),
(4799, '154', '$2y$10$VQ.VuF.3XfAWfqOFfJ0ql.Ne08ydqRnTOj/Kpsr5BiC5omD8Nh5OO', 7, NULL),
(4800, '155', '$2y$10$z2tH7zuLvCDAHGugQpcmA.8W/f5Wn1ajt9fHQ8Pbyfa66BnNEgpxO', 7, NULL),
(4801, '156', '$2y$10$WWCyqajWxBeT23gY8RTrl.DGCafQo6wSNQBBfhI/oGkShwgP.UsQi', 7, NULL),
(4802, '157', '$2y$10$SgvPkiyO1gl5amcQFjkOGOSB.XfQ/cDIH8i133fCXkXXeBuhFJATq', 7, NULL),
(4803, '158', '$2y$10$ebgg//QqBk.C.pJvC0AX2ObG7ZZY3A8cCkz5jLk6QW32mLFsKtvZ6', 7, NULL),
(4804, '159', '$2y$10$5nAOYH6lqDMtzdlVpz5wPe0w9kE9cPwkg87EDqlRvWRJ1Oydj3kGO', 7, NULL),
(4805, '160', '$2y$10$Pr89v3Me66iCKdIvH8fxr.hGPxXG/Otb5RV9LZ8ovBBr/pejVyJnW', 7, NULL),
(4806, '161', '$2y$10$n4lOvkQ4BBM5yXDFS5kbmuH/wWI9AhJWFsFw8CrapMlFGKbpJUCle', 7, NULL),
(4807, '162', '$2y$10$k.14mkwcEqdAaqYCoy13EueJGUEpHpVUOI8kou0gfCOdtbAZ40gCO', 7, NULL),
(4808, '163', '$2y$10$knxwOPWTUcNuG3UQBiwQEusqetidBxiDnRR320Bff/1RMO0guQlpS', 7, NULL),
(4809, '164', '$2y$10$i2hA2fP7Ad5QWJlTCdcS.uvE2HIugD/YQxqpBh304PlxijBt8PwlS', 7, NULL),
(4810, '165', '$2y$10$L7zeNlFPwc3Yr8hg/PY8v.WzRBX3AuV8G7eSE5E5gxQ06a.6oS6Xy', 7, NULL),
(4811, '166', '$2y$10$vnBkrf7.rqcQs4QUWXenlO0VWa7QLkwEPhVtz0Mnwtos.89D19EN2', 7, NULL),
(4812, '167', '$2y$10$pnK5j6Y.Rh/qp3.Y9dcbPuyHsWXhPem1uS/EY8fLHTzEHTSsoYQf2', 7, NULL),
(4813, '168', '$2y$10$TZlhpJ.2jj5DzoAQliqgZuGmLTh43yUjPiH0hPOLXmou2wxosjjAi', 7, NULL),
(4814, '169', '$2y$10$P4yNDjF0lTlQcJkgW9RO1uZKWojrmPMptAso2it6e4C.rFdBSN1W.', 7, NULL),
(4815, '170', '$2y$10$1LWZmdoUP/UOprye0T84juV7B8ZRyEI/vQ760VzRs0k8.Amcm5./m', 7, NULL),
(4816, '171', '$2y$10$5FgEwMEpgg3R4wRM1L6PJ.Rb0bX.LMmXXH8sl9SSEEngitg9MpVR.', 7, NULL),
(4817, '172', '$2y$10$d63D/Ww..aJCK1nbUzkjQu7mB.YPK3hEktCENBsq3Hgqks3MIsbj2', 7, NULL),
(4818, '173', '$2y$10$liNqKkr.g958FN0LusIHBOqqu3awwrJpcrIQtHzJjr3dNxQfC1s0G', 7, NULL),
(4819, '174', '$2y$10$tVFMvegtDS0X9oqBbL0WUeAWDeCY2B4UHNlfDX7.LmpDPBcDVhfT2', 7, NULL),
(4820, '175', '$2y$10$sGt4q29S6/bQC8xi16k9huFC1BZci8Dgwzsnm6qnjaedZYltVxMnG', 7, NULL),
(4821, '176', '$2y$10$HctsbkYUFJIoiO02YUpQGu3tAV1fpws8G.pXU0P2zLLoKM1LWGIbG', 7, NULL),
(4822, '177', '$2y$10$RD4eIxjxq0aJrSVCNb8CZ.OgVXxR.16isD8IPZPrM5lR.CJL/wx6.', 7, NULL),
(4823, '178', '$2y$10$Oo6v5W9tL1P4RqWZStW3Ku2/WKAWvH6t4nSA6sVAu3mmJxabJW5U6', 7, NULL),
(4824, '179', '$2y$10$Fp7FsiwTtivZKEn1xbIttePi6aiJMYwdf4YNxBzbE1T/ZBwM4/ZWe', 7, NULL),
(4825, '180', '$2y$10$9ejVl8CjuFO1V5bd0cYJ0uoOyWqKJwZUYnveRn8ri8y34FAYlmx.i', 7, NULL),
(4826, '181', '$2y$10$l133ipwM41irvxfJD134d./v8uNHmBVVH7gprUi31RWEVc/jj9Acy', 7, NULL),
(4827, '182', '$2y$10$Ndj51FC3QvzLQBdj81W.sOjGCY3ohp0Nlb6DNxTn42lSW1mU0pXr6', 7, NULL),
(4828, '183', '$2y$10$dGwVCyrob6rjK.BKM3Um2ulrr85U3opvCpxY/ws.GVHaTup7/O/jS', 7, NULL),
(4829, '184', '$2y$10$AhBGKZ5kRiL98XAwhbAyE.PmldzKFRSySYzs9CrDuxjXNjAEl7JZS', 7, NULL),
(4830, '185', '$2y$10$k3Zr6lOE2MlDrYYZug3/5OXSP24Hxe9ENUfd0w5jDvtMYx5A83HS6', 7, NULL),
(4831, '186', '$2y$10$Ib6hXDJz2qq5cYNzjTsMEetKOQiIKMoXvM79dG64JtRmWr/9rC926', 7, NULL),
(4832, '187', '$2y$10$aKhU7bWnzk/eKhgotakAje29K4PmFQXGGHT4l/UQVh78E/dh7T79O', 7, NULL),
(4833, '188', '$2y$10$PhOQJnleZhHn1cQ0IcCfOetGYCjkEIn/YIVsXvbPGUTVai1EWLQ3W', 7, NULL),
(4834, '189', '$2y$10$KHQkEQCOEWWlki2HcO8CZuGnF0SxlK4s0tvknsa0Jae3V7romOg7e', 7, NULL),
(4835, '190', '$2y$10$jbtuonTifZ8Q3C.O/7yRWuct6QTdKjFi7TRIxmtFTEYltjq7P9kyK', 7, NULL),
(4836, '191', '$2y$10$gBcp/pCt20qlLS5XGM5kUetfycHDVFuBWuHh3J1pNIVW2uTJAETkm', 7, NULL),
(4837, '192', '$2y$10$85rdI9GtFIAxR1uwb8K5POrGvVCqCb2o/kpOvIe6Nr9kTEP5uXCCW', 7, NULL),
(4838, '193', '$2y$10$abowicEQJhT3QPBwV.U7uuZonhNdftzOFyctd.1Cb1EfPWXH12TU2', 7, NULL),
(4839, '194', '$2y$10$U3ELuAzm3AYcbA5E04dBTOX0YJNN2U0ncW5k0FCtkdEPmNO/MCjfq', 7, NULL),
(4840, '195', '$2y$10$pvSOwh6.JuKW2nTp7McrVejdlDpPBrRLeKh6NtCP9wCeQPwPsyP5C', 7, NULL),
(4841, '196', '$2y$10$Rxx4j820ZYsN/YJ8wkoT1e1fwg0x2WuTRItPYklSmzzMB1h3Bl3f.', 7, NULL),
(4842, '197', '$2y$10$SPxc9Cp5.mP9pKM6xB8sBeKOWa4gK3IJ0/3krKe29aP2yx2b6bf7e', 7, NULL),
(4843, '198', '$2y$10$jKWTtgECvU0CS3wgkZ71puCtRfqSRrbhbn8rbccWNMFT.d2fCAl3e', 7, NULL),
(4844, '199', '$2y$10$8EY3yri/kYegqrUFtgnsZ.dx56pA/B0xA5LySfak.Oa1I6jg59K/m', 7, NULL),
(4845, '200', '$2y$10$YpwSSbLbxoBBYxGcm26egOWMJosf2T.Bp6LiUqfgk4/9Bb7EsQi/a', 7, NULL),
(4846, '201', '$2y$10$Ea1zTqJvGOWLJQtTmQram.7EURORrHGMbG04KZJ.0jv38Vy1Z3o7O', 7, NULL),
(4847, '202', '$2y$10$/oHPTvBBTgwi9X0UQdkcFO5Qqbs77CyWR0sfAyHB4hxuKVR2vFZCG', 7, NULL),
(4848, '203', '$2y$10$7kfomNL/PKXEF0ZE.U.jR.bKFFSFvLG.wb8Y4/DKcx.Wmgfo0qYAm', 7, NULL),
(4849, '204', '$2y$10$w3b.vkpuEANf9GyQyMqiL.C05XCdwYDjITgDMZELH1Y0pZVHfKk1S', 7, NULL),
(4850, '205', '$2y$10$w49Zirzq.EHPUYU.nm1E4e7YT1nR9QtkgCTT525Ujdsbq0mOQP3ha', 7, NULL),
(4851, '206', '$2y$10$e/mFnq1AyNuP9De8B8HK5Ok.EOvMOoXgVc891YzN3.d0p.iE0X17m', 7, NULL),
(4852, '207', '$2y$10$XSPt1lfuMqEj5oM0Rw8Iz.aFN2Q7rXbsm8bsb1r7DGRPFCTRYt2LG', 7, NULL),
(4853, '208', '$2y$10$kQKj6sEJbS8jTYF95MWd/OKak3W4FDF389O/UvW7mOBpFVoN8lvrq', 7, NULL),
(4854, '209', '$2y$10$gGUoI25uSToAhKTmG3M6NeyVk4H5onAN1bsZ5P/2LjC3Ear4gVK8y', 7, NULL),
(4855, '210', '$2y$10$OpAtL.NWJxqTWPubaDDFSOTNe6oxYJeblTO6vx3ghcB3yLSN4NHye', 7, NULL),
(4856, '211', '$2y$10$KOeP8cPDaGBgeFrVmwQUT.wZ2WlKnPMUZ/XhC1G7KHtFVJwFubdOe', 7, NULL),
(4857, '212', '$2y$10$fITjqRFzmeL.6WnfLI0HKeyxNrOhKQLkNvDhDGUtT7E2E.aa4I/5a', 7, NULL),
(4858, '213', '$2y$10$9A376PQ2uYshVd6UB1Rvvuo2/DVM5mbSFDV/zzf7mVAt.eKxIzpG.', 7, NULL),
(4859, '214', '$2y$10$MJAcREqaWkYb27isHwY91erTn0QVbP7ojSlin5CSTNj82ak.99WpS', 7, NULL),
(4860, '215', '$2y$10$BXRdjuxB9LkjbunoJQP1vuGVtBsKFXWY/LjHLOoUHhUjXz7z4P2rC', 7, NULL),
(4861, '216', '$2y$10$0yerrWU6wpV0WQq0r6o3WOxPw5FMdEQoOOkFkC2GbJO43r88aopWC', 7, NULL),
(4862, '217', '$2y$10$eAYPdfTOmeO2wyvM0xDnKe8puhy953DzNVLL4ta2/l1BLXpoRCn9u', 7, NULL),
(4863, '218', '$2y$10$XMQPfy3zxb1L./xTj4Dnm.bWFdf.66tcq1g7qJHK2VKzYdBjDvlI.', 7, NULL),
(4864, '219', '$2y$10$XkDNKRICM7/SC.xPDkIxT./KKJODKaKACYaHZz3ZvTp97BzTEwnD6', 7, NULL),
(4865, '220', '$2y$10$Zkgs6FLgavkw8zMXXTT/zuBYR1nCVucp3n5BOxIDHxSbRvJ/vF9ym', 7, NULL),
(4866, '221', '$2y$10$0zg//dTx8W2ZhZ9QcT2kGe1bK5bp0OAcU8MLpjLIB27hTmwbaVxHS', 7, NULL),
(4867, '222', '$2y$10$peK3eLhPfl7hIDEMRrkCXu8wt0MumwEV.dg5IF6lhx6HhiJii.tcu', 7, NULL),
(4868, '223', '$2y$10$lGPrLsIxhp4AfD.7/dmHden.Igm5RZt0wsjytegDWs/MyKJoOzWqG', 7, NULL),
(4869, '224', '$2y$10$YgN6C2LXiJ4Hsr38cjC7iuRrnxA9jkY0La7QBTqd5qKgJY3WvtZMS', 7, NULL),
(4870, '225', '$2y$10$Uj/sNIR5wepGQQp9.CWyN.DkQwZOG2hhAWIxO6cFaKxWbJUVCZmOi', 7, NULL),
(4871, '226', '$2y$10$GIglQyfm4nP9oc20cfL.BeM8YAGBJ5BVIbS9U3/wOUhn54JUZE6um', 7, NULL),
(4872, '227', '$2y$10$Ij9iO2CWqR0f9DmamV1DN.rMJDFJ7m5xGaRjuCF6vpUIC0JOsJkkq', 7, NULL),
(4873, '228', '$2y$10$jEtF7trm7YSxgj2rd6OEkOtQV6EHkFPPcp6omQGiCQJnfIZhQdJae', 7, NULL),
(4874, '229', '$2y$10$mxekEn7v.JMs05God1mi9OZLOExYdAum.IUUSk224Fl4kkxsbhqD.', 7, NULL),
(4875, '230', '$2y$10$jKyMynN/3Wfo5Ex85ww6j.QWJHXbgwvxcGum6ge/9Efy/iw23dCRS', 7, NULL),
(4876, '231', '$2y$10$RVaXydrF2B0vhZxvV34jHupEzLT5m0NuzMTLs5bAwL1rBCICYCVmu', 7, NULL),
(4877, '232', '$2y$10$E6AuuaWMQBuHEhLWcxom6uCEiptrvED5.Tfmf2uWNW0yvmGnSN67S', 7, NULL),
(4878, '233', '$2y$10$6145CHwPsmS8/dUp9KMmFuFyTffoOFSTnEH585qNdlnj9HxbKFWK6', 7, NULL),
(4879, '234', '$2y$10$Cu6wd60eXYThiaXdpwkH/OgM.qXOVLAxwKSbZsLyQem9BlDCSpcvK', 7, NULL),
(4880, '235', '$2y$10$hxdy0HnczGgYQxZRCW4W7u7raW/LDzFew60yYLqhDuKytV.gMTDWG', 7, NULL),
(4881, '236', '$2y$10$3w5ZlwOfJxJbjU.6joEtK.GWT0FphNLZzOlaPruY7fUTh9COpJf3.', 7, NULL),
(4882, '237', '$2y$10$.jluBoqn5/7qonhtoOmSKetbNUJ3hug2hmvngMU4mWDETAsxaEClG', 7, NULL),
(4883, '238', '$2y$10$xB8pLM.aSzidszJFU4udyuBf1QK0TaZFfSq7CXUeqdeXQ19K2XlI6', 7, NULL),
(4884, '239', '$2y$10$03UA2uPj3Mcm.9XEuIm/mOXtK.6XCmp/kN8RmChp8HRGZjguV2KDu', 7, NULL),
(4885, '240', '$2y$10$EIuOdRckQieKSgij6bTO8efdfttK8.FG4z45Ag8OjQ3AOYNf2QFaK', 7, NULL),
(4886, '241', '$2y$10$O4OH4o5Vv8TIsrVlf25QS.08Se6xE0PyfaJ6/yIXQ/5Q7xCOx2CTu', 7, NULL),
(4887, '242', '$2y$10$peSZHLdIfT/K/TnCXYRZGeLFVnFNBSH1dYLm8azk0Vb2OicPpPnqK', 7, NULL),
(4888, '243', '$2y$10$tkE688vlQdFJztLHA0xp0u.ULxKwwnqwodSySX74JBKDi8m7qD4Ly', 7, NULL),
(4889, '244', '$2y$10$mFbvGMK7YRgPNPfNO9QnE.fFkCk6co10GEvQrGFHsJhmvTKXjb7Om', 7, NULL),
(4890, '245', '$2y$10$vwjj4E8uJ.wOV.R7B09g8.VSyMbLpVrtbyspvUg.pz.aYv5RrMJiC', 7, NULL),
(4891, '246', '$2y$10$eNXIa3mCYK.G5NBQ2Xf2Eex7HnVZIT7nJdeJflPekTVKUIc9vl3X.', 7, NULL),
(4892, '247', '$2y$10$Nml/92WfxOre6YbQett0FOduHwgNrVwubCW6b6PZFLw8nIe.M7cBy', 7, NULL),
(4893, '248', '$2y$10$2J5c2Vf8YcWMRwWdM.QjYeZHLvF4vUNrYgIT8mk75BxI2ufAtb2v6', 7, NULL),
(4894, '249', '$2y$10$VGQGnRu7hRabDhBQ8Wu7yuoEUbc87Wx2W6EIybQ0AVW0KDyfLAiQG', 7, NULL),
(4895, '250', '$2y$10$WD0ZBGlE8laUexYDeYJwDefoKGr6XdelaDDc5/q9tPkF3LlZMxB4.', 7, NULL),
(4896, '251', '$2y$10$kLY3EBmJwbNTJK5QDIzznu39ncshFOfgNDKauvap7paJT2ApTBDPy', 7, NULL),
(4897, '252', '$2y$10$v1Z3.579BzFMdxxmuAVOIOEAKX3aXL2PitsuVa/jYg9ZW4BGaVFWy', 7, NULL),
(4898, '253', '$2y$10$xwhKCWF9FIOHfjQd1JRAg.XhF.BPmD3NBNSqE2UD6Eoi40xsCCIN2', 7, NULL),
(4899, '254', '$2y$10$zYiWe704.A/eGUC0EDg8mOcY8dNValJhR2rSCY5XU03edQ36OU5rm', 7, NULL),
(4900, '255', '$2y$10$lhnjb21idfbYTsHPOP2gGOpcC/Ns/6RG4LTln4wB2bI92sLPwiZoO', 7, NULL),
(4901, '256', '$2y$10$RTYV/Kcgd6xQlIzkhWXLS.FKnb.cSswixG17KGxvkgX4ASjo7kY96', 7, NULL),
(4902, '257', '$2y$10$kWN9e2lZ/vezneEjgRf5suZjYDGOxWElNTIO.KqPIp8w2Ovb/bAT6', 7, NULL),
(4903, '258', '$2y$10$eqMHtBmeSLDMWRB7.F6F.eFApFzRMEbYXaUJB0/EBFbkbzAm1JwAG', 7, NULL),
(4904, '259', '$2y$10$V0taCInfOb355DIwLhOJveUAw2gJfSp2q4qb/UENPd4KytAfbNliq', 7, NULL),
(4905, '260', '$2y$10$qW418vqR8fxlNCkBPXy7U.ru3sKMMTvvvOB6SNlH4FQeuGfa0pe.K', 7, NULL),
(4906, '261', '$2y$10$XZKl2sfvleq9hUInI2SydOy52K3Bu0uaRnZNoGWXcxuu6pBcdRxdq', 7, NULL),
(4907, '262', '$2y$10$Tdp9hG/3hPNPg34on/wNeeKByNw/Asqor.xpCtYKjqCtV0um7PYTe', 7, NULL),
(4908, '263', '$2y$10$9/fTNjQ/5QGai9hGrBIx8.gcZq4Bt73yMsucrJ3R60lITpv4VPgu.', 7, NULL),
(4909, '264', '$2y$10$hT01eMUzdlb60YN35jgfLuBHDyPXmpA9dcy1AdH9T/0OnvRFicB5G', 7, NULL),
(4910, '265', '$2y$10$yHk5vCemyW3OUS2QY2AG0.RzH3YQ0ss8VX9Y7MTxXKMhR6U0u3Qpu', 7, NULL),
(4911, '266', '$2y$10$s3KXyqiot1aaxUzzOSNDhejg0ggpsPclwB3aqsiLoicg8ANFme.qS', 7, NULL),
(4912, '267', '$2y$10$Kk7iXk5MSnAtT5Fu4dEWDuqDJBbqKfas5SyGz4DOd8DncvR6AkjgS', 7, NULL),
(4913, '268', '$2y$10$HCP/y0Q8jb7DI1nEkkzxju3pSh9WCGurLXv1bff72RlZTPflPooOC', 7, NULL),
(4914, '269', '$2y$10$NT4e5W0GFEcaYCJTS2Yq8./BV3bSyawWQH78WFdyDSYmubQ6Lo.7m', 7, NULL),
(4915, '270', '$2y$10$h/bnnwDH0K2B5rnF1g59segFnJ5BBG2KlOxi68Fr2iN1TxDeuvFvK', 7, NULL),
(4916, '271', '$2y$10$lWwVCZdrqlx5BbYrZp2/o.h5s8wEn0MJ.14iEBwQY0JcFLshIYDWG', 7, NULL),
(4917, '272', '$2y$10$PenlErFkpsbc7iY8yCdE3uuyesX8lwvojR/QV.PaIdxrpG6Wvbhe.', 7, NULL),
(4918, '273', '$2y$10$lsH9Y1vLDn.Y2FhqTfOdTurEQNsIfzIsz0uWmTCKrJDco3r5QUmmi', 7, NULL),
(4919, '274', '$2y$10$xIuo5L8YYZLZ2.UwTnbBm.LJQ9N2i.00WrpPgXfZm6li6RhR9i6I2', 7, NULL),
(4920, '275', '$2y$10$ggxOc6j8aokd/DZM3qouUe0L8Pu5mxXCg0ZRgO5xP7hExT3G5QGsG', 7, NULL),
(4921, '276', '$2y$10$zjAtskPa8N8Gtm3pK16UOe/XuIB8BNWNyZA.9vx3I.4dtkz9Vk/6y', 7, NULL),
(4922, '277', '$2y$10$e5n63Y47gb4gtJZLEA6h1e85Z639yw0nFvOTKuR385tUZXTGcaR.u', 7, NULL),
(4923, '278', '$2y$10$gTIkuikEJ5WNaQFHftzHluORyswAdqJifCb9RlaHBxKIQNBYxhthO', 7, NULL),
(4924, '279', '$2y$10$p0ixFzymEKh8HYSIZagQqOtb8PpDaj8pEFbopxuwkovgZDju95vNO', 7, NULL),
(4925, '280', '$2y$10$8.1yDkGpIz42tGTu0zl4ReVbWVFNKhxRShDL6EY6IZccDlCIzkJ/C', 7, NULL),
(4926, '281', '$2y$10$CxlObsCkwiZtUCm4yw6JGuzzx1Qa7O2V0LC0CWQ7IBixvPmPGUiZm', 7, NULL),
(4927, '282', '$2y$10$QYh1WhicliIKPGuYbofeZu2k2e1hD9msBn6DbxibU4.6tWToY2hrS', 7, NULL),
(4928, '283', '$2y$10$ZfuFAMxpnWG1ebyZFKBJ0evCRIP1osyXeViZhfPjfQxPZdnfJyEoa', 7, NULL),
(4929, '284', '$2y$10$c0nX3NSDxnSGv4LkXxPJa.lsNe78o021E787CanJsB4SciFD5kCii', 7, NULL),
(4930, '285', '$2y$10$M7mejwr.G8B2ImXV/9vfIOK.u9LjRx5bE676KW9rrk1IB4HvGI19m', 7, NULL),
(4931, '286', '$2y$10$q95Wy78u3FYLYDxusvj4dOarFjuqA9k4zAQiMd9U/c1JFMcgFIihS', 7, NULL),
(4932, '287', '$2y$10$7QVfHFbWxW4xKKBXs6qen.wTNsFt7rZpa4VGCqkpF4Xo4ARQSf3Z6', 7, NULL),
(4933, '288', '$2y$10$Nmmq0nPtrwFjvjfgy4/c3eU5yNQBOnNVCcB8gDKquD7esQi9Anh2.', 7, NULL),
(4934, '289', '$2y$10$RNSCJZ/Y5eH5GCq733byvusuDgdrht/Z5t9oLRxPy8K/D8hmdMzVO', 7, NULL),
(4935, '290', '$2y$10$7Fk0RODAkj8hVPpBwdP7ZuhbhwCMiXUUwGm6wJYZ3DZNurz2c9aJC', 7, NULL),
(4936, '291', '$2y$10$NgSDSL1EO27Ob./QfGRvmuP3VrK/v52bHumA8/ePpm0ah6kyjLHtO', 7, NULL),
(4937, '292', '$2y$10$FEzw7X1hn8TtVMXGE5ox.OIFY9HNlxztJa3I95j9Xhiizsv0xB2dq', 7, NULL),
(4938, '293', '$2y$10$ptV5OBf2WAs8pHUeWIVBueFYmsTtCp2eNUTx6m5/ekhB72tygx2kS', 7, NULL),
(4939, '294', '$2y$10$bcGRZcUR3F9lBL/tITiV1uzHmYWEuqPoNGT1bu17mTTKBtdCLk012', 7, NULL),
(4940, '295', '$2y$10$u0fz5lYVWHoFeTwAIs2IGuQvnMqwiL3fvUguKF98x0fcmNFbm9DBa', 7, NULL),
(4941, '296', '$2y$10$gyj8VazaLwqyeUTQLG0MAec8XwDNXd5SdemOSC1sT08h9vjjPRkiW', 7, NULL),
(4942, '297', '$2y$10$4Skuo4pCME5O96qJ3uEOre8Mjynp5xaTXvAzczXb51BP3gyxqCjhO', 7, NULL),
(4943, '298', '$2y$10$FuOh8Qsbu2yocAgQ8C/sTeecU28OZHcBneKknZarbO24o7M0g6wX.', 7, NULL),
(4944, '299', '$2y$10$9fK/7On38D/95c2hSngxo.ge6HJE38b.o/AkIO7.TgHI5f53ArsO6', 7, NULL),
(4945, '300', '$2y$10$df9oizsXvAQTMc/e8TyNDuEThZKDHZorpqNPRUafeYDFy93FlUF8e', 7, NULL),
(4946, '301', '$2y$10$RyCtqwF6e4vd426/rcnRuOjLV5frsuEOCbE0JwLgFNFuA8ziTH3ma', 7, NULL),
(4947, '302', '$2y$10$w0bA5l3W3Qiu8oI2YjmY2uGH4mvpf4K6PB3p41xSBQV/D1N5w5PwC', 7, NULL),
(4948, '303', '$2y$10$ModlC6R6HV6yJLQjj9TcLuNR1jjOaFbHU4If18lbfyuquQObW8AZe', 7, NULL),
(4949, '304', '$2y$10$vLP.ubqG8jzcNF7z8KPrUOUOVlATxPRTfSmYukYCjSeAfjRMhVnhO', 7, NULL),
(4950, '305', '$2y$10$CPurnPcfTJ4UojbYZha6u.K/OSa1XArb2vuJPtCsEomFNWN71whby', 7, NULL),
(4951, '306', '$2y$10$Q4RGt.LYglC4IOTCwXqpWOa9FGEpxeakz/0zN7UT/naCi1.z/QHbW', 7, NULL),
(4952, '307', '$2y$10$e0JCGyqKz3I9YNuS0CkafuMSxrife1IpYJLJt81h1OyEioT5GBJia', 7, NULL),
(4953, '308', '$2y$10$qWTCbXWdMJoP8hqrjH6tLeKfWbLLKJGGWLHTiukstKO8kab1ahPEu', 7, NULL),
(4954, '309', '$2y$10$X/ODW76bzP542DQ10GRKfuPXUqwCYFwusuQutQ6yBPJECIZCjiyB6', 7, NULL),
(4955, '310', '$2y$10$S9mZ6KBxlROU0L4JLT8jFONF4ZZffGkQzt1n4DdwLbrCnsPImaAO.', 7, NULL),
(4956, '311', '$2y$10$1AAn6IrAdQp6NWuJ4PFwY.SkAuuyfxXlxn3frjPeJEOdU2eE.K.3G', 7, NULL),
(4957, '312', '$2y$10$6kydgyYVFGi94Kf0aawHeOBlD938cnuEs4F4A/TJ6ye0TLRrZG9Be', 7, NULL),
(4958, '313', '$2y$10$PhnTI7vkYH3d7FKcavbuw.cwoI723G9yOwi/O2/3AoJYJqyrmnLuC', 7, NULL),
(4959, '314', '$2y$10$6MRKkQrhbwMrAq6LsdXToemgVGH.Rv.0rdBTr1S/n5SS/htXwXf82', 7, NULL),
(4960, '315', '$2y$10$4Z2HXOGtw2K.7Jb5XY5ZMOWMkxeQ2rq2AxBNLw00sTllgGygR3UaS', 7, NULL),
(4961, '316', '$2y$10$/f/VqoqdVsR8KG9xh.L9B.SObQYZ2C0MBAzhMDsoN3bD9PxGCVeDW', 7, NULL),
(4962, '317', '$2y$10$O5QctCYuSVhVIKtTotS/tOecf1zZLy5x2EE8Xm/Pdx9Ozmksf0lqi', 7, NULL),
(4963, '318', '$2y$10$f5eV1Yp3lR0jYtl1gmZ8c.hzhgwrCiVi3aEWP4mmYq6erM9/ZOY.6', 7, NULL),
(4964, '319', '$2y$10$XN72J.qAuInSPeH9h/Fbju/VC64RNoKrH3v8H5hCit6672Uh.PP8.', 7, NULL),
(4965, '320', '$2y$10$LCHh7C3BGBABmsKRzgdl4OFaCdVoNYmX8KvDDuS5q7/y4yiXpBfXy', 7, NULL),
(4966, '321', '$2y$10$CS.xc4tPOOROtgSp4341euV1pNd83dc3cHRHTc9cnVKwFh6S2.HVy', 7, NULL),
(4967, '322', '$2y$10$acrPEDmQnkyPYN4kGZbuCOdRUmAOcJKcbJs8tri.5sPOfdu2F.Gty', 7, NULL),
(4968, '323', '$2y$10$3y/XsnPw2BRw.tfAGyZCie7kncgpxYDlFUIeDAULw6vU6LO/G3LW6', 7, NULL),
(4969, '324', '$2y$10$barxZAqe5ZfbCbAzyWqjiuQhMWXrlwpZiFc/Nb6h.vbLOKthh8qtq', 7, NULL),
(4970, '325', '$2y$10$vxzwNn/r0SiCP0BG7gjsv.ZieOP.HDoguwjdScoPKbcHhzpOPfGuK', 7, NULL),
(4971, '', '$2y$10$3rZvOItdqCeILeqdZSvJr.FV3ZNq4IF9yTdS3hYL1lpC9cTwLPlwK', 7, NULL),
(4972, '326', '$2y$10$R51c3fqYMS8fYk5BqyGMUuyA2FF02zvO4Z04YVFHhwC../kH1geZy', 7, NULL),
(4973, '327', '$2y$10$qt0afB9.TjyeUygRIHCnH.Alrs/ix0k/drBUc9.yR7B9bSKAkK4S.', 7, NULL),
(4974, '328', '$2y$10$vP8R/yJytDn0yLN/AikWBOtf7xcUF8lIuTPwFfOSdoXul.5cghQbC', 7, NULL),
(4975, '329', '$2y$10$zPRS83xxkcVZxsxrgV9Y6Oz9d8gUlBvWpq7ST9GiqPa6C5Y3lBw/6', 7, NULL),
(4976, '330', '$2y$10$fenYgblhyKhNm57OYW2/AO44MndelEOU1oH1Hjy6FsyTmj5syF2kC', 7, NULL),
(4977, '331', '$2y$10$KImlsbDQRQFNBRAjzimrdOosBEAaClA/hL82YdwV7LRQnMvgnzwc6', 7, NULL),
(4978, '332', '$2y$10$YfjQ5sOuANtFxDNZKYlvYOch8TaBpKuxmVfk5bqMv1A4CjzNHaAPW', 7, NULL),
(4979, '333', '$2y$10$L/L576SsvH0HGcdytnwZ7.U/T/SlOm3KdsHhhx5FlRkdiUcSuMsTS', 7, NULL),
(4980, '334', '$2y$10$UkajVFIQ1AyrJefN24bACedulAWodwbGQ2TtgxTd88LGTdYpSN29S', 7, NULL),
(4981, '335', '$2y$10$oKfi38QDgqnJ0UPoA0elfOdtsf98TtMx3dqRI1MVSSrJvLqI3qm3q', 7, NULL),
(4982, '336', '$2y$10$43iRyPvZToFQQtxKwt6d4.NIeSWZyGPSO3r2h8Wv3xdFLE61l9KQa', 7, NULL),
(4983, '337', '$2y$10$lLeCIrybMP/nVXgdIHTzP.MW6kgphuLN9EeccyVq4EozRFj.lgTSi', 7, NULL),
(4984, '338', '$2y$10$630WMywVw6JfIQ8ryPSW5OtwioV2Apf4wwo5.QXl4gMJAKBZ.mPny', 7, NULL),
(4985, '339', '$2y$10$1gn0KDXaJSwLa/2NGhhPz.qkc8EAFbFMkFQTJBQke9WxTDhZEjXc.', 7, NULL),
(4986, '340', '$2y$10$w7iLAK3mGFQSLsdndh6k.exZZBrAlyEzb8wgsjnIGSK2tGVCY0QAa', 7, NULL),
(4987, '341', '$2y$10$a7mq/vCwmLVPLHSn1pcY1u53926bSPFmymWDptVe/Wk7.2eBA.dbi', 7, NULL),
(4988, '342', '$2y$10$Txpna1hUfhjLTxMIhcgkFuRVeF.nSwmXu9mHRFZPbl1C3p27g9TnK', 7, NULL),
(4989, '343', '$2y$10$YzofIyFPay1riMveTnBZ8.H6uB7cn99H7MAdFswyiOWnw0RIogeLy', 7, NULL),
(4990, '344', '$2y$10$lxqN/hYhIiohjvZHFIuLFu5dXKHaqjuWs6Y5MX0.Hond9yIPbkgLC', 7, NULL),
(4991, '345', '$2y$10$2stfJCjBX1pbMU2pLOjJVujx86FXwxd8N60yN1.y6P7AurBsgZwVq', 7, NULL),
(4992, '346', '$2y$10$bWJAqFPuVZU7R7iPLZJJMeK1LOsibEhV.aOLYnf/2ZVZvF9U03uOa', 7, NULL),
(4993, '347', '$2y$10$eF3biWekFtaIOZYwzwT/5esHbnTJS.kdzYJGln3quRFUg2Gdc1gqG', 7, NULL),
(4994, '348', '$2y$10$a1iYktGENeRG9OcOfgmovOusn9XTWFrhxn7G5RYlXxZH.Z4vce4kG', 7, NULL),
(4995, '349', '$2y$10$02XyAZgrCZZEMziink6zUuKWyHznOQMc5r3yF4N/OtDKBv5iCMOrK', 7, NULL),
(4996, '350', '$2y$10$qSIZGvP6KyHF5jk.Qhxtceq3HR24l7AfJBKYrUn2Q/1SvF8bTCXBS', 7, NULL),
(4997, '351', '$2y$10$9TMNzsz/wPLdISDf2V8iBuI9fdt5r09ByKRv9EaTS5Oyo7KlP8Iei', 7, NULL),
(4998, '352', '$2y$10$FaMwySZljkEchHBddFRxvesluKYYPrTW/RaMf0J.kPSggvXYfA7Ke', 7, NULL),
(4999, '353', '$2y$10$sVEPZdatPRTXAsRz2lyaO.FPO/DxNX4PW.i7v4ghSevxVgcabqdWu', 7, NULL),
(5000, '354', '$2y$10$zNLSk7fG7Yuhr3zcrVS0Y.UDpGrM5bePtvGqdCVdrkvs50Vv1nsou', 7, NULL),
(5001, '355', '$2y$10$hKtte7iOKh/VAbeY3CzUnucQqUnQtxhEL1m2u119IgsAGdofUd7v6', 7, NULL),
(5002, '356', '$2y$10$MUzy.7II4p6eXARkDZVPkeM5fb4.pklT0G3pB/EJ7tPmb.J1JLku2', 7, NULL),
(5003, '357', '$2y$10$VQmbLVunlr2lADKrpNHj.e0h7hV1XxcE/kkYI1f.PJ7mzwmQsY/Qu', 7, NULL),
(5004, '358', '$2y$10$0//LFuLDCp02NMFrUSFjk.vqSz0tNr1PqpXEquM2FT0.f.hkAxISW', 7, NULL),
(5005, '359', '$2y$10$QJ.UM.YWvMD6wfe.nQoI8OTUDg44I0l47.bL0rdufnpnx2G5guHTO', 7, NULL),
(5006, '360', '$2y$10$PxQiWhe5vBBHAa.Q7nN7S.qB8QlZGo5PnjEv8ls.S84CArt3/FLPu', 7, NULL),
(5007, '361', '$2y$10$18dNbL0jXeXkBeia8A39juD.quJfo8PzbJY6fWNVRVBNZslzbpWjK', 7, NULL),
(5008, '362', '$2y$10$SUfx027V/HevguBuA9W5JeqW0GVRLogW6JAO8IaHKzJdAuB61ZTKm', 7, NULL),
(5009, '363', '$2y$10$..5Y.7AcjXOSBoniHdVMwOvX7DLopZFB5Kwz4saU9HkCsnRLdCe4.', 7, NULL),
(5010, '364', '$2y$10$6TG8Tmjtfv/HJgbHO85nROjNSXPTYvK5ZkcgFJwdPgXx8tylkYP4q', 7, NULL),
(5011, '365', '$2y$10$Fvt.NWB7YP5p97qgV.XTMe9BKXh/BafpraV1MfH8.Hfehx5PdMXhO', 7, NULL),
(5012, '366', '$2y$10$aNWluqJQAZjEjQSqGWfyDuXM66o2t6ByYudhSQ7NfpnH/o9J6IQe6', 7, NULL),
(5013, '367', '$2y$10$lNpcuQRQB0gW7JAEuh.BkuFSqDwyCyz3Bo1Ly6y6P/8r9MGLQFkU.', 7, NULL),
(5014, '368', '$2y$10$rPbDgPTMQPV4NishQiwtb.ewCcWu2LArN3qXqBRYVQqXwYeRPIxGG', 7, NULL),
(5015, '369', '$2y$10$CsUO77V076REMoq5.KI5yeTnuHCTL3R7zeYl.ENxIKsoq2GjiDXpm', 7, NULL),
(5016, '370', '$2y$10$52RhYvHqIi70QUiCTCDy.OHeaTeh1WdtRegrkTAvezdidDDWbJSEK', 7, NULL),
(5017, '371', '$2y$10$V5AgXeNTrtFbtyxXSG5WsOyo/dypRxLDtj.dTScku4qYanXWcZmOm', 7, NULL),
(5018, '372', '$2y$10$1zPXb5SUh/FDjPmR2b/q.u.6iHIJZ/yI/dsSgq8p4LAWaEvpTtdSK', 7, NULL),
(5019, '373', '$2y$10$kCUWfRf7qXTtSEpXx01afOzZ6OYyJyZWqmVmkY1z8ETe4FD/2/IJe', 7, NULL),
(5020, '374', '$2y$10$q7n5LJI5HGccKEudLt99ROs2cHdkELY.Jr/6poL5XjwqHjSlwjjIy', 7, NULL),
(5021, '375', '$2y$10$utDvKPp64v19FeqsbZwerO.1YhimgcYTzC8.SQ.hiXv18DcNlNVW2', 7, NULL),
(5022, '376', '$2y$10$PFh7zFE8kCc84nH2He.ZN.1RHOpF9xQKdHDLMinYh1CL9PwarXh5O', 7, NULL),
(5023, '377', '$2y$10$Gs.ZYvvMp78Xknuvk43AHuBsIV2gFfT/XBzXwFZQva/zOApxBTDHO', 7, NULL),
(5024, '378', '$2y$10$/PZyKH2kicQIlxxpy7TaPut0.2R3KYodHIr73zhHEr2pLp1GRTTbC', 7, NULL),
(5025, '379', '$2y$10$K9uqtuubXTGoblKk01Zlwu5evGDnIhFEh5useAqEBqik3zhsEOoJ2', 7, NULL),
(5026, '380', '$2y$10$R3gArGwN8HFyd5RWAucPs.5ofo7E91Fg6RIPPpHUg46YbhPsuBHjC', 7, NULL),
(5027, '381', '$2y$10$ZoWOBNznFVOQCRHJQYro2OMpATiPDjVWVR4lGgYJ71oW93QEEcpaW', 7, NULL),
(5028, '382', '$2y$10$nKfCbdyMpTblzjX7xbZQB.LN3YnqduzbMltYuZoJiZH85nJToBc8u', 7, NULL),
(5029, '383', '$2y$10$lP2oLd.6r7DgbbWdD0MmtO34RAGAUlORH0R87rfCL3WvkCrj3z0m2', 7, NULL),
(5030, '384', '$2y$10$RN12YMgXMdewsYPuYybwMejAWRiKUHsfi0nDGNdwWG/HH.02zpta2', 7, NULL),
(5031, '385', '$2y$10$hgnrwLIuyUA.8Bsy.mD8R.k2cowyvtt8MRkbcgXCo98y8pAZ5O/Ei', 7, NULL),
(5032, '386', '$2y$10$X2foMvpt0X.2/lhwmfl20emUYSonM47ZMl3cgQtkDzrXcmx94xMuu', 7, NULL),
(5033, '387', '$2y$10$8kcwhe/fMSRF9zr2Q04oaesaK0OOTa4ix80KScdP.7trYDm.n9Rxq', 7, NULL),
(5034, '388', '$2y$10$uprVwYUjQR48N9uEBqYUR.GheKv9pwrf7YAMSTYtonVgxir10og9m', 7, NULL),
(5035, '389', '$2y$10$blhM.SQ431ayOXAeJFpOM.cfGqOekrWk67OChLOwuzaVPk/Xthj3y', 7, NULL),
(5036, '390', '$2y$10$cuX7lHxhsZgEgG42wr32j.Z9zm0R7SK8JC9BKDBIp3zNdl4V6SSS2', 7, NULL),
(5037, '391', '$2y$10$Jo9QlynhpX.1YvCxdK2Tk.qB3XxbIVczfEcg.78Jg8zdqGfX5yocy', 7, NULL),
(5038, '392', '$2y$10$RqpZk9CMOv2L1sgp1gpuv.xkzBn7NKnKA3V9ozEX/UmpswcnhgeAq', 7, NULL),
(5039, '393', '$2y$10$yQYfnzjixL8ssLa1pKq3ce7lRnBQHZNaztLYu6Oxcz.2HBVBbFh1y', 7, NULL),
(5040, '394', '$2y$10$E/uTnc.bSxnXayZyXCGfyOZzb8WkPNANIgNmsNnruL3LKJ25mk9je', 7, NULL),
(5041, '395', '$2y$10$uX054M3Q/NlMiDm1pluEfOSHRROiR1vpZEexTLY5exM.Bil1Dj/JG', 7, NULL),
(5042, '396', '$2y$10$AmvdoVLU6XaGweuLrO8nEeDRNyEbMvrt82hTONkP0XNG0b5o1GGPe', 7, NULL),
(5043, '397', '$2y$10$4hQHI1EwDCyF2QZ/GCxYhemeOsRB/puvHEJeLJxHIVrS.LTe2NvXq', 7, NULL),
(5044, '398', '$2y$10$Hx2nNpDsKNfCFCR4yxMoj.03sl193Hd42eSdzVpfg2S.Ez7F1M27W', 7, NULL),
(5045, '399', '$2y$10$FojkIzskWt/icw6zGbm.y.ceLKQYy/0p.MplCW2i5Y5c8Batir7bK', 7, NULL),
(5046, '400', '$2y$10$AtWa310xhf2zAtYyeUH53u1ChkG0GxTzn.he9ZglQ3LtARqZkVlGK', 7, NULL),
(5047, '401', '$2y$10$gvTcomaBDP3YGN480krz2uuxhmfvVZCGgzGYQrO1TBvoQHAe4Uojq', 7, NULL),
(5048, '402', '$2y$10$1Jk35irnNu45EICm9x0a6.TB5eI2nKtAfcMOKBaHuyVO6hyxFoUAS', 7, NULL),
(5049, '403', '$2y$10$Bsl9YN9CrMUPAN4Lf7xVpeRb5XYfzQyp.ALG7z2bM5tn/xsKWCIrC', 7, NULL),
(5050, '404', '$2y$10$xS7pbH2/hwezYeOgK4a9xeVCNLSU5a3kuJoIUHQA7xZ7OPQbR7bUS', 7, NULL),
(5051, '405', '$2y$10$wUlJjVXZ.2Quo3l5XIl1iuRKZZFDnxtihM9T.eEk1mZJIrpaMkbTS', 7, NULL),
(5052, '406', '$2y$10$UwC/M2uwxPCXJyGvfpULfeh3dI0/jtECncJAmz7xsq5xvm7VKe/jS', 7, NULL),
(5053, '407', '$2y$10$Mc2Wnd31xGZkgkB7r1qkq.M9gmKr2Pla0zbgj79qG60cv6yyuNCQG', 7, NULL),
(5054, '408', '$2y$10$ki0EMG6erWNWhHdHWtd0TOsaG7NOWe0l14WyqwbN0Kze1hKyvJHfW', 7, NULL),
(5055, '409', '$2y$10$4aovjbyLdSPmr02b5cLCquHjtaSHtI3SbB05oPAbchvK7J8P4E676', 7, NULL),
(5056, '410', '$2y$10$do1i/3decNRuTJ3WkcO8Zuadyiu4mmn4Vr6n//i.Spopmioz8HEeS', 7, NULL),
(5057, '411', '$2y$10$hy/4ay.Ga07ZguMdnX9a4OKz1w3Znl/0v.TtUs8ssUhkIAlCD7GYi', 7, NULL),
(5058, '412', '$2y$10$9oIBvOEyHGW/Rq3uk8EDlOdo3H4d7z.vTszhxEz4ulKOO3I7.FZmu', 7, NULL),
(5059, '413', '$2y$10$msnojXkk4aXexH/eML2EluLqneDGmniH9/ViihmmTel9djtgm6hSq', 7, NULL),
(5060, '414', '$2y$10$lts/4ZFgQMcU8KE09h/hnujS3.S9fWZpOzyLeatiu9RNR.sGFUaDC', 7, NULL),
(5061, '415', '$2y$10$qPh6o3lWKxnGryXVu22EMeSRrqti67kAHALNLa8Twf1QzSFMBNj1u', 7, NULL),
(5062, '416', '$2y$10$rKfSqNWCeTfHw73Zx0N1pugYh8pJY1YeOwEjbi4eh0RPHhn4pWeOi', 7, NULL),
(5063, '417', '$2y$10$QodfnXg1AURxGzizLALog.rwyqKS0L1XSZMbmOE8VQ8QuPDHXjV1a', 7, NULL),
(5064, '418', '$2y$10$.JYehFQwDPn3AqmGv755Nu2txp1lGvIdTIl50fAEUSyfz7Vqo72RW', 7, NULL),
(5065, '419', '$2y$10$lormZAveoWLfonK3R6qcLuY5qH.o8JAs9b6uo7m6tNizmit1R8CLa', 7, NULL),
(5066, '420', '$2y$10$QY2D6QuukOOgA0GjpB39ces4BVrIQGVdzZrXrbLUMxZkJuX36HTWO', 7, NULL),
(5067, '421', '$2y$10$jXfpaPTu6mQwpDKKtDUHiOkKmq9RY4GvKwjzLa6YE0x3ZtDrEeZL6', 7, NULL),
(5068, '422', '$2y$10$hKs4DWUpoHAP17BX94bfoe2soGSOKMnZcJzFntZUsbYS/O/x0jYw2', 7, NULL),
(5069, '423', '$2y$10$EB7YEl3RGUN3M1WLNayf/eOse67T8boOVIHr.KEs0l26NWLsgfq.K', 7, NULL),
(5070, '424', '$2y$10$/pTK3reXKDQhJCQc7TvrUOEFC3j8w4XS2d30uQerhH1H/j2KMihXu', 7, NULL),
(5071, '425', '$2y$10$rfuvYp9BRSYMpBhP1baLIOJG2ZPWt77kM0fXU2PkLBIePBHRmKnmu', 7, NULL);
INSERT INTO `users` (`id`, `username`, `password`, `role_id`, `pegawai_id`) VALUES
(5072, '426', '$2y$10$pbwfno9Tfgi8Ax9m5I4bU.sr0r9oLp0NDbFe/DcpSFYuBZB.Yi86y', 7, NULL),
(5073, '427', '$2y$10$YS4OLrilwsab1sMbY.4Cb.WmRbnipkghk951nhhH2LYumqaE22due', 7, NULL),
(5074, '428', '$2y$10$xOnOCvmmuE16cEeLELJNhOluJ/xVX7V28EwXH5eXhqMeLce/RjBOe', 7, NULL),
(5075, '429', '$2y$10$54.S5KLm1upD/ifkzpaf5.gH7bFhSZW4iwRxIlFQtl.eqqCgzpKDi', 7, NULL),
(5076, '430', '$2y$10$acprzJD/uhYDbsDEeDdxn.EUoME76VU62rET77WALXHdD9yDKmlKe', 7, NULL),
(5077, '431', '$2y$10$h4MHsz1C0GgkGfDq3Iu54OCICr3ZGOI3Lf4UJmAsDl/rDXyFXIyqG', 7, NULL),
(5078, '432', '$2y$10$Jnb5Sa78zeQQRJR55n8UY.yF1Bv4cnA/i53ZdDo3WcpZI6NMZI7Ni', 7, NULL),
(5079, '433', '$2y$10$NBX9y3B5/uKlua0IRXTjTOTtQpcTXDvG0XAAzrTdVbeWJwn/TLPlC', 7, NULL),
(5080, '434', '$2y$10$QBW4bhlJJM49u3wDOO0U1uBTi3Skd1tzIH4gjquO.//KH1QwbQDy2', 7, NULL),
(5081, '435', '$2y$10$j1bLAIlJvERhB6SUEV75iObXNrJy.IGss1C0G3pCHLBYNcsbu.BKC', 7, NULL),
(5082, '436', '$2y$10$0LB9bv/1j3M.pphFormPbOM8/EG6xYtDUDW4BsCcuCzdZkjR3GQnq', 7, NULL),
(5083, '437', '$2y$10$5rpstuOaetCKDzqbFSIsGuOWeXXBnrlbCJzpVMX12/hTnyhTDBs6u', 7, NULL),
(5084, '438', '$2y$10$RmA5w8nVPdCSqlZ8ibTo7.IfZZ5VKKtD5Y0xVka5ITHPM.ZShqdGa', 7, NULL),
(5085, '439', '$2y$10$xnWzXLBf5ig0Ot3CqERMZu38ZyL5adyYo5kLEe4XgcZeAs.cWcPAW', 7, NULL),
(5086, '440', '$2y$10$m.qTdyiSSpRMsF2.w48AbeiRayy9mEgNhsiMCq8QPleO6SXpnmr/m', 7, NULL),
(5087, '441', '$2y$10$Gdnk3XMwaMbDKID4mXolKuo0IlhAJfaG.cifTr1WkVqX7Y0AWRIr.', 7, NULL),
(5088, '442', '$2y$10$T5pztm4cRjYZwBMsYTma4eWyhu2j23wem2giRmfSGnBQy8i5rx87m', 7, NULL),
(5089, '443', '$2y$10$Z6iQiNkkXJ.O760FjkuJWuYOq6ECVg7UiHzLo7QnzCUw0owJQhIju', 7, NULL),
(5090, '444', '$2y$10$ejs0Q7EB26JubYLQQ1Ff0egIYzge2p4iPtiPOddBZqQWi/WfOwGSG', 7, NULL),
(5091, '445', '$2y$10$GHDAcjsD6aM5qN.f9Sde5eMPDMcc8iK3/YjhQDUImoM.Ukcymu6tG', 7, NULL),
(5092, '446', '$2y$10$CSyoFLWo5WXfpOOxOQ2lBezojM8VoTHqxy5b.X/KFxb2R1c1MD712', 7, NULL),
(5093, '447', '$2y$10$eVtJvM39iMjfUY6qoMgcZ./hK3g5mzmN5ywR7dYEGb2lycHiXtj4G', 7, NULL),
(5094, '448', '$2y$10$zUz8jZwCgmIwwXN.BrmMb.XRbsVzweg4iaImJCksyekAawRDa4eCC', 7, NULL),
(5095, '449', '$2y$10$al4lMVl4Mrb.90RkgHNgEeCILjqAdvuBHfLVOtmR1j6ZSpXiyTDoO', 7, NULL),
(5096, '450', '$2y$10$.QdxeJAZtIq0JLItbuootO32roSh0F/CbDAf9TwlekhAxE0/d3/Cq', 7, NULL),
(5097, '451', '$2y$10$0pFuyCt/FVPcyFUdKoioa.8C/4/Cqv7EQ75v01QRfWf3jkFGgWJpO', 7, NULL),
(5098, '452', '$2y$10$uEPQt/KMl5S3Ti7qxrYqie4gJhbEvkvlc7pvEa/DVjIiWuvH/uLSG', 7, NULL),
(5099, '453', '$2y$10$r3yiLdQQtEJiKYIONZQYBOlofkJ2id7ze4P/wWxEW.Z6PFTw9oGwK', 7, NULL),
(5100, '454', '$2y$10$/.GCroLGTL6VJW9/.S6LpuhkPVyz4Bzg5sNKF.zyTEXU9PSUECNCC', 7, NULL),
(5101, '455', '$2y$10$fe.oLwGRsy680JarfqssVeOAs.CjyCLCQnmSCbkyXg18h6ZHCcONm', 7, NULL),
(5102, '456', '$2y$10$htGXtEkZ3kDsXV3suISqneWhxYQNdF9s0OqNNC4RiwPXcDpCZ/liu', 7, NULL),
(5103, '457', '$2y$10$Oj.y0GFkHLNtLdOBCCyAsevkBYEMGlP58A0zF7fzOAnVBRFNW5eE.', 7, NULL),
(5104, '458', '$2y$10$cRak6eDZiJXMErutFFVyxunlujMzYoiO.8dgLzlgn.LPr8wDuEcfa', 7, NULL),
(5105, '459', '$2y$10$Xb2Z60/oDJxnkBo0gZ9TH.vO0iZctzptVGFrVSAWrOKBkkF680mTe', 7, NULL),
(5106, '460', '$2y$10$Ok2mftsHbSc2lt7KP0.9xeUxFZ9/l7I99X473SfYLx3nb0DqLxqVC', 7, NULL),
(5107, '461', '$2y$10$XVOqUP4MZoM9cVPvPuP4reZb23h4O25yP3Cmc2c6Nv7OvkcOC90TK', 7, NULL),
(5108, '462', '$2y$10$gYRVL1Yq90YzysXwqLElEuf0riyh8j30k0Ch0dCzCaex/rpnvAON.', 7, NULL),
(5109, '463', '$2y$10$JTVxfpUptk9uAdv7uVoj6uHkK5Eu046bx3TG6dtzPw1I3/aVnffSu', 7, NULL),
(5110, '464', '$2y$10$3k1y8I.lEXdhLENRwDijyu51c.F/HbCui1.Hlnr26QGKsgIhmFDGm', 7, NULL),
(5111, '465', '$2y$10$THkISSywj47./SUSnIJHU.zkDWiZsQX8F.2ehrSH4ExErisCMcgiK', 7, NULL),
(5112, '466', '$2y$10$ZdV8UX44QF5GcWuQ/r7y7udjlkhFF7EDYzpZZwDEozYYfJqvEjhoS', 7, NULL),
(5113, '467', '$2y$10$J1aTiK4wX7LXj.61HqoU7eFoKe32cWtxHtgYz3j/jTBV62TzZo72.', 7, NULL),
(5114, '468', '$2y$10$v7YTMz6Hn4GZ09mNabYReOyS1zyD8u.dcagIBtVZDNu4DM8GVKCl6', 7, NULL),
(5115, '469', '$2y$10$qd.wtPfCbWjX4lQ28SXRweK4EPHpnoSKYn9WNKN2Rg7McwOPHIy4C', 7, NULL),
(5116, '470', '$2y$10$zSY0rpMwrop38Tn307QvAOu9z28nS.zYLffMvYWYVXBdVT1FVqze.', 7, NULL),
(5117, '471', '$2y$10$pnKy1PgY.uNAf0HqgjrUDe8WQwqv12583BksjFAKrntK6uaJNm1wS', 7, NULL),
(5118, '472', '$2y$10$rtpyvkgjfhi5oe1v.Wer4uQcm4LIztQkHcWc2BJllB0Ow7QkoLGna', 7, NULL),
(5119, '473', '$2y$10$GPwdat0bGB82ZWLHGbiNe.WJ3W11p1M3INbyA1S95pttQoDDT2Ude', 7, NULL),
(5120, '474', '$2y$10$X2IvTti1yjUkJSKKcxMgfu.PsXi1t8xKSencHZjF.iv94jx7Q0IfW', 7, NULL),
(5121, '475', '$2y$10$x80HXYGNpa4R.dv1Z0KMEOp01OxRKMx37MIUvo9PJekTCfJsE3yzK', 7, NULL),
(5122, '476', '$2y$10$6oCo8e7SpSa9uJEq1zZ22.vdFINdp2wSHX46kZCPWAKtdTUPFfHRS', 7, NULL),
(5123, '477', '$2y$10$i2bxNDakpqhAMPNzfZps/uYvUJhjl7rBuNb99/XzjkrE2N8M2fkL6', 7, NULL),
(5124, '478', '$2y$10$rZHZRXZ/sO.WuxnmXBk2YuA/Z7OFWShQAgUs2euYs/7dsE2oiliWK', 7, NULL),
(5125, '479', '$2y$10$7S6uxtwV3SzlYrbPhx0dkeCHQRwbNwtMpK5G8qv7XY6YsdinQD/iG', 7, NULL),
(5126, '480', '$2y$10$3gvsCD20n24exTFCTwig3Odlwjl59oirwRurbIbAlY/xGFgaClIB.', 7, NULL),
(5127, '481', '$2y$10$n8armgW/CAb0zFN/lmFd3eISD2BpS9H.kHq1xmB0gfmOM3o2zPNyW', 7, NULL),
(5128, '482', '$2y$10$zLvMlnRjsRqP9lDxwVWkRuT5I2VdbHRUAM7C0kwhLLj9jCbuYjs.6', 7, NULL),
(5129, '483', '$2y$10$vhANQVlfjDosGzs57iR5aOWjQI7O.MYpVPKRrIgp8.aKzNH.H6GZ2', 7, NULL),
(5130, '484', '$2y$10$2aDHqjGSh/4ZGNS5hB4fCO4bGqNVwUpRpCfV9R2xBtTydw0wJfiWO', 7, NULL),
(5131, '485', '$2y$10$cHEU5RjfvkmbglSTJtBsP.gQaLU9pSqWk0tde9kDWyVLCfazstadq', 7, NULL),
(5132, '486', '$2y$10$ve1YceoVPLEACtfFWEJXvOhi6jWk00VXd9C8ApOXVamWg7b0VCY26', 7, NULL),
(5133, '487', '$2y$10$4vCpYkfJeAbY3zPOXL.PueKWK2sNJEdgDqssEGZQj94IUDU9EzeIu', 7, NULL),
(5134, '488', '$2y$10$kJ0ONmGUVc2Jst1uIsVUIOhnh4XR2wFxMe1GFsYnjD5qE8ZIFoPTK', 7, NULL),
(5135, '489', '$2y$10$OWiyY/ETNhuHBZw8MrMml.7e5NLJag4U247m3alRUZPRUj/GTSoMO', 7, NULL),
(5136, '490', '$2y$10$sYR9s1mP8Tx1Zd5e0NVb9OqaK0ynrZreF.byHWVYpGwoHaCn./IPK', 7, NULL),
(5137, '491', '$2y$10$npVqj9OHiwjBYWib9s58H.x22DQ5gfJYKacVHglI67wSqY3vY6Z3G', 7, NULL),
(5138, '492', '$2y$10$HCT5jmndP/.IKQNeFE8QFuxOSLS0KUQzLIRUuo8lzw8dQbhB60Ioe', 7, NULL),
(5139, '493', '$2y$10$Vab7PMH0Mu8oJU/c4Zd7NejjcO4.Oa7y84ETg4YecCtbpp1xCaImy', 7, NULL),
(5140, '494', '$2y$10$vXoHaD7.qSPyYaEhT6jTt.jsk5MbA0XqH7/uPs8XakcaSrewWa5I.', 7, NULL),
(5141, '495', '$2y$10$CqXi8IyulWw1TgVTP//6LuxKf0PfJ8maaxhGDo6DlNXFSR4Evyopq', 7, NULL),
(5142, '496', '$2y$10$hrIy8RylVacsWKpIvgtfJ.AuKfbPDppqwkLufrxi4PHMybNMN1YTO', 7, NULL),
(5143, '497', '$2y$10$gKLX4Xt.Cagt4J3dVzlvMuf8cqarlGb5GzgtErnhdHAwAAPo/A0ay', 7, NULL),
(5144, '498', '$2y$10$E1p6zv0vltv7VGdW5aFJN.DSjj1I6tHEQBq4nLBD.lv4q.Wl6bsG6', 7, NULL),
(5145, '499', '$2y$10$mK13L3jPFxoTHThLDMlBJuwO3FcoSfQHDcZx2/aKUJ6F4rI.eC/GG', 7, NULL),
(5146, '500', '$2y$10$KZul0Hzp77zwHJoEENjkgedMu5xrlvVEi8bdd.wZ12fgOG9uuvfpu', 7, NULL),
(5147, '501', '$2y$10$mLG/dusKakGLpNbj5tZMkuPjuUpPCFZBbRqRVfwzayCl3RnlUMA3G', 7, NULL),
(5148, '502', '$2y$10$Lx5JJg/nsZfPxmxDnbI01.7zaskfCxCfzOXY64zdBkxWGf3UQ5Hty', 7, NULL),
(5149, '503', '$2y$10$Tdk0YMa3Wjpp5tA6MruRq.TLrpzxdvuYBGRvgZpUhav91ongUHbLa', 7, NULL),
(5150, '504', '$2y$10$QdqqpLYuAmfQJDudturiDeQOuhSwEmmQ/DUxQ3pr.yypAHxkYIWoO', 7, NULL),
(5151, '505', '$2y$10$KFHJOpkWnQAwYLnY.3A1m.CVHOw9V0UL2plDfud.lyStvzB.1TF32', 7, NULL),
(5152, '506', '$2y$10$BLEkv0ZX9.0oDy6UFjm7kOmvmQRs.otbXxSNPdiQ2G5oASfwvyy/G', 7, NULL),
(5153, '507', '$2y$10$Cws5okbEK4HahVhx2VkShuz0YnrlYwBdIhHUDQYxz5bYdd2h2fez2', 7, NULL),
(5154, '508', '$2y$10$qN/M4RryJ6w8SRqLmRNcGOrBI.O5mlWUoqt9YplQ3bFsU9K2ZTzKK', 7, NULL),
(5155, '509', '$2y$10$udIY7hC38.HJHYZbQY5wNOwM1EO33FcVt/vA68lGIbghtlV5U2B9y', 7, NULL),
(5156, '510', '$2y$10$XjZ/2EHUrL/GzqnWQCgsYeeOMaeQzdIkb2niYScES8IUx0no2vgs6', 7, NULL),
(5157, '511', '$2y$10$1KttauIjnQutDshEYWxWe.X/aDlWA9aMlMQiZpGBnpzPDPtmYvGOC', 7, NULL),
(5158, '512', '$2y$10$x0EzM2Ttet0/zwHpLW5TOu1ttcioE76nZ1vyE/rHOErp.ViyzGa0y', 7, NULL),
(5159, '513', '$2y$10$x..QeKdbuW9eYxKlIkUjEuhUXVbv6/S6jvcXvaYexsZ3PwyMnvywO', 7, NULL),
(5160, '514', '$2y$10$hpc2O8PO15phvTu9UT/Rx.GW0PawLNnWonswutFIyD3wL6Gett9zC', 7, NULL),
(5161, '515', '$2y$10$iVNe7DZz5XNe.d78y7RqduPJjdHaLPRRFt8maDDmPemhTi0Tv0VXa', 7, NULL),
(5162, '516', '$2y$10$39Tzlu/dpl25oBTEP.jwn.SIe2XiieJQIR92tS6Se5BTDUl3SDEmu', 7, NULL),
(5163, '517', '$2y$10$mxQhq5qHe9zsO2G1aERMB.BTLFzI1pBGJhD.F/SmszNuWbVHUlmz.', 7, NULL),
(5164, '518', '$2y$10$tSGbJ/2owe6Ph86Om/ipROg18dxM9wYJPdoBEpKczUbDoIdSMHDfC', 7, NULL),
(5165, '519', '$2y$10$yoO6VP1M3UJJDFMMTpaWhecJmZt/RoWQh2g9jJ3N3VW5chWSF2KVu', 7, NULL),
(5166, '520', '$2y$10$PXvoyRwWIzjjTmJhpTVhW.oRhBHoSvN7ps7IOuSFofQ1pWGUssUBe', 7, NULL),
(5167, '521', '$2y$10$2q62SOXJt6wNpPGskyxSXO2zGu7Ty0tGax2ad5//6l3tm7xuhMYvO', 7, NULL),
(5168, '522', '$2y$10$gaVCTaDewg2/mT/uQu1zP.7ERlBTi7DfZusDv6Y9o7zNvuf.MCwva', 7, NULL),
(5169, '523', '$2y$10$zpTlONSrcI.6FpC8Vt6Dm.DOVFSo9HPnxQ3ZtWhR4upcLlmEOshwC', 7, NULL),
(5170, '524', '$2y$10$ctpZSGCR0PbmW7ImKN9aU.S0Hp53.faTNS0302ZuZmexHhY40lUYK', 7, NULL),
(5171, '525', '$2y$10$ntgr3l6SUiNG2XaLp22IR.vk9ZudOC6A2Vw9GocsAdj21UCQX9p1C', 7, NULL),
(5172, '526', '$2y$10$QdFduFY9ePdXZBEeCcaEG.NLRFlB.l4miTR9iMPjJzqsKWYADvmi2', 7, NULL),
(5173, '527', '$2y$10$S0ZykNjuo0.RZG/kvwK3Bu55fmKHDvlGchekY8S7MrzqzLpby68Ne', 7, NULL),
(5174, '528', '$2y$10$R6MDGDJEKwmtM2x/I81gyenNKjUG1z2ubDag94SClaTiA96GeEdwy', 7, NULL),
(5175, '529', '$2y$10$QsMr96TH1JN41cLQZzDmFeQwZcKzL1r1FUHN5Y1pUPGTL2D6FQSWa', 7, NULL),
(5176, '530', '$2y$10$iHHGMb/YiLJ6tvQzyaNAhuRxm7NGtxHPtsrk/iwzzA.n1VDI4uvDW', 7, NULL),
(5177, '531', '$2y$10$lO6ckAo9/RpHfVZHU8qZce8qtx1ipY3ubQy6Btz8OLmIZh/eIAA8W', 7, NULL),
(5178, '532', '$2y$10$z7dbG1TfjAnCCs..AekASeI830sLzEg1rs8.PlUqO6qWO/PkMz9Ty', 7, NULL),
(5179, '533', '$2y$10$Gz6Fe6DdTLkbbC3ec66M5Ox74K9PCZ8ZmQG/7AF0oH3eHRXauo2dC', 7, NULL),
(5180, '534', '$2y$10$6RPLm3Pw3C7ADjKhNoxjdeZAmgbg/sI9ayAGrD0EBbLwBE9DTQg86', 7, NULL),
(5181, '535', '$2y$10$QB9GSeGJoFFRgarI4VPBheL2/PMg87GHnJgABGancLOE1CZOSLzQy', 7, NULL),
(5182, '536', '$2y$10$Z692lpllS/sD/brUeWEKQ.C052rrXuvjz6hCRPb0LvI0pMZqaoqb.', 7, NULL),
(5183, '537', '$2y$10$XPTUUFG1sQ6WO6rTdeuwwuFZhb5RHCcmismv.yDyi4DVKTD3TzQQC', 7, NULL),
(5184, '538', '$2y$10$OZFOSeVoAjtykgRGwiLY0ewF/gii1le6Yk3o.7NhIGLhkvOPnSJ.u', 7, NULL),
(5185, '539', '$2y$10$/pZaZNNyUTZTq6NZrqM2I.hbMM33mXC9ypC2sxFkiaYsnGE.GmlQi', 7, NULL),
(5186, '540', '$2y$10$R7.WV52ITkx0XeWK2GplouHCpVP7Y6EGzB1Ffw4Iyf/obhz.y.L5q', 7, NULL),
(5187, '541', '$2y$10$CAiud7DYVCd7W5mUUPZ8B.SXx9R4faQAzb/qceJXWclCEufiCxNzm', 7, NULL),
(5188, '542', '$2y$10$I52FZZj4/I.PZVup2TLV5.2H6W.v/NDzGD4SNwqOMrDIIBvYQFF.O', 7, NULL),
(5189, '543', '$2y$10$ZmJrPcoUxuObfIuWGFJWVu.oupFjUVDEGPGN5B3.tGg8n4edYBMRG', 7, NULL),
(5190, '544', '$2y$10$3GCwLfPf4isBVT3oaUqxEOtpFHhzlX36126I/1NjDdTktL6kdge3a', 7, NULL),
(5191, '545', '$2y$10$kuZWGyb4S4QegobyHlmd8uItX3DDZmsvO7BDuPNGBUnxOclCS96SK', 7, NULL),
(5192, '546', '$2y$10$1sb9kZtmkm5ghKsv.2vfHOtszM4Hfd.lNaH.Rw3GvDCC/.ftOQQf6', 7, NULL),
(5193, '547', '$2y$10$PpDu8qiyYLZckN6578oy7udh.pSPqZRfGKZHSD4LvQYWoVZs8AhL2', 7, NULL),
(5194, '548', '$2y$10$IbhpS.q0KCEwuS5x3EvQeObhpNWFAZnjydt1WzpTvPV/gAnlgccRS', 7, NULL),
(5195, '549', '$2y$10$UjAgZ8daw8PWK6WxnLXOMuKcOYz7.M2ws7fqeSd/BHGcel4NWZo/W', 7, NULL),
(5196, '550', '$2y$10$JZrVprnTiAkYd7iymv0VM.kjRm5nhIE5QZI4ypvQQuMpDM3REnqOi', 7, NULL),
(5197, '551', '$2y$10$7kRAw2YSCsN40CLAupa8r.rbbE7ZQMOwqFWl/vh7Yb34CQ0vt5pkG', 7, NULL),
(5198, '552', '$2y$10$zuPYMdz.gRACm6McaU9Fke0v1jSr7BdaQAY.x4S.IZW7K9xF4NgYG', 7, NULL),
(5199, '553', '$2y$10$u3K4aIQnBriSFBmsDZcOo.nMt/OwkH5tc6J2R9U6GUa134ChQ4oJm', 7, NULL),
(5200, '554', '$2y$10$ZIB1w72rcE7OGQqaR5eSX.IR/qRGKgLbbmxFNR3KExU0BWHxV03.O', 7, NULL),
(5201, '555', '$2y$10$GiOGmWdClKVGXr5pSCJNfuKwB6Yq9YCfC/xZpE5K5mK4Q.JctMHeC', 7, NULL),
(5202, '556', '$2y$10$vaK1SIMQEWlaseq6mkTE7O0tVgqfZx/hAMz5RzbW1D0WVGsgoZTuO', 7, NULL),
(5203, '557', '$2y$10$k5BffN60lATK37ASjr7T3ueQyEHWVh.g6V2B0Ay.5BDGkU9CzXKAe', 7, NULL),
(5204, '558', '$2y$10$2rvUQYkQGkX5qAv0gFwtfuIeSgfbuiShMbpVC4FMjARTW9ncSCqwO', 7, NULL),
(5205, '559', '$2y$10$XJuIG.yOp4UqaD3ZQJ4yY.Ho9Qu6wx1ic9LabHK7E7gGiOq4yjAG.', 7, NULL),
(5206, '560', '$2y$10$yvK24U/yJqF8GPeOv4ur..PEEWJlGdsB/Cf4Hlt1GPwgkuMYV3oMy', 7, NULL),
(5207, '561', '$2y$10$rSvfoBNS1sE8xeCGifAJ1OvZ80wFN4s8MGNR/mmEyP8tTGVcviJDe', 7, NULL),
(5208, '562', '$2y$10$nf1UMck7TIWJtZxEfOMu7.Nc1detiHU3/KZyZx7UfBS.KWYhTZ2XW', 7, NULL),
(5209, '563', '$2y$10$ZdTjH6eGQspyTwyi7iEp0uNxhobm4DPbMKwy3BzANxPmMOYa3zHAS', 7, NULL),
(5210, '564', '$2y$10$bJKoluVALXCke7BwPZEhjOLJWKnQIFHvYqlnhHmfrX8ccdpicW4l2', 7, NULL),
(5211, '565', '$2y$10$fM4VyKkZ7Vcz8NiEyqDw1OGEy6YgjJk/pe1M8G0MyjAVcWf12qU/S', 7, NULL),
(5212, '566', '$2y$10$Bq48VgPfawN0nIqmIn1kr.2Pf58lg8h0/I0vR6Q1ZTiYqdbyFEkG2', 7, NULL),
(5213, '567', '$2y$10$8aBqnAS3n5NSOvN0ANb2He1JyWDcB0rN/HbqxO20muBIha5/5vMRq', 7, NULL),
(5214, '568', '$2y$10$bkKDzrBJJrIIVEDNq4XLMO2gkRKPxgC/RnfidAIQVb5zYGph86E1W', 7, NULL),
(5215, '569', '$2y$10$uP8hOONwkEEv.XnBcIMa8OsLE.1kI0yDbaacHXkg8YlAxw/lPlKRG', 7, NULL),
(5216, '570', '$2y$10$z5yDeQtGrVmqeA1i2p5zj.pyI86tueUzR9.jzUeUANYo/s0fv4L9O', 7, NULL),
(5217, '571', '$2y$10$m0GLal1K7W0hatkPv9ThXewgIUynSSOXfaaVZH5Hk/hoVDCMG9sQ.', 7, NULL),
(5218, '572', '$2y$10$eFP.GiePFOqRM5D9TawBbuwZNLshFFsGumoUIIB0Mcs1Ljiw3iSCG', 7, NULL),
(5219, '573', '$2y$10$zmJ6o/T5pL/aF2Xd46f63esS.rGQyh4leWrnoAVZxzgagz.l8mzae', 7, NULL),
(5220, '574', '$2y$10$pQjMoTLCUa5.8OnX3/U4leTLt3z3BOXIQhdYHj/7Vp3tFMsWBNwxq', 7, NULL),
(5221, '575', '$2y$10$nBKtPfxLDvkEBvnHulZh1eSZKHJbmp4/ov3wMT3Iy72xQCZjyo/jS', 7, NULL),
(5222, '576', '$2y$10$nU6P.61ISl/wB4qRpkjdJO2R4QprFSIJPVTr1zKPxxyr1YJdRcr5.', 7, NULL),
(5223, '577', '$2y$10$Veo6r2wHLh4rXY3RkwXr/OnIAGHUxAmd8GBPVjjGYh8mx.U0asPa2', 7, NULL),
(5224, '578', '$2y$10$b/pO8aJYnVebl9zpW9eKB.UtkpmipPos/ssnF67bn7NTSDbrhDIva', 7, NULL),
(5225, '579', '$2y$10$435MRWSZWJnpvPMFb6qPFunEXkYGME586jQKItwjZEwiqNUGo22Qq', 7, NULL),
(5226, '580', '$2y$10$ZCjV1XQbX0O1FOzBVDgqKua6YnYTwJO1lxBk3CNq8KbPfgFBKnPJi', 7, NULL),
(5227, '581', '$2y$10$jZACtu58zApoCF69tHSc.uWREl5r5/dh96ZjP8ZYas2JYQWRC5ST.', 7, NULL),
(5228, '582', '$2y$10$kyrl6Ic2vpG9OikUzgdC/.jnUHgcfL0WaER1MG3wVbr2FhZ.43ALK', 7, NULL),
(5229, '583', '$2y$10$JqtJN6O.SI8M8Bqk20.Zf.jR8u/Pldxq2em1RKtp/bjWqlbHWOEvy', 7, NULL),
(5230, '584', '$2y$10$b.g/Xim3C4dLp6irJMAq8eneYxdj021uKJdEmZw0KFv2QLlAtSjDq', 7, NULL),
(5231, '585', '$2y$10$YgkfotDBx4VSuOc0dvyVne7Dd3.RZbmcWJhR/63rgsGD9W03vUKgW', 7, NULL),
(5232, '586', '$2y$10$GxXmo2T3PHS9pzyGUZHdV.70XdgJ1/Za4bRIL8kOOwIluxAw.9piC', 7, NULL),
(5233, '587', '$2y$10$RbMaMJDXAhPM9Qio9IsG3uCTmzLkF.BCReZZwniJCq0VL3yAzMLvS', 7, NULL),
(5234, '588', '$2y$10$4N4gL6CEsNlGBtvWkrEQROo6SrWm75SxiuAnR1w8w6zb6O5aMbQpm', 7, NULL),
(5235, '589', '$2y$10$RLbD9tErqB6qO985loItYeViD.igOLd2Tnhfpj01M5aiF39T0GWRq', 7, NULL),
(5236, '590', '$2y$10$P.PmQ6ALZmrZmnAFjTQfLO8C54ccgTNxL/wIDFLJNgoLX9.vTy.86', 7, NULL),
(5237, '591', '$2y$10$BD2.3bdmU50vUck4hLxqteTv8Vj9RofEbBv3T.2wbqAFoy/KNBlWW', 7, NULL),
(5238, '592', '$2y$10$eyiXjbj7/xDiH/UiEDaKJ.8SJDRy1aCcNB1Ck7LhnLhHVf7L25qVm', 7, NULL),
(5239, '593', '$2y$10$Xlj56/ZrPH1.jqUiXsY/W.HtXVXSeITis2k6NheEZ//jTgmbevt2q', 7, NULL),
(5240, '594', '$2y$10$8eQuwSMIVm3Ark2/qNcLR.Gfn.4PhbuRw8cbNpigdIZrqV3U58zK.', 7, NULL),
(5241, '595', '$2y$10$.3xtL8d11GBlZpsKJnnoDOLZPqIpqEK2FelTRFagA9fec4jMLsCQe', 7, NULL),
(5242, '596', '$2y$10$V6MVkv.3qh2VuKO.jgQUm.jpGgrc8tka9hDxo7hwQ85PZjSfRb9/K', 7, NULL),
(5243, '597', '$2y$10$dicHLNtwrmlMOY0LH6NYiueQFqPFWSI7/xO/2vEpiMFQjuav0Eyc.', 7, NULL),
(5244, '598', '$2y$10$ISBGi5Ox5h2xA0yRWa/We.mFozTrOCY9wPLOCFASHSGSUTJv2KPp6', 7, NULL),
(5245, '599', '$2y$10$vuKDlViLFr9Y85VJlPsoW.WU6ib2WmIjUX.KOq1UU0Tuk9swYZppG', 7, NULL),
(5246, '600', '$2y$10$ZV/NWa5IUinL5b5kebRH/O9oUMGKbce5G/U9uAnqPw3daZdzC3oK.', 7, NULL),
(5247, '601', '$2y$10$t5JnmIkzjvb2p5UdylmQOOTBmuDK3JZIishuExS80uY/KiExMqIAO', 7, NULL),
(5248, '602', '$2y$10$JfMssBJBRoRcPST9VwamJueT8N3UWHl/wVDAfAkwgM8WL0H4qNrgm', 7, NULL),
(5249, '603', '$2y$10$6S8FgLLjg5kXaT96zVOimegcVP4lTErtkLYFRf.ottl2p1kIvDczi', 7, NULL),
(5250, '604', '$2y$10$IcdR.qPVgU1EdiwE.3WlbOINzIZyl3yzTj7Qf1KNRBDiXCF9uZPo6', 7, NULL),
(5251, '605', '$2y$10$YNX65lVKo8wWwWZVBUin/.i4563Z/153zjTpMIG4ZLnDY/3L/6tDy', 7, NULL),
(5252, '606', '$2y$10$NoXM5DaE4Txv.8RKMdCr3OaJKfo1mTs/6J/W.LbvNK5xaCLboWIpC', 7, NULL),
(5253, '607', '$2y$10$VdckPlVQqqrR0dVmkROOHOeBx9BIzijhCQeuqX5nydZoWJCc8eNly', 7, NULL),
(5254, '608', '$2y$10$2p6E6FwK1GxmRzjVrNo/ee8Tovdj2CfyoV95yaFFsLBQYMKLq2HnC', 7, NULL),
(5255, '609', '$2y$10$4E4j5Ix8UlM4hjAY17vTbev46UQn6sDfHzePv28W7a4sn0K13f0sa', 7, NULL),
(5256, '610', '$2y$10$igqYdiyjnkBKEq9abbTc/.acBbzjzNpZCcH7bAXw1VnUwEI2lfkLK', 7, NULL),
(5257, '611', '$2y$10$5oECFm0rHy1ui.D9CnxtAemPhe4/lGvjCB9ugLsO9SeUVUi.h3.Dm', 7, NULL),
(5258, '612', '$2y$10$sZYWEU0kJt/44dl7dz5P0u89Xu.eN34Z104jrf1C6B1MafCuf1kQC', 7, NULL),
(5259, '613', '$2y$10$5OtCjnPJ1NFSpdBa/ZIUx.EDenYXXl6sBAHuvalsybZHDU3dgXFuW', 7, NULL),
(5260, '614', '$2y$10$HiWpxbjt8QHKgmh9OATNhup1UncTzhdTy5NzXks5gd9oxFkykN/eS', 7, NULL),
(5261, '615', '$2y$10$4Tx.WYn1YTn/4kU/6X2dge5G1Yw2dU1LDdOxwAXNi7dd19fYThHwu', 7, NULL),
(5262, '616', '$2y$10$sDud11lu2/X17p822NI.oO5C7xqGX.izpDee9aNacXKJsvrvoPHq.', 7, NULL),
(5263, '617', '$2y$10$63nqNEq7lmZfdUE6dJRsBuPR/XPJpKVndUndW1S9oeuEQ/yS2oNEu', 7, NULL),
(5264, '618', '$2y$10$xBKz3e9GRb6yCHHTtdW61uaQxrVROtRM1IbGdaV2HyJ/7Vk.PF3wO', 7, NULL),
(5265, '619', '$2y$10$K794TUUOAKyoGG0vsOSYYunXNddzmAsV2CUGCtaLsx1OnwUkl0fm.', 7, NULL),
(5266, '620', '$2y$10$iw4FwQe.yAd8Rfs5TaoL1ud2J22XQVYAnhYedXe52epAs6tY/Ki9a', 7, NULL),
(5267, '621', '$2y$10$fOs7g88uKt6482P3NA9gdOXThsXa04oELzo.C4Z/3XetrH/JUtdsu', 7, NULL),
(5268, '622', '$2y$10$baApOpKFxO3o.bvnGO60Iu.fYQUftyvijau5DpmJceBA7Ebgy0xby', 7, NULL),
(5269, '623', '$2y$10$4lY1U6ltPAplLQsfgE9vM.f4Hy9caB2xggYAJSonXx.ZGYyEi4Mtu', 7, NULL),
(5270, '624', '$2y$10$kd4pAnBX67Ku15nNZ56i3utLj4RVnLt/Z0FwMVPSrrzN8wDrydC1C', 7, NULL),
(5271, '625', '$2y$10$84/u/OiOQ1zo4b1ZVfZGFuLwadDinn3eCLeyKm3fPCdF67wWHkohi', 7, NULL),
(5272, '626', '$2y$10$aJ8L//n./9a0RdGiMSudTuA.3.PKjd6AckrFx4ZrDoZmlPRWAhIrW', 7, NULL),
(5273, '627', '$2y$10$v/tkxqy6.7gFXExmlLHDdudLqCl8ia/NFryMi14nrZaLMUMlRdIWK', 7, NULL),
(5274, '628', '$2y$10$a5aVdWc5TiZukUyc6QOE/.phoy/C6zHTl5ZH9yvyeRMA1Kjp1hT9K', 7, NULL),
(5275, '629', '$2y$10$Pgno/0X6MOPGGt0FZ5K/dOrxOdnD1UcpERHvNvV841mASNhhArFQK', 7, NULL),
(5276, '630', '$2y$10$bcHKb5LthjpPgEj4qMBxLuwSY9nhLb1eZsr/Hep/j8GjKWacmZPc2', 7, NULL),
(5277, '631', '$2y$10$vht8hDKzE9wc2Jfrdc0R2eE3vTB5Y8hV7N09X861y6sjYH4oBM5y6', 7, NULL),
(5278, '632', '$2y$10$IRe7rsNc5rUNB7I5dhh/buk.byFUgSEYE00tFAS9lP26kw9RepCCm', 7, NULL),
(5279, '633', '$2y$10$YnQc6OxhlrwaWTOuj1hH1OWxMZXOTsvfMY.6pkCGyTM3P0YFAc.Qm', 7, NULL),
(5280, '634', '$2y$10$QUxDmUzirI7S/8mtCobzkOVDuhj3CsT3p6nEJStoeb3Ro1BHNxRoq', 7, NULL),
(5281, '635', '$2y$10$drE.5jMGa5E/1ukrCc4txu5Zr6.YGy4N4nLLZI4FuHNfURHpxg2Ci', 7, NULL),
(5282, '636', '$2y$10$edyOcqbK1cWGZlbnyKMKEeNtylJkJ8kt7LA.9K8XrU4vGBLVHd0JK', 7, NULL),
(5283, '637', '$2y$10$tygxRJt5.V0rJBaR3s1aG.jcid7F1IvKxrEkOoTyBCs8DvNAVq.ja', 7, NULL),
(5284, '638', '$2y$10$.9YM64V2P3W1djH8mxWRNOXAzWRmKcqSUYcEpwEmyGG2Am0GnC1kO', 7, NULL),
(5285, '639', '$2y$10$i/0i/Ya/j4DUeVLZPwfSLOQSnYAwIjtOZNlUJd2ww5rLq4Ur0kkp.', 7, NULL),
(5286, '640', '$2y$10$qiOP/LCS9kxDhgDb5Ni3HO0RivdPtDRF3G7PwAapMHh9bM4v/5G/S', 7, NULL),
(5287, '641', '$2y$10$x0qtvSFwsFyLqVMiMUYAK.lLLq4lJ5eOkntC7WE7CSixBzpTBSidm', 7, NULL),
(5288, '642', '$2y$10$F3EDlPm01Dh.NN8L1.k9U.HuGj9OMe4q9Gay1gVF8GjPEh4hTUpv6', 7, NULL),
(5289, '643', '$2y$10$UsAyPtl2UCITbmgepZaK8OYpViN3Wtdlo2cJxd.d31/x9nFP956eW', 7, NULL),
(5290, '644', '$2y$10$ivTyQE2KOOJLZT1CCY2Snu1YoCFadMQiVCKLxmXXT8NhYe8FxKvLG', 7, NULL),
(5291, '645', '$2y$10$.RsULW8.Tl5cnCo.eZL0aeO4ucKBQDgrrkDOTDLdC01oPr5bb7z3u', 7, NULL),
(5292, '646', '$2y$10$5RwVOf9F24WeLHiDklNZzO8o0tUjX9GOb9Idb1F.8h/KTyQSHiBTO', 7, NULL),
(5293, '647', '$2y$10$/xEJwaucBBwCN/9i9Bwdseo9oTyszyR79fExqbGUUxW/lQI/0inrG', 7, NULL),
(5294, '648', '$2y$10$vH89lctqBQ2xwFfzcMXEzOjstil9zcqmP11.lVqs6xw9H1nkgkoJC', 7, NULL),
(5295, '649', '$2y$10$aejT2D2QUSwFI7aT2xwVTut3vY.M4PHuhbxSC/kgWok2StNZMhZ36', 7, NULL),
(5296, '650', '$2y$10$01qirXuSwPcMPUZk.FLwC.ocm4ApryVzcz/9mCiyO3C8bCr7lyPK2', 7, NULL),
(5297, '651', '$2y$10$gC55WOj8VssOvP1AjT9dY.j1IJZVOxKKeeyOow93eIbxbUj2h/Ohi', 7, NULL),
(5298, '652', '$2y$10$6bvq5hE9TIFtmZ9mj/hrBe6C4CiwPQisXrpYWe2wMEYvil1oQvH9K', 7, NULL),
(5299, '653', '$2y$10$zEo/za9g0uN0uhYZmSu.7OhWAG4i0DdRtNMTmGVb97hyiYsgRBpGG', 7, NULL),
(5300, '654', '$2y$10$oewvbSlP8M4txdR0W9adJ.ynIwX3SNN7drOyNxx./bEklHZYj2mC2', 7, NULL),
(5301, '655', '$2y$10$loePNV9/p3t.wQzFcO7c7.59sSrZ373oLK3/AX4jZhIX1vCis0PAO', 7, NULL),
(5302, '656', '$2y$10$TyW8l0gAr3r3h70l5J6ARO/S96PBuXflb9R4KeM0csAAaZXkCsmsq', 7, NULL),
(5303, '657', '$2y$10$mFx93j7w1QgBaTQ3yKEKJ.yKNl5OylcdjmejeLvxDSRhqXl6P.vfW', 7, NULL),
(5304, '658', '$2y$10$xlB5jKt0UFIE6sRiLMuMluYDEcmffUMGqLtABA30VkZPveAQJpbAO', 7, NULL),
(5305, '659', '$2y$10$n6WJ7jIIHxKsLronCIoH9uOGRGZA3ciRTuq5v/Rwu2mbqYeQYJbFu', 7, NULL),
(5306, '660', '$2y$10$l39djS05QDzahkLcrMAUdOMkmJulAE4PHPfWIoafVlNuucJLhJGIy', 7, NULL),
(5307, '661', '$2y$10$Onj9VADUAEHJCzLUnn.d2ummJuZVX9YfomYMQZKTpnKeD9Htv2ytq', 7, NULL),
(5308, '662', '$2y$10$2UwwMUCwzIpk/ZX5HY1KTuzcoeAPMMj1VU5KdvYq80ctBAIQxjjnG', 7, NULL),
(5309, '663', '$2y$10$XWPDFQy9.sJM9KlgX9Sese47C1cppwupu0WmaCnMUZQi0nEuDVFJC', 7, NULL),
(5310, '664', '$2y$10$ENhSP6Bl9U4/SRJu/UjaJOQc6PgmQh5vFUVgTSjZgzAZJ1MzcXfcq', 7, NULL),
(5311, '665', '$2y$10$.LkY8kpHWMOm8IsbHWO2Y.5BVgDGRJC8CJ6WVrognm3kQm75IztjK', 7, NULL),
(5312, '666', '$2y$10$jSidLoKyFxlXJAcusPrncOT1IdCPTiM8cCOAeoUcdaHafPX7IC63a', 7, NULL),
(5313, '667', '$2y$10$Vuk8nMpmnxgX9cRAgttWIe2WlykfzMv4NXVQkK1RyruJRvkQJ0A7O', 7, NULL),
(5314, '668', '$2y$10$u1x6yGa.vjHWd/UaH/fxPOi9Z0pUtLOrWjkMJXFU9gG8PlP0q3PSC', 7, NULL),
(5315, '669', '$2y$10$2lKqF8dNwZe.Wof8euwdAegpF4Aa40h.WV9oV.INIHCmAKwDYHlRy', 7, NULL),
(5316, '670', '$2y$10$vzFOjdb.I3rK5yusKlpQrugjIpXQlNIKNgCBSzzy.Tjv0OWd5ENIO', 7, NULL),
(5317, '671', '$2y$10$imiNoNXkTR7tp.ULFg/b8uU3Iw8IRKgY3LpHfLJYl67VXb9q8B8vW', 7, NULL),
(5318, '672', '$2y$10$RVZVoh00jm8IyL0iQBiNXep8JVxr9qwnNU2Fi4PEvuYyKK1l7/aTO', 7, NULL),
(5319, '673', '$2y$10$.u2zTQQn3bIrY6ft12RAWu0Mk9aKdbdMJtCE8SOpkefw1u/cKHBfK', 7, NULL),
(5320, '674', '$2y$10$yuFKjaqB8jpDEhYWzYv32eb4n2.I6OeYDwLgZSuw42MGnu54Riy0q', 7, NULL),
(5321, '675', '$2y$10$KZPT4ssxCLydE2ifVlFR3.Wi4xBIbbaW6B6NFxsdYk1esxXEl7fwW', 7, NULL),
(5322, '676', '$2y$10$xsPde/9pTabMjJUk6JwrieTRxVTotGU4Jbek6did1OzNF7rBBogbi', 7, NULL),
(5323, '677', '$2y$10$Ygf6ohXI86mTGnuPKUN6ROCSgMmZX.tu3ieXvuV6WZ4.4IWXuG.gW', 7, NULL),
(5324, '678', '$2y$10$uA/HzJs/mzVVUX3.IXqLx.0wXOO/a3Lku1oH/5/mqXdRhZ8P7xkGG', 7, NULL),
(5325, '679', '$2y$10$2uEN20lVqYTpGnTHMshGsuTlNuUtApcoIe/BUDpKA6vbwvIMleDnm', 7, NULL),
(5326, '680', '$2y$10$r1JaximsJ0K/NiByYbI7Z.k0C/SEo1fQ3jJeB9RrvtVN7Ry8JUlTy', 7, NULL),
(5327, '681', '$2y$10$cT3SIDzqPyV//phnUgXTyuf/lHqmNvAu.wIbSOasEHVDbP5NxNl8S', 7, NULL),
(5328, '682', '$2y$10$339fIw2nGZ7OkUEEX6.EQeGx0cJ4cuw80G6AzhZQ4AwJuJpq5SUwC', 7, NULL),
(5329, '683', '$2y$10$FjHSJa97jJzYBZE.q58mV.aGKQQESEH6..gJPkdHUaTuED4ZYVz1m', 7, NULL),
(5330, '684', '$2y$10$Pfdi4RJPkoVLVZQ6jKHfhOXw78bvlqCQK2UeOmyqIUZU40V8kKMjy', 7, NULL),
(5331, '685', '$2y$10$tYhXgVlrCQw2X/ngyg/4qeOkSDurpjfzI4l1IBCAb4NjMXeDIp7gK', 7, NULL),
(5332, '686', '$2y$10$Um3jAz0l.LNCaK.kUCXWLeNDDeNZQboU7ISbMJGGsjv9XkzKoi0f.', 7, NULL),
(5333, '687', '$2y$10$AY0ZVeKrOez/PhEwCE/t0O.QYVbrNfWlFD4unbzIzzVFauP5Cn3Vq', 7, NULL),
(5334, '688', '$2y$10$gbw99NH4zsdnY1QTZLFqn.0wmzMmukk3rum7DEkoGnlm.U/JGuxM2', 7, NULL),
(5335, '689', '$2y$10$QlEmcnpsu/V8kfeYuTUC5uwm7Ye26yYyOjqwUbXkBvZED/uoeqC3W', 7, NULL),
(5336, '690', '$2y$10$SkOY0Wg6fygI40e5IQcooewdDryDmgO/5l.qpH1Sv.T8l4ja0oF16', 7, NULL),
(5337, '691', '$2y$10$KruDvsh4Z82qiKbqx362l.5E8jkn9ggDPEgFjQThY4/wZFoL5KcuK', 7, NULL),
(5338, '692', '$2y$10$qdY4/J9ndpGL/sOvwhHBJe1Cv.jmJD5KhYrkc0jMAPlc5kDlN7GHa', 7, NULL),
(5339, '693', '$2y$10$Jb2ai7fL02ZECvNkA0GRk.uh3MChSFDdQDLN5wBMNAqh7S0D.vEFK', 7, NULL),
(5340, '694', '$2y$10$6VrfEff8Wb4VsR7h2xOPpug2Q80BzqhFP5Toec6w.1XUQnr2Q4sKS', 7, NULL),
(5341, '695', '$2y$10$EAdbDP9gKrnG0sUmzWaNieazGEYmfkTd6EiSm/7yPSVdLUkiKWg.W', 7, NULL),
(5342, '696', '$2y$10$Jgf08f/DlE0xVNlB86PH/O8Nv99iBYOuCHke4LT.jRuSSjAiEykxG', 7, NULL),
(5343, '697', '$2y$10$RyxTBisdYe2Lf87uF3v0K.ThxgMYP7u.3qii793nyKp8ZQvmFxOJy', 7, NULL),
(5344, '698', '$2y$10$VvzApOCxgyZzGwsiN5HdIOEAQYMA0m70JVvI771yslTUAYCR7qHn2', 7, NULL),
(5345, '699', '$2y$10$b12loYfFuSTulO5F8gXVK.L5eRSO76boweYlEQvt55rpY0fdyB.eO', 7, NULL),
(5346, '700', '$2y$10$AgHsTKo689f3XwRauom/3uMjea43ODQtEv.UZYudnANrha0qjolIq', 7, NULL),
(5347, '701', '$2y$10$2vJ93a./zSN5fbehulo7BeiFQDBUFNJNLGp1CH2tNdk3DwgB2OH6y', 7, NULL),
(5348, '702', '$2y$10$zAt6wgAzjDRtIN.cXv2vW.rlG3yQ9b.1h1KS98b2whwMN.oGd/iai', 7, NULL),
(5349, '703', '$2y$10$AAhfChWd7LrOQTcbBvLky.tlRdr8kHvZ7utj0figb55Rq4CWQGq.6', 7, NULL),
(5350, '704', '$2y$10$7xhTJyqe03I3igIDEgTvT.V/j5PA6.pOKs7fnqIRvcq0uVVwd/PB6', 7, NULL),
(5351, '705', '$2y$10$xdQerLHmhb0nHFDUnNs5AurLFOJPpxk9YNsKR8nuuyfC28u2nAa1y', 7, NULL),
(5352, '706', '$2y$10$JBzH6m7QEgs3ehJhr2GsE.C/kNDBdUqlemJCQmtHJjy31CvwMoaKS', 7, NULL),
(5353, '707', '$2y$10$g3L6Ow9IVPNTYR/3dXfDSOuXzDFdavGxcckiKmEJJu5KgtbSmWjnm', 7, NULL),
(5354, '708', '$2y$10$0HQX5YKCcDypQf8kPRMgiOehtbaBustj84CC1bqlyhZ2mCMmTpvbG', 7, NULL),
(5355, '709', '$2y$10$jwKIE4IpZJW4jmugLw5DqeJvqiLLWNpjK0MfTV1cd.X2yeCPjsqki', 7, NULL),
(5356, '197007042002122002', '$2y$10$mXxXmabAahJcTIAXE47ekOUKmdIcAfMqo8A/Y3gkFRpJVC0JvM6a6', 3, NULL),
(5357, '197112122003121003', '$2y$10$QHjyoZc3.SsMOG7ox7Q5z.Ci3y0c8dAqKcd8.NvPzt72X2PvWSF.K', 3, NULL),
(5358, '197405201993032003', '$2y$10$c7mYq.5AcXH1G3drpdilE.1NAxw6uNMt29hQWoKQAthXDyABKZoGy', 3, NULL),
(5359, '197104211999032002', '$2y$10$2wSfSs/z2STm.u2HacS7U.EjDtsJVfAXzIXmmJRczqdOGHvHGvj/C', 3, NULL),
(5360, '198111072009122001', '$2y$10$8n20SIsw5NjZGUwOS6pNUu8ccpGJAmWNCi6eROm568pPaVyv6SY9e', 3, NULL),
(5361, '198706032018012001', '$2y$10$ezN07jQSbOXhuRGtOjO8MeJi70YYgNIwi9Fmi0ujMQmys1W7eT1TW', 3, NULL),
(5362, '197101141993032002', '$2y$10$sdXCnkew1j3FDhhia6sMbe4sRl7PUZKjKJk7E3AijPERP3Jgbz8jK', 3, NULL),
(5363, '197104161993101001', '$2y$10$x0I0LpC543/YBkQ3q0wTK.TEwCw9jxQzcb95AIdvkKuw/CdCxaAmq', 3, NULL),
(5364, '198104062006042004', '$2y$10$6namQ5Rv/FVJEO/M8ZAi6.n.yEmiKFhDM8EIfFkqR39KRG.w2wQEW', 3, NULL),
(5365, '198606242009122002', '$2y$10$vxQkUt4VbNJfbRl9rg06FO4WGeiKGdJuciYhHkddwC6mJ5r/5WuSu', 3, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `angkatan`
--
ALTER TABLE `angkatan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `batalyon`
--
ALTER TABLE `batalyon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_batalyon_danyon` (`danyon_id`);

--
-- Indexes for table `jadwal_ujian`
--
ALTER TABLE `jadwal_ujian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_jadwal_mapel` (`mata_pelajaran_id`),
  ADD KEY `fk_jadwal_angkatan` (`angkatan_id`),
  ADD KEY `fk_jadwal_pegawai` (`pembuat_soal_id`),
  ADD KEY `fk_jadwal_kelas_ujian` (`kelas_ujian_id`);

--
-- Indexes for table `jawaban_siswa`
--
ALTER TABLE `jawaban_siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_soal_siswa` (`soal_id`,`siswa_id`),
  ADD KEY `fk_jawaban_siswa_siswa` (`siswa_id`),
  ADD KEY `fk_jawaban_siswa_gadik` (`dinilai_by`);

--
-- Indexes for table `jawaban_siswa_nilai_detail`
--
ALTER TABLE `jawaban_siswa_nilai_detail`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_jawaban_rubrik` (`jawaban_siswa_id`,`rubrik_id`),
  ADD KEY `fk_detail_rubrik` (`rubrik_id`);

--
-- Indexes for table `kelas_ujian`
--
ALTER TABLE `kelas_ujian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kelas_penguji` (`penguji_id`);

--
-- Indexes for table `kelas_ujian_peserta`
--
ALTER TABLE `kelas_ujian_peserta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kup_kelas` (`kelas_ujian_id`),
  ADD KEY `fk_kup_siswa` (`siswa_id`);

--
-- Indexes for table `kompi`
--
ALTER TABLE `kompi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kompi_to_batalyon` (`batalyon_id`),
  ADD KEY `fk_kompi_danki` (`danki_id`);

--
-- Indexes for table `laporan_monitoring_detail`
--
ALTER TABLE `laporan_monitoring_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_laporan_periode` (`periode_id`);

--
-- Indexes for table `mata_pelajaran`
--
ALTER TABLE `mata_pelajaran`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `materi_perdupsis`
--
ALTER TABLE `materi_perdupsis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `materi_sosiometri`
--
ALTER TABLE `materi_sosiometri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monitoring_bidang`
--
ALTER TABLE `monitoring_bidang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monitoring_hasil`
--
ALTER TABLE `monitoring_hasil`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_monitoring` (`periode_id`,`siswa_id`,`indikator_id`),
  ADD KEY `fk_monitoring_siswa` (`siswa_id`),
  ADD KEY `fk_monitoring_indikator` (`indikator_id`);

--
-- Indexes for table `monitoring_indikator`
--
ALTER TABLE `monitoring_indikator`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_monitoring_bidang` (`bidang_id`);

--
-- Indexes for table `monitoring_pengesahan`
--
ALTER TABLE `monitoring_pengesahan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pengesahan_periode` (`periode_id`),
  ADD KEY `fk_pengesahan_pleton` (`pleton_id`);

--
-- Indexes for table `monitoring_periode`
--
ALTER TABLE `monitoring_periode`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_monitoring_periode_angkatan` (`angkatan_id`);

--
-- Indexes for table `nilai_ujian`
--
ALTER TABLE `nilai_ujian`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pangkat`
--
ALTER TABLE `pangkat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_user_id_foreign` (`user_id`);

--
-- Indexes for table `penilaian_mental`
--
ALTER TABLE `penilaian_mental`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `angkatan_id` (`angkatan_id`);

--
-- Indexes for table `pleton`
--
ALTER TABLE `pleton`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pleton_kompi_id_foreign` (`kompi_id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profiles_user_id_foreign` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_user_id_foreign` (`user_id`),
  ADD KEY `siswa_pleton_id_foreign` (`pleton_id`);

--
-- Indexes for table `siswa_mapel`
--
ALTER TABLE `siswa_mapel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_mapel_profile_id_foreign` (`profile_id`),
  ADD KEY `siswa_mapel_mapel_id_foreign` (`mapel_id`);

--
-- Indexes for table `soal`
--
ALTER TABLE `soal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_soal_jadwal` (`jadwal_ujian_id`);

--
-- Indexes for table `soal_obe`
--
ALTER TABLE `soal_obe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_soal_obe_mapel` (`mapel_id`),
  ADD KEY `fk_soal_obe_pembuat` (`created_by`);

--
-- Indexes for table `soal_obe_rubrik`
--
ALTER TABLE `soal_obe_rubrik`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rubrik_soal` (`soal_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `users_role_id_foreign` (`role_id`),
  ADD KEY `fk_users_pegawai` (`pegawai_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `angkatan`
--
ALTER TABLE `angkatan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `batalyon`
--
ALTER TABLE `batalyon`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `jadwal_ujian`
--
ALTER TABLE `jadwal_ujian`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `jawaban_siswa`
--
ALTER TABLE `jawaban_siswa`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `jawaban_siswa_nilai_detail`
--
ALTER TABLE `jawaban_siswa_nilai_detail`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kelas_ujian`
--
ALTER TABLE `kelas_ujian`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `kelas_ujian_peserta`
--
ALTER TABLE `kelas_ujian_peserta`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8333;

--
-- AUTO_INCREMENT for table `kompi`
--
ALTER TABLE `kompi`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `laporan_monitoring_detail`
--
ALTER TABLE `laporan_monitoring_detail`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `mata_pelajaran`
--
ALTER TABLE `mata_pelajaran`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `materi_perdupsis`
--
ALTER TABLE `materi_perdupsis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `materi_sosiometri`
--
ALTER TABLE `materi_sosiometri`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `monitoring_bidang`
--
ALTER TABLE `monitoring_bidang`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monitoring_hasil`
--
ALTER TABLE `monitoring_hasil`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monitoring_indikator`
--
ALTER TABLE `monitoring_indikator`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monitoring_pengesahan`
--
ALTER TABLE `monitoring_pengesahan`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monitoring_periode`
--
ALTER TABLE `monitoring_periode`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `nilai_ujian`
--
ALTER TABLE `nilai_ujian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pangkat`
--
ALTER TABLE `pangkat`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=810;

--
-- AUTO_INCREMENT for table `penilaian_mental`
--
ALTER TABLE `penilaian_mental`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pleton`
--
ALTER TABLE `pleton`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4520;

--
-- AUTO_INCREMENT for table `siswa_mapel`
--
ALTER TABLE `siswa_mapel`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soal`
--
ALTER TABLE `soal`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soal_obe`
--
ALTER TABLE `soal_obe`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `soal_obe_rubrik`
--
ALTER TABLE `soal_obe_rubrik`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5366;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `batalyon`
--
ALTER TABLE `batalyon`
  ADD CONSTRAINT `fk_batalyon_danyon` FOREIGN KEY (`danyon_id`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `jadwal_ujian`
--
ALTER TABLE `jadwal_ujian`
  ADD CONSTRAINT `fk_jadwal_angkatan` FOREIGN KEY (`angkatan_id`) REFERENCES `angkatan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jadwal_kelas_ujian` FOREIGN KEY (`kelas_ujian_id`) REFERENCES `kelas_ujian` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jadwal_mapel` FOREIGN KEY (`mata_pelajaran_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jadwal_pegawai` FOREIGN KEY (`pembuat_soal_id`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `jawaban_siswa`
--
ALTER TABLE `jawaban_siswa`
  ADD CONSTRAINT `fk_jawaban_siswa_gadik` FOREIGN KEY (`dinilai_by`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jawaban_siswa_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jawaban_siswa_soal` FOREIGN KEY (`soal_id`) REFERENCES `soal_obe` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `jawaban_siswa_nilai_detail`
--
ALTER TABLE `jawaban_siswa_nilai_detail`
  ADD CONSTRAINT `fk_detail_jawaban` FOREIGN KEY (`jawaban_siswa_id`) REFERENCES `jawaban_siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_rubrik` FOREIGN KEY (`rubrik_id`) REFERENCES `soal_obe_rubrik` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kelas_ujian`
--
ALTER TABLE `kelas_ujian`
  ADD CONSTRAINT `fk_kelas_penguji` FOREIGN KEY (`penguji_id`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kelas_ujian_peserta`
--
ALTER TABLE `kelas_ujian_peserta`
  ADD CONSTRAINT `fk_kup_kelas` FOREIGN KEY (`kelas_ujian_id`) REFERENCES `kelas_ujian` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_kup_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kompi`
--
ALTER TABLE `kompi`
  ADD CONSTRAINT `fk_kompi_batalyon` FOREIGN KEY (`batalyon_id`) REFERENCES `batalyon` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_kompi_danki` FOREIGN KEY (`danki_id`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_kompi_to_batalyon` FOREIGN KEY (`batalyon_id`) REFERENCES `batalyon` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `laporan_monitoring_detail`
--
ALTER TABLE `laporan_monitoring_detail`
  ADD CONSTRAINT `fk_laporan_periode` FOREIGN KEY (`periode_id`) REFERENCES `monitoring_periode` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `monitoring_hasil`
--
ALTER TABLE `monitoring_hasil`
  ADD CONSTRAINT `fk_monitoring_indikator` FOREIGN KEY (`indikator_id`) REFERENCES `monitoring_indikator` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_monitoring_periode` FOREIGN KEY (`periode_id`) REFERENCES `monitoring_periode` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_monitoring_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `monitoring_indikator`
--
ALTER TABLE `monitoring_indikator`
  ADD CONSTRAINT `fk_monitoring_bidang` FOREIGN KEY (`bidang_id`) REFERENCES `monitoring_bidang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `monitoring_pengesahan`
--
ALTER TABLE `monitoring_pengesahan`
  ADD CONSTRAINT `fk_mon_pengesahan_periode` FOREIGN KEY (`periode_id`) REFERENCES `monitoring_periode` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mon_pengesahan_pleton` FOREIGN KEY (`pleton_id`) REFERENCES `pleton` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pengesahan_periode` FOREIGN KEY (`periode_id`) REFERENCES `monitoring_periode` (`id`),
  ADD CONSTRAINT `fk_pengesahan_pleton` FOREIGN KEY (`pleton_id`) REFERENCES `pleton` (`id`);

--
-- Constraints for table `monitoring_periode`
--
ALTER TABLE `monitoring_periode`
  ADD CONSTRAINT `fk_monitoring_periode_angkatan` FOREIGN KEY (`angkatan_id`) REFERENCES `angkatan` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD CONSTRAINT `pegawai_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `penilaian_mental`
--
ALTER TABLE `penilaian_mental`
  ADD CONSTRAINT `penilaian_mental_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penilaian_mental_ibfk_2` FOREIGN KEY (`angkatan_id`) REFERENCES `angkatan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pleton`
--
ALTER TABLE `pleton`
  ADD CONSTRAINT `pleton_kompi_id_foreign` FOREIGN KEY (`kompi_id`) REFERENCES `kompi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_pleton_id_foreign` FOREIGN KEY (`pleton_id`) REFERENCES `pleton` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  ADD CONSTRAINT `siswa_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `siswa_mapel`
--
ALTER TABLE `siswa_mapel`
  ADD CONSTRAINT `siswa_mapel_mapel_id_foreign` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `siswa_mapel_profile_id_foreign` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `soal`
--
ALTER TABLE `soal`
  ADD CONSTRAINT `fk_soal_jadwal` FOREIGN KEY (`jadwal_ujian_id`) REFERENCES `jadwal_ujian` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `soal_obe`
--
ALTER TABLE `soal_obe`
  ADD CONSTRAINT `fk_soal_obe_pembuat` FOREIGN KEY (`created_by`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `soal_obe_rubrik`
--
ALTER TABLE `soal_obe_rubrik`
  ADD CONSTRAINT `fk_rubrik_soal` FOREIGN KEY (`soal_id`) REFERENCES `soal_obe` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
