<?php
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'save') {
        $id_sapi   = intval($_POST['id_sapi']);
        $tgl       = sanitize($koneksi, $_POST['tanggal']);
        $jenis     = sanitize($koneksi, $_POST['jenis_biaya']);
        $jumlah    = intval($_POST['jumlah']);
        $ket       = sanitize($koneksi, $_POST['keterangan'] ?? '');
        $edit_id   = intval($_POST['edit_id'] ?? 0);

        if (!$id_sapi || !$tgl || !$jenis || !$jumlah) {
            $error = 'Semua field wajib diisi.';
        } elseif ($jumlah <= 0) {
            $error = 'Jumlah biaya harus lebih dari 0.';
        } else {
            if ($edit_id) {
                $koneksi->query("UPDATE biaya SET id_sapi=$id_sapi, tanggal='$tgl', jenis_biaya='$jenis', jumlah=$jumlah, keterangan='$ket' WHERE id=$edit_id");
                $msg = 'Data biaya diperbarui.';
            } else {
                $koneksi->query("INSERT INTO biaya (id_sapi, tanggal, jenis_biaya, jumlah, keterangan) VALUES ($id_sapi, '$tgl', '$jenis', $jumlah, '$ket')");
                $msg = 'Biaya berhasil dicatat.';
            }
        }
    } elseif ($act === 'delete') {
        $del_id = intval($_POST['del_id']);
        $koneksi->query("DELETE FROM biaya WHERE id=$del_id");
        $msg = 'Data biaya dihapus.';
    }
}

$filter_sapi = intval($_GET['id_sapi'] ?? 0);
$where = $filter_sapi ? "WHERE b.id_sapi=$filter_sapi" : "WHERE 1=1";
$list = $koneksi->query("SELECT b.*, s.kode_sapi FROM biaya b JOIN sapi s ON b.id_sapi=s.id $where ORDER BY b.tanggal DESC, b.id DESC");
$sapi_opts = $koneksi->query("SELECT id, kode_sapi FROM sapi ORDER BY kode_sapi");

// Total per sapi
$total_query = $filter_sapi
    ? $koneksi->query("SELECT COALESCE(SUM(jumlah),0) as total FROM biaya WHERE id_sapi=$filter_sapi")->fetch_assoc()['total'] ?? 0
    : $koneksi->query("SELECT COALESCE(SUM(jumlah),0) as total FROM biaya")->fetch_assoc()['total'] ?? 0;

$edit_data = null;
if (isset($_GET['aksi']) && $_GET['aksi']==='edit' && isset($_GET['id'])) {
    $eid = intval($_GET['id']);
    $edit_data = $koneksi->query("SELECT * FROM biaya WHERE id=$eid LIMIT 1")->fetch_assoc();
}
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Biaya</div>
        <h1>💰 Pencatatan Biaya</h1>
        <p>Catat seluruh biaya operasional peternakan</p>
    </div>
    <button class="btn btn-warning" onclick="openModal('modalBiaya')">＋ Catat Biaya</button>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon amber">💸</div>
        <div><div class="stat-label"><?= $filter_sapi ? 'Total Biaya Sapi Ini' : 'Total Semua Biaya' ?></div>
        <div class="stat-value" style="font-size:1.2rem"><?= formatRupiah($total_query) ?></div></div>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" action="index.php" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="page" value="biaya">
            <select name="id_sapi" class="form-control" style="width:200px">
                <option value="">Semua Sapi</option>
                <?php $sapi_opts->data_seek(0); while ($s=$sapi_opts->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>" <?= $filter_sapi==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['kode_sapi']) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-warning">Filter</button>
            <a href="index.php?page=biaya" class="btn btn-secondary">Tampilkan Semua</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Daftar Biaya Operasional</div>
        <span class="badge badge-green"><?= $list ? $list->num_rows : 0 ?> data</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Sapi</th><th>Tanggal</th><th>Jenis Biaya</th><th>Jumlah</th><th>Keterangan</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($list && $list->num_rows > 0):
                $no=1; while ($r=$list->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($r['kode_sapi']) ?></strong></td>
                    <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($r['jenis_biaya']) ?></td>
                    <td class="fw-bold"><?= formatRupiah($r['jumlah']) ?></td>
                    <td><?= htmlspecialchars($r['keterangan'] ?? '') ?></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="index.php?page=biaya&aksi=edit&id=<?= $r['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus data biaya ini?')">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="del_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="7"><div class="empty-state"><div class="icon">💰</div><h3>Belum ada data biaya</h3></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="modalBiaya">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">💰 Catat Biaya</div>
            <button class="modal-close" onclick="closeModal('modalBiaya')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Sapi <span>*</span></label>
                        <select name="id_sapi" class="form-control" required>
                            <option value="">-- Pilih Sapi --</option>
                            <?php $sapi_opts->data_seek(0); while ($s=$sapi_opts->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['kode_sapi']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal <span>*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Biaya <span>*</span></label>
                        <input type="text" name="jenis_biaya" class="form-control" placeholder="mis: Obat, Vaksin, Pakan" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah (Rp) <span>*</span></label>
                        <input type="number" name="jumlah" class="form-control" min="1" required>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalBiaya')">Batal</button>
                <button type="submit" class="btn btn-warning">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php if ($edit_data): ?>
<script>document.addEventListener('DOMContentLoaded', () => openModal('modalEdit'));</script>
<div class="modal-overlay open" id="modalEdit">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">✏️ Edit Biaya</div>
            <a href="index.php?page=biaya" class="modal-close">✕</a>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Sapi</label>
                        <select name="id_sapi" class="form-control" required>
                            <?php $sapi_opts->data_seek(0); while ($s=$sapi_opts->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>" <?= $edit_data['id_sapi']==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['kode_sapi']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= $edit_data['tanggal'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Biaya</label>
                        <input type="text" name="jenis_biaya" class="form-control" value="<?= htmlspecialchars($edit_data['jenis_biaya']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah (Rp)</label>
                        <input type="number" name="jumlah" class="form-control" value="<?= $edit_data['jumlah'] ?>" min="1" required>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($edit_data['keterangan'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="index.php?page=biaya" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-warning">💾 Perbarui</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
