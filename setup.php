<?php
// File: setup.php
// Tujuan: Membuat tabel untuk sistem RestoFlow.

header('Content-Type: text/html; charset=utf-8');
require 'db.php'; // Menggunakan koneksi dari db.php

echo "<h1>Setup Database RestoFlow</h1>";

// Fungsi untuk eksekusi query dan menampilkan status
function execute_query($conn, $sql, $message) {
    if (mysqli_query($conn, $sql)) {
        echo "<p style='color:green;'>✓ Berhasil: $message</p>";
    } else {
        echo "<p style='color:red;'>✗ Gagal: $message. Error: " . mysqli_error($conn) . "</p>";
    }
}

// Menghapus tabel lama jika ada
$drop_tables_sql = "
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `payments`, `order_details`, `orders`, `menu`, `tables`, `users`;
SET FOREIGN_KEY_CHECKS = 1;
";
echo "<h2>Menghapus tabel lama...</h2>";
if (mysqli_multi_query($conn, $drop_tables_sql)) {
    // Membersihkan hasil query sebelumnya
    while (mysqli_more_results($conn) && mysqli_next_result($conn)) {;}
    echo "<p style='color:green;'>✓ Tabel lama berhasil dihapus.</p>";
}

echo "<h2>Membuat struktur tabel baru...</h2>";

// ### TABEL BARU: Users ###
$sql_users = "
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','kasir','koki') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
execute_query($conn, $sql_users, "Membuat tabel 'users'");

// Tabel Meja (Dt-1)
$sql_tables = "
CREATE TABLE `tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(50) NOT NULL,
  `status` enum('Tersedia','Terpakai') NOT NULL DEFAULT 'Tersedia',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
execute_query($conn, $sql_tables, "Membuat tabel 'tables'");

// Tabel Menu (Dt-2)
$sql_menu = "
CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 10,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
execute_query($conn, $sql_menu, "Membuat tabel 'menu'");

// Tabel Pesanan (Dt-3)
// ### PERUBAHAN: Menambahkan status 'Masuk' dan menjadikannya default ###
$sql_orders = "
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Masuk','Diproses','Selesai','Lunas','Batal') NOT NULL DEFAULT 'Masuk',
  `total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`table_id`) REFERENCES `tables`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
execute_query($conn, $sql_orders, "Membuat tabel 'orders'");

// Tabel Detail Pesanan (Dt-4)
$sql_order_details = "
CREATE TABLE `order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_id`) REFERENCES `menu`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
execute_query($conn, $sql_order_details, "Membuat tabel 'order_details'");

// Tabel Pembayaran (Dt-5)
$sql_payments = "
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `method` varchar(50) DEFAULT 'Tunai',
  `status` enum('Berhasil','Gagal') NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
execute_query($conn, $sql_payments, "Membuat tabel 'payments'");

echo "<h2>Mengisi data awal...</h2>";

// ### DATA BARU: Users ###
$sql_insert_users = "
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin'),
('kasir', '" . password_hash('kasir123', PASSWORD_DEFAULT) . "', 'kasir'),
('koki', '" . password_hash('koki123', PASSWORD_DEFAULT) . "', 'koki');
";
execute_query($conn, $sql_insert_users, "Mengisi data 'users'");

// Data Awal untuk Meja
$sql_insert_tables = "
INSERT INTO `tables` (`id`, `number`, `status`) VALUES
(1, 'Meja 01', 'Tersedia'),
(2, 'Meja 02', 'Tersedia'),
(3, 'Meja 03', 'Tersedia'),
(4, 'Meja 04', 'Tersedia');
";
execute_query($conn, $sql_insert_tables, "Mengisi data 'tables'");

// Data Awal untuk Menu
$sql_insert_menu = "
INSERT INTO `menu` (`id`, `name`, `price`, `category`, `stock`) VALUES
(1, 'Nasi Goreng Spesial', 25000.00, 'Makanan', 20),
(2, 'Ayam Bakar Madu', 35000.00, 'Makanan', 15),
(3, 'Soto Ayam', 20000.00, 'Makanan', 25),
(4, 'Es Teh Manis', 5000.00, 'Minuman', 50),
(5, 'Jus Jeruk', 10000.00, 'Minuman', 30);
";
execute_query($conn, $sql_insert_menu, "Mengisi data 'menu'");

echo "<hr><h2>Setup Selesai!</h2>";
echo "<p>Database dan tabel untuk RestoFlow telah berhasil dibuat. Silakan buka file utama aplikasi.</p>";
echo "<a href='index.html' style='display:inline-block; padding: 10px 20px; background-color: #10B981; color: white; text-decoration: none; border-radius: 8px;'>Buka Aplikasi</a>";

mysqli_close($conn);
?>