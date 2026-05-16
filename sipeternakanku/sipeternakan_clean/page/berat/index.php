<?php
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'save') {
        $id_sapi = intval($_POST['id_sapi']);
        $tanggal = sanitize($koneksi, $_POST['tanggal']);
        $berat   = floatval($_POST['berat']);
        $ket     = sanitize($koneksi, $_POST['keterangan'] ?? '');
        $edit_id = intval($_POST['edit_id'] ?? 0);

        if (!$id_sapi || !$tanggal || !$berat) {
            $error = 'Semua field wajib diisi.';
        } elseif ($berat <= 0) {
            $error = 'Berat harus lebih dari 0.';
        } else {
            if ($edit_id) {
                $koneksi->query("UPDATE berat_sapi SET id_sapi=$id_sapi, tanggal='$tanggal', berat=$berat, keterangan='$ket' WHERE id=$edit_id");
                $msg = 'Data berat berhasil diperbarui.';
            } else {
                $koneksi->query("INSERT INTO berat_sapi (id_sapi, tanggal, berat, keterangan) VALUES ($id_sapi, '$tanggal', $berat, '$ket')");
                $msg = 'Berat sapi berhasil dicatat.';
            }
        }
    } elseif ($act === 'delete') {
        $koneksi->query("DELETE FROM berat_sapi WHERE id=" . intval($_POST['del_id']));
        $msg = 'Data berat dihapus.';
    }
}

$filter_sapi = intval($_GET['id_sapi'] ?? 0);
$where = $filter_sapi ? "WHERE b.id_sapi=$filter_sapi" : "WHERE 1=1";
$list = $koneksi->query("SELECT b.*, s.kode_sapi FROM berat_sapi b JOIN sapi s ON b.id_sapi=s.id $where ORDER BY b.tanggal DESC");

$sapi_opts = $koneksi->query("SELECT id, kode_sapi FROM sapi ORDER BY kode_sapi");

$edit_data = null;
if (isset($_GET['aksi']) && $_GET['aksi'] === 'edit' && isset($_GET['id'])) {
    $eid = intval($_GET['id']);
    $edit_data = $koneksi->query("SELECT * FROM berat_sapi WHERE id=$eid LIMIT 1")->fetch_assoc();
}
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Update Berat</div>
        <h1>⚖️ Riwayat Berat Sapi</h1>
        <p>Pantau pertumbuhan berat sapi dari waktu ke waktu</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalBerat')">＋ Catat Berat</button>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- Filter by sapi -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <input type="hidden" name="page" value="berat">
            <div class="form-group" style="margin:0">
                <label class="form-label">Filter Sapi</label>
                <select name="id_sapi" class="form-control" style="width:200px">
                    <option value="">Semua Sapi</option>
                    <?php
                    $sa = $koneksi->query("SELECT id, kode_sapi FROM sapi ORDER BY kode_sapi");
                    while ($s = $sa->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>" <?= $filter_sapi==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['kode_sapi']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="display:flex;gap:8px;align-self:flex-end">
                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="index.php?page=berat" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Riwayat Berat Sapi</div>
        <span class="badge badge-green"><?= $list->num_rows ?> data</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Kode Sapi</th><th>Tanggal</th><th>Berat (kg)</th><th>Keterangan</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if ($list && $list->num_rows > 0):
                    $no=1; $prev_sapi=''; $prev_berat=0;
                    $rows = []; while ($r = $list->fetch_assoc()) $rows[] = $r;
                    foreach ($rows as $idx => $b): ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($b['kode_sapi']) ?></strong></td>
                    <td><?= date('d M Y', strtotime($b['tanggal'])) ?></td>
                    <td>
                        <strong><?= number_format($b['berat'], 1) ?></strong> kg
                        <?php
                        // Show diff with next record (previous in time) of same sapi
                        $next = null;
                        for ($i=$idx+1; $i<count($rows); $i++) {
                            if ($rows[$i]['id_sapi'] === $b['id_sapi']) { $next = $rows[$i]; break; }
                        }
                        if ($next) {
                            $diff = $b['berat'] - $next['berat'];
                            if ($diff > 0) echo "<span class='text-green' style='font-size:12px'>▲ +".number_format($diff,1)."</span>";
                            elseif ($diff < 0) echo "<span class='text-red' style='font-size:12px'>▼ ".number_format($diff,1)."</span>";
                        }
                        ?>
                    </td>
                    <td class="text-muted"><?= $b['keterangan'] ?: '-' ?></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="index.php?page=berat&aksi=edit&id=<?= $b['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus data ini?')">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="del_id" value="<?= $b['id'] ?>">
                                <button class="btn btn-danger btn-sm">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6"><div class="empty-state"><div class="icon">⚖️</div><h3>Belum ada data berat</h3></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay <?= $edit_data ? 'open' : '' ?>" id="modalBerat">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title"><?= $edit_data ? '✏️ Edit Berat' : '⚖️ Catat Berat Sapi' ?></div>
            <button class="modal-close" onclick="closeModal('modalBerat')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <?php if ($edit_data): ?><input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>"><?php endif; ?>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Sapi <span>*</span></label>
                        <select name="id_sapi" class="form-control" required>
                            <option value="">-- Pilih Sapi --</option>
                            <?php $sapi_opts->data_seek(0); while ($s = $sapi_opts->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>" <?= ($edit_data && $edit_data['id_sapi']==$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['kode_sapi']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal <span>*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="<?= $edit_data['tanggal'] ?? date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Berat (kg) <span>*</span></label>
                        <input type="number" name="berat" class="form-control" value="<?= $edit_data['berat'] ?? '' ?>" step="0.1" min="1" placeholder="mis: 350" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($edit_data['keterangan'] ?? '') ?>" placeholder="Catatan (opsional)">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalBerat')">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>
