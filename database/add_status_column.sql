-- Add status column to orders table
ALTER TABLE `orders` ADD `status` ENUM('belum_dibayar', 'sudah_dibayar', 'pending', 'dibatalkan') DEFAULT 'belum_dibayar' AFTER `total_harga`;
