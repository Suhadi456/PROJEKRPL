<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'user') { http_response_code(403); exit; }
include '../koneksi/koneksi.php';

$id_sapi = intval($_GET['id_sapi'] ?? 0);
if (!$id_sapi) { echo '<p class="text-muted text-center">Data tidak valid.</p>'; exit; }

$fotos = $koneksi->query("SELECT * FROM foto_sapi WHERE id_sapi=$id_sapi ORDER BY tanggal ASC");

if (!$fotos || $fotos->num_rows === 0) {
    echo '<div class="text-center py-4" style="color:#aaa"><div style="font-size:3rem">📷</div><p>Belum ada foto perkembangan untuk sapi ini.</p></div>';
    exit;
}

$rows = $fotos->fetch_all(MYSQLI_ASSOC);
$carouselId = 'carousel' . $id_sapi;
echo '<div id="' . $carouselId . '" class="carousel slide" data-bs-ride="carousel">';
echo '<div class="carousel-indicators">';
foreach ($rows as $i => $f) {
    echo '<button type="button" data-bs-target="#' . $carouselId . '" data-bs-slide-to="' . $i . '"' . ($i===0?' class="active"':'') . '></button>';
}
echo '</div>';
echo '<div class="carousel-inner">';
foreach ($rows as $i => $f) {
    $fpath = '../uploads/sapi/' . htmlspecialchars($f['foto']);
    $active = $i === 0 ? ' active' : '';
    echo '<div class="carousel-item' . $active . '">';
    echo '<img src="' . $fpath . '" class="d-block w-100" style="max-height:400px;object-fit:contain;border-radius:8px;background:#f8f9fa" alt="Foto sapi" onerror="this.src=\'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22300%22%3E%3Crect fill=%22%23eee%22 width=%22300%22 height=%22300%22/%3E%3Ctext x=%2250%%22 y=%2250%%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-size=%2220%22%3EFoto tidak tersedia%3C/text%3E%3C/svg%3E\'">';
    echo '<div class="carousel-caption d-none d-md-block" style="background:rgba(0,0,0,0.5);border-radius:8px;bottom:20px;padding:8px 16px">';
    echo '<small style="color:#fff;">' . date('d M Y', strtotime($f['tanggal'])) . '</small>';
    if ($f['keterangan']) echo '<br><small style="color:#fff;">' . htmlspecialchars($f['keterangan']) . '</small>';
    echo '</div>';
    echo '</div>';
}
echo '</div>';
if (count($rows) > 1) {
    echo '<button class="carousel-control-prev" type="button" data-bs-target="#' . $carouselId . '" data-bs-slide="prev">';
    echo '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
    echo '<span class="visually-hidden">Previous</span>';
    echo '</button>';
    echo '<button class="carousel-control-next" type="button" data-bs-target="#' . $carouselId . '" data-bs-slide="next">';
    echo '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
    echo '<span class="visually-hidden">Next</span>';
    echo '</button>';
}
echo '</div>';
echo '<p class="text-center text-muted mt-2" style="font-size:.85rem">' . count($rows) . ' foto tersedia</p>';
?>
