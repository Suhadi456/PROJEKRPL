<?php
session_start();
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
            if ($password == $user['password']) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama']     = $user['nama'];
                $_SESSION['role']     = $user['role'];
                header('Location: index.php');
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background: #0f1923;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'DM Sans', sans-serif;
            overflow: hidden;
        }

        /* Animated background */
        .bg-art {
            position: fixed; inset: 0; z-index: 0;
            background: radial-gradient(ellipse 80% 60% at 20% 50%, #1a4a2e 0%, transparent 60%),
                        radial-gradient(ellipse 60% 80% at 80% 20%, #2d5a1b 0%, transparent 55%),
                        radial-gradient(ellipse 50% 50% at 60% 80%, #0e3320 0%, transparent 50%);
        }
        .bg-art::after {
            content: '';
            position: absolute; inset: 0;
            background-image: 
                radial-gradient(circle at 25% 30%, rgba(94,186,94,0.08) 0%, transparent 40%),
                radial-gradient(circle at 75% 70%, rgba(168,218,74,0.06) 0%, transparent 35%);
        }

        /* Floating particles */
        .particle {
            position: fixed;
            border-radius: 50%;
            opacity: 0.15;
            animation: float linear infinite;
        }
        @keyframes float {
            0% { transform: translateY(110vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.15; }
            90% { opacity: 0.15; }
            100% { transform: translateY(-10vh) rotate(720deg); opacity: 0; }
        }

        /* Card */
        .login-container {
            position: relative; z-index: 10;
            display: flex;
            width: 900px;
            max-width: 95vw;
            min-height: 540px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 40px 100px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);
        }

        /* Left panel */
        .left-panel {
            flex: 1;
            background: linear-gradient(145deg, #1e5c32 0%, #2d7a40 40%, #1a4a2e 100%);
            padding: 60px 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .left-panel::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 250px; height: 250px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .left-panel::after {
            content: '';
            position: absolute;
            bottom: -40px; left: -40px;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .brand-icon {
            width: 52px; height: 52px;
            background: rgba(255,255,255,0.15);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.3px;
        }
        .brand-sub { font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 2px; }

        .left-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            margin-bottom: 16px;
        }
        .left-content h1 span { color: #a8e063; }
        .left-content p {
            color: rgba(255,255,255,0.7);
            font-size: 15px;
            line-height: 1.7;
        }

        .features {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.75);
            font-size: 14px;
        }
        .feature-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #a8e063;
            flex-shrink: 0;
        }

        /* Right panel */
        .right-panel {
            width: 400px;
            background: #f8faf7;
            padding: 60px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-title { font-size: 28px; font-weight: 700; color: #1a2e1a; margin-bottom: 6px; }
        .form-subtitle { font-size: 14px; color: #6b7c6b; margin-bottom: 36px; }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 10px;
            padding: 12px 16px;
            color: #dc2626;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #2d3e2d;
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }
        .input-wrapper {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: #8fa88f;
            font-size: 16px;
        }
        .form-input {
            width: 100%;
            padding: 13px 16px 13px 42px;
            background: #fff;
            border: 2px solid #e2ebe2;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            color: #1a2e1a;
            transition: all 0.2s;
            outline: none;
        }
        .form-input:focus {
            border-color: #2d7a40;
            box-shadow: 0 0 0 4px rgba(45,122,64,0.08);
        }
        .form-input::placeholder { color: #b0c4b0; }

        .toggle-pw {
            position: absolute; right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; color: #8fa88f;
            font-size: 16px; padding: 4px;
        }
        .toggle-pw:hover { color: #2d7a40; }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2d7a40, #1e5c32);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 8px;
            letter-spacing: 0.02em;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(45,122,64,0.35);
        }
        .btn-login:active { transform: translateY(0); }

        .login-footer {
            margin-top: 28px;
            text-align: center;
            font-size: 12px;
            color: #9aaa9a;
        }
        .hint-box {
            background: #f0f7f0;
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 20px;
            font-size: 12px;
            color: #5a7a5a;
        }
        .hint-box strong { color: #2d7a40; }

        @media (max-width: 760px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; padding: 48px 32px; }
        }
    </style>
</head>
<body>
    <div class="bg-art"></div>

    <!-- Particles -->
    <div class="particle" style="width:8px;height:8px;background:#a8e063;left:15%;animation-duration:18s;animation-delay:0s;"></div>
    <div class="particle" style="width:5px;height:5px;background:#6ee08a;left:35%;animation-duration:22s;animation-delay:3s;"></div>
    <div class="particle" style="width:10px;height:10px;background:#2d7a40;left:55%;animation-duration:16s;animation-delay:6s;"></div>
    <div class="particle" style="width:6px;height:6px;background:#a8e063;left:75%;animation-duration:20s;animation-delay:1s;"></div>
    <div class="particle" style="width:7px;height:7px;background:#6ee08a;left:88%;animation-duration:24s;animation-delay:8s;"></div>

    <div class="login-container">
        <!-- Left Panel -->
        <div class="left-panel">
            <div class="brand-logo">
                <div class="brand-icon">🐄</div>
                <div>
                    <div class="brand-name">SiPeternakan</div>
                    <div class="brand-sub">Manajemen Peternakan Sapi</div>
                </div>
            </div>

            <div class="left-content">
                <h1>Kelola <span>Peternakan</span> Sapi Anda dengan Mudah</h1>
                <p>Sistem informasi terpadu untuk memantau sapi, pakan, berat badan, keuangan, dan laporan penjualan secara real-time.</p>
            </div>

            <div class="features">
                <div class="feature-item"><div class="feature-dot"></div>Kelola data sapi & riwayat berat</div>
                <div class="feature-item"><div class="feature-dot"></div>Catat pakan & biaya operasional</div>
                <div class="feature-item"><div class="feature-dot"></div>Manajemen pemesanan & pembayaran</div>
                <div class="feature-item"><div class="feature-dot"></div>Laporan keuangan & estimasi keuntungan</div>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <div class="form-title">Selamat Datang 👋</div>
            <div class="form-subtitle">Silakan masuk untuk melanjutkan</div>

            <?php if ($error): ?>
            <div class="alert-error">
                <span>⚠️</span> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" name="username" class="form-input" placeholder="Masukkan username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password" id="password" class="form-input" placeholder="Masukkan password" autocomplete="current-password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword()">👁</button>
                    </div>
                </div>
                <button type="submit" class="btn-login">Masuk ke Dashboard</button>
            </form>

            <div class="hint-box">
                <strong>Demo Login:</strong><br>
                Username: <strong>admin</strong> / Password: <strong>password</strong>
            </div>

            <div class="login-footer">
                © 2025 SiPeternakan · Sistem Manajemen Peternakan Sapi
            </div>
        </div>
    </div>

    <script>
    function togglePassword() {
        const pw = document.getElementById('password');
        pw.type = pw.type === 'password' ? 'text' : 'password';
    }
    </script>
</body>
</html>
