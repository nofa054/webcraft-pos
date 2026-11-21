-- ============================================================
-- Webcraft POS - Database Schema & Seed Data
-- Target: MySQL 8.0+ / MariaDB 10.4+
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `sale_items`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `store_settings`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tabel Pengguna (Users)
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Pengaturan Toko (Store Settings)
CREATE TABLE `store_settings` (
    `id` INT UNSIGNED PRIMARY KEY DEFAULT 1,
    `store_name` VARCHAR(150) NOT NULL DEFAULT 'Webcraft POS UMKM',
    `store_tagline` VARCHAR(200) DEFAULT 'Solusi Kasir & Inventori Terpadu',
    `address` VARCHAR(500) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `logo_path` VARCHAR(255) DEFAULT NULL,
    `receipt_header` VARCHAR(500) DEFAULT NULL,
    `receipt_footer` VARCHAR(500) DEFAULT 'Terima kasih telah berbelanja di toko kami! Barang yang sudah dibeli tidak dapat ditukar/dikembalikan kecuali ada perjanjian.',
    `show_logo_on_receipt` TINYINT(1) NOT NULL DEFAULT 1,
    `show_cashier_on_receipt` TINYINT(1) NOT NULL DEFAULT 1,
    `show_customer_on_receipt` TINYINT(1) NOT NULL DEFAULT 1,
    `receipt_paper_width` ENUM('58mm', '80mm') NOT NULL DEFAULT '80mm',
    `currency_symbol` VARCHAR(10) NOT NULL DEFAULT 'Rp',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Kategori Produk (Categories)
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Supplier / Pemasok (Suppliers)
CREATE TABLE `suppliers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(30) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `contact_person` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `address` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel Pelanggan (Customers)
CREATE TABLE `customers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(30) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `address` VARCHAR(500) DEFAULT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel Produk (Products)
CREATE TABLE `products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `supplier_id` INT UNSIGNED DEFAULT NULL,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `barcode` VARCHAR(100) DEFAULT NULL,
    `name` VARCHAR(180) NOT NULL,
    `unit` VARCHAR(30) NOT NULL DEFAULT 'pcs',
    `cost_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `selling_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `stock` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `min_stock` DECIMAL(15,2) NOT NULL DEFAULT 5.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_products_barcode` (`barcode`),
    INDEX `idx_products_name` (`name`),
    CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabel Transaksi Penjualan (Sales)
CREATE TABLE `sales` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_no` VARCHAR(40) NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NOT NULL,
    `customer_id` INT UNSIGNED DEFAULT NULL,
    `transaction_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `discount_type` ENUM('nominal', 'percent') NOT NULL DEFAULT 'nominal',
    `discount_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `grand_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `gross_profit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `payment_method` ENUM('tunai', 'transfer', 'qris') NOT NULL DEFAULT 'tunai',
    `payment_reference` VARCHAR(100) DEFAULT NULL,
    `amount_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `change_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sales_date` (`transaction_date`),
    CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabel Detail Item Penjualan (Sale Items)
CREATE TABLE `sale_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sale_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `product_sku` VARCHAR(50) NOT NULL,
    `product_name` VARCHAR(180) NOT NULL,
    `unit` VARCHAR(30) NOT NULL DEFAULT 'pcs',
    `qty` DECIMAL(15,2) NOT NULL DEFAULT 1.00,
    `cost_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `selling_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `profit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sale_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabel Riwayat Pergerakan Stok (Stock Movements)
CREATE TABLE `stock_movements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `supplier_id` INT UNSIGNED DEFAULT NULL,
    `movement_type` ENUM('in', 'out', 'adjustment', 'sale') NOT NULL,
    `reference_no` VARCHAR(60) DEFAULT NULL,
    `qty_change` DECIMAL(15,2) NOT NULL,
    `stock_before` DECIMAL(15,2) NOT NULL,
    `stock_after` DECIMAL(15,2) NOT NULL,
    `unit_cost` DECIMAL(15,2) DEFAULT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_stock_movements_date` (`created_at`),
    INDEX `idx_stock_movements_type` (`movement_type`),
    CONSTRAINT `fk_stock_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_movements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_movements_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA AWAL (SEEDER)
-- Password default:
--   admin -> admin123
--   kasir -> kasir123
-- ============================================================

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password_hash`, `role`, `is_active`) VALUES
(1, 'Administrator Toko', 'admin', 'admin@webcraftpos.local', '$2y$12$bIzPAo/ZUY.vwb6jEmynFeSKHxHigPMX7jXAJXJs0luUltrNYRMwC', 'admin', 1),
(2, 'Siti Rahma (Kasir)', 'kasir', 'kasir@webcraftpos.local', '$2y$12$LjoBSxqOlxvJEdWoSKmxzuR4YXmOViJ5u3mzRLiYjOqXNULV6i9lK', 'kasir', 1);

INSERT INTO `store_settings` (`id`, `store_name`, `store_tagline`, `address`, `phone`, `email`, `receipt_header`, `receipt_footer`, `show_logo_on_receipt`, `show_cashier_on_receipt`, `show_customer_on_receipt`, `receipt_paper_width`, `currency_symbol`) VALUES
(1, 'Toko Maju Jaya Mandiri', 'Retail, Sembako, Bahan Bangunan & Kebutuhan Harian', 'Jl. Merdeka Raya No. 88, Kec. Sukamaju, Kota Bandung', '0812-3456-7890', 'info@majujaya.co.id', 'Melayani Eceran & Grosir dengan Harga Bersahabat', 'Terima kasih atas kunjungan Anda! Simpan struk ini sebagai bukti pembayaran yang sah.', 1, 1, 1, '80mm', 'Rp');

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Sembako & Bahan Pokok', 'Beras, minyak goreng, gula, tepung, dan kebutuhan pokok'),
(2, 'Minuman & Kemasan', 'Air mineral, kopi, teh, susu, dan minuman ringan'),
(3, 'Alat & Bahan Bangunan', 'Semen, cat, paku, kuas, dan perlengkapan pertukangan'),
(4, 'Pakaian & Tekstil', 'Kaos, kemeja, celana, dan perlengkapan sandang'),
(5, 'Pakan & Peternakan', 'Pakan ayam, pakan ikan, vitamin ternak'),
(6, 'Kebutuhan Rumah Tangga', 'Sabun, deterjen, tisu, dan alat kebersihan');

INSERT INTO `suppliers` (`id`, `code`, `name`, `contact_person`, `phone`, `email`, `address`) VALUES
(1, 'SUP-001', 'PT Sumber Pangan Nusantara', 'Budi Santoso', '0811-2233-4455', 'sales@sumberpangan.co.id', 'Kawasan Industri Jababeka Blok C-12, Bekasi'),
(2, 'SUP-002', 'CV Mitra Bangunan Perkasa', 'Hendra Wijaya', '0813-9876-5432', 'order@mitrabangunan.id', 'Jl. Soekarno Hatta No. 412, Bandung'),
(3, 'SUP-003', 'UD Agro Pakan Makmur', 'Dewi Lestari', '0857-1122-3344', 'agropakan@gmail.com', 'Jl. Raya Lembang KM 14, Bandung Barat');

INSERT INTO `customers` (`id`, `code`, `name`, `phone`, `email`, `address`, `notes`) VALUES
(1, 'CUST-001', 'Pelanggan Umum', '-', '-', '-', 'Pelanggan walk-in tanpa akun khusus'),
(2, 'CUST-002', 'Warung Bu Haji Aminah', '0812-8899-7766', 'aminah.warung@gmail.com', 'Jl. Melati No. 14, RT 03/05', 'Pelanggan grosir mingguan'),
(3, 'CUST-003', 'Bapak Dedi Kontraktor', '0819-3344-5566', 'dedi.karya@yahoo.com', 'Komp. Griya Asri Blok B4', 'Pembelian rutin material bangunan');

INSERT INTO `products` (`id`, `category_id`, `supplier_id`, `sku`, `barcode`, `name`, `unit`, `cost_price`, `selling_price`, `stock`, `min_stock`, `is_active`) VALUES
(1, 1, 1, 'SMB-001', '8991002101012', 'Beras Premium Pandan Wangi 5kg', 'sak', 62000.00, 72500.00, 45.00, 10.00, 1),
(2, 1, 1, 'SMB-002', '8991002101029', 'Minyak Goreng Sawit Tropical 2 Liter', 'pch', 31000.00, 36500.00, 60.00, 15.00, 1),
(3, 1, 1, 'SMB-003', '8991002101036', 'Gula Pasir Kristal Putih 1kg', 'kg', 14500.00, 17500.00, 8.00, 20.00, 1),
(4, 2, 1, 'MNM-001', '8992761136017', 'Air Mineral Aqua Botol 600ml', 'btl', 2800.00, 4000.00, 120.00, 24.00, 1),
(5, 2, 1, 'MNM-002', '8992761136024', 'Kopi Kapal Api Special Mix (Renceng 10x24g)', 'rcg', 11500.00, 14500.00, 35.00, 10.00, 1),
(6, 3, 2, 'BGN-001', '8993004400105', 'Semen Tiga Roda 50kg', 'sak', 58000.00, 66000.00, 25.00, 10.00, 1),
(7, 3, 2, 'BGN-002', '8993004400112', 'Cat Tembok Dulux Catylac Putih 5kg', 'klg', 135000.00, 158000.00, 4.00, 5.00, 1),
(8, 3, 2, 'BGN-003', '8993004400129', 'Paku Kayu 5cm Kualitas Super', 'kg', 16000.00, 22000.00, 18.00, 5.00, 1),
(9, 4, NULL, 'PKN-001', '8994005500201', 'Kaos Polos Cotton Combed 30s Hitam XL', 'pcs', 32000.00, 55000.00, 22.00, 5.00, 1),
(10, 5, 3, 'PKT-001', '8995006600309', 'Pakan Ayam Broiler Starter BR-1', 'kg', 9200.00, 11500.00, 3.00, 25.00, 1),
(11, 5, 3, 'PKT-002', '8995006600316', 'Pakan Ikan Lele Hi-Pro-Vite 781', 'kg', 10500.00, 13000.00, 50.00, 15.00, 1),
(12, 6, 1, 'RMT-001', '8996007700407', 'Deterjen Bubuk Rinso Anti Noda 770g', 'bks', 17500.00, 21500.00, 28.00, 8.00, 1);
