<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: user/dashboard.php');
    exit;
}

include 'koneksi/koneksi.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = sanitize($koneksi, $_POST['nama'] ?? '');
    $username = sanitize($koneksi, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';
    $no_hp    = sanitize($koneksi, $_POST['no_hp'] ?? '');

    if (!$nama || !$username || !$password || !$konfirmasi) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,50}$/', $username)) {
        $error = 'Username hanya boleh huruf, angka, underscore (min. 4 karakter).';
    } else {
        $cek = $koneksi->query("SELECT id FROM akun WHERE username='$username' LIMIT 1");
        if ($cek && $cek->num_rows > 0) {
            $error = 'Username sudah digunakan. Pilih username lain.';
        } else {
            $koneksi->query("INSERT INTO akun (username, password, nama, no_hp, role) VALUES ('$username', '$password', '$nama', '$no_hp', 'user')");
            $success = 'Akun berhasil dibuat! Silakan login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — SiPeternakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #cad3489c 0%, #b9b62e 50%, #dde8609c 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; padding: 20px 0; }
        .card { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.4); width: 100%; max-width: 440px; }
        .card-header { background: #636318; color: #fff; border-radius: 16px 16px 0 0 !important; text-align: center; padding: 24px; }
        .card-header h1 { font-size: 1.6rem; font-weight: 800; margin: 0; }
        .btn-daftar { background: linear-gradient(135deg,#f9e45b,#d4af37); color: #3e2a00; border: none; width: 100%; padding: 12px; font-weight: 700; border-radius: 8px; }
        .btn-daftar:hover { background: linear-gradient(135deg,#ffe066,#c9a227); color: #3e2a00; }
        .form-control:focus { border-color: #d4af37; box-shadow: 0 0 0 3px rgba(212,175,55,.2); }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <span style="font-size:2rem">🐄</span>
        <h1>Daftar Akun</h1>
        <p class="mb-0 opacity-75" style="font-size:.9rem">SiPeternakan — Buat akun pembeli</p>
    </div>
    <div class="card-body p-4">
        <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success py-2">
            <?= htmlspecialchars($success) ?>
            <a href="login.php" class="fw-bold ms-2">Login Sekarang →</a>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control" placeholder="Nama lengkap Anda"
                       value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control" placeholder="Buat username unik"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                <div class="form-text">Min. 4 karakter, huruf/angka/underscore</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">No. HP</label>
                <input type="text" name="no_hp" class="form-control" placeholder="08xxxx (opsional)"
                       value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                <input type="password" name="konfirmasi" class="form-control" placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn-daftar">Buat Akun</button>
        </form>

        <div class="text-center mt-3" style="font-size:.9rem">
            Sudah punya akun? <a href="login.php" class="fw-semibold text-warning">Login di sini</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
