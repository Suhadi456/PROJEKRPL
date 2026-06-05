<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php'); exit;
}
include '../koneksi/koneksi.php';
$uid = intval($_SESSION['user_id']);

$total_pesan = $koneksi->query("SELECT COUNT(*) as c FROM pemesanan WHERE id_user=$uid")->fetch_assoc()['c'] ?? 0;
$pesan_pending = $koneksi->query("SELECT COUNT(*) as c FROM pemesanan WHERE id_user=$uid AND status='pending'")->fetch_assoc()['c'] ?? 0;
$pesan_dp = $koneksi->query("SELECT COUNT(*) as c FROM pemesanan WHERE id_user=$uid AND status='dp'")->fetch_assoc()['c'] ?? 0;
$pesan_lunas = $koneksi->query("SELECT COUNT(*) as c FROM pemesanan WHERE id_user=$uid AND status='lunas'")->fetch_assoc()['c'] ?? 0;
$sapi_siap = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='siap_jual'")->fetch_assoc()['c'] ?? 0;

$recent_pesan = $koneksi->query("SELECT pm.*, s.kode_sapi FROM pemesanan pm JOIN sapi s ON pm.id_sapi=s.id WHERE pm.id_user=$uid ORDER BY pm.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SiPeternakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .navbar-brand { font-weight: 800; color: #1a4a2e !important; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .stat-card .value { font-size: 2rem; font-weight: 800; color: #1a4a2e; }
        .stat-card .label { color: #666; font-size: .9rem; }
        .sidebar-link { display: block; padding: 10px 16px; color: #333; text-decoration: none; border-radius: 8px; margin-bottom: 4px; }
        .sidebar-link:hover, .sidebar-link.active { background: #e8f5e9; color: #1a4a2e; font-weight: 600; }
        .content { padding: 24px; }
        @media(max-width:768px){ .sidebar-col { display: none; } }
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
    <!-- Sidebar -->
    <div class="col-md-2 sidebar-col bg-white vh-100 shadow-sm pt-3" style="min-height:100vh">
        <a href="dashboard.php" class="sidebar-link active">📊 Dashboard</a>
        <a href="sapi.php" class="sidebar-link">🐄 Katalog Sapi</a>
        <a href="pemesanan.php" class="sidebar-link">📋 Pemesanan Saya</a>
        <a href="pembayaran.php" class="sidebar-link">💳 Riwayat Bayar</a>
        <a href="profil.php" class="sidebar-link">👤 Profil</a>
    </div>

    <!-- Content -->
    <div class="col-md-10">
        <div class="content">
            <h4 class="fw-bold mb-1">Selamat Datang, <?= htmlspecialchars(explode(' ', $_SESSION['nama'])[0]) ?>! 👋</h4>
            <p class="text-muted mb-4">Tersedia <?= $sapi_siap ?> sapi siap jual</p>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card"><div class="value"><?= $total_pesan ?></div><div class="label">Total Pemesanan</div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card"><div class="value text-warning"><?= $pesan_pending ?></div><div class="label">Menunggu Konfirmasi</div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card"><div class="value" style="color:#e65100"><?= $pesan_dp ?></div><div class="label">Sudah DP</div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card"><div class="value"><?= $pesan_lunas ?></div><div class="label">Transaksi Selesai</div></div>
                </div>
            </div>

            <!-- CTA -->
            <div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#1a4a2e,#2e7d32);color:#fff;border-radius:12px">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold mb-1">🐄 <?= $sapi_siap ?> Sapi Siap Jual</h5>
                        <p class="mb-0 opacity-75">Temukan sapi terbaik untuk kebutuhan Anda</p>
                    </div>
                    <a href="sapi.php" class="btn btn-light fw-bold px-4">Lihat Katalog →</a>
                </div>
            </div>

            <!-- Pemesanan terbaru -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">📋 Pemesanan Terbaru</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr><th>Sapi</th><th>Tanggal</th><th>Harga</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php if ($recent_pesan && $recent_pesan->num_rows > 0):
                            $pbadge=['pending'=>'secondary','dp'=>'warning','lunas'=>'success'];
                            while ($p=$recent_pesan->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['kode_sapi']) ?></td>
                                <td><?= date('d M Y', strtotime($p['tanggal_pesan'])) ?></td>
                                <td><?= formatRupiah($p['harga_jual']) ?></td>
                                <td><span class="badge bg-<?= $pbadge[$p['status']]??'secondary' ?>"><?= ucfirst($p['status']) ?></span></td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pemesanan. <a href="sapi.php">Pesan sapi sekarang</a></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
