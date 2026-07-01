<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pemilik', 'admin'])) {
    header('Location: ../../login.php'); exit;
}
include '../../koneksi/koneksi.php';

$id_sapi    = intval($_POST['id_sapi'] ?? 0);
$tanggal    = sanitize($koneksi, $_POST['tanggal'] ?? '');
$keterangan = sanitize($koneksi, $_POST['keterangan'] ?? '');

if ($id_sapi && $tanggal && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $allowed_ext = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext)) {
        $_SESSION['error'] = 'Format file tidak didukung. Gunakan JPG/PNG.';
    } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
        $_SESSION['error'] = 'Ukuran file melebihi 2MB.';
    } else {
        // ★ Gunakan __DIR__ untuk path absolut
        $target_dir = __DIR__ . '/../../uploads/sapi/';
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $nama_file  = 'sapi_' . $id_sapi . '_' . time() . '.' . $ext;
        $target_file = $target_dir . $nama_file;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
            $stmt = $koneksi->prepare("INSERT INTO foto_sapi (id_sapi, foto, keterangan, tanggal) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $id_sapi, $nama_file, $keterangan, $tanggal);
            $stmt->execute();
            $_SESSION['msg'] = 'Foto berhasil diupload.';
        } else {
            $_SESSION['error'] = 'Gagal menyimpan file foto: ' . error_get_last()['message'];
        }
    }
} else {
    $_SESSION['error'] = 'Data tidak lengkap atau tidak ada file dipilih.';
}

header('Location: ../../index.php?page=data_sapi');
exit;
