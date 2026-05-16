<?php
// Dashboard stats
$total_sapi = $koneksi->query("SELECT COUNT(*) as c FROM sapi")->fetch_assoc()['c'] ?? 0;
$sapi_aktif = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='aktif'")->fetch_assoc()['c'] ?? 0;
$sapi_dipesan = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='dipesan'")->fetch_assoc()['c'] ?? 0;
$sapi_terjual = $koneksi->query("SELECT COUNT(*) as c FROM sapi WHERE status='terjual'")->fetch_assoc()['c'] ?? 0;

$total_pakan_hari = $koneksi->query("SELECT COALESCE(SUM(jumlah),0) as c FROM pakan WHERE tanggal=CURDATE()")->fetch_assoc()['c'] ?? 0;
$total_biaya = $koneksi->query("SELECT COALESCE(SUM(jumlah),0) as c FROM biaya")->fetch_assoc()['c'] ?? 0;
$total_pendapatan = $koneksi->query("SELECT COALESCE(SUM(jumlah_bayar),0) as c FROM pembayaran")->fetch_assoc()['c'] ?? 0;

// Recent sapi
$recent_sapi = $koneksi->query("SELECT * FROM sapi ORDER BY created_at DESC LIMIT 5");

// Chart data - berat per bulan (rata-rata)
$chart_berat = $koneksi->query("
    SELECT DATE_FORMAT(tanggal,'%b %Y') as bulan, AVG(berat) as avg_berat
    FROM berat_sapi 
    GROUP BY YEAR(tanggal), MONTH(tanggal)
    ORDER BY tanggal ASC
    LIMIT 6
");
$chart_labels = []; $chart_data = [];
while ($row = $chart_berat->fetch_assoc()) {
    $chart_labels[] = $row['bulan'];
    $chart_data[] = round($row['avg_berat'], 1);
}

// Status sapi for doughnut
$status_data = [$sapi_aktif, $sapi_dipesan, $sapi_terjual];
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1>Selamat Datang, <?= htmlspecialchars(explode(' ', $_SESSION['nama'])[0]) ?> 👋</h1>
        <p>Pantau kondisi peternakan Anda secara real-time</p>
    </div>
    <a href="index.php?page=data_sapi&aksi=tambah" class="btn btn-primary">
        ＋ Tambah Sapi Baru
    </a>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">🐄</div>
        <div>
            <div class="stat-label">Total Sapi</div>
            <div class="stat-value"><?= $total_sapi ?></div>
            <div class="stat-sub">Semua status</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div>
            <div class="stat-label">Sapi Aktif</div>
            <div class="stat-value"><?= $sapi_aktif ?></div>
            <div class="stat-sub">Siap dipelihara</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">📋</div>
        <div>
            <div class="stat-label">Dipesan</div>
            <div class="stat-value"><?= $sapi_dipesan ?></div>
            <div class="stat-sub">Menunggu pelunasan</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">💳</div>
        <div>
            <div class="stat-label">Total Pendapatan</div>
            <div class="stat-value" style="font-size:18px"><?= formatRupiah($total_pendapatan) ?></div>
            <div class="stat-sub">Dari semua pembayaran</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:28px">
    <!-- Berat Chart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">📈 Perkembangan Berat Rata-Rata (kg)</div>
        </div>
        <div class="card-body">
            <canvas id="chartBerat" height="120"></canvas>
        </div>
    </div>
    <!-- Status Doughnut -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">🐄 Status Sapi</div>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:16px">
            <canvas id="chartStatus" height="160" width="160"></canvas>
            <div style="display:flex;flex-direction:column;gap:6px;width:100%">
                <div class="d-flex align-center gap-2" style="font-size:13px">
                    <span style="width:12px;height:12px;border-radius:3px;background:#22c55e;display:inline-block"></span>
                    Aktif: <strong><?= $sapi_aktif ?></strong>
                </div>
                <div class="d-flex align-center gap-2" style="font-size:13px">
                    <span style="width:12px;height:12px;border-radius:3px;background:#f59e0b;display:inline-block"></span>
                    Dipesan: <strong><?= $sapi_dipesan ?></strong>
                </div>
                <div class="d-flex align-center gap-2" style="font-size:13px">
                    <span style="width:12px;height:12px;border-radius:3px;background:#3b82f6;display:inline-block"></span>
                    Terjual: <strong><?= $sapi_terjual ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Sapi Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">🐄 Sapi Terbaru</div>
        <a href="index.php?page=data_sapi" class="btn btn-secondary btn-sm">Lihat Semua</a>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode Sapi</th>
                    <th>Berat Awal</th>
                    <th>Tgl Pembelian</th>
                    <th>Harga Beli</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_sapi && $recent_sapi->num_rows > 0):
                    while ($s = $recent_sapi->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s['kode_sapi']) ?></strong></td>
                    <td><?= number_format($s['berat_awal'], 1) ?> kg</td>
                    <td><?= date('d M Y', strtotime($s['tanggal_pembelian'])) ?></td>
                    <td><?= formatRupiah($s['harga_beli']) ?></td>
                    <td>
                        <?php
                        $badges = ['aktif'=>'badge-green','dipesan'=>'badge-amber','terjual'=>'badge-blue'];
                        $label  = ['aktif'=>'Aktif','dipesan'=>'Dipesan','terjual'=>'Terjual'];
                        ?>
                        <span class="badge <?= $badges[$s['status']] ?? 'badge-gray' ?>">
                            <?= $label[$s['status']] ?? $s['status'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="index.php?page=data_sapi&aksi=detail&id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">Detail</a>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:32px">Belum ada data sapi</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Berat Chart
new Chart(document.getElementById('chartBerat'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels ?: ['Jan','Feb','Mar','Apr','Mei','Jun']) ?>,
        datasets: [{
            label: 'Berat Rata-Rata (kg)',
            data: <?= json_encode($chart_data ?: [300, 315, 330, 345, 358, 370]) ?>,
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34,197,94,0.08)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#22c55e',
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Status Doughnut
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: ['Aktif', 'Dipesan', 'Terjual'],
        datasets: [{
            data: <?= json_encode($status_data) ?>,
            backgroundColor: ['#22c55e', '#f59e0b', '#3b82f6'],
            borderWidth: 0,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: false,
        plugins: { legend: { display: false } },
        cutout: '70%'
    }
});
</script>
