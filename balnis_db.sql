-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 07, 2026 at 03:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `balnis_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang_keluar`
--

CREATE TABLE `barang_keluar` (
  `id_keluar` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `id_sku` int(11) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `total` decimal(15,0) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang_keluar`
--

INSERT INTO `barang_keluar` (`id_keluar`, `tanggal`, `id_sku`, `jumlah`, `total`) VALUES
(8, '2026-06-04', 9, 200, 40000000);

-- --------------------------------------------------------

--
-- Table structure for table `barang_masuk`
--

CREATE TABLE `barang_masuk` (
  `id_masuk` int(11) NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `id_supplier` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang_masuk`
--

INSERT INTO `barang_masuk` (`id_masuk`, `tanggal_masuk`, `id_supplier`, `id_user`) VALUES
(7, '2026-06-04', 1, 4),
(8, '2026-06-04', 1, 4),
(9, '2026-06-04', 1, 4),
(10, '2026-06-04', 1, 4),
(11, '2026-06-04', 1, 4),
(12, '2026-06-04', 1, 4),
(13, '2026-06-04', 1, 4),
(14, '2026-06-04', 1, 4),
(17, '2026-06-04', 1, 4),
(18, '2026-06-05', 1, 4),
(19, '2026-06-05', 2, 2),
(20, '2026-06-05', 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `detail_barang_keluar`
--

CREATE TABLE `detail_barang_keluar` (
  `id_detail_keluar` int(11) NOT NULL,
  `id_keluar` int(11) DEFAULT NULL,
  `id_sku` int(11) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_barang_masuk`
--

CREATE TABLE `detail_barang_masuk` (
  `id_detail` int(11) NOT NULL,
  `id_masuk` int(11) DEFAULT NULL,
  `id_sku` int(11) DEFAULT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 0,
  `harga_beli` decimal(15,0) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_barang_masuk`
--

INSERT INTO `detail_barang_masuk` (`id_detail`, `id_masuk`, `id_sku`, `jumlah`, `harga_beli`) VALUES
(5, 19, 20, 11, 20000),
(6, 20, 13, 12, 120000);

-- --------------------------------------------------------

--
-- Table structure for table `inventori_barang`
--

CREATE TABLE `inventori_barang` (
  `id_sku` int(11) NOT NULL,
  `id_jenis` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `ukuran` varchar(50) NOT NULL,
  `kode_sku` varchar(50) DEFAULT NULL,
  `harga_beli` decimal(15,0) NOT NULL DEFAULT 0,
  `harga_jual` decimal(15,0) NOT NULL DEFAULT 0,
  `stok` int(11) NOT NULL DEFAULT 0,
  `stok_minimum` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventori_barang`
--

INSERT INTO `inventori_barang` (`id_sku`, `id_jenis`, `nama_barang`, `ukuran`, `kode_sku`, `harga_beli`, `harga_jual`, `stok`, `stok_minimum`, `created_at`, `updated_at`) VALUES
(1, 1, '', '5 Liter', 'EMB-LS-BUL-5L', 4000, 6000, 40, 10, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(2, 1, '', '10 Liter', 'EMB-LS-BUL-10L', 5000, 7500, 40, 10, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(3, 1, '', '20 Liter', 'EMB-LS-BUL-20L', 8000, 12000, 25, 8, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(4, 2, '', '10 Liter', 'EMB-LS-KTK-10L', 6000, 9000, 20, 6, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(5, 2, '', '20 Liter', 'EMB-LS-KTK-20L', 9500, 14000, 15, 5, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(6, 4, '', '5 Liter', 'EMB-MSP-BUL-5L', 3800, 5800, 35, 10, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(7, 4, '', '10 Liter', 'EMB-MSP-BUL-10L', 5000, 7500, 30, 8, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(8, 4, '', '20 Liter', 'EMB-MSP-BUL-20L', 7500, 11000, 20, 6, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(9, 7, '', '1 Liter', 'BL-MYK-BIA-1L', 150000, 200000, 15, 17, '2026-06-04 11:38:44', '2026-06-05 07:44:27'),
(10, 7, '', '1.5 Liter', 'BL-MYK-BIA-15L', 165000, 220000, 40, 10, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(12, 8, '', '1.5 Liter', 'BL-MYK-CHP-15L', 200000, 275000, 20, 6, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(13, 9, '', '1 Liter', 'BL-MYK-JCR-1L', 210000, 285000, 27, 5, '2026-06-04 11:38:44', '2026-06-05 07:48:17'),
(14, 10, '', '1.5 Liter', 'BL-PLP-DC-15L', 280000, 380000, 25, 6, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(15, 11, '', '1.5 Liter', 'BL-PLP-VC-15L', 320000, 430000, 18, 5, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(18, 15, '', 'Standar', 'CTG-KJ-POL-STD', 2500, 5000, 0, 5, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(19, 15, '', 'Panjang', 'CTG-KJ-POL-PJG', 3000, 6000, 12, 5, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(20, 16, '', 'Standar', 'CTG-KJ-UKR-STD', 4500, 8000, 19, 4, '2026-06-04 11:38:44', '2026-06-05 07:47:03'),
(21, 17, '', 'Standar', 'CTG-PL-STD-STD', 2000, 4000, 15, 5, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(22, 18, '', 'Standar', 'CTG-PL-AP-STD', 3000, 5500, 10, 4, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(23, 19, '', 'Standar', 'CTG-ML-POL-STD', 3500, 6500, 7, 4, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(24, 21, '', 'Pendek 40cm', 'SPU-LID-PDK-40', 1500, 3000, 10, 5, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(25, 22, '', 'Panjang 120cm', 'SPU-LID-PJG-120', 2000, 4000, 5, 5, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(26, 23, '', 'Standar', 'SPU-CLG-NIL-STD', 5000, 8500, 12, 5, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(27, 24, '', 'Standar', 'SPU-CLG-IJK-STD', 4500, 7500, 8, 4, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(28, 25, '', 'Standar', 'SPU-SCB-LTI-STD', 7000, 12000, 6, 3, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(29, 27, '', '5 Liter', 'TS-LS-INJ-5L', 12000, 18000, 8, 4, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(30, 27, '', '10 Liter', 'TS-LS-INJ-10L', 18000, 26000, 6, 3, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(32, 28, '', '10 Liter', 'TS-LS-TTP-10L', 14000, 20000, 8, 4, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(33, 29, '', '10 Liter', 'TS-LS-AYN-10L', 20000, 29000, 5, 3, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(36, 34, '', '20 cm', 'PN-MXM-SS-20', 35000, 52000, 15, 5, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(37, 34, '', '24 cm', 'PN-MXM-SS-24', 42000, 62000, 12, 4, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(38, 35, '', '20 cm', 'PN-MXM-AL-20', 22000, 35000, 20, 6, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(39, 36, '', '20 cm', 'PN-MSP-SS-20', 33000, 50000, 10, 4, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(40, 36, '', '24 cm', 'PN-MSP-SS-24', 40000, 60000, 8, 3, '2026-06-04 11:38:44', '2026-06-04 11:38:44'),
(41, 37, '', '2Liter', 'MBR-SwtKcl', 30000, 50000, 5, 8, '2026-06-04 18:25:51', '2026-06-04 18:26:29'),
(42, 38, 'Meja Lipat', '3x2m', 'MJLPT-WW-PLSTK', 40000, 60000, 0, 10, '2026-06-05 06:33:18', '2026-06-05 06:33:18'),
(45, 40, 'Rolan', '5m', 'RLN-KRY-5M', 20000, 25000, 26, 19, '2026-06-05 07:30:29', '2026-06-05 07:31:17');

-- --------------------------------------------------------

--
-- Table structure for table `master_barang`
--

CREATE TABLE `master_barang` (
  `id_barang` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `satuan_dasar` varchar(30) NOT NULL DEFAULT 'pcs',
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_barang`
--

INSERT INTO `master_barang` (`id_barang`, `nama_barang`, `satuan_dasar`, `deskripsi`, `created_at`) VALUES
(1, 'Ember Plastik', 'pcs', 'Ember plastik berbagai ukuran', '2026-06-04 11:38:44'),
(2, 'Blender', 'unit', 'Blender elektronik rumah tangga', '2026-06-04 11:38:44'),
(3, 'Centong Nasi', 'pcs', 'Centong nasi berbagai bahan', '2026-06-04 11:38:44'),
(4, 'Sapu', 'pcs', 'Sapu untuk kebersihan rumah', '2026-06-04 11:38:44'),
(5, 'Tempat Sampah', 'pcs', 'Tempat sampah rumah tangga', '2026-06-04 11:38:44'),
(6, 'Panci', 'pcs', 'Panci masak berbagai ukuran', '2026-06-04 11:38:44'),
(7, 'Ember', 'pcs', NULL, '2026-06-04 18:25:51'),
(8, 'Meja Lipat', 'pcs', NULL, '2026-06-05 06:17:41'),
(9, 'esteh', 'pcs', NULL, '2026-06-05 06:42:02'),
(10, 'Rolan', 'pcs', NULL, '2026-06-05 07:30:29');

-- --------------------------------------------------------

--
-- Table structure for table `master_jenis`
--

CREATE TABLE `master_jenis` (
  `id_jenis` int(11) NOT NULL,
  `id_merek` int(11) NOT NULL,
  `nama_jenis` varchar(100) NOT NULL,
  `keterangan` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_jenis`
--

INSERT INTO `master_jenis` (`id_jenis`, `id_merek`, `nama_jenis`, `keterangan`) VALUES
(1, 1, 'Bulat Biasa', NULL),
(2, 1, 'Kotak / Persegi', NULL),
(3, 1, 'Tutup Putar', NULL),
(4, 2, 'Bulat Biasa', NULL),
(5, 2, 'Heavy Duty', NULL),
(6, 3, 'Bulat Biasa', NULL),
(7, 4, 'Blender Biasa', NULL),
(8, 4, 'Blender + Chopper', NULL),
(9, 4, 'Blender + Juicer', NULL),
(10, 5, 'Daily Collection', NULL),
(11, 5, 'Viva Collection', NULL),
(12, 5, 'ProBlend', NULL),
(13, 6, 'Blender Biasa', NULL),
(14, 6, 'Blender Turbo', NULL),
(15, 7, 'Polos', NULL),
(16, 7, 'Ukir', NULL),
(17, 8, 'Standard', NULL),
(18, 8, 'Anti-Panas', NULL),
(19, 9, 'Polos', NULL),
(20, 9, 'Motif', NULL),
(21, 10, 'Pendek / Serok', NULL),
(22, 10, 'Panjang / Gagang', NULL),
(23, 11, 'Serat Nilon', NULL),
(24, 11, 'Ijuk', NULL),
(25, 12, 'Lantai', NULL),
(26, 12, 'Langit-langit', NULL),
(27, 13, 'Injak / Pedal', NULL),
(28, 13, 'Tanpa Tutup', NULL),
(29, 13, 'Ayun', NULL),
(30, 14, 'Injak / Pedal', NULL),
(31, 14, 'Tanpa Tutup', NULL),
(32, 15, 'FNISS', NULL),
(33, 15, 'KNODD', NULL),
(34, 16, 'Stainless Steel', NULL),
(35, 16, 'Aluminium', NULL),
(36, 17, 'Stainless Steel', NULL),
(37, 19, 'kecil', NULL),
(38, 20, 'plastik', NULL),
(39, 21, 'large', NULL),
(40, 22, 'besi', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `master_merek`
--

CREATE TABLE `master_merek` (
  `id_merek` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `nama_merek` varchar(100) NOT NULL,
  `keterangan` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_merek`
--

INSERT INTO `master_merek` (`id_merek`, `id_barang`, `nama_merek`, `keterangan`) VALUES
(1, 1, 'Lion Star', NULL),
(2, 1, 'Maspion', NULL),
(3, 1, 'Shinpo', NULL),
(4, 2, 'Miyako', NULL),
(5, 2, 'Philips', NULL),
(6, 2, 'Cosmos', NULL),
(7, 3, 'Kayu Jati', NULL),
(8, 3, 'Plastik Biasa', NULL),
(9, 3, 'Melamin', NULL),
(10, 4, 'Lidi Alami', NULL),
(11, 4, 'Cling', NULL),
(12, 4, 'Scotch-Brite', NULL),
(13, 5, 'Lion Star', NULL),
(14, 5, 'Maspion', NULL),
(15, 5, 'IKEA', NULL),
(16, 6, 'Maxim', NULL),
(17, 6, 'Maspion', NULL),
(18, 6, 'Teflon', NULL),
(19, 7, 'Sawit', NULL),
(20, 8, 'WorkWork', NULL),
(21, 9, 'kota', NULL),
(22, 10, 'kroya', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_inventori`
--

CREATE TABLE `riwayat_inventori` (
  `id_log` int(11) NOT NULL,
  `id_sku` int(11) DEFAULT NULL,
  `nama_lengkap` varchar(255) DEFAULT NULL,
  `aksi` enum('TAMBAH','UPDATE','HAPUS') NOT NULL,
  `stok_lama` int(11) DEFAULT 0,
  `stok_baru` int(11) DEFAULT 0,
  `selisih` int(11) DEFAULT 0,
  `alasan` varchar(200) DEFAULT NULL,
  `user_admin` varchar(80) DEFAULT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_inventori`
--

INSERT INTO `riwayat_inventori` (`id_log`, `id_sku`, `nama_lengkap`, `aksi`, `stok_lama`, `stok_baru`, `selisih`, `alasan`, `user_admin`, `tanggal`) VALUES
(1, 41, 'Ember - Sawit - kecil - 2Liter', 'TAMBAH', 0, 20, 20, 'Input awal / barang baru', '0', '2026-06-04 18:25:51'),
(2, 41, 'Ember - Sawit - kecil - 2Liter', 'UPDATE', 20, 5, -15, '0', 'admin', '2026-06-04 18:26:29'),
(3, NULL, 'Blender - Cosmos - Blender Biasa - 1 Liter', 'HAPUS', 96, 0, -96, 'Tidak Dijual Lagi', 'admin', '2026-06-04 21:40:58'),
(4, NULL, 'esteh - kota - large - 1liter', 'TAMBAH', 0, 0, 0, 'Input awal / barang baru', 'admin', '2026-06-05 06:42:02'),
(5, NULL, 'esteh - kota - large - 1liter', 'TAMBAH', 0, 0, 0, 'Input awal / barang baru', 'admin', '2026-06-05 06:42:02'),
(6, NULL, 'esteh - kota - large - 1liter', 'HAPUS', 15, 0, -15, 'Tidak Dijual Lagi', 'karyawan1', '2026-06-05 06:50:59'),
(7, 45, 'Rolan - kroya - besi - 5m', 'TAMBAH', 0, 2, 2, 'Input awal / barang baru', 'karyawan1', '2026-06-05 07:30:29'),
(8, 45, 'Rolan - kroya - besi - 5m', 'TAMBAH', 0, 2, 2, 'Input awal / barang baru', 'karyawan1', '2026-06-05 07:30:29'),
(9, 45, 'Rolan - kroya - besi - 5m', 'UPDATE', 2, 26, 24, '0', 'karyawan1', '2026-06-05 07:31:17'),
(10, NULL, 'Blender - Cosmos - Blender Turbo - 1 Liter', 'UPDATE', 44, 40, -4, '0', 'karyawan1', '2026-06-05 07:31:55'),
(11, NULL, 'Blender - Cosmos - Blender Turbo - 1 Liter', 'UPDATE', 40, 35, -5, NULL, 'karyawan1', '2026-06-05 07:35:13'),
(12, NULL, 'Blender - Cosmos - Blender Turbo - 1 Liter', 'UPDATE', 35, 30, -5, '0', 'karyawan1', '2026-06-05 07:41:15'),
(13, NULL, 'Blender - Cosmos - Blender Turbo - 1 Liter', 'UPDATE', 30, 20, -10, 'Koreksi Data / Salah Input', 'karyawan1', '2026-06-05 07:43:51'),
(14, 9, 'Blender - Miyako - Blender Biasa - 1 Liter', 'UPDATE', 15, 15, 0, 'Barang Rusak / Expired', 'karyawan1', '2026-06-05 07:44:27'),
(15, NULL, 'Tempat Sampah - Maspion - Tanpa Tutup - 5 Liter', 'UPDATE', 12, 17, 5, 'Perubahan Harga Saja', 'karyawan1', '2026-06-05 07:44:41'),
(16, NULL, 'Tempat Sampah - Maspion - Tanpa Tutup - 5 Liter', 'UPDATE', 17, 10, -7, 'Penerimaan Stok Baru', 'karyawan1', '2026-06-05 07:44:58'),
(17, NULL, 'Blender - Cosmos - Blender Turbo - 1 Liter', 'HAPUS', 20, 0, -20, 'Tidak Dijual Lagi', 'karyawan1', '2026-06-05 07:45:11'),
(18, NULL, 'Tempat Sampah - Maspion - Tanpa Tutup - 5 Liter', 'HAPUS', 10, 0, -10, 'Kesalahan Input Data', 'karyawan1', '2026-06-05 07:45:23'),
(19, NULL, 'Tempat Sampah - Maspion - Injak / Pedal - 5 Liter', 'HAPUS', 7, 0, -7, 'Barang Rusak Total', 'karyawan1', '2026-06-05 07:45:34'),
(20, NULL, 'Tempat Sampah - Lion Star - Tanpa Tutup - 5 Liter', 'HAPUS', 10, 0, -10, 'Produk Dihentikan Produsen', 'karyawan1', '2026-06-05 07:45:41'),
(21, NULL, 'Blender - Miyako - Blender + Chopper - 1 Liter', 'UPDATE', 30, 30, 0, 'Penyesuaian Stok Fisik (Opname)', 'karyawan1', '2026-06-05 07:49:15'),
(22, NULL, 'Blender - Miyako - Blender + Chopper - 1 Liter', 'HAPUS', 30, 0, -30, 'Tidak Dijual Lagi', 'karyawan1', '2026-06-05 07:49:55');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id_supplier` int(11) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`id_supplier`, `nama_supplier`, `alamat`, `no_hp`) VALUES
(1, 'eki', 'jatibarang', '089898989898'),
(2, 'PT. Susah', 'Jl. nyanyi', '08123432167');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','karyawan') NOT NULL DEFAULT 'karyawan',
  `status` enum('pending','active','inactive') DEFAULT 'pending',
  `last_login` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `secret_code` varchar(255) DEFAULT NULL COMMENT 'Hash bcrypt kode rahasia admin',
  `security_question` varchar(255) DEFAULT NULL COMMENT 'Pertanyaan keamanan pengganti kode rahasia',
  `security_answer` varchar(255) DEFAULT NULL COMMENT 'Hash bcrypt jawaban pertanyaan keamanan',
  `fp_attempts` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Jumlah percobaan lupa password',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `last_login`, `reset_token`, `reset_expires`, `secret_code`, `fp_attempts`, `created_at`, `updated_at`) VALUES
(2, 'karyawan1', 'karyawan1@balnis.com', '$2y$10$F00iJ4IvIkXpuxuyBk9lye6Iuo7O2X5qQaDTsPgjsAJglK2ltm.YO', 'karyawan', 'active', '2026-06-05 14:14:14', NULL, NULL, NULL, 0, '2026-04-08 16:51:45', '2026-06-05 07:14:14'),
(4, 'admin', 'hilalnafisadilah@gmail.com', '$2y$12$474v6oqlfbnp0kKsfdUNf.kwk3ac7g4d.q24F3R3OalT6bchioxzu', 'admin', 'active', '2026-06-07 19:47:30', NULL, NULL, '$2y$12$5DZBlbowRQ2fj4.2jZIKWube0mPvsvOsK.gupgemtl279RO6Otnn.', 0, '2026-04-09 02:22:30', '2026-06-07 12:47:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD PRIMARY KEY (`id_keluar`),
  ADD KEY `id_sku` (`id_sku`);

--
-- Indexes for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD PRIMARY KEY (`id_masuk`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `detail_barang_keluar`
--
ALTER TABLE `detail_barang_keluar`
  ADD PRIMARY KEY (`id_detail_keluar`),
  ADD KEY `id_keluar` (`id_keluar`),
  ADD KEY `id_sku` (`id_sku`);

--
-- Indexes for table `detail_barang_masuk`
--
ALTER TABLE `detail_barang_masuk`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_masuk` (`id_masuk`),
  ADD KEY `id_sku` (`id_sku`);

--
-- Indexes for table `inventori_barang`
--
ALTER TABLE `inventori_barang`
  ADD PRIMARY KEY (`id_sku`),
  ADD UNIQUE KEY `kode_sku` (`kode_sku`),
  ADD KEY `idx_id_jenis` (`id_jenis`);

--
-- Indexes for table `master_barang`
--
ALTER TABLE `master_barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `master_jenis`
--
ALTER TABLE `master_jenis`
  ADD PRIMARY KEY (`id_jenis`),
  ADD KEY `idx_id_merek` (`id_merek`);

--
-- Indexes for table `master_merek`
--
ALTER TABLE `master_merek`
  ADD PRIMARY KEY (`id_merek`),
  ADD KEY `idx_id_barang` (`id_barang`);

--
-- Indexes for table `riwayat_inventori`
--
ALTER TABLE `riwayat_inventori`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_id_sku` (`id_sku`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_supplier`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  MODIFY `id_keluar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  MODIFY `id_masuk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `detail_barang_keluar`
--
ALTER TABLE `detail_barang_keluar`
  MODIFY `id_detail_keluar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_barang_masuk`
--
ALTER TABLE `detail_barang_masuk`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventori_barang`
--
ALTER TABLE `inventori_barang`
  MODIFY `id_sku` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `master_barang`
--
ALTER TABLE `master_barang`
  MODIFY `id_barang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `master_jenis`
--
ALTER TABLE `master_jenis`
  MODIFY `id_jenis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `master_merek`
--
ALTER TABLE `master_merek`
  MODIFY `id_merek` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `riwayat_inventori`
--
ALTER TABLE `riwayat_inventori`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD CONSTRAINT `bk_ibfk_1` FOREIGN KEY (`id_sku`) REFERENCES `inventori_barang` (`id_sku`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD CONSTRAINT `bm_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `bm_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `detail_barang_keluar`
--
ALTER TABLE `detail_barang_keluar`
  ADD CONSTRAINT `dbk_ibfk_1` FOREIGN KEY (`id_keluar`) REFERENCES `barang_keluar` (`id_keluar`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `dbk_ibfk_2` FOREIGN KEY (`id_sku`) REFERENCES `inventori_barang` (`id_sku`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `detail_barang_masuk`
--
ALTER TABLE `detail_barang_masuk`
  ADD CONSTRAINT `dbm_ibfk_1` FOREIGN KEY (`id_masuk`) REFERENCES `barang_masuk` (`id_masuk`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `dbm_ibfk_2` FOREIGN KEY (`id_sku`) REFERENCES `inventori_barang` (`id_sku`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventori_barang`
--
ALTER TABLE `inventori_barang`
  ADD CONSTRAINT `ib_ibfk_1` FOREIGN KEY (`id_jenis`) REFERENCES `master_jenis` (`id_jenis`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `master_jenis`
--
ALTER TABLE `master_jenis`
  ADD CONSTRAINT `jenis_ibfk_1` FOREIGN KEY (`id_merek`) REFERENCES `master_merek` (`id_merek`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `master_merek`
--
ALTER TABLE `master_merek`
  ADD CONSTRAINT `merek_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `master_barang` (`id_barang`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `riwayat_inventori`
--
ALTER TABLE `riwayat_inventori`
  ADD CONSTRAINT `riwayat_ibfk_1` FOREIGN KEY (`id_sku`) REFERENCES `inventori_barang` (`id_sku`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================================
-- FIX: Update nama_barang yang kosong di inventori_barang
-- Mengambil nama dari master_barang lewat chain join
-- ============================================================
UPDATE `inventori_barang` ib
JOIN `master_jenis` mj ON ib.id_jenis = mj.id_jenis
JOIN `master_merek` mm ON mj.id_merek = mm.id_merek
JOIN `master_barang` mb ON mm.id_barang = mb.id_barang
SET ib.nama_barang = mb.nama_barang
WHERE ib.nama_barang = '' OR ib.nama_barang IS NULL;
