<?php
$current_page = $_GET['page'] ?? 'dashboard';
function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
$role = $_SESSION['role'] ?? 'pemilik';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">🐄</div>
        <div class="sidebar-brand-text">
            <div class="sidebar-brand-name">SiPeternakan</div>
            <div class="sidebar-brand-sub">Manajemen Peternakan Sapi</div>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?></div>
        <div>
            <div class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></div>
            <div class="user-role"><?= ucfirst($role) ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Utama</div>
        <div class="nav-item">
            <a href="index.php?page=dashboard" class="<?= isActive('dashboard') ?>">
                <span class="nav-icon">📊</span> Dashboard
            </a>
        </div>

        <?php if (in_array($role, ['admin','pemilik'])): ?>
        <div class="nav-section-label">Data Ternak</div>
        <div class="nav-item">
            <a href="index.php?page=data_sapi" class="<?= isActive('data_sapi') ?>">
                <span class="nav-icon">🐄</span> Data Sapi
            </a>
        </div>
        <div class="nav-item">
            <a href="index.php?page=pakan" class="<?= isActive('pakan') ?>">
                <span class="nav-icon">🌾</span> Pencatatan Pakan
            </a>
        </div>
        <div class="nav-item">
            <a href="index.php?page=berat" class="<?= isActive('berat') ?>">
                <span class="nav-icon">⚖️</span> Update Berat Sapi
            </a>
        </div>
        <div class="nav-item">
            <a href="index.php?page=biaya" class="<?= isActive('biaya') ?>">
                <span class="nav-icon">💰</span> Pencatatan Biaya
            </a>
        </div>

        <div class="nav-section-label">Transaksi</div>
        <div class="nav-item">
            <a href="index.php?page=pemesanan" class="<?= isActive('pemesanan') ?>">
                <span class="nav-icon">📋</span> Pemesanan
            </a>
        </div>
        <div class="nav-item">
            <a href="index.php?page=pembayaran" class="<?= isActive('pembayaran') ?>">
                <span class="nav-icon">💳</span> Pembayaran
            </a>
        </div>

        <div class="nav-section-label">Laporan</div>
        <div class="nav-item">
            <a href="index.php?page=laporan" class="<?= isActive('laporan') ?>">
                <span class="nav-icon">📈</span> Laporan Penjualan
            </a>
        </div>
        <div class="nav-item">
            <a href="index.php?page=estimasi" class="<?= isActive('estimasi') ?>">
                <span class="nav-icon">🧮</span> Estimasi Keuangan
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar?')">
            <span>🚪</span> Keluar
        </a>
    </div>
</aside>
