<?php
$msg = ''; $error = '';
$notif_siap_jual = null;

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
            $koneksi->query("INSERT INTO berat_sapi (id_sapi, tanggal, berat, keterangan) VALUES ($id_sapi, '$tgl', $berat, '$ket')");
            $msg = 'Berat sapi berhasil dicatat.';

            $sapi_info = $koneksi->query("SELECT id, kode_sapi, status FROM sapi WHERE id=$id_sapi LIMIT 1")->fetch_assoc();
            if ($sapi_info && $sapi_info['status'] === 'digemukkan' && $berat >= 400) {
                $notif_siap_jual = [
                    'id'     => $id_sapi,
                    'kode'   => $sapi_info['kode_sapi'],
                    'berat'  => $berat,
                ];
            }
        }
    } elseif ($act === 'delete') {
        $del_id = intval($_POST['del_id']);
        $koneksi->query("DELETE FROM berat_sapi WHERE id=$del_id");
        $msg = 'Data berat dihapus.';
    } elseif ($act === 'siap_jual') {
        $sj_id = intval($_POST['sj_id']);
        $harga_jual = intval($_POST['harga_jual'] ?? 0);
        
        $sapi = $koneksi->query("SELECT harga_beli FROM sapi WHERE id=$sj_id LIMIT 1")->fetch_assoc();
        $total_biaya = $koneksi->query("SELECT COALESCE(SUM(jumlah),0) as total FROM biaya WHERE id_sapi=$sj_id")->fetch_assoc()['total'] ?? 0;
        $modal = $sapi['harga_beli'] + $total_biaya;
        
        if ($harga_jual <= 0) {
            $error = 'Harga jual harus diisi dan lebih dari 0.';
        } else {
            $koneksi->query("UPDATE sapi SET status='siap_jual', harga_jual_target=$harga_jual WHERE id=$sj_id AND status='digemukkan'");
            if ($harga_jual < $modal) {
                $msg = '⚠️ Status berubah menjadi Siap Jual. Peringatan: Harga jual (Rp '.number_format($harga_jual).') kurang dari total modal (Rp '.number_format($modal).'). Anda berisiko rugi.';
            } else {
                $msg = '✅ Status sapi berhasil diubah menjadi Siap Jual dengan harga jual Rp '.number_format($harga_jual);
            }
        }
    }
}

$filter_sapi = intval($_GET['id_sapi'] ?? 0);
$where = $filter_sapi ? "WHERE b.id_sapi=$filter_sapi" : "WHERE 1=1";
// ★ Ambil berat_awal dari sapi juga
$list = $koneksi->query("SELECT b.*, s.kode_sapi, s.berat_awal FROM berat_sapi b JOIN sapi s ON b.id_sapi=s.id $where ORDER BY b.tanggal ASC, b.id ASC");
$sapi_opts = $koneksi->query("SELECT id, kode_sapi FROM sapi WHERE status IN ('digemukkan','siap_jual') ORDER BY kode_sapi");
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Berat Sapi</div>
        <h1>⚖️ Update Berat Sapi</h1>
        <p>Catat perkembangan berat sapi secara berkala (histori tersimpan lengkap)</p>
    </div>
    <button class="btn btn-warning" onclick="openModal('modalBerat')">＋ Catat Berat</button>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($notif_siap_jual): ?>
<div class="alert" style="background:#fff9c4;color:#5a3e00;border-left:4px solid #d4af37;padding:16px 20px;border-radius:10px;margin-bottom:20px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <strong>🎉 Sapi <?= htmlspecialchars($notif_siap_jual['kode']) ?> sudah siap jual!</strong><br>
            <span style="font-size:.9rem">Berat terkini: <strong><?= number_format($notif_siap_jual['berat'],1) ?> kg</strong> — telah mencapai target berat ≥ 400 kg.</span>
        </div>
        <button class="btn btn-warning" 
            onclick="openHargaJualModalFromBerat(<?= $notif_siap_jual['id'] ?>, '<?= htmlspecialchars($notif_siap_jual['kode']) ?>')">
            ✅ Atur Harga & Siap Jual
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Highlight sapi yang sudah capai target -->
<?php
$sapi_target = $koneksi->query("
    SELECT s.id, s.kode_sapi, MAX(b.berat) as berat_maks
    FROM sapi s
    JOIN berat_sapi b ON b.id_sapi = s.id
    WHERE s.status = 'digemukkan'
    GROUP BY s.id, s.kode_sapi
    HAVING berat_maks >= 400
");
if ($sapi_target && $sapi_target->num_rows > 0):
?>
<div class="alert alert-info" style="margin-bottom:20px">
    <strong>🔔 Sapi berikut sudah mencapai target berat ≥ 400 kg:</strong>
    <ul style="margin:8px 0 0 0;padding-left:20px">
    <?php while ($st = $sapi_target->fetch_assoc()): ?>
        <li><?= htmlspecialchars($st['kode_sapi']) ?> — <?= number_format($st['berat_maks'],1) ?> kg
            <button class="btn btn-warning btn-sm" style="font-size:.75rem;padding:3px 10px" 
                onclick="openHargaJualModalFromBerat(<?= $st['id'] ?>, '<?= htmlspecialchars($st['kode_sapi']) ?>')">
                Atur Harga & Siap Jual
            </button>
        </li>
    <?php endwhile; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" action="index.php" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="page" value="berat">
            <select name="id_sapi" class="form-control" style="width:200px">
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
            <?php
            // ★ Proses data dengan berat awal sebagai patokan
            $rows = [];
            $prev_berat_per_sapi = []; // untuk menyimpan berat sebelumnya per sapi (dimulai dari berat_awal)
            
            if ($list && $list->num_rows > 0) {
                while ($r = $list->fetch_assoc()) {
                    $kode = $r['kode_sapi'];
                    // Jika belum ada entry untuk sapi ini, set prev_berat = berat_awal
                    if (!isset($prev_berat_per_sapi[$kode])) {
                        $prev_berat_per_sapi[$kode] = (float)$r['berat_awal'];
                    }
                    
                    // Hitung selisih dari prev_berat ke berat sekarang
                    $diff = $r['berat'] - $prev_berat_per_sapi[$kode];
                    $r['selisih'] = $diff;
                    
                    // Update prev_berat dengan berat sekarang
                    $prev_berat_per_sapi[$kode] = $r['berat'];
                    
                    $rows[] = $r;
                }
                // Balik array agar terbaru di atas
                $rows = array_reverse($rows);
            }
            
            if (!empty($rows)):
                $no = 1;
                foreach ($rows as $r): 
                    $selisih_html = '';
                    if ($r['selisih'] !== null) {
                        if ($r['selisih'] >= 0) {
                            $selisih_html = ' <span style="color:#d4af37;font-size:.8rem">▲' . number_format($r['selisih'], 1) . '</span>';
                        } else {
                            $selisih_html = ' <span style="color:#c62828;font-size:.8rem">▼' . number_format(abs($r['selisih']), 1) . '</span>';
                        }
                    }
            ?>
                <tr <?= $r['berat'] >= 400 ? 'style="background:#fffde7"' : '' ?>>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($r['kode_sapi']) ?></strong></td>
                    <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                    <td>
                        <?= number_format($r['berat'], 1) ?> kg<?= $selisih_html ?>
                        <?php if ($r['berat'] >= 400): ?>
                        <span class="badge badge-gold" style="font-size:.7rem">🎯 Target</span>
                        <?php endif; ?>
                    </td>
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

<!-- Modal Catat Berat -->
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
                        <div class="form-text">Sapi dengan berat ≥ 400 kg akan disarankan untuk "Siap Jual"</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalBerat')">Batal</button>
                <button type="submit" class="btn btn-warning">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Input Harga Jual untuk Berat -->
<div class="modal-overlay" id="modalHargaJualBerat">
    <div class="modal-box" style="max-width:500px">
        <div class="modal-header">
            <div class="modal-title">💰 Atur Harga Jual</div>
            <button class="modal-close" onclick="closeModal('modalHargaJualBerat')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="siap_jual">
            <input type="hidden" name="sj_id" id="harga_jual_id_berat">
            <div class="modal-body">
                <p class="mb-2">Sapi: <strong id="harga_jual_kode_berat"></strong></p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Harga Jual (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="harga_jual" id="harga_jual_input_berat" class="form-control" min="1" required>
                    <div class="form-text" id="harga_jual_info_berat"></div>
                </div>
                <div id="harga_jual_warning_berat" style="display:none;background:#fff3cd;border-left:4px solid #ffc107;padding:10px;border-radius:4px;margin-top:10px">
                    ⚠️ <span id="warning_text_berat"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalHargaJualBerat')">Batal</button>
                <button type="submit" class="btn btn-warning">✅ Konfirmasi Siap Jual</button>
            </div>
        </form>
    </div>
</div>

<script>
function openHargaJualModalFromBerat(id, kode) {
    document.getElementById('harga_jual_id_berat').value = id;
    document.getElementById('harga_jual_kode_berat').textContent = kode;
    document.getElementById('harga_jual_input_berat').value = '';
    document.getElementById('harga_jual_info_berat').textContent = 'Memuat data...';
    document.getElementById('harga_jual_warning_berat').style.display = 'none';
    
    fetch('get_biaya_sapi.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            let modal = data.harga_beli + data.total_biaya;
            document.getElementById('harga_jual_info_berat').textContent = 'Harga beli: Rp ' + data.harga_beli.toLocaleString('id-ID') + ' | Total modal: Rp ' + modal.toLocaleString('id-ID');
            document.getElementById('harga_jual_input_berat').oninput = function() {
                let val = parseInt(this.value) || 0;
                if (val > 0 && val < modal) {
                    document.getElementById('harga_jual_warning_berat').style.display = 'block';
                    document.getElementById('warning_text_berat').textContent = 'Harga jual (Rp ' + val.toLocaleString('id-ID') + ') kurang dari total modal (Rp ' + modal.toLocaleString('id-ID') + '). Anda berisiko rugi.';
                } else {
                    document.getElementById('harga_jual_warning_berat').style.display = 'none';
                }
            };
        })
        .catch(() => {
            document.getElementById('harga_jual_info_berat').textContent = 'Gagal memuat data biaya.';
        });
    
    openModal('modalHargaJualBerat');
}
</script>