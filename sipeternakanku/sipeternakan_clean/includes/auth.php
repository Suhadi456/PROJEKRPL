<?php
session_start();
include 'koneksi/koneksi.php';

// cek_login.php — handled inside login.php
// This file only handles session guard

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
