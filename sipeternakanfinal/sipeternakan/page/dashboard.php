<?php
$total_sapi   = $koneksi->query("SELECT COUNT(*) as c FROM sapi")->fetch_assoc()['c'] ?? 0;
$sapi_gemuk   = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='digemukkan'")->fetch_assoc()['c'] ?? 0;
$sapi_jual    = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='siap_jual'")->fetch_assoc()['c'] ?? 0;
$sapi_dipesan = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='dipesan'")->fetch_assoc()['c'] ?? 0;
$sapi_terjual = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='terjual'")->fetch_assoc()['c'] ?? 0;
$total_pendapatan = $koneksi->query("SELECT COALESCE(SUM(jumlah_bayar),0) as c FROM pembayaran")->fetch_assoc()['c'] ?? 0;
$total_biaya      = $koneksi->query("SELECT COALESCE(SUM(jumlah),0) as c FROM biaya")->fetch_assoc()['c'] ?? 0;
$total_harga_beli = $koneksi->query("SELECT COALESCE(SUM(harga_beli),0) as c FROM sapi")->fetch_assoc()['c'] ?? 0;
$estimasi_untung  = $total_pendapatan - $total_biaya - $total_harga_beli;

$recent_sapi = $koneksi->query("SELECT * FROM sapi ORDER BY created_at DESC LIMIT 5");
$recent_pesan = $koneksi->query("SELECT pm.*, s.kode_sapi FROM pemesanan pm JOIN sapi s ON pm.id_sapi=s.id ORDER BY pm.created_at DESC LIMIT 5");

$chart_berat = $koneksi->query("SELECT DATE_FORMAT(tanggal,'%b %Y') as bulan, AVG(berat) as avg_berat FROM berat_sapi GROUP BY YEAR(tanggal), MONTH(tanggal) ORDER BY tanggal ASC LIMIT 6");
$chart_labels = []; $chart_data = [];
while ($row = $chart_berat->fetch_assoc()) {
    $chart_labels[] = $row['bulan'];
    $chart_data[] = round($row['avg_berat'], 1);
}
$status_data = [$sapi_gemuk, $sapi_jual, $sapi_dipesan, $sapi_terjual];
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Selamat Datang, <?= htmlspecialchars(explode(' ', $_SESSION['nama'])[0]) ?> 👋</h1>
        <p>Pantau kondisi peternakan Anda secara real-time</p>
    </div>
    <a href="index.php?page=data_sapi" class="btn btn-primary">＋ Tambah Sapi Baru</a>
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
        <div class="card-body" style="padding:20px">
            <canvas id="beratChart" height="200"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">🥧 Distribusi Status Sapi</div></div>
        <div class="card-body" style="padding:20px">
            <canvas id="statusChart" height="200"></canvas>
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
                <thead><tr><th>Kode</th><th>Berat</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($recent_sapi): while ($s=$recent_sapi->fetch_assoc()): 
                    $sbadge=['digemukkan'=>'badge-blue','siap_jual'=>'badge-green','dipesan'=>'badge-amber','terjual'=>'badge-gray'];
                    $slabel=['digemukkan'=>'Digemukkan','siap_jual'=>'Siap Jual','dipesan'=>'Dipesan','terjual'=>'Terjual'];
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['kode_sapi']) ?></strong></td>
                        <td><?= number_format($s['berat_awal'],1) ?> kg</td>
                        <td><span class="badge <?= $sbadge[$s['status']]??'badge-gray' ?>"><?= $slabel[$s['status']]??ucfirst($s['status']) ?></span></td>
                    </tr>
                <?php endwhile; endif; ?>
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
                <?php if ($recent_pesan): while ($p=$recent_pesan->fetch_assoc()):
                    $pbadge=['pending'=>'badge-gray','dp'=>'badge-amber','lunas'=>'badge-green'];
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['kode_sapi']) ?></strong></td>
                        <td><?= htmlspecialchars($p['nama_pembeli']) ?></td>
                        <td><span class="badge <?= $pbadge[$p['status']]??'badge-gray' ?>"><?= ucfirst($p['status']) ?></span></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Berat Chart
const beratCtx = document.getElementById('beratChart').getContext('2d');
new Chart(beratCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{ label: 'Rata-rata Berat (kg)', data: <?= json_encode($chart_data) ?>,
            borderColor: '#2e7d32', backgroundColor: 'rgba(46,125,50,0.1)', tension: 0.3, fill: true, pointRadius: 4 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: false } } }
});

// Status Donut
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Digemukkan','Siap Jual','Dipesan','Terjual'],
        datasets: [{ data: <?= json_encode($status_data) ?>,
            backgroundColor: ['#1565c0','#2e7d32','#f57f17','#616161'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
</script>
