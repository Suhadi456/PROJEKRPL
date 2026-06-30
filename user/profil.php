<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php'); exit;
}
include '../koneksi/koneksi.php';
$uid = intval($_SESSION['user_id']);
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = sanitize($koneksi, $_POST['nama']);
    $hp    = sanitize($koneksi, $_POST['no_hp'] ?? '');
    $alamat= sanitize($koneksi, $_POST['alamat'] ?? '');
    $pw_lama = $_POST['password_lama'] ?? '';
    $pw_baru = $_POST['password_baru'] ?? '';
    $pw_konfirm = $_POST['password_konfirm'] ?? '';

    if (!$nama) {
        $error = 'Nama tidak boleh kosong.';
    } else {
        $koneksi->query("UPDATE akun SET nama='$nama', no_hp='$hp', alamat='$alamat' WHERE id=$uid");
        $_SESSION['nama'] = $nama;

        if ($pw_lama || $pw_baru) {
            $user = $koneksi->query("SELECT password FROM akun WHERE id=$uid LIMIT 1")->fetch_assoc();
            if ($pw_lama !== $user['password']) {
                $error = 'Password lama salah.';
            } elseif (strlen($pw_baru) < 6) {
                $error = 'Password baru minimal 6 karakter.';
            } elseif ($pw_baru !== $pw_konfirm) {
                $error = 'Konfirmasi password tidak cocok.';
            } else {
                $koneksi->query("UPDATE akun SET password='$pw_baru' WHERE id=$uid");
                $msg = 'Profil dan password berhasil diperbarui.';
            }
        } else {
            $msg = 'Profil berhasil diperbarui.';
        }
    }
}

$user = $koneksi->query("SELECT * FROM akun WHERE id=$uid LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil — SiPeternakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{background:#f4f6f9;font-family:'Segoe UI',sans-serif}
        .navbar-brand{font-weight:800;color:#d4af37!important}
        .sidebar-link{display:block;padding:10px 16px;color:#333;text-decoration:none;border-radius:8px;margin-bottom:4px}
        .sidebar-link:hover,.sidebar-link.active{background:#fff9c4;color:#3e2a00;font-weight:600}
        @media(max-width:768px){.sidebar-col{display:none}}
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm px-4">
    <span class="navbar-brand">🐄 SiPeternakan</span>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted" style="font-size:.9rem">👤 <?= htmlspecialchars($_SESSION['nama']) ?></span>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm" onclick="return confirm('Keluar?')">Keluar</a>
    </div>
</nav>
<div class="container-fluid">
<div class="row">
    <div class="col-md-2 sidebar-col shadow-sm pt-3"
     style="background:linear-gradient(180deg,#f9e45b,#d4af37); height:100vh; position:sticky; top:0;">
        <a href="dashboard.php" class="sidebar-link">📊 Dashboard</a>
        <a href="sapi.php" class="sidebar-link">🐄 Katalog Sapi</a>
        <a href="pemesanan.php" class="sidebar-link">📋 Pemesanan Saya</a>
        <a href="pembayaran.php" class="sidebar-link">💳 Riwayat Pembayaran</a>
        <a href="profil.php" class="sidebar-link active">👤 Profil</a>
    </div>
    <div class="col-md-10 p-4">
        <h4 class="fw-bold mb-1">👤 Profil Saya</h4>
        <p class="text-muted mb-4">Kelola informasi akun Anda</p>

        <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold">📝 Edit Profil</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Username</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                                <div class="form-text">Username tidak dapat diubah</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">No. HP</label>
                                <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Alamat</label>
                                <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                            </div>
                            <hr>
                            <p class="fw-semibold mb-2">🔒 Ganti Password <small class="text-muted fw-normal">(Kosongkan jika tidak ingin ganti)</small></p>
                            <div class="mb-3">
                                <label class="form-label">Password Lama</label>
                                <input type="password" name="password_lama" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password_baru" class="form-control" minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" name="password_konfirm" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-warning w-100 fw-bold">💾 Simpan Perubahan</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold">ℹ️ Info Akun</div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div style="width:80px;height:80px;background:#d4af37;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;margin:0 auto">
                                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                            </div>
                            <h5 class="mt-2 mb-0"><?= htmlspecialchars($user['nama']) ?></h5>
                            <span class="badge bg-warning">Pembeli</span>
                        </div>
                        <table class="table table-sm">
                            <tr><td class="text-muted">Username</td><td><strong><?= htmlspecialchars($user['username']) ?></strong></td></tr>
                            <tr><td class="text-muted">No. HP</td><td><?= htmlspecialchars($user['no_hp'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Bergabung</td><td><?= date('d M Y', strtotime($user['created_at'])) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
