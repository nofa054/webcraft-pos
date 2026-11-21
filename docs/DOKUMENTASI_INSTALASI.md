# Dokumentasi Instalasi dan Konfigurasi Webcraft POS

Dokumen ini menjelaskan langkah-langkah instalasi dan konfigurasi aplikasi **Webcraft POS** baik pada server lokal (**XAMPP**, **Laragon**, **WAMP**, atau **PHP Built-in Server**) maupun pada layanan **Web Hosting (cPanel / Apache)**.

---

## 1. Persyaratan Sistem (System Requirements)

- **PHP**: Versi 8.0 ke atas (Direkomendasikan PHP 8.1 / 8.2 / 8.3 / 8.4)
- **Ekstensi PHP**:
  - `pdo` & `pdo_mysql` (dan/atau `pdo_sqlite` untuk mode lokal instan)
  - `mbstring`
  - `json`
  - `fileinfo` (untuk validasi upload logo toko)
  - `zip` & `xml` (untuk ekspor file `.xlsx`)
- **Database**: MySQL 5.7+ / MySQL 8.0+ atau MariaDB 10.4+
- **Web Server**: Apache 2.4+ (dengan `mod_rewrite` aktif) atau Nginx / PHP Built-in Server.

---

## 2. Langkah Instalasi pada Server Lokal (XAMPP / Laragon)

1. **Salin Folder Proyek**
   - Jika menggunakan **XAMPP**, salin seluruh isi folder proyek ke dalam `C:\xampp\htdocs\webcraft-pos`.
   - Jika menggunakan **Laragon**, salin ke dalam `C:\laragon\www\webcraft-pos`.

2. **Buat Database MySQL / MariaDB**
   - Buka **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Buat database baru dengan nama `webcraft_pos`.
   - Pilih tab **Import**, lalu pilih file `database/schema.sql` dari folder proyek dan klik **Go / Kirim**.
   - *(Catatan: Aplikasi juga dilengkapi fitur auto-init yang secara otomatis membuat tabel dan data awal apabila database `webcraft_pos` masih kosong).*

3. **Konfigurasi Koneksi Database (`.env`)**
   - Salin file `.env.example` menjadi `.env`.
   - Sesuaikan pengaturan database di dalam file `.env`:
     ```ini
     APP_NAME="Webcraft POS"
     APP_ENV="local"
     APP_DEBUG=true
     APP_TIMEZONE="Asia/Jakarta"

     DB_DRIVER="mysql"
     DB_HOST="127.0.0.1"
     DB_PORT="3306"
     DB_NAME="webcraft_pos"
     DB_USER="root"
     DB_PASS=""
     DB_CHARSET="utf8mb4"
     ```

4. **Instalasi Library Composer (Opsional)**
   - Untuk menginstal library `phpoffice/phpspreadsheet` dan `dompdf/dompdf` di folder `vendor/`, jalankan perintah berikut di terminal:
     ```bash
     composer install
     ```
   - *(Catatan: Apabila Anda belum menjalankan `composer install`, fitur ekspor Excel `.xlsx` dan PDF `.pdf` tetap berfungsi 100% menggunakan generator native bawaan `App\Helpers\Exporter`).*

5. **Akses Aplikasi melalui Browser**
   - Buka browser dan kunjungi `http://localhost/webcraft-pos/`.

---

## 3. Cara Menjalankan Cepat dengan PHP Built-in Server (Tanpa XAMPP)

Jika Anda ingin langsung menjalankan aplikasi melalui terminal:

```bash
php -S localhost:8000
```

Kemudian buka `http://localhost:8000` di browser Anda. Apabila service MySQL belum dinyalakan di komputer Anda dan opsi `DB_AUTO_FALLBACK_SQLITE=true` aktif, aplikasi akan otomatis menggunakan database lokal di `storage/database.sqlite` lengkap dengan data awal sehingga dapat langsung diuji coba.

---

## 4. Langkah Deployment ke Shared Hosting (cPanel)

1. **Buat Database & User di cPanel**
   - Masuk ke cPanel &rarr; **MySQL® Database Wizard**.
   - Buat database baru (misalnya `u12345_pos`) dan pengguna database beserta password-nya.
   - Berikan hak akses **ALL PRIVILEGES** kepada pengguna tersebut.
   - Buka **phpMyAdmin** di cPanel, pilih database yang baru dibuat, lalu **Import** file `database/schema.sql`.

2. **Unggah Source Code**
   - Kompres seluruh file proyek menjadi `.zip`, lalu unggah ke folder `public_html/` (atau subdomain pilihan Anda, misalnya `pos.tokoanda.com`) melalui **File Manager** cPanel, kemudian ekstrak.

3. **Sesuaikan File `.env`**
   - Buat file `.env` di direktori aplikasi dan masukkan kredensial database cPanel:
     ```ini
     APP_NAME="Toko Anda POS"
     APP_ENV="production"
     APP_DEBUG=false
     APP_TIMEZONE="Asia/Jakarta"

     DB_DRIVER="mysql"
     DB_HOST="localhost"
     DB_PORT="3306"
     DB_NAME="u12345_pos"
     DB_USER="u12345_user"
     DB_PASS="PasswordDatabaseAnda"
     DB_CHARSET="utf8mb4"
     DB_AUTO_FALLBACK_SQLITE=false
     ```

4. **Pastikan Izin Folder (Permissions)**
   - Pastikan folder `public/uploads/` dan `storage/` memiliki izin tulis (`chmod 755` atau `775`).
   - File `.htaccess` di root proyek secara otomatis melindungi folder `app/`, `config/`, `database/`, `storage/`, `views/`, dan file `.env` agar tidak dapat diakses langsung oleh publik.

