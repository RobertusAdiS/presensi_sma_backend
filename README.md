# Presensi SMA - Backend REST API Service

Repositori ini berisi kode program **Backend REST API** untuk Sistem Absensi SMA.

## 🚀 Fitur Backend REST API
- **Pure JSON Endpoints**: Semua respon dikembalikan dalam format standar JSON (`{ status, message, data }`).
- **CORS Enabled**: Mendukung pengiriman request dari aplikasi Frontend terpisah.
- **Autentikasi & Session API**: Endpoint login Admin/Guru, login Siswa (NISN), check session (`me.php`), dan logout.
- **Resource Endpoints**:
  - `/api/dashboard/stats.php` - Total hitungan statistik & absensi hari ini.
  - `/api/siswa/` - REST API CRUD data siswa.
  - `/api/guru/` - REST API CRUD data guru.
  - `/api/kelas/` - REST API CRUD data kelas.
  - `/api/mapel/` - REST API CRUD data mata pelajaran.
  - `/api/jadwal/` - REST API CRUD data jadwal pelajaran.
  - `/api/absensi/input.php` - Simpan absensi kelas massal.
  - `/api/absensi/laporan.php` - Rekap absensi bulanan.
  - `/api/absensi/scan.php` - Pemrosesan scan QR Code presensi mandiri.
  - `/api/student/profile.php` - Data profil & histori kehadiran siswa.

## 🛠️ Instalasi & Konfigurasi Backend
1. Pastikan server web (XAMPP Apache) dan database MySQL berjalan.
2. Import `database.sql` ke database MySQL local (`absensi_sma`).
3. Sesuaikan koneksi database di `config/database.php` jika diperlukan:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'absensi_sma');
   ```

## 📁 Struktur Folder
```
presensi_sma_backend/
├── config/
│   └── database.php
├── app/
│   ├── auth.php
│   ├── helpers.php
│   └── middleware.php
├── api/
│   ├── auth/
│   ├── dashboard/
│   ├── siswa/
│   ├── guru/
│   ├── kelas/
│   ├── mapel/
│   ├── jadwal/
│   ├── absensi/
│   └── student/
├── database.sql
└── README.md
```
