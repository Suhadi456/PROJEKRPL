<?php
$aksi = $_GET['aksi'] ?? '';
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $kode     = sanitize($koneksi, $_POST['kode_sapi']);
        $berat    = floatval($_POST['berat_awal']);
        $tgl      = sanitize($koneksi, $_POST['tanggal_pembelian']);
        $harga    = intval($_POST['harga_beli']);
        $status   = sanitize($koneksi, $_POST['status'] ?? 'digemukkan');
        $ket      = sanitize($koneksi, $_POST['keterangan'] ?? '');
        $edit_id  = intval($_POST['edit_id'] ?? 0);

        $valid_status = ['digemukkan','siap_jual','dipesan','terjual'];
        if (!in_array($status, $valid_status)) $status = 'digemukkan';

        if (!$kode || !$berat || !$tgl || !$harga) {
            $error = 'Semua field wajib diisi.';
        } elseif ($berat <= 0) {
            $error = 'Berat awal harus lebih dari 0.';
        } elseif ($harga <= 0) {
            $error = 'Harga beli harus lebih dari 0.';
        } elseif ($tgl > date('Y-m-d')) {
            $error = 'Tanggal pembelian tidak boleh melebihi hari ini.';
        } else {
            if ($edit_id) {
                $existing = $koneksi->query("SELECT status FROM sapi WHERE id=$edit_id LIMIT 1")->fetch_assoc();
                if ($existing['status'] === 'terjual' && $status !== 'terjual') {
                    $error = 'Status sapi yang sudah terjual tidak bisa diubah kembali.';
                } else {
                    $dup = $koneksi->query("SELECT id FROM sapi WHERE kode_sapi='$kode' AND id!=$edit_id LIMIT 1");
                    if ($dup->num_rows > 0) {
                        $error = 'Kode sapi sudah digunakan oleh sapi lain.';
                    } else {
                        $koneksi->query("UPDATE sapi SET kode_sapi='$kode', berat_awal=$berat, tanggal_pembelian='$tgl', harga_beli=$harga, status='$status', keterangan='$ket' WHERE id=$edit_id");
                        $msg = 'Data sapi berhasil diperbarui.';
                        $aksi = '';
                    }
                }
            } else {
                $dup = $koneksi->query("SELECT id FROM sapi WHERE kode_sapi='$kode' LIMIT 1");
                if ($dup->num_rows > 0) {
                    $error = 'Kode sapi sudah digunakan.';
                } else {
                    $koneksi->query("INSERT INTO sapi (kode_sapi, berat_awal, tanggal_pembelian, harga_beli, status, keterangan) VALUES ('$kode', $berat, '$tgl', $harga, '$status', '$ket')");
                    $msg = 'Sapi baru berhasil ditambahkan.';
                    $aksi = '';
                }
            }
        }
    } elseif ($act === 'delete') {
        $del_id = intval($_POST['del_id']);
        $check = $koneksi->query("SELECT status FROM sapi WHERE id=$del_id LIMIT 1")->fetch_assoc();
        if ($check['status'] === 'terjual') {
            $error = 'Sapi yang sudah terjual tidak dapat dihapus.';
        } elseif ($check['status'] === 'dipesan') {
            $error = 'Sapi yang sedang dipesan tidak dapat dihapus. Batalkan pemesanan terlebih dahulu.';
        } else {
            $koneksi->query("DELETE FROM sapi WHERE id=$del_id");
            $msg = 'Data sapi berhasil dihapus.';
        }
    }
}

$edit_data = null;
if ($aksi === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $edit_data = $koneksi->query("SELECT * FROM sapi WHERE id=$edit_id LIMIT 1")->fetch_assoc();
}

$search = sanitize($koneksi, $_GET['q'] ?? '');
$filter_status = sanitize($koneksi, $_GET['status'] ?? '');
$where = "WHERE 1=1";
if ($search) $where .= " AND kode_sapi LIKE '%$search%'";
if ($filter_status) $where .= " AND status='$filter_status'";
$sapi_list = $koneksi->query("SELECT * FROM sapi $where ORDER BY created_at DESC");

$status_badges = [
    'digemukkan' => 'badge-blue',
    'siap_jual'  => 'badge-green',
    'dipesan'    => 'badge-amber',
    'terjual'    => 'badge-gray',
];
$status_labels = [
    'digemukkan' => 'Digemukkan',
    'siap_jual'  => 'Siap Jual',
    'dipesan'    => 'Dipesan',
    'terjual'    => 'Terjual',
];
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Data Sapi</div>
        <h1>🐄 Data Sapi</h1>
        <p>Kelola seluruh data sapi peternakan Anda</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalTambah')">＋ Tambah Sapi</button>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" action="index.php" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="page" value="data_sapi">
            <div class="search-bar">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" class="form-control" placeholder="Cari kode sapi..." value="<?= htmlspecialchars($search) ?>" style="padding-left:38px;min-width:220px">
            </div>
            <select name="status" class="form-control" style="width:180px">
                <option value="">Semua Status</option>
                <option value="digemukkan" <?= $filter_status==='digemukkan'?'selected':'' ?>>Digemukkan</option>
                <option value="siap_jual" <?= $filter_status==='siap_jual'?'selected':'' ?>>Siap Jual</option>
                <option value="dipesan" <?= $filter_status==='dipesan'?'selected':'' ?>>Dipesan</option>
                <option value="terjual" <?= $filter_status==='terjual'?'selected':'' ?>>Terjual</option>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="index.php?page=data_sapi" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Daftar Sapi</div>
        <span class="badge badge-green"><?= $sapi_list ? $sapi_list->num_rows : 0 ?> data</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode Sapi</th>
                    <th>Berat Awal</th>
                    <th>Berat Terkini</th>
                    <th>Tgl Pembelian</th>
                    <th>Harga Beli</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sapi_list && $sapi_list->num_rows > 0):
                    $no = 1;
                    while ($s = $sapi_list->fetch_assoc()):
                        $berat_terkini = $koneksi->query("SELECT berat FROM berat_sapi WHERE id_sapi={$s['id']} ORDER BY tanggal DESC, id DESC LIMIT 1")->fetch_assoc();
                ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($s['kode_sapi']) ?></strong></td>
                    <td><?= number_format($s['berat_awal'], 1) ?> kg</td>
                    <td><?= $berat_terkini ? number_format($berat_terkini['berat'], 1).' kg' : '<span class="text-muted">-</span>' ?></td>
                    <td><?= date('d M Y', strtotime($s['tanggal_pembelian'])) ?></td>
                    <td><?= formatRupiah($s['harga_beli']) ?></td>
                    <td><span class="badge <?= $status_badges[$s['status']] ?? 'badge-gray' ?>"><?= $status_labels[$s['status']] ?? ucfirst($s['status']) ?></span></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="index.php?page=data_sapi&aksi=edit&id=<?= $s['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                            <?php if (!in_array($s['status'], ['terjual','dipesan'])): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus sapi <?= htmlspecialchars($s['kode_sapi']) ?>?')">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="del_id" value="<?= $s['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="8"><div class="empty-state"><div class="icon">🐄</div><h3>Belum ada data sapi</h3></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">➕ Tambah Sapi Baru</div>
            <button class="modal-close" onclick="closeModal('modalTambah')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Kode Sapi <span>*</span></label>
                        <input type="text" name="kode_sapi" class="form-control" placeholder="mis: SP-005" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Berat Awal (kg) <span>*</span></label>
                        <input type="number" name="berat_awal" class="form-control" step="0.1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Pembelian <span>*</span></label>
                        <input type="date" name="tanggal_pembelian" class="form-control" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Beli (Rp) <span>*</span></label>
                        <input type="number" name="harga_beli" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Awal</label>
                        <select name="status" class="form-control">
                            <option value="digemukkan">Digemukkan</option>
                            <option value="siap_jual">Siap Jual</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php if ($aksi === 'edit' && $edit_data): ?>
<script>document.addEventListener('DOMContentLoaded', () => openModal('modalEdit'));</script>
<div class="modal-overlay open" id="modalEdit">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">✏️ Edit Sapi: <?= htmlspecialchars($edit_data['kode_sapi']) ?></div>
            <a href="index.php?page=data_sapi" class="modal-close">✕</a>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Kode Sapi</label>
                        <input type="text" name="kode_sapi" class="form-control" value="<?= htmlspecialchars($edit_data['kode_sapi']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Berat Awal (kg)</label>
                        <input type="number" name="berat_awal" class="form-control" value="<?= $edit_data['berat_awal'] ?>" step="0.1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Pembelian</label>
                        <input type="date" name="tanggal_pembelian" class="form-control" value="<?= $edit_data['tanggal_pembelian'] ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Beli (Rp)</label>
                        <input type="number" name="harga_beli" class="form-control" value="<?= $edit_data['harga_beli'] ?>" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" <?= $edit_data['status']==='terjual'?'disabled':'' ?>>
                            <option value="digemukkan" <?= $edit_data['status']==='digemukkan'?'selected':'' ?>>Digemukkan</option>
                            <option value="siap_jual" <?= $edit_data['status']==='siap_jual'?'selected':'' ?>>Siap Jual</option>
                            <option value="dipesan" <?= $edit_data['status']==='dipesan'?'selected':'' ?>>Dipesan</option>
                            <option value="terjual" <?= $edit_data['status']==='terjual'?'selected':'' ?>>Terjual</option>
                        </select>
                        <?php if ($edit_data['status']==='terjual'): ?>
                        <input type="hidden" name="status" value="terjual">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($edit_data['keterangan'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="index.php?page=data_sapi" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">💾 Perbarui</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
