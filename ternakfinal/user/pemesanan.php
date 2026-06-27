<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php'); exit;
}
include '../koneksi/koneksi.php';
$uid = intval($_SESSION['user_id']);

$list = $koneksi->query("
    SELECT pm.*, s.kode_sapi, s.berat_awal, s.status as status_sapi,
           (SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran WHERE id_pemesanan=pm.id) as total_bayar
    FROM pemesanan pm JOIN sapi s ON pm.id_sapi=s.id
    WHERE pm.id_user=$uid
    ORDER BY pm.created_at DESC
");
$pbadge = ['pending'=>'secondary','dp'=>'warning','lunas'=>'success']; // lihat badge override di bawah
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Saya — SiPeternakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{background:#f4f6f9;font-family:'Segoe UI',sans-serif}
        .navbar-brand{font-weight:800;color:#d4af37!important}
        .sidebar-link{display:block;padding:10px 16px;color:#333;text-decoration:none;border-radius:8px;margin-bottom:4px}
        .sidebar-link:hover,.sidebar-link.active{background:rgba(62,42,0,.12);color:#3e2a00;font-weight:700}
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
    <div class="col-md-2 sidebar-col vh-100 shadow-sm pt-3" style="min-height:100vh; background:linear-gradient(180deg,#f9e45b,#d4af37);">
        <a href="dashboard.php" class="sidebar-link">📊 Dashboard</a>
        <a href="sapi.php" class="sidebar-link">🐄 Katalog Sapi</a>
        <a href="pemesanan.php" class="sidebar-link active">📋 Pemesanan Saya</a>
        <a href="pembayaran.php" class="sidebar-link">💳 Riwayat Pembayaran</a>
        <a href="profil.php" class="sidebar-link">👤 Profil</a>
    </div>
    <div class="col-md-10 p-4">
        <h4 class="fw-bold mb-1">📋 Pemesanan Saya</h4>
        <p class="text-muted mb-4">Riwayat dan status pemesanan Anda</p>

        <?php if ($list && $list->num_rows > 0): ?>
        <div class="row g-3">
        <?php while ($r = $list->fetch_assoc()):
            $sisa = $r['harga_jual'] - $r['total_bayar'];
        ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="fw-bold mb-0">🐄 <?= htmlspecialchars($r['kode_sapi']) ?></h6>
                                <small class="text-muted">Dipesan: <?= date('d M Y', strtotime($r['tanggal_pesan'])) ?></small>
                            </div>
                            <span class="badge fs-6" style="<?= $r['status']==="lunas"?'background:#d4af37;color:#3e2a00':($r['status']==="dp"?'background:#fdd835;color:#3e2a00':'background:#9e9e9e;color:#fff') ?>"><?= ucfirst($r['status']) ?></span>
                        </div>
                        <div class="row g-2" style="font-size:.9rem">
                            <div class="col-6"><span class="text-muted">Harga Jual:</span><br><strong><?= formatRupiah($r['harga_jual']) ?></strong></div>
                            <div class="col-6"><span class="text-muted">DP Awal:</span><br><strong><?= formatRupiah($r['dp']) ?></strong></div>
                            <div class="col-6"><span class="text-muted">Total Dibayar:</span><br><strong style="color:#d4af37"><?= formatRupiah($r['total_bayar']) ?></strong></div>
                            <div class="col-6"><span class="text-muted">Sisa Tagihan:</span><br>
                                <?php if ($sisa > 0): ?>
                                <strong class="text-danger"><?= formatRupiah($sisa) ?></strong>
                                <?php else: ?>
                                <span class="badge" style="background:#d4af37;color:#3e2a00">✅ Lunas</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($r['keterangan']): ?>
                        <div class="mt-2 p-2 bg-light rounded" style="font-size:.85rem"><em><?= htmlspecialchars($r['keterangan']) ?></em></div>
                        <?php endif; ?>
                        <a href="pembayaran.php?id_pesan=<?= $r['id'] ?>" class="btn btn-outline-warning btn-sm mt-3">
                            💳 Lihat Riwayat Pembayaran
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <div style="font-size:4rem">📋</div>
            <h5 class="text-muted">Belum ada pemesanan</h5>
            <a href="sapi.php" class="btn btn-warning mt-2">🐄 Lihat Katalog Sapi</a>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
