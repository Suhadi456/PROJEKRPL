<?php
$msg   = '';
$error = '';
$role  = $_SESSION['role'] ?? 'pemilik';
$id_pesan_filter = intval($_GET['id_pesan'] ?? 0);

// Pemilik DAN user bisa catat pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'bayar') {
        $id_pesan = intval($_POST['id_pemesanan']);
        $tgl      = sanitize($koneksi, $_POST['tanggal_bayar']);
        $jumlah   = intval($_POST['jumlah_bayar']);
        $jenis    = sanitize($koneksi, $_POST['jenis_pembayaran']);
        $ket      = sanitize($koneksi, $_POST['keterangan'] ?? '');

        if (!$id_pesan || !$tgl || !$jumlah || !$jenis) {
            $error = 'Semua field wajib diisi.';
        } elseif ($jumlah <= 0) {
            $error = 'Jumlah bayar harus lebih dari 0.';
        } else {
            $pesan       = $koneksi->query("SELECT * FROM pemesanan WHERE id=$id_pesan LIMIT 1")->fetch_assoc();
            $sudah_bayar = $koneksi->query("SELECT COALESCE(SUM(jumlah_bayar),0) as total FROM pembayaran WHERE id_pemesanan=$id_pesan")->fetch_assoc()['total'] ?? 0;
            $sisa        = $pesan['harga_jual'] - $sudah_bayar;

            if ($jumlah > $sisa) {
                $error = 'Jumlah bayar (' . formatRupiah($jumlah) . ') melebihi sisa tagihan (' . formatRupiah($sisa) . ').';
            } else {
                $stmt = $koneksi->prepare("INSERT INTO pembayaran (id_pemesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran, keterangan) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isdss", $id_pesan, $tgl, $jumlah, $jenis, $ket);
                $stmt->execute();

                $total_baru = $sudah_bayar + $jumlah;
                if ($total_baru >= $pesan['harga_jual']) {
                    $koneksi->query("UPDATE pemesanan SET status='lunas' WHERE id=$id_pesan");
                    $koneksi->query("UPDATE sapi SET status='terjual' WHERE id={$pesan['id_sapi']}");
                    $msg = '✅ Pembayaran lunas! Status sapi diubah menjadi Terjual.';
                } else {
                    $koneksi->query("UPDATE pemesanan SET status='dp' WHERE id=$id_pesan");
                    $msg = 'Pembayaran DP berhasil dicatat. Sisa tagihan: ' . formatRupiah($sisa - $jumlah);
                }
            }
        }
    }
}

$where_pesan   = $id_pesan_filter ? "WHERE pm.id=$id_pesan_filter" : "WHERE 1=1";
$pemesanan_list = $koneksi->query("
    SELECT pm.*, s.kode_sapi,
           (SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran WHERE id_pemesanan=pm.id) as total_bayar
    FROM pemesanan pm JOIN sapi s ON pm.id_sapi=s.id
    $where_pesan
    ORDER BY pm.created_at DESC
");

$riwayat_bayar = $koneksi->query("
    SELECT py.*, pm.nama_pembeli, pm.harga_jual, s.kode_sapi
    FROM pembayaran py
    JOIN pemesanan pm ON py.id_pemesanan=pm.id
    JOIN sapi s ON pm.id_sapi=s.id
    " . ($id_pesan_filter ? "WHERE py.id_pemesanan=$id_pesan_filter" : "") . "
    ORDER BY py.tanggal_bayar DESC
");

$status_badges = ['pending'=>'badge-gray','dp'=>'badge-amber','lunas'=>'badge-green'];
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Pembayaran</div>
        <h1>💳 Pembayaran</h1>
        <p>Kelola dan catat pembayaran pemesanan sapi</p>
    </div>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($id_pesan_filter): ?>
<div class="alert alert-info">
    📋 Menampilkan pembayaran untuk Pemesanan #<?= $id_pesan_filter ?> — <a href="index.php?page=pembayaran">Tampilkan Semua</a>
</div>
<?php endif; ?>

<!-- Daftar Pemesanan & Tombol Bayar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <div class="card-title">📋 Daftar Pemesanan</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Sapi</th><th>Pembeli</th><th>Harga Jual</th><th>Total Bayar</th><th>Sisa</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($pemesanan_list && $pemesanan_list->num_rows > 0):
                $no=1; while ($r=$pemesanan_list->fetch_assoc()):
                $sisa = $r['harga_jual'] - $r['total_bayar'];
            ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($r['kode_sapi']) ?></strong></td>
                    <td><?= htmlspecialchars($r['nama_pembeli']) ?></td>
                    <td><?= formatRupiah($r['harga_jual']) ?></td>
                    <td><?= formatRupiah($r['total_bayar']) ?></td>
                    <td><?= $sisa > 0 ? formatRupiah($sisa) : '<span class="badge badge-green">Lunas</span>' ?></td>
                    <td><span class="badge <?= $status_badges[$r['status']]??'badge-gray' ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <?php if ($r['status'] !== 'lunas'): ?>
                        <button class="btn btn-warning btn-sm"
                            onclick="openBayarModal(<?= $r['id'] ?>, '<?= htmlspecialchars($r['kode_sapi']) ?>', '<?= htmlspecialchars($r['nama_pembeli']) ?>', <?= $r['harga_jual'] ?>, <?= $r['total_bayar'] ?>)">
                            💰 Catat Bayar
                        </button>
                        <?php else: ?>
                        <span class="badge badge-green">✅ Lunas</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="8"><div class="empty-state"><div class="icon">💳</div><h3>Belum ada pemesanan</h3></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Riwayat Pembayaran -->
<div class="card">
    <div class="card-header">
        <div class="card-title">📜 Riwayat Pembayaran</div>
        <span class="badge badge-green"><?= $riwayat_bayar ? $riwayat_bayar->num_rows : 0 ?> transaksi</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Tanggal</th><th>Sapi</th><th>Pembeli</th><th>Jenis</th><th>Jumlah</th><th>Keterangan</th></tr>
            </thead>
            <tbody>
            <?php if ($riwayat_bayar && $riwayat_bayar->num_rows > 0):
                $no=1;
                $jenis_badge = ['dp'=>'badge-amber','pelunasan'=>'badge-green','cicilan'=>'badge-blue'];
                while ($r=$riwayat_bayar->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><?= date('d M Y', strtotime($r['tanggal_bayar'])) ?></td>
                    <td><strong><?= htmlspecialchars($r['kode_sapi']) ?></strong></td>
                    <td><?= htmlspecialchars($r['nama_pembeli']) ?></td>
                    <td><span class="badge <?= $jenis_badge[$r['jenis_pembayaran']]??'badge-gray' ?>"><?= ucfirst($r['jenis_pembayaran']) ?></span></td>
                    <td class="fw-bold"><?= formatRupiah($r['jumlah_bayar']) ?></td>
                    <td><?= htmlspecialchars($r['keterangan'] ?? '') ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="7"><div class="empty-state"><div class="icon">📜</div><h3>Belum ada riwayat pembayaran</h3></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Catat Pembayaran -->
<div class="modal-overlay" id="modalBayar">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">💳 Catat Pembayaran</div>
            <button class="modal-close" onclick="closeModal('modalBayar')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="bayar">
            <input type="hidden" name="id_pemesanan" id="modal_id_pesan">
            <div class="modal-body">
                <div id="modal_info" style="background:#fffde7;border-radius:8px;padding:12px;margin-bottom:16px;font-size:.9rem;border-left:4px solid #fdd835"></div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Tanggal Bayar <span>*</span></label>
                        <input type="date" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah Bayar (Rp) <span>*</span></label>
                        <input type="number" name="jumlah_bayar" id="modal_jumlah" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Pembayaran <span>*</span></label>
                        <select name="jenis_pembayaran" class="form-control" required>
                            <option value="dp">DP / Uang Muka</option>
                            <option value="cicilan">Cicilan</option>
                            <option value="pelunasan">Pelunasan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalBayar')">Batal</button>
                <button type="submit" class="btn btn-warning">💾 Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBayarModal(id, kode, pembeli, harga, sudah_bayar) {
    const sisa = harga - sudah_bayar;
    document.getElementById('modal_id_pesan').value = id;
    document.getElementById('modal_jumlah').value   = sisa;
    document.getElementById('modal_info').innerHTML =
        `🐄 <b>${kode}</b> | Pembeli: <b>${pembeli}</b><br>` +
        `Harga: <b>Rp ${harga.toLocaleString('id-ID')}</b> | ` +
        `Sudah bayar: <b>Rp ${sudah_bayar.toLocaleString('id-ID')}</b> | ` +
        `Sisa: <b style="color:#c62828">Rp ${sisa.toLocaleString('id-ID')}</b>`;
    openModal('modalBayar');
}
</script>
