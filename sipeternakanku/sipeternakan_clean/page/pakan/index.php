<?php
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $id_sapi     = intval($_POST['id_sapi']);
        $tanggal     = sanitize($koneksi, $_POST['tanggal']);
        $waktu       = sanitize($koneksi, $_POST['waktu_pakan']);
        $jenis       = sanitize($koneksi, $_POST['jenis_pakan']);
        $jumlah      = floatval($_POST['jumlah']);
        $keterangan  = sanitize($koneksi, $_POST['keterangan'] ?? '');
        $edit_id     = intval($_POST['edit_id'] ?? 0);

        if (!$id_sapi || !$tanggal || !$waktu || !$jenis || !$jumlah) {
            $error = 'Semua field wajib diisi.';
        } elseif ($jumlah <= 0) {
            $error = 'Jumlah pakan harus lebih dari 0.';
        } elseif (!in_array($waktu, ['pagi','sore'])) {
            $error = 'Waktu pakan tidak valid.';
        } else {
            // check sapi exists
            $cek = $koneksi->query("SELECT id FROM sapi WHERE id=$id_sapi LIMIT 1");
            if ($cek->num_rows === 0) {
                $error = 'Sapi tidak ditemukan.';
            } else {
                if ($edit_id) {
                    $koneksi->query("UPDATE pakan SET id_sapi=$id_sapi, tanggal='$tanggal', waktu_pakan='$waktu', jenis_pakan='$jenis', jumlah=$jumlah, keterangan='$keterangan' WHERE id=$edit_id");
                    $msg = 'Data pakan berhasil diperbarui.';
                } else {
                    $koneksi->query("INSERT INTO pakan (id_sapi, tanggal, waktu_pakan, jenis_pakan, jumlah, keterangan) VALUES ($id_sapi, '$tanggal', '$waktu', '$jenis', $jumlah, '$keterangan')");
                    $msg = 'Data pakan berhasil dicatat.';
                }
            }
        }
    } elseif ($act === 'delete') {
        $del_id = intval($_POST['del_id']);
        $koneksi->query("DELETE FROM pakan WHERE id=$del_id");
        $msg = 'Data pakan berhasil dihapus.';
    }
}

// Filter
$filter_sapi = intval($_GET['id_sapi'] ?? 0);
$filter_tgl  = sanitize($koneksi, $_GET['tgl'] ?? '');
$where = "WHERE 1=1";
if ($filter_sapi) $where .= " AND p.id_sapi=$filter_sapi";
if ($filter_tgl)  $where .= " AND p.tanggal='$filter_tgl'";

$pakan_list = $koneksi->query("
    SELECT p.*, s.kode_sapi FROM pakan p
    JOIN sapi s ON p.id_sapi = s.id
    $where ORDER BY p.tanggal DESC, p.created_at DESC
");

// Sapi list for dropdown
$sapi_options = $koneksi->query("SELECT id, kode_sapi FROM sapi WHERE status='aktif' ORDER BY kode_sapi");

// Edit data
$edit_data = null;
if (isset($_GET['aksi']) && $_GET['aksi'] === 'edit' && isset($_GET['id'])) {
    $eid = intval($_GET['id']);
    $edit_data = $koneksi->query("SELECT * FROM pakan WHERE id=$eid LIMIT 1")->fetch_assoc();
}
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Pencatatan Pakan</div>
        <h1>🌾 Pencatatan Pakan</h1>
        <p>Catat pemberian pakan sapi setiap pagi dan sore</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalPakan')">＋ Catat Pakan</button>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- Filter -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="page" value="pakan">
            <div class="form-group" style="margin:0">
                <label class="form-label">Sapi</label>
                <select name="id_sapi" class="form-control" style="width:160px">
                    <option value="">Semua Sapi</option>
                    <?php
                    $sapi_all = $koneksi->query("SELECT id, kode_sapi FROM sapi ORDER BY kode_sapi");
                    while ($s = $sapi_all->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>" <?= $filter_sapi==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['kode_sapi']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tgl" class="form-control" value="<?= $filter_tgl ?>">
            </div>
            <div style="display:flex;gap:8px;align-self:flex-end">
                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="index.php?page=pakan" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Riwayat Pemberian Pakan</div>
        <span class="badge badge-green"><?= $pakan_list->num_rows ?> catatan</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode Sapi</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Jenis Pakan</th>
                    <th>Jumlah (kg)</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pakan_list && $pakan_list->num_rows > 0):
                    $no = 1; while ($p = $pakan_list->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($p['kode_sapi']) ?></strong></td>
                    <td><?= date('d M Y', strtotime($p['tanggal'])) ?></td>
                    <td>
                        <span class="badge <?= $p['waktu_pakan']==='pagi' ? 'badge-amber' : 'badge-blue' ?>">
                            <?= $p['waktu_pakan']==='pagi' ? '🌅 Pagi' : '🌆 Sore' ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($p['jenis_pakan']) ?></td>
                    <td><strong><?= number_format($p['jumlah'], 1) ?></strong> kg</td>
                    <td class="text-muted"><?= $p['keterangan'] ?: '-' ?></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="index.php?page=pakan&aksi=edit&id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus catatan ini?')">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="del_id" value="<?= $p['id'] ?>">
                                <button class="btn btn-danger btn-sm">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="8">
                    <div class="empty-state"><div class="icon">🌾</div><h3>Belum ada catatan pakan</h3></div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Pakan -->
<div class="modal-overlay <?= $edit_data ? 'open' : '' ?>" id="modalPakan">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title"><?= $edit_data ? '✏️ Edit Catatan Pakan' : '🌾 Catat Pakan Baru' ?></div>
            <button class="modal-close" onclick="closeModal('modalPakan');history.replaceState(null,'',window.location.pathname+'?page=pakan')">✕</button>
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
                            $sapi_options->data_seek(0);
                            while ($s = $sapi_options->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>" <?= ($edit_data && $edit_data['id_sapi']==$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['kode_sapi']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal <span>*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="<?= $edit_data['tanggal'] ?? date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Pakan <span>*</span></label>
                        <select name="waktu_pakan" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            <option value="pagi" <?= ($edit_data['waktu_pakan']??'')==='pagi'?'selected':'' ?>>🌅 Pagi</option>
                            <option value="sore" <?= ($edit_data['waktu_pakan']??'')==='sore'?'selected':'' ?>>🌆 Sore</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Pakan <span>*</span></label>
                        <select name="jenis_pakan" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            <option value="Rumput" <?= ($edit_data['jenis_pakan']??'')==='Rumput'?'selected':'' ?>>🌿 Rumput</option>
                            <option value="Konsentrat" <?= ($edit_data['jenis_pakan']??'')==='Konsentrat'?'selected':'' ?>>🌽 Konsentrat</option>
                            <option value="Dedak" <?= ($edit_data['jenis_pakan']??'')==='Dedak'?'selected':'' ?>>🌾 Dedak</option>
                            <option value="Jerami" <?= ($edit_data['jenis_pakan']??'')==='Jerami'?'selected':'' ?>>🌾 Jerami</option>
                            <option value="Campuran" <?= ($edit_data['jenis_pakan']??'')==='Campuran'?'selected':'' ?>>🥗 Campuran</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah (kg) <span>*</span></label>
                        <input type="number" name="jumlah" class="form-control" value="<?= $edit_data['jumlah'] ?? '' ?>" step="0.1" min="0.1" placeholder="mis: 15" required>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($edit_data['keterangan'] ?? '') ?>" placeholder="Catatan tambahan (opsional)">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalPakan')">Batal</button>
                <button type="submit" class="btn btn-primary">💾 <?= $edit_data ? 'Perbarui' : 'Simpan' ?></button>
            </div>
        </form>
    </div>
</div>
