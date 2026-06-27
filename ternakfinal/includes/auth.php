<?php
// Auth guard — include di setiap halaman yang memerlukan login
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ' . (str_starts_with($_SERVER['PHP_SELF'] ?? '', '/user/') ? '../login.php' : 
           (str_starts_with($_SERVER['PHP_SELF'] ?? '', '/admin/') ? '../login.php' : 'login.php')));
    exit;
}
