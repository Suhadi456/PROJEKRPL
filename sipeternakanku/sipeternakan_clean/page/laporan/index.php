<?php
$tgl_dari = sanitize($koneksi, $_GET['dari'] ?? date('Y-m-01'));
$tgl_sampai = sanitize($koneksi, $_GET['sampai'] ?? date('Y-m-d'));

$laporan = $koneksi->query("
    SELECT pm.*, s.kode_sapi, s.harga_beli, s.berat_awal,
           (SELECT COALESCE(SUM(jumlah),0) FROM biaya WHERE id_sapi=s.id) as total_biaya,
           (SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran WHERE id_pemesanan=pm.id) as total_bayar
    FROM pemesanan pm
    JOIN sapi s ON pm.id_sapi=s.id
    WHERE pm.tanggal_pesan BETWEEN '$tgl_dari' AND '$tgl_sampai'
    ORDER BY pm.tanggal_pesan DESC
");

$summary = $koneksi->query("
    SELECT COUNT(*) as total_transaksi,
           COALESCE(SUM(pm.harga_jual),0) as total_harga_jual,
           COALESCE(SUM(s.harga_beli),0) as total_harga_beli
    FROM pemesanan pm JOIN sapi s ON pm.id_sapi=s.id
    WHERE pm.tanggal_pesan BETWEEN '$tgl_dari' AND '$tgl_sampai'
")->fetch_assoc();

$total_bayar_all = $koneksi->query("
    SELECT COALESCE(SUM(py.jumlah_bayar),0) as t
    FROM pembayaran py
    JOIN pemesanan pm ON py.id_pemesanan=pm.id
    WHERE pm.tanggal_pesan BETWEEN '$tgl_dari' AND '$tgl_sampai'
")->fetch_assoc()['t'];

$total_biaya_all = $koneksi->query("
    SELECT COALESCE(SUM(b.jumlah),0) as t
    FROM biaya b
    JOIN sapi s ON b.id_sapi=s.id
    JOIN pemesanan pm ON pm.id_sapi=s.id
    WHERE pm.tanggal_pesan BETWEEN '$tgl_dari' AND '$tgl_sampai'
")->fetch_assoc()['t'];

$estimasi_untung = $summary['total_harga_jual'] - $summary['total_harga_beli'] - $total_biaya_all;
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Laporan Penjualan</div>
        <h1>📈 Laporan Penjualan</h1>
        <p>Ringkasan transaksi penjualan sapi berdasarkan periode</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary">🖨️ Cetak Laporan</button>
</div>

<!-- Filter Periode -->
<div class="card" style="margin-bottom:24px">
    <div class="card-body" style="padding:20px 24px">
        <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="page" value="laporan">
            <div class="form-group" style="margin:0">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="dari" class="form-control" value="<?= $tgl_dari ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="sampai" class="form-control" value="<?= $tgl_sampai ?>">
            </div>
            <div style="display:flex;gap:8px;align-self:flex-end">
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
                <a href="index.php?page=laporan" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon blue">📋</div>
        <div>
            <div class="stat-label">Total Transaksi</div>
            <div class="stat-value"><?= $summary['total_transaksi'] ?></div>
            <div class="stat-sub">Periode ini</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div>
            <div class="stat-label">Total Harga Jual</div>
            <div class="stat-value" style="font-size:17px"><?= formatRupiah($summary['total_harga_jual']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">💳</div>
        <div>
            <div class="stat-label">Total Diterima</div>
            <div class="stat-value" style="font-size:17px"><?= formatRupiah($total_bayar_all) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $estimasi_untung >= 0 ? 'green' : 'red' ?>">📊</div>
        <div>
            <div class="stat-label">Estimasi Keuntungan</div>
            <div class="stat-value <?= $estimasi_untung >= 0 ? 'text-green' : 'text-red' ?>" style="font-size:17px"><?= formatRupiah(abs($estimasi_untung)) ?></div>
            <div class="stat-sub"><?= $estimasi_untung >= 0 ? '📈 Untung' : '📉 Rugi' ?></div>
        </div>
    </div>
</div>

<!-- Tabel Laporan -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Detail Transaksi Penjualan</div>
        <span class="text-muted" style="font-size:13px"><?= date('d M Y', strtotime($tgl_dari)) ?> — <?= date('d M Y', strtotime($tgl_sampai)) ?></span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode Sapi</th>
                    <th>Pembeli</th>
                    <th>Tgl Pesan</th>
                    <th>Harga Beli</th>
                    <th>Total Biaya</th>
                    <th>Harga Jual</th>
                    <th>Terbayar</th>
                    <th>Estimasi Profit</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($laporan && $laporan->num_rows > 0): $no=1; while ($r = $laporan->fetch_assoc()):
                    $modal = $r['harga_beli'] + $r['total_biaya'];
                    $profit = $r['harga_jual'] - $modal;
                ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($r['kode_sapi']) ?></strong></td>
                    <td><?= htmlspecialchars($r['nama_pembeli']) ?></td>
                    <td><?= date('d M Y', strtotime($r['tanggal_pesan'])) ?></td>
                    <td><?= formatRupiah($r['harga_beli']) ?></td>
                    <td class="text-red"><?= formatRupiah($r['total_biaya']) ?></td>
                    <td class="font-bold"><?= formatRupiah($r['harga_jual']) ?></td>
                    <td class="text-green"><?= formatRupiah($r['total_bayar']) ?></td>
                    <td class="font-bold <?= $profit >= 0 ? 'text-green' : 'text-red' ?>">
                        <?= ($profit >= 0 ? '+' : '') . formatRupiah($profit) ?>
                    </td>
                    <td>
                        <?php $sb=['pending'=>'badge-gray','dp'=>'badge-amber','lunas'=>'badge-green']; ?>
                        <span class="badge <?= $sb[$r['status']] ?? 'badge-gray' ?>"><?= ucfirst($r['status']) ?></span>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="10">
                    <div class="empty-state">
                        <div class="icon">📈</div>
                        <h3>Belum ada data penjualan</h3>
                        <p>Tidak ada transaksi pada periode ini</p>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media print {
    .sidebar, .topbar, .page-header .btn, .card:first-child { display: none !important; }
    .main-content { margin-left: 0 !important; }
    body { background: white; }
}
</style>
