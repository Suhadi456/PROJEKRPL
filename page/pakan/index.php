<?php
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'save') {
        $id_sapi      = intval($_POST['id_sapi']);
        $tgl          = sanitize($koneksi, $_POST['tanggal']);
        $waktu        = sanitize($koneksi, $_POST['waktu_pakan']);
        $jenis        = sanitize($koneksi, $_POST['jenis_pakan']);
        $jumlah       = floatval($_POST['jumlah']);
        $biaya_pakan  = intval($_POST['biaya_pakan'] ?? 0);
        $ket          = sanitize($koneksi, $_POST['keterangan'] ?? '');
        $edit_id      = intval($_POST['edit_id'] ?? 0);

        if (!$id_sapi || !$tgl || !$waktu || !$jenis || !$jumlah) {
            $error = 'Semua field wajib diisi.';
        } elseif ($jumlah <= 0) {
            $error = 'Jumlah pakan harus lebih dari 0.';
        } elseif (!in_array($waktu, ['pagi','sore'])) {
            $error = 'Waktu pakan tidak valid.';
        } else {
            // Cek duplikasi (max 2 per hari per sapi: pagi dan sore)
            $dup_query = "SELECT id FROM pakan WHERE id_sapi=$id_sapi AND tanggal='$tgl' AND waktu_pakan='$waktu'";
            if ($edit_id) $dup_query .= " AND id!=$edit_id";
            $dup = $koneksi->query($dup_query);
            if ($dup->num_rows > 0) {
                $error = "Sudah ada pencatatan pakan $waktu untuk sapi ini pada tanggal tersebut.";
            } else {
                if ($edit_id) {
                    $koneksi->query("UPDATE pakan SET id_sapi=$id_sapi, tanggal='$tgl', waktu_pakan='$waktu', jenis_pakan='$jenis', jumlah=$jumlah, biaya_pakan=$biaya_pakan, keterangan='$ket' WHERE id=$edit_id");
                    $msg = 'Data pakan diperbarui.';
                } else {
                    $koneksi->query("INSERT INTO pakan (id_sapi, tanggal, waktu_pakan, jenis_pakan, jumlah, biaya_pakan, keterangan) VALUES ($id_sapi, '$tgl', '$waktu', '$jenis', $jumlah, $biaya_pakan, '$ket')");
                    // Auto-sync biaya pakan ke tabel biaya
                    if ($biaya_pakan > 0) {
                        $kode_sapi_row = $koneksi->query("SELECT kode_sapi FROM sapi WHERE id=$id_sapi LIMIT 1")->fetch_assoc();
                        $kode = $kode_sapi_row['kode_sapi'] ?? '';
                        $jenis_biaya = "Pakan $waktu - $jenis";
                        $koneksi->query("INSERT INTO biaya (id_sapi, tanggal, jenis_biaya, jumlah, keterangan) VALUES ($id_sapi, '$tgl', '$jenis_biaya', $biaya_pakan, 'Auto dari pencatatan pakan')");
                    }
                    $msg = 'Pakan berhasil dicatat.';
                }
            }
        }
    } elseif ($act === 'delete') {
        $del_id = intval($_POST['del_id']);
        $koneksi->query("DELETE FROM pakan WHERE id=$del_id");
        $msg = 'Data pakan dihapus.';
    }
}

$filter_sapi = intval($_GET['id_sapi'] ?? 0);
$filter_tgl  = sanitize($koneksi, $_GET['tgl'] ?? '');
$where = "WHERE 1=1";
if ($filter_sapi) $where .= " AND p.id_sapi=$filter_sapi";
if ($filter_tgl) $where .= " AND p.tanggal='$filter_tgl'";

$list = $koneksi->query("SELECT p.*, s.kode_sapi FROM pakan p JOIN sapi s ON p.id_sapi=s.id $where ORDER BY p.tanggal DESC, p.waktu_pakan ASC");
$sapi_opts = $koneksi->query("SELECT id, kode_sapi FROM sapi WHERE status IN ('digemukkan','siap_jual') ORDER BY kode_sapi");
$sapi_all = $koneksi->query("SELECT id, kode_sapi FROM sapi ORDER BY kode_sapi");

$edit_data = null;
if (isset($_GET['aksi']) && $_GET['aksi'] === 'edit' && isset($_GET['id'])) {
    $eid = intval($_GET['id']);
    $edit_data = $koneksi->query("SELECT * FROM pakan WHERE id=$eid LIMIT 1")->fetch_assoc();
}
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Pakan</div>
        <h1>🌾 Pencatatan Pakan</h1>
        <p>Catat pemberian pakan sapi (maks. 2x/hari: pagi & sore)</p>
    </div>
    <button class="btn btn-warning" onclick="openModal('modalPakan')">＋ Catat Pakan</button>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" action="index.php" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="page" value="pakan">
            <select name="id_sapi" class="form-control" style="width:180px">
                <option value="">Semua Sapi</option>
                <?php $sapi_all->data_seek(0); while ($s = $sapi_all->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>" <?= $filter_sapi==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['kode_sapi']) ?></option>
                <?php endwhile; ?>
            </select>
            <input type="date" name="tgl" class="form-control" style="width:180px" value="<?= htmlspecialchars($filter_tgl) ?>">
            <button type="submit" class="btn btn-warning">Filter</button>
            <a href="index.php?page=pakan" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Riwayat Pemberian Pakan</div>
        <span class="badge badge-green"><?= $list ? $list->num_rows : 0 ?> data</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Sapi</th><th>Tanggal</th><th>Waktu</th><th>Jenis Pakan</th><th>Jumlah (kg)</th><th>Biaya</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($list && $list->num_rows > 0):
                $no=1; while ($r=$list->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($r['kode_sapi']) ?></strong></td>
                    <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                    <td><span class="badge <?= $r['waktu_pakan']==='pagi'?'badge-amber':'badge-blue' ?>"><?= ucfirst($r['waktu_pakan']) ?></span></td>
                    <td><?= htmlspecialchars($r['jenis_pakan']) ?></td>
                    <td><?= number_format($r['jumlah'], 1) ?> kg</td>
                    <td><?= $r['biaya_pakan']>0 ? formatRupiah($r['biaya_pakan']) : '<span class="text-muted">-</span>' ?></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="index.php?page=pakan&aksi=edit&id=<?= $r['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus data pakan ini?')">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="del_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="8"><div class="empty-state"><div class="icon">🌾</div><h3>Belum ada data pakan</h3></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pakan -->
<div class="modal-overlay" id="modalPakan">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">🌾 Catat Pemberian Pakan</div>
            <button class="modal-close" onclick="closeModal('modalPakan')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Sapi <span>*</span></label>
                        <select name="id_sapi" class="form-control" required>
                            <option value="">-- Pilih Sapi --</option>
                            <?php if ($sapi_opts): $sapi_opts->data_seek(0); while ($s=$sapi_opts->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['kode_sapi']) ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal <span>*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu <span>*</span></label>
                        <select name="waktu_pakan" class="form-control" required>
                            <option value="pagi">🌅 Pagi</option>
                            <option value="sore">🌆 Sore</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Pakan <span>*</span></label>
                        <input type="text" name="jenis_pakan" class="form-control" placeholder="mis: Rumput, Konsentrat" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah (kg) <span>*</span></label>
                        <input type="number" name="jumlah" class="form-control" step="0.1" min="0.1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Biaya Pakan (Rp)</label>
                        <input type="number" name="biaya_pakan" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalPakan')">Batal</button>
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
            <div class="modal-title">✏️ Edit Data Pakan</div>
            <a href="index.php?page=pakan" class="modal-close">✕</a>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Sapi</label>
                        <select name="id_sapi" class="form-control" required>
                            <?php $sapi_all->data_seek(0); while ($s=$sapi_all->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>" <?= $edit_data['id_sapi']==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['kode_sapi']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= $edit_data['tanggal'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu</label>
                        <select name="waktu_pakan" class="form-control">
                            <option value="pagi" <?= $edit_data['waktu_pakan']==='pagi'?'selected':'' ?>>Pagi</option>
                            <option value="sore" <?= $edit_data['waktu_pakan']==='sore'?'selected':'' ?>>Sore</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Pakan</label>
                        <input type="text" name="jenis_pakan" class="form-control" value="<?= htmlspecialchars($edit_data['jenis_pakan']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah (kg)</label>
                        <input type="number" name="jumlah" class="form-control" value="<?= $edit_data['jumlah'] ?>" step="0.1" min="0.1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Biaya (Rp)</label>
                        <input type="number" name="biaya_pakan" class="form-control" value="<?= $edit_data['biaya_pakan'] ?? 0 ?>" min="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="index.php?page=pakan" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-warning">💾 Perbarui</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
