<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Role-based redirect
if ($_SESSION['role'] === 'admin') { header('Location: admin/dashboard.php'); exit; }
if ($_SESSION['role'] === 'user') { header('Location: user/dashboard.php'); exit; }

include 'koneksi/koneksi.php';
// Only pemilik reaches here
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

// AJAX handler: galeri foto (harus sebelum HTML output)
if (isset($_GET['ajax_foto']) && isset($_GET['id_sapi']) && $page === 'data_sapi') {
    $fsapi = intval($_GET['id_sapi']);
    $fotos = $koneksi->query("SELECT * FROM foto_sapi WHERE id_sapi=$fsapi ORDER BY tanggal ASC");
    if ($fotos && $fotos->num_rows > 0) {
        echo '<div class="foto-grid">';
        while ($f = $fotos->fetch_assoc()) {
            $fpath = 'uploads/sapi/' . htmlspecialchars($f['foto']);
            echo '<div style="text-align:center;width:130px">';
            echo "<img src='$fpath' class='foto-thumb' alt='foto'
      onclick=\"window.open('$fpath','_blank')\"
      onerror=\"this.src='assets/img/no-photo.png'\">";
            echo '<div style="font-size:.75rem;color:#888;margin-top:4px">' . date('d M Y', strtotime($f['tanggal'])) . '</div>';
            if ($f['keterangan']) echo '<div style="font-size:.75rem;color:#555">' . htmlspecialchars($f['keterangan']) . '</div>';
            echo "<form method='POST' onsubmit=\"return confirm('Hapus foto ini?')\" style='margin-top:4px'>";
            echo '<input type="hidden" name="_action" value="hapus_foto">';
            echo '<input type="hidden" name="foto_id" value="' . $f['id'] . '">';
            echo '<button type="submit" class="btn btn-danger btn-sm" style="font-size:.72rem;padding:3px 8px">🗑️</button>';
            echo '</form>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<div class="empty-state"><div class="icon">📷</div><h3>Belum ada foto</h3><p>Upload foto pertama di atas</p></div>';
    }
    exit;
}

$pageTitle = $titles[$page] ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — SiPeternakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <header class="topbar">
            <div class="d-flex align-center gap-3">
                <button class="btn btn-sm btn-outline-secondary" id="sidebarToggle">☰</button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
