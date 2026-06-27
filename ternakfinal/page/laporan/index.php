<?php
$tgl_dari = sanitize($koneksi, $_GET['dari'] ?? date('Y-01-01'));
$tgl_sampai = sanitize($koneksi, $_GET['sampai'] ?? date('Y-12-31'));
$filter_status = sanitize($koneksi, $_GET['status'] ?? '');

$where = "WHERE pm.tanggal_pesan BETWEEN '$tgl_dari' AND '$tgl_sampai'";
if ($filter_status) $where .= " AND pm.status='$filter_status'";

$list = $koneksi->query("
    SELECT pm.*, s.kode_sapi, s.harga_beli,
           (SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran WHERE id_pemesanan=pm.id) as total_bayar,
           (SELECT COALESCE(SUM(jumlah),0) FROM biaya WHERE id_sapi=s.id) as total_biaya
    FROM pemesanan pm
    JOIN sapi s ON pm.id_sapi=s.id
    $where
    ORDER BY pm.tanggal_pesan DESC
");

$stat = $koneksi->query("
    SELECT COUNT(*) as total_transaksi, COALESCE(SUM(pm.harga_jual),0) as total_harga_jual,
           (SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran py 
            JOIN pemesanan pmj ON py.id_pemesanan=pmj.id 
            WHERE pmj.tanggal_pesan BETWEEN '$tgl_dari' AND '$tgl_sampai') as total_diterima
    FROM pemesanan pm JOIN sapi s ON pm.id_sapi=s.id $where
")->fetch_assoc();

$status_badges = ['pending'=>'badge-gray','dp'=>'badge-amber','lunas'=>'badge-green'];
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Laporan</div>
        <h1>📈 Laporan Penjualan</h1>
        <p>Ringkasan transaksi penjualan berdasarkan periode</p>
    </div>
</div>

<!-- Filter -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" action="index.php" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="page" value="laporan">
            <div>
                <label class="form-label" style="font-size:.8rem;margin-bottom:4px;display:block">Dari Tanggal</label>
                <input type="date" name="dari" class="form-control" value="<?= $tgl_dari ?>">
            </div>
            <div>
                <label class="form-label" style="font-size:.8rem;margin-bottom:4px;display:block">Sampai Tanggal</label>
                <input type="date" name="sampai" class="form-control" value="<?= $tgl_sampai ?>">
            </div>
            <div>
                <label class="form-label" style="font-size:.8rem;margin-bottom:4px;display:block">Status Pembayaran</label>
                <select name="status" class="form-control" style="width:150px">
                    <option value="">Semua</option>
                    <option value="pending" <?= $filter_status==='pending'?'selected':'' ?>>Pending</option>
                    <option value="dp" <?= $filter_status==='dp'?'selected':'' ?>>DP</option>
                    <option value="lunas" <?= $filter_status==='lunas'?'selected':'' ?>>Lunas</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <a href="index.php?page=laporan" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<!-- Ringkasan -->
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon green">📋</div>
        <div><div class="stat-label">Total Transaksi</div><div class="stat-value"><?= $stat['total_transaksi'] ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div><div class="stat-label">Total Harga Jual</div><div class="stat-value" style="font-size:1rem"><?= formatRupiah($stat['total_harga_jual']) ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">💳</div>
        <div><div class="stat-label">Total Diterima</div><div class="stat-value" style="font-size:1rem"><?= formatRupiah($stat['total_diterima']) ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= ($stat['total_harga_jual']-$stat['total_diterima'])>0?'amber':'green' ?>">⏳</div>
        <div><div class="stat-label">Belum Terbayar</div><div class="stat-value" style="font-size:1rem"><?= formatRupiah($stat['total_harga_jual']-$stat['total_diterima']) ?></div></div>
    </div>
</div>

<!-- Tabel -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Detail Transaksi</div>
        <span class="badge badge-green"><?= $list ? $list->num_rows : 0 ?> transaksi</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Kode Sapi</th><th>Pembeli</th><th>No HP</th><th>Tgl Pesan</th><th>Harga Jual</th><th>Total Bayar</th><th>Sisa</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php if ($list && $list->num_rows > 0):
                $no=1; while ($r=$list->fetch_assoc()):
                $sisa = $r['harga_jual'] - $r['total_bayar'];
            ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($r['kode_sapi']) ?></strong></td>
                    <td><?= htmlspecialchars($r['nama_pembeli']) ?></td>
                    <td><?= htmlspecialchars($r['no_hp']) ?></td>
                    <td><?= date('d M Y', strtotime($r['tanggal_pesan'])) ?></td>
                    <td><?= formatRupiah($r['harga_jual']) ?></td>
                    <td><?= formatRupiah($r['total_bayar']) ?></td>
                    <td><?= $sisa>0 ? formatRupiah($sisa) : '<span class="badge badge-green">Lunas</span>' ?></td>
                    <td><span class="badge <?= $status_badges[$r['status']]??'badge-gray' ?>"><?= ucfirst($r['status']) ?></span></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="9"><div class="empty-state"><div class="icon">📈</div><h3>Tidak ada data untuk periode ini</h3></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
