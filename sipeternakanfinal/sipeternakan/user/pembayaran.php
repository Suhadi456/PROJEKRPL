<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php'); exit;
}
include '../koneksi/koneksi.php';
$uid = intval($_SESSION['user_id']);
$id_pesan_filter = intval($_GET['id_pesan'] ?? 0);

$where = "WHERE pm.id_user=$uid";
if ($id_pesan_filter) $where .= " AND py.id_pemesanan=$id_pesan_filter";

$list = $koneksi->query("
    SELECT py.*, pm.nama_pembeli, pm.harga_jual, pm.status as status_pesan, s.kode_sapi
    FROM pembayaran py
    JOIN pemesanan pm ON py.id_pemesanan=pm.id
    JOIN sapi s ON pm.id_sapi=s.id
    $where
    ORDER BY py.tanggal_bayar DESC
");
$jenis_badges = ['dp'=>'warning','pelunasan'=>'success','cicilan'=>'info'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembayaran — SiPeternakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{background:#f4f6f9;font-family:'Segoe UI',sans-serif}
        .navbar-brand{font-weight:800;color:#1a4a2e!important}
        .sidebar-link{display:block;padding:10px 16px;color:#333;text-decoration:none;border-radius:8px;margin-bottom:4px}
        .sidebar-link:hover,.sidebar-link.active{background:#e8f5e9;color:#1a4a2e;font-weight:600}
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
    <div class="col-md-2 sidebar-col bg-white shadow-sm pt-3" style="min-height:100vh">
        <a href="dashboard.php" class="sidebar-link">📊 Dashboard</a>
        <a href="sapi.php" class="sidebar-link">🐄 Katalog Sapi</a>
        <a href="pemesanan.php" class="sidebar-link">📋 Pemesanan Saya</a>
        <a href="pembayaran.php" class="sidebar-link active">💳 Riwayat Bayar</a>
        <a href="profil.php" class="sidebar-link">👤 Profil</a>
    </div>
    <div class="col-md-10 p-4">
        <h4 class="fw-bold mb-1">💳 Riwayat Pembayaran</h4>
        <p class="text-muted mb-4">
            <?php if ($id_pesan_filter): ?>
            <a href="pembayaran.php" class="text-success">← Lihat semua pembayaran</a>
            <?php else: ?>
            Semua riwayat pembayaran Anda
            <?php endif; ?>
        </p>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>Tanggal</th><th>Kode Sapi</th><th>Jenis</th><th>Jumlah</th><th>Harga Jual</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($list && $list->num_rows > 0):
                        while ($r = $list->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($r['tanggal_bayar'])) ?></td>
                            <td><strong><?= htmlspecialchars($r['kode_sapi']) ?></strong></td>
                            <td><span class="badge bg-<?= $jenis_badges[$r['jenis_pembayaran']]??'secondary' ?>"><?= ucfirst($r['jenis_pembayaran']) ?></span></td>
                            <td class="fw-bold text-success"><?= formatRupiah($r['jumlah_bayar']) ?></td>
                            <td><?= formatRupiah($r['harga_jual']) ?></td>
                            <td><span class="badge bg-<?= $r['status_pesan']==='lunas'?'success':'warning' ?>"><?= ucfirst($r['status_pesan']) ?></span></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat pembayaran</td></tr>
                    <?php endif; ?>
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
