# Webcraft POS – Sistem Kasir dan Manajemen Stok Berbasis Web

**Webcraft POS** adalah aplikasi *Point of Sale* (POS) dan manajemen inventori berbasis web yang dirancang khusus untuk berbagai jenis UMKM (toko retail, toko kelontong, toko bangunan, toko pakaian, toko pakan, dan usaha perdagangan lainnya).

Aplikasi ini dibangun menggunakan **PHP 8.x Native** dengan arsitektur modular (MVC-style), koneksi **PDO Prepared Statements**, antarmuka responsif **Bootstrap 5**, visualisasi grafik **Chart.js**, serta fitur ekspor laporan ke **Excel (.xlsx)** dan **PDF (.pdf)**.

---

## Fitur Utama

1. **Autentikasi & Manajemen Pengguna (RBAC)**
   - Login & Logout aman menggunakan session dan `password_hash()` (Bcrypt).
   - Pembatasan hak akses berdasarkan role **Admin** dan **Kasir**.
   - Pengelolaan akun pengguna (Tambah, Edit, Reset Password, Aktif/Nonaktif, Hapus) serta pengaturan profil mandiri.
   - Proteksi **CSRF Token** pada seluruh formulir dan request mutasi data.

2. **Dashboard Analitik**
   - Ringkasan omzet penjualan harian & keuntungan kotor hari ini.
   - Jumlah transaksi harian & akumulasi bulanan.
   - Jumlah produk aktif & indikator peringatan stok menipis.
   - Grafik tren penjualan & laba kotor 7 hari terakhir (Chart.js) serta grafik distribusi metode pembayaran.
   - Daftar transaksi terbaru & tabel produk stok menipis.

3. **Manajemen Produk & Kategori**
   - CRUD Produk lengkap: SKU (otomatis/manual), Barcode (opsional, mendukung barcode scanner), Nama, Kategori, Supplier, Satuan (`pcs`, `kg`, `liter`, `dus`, `sak`, `btl`, `rcg`, dll), Harga Modal, Harga Jual, Kalkulasi Margin, Stok Awal, dan Batas Minimum Stok.
   - CRUD Kategori Produk beserta pengelompokan jumlah produk dan proteksi penghapusan kategori yang masih digunakan oleh produk.

4. **Sistem Kasir (POS) & Pencetakan Struk**
   - Pencarian cepat berdasarkan nama, kode SKU, atau scan Barcode langsung (`Enter` otomatis masuk keranjang).
   - Keranjang belanja interaktif dengan pengaturan kuantitas (mendukung angka desimal seperti `0.5 kg`) dan validasi stok real-time.
   - Perhitungan Subtotal, Diskon Transaksi (Nominal `Rp` atau Persentase `%`), dan Grand Total.
   - Metode pembayaran **Tunai**, **Transfer**, dan **QRIS** beserta nomor referensi pembayaran dan tombol uang cepat (`Uang Pas`, `20.000`, `50.000`, `100.000`, `200.000`).
   - Perhitungan uang kembalian otomatis.
   - Pencetakan struk thermal (**58mm** dan **80mm**) lengkap dengan informasi toko.

5. **Manajemen Stok & Kartu Stok**
   - Pencatatan **Stok Masuk** (`in`) dari supplier beserta opsi pembaruan harga modal.
   - Pencatatan **Stok Keluar** (`out`) untuk barang rusak/kadaluarsa/sampel.
   - **Penyesuaian Stok / Stock Opname** (`adjustment`) sesuai jumlah fisik gudang.
   - Pengurangan stok otomatis & atomik saat transaksi kasir berlangsung.
   - Riwayat perubahan stok lengkap (*Stock Movements*) dan notifikasi stok minimum di topbar/sidebar.

6. **Manajemen Pelanggan & Supplier**
   - Pengelolaan data pelanggan beserta rekap total transaksi dan total belanja.
   - Pengelolaan data pemasok/supplier beserta jumlah produk yang dipasok.

7. **Laporan Lengkap & Ekspor (Excel / PDF)**
   - Laporan Penjualan Harian
   - Laporan Penjualan Bulanan
   - Laporan Berdasarkan Rentang Tanggal
   - Laporan Rincian Transaksi
   - Laporan Produk Terlaris (*Best Selling Products*)
   - Laporan Persediaan Barang & Valuasi Aset Stok
   - Laporan Keuntungan Kotor (*Gross Profit*) berdasarkan harga modal dan harga jual
   - Ekspor langsung ke **Excel (`.xlsx`)** dan **PDF (`.pdf`)**.

8. **Pengaturan Toko**
   - Konfigurasi nama toko, slogan/tagline, alamat, nomor telepon, email, dan upload logo toko.
   - Pengaturan tampilan struk kasir (header, footer, ukuran kertas 58mm/80mm, visibilitas logo/kasir/pelanggan).

---

## Struktur Direktori Proyek

```text
Web POS/
├── app/
│   ├── Controllers/         # Controller modular untuk setiap fitur
│   ├── Core/                # Database PDO, Auth RBAC, CSRF Guard, Router
│   ├── Helpers/             # Helper global & Exporter Excel/PDF
│   └── Models/              # Model database (PDO Prepared Statements)
├── config/
│   └── config.php           # Konfigurasi aplikasi, session, environment & autoloader
├── database/
│   └── schema.sql           # Skema tabel MySQL/MariaDB beserta relasi & data awal
├── docs/
│   ├── DOKUMENTASI_INSTALASI.md
│   ├── PANDUAN_PENGGUNAAN.md
│   └── HASIL_PENGUJIAN.md
├── public/
│   ├── assets/
│   │   ├── css/style.css    # Stylesheet kustom responsif
│   │   └── js/app.js        # Utilitas JavaScript frontend
│   ├── uploads/             # Penyimpanan logo toko
│   └── index.php            # Entry point alternatif untuk DocumentRoot public/
├── storage/                 # Penyimpanan lokal / SQLite fallback
├── tests/
│   └── run_tests.php        # Suite pengujian otomatis fitur utama
├── views/                   # File tampilan HTML5 + Bootstrap 5
│   ├── auth/
│   ├── categories/
│   ├── dashboard/
│   ├── layouts/
│   ├── partners/
│   ├── pos/
│   ├── products/
│   ├── reports/
│   ├── settings/
│   ├── stock/
│   └── users/
├── .env.example             # Template konfigurasi environment
├── .htaccess                # Pengamanan direktori pada web server Apache
├── composer.json            # Konfigurasi dependensi Composer (PhpSpreadsheet & Dompdf)
└── index.php                # Front Controller utama
```

---

## Akun Default

| Role | Username | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Akses penuh seluruh fitur, manajemen produk/stok, laporan laba, pengguna & pengaturan toko |
| **Kasir** | `kasir` | `kasir123` | Akses dashboard, mesin kasir (POS), cetak struk, riwayat transaksi, katalog & pelanggan |

---

## Dokumentasi Lengkap

- [Dokumentasi Instalasi dan Konfigurasi](docs/DOKUMENTASI_INSTALASI.md)
- [Panduan Penggunaan Aplikasi](docs/PANDUAN_PENGGUNAAN.md)
- [Hasil Pengujian Fitur Utama](docs/HASIL_PENGUJIAN.md)

