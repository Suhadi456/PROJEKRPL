# SiPeternakan v2.0 — Sistem Informasi Manajemen Peternakan Sapi

## Cara Menjalankan di XAMPP

### 1. Persiapan
- Install XAMPP (PHP 7.4+ atau PHP 8.x)
- Pastikan Apache dan MySQL berjalan

### 2. Letakkan Project
Salin folder `ternakfinal` ke:
```
C:\xampp\htdocs\ternakfinal\
```

### 3. Import Database
- Buka browser: http://localhost/phpmyadmin
- Klik **New** → buat database bernama `ternak_db`
- Klik tab **Import** → pilih file `database/ternak_db.sql`
- Klik **Go**

### 4. Konfigurasi (jika perlu)
Edit file `koneksi/koneksi.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // sesuaikan username MySQL Anda
define('DB_PASS', '');       // sesuaikan password MySQL Anda
define('DB_NAME', 'ternak_db');
```

### 5. Jalankan
Buka browser: http://localhost/ternakfinal

---

## Akun Default

| Role     | Username  | Password    |
|----------|-----------|-------------|
| Admin    | admin     | admin123    |
| Pemilik  | pemilik   | pemilik123  |
| User     | user1     | user123     |

---

## Alur Sistem

### Login & Role
- **Admin** → Panel manajemen akun (`admin/dashboard.php`)
- **Pemilik** → Panel utama manajemen peternakan (`index.php`)
- **User/Pembeli** → Katalog sapi & pemesanan (`user/dashboard.php`)

### Flow Pemesanan
1. User daftar akun (`register.php`)
2. Login → masuk User Dashboard
3. Pilih sapi dari Katalog Sapi
4. Isi form pemesanan + DP
5. Status sapi otomatis berubah: `siap_jual` → `dipesan`
6. Pemilik catat pembayaran lunas → status sapi: `terjual`

### Status Sapi
- `digemukkan` — Sapi dalam pemeliharaan
- `siap_jual`  — Sapi siap ditawarkan
- `dipesan`    — Ada pemesanan aktif
- `terjual`    — Pembayaran lunas, transaksi selesai

### Validasi Pakan
- Maksimal 2x/hari per sapi (pagi & sore)
- Tidak bisa double input waktu yang sama

---

## Struktur Folder

```
sipeternakan/
├── admin/           # Panel admin
├── user/            # Dashboard user/pembeli
│   ├── dashboard.php
│   ├── sapi.php     # Katalog sapi
│   ├── pemesanan.php
│   ├── pembayaran.php
│   └── profil.php
├── page/            # Halaman pemilik
│   ├── dashboard.php
│   ├── data_sapi/
│   ├── pakan/
│   ├── berat/
│   ├── biaya/
│   ├── pemesanan/
│   ├── pembayaran/
│   ├── laporan/
│   └── estimasi/
├── assets/          # CSS, JS
├── koneksi/         # Koneksi database
├── includes/        # Sidebar, auth helpers
├── database/        # File SQL
├── index.php        # Entry point pemilik
├── login.php
├── register.php
└── logout.php
```
