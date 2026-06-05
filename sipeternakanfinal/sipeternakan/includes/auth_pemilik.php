<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!in_array($_SESSION['role'], ['admin','pemilik'])) { header('Location: login.php'); exit; }
