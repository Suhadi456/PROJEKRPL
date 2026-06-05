<?php
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'save') {
        $id_sapi = intval($_POST['id_sapi']);
        $tgl     = sanitize($koneksi, $_POST['tanggal']);
        $berat   = floatval($_POST['berat']);
        $ket     = sanitize($koneksi, $_POST['keterangan'] ?? '');

        if (!$id_sapi || !$tgl || !$berat) {
            $error = 'Semua field wajib diisi.';
        } elseif ($berat <= 0) {
            $error = 'Berat harus lebih dari 0.';
        } elseif ($tgl > date('Y-m-d')) {
            $error = 'Tanggal tidak boleh melebihi hari ini.';
        } else {
            // Always INSERT (history tidak pernah overwrite)
            $koneksi->query("INSERT INTO berat_sapi (id_sapi, tanggal, berat, keterangan) VALUES ($id_sapi, '$tgl', $berat, '$ket')");
            $msg = 'Berat sapi berhasil dicatat.';
        }
    } elseif ($act === 'delete') {
        $del_id = intval($_POST['del_id']);
        $koneksi->query("DELETE FROM berat_sapi WHERE id=$del_id");
        $msg = 'Data berat dihapus.';
    }
}

$filter_sapi = intval($_GET['id_sapi'] ?? 0);
$where = $filter_sapi ? "WHERE b.id_sapi=$filter_sapi" : "WHERE 1=1";
$list = $koneksi->query("SELECT b.*, s.kode_sapi FROM berat_sapi b JOIN sapi s ON b.id_sapi=s.id $where ORDER BY b.tanggal DESC, b.id DESC");
$sapi_opts = $koneksi->query("SELECT id, kode_sapi FROM sapi ORDER BY kode_sapi");
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Berat Sapi</div>
        <h1>⚖️ Update Berat Sapi</h1>
        <p>Catat perkembangan berat sapi secara berkala (histori tersimpan lengkap)</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalBerat')">＋ Catat Berat</button>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" action="index.php" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="page" value="berat">
            <select name="id_sapi" class="form-control" style="width:180px">
                <option value="">Semua Sapi</option>
                <?php $sapi_opts->data_seek(0); while ($s=$sapi_opts->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>" <?= $filter_sapi==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['kode_sapi']) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="index.php?page=berat" class="btn btn-secondary">Tampilkan Semua</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Riwayat Berat Sapi</div>
        <span class="badge badge-green"><?= $list ? $list->num_rows : 0 ?> data</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Kode Sapi</th><th>Tanggal</th><th>Berat (kg)</th><th>Keterangan</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($list && $list->num_rows > 0):
                $no=1; $prev_sapi=''; $prev_berat=0;
                $rows = $list->fetch_all(MYSQLI_ASSOC);
                foreach ($rows as $r):
                    $selisih = '';
                    if ($r['kode_sapi'] === $prev_sapi && $prev_berat) {
                        $diff = $r['berat'] - $prev_berat;
                        $selisih = $diff >= 0 ? '<span style="color:#2e7d32">▲'.number_format($diff,1).'</span>' : '<span style="color:#c62828">▼'.number_format(abs($diff),1).'</span>';
                    }
                    $prev_sapi = $r['kode_sapi'];
                    $prev_berat = $r['berat'];
            ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($r['kode_sapi']) ?></strong></td>
                    <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                    <td><?= number_format($r['berat'], 1) ?> kg</td>
                    <td><?= htmlspecialchars($r['keterangan'] ?? '') ?></td>
                    <td>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus data berat ini?')">
                            <input type="hidden" name="_action" value="delete">
                            <input type="hidden" name="del_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                        </form>
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
<div class="modal-overlay" id="modalBerat">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">⚖️ Catat Berat Sapi</div>
            <button class="modal-close" onclick="closeModal('modalBerat')">✕</button>
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
                        <label class="form-label">Berat (kg) <span>*</span></label>
                        <input type="number" name="berat" class="form-control" step="0.1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
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
