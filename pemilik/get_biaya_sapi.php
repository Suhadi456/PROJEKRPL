<?php
session_start();
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','pemilik'])) {
    http_response_code(403);
    exit;
}
include 'koneksi/koneksi.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
}

$sapi = $koneksi->query("SELECT harga_beli FROM sapi WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$sapi) {
    echo json_encode(['error' => 'Sapi tidak ditemukan']);
    exit;
}

$total_biaya = $koneksi->query("SELECT COALESCE(SUM(jumlah),0) as total FROM biaya WHERE id_sapi=$id")->fetch_assoc()['total'] ?? 0;

echo json_encode([
    'harga_beli' => (int)$sapi['harga_beli'],
    'total_biaya' => (int)$total_biaya
]);