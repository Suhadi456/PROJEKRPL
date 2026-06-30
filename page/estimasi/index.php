<?php
// Per sapi: total biaya = harga beli + total biaya operasional
// Estimasi keuntungan = total pembayaran diterima - total biaya
$sapi_list = $koneksi->query("
    SELECT s.id, s.kode_sapi, s.harga_beli, s.status,
           (SELECT COALESCE(SUM(jumlah),0) FROM biaya WHERE id_sapi=s.id) as total_biaya_ops,
           (SELECT pm.harga_jual FROM pemesanan pm WHERE pm.id_sapi=s.id ORDER BY pm.created_at DESC LIMIT 1) as harga_jual,
           (SELECT COALESCE(SUM(py.jumlah_bayar),0) FROM pembayaran py JOIN pemesanan pm ON py.id_pemesanan=pm.id WHERE pm.id_sapi=s.id) as total_bayar,
           (SELECT berat FROM berat_sapi WHERE id_sapi=s.id ORDER BY tanggal DESC, id DESC LIMIT 1) as berat_terkini
    FROM sapi s
    ORDER BY s.created_at DESC
");

// Global totals
$g = $koneksi->query("
    SELECT 
        (SELECT COALESCE(SUM(harga_beli),0) FROM sapi) as total_modal_beli,
        (SELECT COALESCE(SUM(jumlah),0) FROM biaya) as total_biaya_ops,
        (SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran) as total_pendapatan
")->fetch_assoc();

$total_modal = $g['total_modal_beli'] + $g['total_biaya_ops'];
$total_pendapatan = $g['total_pendapatan'];
$estimasi_untung = $total_pendapatan - $total_modal;

$status_labels = ['digemukkan'=>'Digemukkan','siap_jual'=>'Siap Jual','dipesan'=>'Dipesan','terjual'=>'Terjual'];
$status_badges = ['digemukkan'=>'badge-blue','siap_jual'=>'badge-green','dipesan'=>'badge-amber','terjual'=>'badge-gray'];
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Estimasi</div>
        <h1>🧮 Estimasi Keuangan</h1>
        <p>Perhitungan modal, pendapatan, dan estimasi keuntungan</p>
    </div>
</div>

<!-- Ringkasan Global -->
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon blue">🏦</div>
        <div><div class="stat-label">Total Modal Beli Sapi</div><div class="stat-value" style="font-size:1rem"><?= formatRupiah($g['total_modal_beli']) ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">💸</div>
        <div><div class="stat-label">Total Biaya Operasional</div><div class="stat-value" style="font-size:1rem"><?= formatRupiah($g['total_biaya_ops']) ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">📉</div>
        <div><div class="stat-label">Total Modal Keseluruhan</div><div class="stat-value" style="font-size:1rem"><?= formatRupiah($total_modal) ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div><div class="stat-label">Total Pendapatan</div><div class="stat-value" style="font-size:1rem"><?= formatRupiah($total_pendapatan) ?></div></div>
    </div>
    <div class="stat-card" style="<?= $estimasi_untung>=0?'border-left:4px solid #d4af37':'border-left:4px solid #c62828' ?>">
        <div class="stat-icon <?= $estimasi_untung>=0?'green':'red' ?>">📊</div>
        <div>
            <div class="stat-label">Estimasi <?= $estimasi_untung>=0?'Keuntungan':'Kerugian' ?></div>
            <div class="stat-value" style="font-size:1rem;color:<?= $estimasi_untung>=0?'#d4af37':'#c62828' ?>"><?= formatRupiah(abs($estimasi_untung)) ?></div>
            <div class="stat-sub"><?= $estimasi_untung>=0?'💹 Untung':'📉 Rugi' ?></div>
        </div>
    </div>
</div>

<!-- Per Sapi -->
<div class="card">
    <div class="card-header">
        <div class="card-title">📊 Rincian Per Sapi</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Kode Sapi</th><th>Berat Terkini</th><th>Harga Beli</th><th>Biaya Ops</th><th>Total Modal</th><th>Harga Jual</th><th>Sudah Dibayar</th><th>Est. Untung</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php if ($sapi_list && $sapi_list->num_rows > 0):
                $no=1; while ($r=$sapi_list->fetch_assoc()):
                $total_modal_sapi = $r['harga_beli'] + $r['total_biaya_ops'];
                $harga_jual = $r['harga_jual'] ?: 0;
                $est_untung = $harga_jual > 0 ? ($harga_jual - $total_modal_sapi) : 0;
            ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($r['kode_sapi']) ?></strong></td>
                    <td><?= $r['berat_terkini'] ? number_format($r['berat_terkini'],1).' kg' : '-' ?></td>
                    <td><?= formatRupiah($r['harga_beli']) ?></td>
                    <td><?= formatRupiah($r['total_biaya_ops']) ?></td>
                    <td><strong><?= formatRupiah($total_modal_sapi) ?></strong></td>
                    <td><?= $harga_jual>0 ? formatRupiah($harga_jual) : '<span class="text-muted">Belum ada</span>' ?></td>
                    <td><?= formatRupiah($r['total_bayar']) ?></td>
                    <td>
                        <?php if ($harga_jual > 0): ?>
                        <span style="font-weight:600;color:<?= $est_untung>=0?'#d4af37':'#c62828' ?>">
                            <?= ($est_untung>=0?'▲ ':'▼ ') . formatRupiah(abs($est_untung)) ?>
                        </span>
                        <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                    </td>
                    <td><span class="badge <?= $status_badges[$r['status']]??'badge-gray' ?>"><?= $status_labels[$r['status']]??ucfirst($r['status']) ?></span></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="10"><div class="empty-state"><div class="icon">🧮</div><h3>Belum ada data sapi</h3></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
