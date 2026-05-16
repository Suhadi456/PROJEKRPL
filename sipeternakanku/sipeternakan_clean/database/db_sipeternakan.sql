-- ============================================================
-- DATABASE: db_sipeternakan
-- Sistem Informasi Manajemen Peternakan Sapi
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_sipeternakan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_sipeternakan;

-- Tabel User / Akun
CREATE TABLE IF NOT EXISTS `akun` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `role` ENUM('admin','pemilik') DEFAULT 'pemilik',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Data Sapi
CREATE TABLE IF NOT EXISTS `sapi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_sapi` VARCHAR(20) NOT NULL UNIQUE,
  `berat_awal` FLOAT NOT NULL,
  `tanggal_pembelian` DATE NOT NULL,
  `harga_beli` BIGINT NOT NULL,
  `status` ENUM('aktif','dipesan','terjual') DEFAULT 'aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Pakan
CREATE TABLE IF NOT EXISTS `pakan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sapi` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `waktu_pakan` ENUM('pagi','sore') NOT NULL,
  `jenis_pakan` VARCHAR(50) NOT NULL,
  `jumlah` FLOAT NOT NULL,
  `keterangan` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_sapi`) REFERENCES `sapi`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Riwayat Berat Sapi
CREATE TABLE IF NOT EXISTS `berat_sapi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sapi` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `berat` FLOAT NOT NULL,
  `keterangan` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_sapi`) REFERENCES `sapi`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Biaya
CREATE TABLE IF NOT EXISTS `biaya` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sapi` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `jenis_biaya` VARCHAR(100) NOT NULL,
  `jumlah` BIGINT NOT NULL,
  `keterangan` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_sapi`) REFERENCES `sapi`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Pemesanan
CREATE TABLE IF NOT EXISTS `pemesanan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sapi` INT NOT NULL,
  `nama_pembeli` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `tanggal_pesan` DATE NOT NULL,
  `harga_jual` BIGINT NOT NULL,
  `dp` BIGINT DEFAULT 0,
  `status` ENUM('pending','dp','lunas') DEFAULT 'pending',
  `keterangan` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_sapi`) REFERENCES `sapi`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Pembayaran
CREATE TABLE IF NOT EXISTS `pembayaran` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pemesanan` INT NOT NULL,
  `tanggal_bayar` DATE NOT NULL,
  `jumlah_bayar` BIGINT NOT NULL,
  `jenis_pembayaran` ENUM('dp','pelunasan','cicilan') NOT NULL,
  `keterangan` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_pemesanan`) REFERENCES `pemesanan`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATA AWAL (Seed)
-- ============================================================

-- Akun default: admin / admin123
INSERT INTO `akun` (`username`, `password`, `nama`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('pemilik', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pak Budi Santoso', 'pemilik');
-- Password default: "password" (hash bcrypt)

-- Contoh data sapi
INSERT INTO `sapi` (`kode_sapi`, `berat_awal`, `tanggal_pembelian`, `harga_beli`, `status`) VALUES
('SP-001', 320.5, '2024-01-15', 15000000, 'aktif'),
('SP-002', 285.0, '2024-01-20', 12500000, 'aktif'),
('SP-003', 410.0, '2024-02-01', 18000000, 'dipesan'),
('SP-004', 350.0, '2024-02-10', 16000000, 'terjual');

-- Contoh data pakan
INSERT INTO `pakan` (`id_sapi`, `tanggal`, `waktu_pakan`, `jenis_pakan`, `jumlah`) VALUES
(1, '2024-05-01', 'pagi', 'Rumput', 15.0),
(1, '2024-05-01', 'sore', 'Konsentrat', 5.0),
(2, '2024-05-01', 'pagi', 'Rumput', 12.0),
(2, '2024-05-01', 'sore', 'Rumput', 12.0);

-- Contoh berat sapi
INSERT INTO `berat_sapi` (`id_sapi`, `tanggal`, `berat`) VALUES
(1, '2024-02-01', 325.0),
(1, '2024-03-01', 340.5),
(1, '2024-04-01', 358.0),
(2, '2024-02-01', 290.0),
(2, '2024-03-01', 305.0);

-- Contoh pemesanan
INSERT INTO `pemesanan` (`id_sapi`, `nama_pembeli`, `no_hp`, `tanggal_pesan`, `harga_jual`, `dp`, `status`) VALUES
(3, 'Haji Salim', '081234567890', '2024-04-15', 22000000, 5000000, 'dp'),
(4, 'Pak Darman', '082198765432', '2024-03-20', 20000000, 20000000, 'lunas');

-- Contoh biaya
INSERT INTO `biaya` (`id_sapi`, `tanggal`, `jenis_biaya`, `jumlah`) VALUES
(1, '2024-04-01', 'Pakan Rumput', 150000),
(1, '2024-04-15', 'Obat Vitamin', 75000),
(2, '2024-04-01', 'Pakan Konsentrat', 200000);
