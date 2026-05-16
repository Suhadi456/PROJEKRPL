<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'koneksi/koneksi.php';

$page = $_GET['page'] ?? 'dashboard';

$titles = [
    'dashboard'  => 'Dashboard',
    'data_sapi'  => 'Data Sapi',
    'pakan'      => 'Pencatatan Pakan',
    'berat'      => 'Update Berat Sapi',
    'biaya'      => 'Pencatatan Biaya',
    'pemesanan'  => 'Pemesanan',
    'pembayaran' => 'Pembayaran',
    'laporan'    => 'Laporan Penjualan',
    'estimasi'   => 'Estimasi Keuangan',
];
$pageTitle = $titles[$page] ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — SiPeternakan</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div class="d-flex align-center gap-3">
                <button class="btn btn-secondary btn-icon" id="sidebarToggle" style="display:none">☰</button>
                <span class="topbar-title"><?= htmlspecialchars($pageTitle) ?></span>
            </div>
            <div class="topbar-right">
                <span class="topbar-date" id="dateDisplay"></span>
                <span class="badge badge-green">🟢 Online</span>
            </div>
        </header>

        <div class="page-content">
            <?php
            error_reporting(0);
            switch ($page) {
                case 'dashboard':  include 'page/dashboard.php'; break;
                case 'data_sapi':  include 'page/data_sapi/index.php'; break;
                case 'pakan':      include 'page/pakan/index.php'; break;
                case 'berat':      include 'page/berat/index.php'; break;
                case 'biaya':      include 'page/biaya/index.php'; break;
                case 'pemesanan':  include 'page/pemesanan/index.php'; break;
                case 'pembayaran': include 'page/pembayaran/index.php'; break;
                case 'laporan':    include 'page/laporan/index.php'; break;
                case 'estimasi':   include 'page/estimasi/index.php'; break;
                default:           include 'page/dashboard.php'; break;
            }
            ?>
        </div>
    </div>
</div>

<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99" onclick="closeSidebar()"></div>

<script>
document.getElementById('dateDisplay').textContent = new Date().toLocaleDateString('id-ID', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
