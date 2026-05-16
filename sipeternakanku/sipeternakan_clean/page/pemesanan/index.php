<?php
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'save') {
        $id_sapi    = intval($_POST['id_sapi']);
        $nama       = sanitize($koneksi, $_POST['nama_pembeli']);
        $hp         = sanitize($koneksi, $_POST['no_hp']);
        $tgl        = sanitize($koneksi, $_POST['tanggal_pesan']);
        $harga_jual = intval($_POST['harga_jual']);
        $dp         = intval($_POST['dp'] ?? 0);
        $ket        = sanitize($koneksi, $_POST['keterangan'] ?? '');
        $edit_id    = intval($_POST['edit_id'] ?? 0);

        if (!$id_sapi || !$nama || !$hp || !$tgl || !$harga_jual) {
            $error = 'Semua field wajib diisi.';
        } elseif (!preg_match('/^[0-9]{8,15}$/', preg_replace('/[-\s]/', '', $hp))) {
            $error = 'Format nomor HP tidak valid.';
        } elseif ($harga_jual <= 0) {
            $error = 'Harga jual harus lebih dari 0.';
        } else {
            // check sapi available
            $sapi = $koneksi->query("SELECT status FROM sapi WHERE id=$id_sapi LIMIT 1")->fetch_assoc();
            if (!$sapi) {
                $error = 'Sapi tidak ditemukan.';
            } elseif (!$edit_id && $sapi['status'] !== 'aktif') {
                $error = 'Sapi sudah dipesan atau terjual.';
            } else {
                $status = $dp >= $harga_jual ? 'lunas' : ($dp > 0 ? 'dp' : 'pending');
                if ($edit_id) {
                    $koneksi->query("UPDATE pemesanan SET id_sapi=$id_sapi, nama_pembeli='$nama', no_hp='$hp', tanggal_pesan='$tgl', harga_jual=$harga_jual, dp=$dp, status='$status', keterangan='$ket' WHERE id=$edit_id");
                    $msg = 'Pemesanan diperbarui.';
                } else {
                    $koneksi->query("INSERT INTO pemesanan (id_sapi, nama_pembeli, no_hp, tanggal_pesan, harga_jual, dp, status, keterangan) VALUES ($id_sapi, '$nama', '$hp', '$tgl', $harga_jual, $dp, '$status', '$ket')");
                    // Update sapi status
                    $new_status = $status === 'lunas' ? 'terjual' : 'dipesan';
                    $koneksi->query("UPDATE sapi SET status='$new_status' WHERE id=$id_sapi");
                    $msg = 'Pemesanan berhasil disimpan.';
                }
            }
        }
    } elseif ($act === 'delete') {
        $del_id = intval($_POST['del_id']);
        $row = $koneksi->query("SELECT id_sapi FROM pemesanan WHERE id=$del_id LIMIT 1")->fetch_assoc();
        if ($row) {
            $koneksi->query("DELETE FROM pemesanan WHERE id=$del_id");
            $koneksi->query("UPDATE sapi SET status='aktif' WHERE id={$row['id_sapi']}");
        }
        $msg = 'Pemesanan dibatalkan.';
    }
}

$list = $koneksi->query("
    SELECT pm.*, s.kode_sapi,
           (SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran WHERE id_pemesanan=pm.id) as total_bayar
    FROM pemesanan pm JOIN sapi s ON pm.id_sapi=s.id
    ORDER BY pm.created_at DESC
");
$sapi_opts = $koneksi->query("SELECT id, kode_sapi FROM sapi WHERE status='aktif' ORDER BY kode_sapi");

$edit_data = null;
if (isset($_GET['aksi']) && $_GET['aksi'] === 'edit' && isset($_GET['id'])) {
    $eid = intval($_GET['id']);
    $edit_data = $koneksi->query("SELECT * FROM pemesanan WHERE id=$eid LIMIT 1")->fetch_assoc();
}
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Pemesanan</div>
        <h1>📋 Pemesanan Sapi</h1>
        <p>Kelola data pemesanan dan transaksi penjualan sapi</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalPesan')">＋ Buat Pemesanan</button>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><div class="card-title">Daftar Pemesanan</div></div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Kode Sapi</th><th>Pembeli</th><th>No HP</th><th>Tgl Pesan</th><th>Harga Jual</th><th>DP</th><th>Total Bayar</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if ($list && $list->num_rows > 0): $no=1; while ($p = $list->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($p['kode_sapi']) ?></strong></td>
                    <td><?= htmlspecialchars($p['nama_pembeli']) ?></td>
                    <td><?= htmlspecialchars($p['no_hp']) ?></td>
                    <td><?= date('d M Y', strtotime($p['tanggal_pesan'])) ?></td>
                    <td><?= formatRupiah($p['harga_jual']) ?></td>
                    <td><?= formatRupiah($p['dp']) ?></td>
                    <td class="text-green font-bold"><?= formatRupiah($p['total_bayar']) ?></td>
                    <td>
                        <?php $sb=['pending'=>'badge-gray','dp'=>'badge-amber','lunas'=>'badge-green']; ?>
                        <span class="badge <?= $sb[$p['status']] ?? 'badge-gray' ?>"><?= ucfirst($p['status']) ?></span>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="index.php?page=pembayaran&id_pesan=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">💳 Bayar</a>
                            <?php if ($p['status'] !== 'lunas'): ?>
                            <a href="index.php?page=pemesanan&aksi=edit&id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Batalkan pemesanan ini?')">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="del_id" value="<?= $p['id'] ?>">
                                <button class="btn btn-danger btn-sm">✕</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="10"><div class="empty-state"><div class="icon">📋</div><h3>Belum ada pemesanan</h3></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Pemesanan -->
<div class="modal-overlay <?= $edit_data ? 'open' : '' ?>" id="modalPesan">
    <div class="modal-box" style="max-width:640px">
        <div class="modal-header">
            <div class="modal-title"><?= $edit_data ? '✏️ Edit Pemesanan' : '📋 Buat Pemesanan Baru' ?></div>
            <button class="modal-close" onclick="closeModal('modalPesan')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <?php if ($edit_data): ?><input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>"><?php endif; ?>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Pilih Sapi <span>*</span></label>
                        <select name="id_sapi" class="form-control" required>
                            <option value="">-- Pilih Sapi --</option>
                            <?php
                            if ($edit_data) {
                                // Show all sapi for edit
                                $all_s = $koneksi->query("SELECT id, kode_sapi FROM sapi ORDER BY kode_sapi");
                                while ($s = $all_s->fetch_assoc()):
                            } else {
                                $sapi_opts->data_seek(0);
                                while ($s = $sapi_opts->fetch_assoc()):
                            }
                            ?>
                            <option value="<?= $s['id'] ?>" <?= ($edit_data && $edit_data['id_sapi']==$s['id'])?'selected':'' ?>><?= htmlspecialchars($s['kode_sapi']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Pembeli <span>*</span></label>
                        <input type="text" name="nama_pembeli" class="form-control" value="<?= htmlspecialchars($edit_data['nama_pembeli'] ?? '') ?>" placeholder="Nama lengkap pembeli" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No HP <span>*</span></label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($edit_data['no_hp'] ?? '') ?>" placeholder="mis: 081234567890" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Pesan <span>*</span></label>
                        <input type="date" name="tanggal_pesan" class="form-control" value="<?= $edit_data['tanggal_pesan'] ?? date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Jual (Rp) <span>*</span></label>
                        <input type="number" name="harga_jual" class="form-control" value="<?= $edit_data['harga_jual'] ?? '' ?>" min="1" placeholder="mis: 20000000" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">DP Awal (Rp)</label>
                        <input type="number" name="dp" class="form-control" value="<?= $edit_data['dp'] ?? 0 ?>" min="0" placeholder="0 jika belum ada DP">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($edit_data['keterangan'] ?? '') ?>" placeholder="Catatan tambahan">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalPesan')">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>
