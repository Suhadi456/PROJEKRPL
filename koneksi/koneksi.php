<?php
// =============================================================
// KONFIGURASI KONEKSI DATABASE
// Menggunakan Environment Variable (untuk Railway)
// =============================================================

$DB_HOST = $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?: 'mysql.railway.internal';
$DB_USER = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?: 'root';
$DB_PASS = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?: '';
$DB_NAME = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?: 'railway';

// ★ PASTIKAN: mysqli (bukan mysql)
$koneksi = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($koneksi->connect_error) {
    die('<div style="padding:20px;background:#fee;color:#c00;font-family:sans-serif">
        <h3>❌ Koneksi Database Gagal</h3>
        <p>' . $koneksi->connect_error . '</p>
        <p>Pastikan environment variable database sudah diset dengan benar.</p>
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
        'dipesan'    => '<span class="badge badge-amber">Dipesan</span>',
        'terjual'    => '<span class="badge badge-gray">Terjual</span>',
        'aktif'      => '<span class="badge badge-blue">Aktif</span>',
    ];
    return $map[$status] ?? '<span class="badge">' . htmlspecialchars($status) . '</span>';
}
?>
