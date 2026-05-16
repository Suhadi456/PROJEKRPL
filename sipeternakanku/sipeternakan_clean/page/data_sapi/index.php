<?php
$aksi = $_GET['aksi'] ?? '';
$msg = '';
$error = '';

// === SAVE / UPDATE / DELETE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $kode     = sanitize($koneksi, $_POST['kode_sapi']);
        $berat    = floatval($_POST['berat_awal']);
        $tgl      = sanitize($koneksi, $_POST['tanggal_pembelian']);
        $harga    = intval($_POST['harga_beli']);
        $edit_id  = intval($_POST['edit_id'] ?? 0);

        if (!$kode || !$berat || !$tgl || !$harga) {
            $error = 'Semua field wajib diisi.';
        } elseif ($berat <= 0) {
            $error = 'Berat awal harus lebih dari 0.';
        } elseif ($harga <= 0) {
            $error = 'Harga beli harus lebih dari 0.';
        } else {
            if ($edit_id) {
                // Check duplicate kode
                $dup = $koneksi->query("SELECT id FROM sapi WHERE kode_sapi='$kode' AND id!=$edit_id LIMIT 1");
                if ($dup->num_rows > 0) {
                    $error = 'Kode sapi sudah digunakan oleh sapi lain.';
                } else {
                    $koneksi->query("UPDATE sapi SET kode_sapi='$kode', berat_awal=$berat, tanggal_pembelian='$tgl', harga_beli=$harga WHERE id=$edit_id");
                    $msg = 'Data sapi berhasil diperbarui.';
                    $aksi = '';
                }
            } else {
                $dup = $koneksi->query("SELECT id FROM sapi WHERE kode_sapi='$kode' LIMIT 1");
                if ($dup->num_rows > 0) {
                    $error = 'Kode sapi sudah digunakan.';
                } else {
                    $koneksi->query("INSERT INTO sapi (kode_sapi, berat_awal, tanggal_pembelian, harga_beli) VALUES ('$kode', $berat, '$tgl', $harga)");
                    $msg = 'Sapi baru berhasil ditambahkan.';
                    $aksi = '';
                }
            }
        }
    } elseif ($act === 'delete') {
        $del_id = intval($_POST['del_id']);
        // Check if terjual
        $check = $koneksi->query("SELECT status FROM sapi WHERE id=$del_id LIMIT 1")->fetch_assoc();
        if ($check['status'] === 'terjual') {
            $error = 'Sapi yang sudah terjual tidak dapat dihapus.';
        } else {
            $koneksi->query("DELETE FROM sapi WHERE id=$del_id");
            $msg = 'Data sapi berhasil dihapus.';
        }
    }
}

// === EDIT - load data ===
$edit_data = null;
if ($aksi === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $edit_data = $koneksi->query("SELECT * FROM sapi WHERE id=$edit_id LIMIT 1")->fetch_assoc();
}

// === SEARCH + LIST ===
$search = sanitize($koneksi, $_GET['q'] ?? '');
$filter_status = sanitize($koneksi, $_GET['status'] ?? '');
$where = "WHERE 1=1";
if ($search) $where .= " AND kode_sapi LIKE '%$search%'";
if ($filter_status) $where .= " AND status='$filter_status'";
$sapi_list = $koneksi->query("SELECT * FROM sapi $where ORDER BY created_at DESC");
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

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" action="index.php" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="page" value="data_sapi">
            <div class="search-bar">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" class="form-control" placeholder="Cari kode sapi..." value="<?= htmlspecialchars($search) ?>" style="padding-left:38px;min-width:220px">
            </div>
            <select name="status" class="form-control" style="width:160px">
                <option value="">Semua Status</option>
                <option value="aktif" <?= $filter_status==='aktif'?'selected':'' ?>>Aktif</option>
                <option value="dipesan" <?= $filter_status==='dipesan'?'selected':'' ?>>Dipesan</option>
                <option value="terjual" <?= $filter_status==='terjual'?'selected':'' ?>>Terjual</option>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="index.php?page=data_sapi" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Daftar Sapi</div>
        <span class="badge badge-green"><?= $sapi_list->num_rows ?> data</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode Sapi</th>
                    <th>Berat Awal</th>
                    <th>Tgl Pembelian</th>
                    <th>Harga Beli</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sapi_list && $sapi_list->num_rows > 0):
                    $no = 1;
                    while ($s = $sapi_list->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($s['kode_sapi']) ?></strong></td>
                    <td><?= number_format($s['berat_awal'], 1) ?> kg</td>
                    <td><?= date('d M Y', strtotime($s['tanggal_pembelian'])) ?></td>
                    <td><?= formatRupiah($s['harga_beli']) ?></td>
                    <td>
                        <?php $badges=['aktif'=>'badge-green','dipesan'=>'badge-amber','terjual'=>'badge-blue']; ?>
                        <span class="badge <?= $badges[$s['status']] ?? 'badge-gray' ?>">
                            <?= ucfirst($s['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="index.php?page=data_sapi&aksi=edit&id=<?= $s['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                            <?php if ($s['status'] !== 'terjual'): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus sapi <?= htmlspecialchars($s['kode_sapi']) ?>?')">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="del_id" value="<?= $s['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️ Hapus</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <div class="icon">🐄</div>
                        <h3>Belum ada data sapi</h3>
                        <p>Klik tombol "Tambah Sapi" untuk menambahkan data baru</p>
                    </div>
                </td></tr>
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
                        <input type="number" name="berat_awal" class="form-control" placeholder="mis: 300" step="0.1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Pembelian <span>*</span></label>
                        <input type="date" name="tanggal_pembelian" class="form-control" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Beli (Rp) <span>*</span></label>
                        <input type="number" name="harga_beli" class="form-control" placeholder="mis: 15000000" min="1" required>
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

<!-- Modal Edit -->
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
                        <label class="form-label">Kode Sapi <span>*</span></label>
                        <input type="text" name="kode_sapi" class="form-control" value="<?= htmlspecialchars($edit_data['kode_sapi']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Berat Awal (kg) <span>*</span></label>
                        <input type="number" name="berat_awal" class="form-control" value="<?= $edit_data['berat_awal'] ?>" step="0.1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Pembelian <span>*</span></label>
                        <input type="date" name="tanggal_pembelian" class="form-control" value="<?= $edit_data['tanggal_pembelian'] ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Beli (Rp) <span>*</span></label>
                        <input type="number" name="harga_beli" class="form-control" value="<?= $edit_data['harga_beli'] ?>" min="1" required>
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
