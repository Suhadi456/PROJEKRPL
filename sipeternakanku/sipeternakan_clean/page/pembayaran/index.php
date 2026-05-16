<?php
$msg = ''; $error = '';
$id_pesan_filter = intval($_GET['id_pesan'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'save') {
        $id_pesan   = intval($_POST['id_pemesanan']);
        $tgl        = sanitize($koneksi, $_POST['tanggal_bayar']);
        $jumlah     = intval($_POST['jumlah_bayar']);
        $jenis      = sanitize($koneksi, $_POST['jenis_pembayaran']);
        $ket        = sanitize($koneksi, $_POST['keterangan'] ?? '');

        if (!$id_pesan || !$tgl || !$jumlah || !$jenis) {
            $error = 'Semua field wajib diisi.';
        } elseif ($jumlah <= 0) {
            $error = 'Jumlah pembayaran harus lebih dari 0.';
        } else {
            // Get pemesanan info
            $pesan = $koneksi->query("SELECT * FROM pemesanan WHERE id=$id_pesan LIMIT 1")->fetch_assoc();
            if (!$pesan) {
                $error = 'Data pemesanan tidak ditemukan.';
            } elseif ($pesan['status'] === 'lunas') {
                $error = 'Sapi ini sudah lunas dibayar.';
            } else {
                $total_bayar = $koneksi->query("SELECT COALESCE(SUM(jumlah_bayar),0) as t FROM pembayaran WHERE id_pemesanan=$id_pesan")->fetch_assoc()['t'];
                $sisa = $pesan['harga_jual'] - $total_bayar;
                if ($jumlah > $sisa) {
                    $error = "Nominal melebihi sisa tagihan (" . formatRupiah($sisa) . ")";
                } else {
                    $koneksi->query("INSERT INTO pembayaran (id_pemesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran, keterangan) VALUES ($id_pesan, '$tgl', $jumlah, '$jenis', '$ket')");
                    // Update status
                    $new_total = $total_bayar + $jumlah;
                    if ($new_total >= $pesan['harga_jual']) {
                        $koneksi->query("UPDATE pemesanan SET status='lunas' WHERE id=$id_pesan");
                        $koneksi->query("UPDATE sapi SET status='terjual' WHERE id={$pesan['id_sapi']}");
                        $msg = '🎉 Pembayaran lunas! Status sapi diubah menjadi Terjual.';
                    } else {
                        $koneksi->query("UPDATE pemesanan SET status='dp' WHERE id=$id_pesan");
                        $msg = 'Pembayaran dicatat.';
                    }
                }
            }
        }
    } elseif ($act === 'delete') {
        $del_id = intval($_POST['del_id']);
        $pay = $koneksi->query("SELECT id_pemesanan FROM pembayaran WHERE id=$del_id LIMIT 1")->fetch_assoc();
        $koneksi->query("DELETE FROM pembayaran WHERE id=$del_id");
        if ($pay) {
            // Recalculate status
            $pid = $pay['id_pemesanan'];
            $pesan = $koneksi->query("SELECT * FROM pemesanan WHERE id=$pid LIMIT 1")->fetch_assoc();
            $total = $koneksi->query("SELECT COALESCE(SUM(jumlah_bayar),0) as t FROM pembayaran WHERE id_pemesanan=$pid")->fetch_assoc()['t'];
            $new_status = $total >= $pesan['harga_jual'] ? 'lunas' : ($total > 0 ? 'dp' : 'pending');
            $koneksi->query("UPDATE pemesanan SET status='$new_status' WHERE id=$pid");
            $sapi_status = $new_status === 'lunas' ? 'terjual' : 'dipesan';
            $koneksi->query("UPDATE sapi SET status='$sapi_status' WHERE id={$pesan['id_sapi']}");
        }
        $msg = 'Data pembayaran dihapus.';
    }
}

// Selected pemesanan
$selected_pesan = null;
if ($id_pesan_filter) {
    $selected_pesan = $koneksi->query("SELECT pm.*, s.kode_sapi FROM pemesanan pm JOIN sapi s ON pm.id_sapi=s.id WHERE pm.id=$id_pesan_filter LIMIT 1")->fetch_assoc();
}

// Payment list
$where_pay = $id_pesan_filter ? "WHERE py.id_pemesanan=$id_pesan_filter" : "WHERE 1=1";
$payments = $koneksi->query("
    SELECT py.*, pm.nama_pembeli, pm.harga_jual, s.kode_sapi
    FROM pembayaran py
    JOIN pemesanan pm ON py.id_pemesanan=pm.id
    JOIN sapi s ON pm.id_sapi=s.id
    $where_pay
    ORDER BY py.tanggal_bayar DESC
");

// Pemesanan list for dropdown
$pesan_opts = $koneksi->query("SELECT pm.id, s.kode_sapi, pm.nama_pembeli, pm.harga_jual, pm.status FROM pemesanan pm JOIN sapi s ON pm.id_sapi=s.id WHERE pm.status!='lunas' ORDER BY pm.created_at DESC");
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Pembayaran</div>
        <h1>💳 Pencatatan Pembayaran</h1>
        <p>Catat DP dan pelunasan dari pembeli</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalBayar')">＋ Catat Pembayaran</button>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- Selected pemesanan info -->
<?php if ($selected_pesan):
    $total_bayar_p = $koneksi->query("SELECT COALESCE(SUM(jumlah_bayar),0) as t FROM pembayaran WHERE id_pemesanan=$id_pesan_filter")->fetch_assoc()['t'];
    $sisa = $selected_pesan['harga_jual'] - $total_bayar_p;
?>
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--green-500)">
    <div class="card-body" style="padding:20px 24px">
        <div style="display:flex;flex-wrap:wrap;gap:24px;align-items:center">
            <div>
                <div class="text-muted" style="font-size:12px">SAPI</div>
                <div class="font-bold"><?= htmlspecialchars($selected_pesan['kode_sapi']) ?></div>
            </div>
            <div>
                <div class="text-muted" style="font-size:12px">PEMBELI</div>
                <div class="font-bold"><?= htmlspecialchars($selected_pesan['nama_pembeli']) ?></div>
            </div>
            <div>
                <div class="text-muted" style="font-size:12px">HARGA JUAL</div>
                <div class="font-bold"><?= formatRupiah($selected_pesan['harga_jual']) ?></div>
            </div>
            <div>
                <div class="text-muted" style="font-size:12px">TOTAL DIBAYAR</div>
                <div class="font-bold text-green"><?= formatRupiah($total_bayar_p) ?></div>
            </div>
            <div>
                <div class="text-muted" style="font-size:12px">SISA TAGIHAN</div>
                <div class="font-bold <?= $sisa > 0 ? 'text-red' : 'text-green' ?>"><?= formatRupiah($sisa) ?></div>
            </div>
            <div>
                <?php $sb=['pending'=>'badge-gray','dp'=>'badge-amber','lunas'=>'badge-green']; ?>
                <span class="badge <?= $sb[$selected_pesan['status']] ?>"><?= ucfirst($selected_pesan['status']) ?></span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Riwayat Pembayaran</div>
        <?php if ($id_pesan_filter): ?>
        <a href="index.php?page=pembayaran" class="btn btn-secondary btn-sm">Lihat Semua</a>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Kode Sapi</th><th>Pembeli</th><th>Tgl Bayar</th><th>Jenis</th><th>Jumlah</th><th>Keterangan</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if ($payments && $payments->num_rows > 0): $no=1; while ($p = $payments->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($p['kode_sapi']) ?></strong></td>
                    <td><?= htmlspecialchars($p['nama_pembeli']) ?></td>
                    <td><?= date('d M Y', strtotime($p['tanggal_bayar'])) ?></td>
                    <td>
                        <?php $jb=['dp'=>'badge-amber','pelunasan'=>'badge-green','cicilan'=>'badge-blue']; ?>
                        <span class="badge <?= $jb[$p['jenis_pembayaran']] ?? 'badge-gray' ?>"><?= ucfirst($p['jenis_pembayaran']) ?></span>
                    </td>
                    <td class="text-green font-bold"><?= formatRupiah($p['jumlah_bayar']) ?></td>
                    <td class="text-muted"><?= $p['keterangan'] ?: '-' ?></td>
                    <td>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus data ini?')">
                            <input type="hidden" name="_action" value="delete">
                            <input type="hidden" name="del_id" value="<?= $p['id'] ?>">
                            <button class="btn btn-danger btn-sm">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="8"><div class="empty-state"><div class="icon">💳</div><h3>Belum ada data pembayaran</h3></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Pembayaran -->
<div class="modal-overlay" id="modalBayar">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">💳 Catat Pembayaran</div>
            <button class="modal-close" onclick="closeModal('modalBayar')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Pemesanan <span>*</span></label>
                        <select name="id_pemesanan" class="form-control" required>
                            <option value="">-- Pilih Pemesanan --</option>
                            <?php
                            if ($pesan_opts) while ($po = $pesan_opts->fetch_assoc()):
                            ?>
                            <option value="<?= $po['id'] ?>" <?= $id_pesan_filter==$po['id']?'selected':'' ?>>
                                <?= htmlspecialchars($po['kode_sapi']) ?> — <?= htmlspecialchars($po['nama_pembeli']) ?> (<?= formatRupiah($po['harga_jual']) ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Bayar <span>*</span></label>
                        <input type="date" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Pembayaran <span>*</span></label>
                        <select name="jenis_pembayaran" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            <option value="dp">DP</option>
                            <option value="cicilan">Cicilan</option>
                            <option value="pelunasan">Pelunasan</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Jumlah Bayar (Rp) <span>*</span></label>
                        <input type="number" name="jumlah_bayar" class="form-control" min="1" placeholder="mis: 5000000" required>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Catatan (opsional)">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalBayar')">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
<?php if ($id_pesan_filter): ?>
document.addEventListener('DOMContentLoaded', () => openModal('modalBayar'));
<?php endif; ?>
</script>
