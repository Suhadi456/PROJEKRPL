<?php
// =============================================================
// KONFIGURASI KONEKSI DATABASE
// Sistem Informasi Manajemen Peternakan Sapi
// =============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_sipeternakan');

$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($koneksi->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Koneksi database gagal: ' . $koneksi->connect_error]));
}

$koneksi->set_charset('utf8mb4');

// Helper: format Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Helper: sanitize input
function sanitize($koneksi, $data) {
    return $koneksi->real_escape_string(htmlspecialchars(trim($data)));
}
?>
