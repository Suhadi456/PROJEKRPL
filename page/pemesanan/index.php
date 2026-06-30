<?php
$msg = ''; $error = '';
$role    = $_SESSION['role'] ?? 'pemilik';
$is_user = ($role === 'user');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $id_sapi    = intval($_POST['id_sapi']);
        $nama       = sanitize($koneksi, $_POST['nama_pembeli']);
        $hp         = preg_replace('/[^0-9]/', '', $_POST['no_hp'] ?? '');
        $tgl        = sanitize($koneksi, $_POST['tanggal_pesan']);
        $harga_jual = intval($_POST['harga_jual']);
        $dp         = intval($_POST['dp'] ?? 0);
        $ket        = sanitize($koneksi, $_POST['keterangan'] ?? '');
        $edit_id    = intval($_POST['edit_id'] ?? 0);

        if (!$id_sapi || !$nama || !$hp || !$tgl || !$harga_jual) {
            $error = 'Semua field wajib diisi.';
        } elseif (strlen($hp) < 8 || strlen($hp) > 15) {
            $error = 'Nomor HP harus 8–15 digit angka.';
        } elseif ($harga_jual <= 0) {
            $error = 'Harga jual harus lebih dari 0.';
        } elseif ($dp < 0) {
            $error = 'DP tidak boleh negatif.';
        } else {
            $sapi = $koneksi->query("SELECT * FROM sapi WHERE id=$id_sapi LIMIT 1")->fetch_assoc();
            if (!$sapi) {
                $error = 'Sapi tidak ditemukan.';
            } elseif (!$edit_id && !in_array($sapi['status'], ['siap_jual','digemukkan'])) {
                $error = 'Sapi sudah dipesan atau terjual, tidak bisa dipesan lagi.';
            } else {
                $status  = $dp >= $harga_jual ? 'lunas' : ($dp > 0 ? 'dp' : 'pending');
                $id_user = intval($_SESSION['user_id'] ?? 0) ?: 'NULL';

                if ($edit_id) {
                    $koneksi->query("UPDATE pemesanan SET id_sapi=$id_sapi, nama_pembeli='$nama', no_hp='$hp', tanggal_pesan='$tgl', harga_jual=$harga_jual, dp=$dp, status='$status', keterangan='$ket' WHERE id=$edit_id");
                    $msg = 'Pemesanan berhasil diperbarui.';
                } else {
                    $cek = $koneksi->query("SELECT id FROM pemesanan WHERE id_sapi=$id_sapi AND status IN ('pending','dp') LIMIT 1");
                    if ($cek->num_rows > 0) {
                        $error = 'Sapi ini sudah ada pemesanan aktif yang belum lunas.';
                    } else {
                        $koneksi->query("INSERT INTO pemesanan (id_sapi, id_user, nama_pembeli, no_hp, tanggal_pesan, harga_jual, dp, status, keterangan) VALUES ($id_sapi, $id_user, '$nama', '$hp', '$tgl', $harga_jual, $dp, '$status', '$ket')");
                        $new_status_sapi = $status === 'lunas' ? 'terjual' : 'dipesan';
                        $koneksi->query("UPDATE sapi SET status='$new_status_sapi' WHERE id=$id_sapi");
                        if ($dp > 0) {
                            $pid = $koneksi->insert_id;
                            $jenis_dp = $dp >= $harga_jual ? 'pelunasan' : 'dp';
                            $koneksi->query("INSERT INTO pembayaran (id_pemesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran, keterangan) VALUES ($pid, '$tgl', $dp, '$jenis_dp', 'Pembayaran saat pemesanan')");
                        }
                        $msg = 'Pemesanan berhasil disimpan.' . ($status==='lunas' ? ' Status sapi: Terjual.' : ' Status sapi: Dipesan.');
                    }
                }
            }
        }
    } elseif ($act === 'delete') {
        $del_id = intval($_POST['del_id']);
        $row = $koneksi->query("SELECT id_sapi, status FROM pemesanan WHERE id=$del_id LIMIT 1")->fetch_assoc();
        if ($row) {
            $koneksi->query("DELETE FROM pembayaran WHERE id_pemesanan=$del_id");
            $koneksi->query("DELETE FROM pemesanan WHERE id=$del_id");
            if ($row['status'] !== 'lunas') {
                $koneksi->query("UPDATE sapi SET status='siap_jual' WHERE id={$row['id_sapi']}");
            }
        }
        $msg = 'Pemesanan dibatalkan dan sapi dikembalikan ke status Siap Jual.';
    }
}

$list = $koneksi->query("
    SELECT pm.*, s.kode_sapi, s.status as status_sapi,
           (SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran WHERE id_pemesanan=pm.id) as total_bayar
    FROM pemesanan pm JOIN sapi s ON pm.id_sapi=s.id
    ORDER BY pm.created_at DESC
");
$sapi_opts = $koneksi->query("SELECT id, kode_sapi, harga_beli FROM sapi WHERE status IN ('siap_jual','digemukkan') ORDER BY kode_sapi");

$edit_data = null;
if (isset($_GET['aksi']) && $_GET['aksi'] === 'edit' && isset($_GET['id'])) {
    $eid = intval($_GET['id']);
    $edit_data = $koneksi->query("SELECT * FROM pemesanan WHERE id=$eid LIMIT 1")->fetch_assoc();
}

$status_badges = ['pending'=>'badge-gray','dp'=>'badge-amber','lunas'=>'badge-green'];
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Pemesanan</div>
        <h1>📋 Pemesanan Sapi</h1>
        <p>Kelola data pemesanan dan transaksi penjualan</p>
    </div>
    <button class="btn btn-warning" onclick="openModal('modalPesan')">＋ Buat Pemesanan</button>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Daftar Pemesanan</div>
        <span class="badge badge-green"><?= $list ? $list->num_rows : 0 ?> data</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Kode Sapi</th><th>Pembeli</th><th>No HP</th><th>Tgl Pesan</th><th>Harga Jual</th><th>Total Bayar</th><th>Sisa</th><th>Status</th><th>Aksi</th></tr>
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
                    <td class="fw-bold"><?= formatRupiah($r['total_bayar']) ?></td>
                    <td>
                        <?php if ($sisa > 0): ?>
                        <span style="color:#c62828"><?= formatRupiah($sisa) ?></span>
                        <?php else: ?>
                        <span class="badge badge-green">Lunas</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $status_badges[$r['status']] ?? 'badge-gray' ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <div class="d-flex gap-2" style="flex-wrap:wrap">
                            <?php if ($r['status'] !== 'lunas'): ?>
                            <a href="index.php?page=pembayaran&id_pesan=<?= $r['id'] ?>" class="btn btn-warning btn-sm">💳 Bayar</a>
                            <a href="index.php?page=pemesanan&aksi=edit&id=<?= $r['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Batalkan pemesanan ini?')">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="del_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:.85rem">✅ Selesai</span>
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

<!-- Modal Tambah Pemesanan -->
<div class="modal-overlay" id="modalPesan">
    <div class="modal-box" style="max-width:600px">
        <div class="modal-header">
            <div class="modal-title">📋 Buat Pemesanan Baru</div>
            <button class="modal-close" onclick="closeModal('modalPesan')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Pilih Sapi <span>*</span></label>
                        <select name="id_sapi" class="form-control" required onchange="isiHarga(this)">
                            <option value="">-- Pilih Sapi --</option>
                            <?php if ($sapi_opts): while ($s=$sapi_opts->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>" data-harga="<?= $s['harga_beli'] ?>"><?= htmlspecialchars($s['kode_sapi']) ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Pembeli <span>*</span></label>
                        <input type="text" name="nama_pembeli" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. HP <span>*</span></label>
                        <input type="text" name="no_hp" class="form-control" placeholder="08xxx" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Pesan <span>*</span></label>
                        <input type="date" name="tanggal_pesan" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Jual (Rp) <span>*</span></label>
                        <input type="number" name="harga_jual" id="hargaJualInput" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">DP Awal (Rp)</label>
                        <input type="number" name="dp" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalPesan')">Batal</button>
                <button type="submit" class="btn btn-warning">💾 Simpan Pemesanan</button>
            </div>
        </form>
    </div>
</div>

<?php if ($edit_data): ?>
<script>document.addEventListener('DOMContentLoaded', () => openModal('modalEdit'));</script>
<div class="modal-overlay open" id="modalEdit">
    <div class="modal-box" style="max-width:600px">
        <div class="modal-header">
            <div class="modal-title">✏️ Edit Pemesanan</div>
            <a href="index.php?page=pemesanan" class="modal-close">✕</a>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
            <input type="hidden" name="id_sapi" value="<?= $edit_data['id_sapi'] ?>">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nama Pembeli</label>
                        <input type="text" name="nama_pembeli" class="form-control" value="<?= htmlspecialchars($edit_data['nama_pembeli']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($edit_data['no_hp']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Pesan</label>
                        <input type="date" name="tanggal_pesan" class="form-control" value="<?= $edit_data['tanggal_pesan'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Jual (Rp)</label>
                        <input type="number" name="harga_jual" class="form-control" value="<?= $edit_data['harga_jual'] ?>" required>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($edit_data['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="index.php?page=pemesanan" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-warning">💾 Perbarui</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function isiHarga(sel) {
    const opt = sel.options[sel.selectedIndex];
    const h = opt.getAttribute('data-harga');
    if (h) document.getElementById('hargaJualInput').value = h;
}
</script>
