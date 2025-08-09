<?php
// File: db.php
// Tujuan: Mengelola koneksi ke database.

$host = 'localhost';
$user = 'root'; // User default XAMPP
$pass = '';     // Password default XAMPP kosong
$db   = 'restoflow_db'; // Ganti nama database menjadi untuk RestoFlow

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi Database Gagal: ' . mysqli_connect_error()]);
    exit();
}

mysqli_set_charset($conn, "utf8mb4");
?>