<?php

declare(strict_types=1);

/**
 * Script Otomatis Integrasi & Setup Laragon untuk Webcraft POS
 * 1. Mengecek koneksi MySQL Laragon (127.0.0.1:3306) dan membuat database `webcraft_pos` + tabel + seed data.
 * 2. Mencari lokasi instalasi Laragon (`www`) dan membuat tautan folder (`webcraft-pos`) agar bisa langsung diakses di http://localhost/webcraft-pos atau http://webcraft-pos.test
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

echo "=== SETUP OTOMATIS LARAGON - WEBCRAFT POS ===\n\n";

// 1. Cek & Buat Database MySQL di Laragon
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `webcraft_pos` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[OK] Database MySQL 'webcraft_pos' berhasil dibuat/ditemukan di Laragon!\n";

    // Paksa inisialisasi tabel dan data awal menggunakan class Database
    $appPdo = \App\Core\Database::getConnection();
    $driver = \App\Core\Database::getDriver();
    $tables = $appPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "[OK] Driver Aktif: " . strtoupper($driver) . " | Jumlah Tabel: " . count($tables) . " (" . implode(', ', $tables) . ")\n";
} catch (Throwable $e) {
    echo "[INFO] MySQL Laragon belum berjalan di port 3306 (" . $e->getMessage() . ").\n";
    echo "       Pastikan Anda sudah klik 'Start All' di aplikasi Laragon.\n";
}

// 2. Cari folder laragon/www dan hubungkan proyek E:\Web POS
$possiblePaths = [
    'C:\\laragon\\www',
    'D:\\laragon\\www',
    'E:\\laragon\\www',
    getenv('USERPROFILE') . '\\laragon\\www',
];

$foundLaragon = null;
foreach ($possiblePaths as $path) {
    if ($path && is_dir($path)) {
        $foundLaragon = $path;
        break;
    }
}

if ($foundLaragon !== null) {
    echo "[OK] Folder Laragon ditemukan di: {$foundLaragon}\n";
    $targetLink = $foundLaragon . DIRECTORY_SEPARATOR . 'webcraft-pos';
    if (!file_exists($targetLink)) {
        $cmd = sprintf('mklink /J %s %s', escapeshellarg($targetLink), escapeshellarg(BASE_PATH));
        exec($cmd, $out, $ret);
        if ($ret === 0) {
            echo "[OK] Berhasil menghubungkan proyek ke: {$targetLink}\n";
            echo "     Sekarang Anda bisa langsung membuka: http://localhost/webcraft-pos\n";
        } else {
            echo "[INFO] Gagal membuat junction otomatis. Anda dapat menyalin folder E:\\Web POS ke {$targetLink}\n";
        }
    } else {
        echo "[OK] Folder proyek sudah terhubung di: {$targetLink}\n";
        echo "     Buka di browser: http://localhost/webcraft-pos\n";
    }
} else {
    echo "[INFO] Folder standar C:\\laragon\\www tidak ditemukan di lokasi default.\n";
}

// 3. Segarkan tanggal transaksi sampel agar selalu berada di rentang 7 hari terakhir hingga hari ini
$appPdo = \App\Core\Database::getConnection();
$rows = $appPdo->query("SELECT id FROM sales ORDER BY id ASC LIMIT 6")->fetchAll(PDO::FETCH_COLUMN);
$offsets = [4, 3, 2, 1, 0, 0];
foreach ($rows as $i => $id) {
    if (!isset($offsets[$i])) {
        break;
    }
    $daysAgo = $offsets[$i];
    $dt = date('Y-m-d', strtotime("-{$daysAgo} days")) . sprintf(' %02d:15:00', 9 + $i);
    $inv = 'INV-' . date('Ymd', strtotime("-{$daysAgo} days")) . sprintf('-000%d', $i + 1);
    $stmtUpd = $appPdo->prepare("UPDATE sales SET transaction_date = ?, created_at = ?, invoice_no = ? WHERE id = ?");
    $stmtUpd->execute([$dt, $dt, $inv, $id]);
}
echo "[OK] Tanggal sampel transaksi telah disegarkan ke minggu ini (" . date('d M Y') . ") pada driver: " . strtoupper(\App\Core\Database::getDriver()) . "\n";


