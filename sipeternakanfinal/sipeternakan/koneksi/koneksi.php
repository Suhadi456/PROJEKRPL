<?php
// =============================================================
// KONFIGURASI KONEKSI DATABASE
// Sistem Informasi Manajemen Peternakan Sapi v2.0
// =============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_sipeternakan');

$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($koneksi->connect_error) {
    die('<div style="padding:20px;background:#fee;color:#c00;font-family:sans-serif">
        <h3>❌ Koneksi Database Gagal</h3>
        <p>' . $koneksi->connect_error . '</p>
        <p>Pastikan MySQL berjalan dan database <b>db_sipeternakan</b> sudah diimport.</p>
    </div>');
}

$koneksi->set_charset('utf8mb4');

// Helper: format Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Helper: sanitize input
function sanitize($koneksi, $data) {
    return $koneksi->real_escape_string(htmlspecialchars(trim($data)));
}

// Helper: cek role session
function requireLogin($redirect = '../login.php') {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $redirect);
        exit;
    }
}

function requireRole($role, $redirect = '../login.php') {
    requireLogin($redirect);
    if ($_SESSION['role'] !== $role) {
        header('Location: ' . $redirect);
        exit;
    }
}

// Label status sapi
function statusSapiLabel($status) {
    $map = [
        'digemukkan' => '<span class="badge badge-blue">Digemukkan</span>',
        'siap_jual'  => '<span class="badge badge-green">Siap Jual</span>',
        'dipesan'    => '<span class="badge badge-yellow">Dipesan</span>',
        'terjual'    => '<span class="badge badge-gray">Terjual</span>',
        // backward compat
        'aktif'      => '<span class="badge badge-blue">Aktif</span>',
    ];
    return $map[$status] ?? '<span class="badge">' . htmlspecialchars($status) . '</span>';
}
