-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Waktu pembuatan: 29 Des 2025 pada 01.42
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `albatoserba`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `daftar_barang`
--

CREATE TABLE `daftar_barang` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `sales_id` int(11) DEFAULT NULL,
  `gudang_id` int(11) DEFAULT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `harga_ambil` decimal(10,2) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `barcode` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `daftar_barang`
--

INSERT INTO `daftar_barang` (`id`, `order_id`, `supplier_id`, `sales_id`, `gudang_id`, `nama_barang`, `harga_ambil`, `qty`, `subtotal`, `gambar`, `barcode`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, 'nestle', 9000.00, 12, 108000.00, '1765632514_Fruit Tea 350ml (2).png', '', '2025-12-14 06:18:44', '2025-12-15 11:17:09'),
(3, NULL, 1, 1, NULL, 'dancow', 3000.00, 30, NULL, '1765633639_IMG-20250704-WA0022.jpg', '', '2025-12-14 06:18:44', '2025-12-15 11:17:09'),
(4, 2, 1, 2, NULL, 'nestle4321', 4111.00, 42, 172662.00, '1765633707_NU Teh Susu (3).png', '', '2025-12-23 06:01:07', '2025-12-23 06:01:07'),
(5, NULL, 1, 2, NULL, 'susuy123', 111.00, 11, NULL, '1765634073_NU Green Tea 500ml (3).png', '', '2025-12-14 06:18:44', '2025-12-15 11:17:09'),
(6, 3, 2, 3, NULL, 'EXTRA JIZZZ', 9000.00, 90, 810000.00, '1766543067_vanilla_750gr.webp', '', '2025-12-24 02:24:27', '2025-12-24 02:24:27'),
(7, NULL, 2, 3, NULL, 'flashdisk', 9000.00, 900, NULL, '1765636034_Javana (1).png', '', '2025-12-14 06:18:44', '2025-12-15 11:17:09'),
(16, NULL, 2, 3, NULL, 'softex', 8000.00, 88, 704000.00, '1766543220_whatsapp_image_2025-10-26_at_12_55_08_84dff0ee.webp', '1435424', '2025-12-24 02:27:00', '2025-12-24 02:27:00'),
(17, NULL, 1, 2, 4, 'paseo', 4000.00, 66, 264000.00, '1766570550_poster_klaim.webp', '14354444', '2025-12-24 10:02:30', '2025-12-24 10:02:30'),
(21, NULL, NULL, NULL, NULL, 'vaseline', 3000.00, NULL, NULL, '1766038676_69439c943e2eb.webp', '14354564', '2025-12-18 06:17:56', '2025-12-18 06:17:56'),
(22, NULL, NULL, NULL, NULL, 'vaseline', 1000.00, NULL, NULL, '1766113567_6944c11fd9871.webp', '314', '2025-12-19 03:06:08', '2025-12-19 03:06:08'),
(23, NULL, NULL, NULL, NULL, 'nivea', 1000.00, NULL, NULL, '1766209926_694639860a10e.webp', '14354564', '2025-12-20 05:52:06', '2025-12-20 05:52:06'),
(24, NULL, NULL, NULL, NULL, 'vaseline', 3000.00, NULL, NULL, '1766210364_69463b3c890c9.webp', '31', '2025-12-20 05:59:26', '2025-12-20 05:59:26'),
(25, NULL, NULL, 15, NULL, 'vaseline', 1000.00, NULL, NULL, '1766210489_69463bb94d2e7.webp', '14354564', '2025-12-20 06:01:29', '2025-12-20 06:01:29'),
(26, NULL, NULL, NULL, NULL, 'lactogrow', 1000.00, NULL, NULL, '1766211046_69463de6cffa2.webp', '5446546515621', '2025-12-20 06:10:46', '2025-12-20 06:10:46'),
(28, 2, 1, 2, NULL, 'niveadd', 3000.00, 124, 372000.00, '1766570571_differences-between-html-and-html5.webp', '', '2025-12-24 10:02:51', '2025-12-24 10:02:51'),
(33, NULL, NULL, NULL, NULL, 'vaseline123', 1232.00, NULL, NULL, '1766674831_694d518f04b6d.webp', '123', '2025-12-25 15:00:31', '2025-12-25 15:00:31'),
(34, NULL, NULL, NULL, NULL, 'vaseline123', 123000.00, NULL, NULL, '1766675102_694d529e36bfe.webp', '123', '2025-12-25 15:05:02', '2025-12-25 15:05:02'),
(35, NULL, NULL, NULL, NULL, 'vaseline123', 123000.00, NULL, NULL, '1766675316_694d5374da7af.webp', '123', '2025-12-25 15:08:37', '2025-12-25 15:08:37'),
(36, NULL, NULL, NULL, NULL, 'vaseline123', 123000.00, NULL, NULL, '1766675649_694d54c18f5e6.png', '123', '2025-12-25 15:14:09', '2025-12-25 15:14:09'),
(37, NULL, NULL, NULL, 16, 'vaseline123', 321.00, NULL, NULL, '1766675734_694d55169ca80.png', '14354564', '2025-12-25 15:15:34', '2025-12-25 15:15:34'),
(38, NULL, NULL, NULL, NULL, 'vaseline1234', 1234000.00, NULL, NULL, '1766682667_694d702b6eed0.jpg', '1234', '2025-12-25 17:11:07', '2025-12-25 17:11:07'),
(39, NULL, 2, 3, 18, 'vaseline1234', 321000.00, NULL, NULL, '1766682877_694d70fdd4245.png', '31', '2025-12-25 17:14:37', '2025-12-25 17:14:37'),
(40, NULL, 27, 31, 19, 'nivea3', 1000.00, NULL, NULL, '1766683655_694d7407b5dda.png', '31', '2025-12-25 17:27:36', '2025-12-25 17:27:36'),
(41, NULL, 28, 32, 20, 'Telur', 2000.00, NULL, NULL, '1766684613_694d77c540bda.png', '20', '2025-12-25 17:43:33', '2025-12-25 17:43:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `gudang`
--

CREATE TABLE `gudang` (
  `id` int(11) NOT NULL,
  `sektor_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) NOT NULL,
  `sales_id` int(11) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `qty` int(11) DEFAULT 0,
  `harga_ambil` decimal(10,2) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expired_date` date DEFAULT NULL,
  `max_order` int(11) DEFAULT 0,
  `harga_jual` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `gudang`
--

INSERT INTO `gudang` (`id`, `sektor_id`, `supplier_id`, `sales_id`, `nama_barang`, `deskripsi`, `qty`, `harga_ambil`, `barcode`, `gambar`, `created_at`, `updated_at`, `expired_date`, `max_order`, `harga_jual`) VALUES
(4, 2, 0, 0, 'paseo', '', 200, 4000.00, '14354444', '1765713401_693ea5f93da70.webp', '2025-12-14 10:52:35', '2025-12-25 14:56:31', NULL, 0, 0.00),
(16, 1, 13, 16, 'vaseline123', 'd', 118, 321000.00, '143545', '1766675734_694d55169ca80.png', '2025-12-25 15:15:34', '2025-12-25 17:07:09', '2026-01-31', 30, 2000000.00),
(18, 1, 2, 3, 'vaseline1234', '', 50, 321000.00, '31', '1766682877_694d70fdd4245.png', '2025-12-25 17:14:37', '2025-12-25 17:14:37', NULL, 0, 0.00),
(19, 1, 27, 31, 'nivea3', '', 300, 1000.00, '31', '1766683655_694d7407b5dda.png', '2025-12-25 17:27:36', '2025-12-25 17:28:25', '2026-01-31', 20, 0.00),
(20, 1, 28, 32, 'Telur', '', 30, 2000.00, '20', '1766684613_694d77c540bda.png', '2025-12-25 17:43:33', '2025-12-25 17:43:33', '2025-12-31', 20, 0.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `gudang_lama`
--

CREATE TABLE `gudang_lama` (
  `id` int(11) NOT NULL,
  `nama_sektor` varchar(255) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `qty` int(11) DEFAULT 0,
  `barcode` varchar(255) DEFAULT NULL,
  `harga_ambil` decimal(10,2) DEFAULT NULL,
  `gambar` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `gudang_pecahon`
--

CREATE TABLE `gudang_pecahon` (
  `id` int(11) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `harga_ambil` decimal(10,2) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `supplier_id` int(11) DEFAULT NULL,
  `sales_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `gudang_pecahon`
--

INSERT INTO `gudang_pecahon` (`id`, `nama_barang`, `deskripsi`, `qty`, `harga_ambil`, `barcode`, `gambar`, `created_at`, `updated_at`, `supplier_id`, `sales_id`) VALUES
(1, 'lactogrow13', NULL, 29, 3000.00, '31', '1765782429_693fb39de25ae.webp', '2025-12-14 11:42:30', '2025-12-15 07:07:10', NULL, NULL),
(11, 'vaseline', NULL, 22, 1000.00, '14354561', '693fe9b8c77c1.webp', '2025-12-15 10:58:00', '2025-12-15 10:58:00', 1, 2),
(13, 'niveaqd', NULL, 142, 3000.00, '3114', '1766676383_34.png', '2025-12-15 11:00:05', '2025-12-25 15:30:26', 2, 3);

-- --------------------------------------------------------

--
-- Struktur dari tabel `jenis_kunjungan`
--

CREATE TABLE `jenis_kunjungan` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_jenis` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jenis_kunjungan`
--

INSERT INTO `jenis_kunjungan` (`id`, `nama_jenis`, `created_at`) VALUES
(9, 'Hari', '2025-12-22 16:10:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `sales_id` int(11) NOT NULL,
  `tanggal_order` datetime DEFAULT current_timestamp(),
  `catatan` text DEFAULT NULL,
  `total_harga` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `sales_id`, `tanggal_order`, `catatan`, `total_harga`) VALUES
(1, 1, '2025-12-13 20:28:34', NULL, 108000.00),
(2, 2, '2025-12-13 20:48:27', NULL, 544662.00),
(3, 3, '2025-12-13 21:04:14', NULL, 1795000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `returs`
--

CREATE TABLE `returs` (
  `id` int(11) NOT NULL,
  `daftar_barang_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `alasan` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `qty` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_kunjungan`
--

CREATE TABLE `riwayat_kunjungan` (
  `id` int(11) NOT NULL,
  `sales_id` int(11) NOT NULL,
  `tanggal_kunjungan` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `riwayat_kunjungan`
--

INSERT INTO `riwayat_kunjungan` (`id`, `sales_id`, `tanggal_kunjungan`) VALUES
(4, 3, '2025-12-15'),
(5, 3, '2025-12-15'),
(6, 3, '2025-12-15'),
(7, 3, '2025-12-15'),
(8, 3, '2025-12-15'),
(9, 3, '2025-12-15'),
(59, 12, '2025-12-20'),
(60, 12, '2025-12-20'),
(61, 12, '2025-12-20'),
(62, 12, '2025-12-20'),
(63, 12, '2025-12-20'),
(64, 12, '2025-12-20'),
(68, 15, '2025-12-20'),
(72, 3, '2025-12-24'),
(73, 3, '2025-12-24'),
(74, 3, '2025-12-24'),
(75, 3, '2025-12-24'),
(76, 3, '2025-12-24'),
(77, 3, '2025-12-24'),
(78, 3, '2025-12-24'),
(79, 3, '2025-12-24'),
(80, 23, '2025-12-24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_order`
--

CREATE TABLE `riwayat_order` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_harga` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `riwayat_order`
--

INSERT INTO `riwayat_order` (`id`, `order_id`, `created_at`, `total_harga`) VALUES
(15, 3, '2025-12-24 08:17:10', 455004.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_order_detail`
--

CREATE TABLE `riwayat_order_detail` (
  `id` int(11) NOT NULL,
  `riwayat_order_id` int(11) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `harga` decimal(10,2) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `riwayat_order_detail`
--

INSERT INTO `riwayat_order_detail` (`id`, `riwayat_order_id`, `nama_barang`, `harga`, `qty`, `subtotal`, `gambar`, `satuan`, `created_at`) VALUES
(16, 15, 'EXTRA JIZZZ', 9000.00, 1, 9000.00, '1766543067_vanilla_750gr.webp', 'P', '2025-12-24 08:17:10'),
(17, 15, 'flashdisk', 9000.00, 2, 18000.00, '1765636034_Javana (1).png', 'P', '2025-12-24 08:17:10'),
(18, 15, 'lactogrow1333', 1000.00, 3, 3000.00, '1766543104_whatsapp_image_2025-06-05_at_15_50_38.webp', 'P', '2025-12-24 08:17:10'),
(19, 15, 'lactogrow323', 30001.00, 4, 120004.00, '1766543046_vanilla_750gr.webp', 'P', '2025-12-24 08:17:10'),
(20, 15, 'softex', 8000.00, 5, 40000.00, '1766543220_whatsapp_image_2025-10-26_at_12_55_08_84dff0ee.webp', 'P', '2025-12-24 08:17:10'),
(21, 15, 'vaseline', 3000.00, 6, 18000.00, '1765759835_693f5b5b0e657.webp', 'P', '2025-12-24 08:17:10'),
(22, 15, 'vaseline11111', 1000.00, 7, 7000.00, '1766543158_whatsapp_image_2025-12-19_at_21_41_39.webp', 'P', '2025-12-24 08:17:10'),
(23, 15, 'vaseline3333', 30000.00, 8, 240000.00, '1766540453_id-11134207-7qul6-lk3ujpyrusyq2f.jpg', 'P', '2025-12-24 08:17:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `nama_sales` varchar(255) NOT NULL,
  `perusahaan` varchar(255) DEFAULT NULL,
  `kontak` varchar(255) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `jenis_kunjungan_id` int(11) NOT NULL,
  `kunjungan` varchar(255) DEFAULT NULL,
  `interval_kunjungan` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sales`
--

INSERT INTO `sales` (`id`, `nama_sales`, `perusahaan`, `kontak`, `supplier_id`, `jenis_kunjungan_id`, `kunjungan`, `interval_kunjungan`) VALUES
(1, 'susi', 'pt mitra kencana', '02', 1, 9, 'Hari', 1),
(2, 'nuri', 'pt mitra kencana', '98247', 1, 9, 'Hari', 1),
(3, 'ANIS', 'cv indah permata', '9090', 2, 9, '0', 2),
(12, 'POS', 'pt mitra kencana', '0857', 1, 9, 'Hari', 2),
(15, 'farff', 'PT AYUE', '', NULL, 9, 'Hari', 7),
(16, 'iya', 'pt yuuuuuu', '', 13, 9, 'Hari', 7),
(23, 'yono1', 'cv indah permata', '08571', 2, 9, '0', 7),
(31, '666', '666', '', 27, 1, NULL, 7),
(32, '20', '20', '', 28, 1, NULL, 7);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sektor`
--

CREATE TABLE `sektor` (
  `id` int(11) NOT NULL,
  `nama_sektor` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sektor`
--

INSERT INTO `sektor` (`id`, `nama_sektor`, `deskripsi`, `tanggal_update`) VALUES
(1, 'ABDU', 'jajan', '2025-12-14 06:44:04'),
(2, 'MUS', 'mie', '2025-12-14 10:51:56'),
(3, 'MUHYI', 'SABUN', '2025-12-15 00:52:00'),
(4, 'MAHI', 'SERANGGA', '2025-12-25 09:26:46'),
(5, 'MUSd', 's', '2025-12-25 09:29:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `supplier`
--

CREATE TABLE `supplier` (
  `id` int(11) NOT NULL,
  `nama_supplier` varchar(255) NOT NULL,
  `kontak` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `supplier`
--

INSERT INTO `supplier` (`id`, `nama_supplier`, `kontak`, `alamat`, `created_at`) VALUES
(1, 'pt mitra kencana', '928', 'bintoro', '2025-12-13 13:27:55'),
(2, 'cv indah permata', '0294', 'lampung', '2025-12-13 14:03:18'),
(13, 'pt yuuuuuu', NULL, NULL, '2025-12-20 06:05:19'),
(27, '666', NULL, NULL, '2025-12-25 17:26:10'),
(28, '20', NULL, NULL, '2025-12-25 17:41:34');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `daftar_barang`
--
ALTER TABLE `daftar_barang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_barang_order` (`order_id`),
  ADD KEY `fk_barang_supplier` (`supplier_id`),
  ADD KEY `fk_barang_sales` (`sales_id`),
  ADD KEY `fk_barang_gudang` (`gudang_id`);

--
-- Indeks untuk tabel `gudang`
--
ALTER TABLE `gudang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sektor_id` (`sektor_id`);

--
-- Indeks untuk tabel `gudang_lama`
--
ALTER TABLE `gudang_lama`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `gudang_pecahon`
--
ALTER TABLE `gudang_pecahon`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jenis_kunjungan`
--
ALTER TABLE `jenis_kunjungan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_nama_jenis` (`nama_jenis`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_sales` (`sales_id`);

--
-- Indeks untuk tabel `returs`
--
ALTER TABLE `returs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_returs_barang` (`daftar_barang_id`),
  ADD KEY `fk_returs_order` (`order_id`),
  ADD KEY `fk_returs_supplier` (`supplier_id`);

--
-- Indeks untuk tabel `riwayat_kunjungan`
--
ALTER TABLE `riwayat_kunjungan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kunjungan_sales` (`sales_id`);

--
-- Indeks untuk tabel `riwayat_order`
--
ALTER TABLE `riwayat_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_riwayat_order` (`order_id`);

--
-- Indeks untuk tabel `riwayat_order_detail`
--
ALTER TABLE `riwayat_order_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_riwayat_detail` (`riwayat_order_id`);

--
-- Indeks untuk tabel `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sales_supplier` (`supplier_id`);

--
-- Indeks untuk tabel `sektor`
--
ALTER TABLE `sektor`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `daftar_barang`
--
ALTER TABLE `daftar_barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT untuk tabel `gudang`
--
ALTER TABLE `gudang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `gudang_lama`
--
ALTER TABLE `gudang_lama`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `gudang_pecahon`
--
ALTER TABLE `gudang_pecahon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `jenis_kunjungan`
--
ALTER TABLE `jenis_kunjungan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `returs`
--
ALTER TABLE `returs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `riwayat_kunjungan`
--
ALTER TABLE `riwayat_kunjungan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT untuk tabel `riwayat_order`
--
ALTER TABLE `riwayat_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `riwayat_order_detail`
--
ALTER TABLE `riwayat_order_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `sektor`
--
ALTER TABLE `sektor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `daftar_barang`
--
ALTER TABLE `daftar_barang`
  ADD CONSTRAINT `fk_barang_gudang` FOREIGN KEY (`gudang_id`) REFERENCES `gudang` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_barang_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_barang_sales` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_barang_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `gudang`
--
ALTER TABLE `gudang`
  ADD CONSTRAINT `gudang_ibfk_1` FOREIGN KEY (`sektor_id`) REFERENCES `sektor` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_sales` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `returs`
--
ALTER TABLE `returs`
  ADD CONSTRAINT `fk_returs_barang` FOREIGN KEY (`daftar_barang_id`) REFERENCES `daftar_barang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_returs_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_returs_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `riwayat_kunjungan`
--
ALTER TABLE `riwayat_kunjungan`
  ADD CONSTRAINT `fk_kunjungan_sales` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `riwayat_order`
--
ALTER TABLE `riwayat_order`
  ADD CONSTRAINT `fk_riwayat_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `riwayat_order_detail`
--
ALTER TABLE `riwayat_order_detail`
  ADD CONSTRAINT `fk_riwayat_detail` FOREIGN KEY (`riwayat_order_id`) REFERENCES `riwayat_order` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
