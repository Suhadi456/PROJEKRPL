<?php
// Per-sapi estimasi
$estimasi = $koneksi->query("
    SELECT s.id, s.kode_sapi, s.harga_beli, s.status, s.berat_awal,
           COALESCE((SELECT SUM(jumlah) FROM biaya WHERE id_sapi=s.id), 0) as total_biaya,
           COALESCE((SELECT harga_jual FROM pemesanan WHERE id_sapi=s.id ORDER BY created_at DESC LIMIT 1), 0) as harga_jual,
           COALESCE((SELECT berat FROM berat_sapi WHERE id_sapi=s.id ORDER BY tanggal DESC LIMIT 1), s.berat_awal) as berat_terakhir
    FROM sapi s
    ORDER BY s.status, s.kode_sapi
");

$rows = [];
while ($r = $estimasi->fetch_assoc()) $rows[] = $r;

// Aggregates
$total_modal   = array_sum(array_column($rows, 'harga_beli')) + array_sum(array_column($rows, 'total_biaya'));
$total_biaya_x = array_sum(array_column($rows, 'total_biaya'));
$terjual       = array_filter($rows, fn($r) => $r['status'] === 'terjual');
$aktif         = array_filter($rows, fn($r) => $r['status'] === 'aktif');
$total_penjualan = array_sum(array_column($terjual, 'harga_jual'));
$total_beli      = array_sum(array_column($rows, 'harga_beli'));

$profit_realisasi = $total_penjualan - array_sum(array_column($terjual, 'harga_beli')) - array_sum(array_column($terjual, 'total_biaya'));

// Chart labels/data per sapi
$chart_labels = array_map(fn($r) => $r['kode_sapi'], $rows);
$chart_modal  = array_map(fn($r) => $r['harga_beli'] + $r['total_biaya'], $rows);
$chart_jual   = array_map(fn($r) => $r['harga_jual'], $rows);
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Estimasi Keuangan</div>
        <h1>🧮 Estimasi Keuangan</h1>
        <p>Analisis keuntungan dan perhitungan modal per sapi</p>
    </div>
</div>

<!-- Top Stats -->
<div class="stats-grid" style="margin-bottom:28px">
    <div class="stat-card">
        <div class="stat-icon red">💸</div>
        <div>
            <div class="stat-label">Total Modal (Beli + Biaya)</div>
            <div class="stat-value" style="font-size:17px"><?= formatRupiah($total_modal) ?></div>
            <div class="stat-sub">Semua sapi</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div>
            <div class="stat-label">Total Penjualan (Terjual)</div>
            <div class="stat-value" style="font-size:17px"><?= formatRupiah($total_penjualan) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">📊</div>
        <div>
            <div class="stat-label">Profit Realisasi</div>
            <div class="stat-value <?= $profit_realisasi >= 0 ? 'text-green' : 'text-red' ?>" style="font-size:17px">
                <?= ($profit_realisasi >= 0 ? '+' : '') . formatRupiah($profit_realisasi) ?>
            </div>
            <div class="stat-sub"><?= $profit_realisasi >= 0 ? '📈 Untung' : '📉 Rugi' ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">🐄</div>
        <div>
            <div class="stat-label">Sapi Aktif (Aset)</div>
            <div class="stat-value"><?= count($aktif) ?></div>
            <div class="stat-sub">Belum terjual</div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <div class="card-title">📊 Perbandingan Modal vs Harga Jual per Sapi</div>
    </div>
    <div class="card-body">
        <canvas id="chartEstimasi" height="90"></canvas>
    </div>
</div>

<!-- Per-Sapi Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">🐄 Rincian Estimasi per Sapi</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode Sapi</th>
                    <th>Harga Beli</th>
                    <th>Total Biaya</th>
                    <th>Total Modal</th>
                    <th>Berat Awal → Terakhir</th>
                    <th>Harga Jual</th>
                    <th>Est. Keuntungan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) > 0): $no=1; foreach ($rows as $r):
                    $modal = $r['harga_beli'] + $r['total_biaya'];
                    $profit = $r['harga_jual'] > 0 ? $r['harga_jual'] - $modal : null;
                    $naik_berat = $r['berat_terakhir'] - $r['berat_awal'];
                ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($r['kode_sapi']) ?></strong></td>
                    <td><?= formatRupiah($r['harga_beli']) ?></td>
                    <td class="text-red"><?= formatRupiah($r['total_biaya']) ?></td>
                    <td class="font-bold"><?= formatRupiah($modal) ?></td>
                    <td>
                        <?= number_format($r['berat_awal'],1) ?> →
                        <strong><?= number_format($r['berat_terakhir'],1) ?> kg</strong>
                        <span class="<?= $naik_berat >= 0 ? 'text-green' : 'text-red' ?>" style="font-size:12px">
                            (<?= $naik_berat >= 0 ? '+' : '' ?><?= number_format($naik_berat,1) ?> kg)
                        </span>
                    </td>
                    <td><?= $r['harga_jual'] > 0 ? formatRupiah($r['harga_jual']) : '<span class="text-muted">Belum ditetapkan</span>' ?></td>
                    <td>
                        <?php if ($profit !== null): ?>
                        <span class="font-bold <?= $profit >= 0 ? 'text-green' : 'text-red' ?>">
                            <?= ($profit >= 0 ? '▲ +' : '▼ ') . formatRupiah(abs($profit)) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $sb=['aktif'=>'badge-green','dipesan'=>'badge-amber','terjual'=>'badge-blue']; ?>
                        <span class="badge <?= $sb[$r['status']] ?? 'badge-gray' ?>"><?= ucfirst($r['status']) ?></span>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="9">
                    <div class="empty-state"><div class="icon">🧮</div><h3>Belum ada data</h3></div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
new Chart(document.getElementById('chartEstimasi'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [
            {
                label: 'Total Modal (Beli + Biaya)',
                data: <?= json_encode($chart_modal) ?>,
                backgroundColor: 'rgba(239,68,68,0.7)',
                borderRadius: 6
            },
            {
                label: 'Harga Jual',
                data: <?= json_encode($chart_jual) ?>,
                backgroundColor: 'rgba(34,197,94,0.7)',
                borderRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: ctx => ' Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'jt' },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>
