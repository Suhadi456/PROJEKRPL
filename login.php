<?php
session_start();

// Already logged in - redirect by role
if (!empty($_SESSION['user_id'])) {
    $r = $_SESSION['role'] ?? 'user';
    if ($r === 'admin') header('Location: admin/dashboard.php');
    elseif ($r === 'pemilik') header('Location: index.php');
    else header('Location: user/dashboard.php');
    exit;
}

include 'koneksi/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($koneksi, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        $sql = "SELECT * FROM akun WHERE username = '$username' LIMIT 1";
        $result = $koneksi->query($sql);

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if ($password === $user['password']) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama']     = $user['nama'];
                $_SESSION['role']     = $user['role'];
                
                // Redirect berdasarkan role
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } elseif ($user['role'] === 'pemilik') {
                    header('Location: index.php');
                } else {
                    header('Location: user/dashboard.php');
                }
                exit;
            } else {
                $error = 'Password salah. Silakan coba lagi.';
            }
        } else {
            $error = 'Username tidak ditemukan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SiPeternakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #afc23298 0%, #b5b21e 50%, #fdfd7f5a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { background: #fff; border-radius: 16px; padding: 40px; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
        .login-brand { text-align: center; margin-bottom: 30px; }
        .login-brand .icon { font-size: 3rem; display: block; margin-bottom: 10px; }
        .login-brand h1 { font-size: 1.8rem; font-weight: 800; color: #92961d; margin: 0; }
        .login-brand p { color: #666; font-size: 0.9rem; margin: 4px 0 0; }
        .form-control:focus { border-color: #d4af37; box-shadow: 0 0 0 3px rgba(212,175,55,.2); }
        .btn-login { background: linear-gradient(135deg,#f9e45b,#d4af37); color: #3e2a00; border: none; width: 100%; padding: 12px; font-weight: 700; border-radius: 8px; font-size: 1rem; }
        .btn-login:hover { background: linear-gradient(135deg,#ffe066,#c9a227); color: #3e2a00; }
        .register-link { text-align: center; margin-top: 20px; font-size: 0.9rem; }
        .register-link a { color: #bbb816; font-weight: 600; }
        .demo-accounts { background: #f8f9fa; border-radius: 8px; padding: 12px; margin-top: 20px; font-size: 0.8rem; }
        .demo-accounts strong { color: #333; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-brand">
        <span class="icon">🐄</span>
        <h1>SiPeternakan</h1>
        <p>Sistem Informasi Manajemen Peternakan Sapi</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Masukkan username" 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
        </div>
        <button type="submit" class="btn-login">🔐 Masuk</button>
    </form>

    <div class="register-link">
        Belum punya akun? <a href="register.php">Daftar di sini</a>
    </div>

    <!-- <div class="demo-accounts">
        <strong>Akun Demo:</strong><br>
        👨‍💼 Admin: <code>admin</code> / <code>admin123</code><br>
        🏠 Pemilik: <code>pemilik</code> / <code>pemilik123</code><br>
        👤 User: <code>user1</code> / <code>user123</code>
    </div> -->
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
