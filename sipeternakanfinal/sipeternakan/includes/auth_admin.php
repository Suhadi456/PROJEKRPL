<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit; }
