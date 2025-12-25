-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 14 Des 2025 pada 06.16
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
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `daftar_barang`
--

INSERT INTO `daftar_barang` (`id`, `order_id`, `supplier_id`, `sales_id`, `gudang_id`, `nama_barang`, `harga_ambil`, `qty`, `subtotal`, `gambar`) VALUES
(1, 1, 1, 1, NULL, 'nestle', 9000.00, 12, 108000.00, '1765632514_Fruit Tea 350ml (2).png'),
(3, NULL, 1, 1, 1, 'dancow', 3000.00, 30, NULL, '1765633639_IMG-20250704-WA0022.jpg'),
(4, 2, 1, 2, NULL, 'nestle4321', 4111.00, 42, 172662.00, '1765633707_NU Teh Susu (3).png'),
(5, NULL, 1, 2, 1, 'susuy123', 111.00, 11, NULL, '1765634073_NU Green Tea 500ml (3).png'),
(6, 3, 2, 3, NULL, 'EXTRA JIZZZ', 9000.00, 90, 810000.00, '1765634654_IMG-20250704-WA0018.jpg'),
(7, NULL, 2, 3, 1, 'flashdisk', 9000.00, 900, NULL, '1765636034_Javana (1).png');

-- --------------------------------------------------------

--
-- Struktur dari tabel `gudang`
--

CREATE TABLE `gudang` (
  `id` int(11) NOT NULL,
  `nama_sektor` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `qty` int(11) DEFAULT 0,
  `barcode` varchar(255) DEFAULT NULL,
  `harga_ambil` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `gudang`
--

INSERT INTO `gudang` (`id`, `nama_sektor`, `deskripsi`, `created_at`, `qty`, `barcode`, `harga_ambil`) VALUES
(1, 'abdu', 'sektor sabun', '2025-12-13 13:23:30', 939, '24241', 24244.00);

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
(2, 2, '2025-12-13 20:48:27', NULL, 172662.00),
(3, 3, '2025-12-13 21:04:14', NULL, 810000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `returs`
--

CREATE TABLE `returs` (
  `id` int(11) NOT NULL,
  `daftar_barang_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `alasan` text DEFAULT NULL
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
(1, 2, '2025-12-13 13:49:44', 4111.00);

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
(1, 1, 'nestle4321', 4111.00, 1, 4111.00, '1765633707_NU Teh Susu (3).png', 'Pcs', '2025-12-13 13:49:44');

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
  `kunjungan` int(11) DEFAULT 0,
  `interval_kunjungan` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sales`
--

INSERT INTO `sales` (`id`, `nama_sales`, `perusahaan`, `kontak`, `supplier_id`, `kunjungan`, `interval_kunjungan`) VALUES
(1, 'susi', 'pt mitra kencana', '02', 1, 0, 1),
(2, 'nuri', 'pt mitra kencana', '98247', 1, 0, 1),
(3, 'ANIS', 'cv indah permata', '09090', 2, 0, 1);

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
(2, 'cv indah permata', '0294', 'lampung', '2025-12-13 14:03:18');

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
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `gudang`
--
ALTER TABLE `gudang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `returs`
--
ALTER TABLE `returs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `riwayat_kunjungan`
--
ALTER TABLE `riwayat_kunjungan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `riwayat_order`
--
ALTER TABLE `riwayat_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `riwayat_order_detail`
--
ALTER TABLE `riwayat_order_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
