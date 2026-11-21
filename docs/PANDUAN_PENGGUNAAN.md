# Panduan Penggunaan Aplikasi Webcraft POS

Panduan ini ditujukan bagi **Pemilik Usaha / Administrator Toko** dan **Staf Kasir** dalam mengoperasikan sistem **Webcraft POS** sehari-hari.

---

## 1. Login dan Manajemen Akun

1. Buka halaman utama aplikasi, maka Anda akan diarahkan ke halaman **Login**.
2. Masukkan **Username** dan **Password**:
   - **Admin Default**: `admin` / `admin123`
   - **Kasir Default**: `kasir` / `kasir123`
3. Untuk mengganti nama profil atau password pribadi Anda:
   - Klik nama akun Anda di pojok kanan atas &rarr; pilih **Pengaturan Profil & Password**.
4. Untuk keluar dari sistem, klik menu akun di pojok kanan atas &rarr; **Keluar (Logout)**.

---

## 2. Navigasi Dashboard

Pada halaman **Dashboard**, Anda dapat memantau kondisi toko secara real-time:
- **Omzet Penjualan Hari Ini**: Total pendapatan kotor dan laba kotor hari ini.
- **Jumlah Transaksi Hari Ini**: Banyaknya struk penjualan hari ini serta rekap bulanan.
- **Total Produk Aktif**: Jumlah item yang siap dijual di kasir.
- **Informasi Stok Menipis**: Jumlah produk yang stoknya berada pada atau di bawah batas minimum (`min_stock`).
- **Grafik Tren Penjualan 7 Hari Terakhir**: Grafik perbandingan omzet dan laba kotor harian.
- **Distribusi Metode Pembayaran**: Proporsi pembayaran Tunai, Transfer, dan QRIS.

---

## 3. Mengelola Kategori & Katalog Produk (Khusus Admin)

### A. Kategori Produk
1. Buka menu **Kategori Produk** di sidebar kiri.
2. Isi formulir **Tambah Kategori Baru** di sebelah kiri, lalu klik **Simpan Kategori**.
3. Untuk menghapus kategori, klik tombol tempat sampah. Sistem secara otomatis mengunci penghapusan apabila kategori tersebut masih digunakan oleh produk agar integritas data terjaga.

### B. Data Produk
1. Buka menu **Data Produk** &rarr; klik tombol **Tambah Produk Baru**.
2. Isi informasi produk:
   - **Kode Produk / SKU**: Terisi otomatis (misal `PRD-0013`) atau dapat diubah manual.
   - **Barcode Produk**: Opsional, arahkan scanner barcode ke kolom ini jika produk memiliki barcode kemasan.
   - **Satuan Jual**: Pilih atau ketik satuan seperti `pcs`, `kg`, `liter`, `dus`, `sak`, `btl`, `rcg`, `klg`, `bks`, dll.
   - **Harga Modal & Harga Jual**: Sistem otomatis menghitung margin keuntungan per unit.
   - **Stok Awal & Batas Minimum Stok**: Jika stok menyentuh batas minimum, produk akan masuk ke daftar peringatan stok menipis.

---

## 4. Transaksi Penjualan di Mesin Kasir (POS)

1. Buka menu **Mesin Kasir (POS)** (atau tekan tombol pintas keyboard **`F2`** dari halaman mana saja).
2. **Memilih Produk**:
   - Ketik nama produk atau SKU di kolom pencarian, atau filter berdasarkan kategori, lalu klik kartu produk.
   - **Menggunakan Barcode Scanner**: Letakkan kursor pada kolom pencarian, scan barcode barang. Produk akan otomatis masuk ke keranjang belanja saat scanner mengirimkan sinyal `Enter`.
3. **Mengatur Keranjang Belanja**:
   - Gunakan tombol `-` / `+` atau ketik langsung jumlah beli pada kolom **Qty** (mendukung angka pecahan/desimal seperti `0.5` atau `1.5` untuk penjualan curah seperti kg/liter/meter).
   - Pilih **Pelanggan** di bagian atas keranjang (default: *Pelanggan Umum*), atau klik ikon **`+`** untuk mendaftarkan pelanggan baru langsung dari layar kasir.
4. **Diskon & Pembayaran**:
   - Jika ada diskon transaksi, pilih tipe diskon (**Rp** untuk nominal atau **%** untuk persentase) dan isi nilainya.
   - Pilih **Metode Pembayaran**:
     - **Tunai**: Masukkan jumlah uang yang diterima dari pembeli atau klik tombol cepat (**Uang Pas**, **20.000**, **50.000**, **100.000**, **200.000**). Sistem otomatis menghitung **Uang Kembalian**.
     - **Transfer / QRIS**: Nominal uang diterima otomatis menyesuaikan total tagihan, dan Anda dapat mencatat **Nomor Referensi / RRN** pembayaran.
5. Klik tombol **Proses Pembayaran & Simpan**.
6. Layar pop-up rincian kembalian akan muncul. Klik **Cetak Struk Penjualan** untuk mencetak struk ke printer thermal.

---

## 5. Manajemen Stok (Stok Masuk, Stok Keluar & Stock Opname)

Buka menu **Manajemen Stok**:
- **Catat Stok Masuk (Pembelian)**: Digunakan saat barang datang dari supplier. Pilih produk, masukkan jumlah masuk (`+`), pilih supplier, dan centang opsi pembaruan harga modal jika harga beli berubah.
- **Catat Stok Keluar**: Digunakan untuk barang rusak, kadaluarsa, atau pemakaian internal toko (`-`).
- **Penyesuaian Stok (Stock Opname)**: Digunakan saat pemeriksaan fisik gudang. Masukkan jumlah fisik aktual yang dihitung di gudang; sistem akan otomatis menyesuaikan selisihnya (`+` atau `-`) dan mencatatnya di riwayat kartu stok.
- **Notifikasi Stok Minimum**: Klik ikon lonceng di bagian atas layar kapan saja untuk melihat daftar barang yang hampir habis.

---

## 6. Laporan & Ekspor ke Excel / PDF (Khusus Admin)

Buka menu **Laporan & Laba**, lalu pilih salah satu dari 7 tab laporan yang tersedia:
1. **Penjualan Harian**: Rekapitulasi transaksi, diskon, omzet, HPP, dan laba kotor per tanggal.
2. **Penjualan Bulanan**: Rekapitulasi performa penjualan per bulan dalam satu tahun buku.
3. **Rentang Tanggal**: Laporan transaksi penjualan berdasarkan periode tanggal awal s/d akhir.
4. **Rincian Transaksi**: Daftar detail per faktur/invoice dengan filter metode pembayaran.
5. **Produk Terlaris**: Peringkat produk dengan kuantitas dan kontribusi penjualan tertinggi.
6. **Persediaan Barang**: Laporan posisi stok terkini beserta valuasi nilai aset modal dan estimasi nilai jual.
7. **Keuntungan Kotor**: Analisis margin laba kotor per produk serta rekapitulasi laba kotor bersih.

Klik tombol **Ekspor Excel (.xlsx)** atau **Ekspor PDF (.pdf)** di sebelah kanan filter untuk mengunduh laporan resmi.

---

## 7. Pengaturan Toko & Cetakan Struk (Khusus Admin)

Buka menu **Pengaturan Toko**:
- Ubah nama toko, slogan, alamat, nomor telepon, email, serta unggah **Logo Toko**.
- Pilih ukuran kertas printer (**Thermal 80mm** atau **Thermal 58mm**).
- Atur teks sub-header dan catatan kaki (*footer*) struk, serta sakelar tampilkan logo, nama kasir, dan nama pelanggan pada struk.

