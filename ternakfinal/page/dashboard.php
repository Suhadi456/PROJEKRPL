<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$total_sapi   = $koneksi->query("SELECT COUNT(*) as c FROM sapi")->fetch_assoc()['c'] ?? 0;
$sapi_gemuk   = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='digemukkan'")->fetch_assoc()['c'] ?? 0;
$sapi_jual    = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='siap_jual'")->fetch_assoc()['c'] ?? 0;
$sapi_dipesan = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='dipesan'")->fetch_assoc()['c'] ?? 0;
$sapi_terjual = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='terjual'")->fetch_assoc()['c'] ?? 0;
$total_pendapatan = $koneksi->query("SELECT COALESCE(SUM(jumlah_bayar),0) as c FROM pembayaran")->fetch_assoc()['c'] ?? 0;
$total_biaya      = $koneksi->query("SELECT COALESCE(SUM(jumlah),0) as c FROM biaya")->fetch_assoc()['c'] ?? 0;
$total_harga_beli = $koneksi->query("SELECT COALESCE(SUM(harga_beli),0) as c FROM sapi")->fetch_assoc()['c'] ?? 0;
$estimasi_untung  = $total_pendapatan - $total_biaya - $total_harga_beli;

$recent_sapi = $koneksi->query("
    SELECT s.*, 
           COALESCE((SELECT SUM(jumlah) FROM biaya WHERE id_sapi=s.id), 0) as total_biaya_ops,
           (s.harga_beli + COALESCE((SELECT SUM(jumlah) FROM biaya WHERE id_sapi=s.id), 0)) as total_modal
    FROM sapi s
    ORDER BY s.created_at DESC 
    LIMIT 5
");

if (!$recent_sapi) {
    die('Error query recent_sapi: ' . $koneksi->error);
}

$recent_pesan = $koneksi->query("
    SELECT pm.*, s.kode_sapi 
    FROM pemesanan pm 
    JOIN sapi s ON pm.id_sapi=s.id 
    ORDER BY pm.created_at DESC 
    LIMIT 5
");

// ★ Data grafik dari database
$chart_labels = [];
$chart_data   = [];
$chart_berat  = $koneksi->query("
    SELECT DATE_FORMAT(tanggal,'%b %Y') as bulan, 
           ROUND(AVG(berat),1) as avg_berat 
    FROM berat_sapi 
    GROUP BY YEAR(tanggal), MONTH(tanggal) 
    ORDER BY MIN(tanggal) ASC 
    LIMIT 6
");
if ($chart_berat && $chart_berat->num_rows > 0) {
    while ($row = $chart_berat->fetch_assoc()) {
        $chart_labels[] = $row['bulan'];
        $chart_data[]   = (float)($row['avg_berat'] ?? 0);
    }
}
// Fallback jika data kosong
if (empty($chart_labels)) {
    $chart_labels = ['Belum ada data'];
    $chart_data   = [0];
}

$status_data = [
    (int)$sapi_gemuk,
    (int)$sapi_jual,
    (int)$sapi_dipesan,
    (int)$sapi_terjual,
];

// Jika semua status 0, tampilkan dummy agar grafik tetap muncul
$status_labels = ['Digemukkan', 'Siap Jual', 'Dipesan', 'Terjual'];
$status_colors = ['#1565c0', '#d4af37', '#f57f17', '#9e9e9e'];
if (array_sum($status_data) === 0) {
    $status_data = [1, 0, 0, 0];
    $status_labels = ['Belum ada data', 'Siap Jual', 'Dipesan', 'Terjual'];
    $status_colors = ['#e0e0e0', '#d4af37', '#f57f17', '#9e9e9e'];
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Selamat Datang, <?= htmlspecialchars(explode(' ', $_SESSION['nama'])[0]) ?> 👋</h1>
        <p>Pantau kondisi peternakan Anda secara real-time</p>
    </div>
    <a href="index.php?page=data_sapi" class="btn btn-warning">＋ Tambah Sapi Baru</a>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">🐄</div>
        <div><div class="stat-label">Total Sapi</div><div class="stat-value"><?= $total_sapi ?></div><div class="stat-sub">Semua status</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">🌱</div>
        <div><div class="stat-label">Digemukkan</div><div class="stat-value"><?= $sapi_gemuk ?></div><div class="stat-sub">Dalam pemeliharaan</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div><div class="stat-label">Siap Jual</div><div class="stat-value"><?= $sapi_jual ?></div><div class="stat-sub">Siap ditawarkan</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">📋</div>
        <div><div class="stat-label">Dipesan</div><div class="stat-value"><?= $sapi_dipesan ?></div><div class="stat-sub">Menunggu pembayaran</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gray">💵</div>
        <div><div class="stat-label">Terjual</div><div class="stat-value"><?= $sapi_terjual ?></div><div class="stat-sub">Selesai terjual</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div><div class="stat-label">Total Pendapatan</div><div class="stat-value" style="font-size:1.1rem"><?= formatRupiah($total_pendapatan) ?></div><div class="stat-sub">Dari semua pembayaran</div></div>
    </div>
</div>

<!-- Charts Row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
    <div class="card">
        <div class="card-header"><div class="card-title">📈 Rata-rata Berat Sapi</div></div>
        <div class="card-body" style="padding:20px;height:260px;position:relative;">
            <canvas id="beratChart" style="width:100%;height:100%;display:block;"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">🥧 Distribusi Status Sapi</div></div>
        <div class="card-body" style="padding:20px;height:260px;position:relative;">
            <canvas id="statusChart" style="width:100%;height:100%;display:block;"></canvas>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div class="card">
        <div class="card-header">
            <div class="card-title">🐄 Sapi Terbaru</div>
            <a href="index.php?page=data_sapi" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Kode</th><th>Berat</th><th>Total Modal</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($recent_sapi && $recent_sapi->num_rows > 0): 
                    while ($s=$recent_sapi->fetch_assoc()): 
                        $sbadge=['digemukkan'=>'badge-blue','siap_jual'=>'badge-green','dipesan'=>'badge-amber','terjual'=>'badge-gray'];
                        $slabel=['digemukkan'=>'Digemukkan','siap_jual'=>'Siap Jual','dipesan'=>'Dipesan','terjual'=>'Terjual'];
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['kode_sapi']) ?></strong></td>
                        <td><?= number_format($s['berat_awal'],1) ?> kg</td>
                        <td><?= formatRupiah($s['total_modal']) ?></td>
                        <td><span class="badge <?= $sbadge[$s['status']]??'badge-gray' ?>"><?= $slabel[$s['status']]??ucfirst($s['status']) ?></span></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4"><div class="empty-state"><div class="icon">🐄</div><h3>Belum ada data sapi</h3></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title">📋 Pemesanan Terbaru</div>
            <a href="index.php?page=pemesanan" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Sapi</th><th>Pembeli</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($recent_pesan && $recent_pesan->num_rows > 0): 
                    while ($p=$recent_pesan->fetch_assoc()):
                        $pbadge=['pending'=>'badge-gray','dp'=>'badge-amber','lunas'=>'badge-green'];
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['kode_sapi']) ?></strong></td>
                        <td><?= htmlspecialchars($p['nama_pembeli']) ?></td>
                        <td><span class="badge <?= $pbadge[$p['status']]??'badge-gray' ?>"><?= ucfirst($p['status']) ?></span></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="3"><div class="empty-state"><div class="icon">📋</div><h3>Belum ada pemesanan</h3></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard ready');
    
    // Cek Chart.js
    if (typeof Chart === 'undefined') {
        console.error('Chart.js tidak terdefinisi! Periksa CDN.');
        return;
    }
    console.log('Chart.js version:', Chart.version);
    
    // ===== GRAFIK 1: LINE =====
    var beratCtx = document.getElementById('beratChart');
    if (beratCtx) {
        try {
            var chart1 = new Chart(beratCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?= json_encode($chart_labels, JSON_UNESCAPED_UNICODE) ?>,
                    datasets: [{
                        label: 'Rata-rata Berat (kg)',
                        data: <?= json_encode($chart_data) ?>,
                        borderColor: '#d4af37',
                        backgroundColor: 'rgba(212,175,55,0.2)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: false } }
                }
            });
            chart1.resize();
            console.log('✅ Grafik berat dibuat');
        } catch(e) {
            console.error('Error grafik berat:', e);
        }
    }
    
    // ===== GRAFIK 2: DONUT =====
    var statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        try {
            var chart2 = new Chart(statusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($status_labels, JSON_UNESCAPED_UNICODE) ?>,
                    datasets: [{
                        data: <?= json_encode($status_data) ?>,
                        backgroundColor: <?= json_encode($status_colors) ?>
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
            chart2.resize();
            console.log('✅ Grafik status dibuat');
        } catch(e) {
            console.error('Error grafik status:', e);
        }
    }
});
</script>