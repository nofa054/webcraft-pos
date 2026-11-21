# Laporan Hasil Pengujian Fitur Utama – Webcraft POS

Dokumen ini berisi hasil pengujian fungsional, keamanan, dan integrasi database pada aplikasi **Webcraft POS**. Pengujian dapat dijalankan ulang kapan saja melalui perintah CLI:

```bash
php tests/run_tests.php
```

---

## Ringkasan Lingkungan Pengujian

- **Versi PHP**: PHP 8.4.24 (Kompatibel penuh dengan PHP 8.0+)
- **Database Engine**: PDO (`pdo_mysql` & `pdo_sqlite`)
- **Total Skenario Uji**: 19 Skenario Utama + Validasi Sintaks 44 File PHP
- **Status Akhir**: **19 LULUS (PASS) / 0 GAGAL (FAIL)**

---

## Tabel Hasil Pengujian Fitur Utama

| No | Modul / Fitur | Skenario Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status |
| :-: | :--- | :--- | :--- | :--- | :-: |
| 1 | **Koneksi Database & Skema** | Inisialisasi koneksi PDO dan pembuatan 9 tabel berelasi | Koneksi PDO berhasil tanpa error, tabel & seed data siap digunakan | Koneksi berhasil, seluruh tabel & relasi aktif | **PASS** |
| 2 | **Keamanan Autentikasi** | Percobaan login menggunakan password yang salah | Sistem menolak login dan mengembalikan pesan error yang aman | Login ditolak dengan pesan validasi | **PASS** |
| 3 | **Autentikasi & Role Kasir** | Login dengan akun `kasir` (`kasir123`) | Session terbentuk dengan role `kasir`, pembatasan akses admin aktif | Role `kasir` terverifikasi (`isAdmin() === false`) | **PASS** |
| 4 | **Autentikasi & Role Admin** | Login dengan akun `admin` (`admin123`) | Session terbentuk dengan role `admin`, akses penuh diizinkan | Role `admin` terverifikasi (`isAdmin() === true`) | **PASS** |
| 5 | **Keamanan CSRF Token** | Generasi token CSRF 64-karakter & validasi `hash_equals` | Token valid diterima, token kosong/palsu ditolak | Token 64 hex chars valid & token palsu ditolak | **PASS** |
| 6 | **Manajemen Kategori** | Tambah kategori baru dan ubah nama/deskripsi kategori | Data kategori baru tersimpan dan dapat diperbarui | Kategori berhasil ditambah & diubah | **PASS** |
| 7 | **Proteksi Relasi Kategori** | Percobaan menghapus kategori yang masih memiliki produk | Sistem menolak penghapusan kategori yang sedang dipakai | Penghapusan ditolak (`delete() === false`) | **PASS** |
| 8 | **Manajemen Produk & Barcode** | Tambah produk baru dengan stok awal 20 pcs & cari via Barcode/SKU | Produk tersimpan, pencarian barcode akurat, stok awal tercatat di `stock_movements` | Produk ditemukan via barcode, stok = 20 pcs | **PASS** |
| 9 | **Stok Masuk (Stock In)** | Catat stok masuk `+10 pcs` dengan pembaruan harga modal ke Rp 10.500 | Stok bertambah menjadi 30 pcs, harga modal terupdate, log mutasi `in` tercatat | Stok = 30 pcs, Harga Modal = Rp 10.500 | **PASS** |
| 10 | **Stok Keluar (Stock Out)** | Catat stok keluar `-3 pcs` (barang sampel/rusak) | Stok berkurang menjadi 27 pcs dan riwayat `out` tercatat | Stok = 27 pcs | **PASS** |
| 11 | **Penyesuaian Stok (Opname)** | Catat penyesuaian stok fisik aktual menjadi `25 pcs` | Sistem menghitung selisih (`-2 pcs`), mengubah stok ke 25 pcs & mencatat log `adjustment` | Stok disesuaikan ke 25 pcs | **PASS** |
| 12 | **Transaksi Kasir (POS)** | Checkout 5 pcs (@ Rp 15.000), Diskon Rp 5.000, Tunai Rp 100.000 | Subtotal Rp 75.000, Grand Total Rp 70.000, Kembalian Rp 30.000, Laba Kotor Rp 17.500 | Perhitungan faktur, laba kotor & kembalian 100% akurat | **PASS** |
| 13 | **Potong Stok Otomatis POS** | Cek stok produk setelah transaksi kasir selesai | Stok berkurang otomatis dari 25 pcs menjadi 20 pcs dan tercatat di kartu stok (`sale`) | Stok otomatis menjadi 20 pcs | **PASS** |
| 14 | **Konsistensi Transaksi DB** | Checkout dengan jumlah beli melebihi stok tersedia (999 pcs) | Transaksi dibatalkan (`rollBack()`), stok tidak berubah sama sekali | Rollback berhasil, stok tetap utuh 20 pcs | **PASS** |
| 15 | **Pelanggan & Supplier** | Tambah, ubah, dan hapus data pelanggan/supplier | Operasi CRUD berjalan lancar menggunakan PDO prepared statements | CRUD Pelanggan & Supplier berhasil | **PASS** |
| 16 | **Dashboard & 7 Laporan** | Query statistik dashboard, laporan harian, bulanan, terlaris, stok, & laba kotor | Seluruh data agregasi penjualan, valuasi stok, dan margin laba terhitung akurat | Seluruh laporan menghasilkan data valid | **PASS** |
| 17 | **Pengaturan Toko & Struk** | Simpan perubahan profil toko dan pengaturan tampilan struk | Data `store_settings` tersimpan dan langsung diterapkan pada struk | Pengaturan toko berhasil diperbarui | **PASS** |
| 18 | **Validasi Sintaks PHP 8.x** | Pemeriksaan `php -l` pada seluruh 44 file PHP di dalam proyek | Tidak ada syntax error atau deprecated warning pada seluruh kode sumber | 0 Syntax Error dari 44 file PHP | **PASS** |
| 19 | **Hapus Kategori Kosong** | Hapus kategori yang sudah tidak digunakan oleh produk mana pun | Kategori yang tidak memiliki produk berhasil dihapus bersih | Kategori berhasil dihapus | **PASS** |

