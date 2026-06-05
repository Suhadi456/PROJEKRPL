<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php'); exit;
}
include '../koneksi/koneksi.php';
$uid = intval($_SESSION['user_id']);

$msg = ''; $error = '';

// Proses form pemesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sapi    = intval($_POST['id_sapi']);
    $nama       = sanitize($koneksi, $_POST['nama_pembeli']);
    $hp         = preg_replace('/[^0-9]/', '', $_POST['no_hp'] ?? '');
    $alamat     = sanitize($koneksi, $_POST['alamat'] ?? '');
    $tgl        = sanitize($koneksi, $_POST['tanggal_pesan']);
    $harga_jual = intval($_POST['harga_jual']);
    $dp         = intval($_POST['dp'] ?? 0);
    $ket        = sanitize($koneksi, $_POST['keterangan'] ?? '');

    if (!$id_sapi || !$nama || !$hp || !$tgl || !$harga_jual) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($hp) < 8 || strlen($hp) > 15) {
        $error = 'Nomor HP harus 8-15 digit angka.';
    } elseif ($dp < 0) {
        $error = 'DP tidak boleh negatif.';
    } else {
        $sapi = $koneksi->query("SELECT * FROM sapi WHERE id=$id_sapi LIMIT 1")->fetch_assoc();
        if (!$sapi || $sapi['status'] !== 'siap_jual') {
            $error = 'Sapi tidak tersedia untuk dipesan.';
        } else {
            $cek = $koneksi->query("SELECT id FROM pemesanan WHERE id_sapi=$id_sapi AND status IN ('pending','dp') LIMIT 1");
            if ($cek->num_rows > 0) {
                $error = 'Sapi ini sudah ada pemesanan aktif.';
            } else {
                $status = $dp >= $harga_jual ? 'lunas' : ($dp > 0 ? 'dp' : 'pending');
                $koneksi->query("INSERT INTO pemesanan (id_sapi, id_user, nama_pembeli, no_hp, alamat, tanggal_pesan, harga_jual, dp, status, keterangan)
                    VALUES ($id_sapi, $uid, '$nama', '$hp', '$alamat', '$tgl', $harga_jual, $dp, '$status', '$ket')");
                $new_status_sapi = $status === 'lunas' ? 'terjual' : 'dipesan';
                $koneksi->query("UPDATE sapi SET status='$new_status_sapi' WHERE id=$id_sapi");
                if ($dp > 0) {
                    $pid = $koneksi->insert_id;
                    $jenis_dp = $dp >= $harga_jual ? 'pelunasan' : 'dp';
                    $koneksi->query("INSERT INTO pembayaran (id_pemesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran) VALUES ($pid, '$tgl', $dp, '$jenis_dp')");
                }
                $msg = 'Pemesanan berhasil! Silakan cek halaman Pemesanan Saya.';
            }
        }
    }
}

$search = sanitize($koneksi, $_GET['q'] ?? '');
$where = "WHERE status='siap_jual'";
if ($search) $where .= " AND kode_sapi LIKE '%$search%'";
$sapi_list = $koneksi->query("SELECT * FROM sapi $where ORDER BY created_at DESC");

// Prefill user data
$user_data = $koneksi->query("SELECT * FROM akun WHERE id=$uid LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Sapi — SiPeternakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .navbar-brand { font-weight: 800; color: #1a4a2e !important; }
        .sidebar-link { display: block; padding: 10px 16px; color: #333; text-decoration: none; border-radius: 8px; margin-bottom: 4px; }
        .sidebar-link:hover, .sidebar-link.active { background: #e8f5e9; color: #1a4a2e; font-weight: 600; }
        .sapi-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.08); height: 100%; }
        .sapi-card .kode { font-size: 1.3rem; font-weight: 800; color: #1a4a2e; }
        .sapi-card .harga { font-size: 1.1rem; font-weight: 700; color: #2e7d32; }
        @media(max-width:768px){ .sidebar-col { display: none; } }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm px-4">
    <span class="navbar-brand">🐄 SiPeternakan</span>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted" style="font-size:.9rem">👤 <?= htmlspecialchars($_SESSION['nama']) ?></span>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm" onclick="return confirm('Keluar?')">Keluar</a>
    </div>
</nav>
<div class="container-fluid">
<div class="row">
    <div class="col-md-2 sidebar-col bg-white shadow-sm pt-3" style="min-height:100vh">
        <a href="dashboard.php" class="sidebar-link">📊 Dashboard</a>
        <a href="sapi.php" class="sidebar-link active">🐄 Katalog Sapi</a>
        <a href="pemesanan.php" class="sidebar-link">📋 Pemesanan Saya</a>
        <a href="pembayaran.php" class="sidebar-link">💳 Riwayat Bayar</a>
        <a href="profil.php" class="sidebar-link">👤 Profil</a>
    </div>
    <div class="col-md-10 p-4">
        <h4 class="fw-bold mb-1">🐄 Katalog Sapi Siap Jual</h4>
        <p class="text-muted mb-3">Pilih sapi dan lakukan pemesanan langsung</p>

        <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?> <a href="pemesanan.php">Lihat Pemesanan →</a></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Search -->
        <form method="GET" class="mb-4 d-flex gap-2">
            <input type="text" name="q" class="form-control" placeholder="Cari kode sapi..." value="<?= htmlspecialchars($search) ?>" style="max-width:300px">
            <button type="submit" class="btn btn-success">🔍 Cari</button>
            <a href="sapi.php" class="btn btn-outline-secondary">Reset</a>
        </form>

        <?php if ($sapi_list && $sapi_list->num_rows > 0): ?>
        <div class="row g-3">
        <?php while ($s = $sapi_list->fetch_assoc()):
            $berat_terkini = $koneksi->query("SELECT berat FROM berat_sapi WHERE id_sapi={$s['id']} ORDER BY tanggal DESC LIMIT 1")->fetch_assoc();
            $berat_show = $berat_terkini ? $berat_terkini['berat'] : $s['berat_awal'];
        ?>
            <div class="col-sm-6 col-lg-4">
                <div class="sapi-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="kode">🐄 <?= htmlspecialchars($s['kode_sapi']) ?></div>
                        <span class="badge bg-success">Siap Jual</span>
                    </div>
                    <div class="row g-2 mb-3" style="font-size:.9rem">
                        <div class="col-6"><span class="text-muted">Berat:</span><br><strong><?= number_format($berat_show,1) ?> kg</strong></div>
                        <div class="col-6"><span class="text-muted">Beli sejak:</span><br><strong><?= date('d M Y', strtotime($s['tanggal_pembelian'])) ?></strong></div>
                        <div class="col-12"><span class="text-muted">Harga Referensi:</span><br><span class="harga"><?= formatRupiah($s['harga_beli']) ?></span></div>
                    </div>
                    <button class="btn btn-success w-100" onclick="openPesan(<?= $s['id'] ?>, '<?= htmlspecialchars($s['kode_sapi']) ?>', <?= $s['harga_beli'] ?>)">
                        📋 Pesan Sekarang
                    </button>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <div style="font-size:4rem">🐄</div>
            <h5 class="text-muted">Belum ada sapi siap jual saat ini</h5>
            <p class="text-muted">Silakan cek kembali nanti</p>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- Modal Pemesanan -->
<div class="modal fade" id="modalPesan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">📋 Form Pemesanan Sapi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_sapi" id="form_id_sapi">
                    <div class="alert alert-info py-2" id="info_sapi" style="font-size:.9rem"></div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Pembeli <span class="text-danger">*</span></label>
                        <input type="text" name="nama_pembeli" class="form-control" value="<?= htmlspecialchars($user_data['nama'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. HP <span class="text-danger">*</span></label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($user_data['no_hp'] ?? '') ?>" placeholder="08xxx" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat pengiriman (opsional)"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Pesan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_pesan" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga Jual Disepakati (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="harga_jual" id="form_harga" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">DP / Uang Muka (Rp)</label>
                        <input type="number" name="dp" class="form-control" value="0" min="0">
                        <div class="form-text">Isi 0 jika belum bayar DP</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold">💾 Konfirmasi Pemesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openPesan(id, kode, harga) {
    document.getElementById('form_id_sapi').value = id;
    document.getElementById('form_harga').value = harga;
    document.getElementById('info_sapi').textContent = '🐄 Sapi: ' + kode + ' | Harga Referensi: Rp ' + harga.toLocaleString('id-ID');
    new bootstrap.Modal(document.getElementById('modalPesan')).show();
}
</script>
</body>
</html>
