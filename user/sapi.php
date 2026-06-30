<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php'); exit;
}
include '../koneksi/koneksi.php';
$uid = intval($_SESSION['user_id']);

$msg = ''; $error = '';

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
                $stmt = $koneksi->prepare("INSERT INTO pemesanan (id_sapi, id_user, nama_pembeli, no_hp, alamat, tanggal_pesan, harga_jual, dp, status, keterangan) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("iissssiiss", $id_sapi, $uid, $nama, $hp, $alamat, $tgl, $harga_jual, $dp, $status, $ket);

                if ($stmt->execute()) {
                    $pid = $koneksi->insert_id;
                    $new_status_sapi = $status === 'lunas' ? 'terjual' : 'dipesan';
                    $koneksi->query("UPDATE sapi SET status='$new_status_sapi' WHERE id=$id_sapi");
                    
                    if ($dp > 0) {
                        $jenis_dp = $dp >= $harga_jual ? 'pelunasan' : 'dp';
                        $koneksi->query("INSERT INTO pembayaran (id_pemesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran) VALUES ($pid, '$tgl', $dp, '$jenis_dp')");
                    }
                    $msg = 'Pemesanan berhasil! Silakan cek halaman Pemesanan Saya.';
                } else {
                    $error = 'Gagal menyimpan pemesanan: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// ★ PERBAIKAN 1: Ambil harga_jual_target
$search = sanitize($koneksi, $_GET['q'] ?? '');
$where  = "WHERE status='siap_jual'";
if ($search) $where .= " AND kode_sapi LIKE '%$search%'";
$sapi_list = $koneksi->query("SELECT *, harga_jual_target FROM sapi $where ORDER BY created_at DESC");

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
        body { background: #fdfbf0; font-family: 'Segoe UI', sans-serif; }
        .navbar-brand { font-weight: 800; color: #d4af37 !important; }
        .sidebar-link { display: block; padding: 10px 16px; color: #3e2a00; text-decoration: none; border-radius: 8px; margin-bottom: 4px; font-weight: 500; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(62,42,0,.12); color: #3e2a00; font-weight: 700; }
        .sapi-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.08); height: 100%; }
        .sapi-card .kode { font-size: 1.3rem; font-weight: 800; color: #d4af37; }
        .sapi-card .harga { font-size: 1.1rem; font-weight: 700; color: #8a6d00; }
        .foto-thumb { width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #f5eec8;cursor:pointer;transition:transform .15s; }
        .foto-thumb:hover { transform:scale(1.06);border-color:#d4af37; }
        @media(max-width:768px){ .sidebar-col { display: none; } }
        .btn-disabled { opacity: 0.6; cursor: not-allowed; pointer-events: none; }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm px-4" style="border-bottom:2px solid #f5eec8">
    <span class="navbar-brand">🐄 SiPeternakan</span>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted" style="font-size:.9rem">👤 <?= htmlspecialchars($_SESSION['nama']) ?></span>
        <a href="../logout.php" class="btn btn-sm btn-outline-warning" onclick="return confirm('Keluar?')">Keluar</a>
    </div>
</nav>
<div class="container-fluid">
<div class="row">
    <div class="col-md-2 sidebar-col shadow-sm pt-3" style="min-height:100vh;background:linear-gradient(180deg,#f9e45b,#d4af37)">
        <a href="dashboard.php" class="sidebar-link">📊 Dashboard</a>
        <a href="sapi.php" class="sidebar-link active">🐄 Katalog Sapi</a>
        <a href="pemesanan.php" class="sidebar-link">📋 Pemesanan Saya</a>
        <a href="pembayaran.php" class="sidebar-link">💳 Riwayat Pembayaran</a>
        <a href="profil.php" class="sidebar-link">👤 Profil</a>
    </div>
    <div class="col-md-10 p-4">
        <h4 class="fw-bold mb-1" style="color:#d4af37">🐄 Katalog Sapi Siap Jual</h4>
        <p class="text-muted mb-3">Pilih sapi dan lakukan pemesanan langsung</p>

        <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?> <a href="pemesanan.php" class="fw-bold">Lihat Pemesanan →</a></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="GET" class="mb-4 d-flex gap-2">
            <input type="text" name="q" class="form-control" placeholder="Cari kode sapi..." value="<?= htmlspecialchars($search) ?>" style="max-width:300px;border-color:#e0d4a0">
            <button type="submit" class="btn btn-warning fw-bold">🔍 Cari</button>
            <a href="sapi.php" class="btn btn-outline-secondary">Reset</a>
        </form>

        <?php if ($sapi_list && $sapi_list->num_rows > 0): ?>
        <div class="row g-3">
        <?php while ($s = $sapi_list->fetch_assoc()):
            $berat_terkini = $koneksi->query("SELECT berat FROM berat_sapi WHERE id_sapi={$s['id']} ORDER BY tanggal DESC, id DESC LIMIT 1")->fetch_assoc();
            $berat_show    = $berat_terkini ? $berat_terkini['berat'] : $s['berat_awal'];
            $jml_foto      = $koneksi->query("SELECT COUNT(*) as c FROM foto_sapi WHERE id_sapi={$s['id']}")->fetch_assoc()['c'];
            
            // ★ PERBAIKAN 2: Gunakan harga_jual_target
            $harga_jual_display = $s['harga_jual_target'] ?? 0;
            $harga_text = $harga_jual_display > 0 ? formatRupiah($harga_jual_display) : '<span class="text-muted">Belum ditentukan</span>';
            $button_disabled = $harga_jual_display <= 0 ? 'btn-disabled' : '';
            $onclick_pesan = $harga_jual_display > 0 ? "openPesan({$s['id']}, '".htmlspecialchars($s['kode_sapi'])."', {$harga_jual_display})" : "alert('Harga jual belum ditentukan oleh pemilik.')";
        ?>
            <div class="col-sm-6 col-lg-4">
                <div class="sapi-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="kode">🐄 <?= htmlspecialchars($s['kode_sapi']) ?></div>
                        <span class="badge" style="background:#fff9c4;color:#8a6d00;border:1px solid #f5d442">✅ Siap Jual</span>
                    </div>
                    <div class="row g-2 mb-3" style="font-size:.9rem">
                        <div class="col-6"><span class="text-muted">Berat Terkini:</span><br><strong><?= number_format($berat_show, 1) ?> kg</strong></div>
                        <div class="col-6"><span class="text-muted">Sejak:</span><br><strong><?= date('d M Y', strtotime($s['tanggal_pembelian'])) ?></strong></div>
                        <div class="col-12">
                            <span class="text-muted">Harga Jual:</span><br>
                            <span class="harga"><?= $harga_text ?></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-warning fw-bold flex-fill <?= $button_disabled ?>"
                            onclick="<?= $onclick_pesan ?>">
                            📋 Pesan
                        </button>
                        <button class="btn btn-sm"
                            style="background:#fffde7;color:#8a6d00;border:1px solid #f5eec8;white-space:nowrap"
                            onclick="lihatFoto(<?= $s['id'] ?>, '<?= htmlspecialchars($s['kode_sapi']) ?>')">
                            📸 Foto <?= $jml_foto > 0 ? "($jml_foto)" : '' ?>
                        </button>
                    </div>
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
            <div class="modal-header" style="background:linear-gradient(135deg,#fffde7,#fff9c4)">
                <h5 class="modal-title fw-bold" style="color:#3e2a00">📋 Form Pemesanan Sapi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_sapi" id="form_id_sapi">
                    <div class="alert py-2" id="info_sapi" style="background:#fffde7;color:#3e2a00;border-left:4px solid #fdd835;font-size:.9rem"></div>
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
                        <div class="form-text">Isi 0 jika belum membayar DP</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold">💾 Konfirmasi Pemesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Galeri Foto -->
<div class="modal fade" id="modalFotoUser" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#fffde7,#fff9c4)">
                <h5 class="modal-title fw-bold" style="color:#3e2a00">📸 Perkembangan Sapi: <span id="fotoKodeUser"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fotoCarouselWrap">
                <div class="text-center text-muted py-4">Memuat foto...</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openPesan(id, kode, harga) {
    document.getElementById('form_id_sapi').value = id;
    document.getElementById('form_harga').value   = harga;
    document.getElementById('info_sapi').textContent = '🐄 Sapi: ' + kode + ' | Harga Jual: Rp ' + harga.toLocaleString('id-ID');
    new bootstrap.Modal(document.getElementById('modalPesan')).show();
}

function lihatFoto(id, kode) {
    document.getElementById('fotoKodeUser').textContent = kode;
    document.getElementById('fotoCarouselWrap').innerHTML = '<div class="text-center text-muted py-4">⏳ Memuat foto...</div>';
    new bootstrap.Modal(document.getElementById('modalFotoUser')).show();

    fetch('sapi_foto.php?id_sapi=' + id)
        .then(r => r.text())
        .then(html => { document.getElementById('fotoCarouselWrap').innerHTML = html; })
        .catch(() => { document.getElementById('fotoCarouselWrap').innerHTML = '<div class="text-center text-muted py-4">Gagal memuat foto.</div>'; });
}
</script>
</body>
</html>