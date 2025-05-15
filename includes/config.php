<?php
// config.php - Konfigurasi database dan fungsi-fungsi umum

// Definisikan base URL
define('BASE_URL', 'http://192.168.1.33:4141/');

// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Vol1432');
define('DB_NAME', 'digital_printing');

// Koneksi database
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    return $conn;
}

// Fungsi untuk membersihkan input
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk mendapatkan cabang aktif
function getCabangAktif() {
    if (isset($_SESSION['user']) && isset($_SESSION['cabang'])) {
        return $_SESSION['cabang'];
    }
    return null;
}

// Fungsi untuk memeriksa login
function checkLogin() {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
}

// Fungsi untuk update stok bahan
function updateStok($id_bahan, $jumlah, $conn) {
    $sql = "UPDATE bahan SET stok = stok - ? WHERE id_bahan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $jumlah, $id_bahan);
    return $stmt->execute();
}

// Fungsi untuk format angka ke rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Mulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
