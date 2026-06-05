<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit;
}
include '../koneksi/koneksi.php';

$total_user   = $koneksi->query("SELECT COUNT(*) as c FROM akun WHERE role='user'")->fetch_assoc()['c'] ?? 0;
$total_sapi   = $koneksi->query("SELECT COUNT(*) as c FROM sapi")->fetch_assoc()['c'] ?? 0;
$total_pesan  = $koneksi->query("SELECT COUNT(*) as c FROM pemesanan")->fetch_assoc()['c'] ?? 0;
$total_bayar  = $koneksi->query("SELECT COALESCE(SUM(jumlah_bayar),0) as c FROM pembayaran")->fetch_assoc()['c'] ?? 0;

$users = $koneksi->query("SELECT * FROM akun ORDER BY created_at DESC");
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'delete_user') {
        $del = intval($_POST['del_id']);
        if ($del == $_SESSION['user_id']) {
            $error = 'Tidak bisa menghapus akun sendiri.';
        } else {
            $koneksi->query("DELETE FROM akun WHERE id=$del");
            $msg = 'Akun berhasil dihapus.';
            header('Location: dashboard.php'); exit;
        }
    } elseif ($act === 'toggle_role') {
        $tid = intval($_POST['toggle_id']);
        $new_role = sanitize($koneksi, $_POST['new_role']);
        if (in_array($new_role, ['admin','pemilik','user'])) {
            $koneksi->query("UPDATE akun SET role='$new_role' WHERE id=$tid");
            $msg = 'Role berhasil diperbarui.';
            header('Location: dashboard.php'); exit;
        }
    }
}
$users = $koneksi->query("SELECT * FROM akun ORDER BY role, created_at DESC");
$role_badge = ['admin'=>'danger','pemilik'=>'primary','user'=>'success'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — SiPeternakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{background:#f4f6f9;font-family:'Segoe UI',sans-serif}
        .navbar-brand{font-weight:800;color:#1a4a2e!important}
        .sidebar-link{display:block;padding:10px 16px;color:#333;text-decoration:none;border-radius:8px;margin-bottom:4px}
        .sidebar-link:hover,.sidebar-link.active{background:#fce4ec;color:#b71c1c;font-weight:600}
        .stat-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
        .stat-card .value{font-size:2rem;font-weight:800}
        @media(max-width:768px){.sidebar-col{display:none}}
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm px-4">
    <span class="navbar-brand">🐄 SiPeternakan <span class="badge bg-danger" style="font-size:.7rem">ADMIN</span></span>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted" style="font-size:.9rem">👤 <?= htmlspecialchars($_SESSION['nama']) ?></span>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm" onclick="return confirm('Keluar?')">Keluar</a>
    </div>
</nav>
<div class="container-fluid">
<div class="row">
    <div class="col-md-2 sidebar-col bg-white shadow-sm pt-3" style="min-height:100vh">
        <a href="dashboard.php" class="sidebar-link active">🏠 Dashboard Admin</a>
        <a href="../index.php" class="sidebar-link">🐄 Panel Pemilik</a>
        <a href="../logout.php" class="sidebar-link" onclick="return confirm('Keluar?')">🚪 Logout</a>
    </div>
    <div class="col-md-10 p-4">
        <h4 class="fw-bold mb-1">🛡️ Panel Administrator</h4>
        <p class="text-muted mb-4">Kelola seluruh akun dan pantau sistem</p>

        <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3"><div class="stat-card"><div class="value text-danger"><?= $total_user ?></div><div class="text-muted">Total User/Pembeli</div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card"><div class="value" style="color:#1a4a2e"><?= $total_sapi ?></div><div class="text-muted">Total Sapi</div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card"><div class="value text-warning"><?= $total_pesan ?></div><div class="text-muted">Total Pemesanan</div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card"><div class="value text-success" style="font-size:1.2rem"><?= formatRupiah($total_bayar) ?></div><div class="text-muted">Total Pembayaran</div></div></div>
        </div>

        <!-- Manajemen Akun -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">👥 Manajemen Akun</span>
                <span class="badge bg-secondary"><?= $users ? $users->num_rows : 0 ?> akun</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Nama</th><th>Username</th><th>No HP</th><th>Role</th><th>Bergabung</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($users): $no=1; while ($u=$users->fetch_assoc()): ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($u['nama']) ?></strong></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['no_hp'] ?? '-') ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="_action" value="toggle_role">
                                    <input type="hidden" name="toggle_id" value="<?= $u['id'] ?>">
                                    <select name="new_role" class="form-select form-select-sm d-inline" style="width:110px" onchange="this.form.submit()" <?= $u['id']==$_SESSION['user_id']?'disabled':'' ?>>
                                        <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                                        <option value="pemilik" <?= $u['role']==='pemilik'?'selected':'' ?>>Pemilik</option>
                                        <option value="user" <?= $u['role']==='user'?'selected':'' ?>>User</option>
                                    </select>
                                </form>
                            </td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus akun <?= htmlspecialchars($u['username']) ?>?')">
                                    <input type="hidden" name="_action" value="delete_user">
                                    <input type="hidden" name="del_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">🗑️ Hapus</button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted small">Akun saya</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
