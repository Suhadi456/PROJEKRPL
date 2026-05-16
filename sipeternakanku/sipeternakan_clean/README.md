# 🐄 SiPeternakan — Sistem Informasi Manajemen Peternakan Sapi

Aplikasi web berbasis **PHP + MySQL** untuk mengelola peternakan sapi secara terpadu.

---

## ✅ Fitur

| Modul | Keterangan |
|---|---|
| 🔐 Login & Auth | Session-based login dengan bcrypt |
| 🐄 Data Sapi | CRUD data sapi, kode unik, status otomatis |
| 🌾 Pencatatan Pakan | Catat pakan pagi/sore per sapi |
| ⚖️ Berat Sapi | Riwayat & pertumbuhan berat sapi |
| 💰 Pencatatan Biaya | Catat semua pengeluaran operasional |
| 📋 Pemesanan | Manajemen pemesanan & status sapi otomatis |
| 💳 Pembayaran | DP, cicilan, pelunasan — status update otomatis |
| 📈 Laporan Penjualan | Laporan per periode + estimasi profit |
| 🧮 Estimasi Keuangan | Analisis modal vs penjualan per sapi + chart |

---

## 🚀 Cara Instalasi di XAMPP

### 1. Salin folder ke htdocs
```
C:\xampp\htdocs\sipeternakan\
```

### 2. Import database
- Buka **phpMyAdmin** → `http://localhost/phpmyadmin`
- Klik **New** → buat database `db_sipeternakan`
- Klik tab **Import** → pilih file `database/db_sipeternakan.sql`
- Klik **Go**

### 3. Konfigurasi koneksi (jika perlu)
Edit file `koneksi/koneksi.php`:
```php
define('DB_HOST', 'localhost');  // host database
define('DB_USER', 'root');       // username MySQL (default XAMPP: root)
define('DB_PASS', '');           // password MySQL (default XAMPP: kosong)
define('DB_NAME', 'db_sipeternakan');
```

### 4. Akses aplikasi
Buka browser → `http://localhost/sipeternakan`

---

## 🔑 Akun Default

| Username | Password | Role |
|---|---|---|
| `admin` | `password` | Admin |
| `pemilik` | `password` | Pemilik |

> ⚠️ Segera ganti password setelah pertama login!

---

## 🛠️ Teknologi

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Chart**: Chart.js 4.4
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: XAMPP (Apache + MySQL)

---

## 📁 Struktur Folder

```
sipeternakan/
├── assets/
│   ├── css/style.css       ← Stylesheet utama
│   └── js/app.js           ← JavaScript global
├── database/
│   └── db_sipeternakan.sql ← Schema + data awal
├── includes/
│   ├── auth.php            ← Session guard
│   └── sidebar.php         ← Komponen sidebar
├── koneksi/
│   └── koneksi.php         ← Koneksi database
├── page/
│   ├── dashboard.php
│   ├── data_sapi/index.php
│   ├── pakan/index.php
│   ├── berat/index.php
│   ├── biaya/index.php
│   ├── pemesanan/index.php
│   ├── pembayaran/index.php
│   ├── laporan/index.php
│   └── estimasi/index.php
├── index.php               ← App shell + routing
├── login.php               ← Halaman login
└── logout.php              ← Handler logout
```

---

## 📞 Dukungan

Jika ada masalah, pastikan:
- XAMPP sudah berjalan (Apache + MySQL aktif)
- Database sudah diimport dengan benar
- Versi PHP minimal 7.4
