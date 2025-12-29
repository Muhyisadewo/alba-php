-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Waktu pembuatan: 29 Des 2025 pada 01.55
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

-- --------------------------------------------------------

--
-- Struktur dari tabel `jenis_kunjungan`
--

CREATE TABLE `jenis_kunjungan` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_jenis` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `gudang`
--
ALTER TABLE `gudang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `gudang_lama`
--
ALTER TABLE `gudang_lama`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `gudang_pecahon`
--
ALTER TABLE `gudang_pecahon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jenis_kunjungan`
--
ALTER TABLE `jenis_kunjungan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `riwayat_order_detail`
--
ALTER TABLE `riwayat_order_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sektor`
--
ALTER TABLE `sektor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
