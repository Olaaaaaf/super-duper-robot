-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql306.infinityfree.com
-- Generation Time: May 29, 2025 at 05:44 AM
-- Server version: 10.6.19-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_38965406_absensi_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id_absensi` int(11) NOT NULL,
  `id_pegawai` int(11) DEFAULT NULL,
  `nip` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `keterangan` enum('Hadir','Alpha','Izin','Sakit','Cuti','Dinas Luar') NOT NULL,
  `catatan` text DEFAULT NULL,
  `lampiran_izin_sakit` varchar(255) DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL,
  `status_izin` enum('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu',
  `timestamp_catat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id_absensi`, `id_pegawai`, `nip`, `tanggal`, `jam_masuk`, `jam_pulang`, `keterangan`, `catatan`, `lampiran_izin_sakit`, `catatan_admin`, `status_izin`, `timestamp_catat`) VALUES
(1, NULL, '198001012005011001', '2025-05-02', '07:55:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(2, NULL, '198001012005011001', '2025-05-05', '07:58:00', '16:05:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(3, NULL, '198001012005011001', '2025-05-06', '08:00:00', '16:10:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(4, NULL, '198001012005011001', '2025-05-07', '08:05:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(5, NULL, '198001012005011001', '2025-05-08', '08:30:00', '16:15:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(6, NULL, '198001012005011001', '2025-05-09', NULL, NULL, 'Izin', NULL, 'izin_budi_090525.pdf', NULL, 'Disetujui', '2025-05-24 15:45:29'),
(7, NULL, '198001012005011001', '2025-05-12', '07:50:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(8, NULL, '198001012005011001', '2025-05-13', '07:59:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(9, NULL, '198001012005011001', '2025-05-14', NULL, NULL, 'Dinas Luar', NULL, 'spd_budi_140525.pdf', NULL, 'Disetujui', '2025-05-24 15:45:29'),
(10, NULL, '198001012005011001', '2025-05-16', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(11, NULL, '198001012005011001', '2025-05-19', '07:57:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(12, NULL, '198001012005011001', '2025-05-20', '08:10:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(13, NULL, '198001012005011001', '2025-05-21', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(14, NULL, '198001012005011001', '2025-05-22', '07:59:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(15, NULL, '198001012005011001', '2025-05-23', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(31, NULL, '199010202015031003', '2025-05-02', '07:50:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(32, NULL, '199010202015031003', '2025-05-05', '07:55:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(33, NULL, '199010202015031003', '2025-05-06', '07:58:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(34, NULL, '199010202015031003', '2025-05-07', NULL, NULL, 'Cuti', NULL, 'cuti_rudi_070525.pdf', NULL, 'Disetujui', '2025-05-24 15:45:29'),
(35, NULL, '199010202015031003', '2025-05-08', NULL, NULL, 'Cuti', NULL, 'cuti_rudi_080525.pdf', NULL, 'Disetujui', '2025-05-24 15:45:29'),
(36, NULL, '199010202015031003', '2025-05-09', NULL, NULL, 'Sakit', NULL, 'surat_rudi_090525.pdf', NULL, 'Disetujui', '2025-05-24 15:45:29'),
(37, NULL, '199010202015031003', '2025-05-12', '07:59:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(38, NULL, '199010202015031003', '2025-05-13', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(39, NULL, '199010202015031003', '2025-05-14', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(40, NULL, '199010202015031003', '2025-05-16', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(41, NULL, '199010202015031003', '2025-05-19', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(42, NULL, '199010202015031003', '2025-05-20', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(43, NULL, '199010202015031003', '2025-05-21', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(44, NULL, '199010202015031003', '2025-05-22', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(45, NULL, '199010202015031003', '2025-05-23', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(46, NULL, '199203252017042004', '2025-05-02', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(47, NULL, '199203252017042004', '2025-05-05', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(48, NULL, '199203252017042004', '2025-05-06', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(49, NULL, '199203252017042004', '2025-05-07', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(50, NULL, '199203252017042004', '2025-05-08', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(51, NULL, '199203252017042004', '2025-05-09', NULL, NULL, 'Izin', NULL, 'izin_dewi_090525.pdf', NULL, 'Ditolak', '2025-05-24 15:45:29'),
(52, NULL, '199203252017042004', '2025-05-12', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(53, NULL, '199203252017042004', '2025-05-13', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(54, NULL, '199203252017042004', '2025-05-14', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(55, NULL, '199203252017042004', '2025-05-16', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(56, NULL, '199203252017042004', '2025-05-19', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(57, NULL, '199203252017042004', '2025-05-20', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(58, NULL, '199203252017042004', '2025-05-21', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(59, NULL, '199203252017042004', '2025-05-22', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(60, NULL, '199203252017042004', '2025-05-23', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(61, NULL, '197511302000011005', '2025-05-02', '07:45:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(62, NULL, '197511302000011005', '2025-05-05', '07:40:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(63, NULL, '197511302000011005', '2025-05-06', '07:30:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(64, NULL, '197511302000011005', '2025-05-07', '07:45:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(65, NULL, '197511302000011005', '2025-05-08', NULL, NULL, 'Dinas Luar', NULL, 'spd_joko_080525.pdf', NULL, 'Disetujui', '2025-05-24 15:45:29'),
(66, NULL, '197511302000011005', '2025-05-09', NULL, NULL, 'Dinas Luar', NULL, 'spd_joko_090525.pdf', NULL, 'Disetujui', '2025-05-24 15:45:29'),
(67, NULL, '197511302000011005', '2025-05-12', '07:45:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(68, NULL, '197511302000011005', '2025-05-13', '07:45:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(69, NULL, '197511302000011005', '2025-05-14', '07:45:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(70, NULL, '197511302000011005', '2025-05-16', '07:45:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(71, NULL, '197511302000011005', '2025-05-19', '07:45:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(72, NULL, '197511302000011005', '2025-05-20', '07:45:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(73, NULL, '197511302000011005', '2025-05-21', '07:45:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(74, NULL, '197511302000011005', '2025-05-22', '07:45:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(75, NULL, '197511302000011005', '2025-05-23', '07:45:00', '17:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(76, NULL, '199507072020061006', '2025-05-02', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(77, NULL, '199507072020061006', '2025-05-05', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(78, NULL, '199507072020061006', '2025-05-06', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(79, NULL, '199507072020061006', '2025-05-07', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(80, NULL, '199507072020061006', '2025-05-08', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(81, NULL, '199507072020061006', '2025-05-09', NULL, NULL, 'Cuti', NULL, 'cuti_putri_090525.pdf', NULL, 'Ditolak', '2025-05-24 15:45:29'),
(82, NULL, '199507072020061006', '2025-05-12', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(83, NULL, '199507072020061006', '2025-05-13', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(84, NULL, '199507072020061006', '2025-05-14', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(85, NULL, '199507072020061006', '2025-05-16', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(86, NULL, '199507072020061006', '2025-05-19', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(87, NULL, '199507072020061006', '2025-05-20', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(88, NULL, '199507072020061006', '2025-05-21', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(89, NULL, '199507072020061006', '2025-05-22', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(90, NULL, '199507072020061006', '2025-05-23', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(91, NULL, '198802022012072007', '2025-05-02', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(92, NULL, '198802022012072007', '2025-05-05', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(93, NULL, '198802022012072007', '2025-05-06', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(94, NULL, '198802022012072007', '2025-05-07', NULL, NULL, 'Alpha', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(95, NULL, '198802022012072007', '2025-05-08', NULL, NULL, 'Sakit', NULL, 'surat_agus_080525.pdf', NULL, 'Disetujui', '2025-05-24 15:45:29'),
(96, NULL, '198802022012072007', '2025-05-09', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(97, NULL, '198802022012072007', '2025-05-12', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(98, NULL, '198802022012072007', '2025-05-13', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(99, NULL, '198802022012072007', '2025-05-14', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(100, NULL, '198802022012072007', '2025-05-16', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(101, NULL, '198802022012072007', '2025-05-19', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(102, NULL, '198802022012072007', '2025-05-20', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(103, NULL, '198802022012072007', '2025-05-21', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(104, NULL, '198802022012072007', '2025-05-22', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29'),
(105, NULL, '198802022012072007', '2025-05-23', '08:00:00', '16:00:00', 'Hadir', NULL, NULL, NULL, NULL, '2025-05-24 15:45:29');

-- --------------------------------------------------------

--
-- Table structure for table `dinas`
--

CREATE TABLE `dinas` (
  `id_dinas` int(11) NOT NULL,
  `nama_dinas` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `dinas`
--

INSERT INTO `dinas` (`id_dinas`, `nama_dinas`) VALUES
(1, 'Sekretariat Daerah'),
(2, 'Sekretariat DPRD'),
(3, 'Inspektorat'),
(4, 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia'),
(5, 'Badan Perencanaan Pembangunan Daerah'),
(6, 'Badan Keuangan dan Aset Daerah'),
(7, 'Badan Pelayanan Pajak dan Retribusi Daerah'),
(8, 'Badan Penanggulangan Bencana Daerah'),
(9, 'Badan Kesatuan Bangsa dan Politik'),
(10, 'Dinas Pendidikan'),
(11, 'Dinas Kesehatan'),
(12, 'Dinas Pekerjaan Umum dan Penataan Ruang'),
(13, 'Dinas Perumahan dan Kawasan Pemukiman'),
(14, 'Dinas Sosial'),
(15, 'Dinas Tenaga Kerja'),
(16, 'Dinas Pemberdayaan Perempuan dan Perlindungan Anak dan Keluarga Berencana'),
(17, 'Dinas Pemberdayaan Masyarakat Kampung'),
(18, 'Dinas Ketahanan Pangan'),
(19, 'Dinas Pertanian'),
(20, 'Dinas Perikanan'),
(21, 'Dinas Pemuda dan Olahraga'),
(22, 'Dinas Kebudayaan dan Pariwisata'),
(23, 'Dinas Perindustrian Dan Perdagangan'),
(24, 'Dinas Penanaman Modal Perijinan dan Koperasi'),
(25, 'Dinas Kependudukan dan Pencatatan Sipil'),
(26, 'Dinas Perhubungan'),
(27, 'Dinas Perpustakaan dan Arsip Daerah'),
(28, 'Dinas Komunikasi, Informatika dan Persandian'),
(29, 'Dinas Lingkunan Hidup'),
(30, 'Satuan Polisi Pamong Praja'),
(31, 'RSUD Kabupaten Deiyai'),
(32, 'Distrik Tigi Barat'),
(33, 'Distrik Tigi Timur'),
(34, 'Distrik Tigi'),
(35, 'Distrik Bouwobado'),
(36, 'Distrik Kapiraya');

-- --------------------------------------------------------

--
-- Table structure for table `hari_libur`
--

CREATE TABLE `hari_libur` (
  `id_libur` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `hari_libur`
--

INSERT INTO `hari_libur` (`id_libur`, `tanggal`, `keterangan`) VALUES
(1, '2025-01-01', 'Tahun Baru Masehi'),
(2, '2025-03-29', 'Hari Raya Nyepi'),
(3, '2025-04-20', 'Idul Fitri 1446 H'),
(4, '2025-04-21', 'Idul Fitri 1446 H'),
(5, '2025-05-01', 'Hari Buruh Internasional'),
(6, '2025-05-15', 'Kenaikan Isa Al Masih'),
(7, '2025-06-01', 'Hari Lahir Pancasila'),
(8, '2025-06-17', 'Hari Raya Idul Adha 1446 H');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_user_target` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `tanggal_notifikasi` datetime DEFAULT current_timestamp(),
  `dibaca` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `id_pegawai` int(11) NOT NULL,
  `nip` varchar(50) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `pangkat` varchar(100) DEFAULT NULL,
  `golongan` varchar(50) DEFAULT NULL,
  `jabatan` varchar(255) DEFAULT NULL,
  `dinas` varchar(255) NOT NULL,
  `status_kepegawaian` enum('PNS','PPPK','Honor') NOT NULL DEFAULT 'PNS',
  `id_dinas` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pegawai`
--

INSERT INTO `pegawai` (`id_pegawai`, `nip`, `nama`, `pangkat`, `golongan`, `jabatan`, `dinas`, `status_kepegawaian`, `id_dinas`) VALUES
(11, '1992121420201003', 'bendahara', '', '', 'staff', 'Pemerintah Daerah', 'PNS', NULL),
(12, '1992121420201002', 'admin', '', '', 'staff', 'Pemerintah Daerah', 'PNS', NULL),
(93, '198010012005011001', 'JHON DENI A GOBAI S.Kom', 'Staff', '3A', 'ANALISIS JABATAN', 'Sekretariat Daerah', 'PNS', NULL),
(9, '123456789123456789', 'HILMAN FARIZ FAUZAa', 'admin', '3A', 'Superadmin', 'Sekretariat Daerah', 'PNS', NULL),
(19, 'BP2025001', 'Bupati Kabupaten Deiyai', 'Pembina Utama', 'IV/e', 'Bupati', '', 'PNS', NULL),
(18, 'SA2025001', 'Superadmin Sistem', 'Pembina Utama', 'IV/e', 'Superadmin', '', 'PNS', NULL),
(20, 'AD001', 'Admin Sekretariat Daerah', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 1),
(21, 'AD002', 'Admin Sekretariat DPRD', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 2),
(22, 'AD003', 'Admin Inspektorat', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 3),
(23, 'AD004', 'Admin Badan Kepegawaian dan Pengembangan Sumber Daya Manusia', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 4),
(24, 'AD005', 'Admin Badan Perencanaan Pembangunan Daerah', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 5),
(25, 'AD006', 'Admin Badan Keuangan dan Aset Daerah', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 6),
(26, 'AD007', 'Admin Badan Pelayanan Pajak dan Retribusi Daerah', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 7),
(27, 'AD008', 'Admin Badan Penanggulangan Bencana Daerah', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 8),
(28, 'AD009', 'Admin Badan Kesatuan Bangsa dan Politik', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 9),
(29, 'AD010', 'Admin Dinas Pendidikan', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 10),
(30, 'AD011', 'Admin Dinas Kesehatan', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 11),
(31, 'AD012', 'Admin Dinas Pekerjaan Umum dan Penataan Ruang', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 12),
(32, 'AD013', 'Admin Dinas Perumahan dan Kawasan Pemukiman', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 13),
(33, 'AD014', 'Admin Dinas Sosial', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 14),
(34, 'AD015', 'Admin Dinas Tenaga Kerja', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 15),
(35, 'AD016', 'Admin Dinas Pemberdayaan Perempuan dan Perlindungan Anak dan Keluarga Berencana', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 16),
(36, 'AD017', 'Admin Dinas Pemberdayaan Masyarakat Kampung', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 17),
(37, 'AD018', 'Admin Dinas Ketahanan Pangan', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 18),
(38, 'AD019', 'Admin Dinas Pertanian', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 19),
(39, 'AD020', 'Admin Dinas Perikanan', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 20),
(40, 'AD021', 'Admin Dinas Pemuda dan Olahraga', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 21),
(41, 'AD022', 'Admin Dinas Kebudayaan dan Pariwisata', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 22),
(42, 'AD023', 'Admin Dinas Perindustrian Dan Perdagangan', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 23),
(43, 'AD024', 'Admin Dinas Penanaman Modal Perijinan dan Koperasi', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 24),
(44, 'AD025', 'Admin Dinas Kependudukan dan Pencatatan Sipil', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 25),
(45, 'AD026', 'Admin Dinas Perhubungan', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 26),
(46, 'AD027', 'Admin Dinas Perpustakaan dan Arsip Daerah', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 27),
(47, 'AD028', 'Admin Dinas Komunikasi, Informatika dan Persandian', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 28),
(48, 'AD029', 'Admin Dinas Lingkunan Hidup', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 29),
(49, 'AD030', 'Admin Satuan Polisi Pamong Praja', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 30),
(50, 'AD031', 'Admin RSUD Kabupaten Deiyai', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 31),
(51, 'AD032', 'Admin Distrik Tigi Barat', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 32),
(52, 'AD033', 'Admin Distrik Tigi Timur', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 33),
(53, 'AD034', 'Admin Distrik Tigi', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 34),
(54, 'AD035', 'Admin Distrik Bouwobado', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 35),
(55, 'AD036', 'Admin Distrik Kapiraya', 'Penata', 'III/c', 'Admin Dinas', '', 'PNS', 36),
(56, 'BD001', 'Bendahara Sekretariat Daerah', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 1),
(57, 'BD002', 'Bendahara Sekretariat DPRD', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 2),
(58, 'BD003', 'Bendahara Inspektorat', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 3),
(59, 'BD004', 'Bendahara Badan Kepegawaian dan Pengembangan Sumber Daya Manusia', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 4),
(60, 'BD005', 'Bendahara Badan Perencanaan Pembangunan Daerah', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 5),
(61, 'BD006', 'Bendahara Badan Keuangan dan Aset Daerah', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 6),
(62, 'BD007', 'Bendahara Badan Pelayanan Pajak dan Retribusi Daerah', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 7),
(63, 'BD008', 'Bendahara Badan Penanggulangan Bencana Daerah', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 8),
(64, 'BD009', 'Bendahara Badan Kesatuan Bangsa dan Politik', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 9),
(65, 'BD010', 'Bendahara Dinas Pendidikan', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 10),
(66, 'BD011', 'Bendahara Dinas Kesehatan', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 11),
(67, 'BD012', 'Bendahara Dinas Pekerjaan Umum dan Penataan Ruang', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 12),
(68, 'BD013', 'Bendahara Dinas Perumahan dan Kawasan Pemukiman', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 13),
(69, 'BD014', 'Bendahara Dinas Sosial', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 14),
(70, 'BD015', 'Bendahara Dinas Tenaga Kerja', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 15),
(71, 'BD016', 'Bendahara Dinas Pemberdayaan Perempuan dan Perlindungan Anak dan Keluarga Berencana', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 16),
(72, 'BD017', 'Bendahara Dinas Pemberdayaan Masyarakat Kampung', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 17),
(73, 'BD018', 'Bendahara Dinas Ketahanan Pangan', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 18),
(74, 'BD019', 'Bendahara Dinas Pertanian', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 19),
(75, 'BD020', 'Bendahara Dinas Perikanan', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 20),
(76, 'BD021', 'Bendahara Dinas Pemuda dan Olahraga', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 21),
(77, 'BD022', 'Bendahara Dinas Kebudayaan dan Pariwisata', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 22),
(78, 'BD023', 'Bendahara Dinas Perindustrian Dan Perdagangan', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 23),
(79, 'BD024', 'Bendahara Dinas Penanaman Modal Perijinan dan Koperasi', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 24),
(80, 'BD025', 'Bendahara Dinas Kependudukan dan Pencatatan Sipil', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 25),
(81, 'BD026', 'Bendahara Dinas Perhubungan', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 26),
(82, 'BD027', 'Bendahara Dinas Perpustakaan dan Arsip Daerah', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 27),
(83, 'BD028', 'Bendahara Dinas Komunikasi, Informatika dan Persandian', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 28),
(84, 'BD029', 'Bendahara Dinas Lingkunan Hidup', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 29),
(85, 'BD030', 'Bendahara Satuan Polisi Pamong Praja', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 30),
(86, 'BD031', 'Bendahara RSUD Kabupaten Deiyai', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 31),
(87, 'BD032', 'Bendahara Distrik Tigi Barat', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 32),
(88, 'BD033', 'Bendahara Distrik Tigi Timur', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 33),
(89, 'BD034', 'Bendahara Distrik Tigi', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 34),
(90, 'BD035', 'Bendahara Distrik Bouwobado', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 35),
(91, 'BD036', 'Bendahara Distrik Kapiraya', 'Penata Muda', 'III/a', 'Bendahara Dinas', '', 'PNS', 36);

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `parameter` varchar(255) NOT NULL,
  `nilai` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`parameter`, `nilai`) VALUES
('Jam Masuk Default', '08:00:00'),
('Jam Pulang Default', '16:00:00'),
('Waktu Toleransi Keterlambatan', '15'),
('Max Izin Tanpa Surat', '3'),
('Persentase Potongan Alpha', '4'),
('Persentase Potongan Izin', '2.5'),
('Persentase Potongan Sakit', '1.5'),
('Persentase Potongan Tidak Disetujui', '10');

-- --------------------------------------------------------

--
-- Table structure for table `rekapitulasi_bulanan`
--

CREATE TABLE `rekapitulasi_bulanan` (
  `id_rekap` int(11) NOT NULL,
  `id_pegawai` int(11) DEFAULT NULL,
  `nip` varchar(50) NOT NULL,
  `bulan` int(2) NOT NULL,
  `tahun` int(4) NOT NULL,
  `total_hadir` int(5) DEFAULT 0,
  `total_alpha` int(5) DEFAULT 0,
  `total_izin` int(5) DEFAULT 0,
  `total_sakit` int(5) DEFAULT 0,
  `total_cuti` int(5) DEFAULT 0,
  `total_dinas_luar` int(5) DEFAULT 0,
  `total_hari_kerja` int(5) DEFAULT 0,
  `persentase_kehadiran` decimal(5,2) DEFAULT 0.00,
  `potongan_tpp` decimal(10,2) DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `rekapitulasi_bulanan`
--

INSERT INTO `rekapitulasi_bulanan` (`id_rekap`, `id_pegawai`, `nip`, `bulan`, `tahun`, `total_hadir`, `total_alpha`, `total_izin`, `total_sakit`, `total_cuti`, `total_dinas_luar`, `total_hari_kerja`, `persentase_kehadiran`, `potongan_tpp`) VALUES
(1, NULL, '199001012015031003', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(2, NULL, '197503102000022002', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(4, NULL, '198808252012051005', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(5, NULL, '198505152010022002', 5, 2025, 13, 2, 0, 0, 0, 0, 20, '65.00', '0.14'),
(6, NULL, '199207202018042004', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(7, NULL, '1000000000000000002', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(8, NULL, '197001011995011001', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(10, 9, '123456789123456789', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(11, NULL, '2', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(12, NULL, '3', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(13, 12, '1992121420201002', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(14, 11, '1992121420201003', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(17, NULL, 'AD004', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(18, NULL, 'AD009', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(19, NULL, 'AD006', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(20, NULL, 'AD007', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(21, NULL, 'AD008', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(22, NULL, 'AD005', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(23, NULL, 'AD022', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(24, NULL, 'AD025', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(25, NULL, 'AD011', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(26, NULL, 'AD018', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(27, NULL, 'AD028', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(28, NULL, 'AD029', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(29, NULL, 'AD012', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(30, NULL, 'AD017', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(31, NULL, 'AD016', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(32, NULL, 'AD021', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(33, NULL, 'AD024', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(34, NULL, 'AD010', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(35, NULL, 'AD026', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(36, NULL, 'AD020', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(37, NULL, 'AD023', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(38, NULL, 'AD027', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(39, NULL, 'AD019', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(40, NULL, 'AD013', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(41, NULL, 'AD014', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(42, NULL, 'AD015', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(43, NULL, 'AD035', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(44, NULL, 'AD036', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(45, NULL, 'AD034', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(46, NULL, 'AD032', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(47, NULL, 'AD033', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(48, NULL, 'AD003', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(49, NULL, 'AD031', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(50, NULL, 'AD030', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(51, NULL, 'AD001', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(52, NULL, 'AD002', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(53, NULL, 'BD004', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(54, NULL, 'BD009', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(55, NULL, 'BD006', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(56, NULL, 'BD007', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(57, NULL, 'BD008', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(58, NULL, 'BD005', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(59, NULL, 'BD022', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(60, NULL, 'BD025', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(61, NULL, 'BD011', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(62, NULL, 'BD018', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(63, NULL, 'BD028', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(64, NULL, 'BD029', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(65, NULL, 'BD012', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(66, NULL, 'BD017', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(67, NULL, 'BD016', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(68, NULL, 'BD021', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(69, NULL, 'BD024', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(70, NULL, 'BD010', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(71, NULL, 'BD026', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(72, NULL, 'BD020', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(73, NULL, 'BD023', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(74, NULL, 'BD027', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(75, NULL, 'BD019', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(76, NULL, 'BD013', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(77, NULL, 'BD014', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(78, NULL, 'BD015', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(79, NULL, 'BD035', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(80, NULL, 'BD036', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(81, NULL, 'BD034', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(82, NULL, 'BD032', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(83, NULL, 'BD033', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(84, NULL, 'BD003', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(85, NULL, 'BD031', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(86, NULL, 'BD030', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(87, NULL, 'BD001', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(88, NULL, 'BD002', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(89, NULL, 'BP2025001', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00'),
(90, NULL, 'SA2025001', 5, 2025, 0, 0, 0, 0, 0, 0, 20, '0.00', '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `id_pegawai` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `hak_akses` enum('Superadmin','Admin Dinas','Bendahara Dinas','Bupati','Pegawai') NOT NULL,
  `status_akun` enum('Aktif','Nonaktif','Blokir') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_dinas_terkait` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `id_pegawai`, `username`, `password`, `hak_akses`, `status_akun`, `created_at`, `updated_at`, `id_dinas_terkait`) VALUES
(5, 19, 'bupati', 'bupati123', 'Bupati', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', NULL),
(4, 18, 'superadmin', 'superadmin123', 'Superadmin', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', NULL),
(6, 20, 'adminsekretariatdaerah', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 1),
(7, 21, 'adminsekretariatdprd', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 2),
(8, 22, 'admininspektorat', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 3),
(9, 23, 'adminbadankepegawaiandanpengembangansumberdayamanusia', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 4),
(10, 24, 'adminbadanperencanaanpembangunandaerah', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 5),
(11, 25, 'adminbadankeuangandanasetdaerah', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 6),
(12, 26, 'adminbadanpelayananpajakdanretribusidaerah', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 7),
(13, 27, 'adminbadanpenanggulanganbencanadaerah', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 8),
(14, 28, 'adminbadankesatuanbangsadanpolitik', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 9),
(15, 29, 'admindinaspendidikan', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 10),
(16, 30, 'admindinaskesehatan', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 11),
(17, 31, 'admindinaspekerjaanumumdanpenataanruang', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 12),
(18, 32, 'admindinasperumahandankawasanpemukiman', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 13),
(19, 33, 'admindinassosial', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 14),
(20, 34, 'admindinastenagakerja', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 15),
(21, 35, 'admindinaspemberdayaanperempuandanperlindungananakdankeluargaberencana', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 16),
(22, 36, 'admindinaspemberdayaanmasyarakatkampung', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 17),
(23, 37, 'admindinasketahananpangan', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 18),
(24, 38, 'admindinaspertanian', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 19),
(25, 39, 'admindinasperikanan', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 20),
(26, 40, 'admindinaspemudadanolahraga', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 21),
(27, 41, 'admindinaskebudayaandanpariwisata', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 22),
(28, 42, 'admindinasperindustriandanperdagangan', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 23),
(29, 43, 'admindinaspenanamanmodalperijinandankoperasi', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 24),
(30, 44, 'admindinaskependudukandanpencatatansipil', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 25),
(31, 45, 'admindinasperhubungan', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 26),
(32, 46, 'admindinasperpustakaandanarsipdaerah', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 27),
(33, 47, 'admindinaskomunikasi,informatikadanpersandian', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 28),
(34, 48, 'admindinaslingkunanhidup', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 29),
(35, 49, 'adminsatuanpolisipamongpraja', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 30),
(36, 50, 'adminrsudkabupatendeiyai', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 31),
(37, 51, 'admindistriktigibarat', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 32),
(38, 52, 'admindistriktigitimur', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 33),
(39, 53, 'admindistriktigi', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 34),
(40, 54, 'admindistrikbouwobado', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 35),
(41, 55, 'admindistrikkapiraya', 'adminpass', 'Admin Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 36),
(42, 56, 'bendaharasekretariatdaerah', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 1),
(43, 57, 'bendaharasekretariatdprd', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 2),
(44, 58, 'bendaharainspektorat', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 3),
(45, 59, 'bendaharabadankepegawaiandanpengembangansumberdayamanusia', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 4),
(46, 60, 'bendaharabadanperencanaanpembangunandaerah', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 5),
(47, 61, 'bendaharabadankeuangandanasetdaerah', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 6),
(48, 62, 'bendaharabadanpelayananpajakdanretribusidaerah', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 7),
(49, 63, 'bendaharabadanpenanggulanganbencanadaerah', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 8),
(50, 64, 'bendaharabadankesatuanbangsadanpolitik', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 9),
(51, 65, 'bendaharadinaspendidikan', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 10),
(52, 66, 'bendaharadinaskesehatan', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 11),
(53, 67, 'bendaharadinaspekerjaanumumdanpenataanruang', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 12),
(54, 68, 'bendaharadinasperumahandankawasanpemukiman', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 13),
(55, 69, 'bendaharadinassosial', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 14),
(56, 70, 'bendaharadinastenagakerja', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 15),
(57, 71, 'bendaharadinaspemberdayaanperempuandanperlindungananakdankeluargaberencana', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 16),
(58, 72, 'bendaharadinaspemberdayaanmasyarakatkampung', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 17),
(59, 73, 'bendaharadinasketahananpangan', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 18),
(60, 74, 'bendaharadinaspertanian', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 19),
(61, 75, 'bendaharadinasperikanan', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 20),
(62, 76, 'bendaharadinaspemudadanolahraga', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 21),
(63, 77, 'bendaharadinaskebudayaandanpariwisata', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 22),
(64, 78, 'bendaharadinasperindustriandanperdagangan', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 23),
(65, 79, 'bendaharadinaspenanamanmodalperijinandankoperasi', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 24),
(66, 80, 'bendaharadinaskependudukandanpencatatansipil', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 25),
(67, 81, 'bendaharadinasperhubungan', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 26),
(68, 82, 'bendaharadinasperpustakaandanarsipdaerah', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 27),
(69, 83, 'bendaharadinaskomunikasi,informatikadanpersandian', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 28),
(70, 84, 'bendaharadinaslingkunanhidup', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 29),
(71, 85, 'bendaharasatuanpolisipamongpraja', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 30),
(72, 86, 'bendahararsudkabupatendeiyai', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 31),
(73, 87, 'bendaharadistriktigibarat', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 32),
(74, 88, 'bendaharadistriktigitimur', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 33),
(75, 89, 'bendaharadistriktigi', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 34),
(76, 90, 'bendaharadistrikbouwobado', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 35),
(77, 91, 'bendaharadistrikkapiraya', 'bendaharapass', 'Bendahara Dinas', 'Aktif', '2025-05-29 07:33:45', '2025-05-29 07:33:45', 36);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id_absensi`),
  ADD UNIQUE KEY `idx_nip_tanggal` (`nip`,`tanggal`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `fk_absensi_pegawai` (`id_pegawai`);

--
-- Indexes for table `dinas`
--
ALTER TABLE `dinas`
  ADD PRIMARY KEY (`id_dinas`),
  ADD UNIQUE KEY `nama_dinas` (`nama_dinas`);

--
-- Indexes for table `hari_libur`
--
ALTER TABLE `hari_libur`
  ADD PRIMARY KEY (`id_libur`),
  ADD UNIQUE KEY `tanggal` (`tanggal`),
  ADD KEY `idx_tanggal` (`tanggal`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `id_user_target` (`id_user_target`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id_pegawai`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD KEY `idx_nip` (`nip`),
  ADD KEY `idx_dinas` (`dinas`),
  ADD KEY `fk_dinas` (`id_dinas`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`parameter`);

--
-- Indexes for table `rekapitulasi_bulanan`
--
ALTER TABLE `rekapitulasi_bulanan`
  ADD PRIMARY KEY (`id_rekap`),
  ADD UNIQUE KEY `idx_nip_bulan_tahun` (`nip`,`bulan`,`tahun`),
  ADD KEY `fk_rekap_pegawai` (`id_pegawai`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `id_pegawai` (`id_pegawai`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_hak_akses` (`hak_akses`),
  ADD KEY `idx_users_id_dinas_terkait` (`id_dinas_terkait`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id_absensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `dinas`
--
ALTER TABLE `dinas`
  MODIFY `id_dinas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `hari_libur`
--
ALTER TABLE `hari_libur`
  MODIFY `id_libur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id_pegawai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `rekapitulasi_bulanan`
--
ALTER TABLE `rekapitulasi_bulanan`
  MODIFY `id_rekap` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
