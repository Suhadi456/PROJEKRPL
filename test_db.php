<?php
$host = 'mysql.railway.internal';
$user = 'root';
$pass = 'pOTPLapuqYhMmdBFwctNsuOEVWUycYHs'; // Password dari screenshot
$db   = 'railway';

echo "Mencoba koneksi ke database...<br>";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("❌ Koneksi GAGAL: " . $conn->connect_error);
} else {
    echo "✅ Koneksi BERHASIL! Database terhubung.";
    $conn->close();
}
?>
