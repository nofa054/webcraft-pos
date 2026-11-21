<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Kelas Database (Singleton PDO Wrapper)
 * Mendukung MySQL/MariaDB (utama) dan SQLite (fallback otomatis untuk demo/pengujian lokal).
 */
class Database
{
    private static ?PDO $pdo = null;
    private static string $activeDriver = 'mysql';

    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $dbConf = $config['db'];

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $driver = strtolower($dbConf['driver'] ?? 'mysql');

        if ($driver === 'mysql') {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $dbConf['host'],
                    $dbConf['port'],
                    $dbConf['database'],
                    $dbConf['charset']
                );
                self::$pdo = new PDO($dsn, $dbConf['username'], $dbConf['password'], $options);
                self::$activeDriver = 'mysql';
                self::ensureMysqlInitialized(self::$pdo);
                return self::$pdo;
            } catch (PDOException $e) {
                // Coba buat database otomatis jika MySQL aktif namun nama database belum dibuat
                try {
                    $rootDsn = sprintf(
                        'mysql:host=%s;port=%s;charset=%s',
                        $dbConf['host'],
                        $dbConf['port'],
                        $dbConf['charset']
                    );
                    $tempPdo = new PDO($rootDsn, $dbConf['username'], $dbConf['password'], $options);
                    $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbConf['database']);
                    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                    $dsn = sprintf(
                        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                        $dbConf['host'],
                        $dbConf['port'],
                        $dbName,
                        $dbConf['charset']
                    );
                    self::$pdo = new PDO($dsn, $dbConf['username'], $dbConf['password'], $options);
                    self::$activeDriver = 'mysql';
                    self::ensureMysqlInitialized(self::$pdo);
                    return self::$pdo;
                } catch (PDOException $innerEx) {
                    if (empty($dbConf['fallback_sqlite'])) {
                        throw new RuntimeException('Koneksi Database MySQL gagal: ' . $e->getMessage());
                    }
                }
            }
        }

        // Fallback atau mode SQLite
        if (!is_dir(STORAGE_PATH)) {
            mkdir(STORAGE_PATH, 0775, true);
        }

        $sqlitePath = $dbConf['sqlite_path'];
        self::$pdo = new PDO('sqlite:' . $sqlitePath, null, null, $options);
        self::$pdo->exec('PRAGMA foreign_keys = ON');
        self::$pdo->exec('PRAGMA journal_mode = WAL');
        self::$activeDriver = 'sqlite';

        self::ensureSqliteInitialized(self::$pdo);

        return self::$pdo;
    }

    public static function getDriver(): string
    {
        if (self::$pdo === null) {
            self::getConnection();
        }
        return self::$activeDriver;
    }

    private static function ensureMysqlInitialized(PDO $pdo): void
    {
        $stmt = $pdo->query("SHOW TABLES LIKE 'stock_movements'");
        if ($stmt->fetchColumn() !== false) {
            return;
        }

        $schemaFile = BASE_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'schema.sql';
        if (is_file($schemaFile)) {
            $sql = (string) file_get_contents($schemaFile);
            // Hilangkan baris komentar SQL dan jalankan per statement agar kompatibel di semua versi MySQL/MariaDB Laragon
            $lines = explode("\n", $sql);
            $cleanSql = '';
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                    continue;
                }
                $cleanSql .= $line . "\n";
            }
            $statements = array_filter(array_map('trim', explode(';', $cleanSql)));
            foreach ($statements as $statement) {
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }
        }
        self::seedSampleTransactions($pdo);
    }

    private static function ensureSqliteInitialized(PDO $pdo): void
    {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if ($stmt->fetchColumn() !== false) {
            return;
        }

        $pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                username TEXT NOT NULL UNIQUE,
                email TEXT DEFAULT NULL,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'kasir',
                is_active INTEGER NOT NULL DEFAULT 1,
                last_login TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE store_settings (
                id INTEGER PRIMARY KEY DEFAULT 1,
                store_name TEXT NOT NULL DEFAULT 'Webcraft POS UMKM',
                store_tagline TEXT DEFAULT 'Solusi Kasir & Inventori Terpadu',
                address TEXT DEFAULT NULL,
                phone TEXT DEFAULT NULL,
                email TEXT DEFAULT NULL,
                logo_path TEXT DEFAULT NULL,
                receipt_header TEXT DEFAULT NULL,
                receipt_footer TEXT DEFAULT 'Terima kasih telah berbelanja di toko kami!',
                show_logo_on_receipt INTEGER NOT NULL DEFAULT 1,
                show_cashier_on_receipt INTEGER NOT NULL DEFAULT 1,
                show_customer_on_receipt INTEGER NOT NULL DEFAULT 1,
                receipt_paper_width TEXT NOT NULL DEFAULT '80mm',
                currency_symbol TEXT NOT NULL DEFAULT 'Rp',
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                description TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE suppliers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                contact_person TEXT DEFAULT NULL,
                phone TEXT DEFAULT NULL,
                email TEXT DEFAULT NULL,
                address TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                phone TEXT DEFAULT NULL,
                email TEXT DEFAULT NULL,
                address TEXT DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                supplier_id INTEGER DEFAULT NULL,
                sku TEXT NOT NULL UNIQUE,
                barcode TEXT DEFAULT NULL,
                name TEXT NOT NULL,
                unit TEXT NOT NULL DEFAULT 'pcs',
                cost_price REAL NOT NULL DEFAULT 0,
                selling_price REAL NOT NULL DEFAULT 0,
                stock REAL NOT NULL DEFAULT 0,
                min_stock REAL NOT NULL DEFAULT 5,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
                FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
            );

            CREATE TABLE sales (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_no TEXT NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                customer_id INTEGER DEFAULT NULL,
                transaction_date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                subtotal REAL NOT NULL DEFAULT 0,
                discount_type TEXT NOT NULL DEFAULT 'nominal',
                discount_value REAL NOT NULL DEFAULT 0,
                discount_amount REAL NOT NULL DEFAULT 0,
                grand_total REAL NOT NULL DEFAULT 0,
                total_cost REAL NOT NULL DEFAULT 0,
                gross_profit REAL NOT NULL DEFAULT 0,
                payment_method TEXT NOT NULL DEFAULT 'tunai',
                payment_reference TEXT DEFAULT NULL,
                amount_paid REAL NOT NULL DEFAULT 0,
                change_amount REAL NOT NULL DEFAULT 0,
                notes TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
            );

            CREATE TABLE sale_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sale_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                product_sku TEXT NOT NULL,
                product_name TEXT NOT NULL,
                unit TEXT NOT NULL DEFAULT 'pcs',
                qty REAL NOT NULL DEFAULT 1,
                cost_price REAL NOT NULL DEFAULT 0,
                selling_price REAL NOT NULL DEFAULT 0,
                subtotal REAL NOT NULL DEFAULT 0,
                profit REAL NOT NULL DEFAULT 0,
                FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
            );

            CREATE TABLE stock_movements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                supplier_id INTEGER DEFAULT NULL,
                movement_type TEXT NOT NULL,
                reference_no TEXT DEFAULT NULL,
                qty_change REAL NOT NULL,
                stock_before REAL NOT NULL,
                stock_after REAL NOT NULL,
                unit_cost REAL DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
                FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
            );
        ");

        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $kasirPass = password_hash('kasir123', PASSWORD_DEFAULT);

        $stmtUser = $pdo->prepare("INSERT INTO users (name, username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $stmtUser->execute(['Administrator Toko', 'admin', 'admin@webcraftpos.local', $adminPass, 'admin']);
        $stmtUser->execute(['Siti Rahma (Kasir)', 'kasir', 'kasir@webcraftpos.local', $kasirPass, 'kasir']);

        $stmtStore = $pdo->prepare("
            INSERT INTO store_settings (id, store_name, store_tagline, address, phone, email, receipt_header, receipt_footer, show_logo_on_receipt, show_cashier_on_receipt, show_customer_on_receipt, receipt_paper_width, currency_symbol)
            VALUES (1, ?, ?, ?, ?, ?, ?, ?, 1, 1, 1, '80mm', 'Rp')
        ");
        $stmtStore->execute([
            'Toko Maju Jaya Mandiri',
            'Retail, Sembako, Bahan Bangunan & Kebutuhan Harian',
            'Jl. Merdeka Raya No. 88, Kec. Sukamaju, Kota Bandung',
            '0812-3456-7890',
            'info@majujaya.co.id',
            'Melayani Eceran & Grosir dengan Harga Bersahabat',
            'Terima kasih atas kunjungan Anda! Simpan struk ini sebagai bukti pembayaran yang sah.',
        ]);

        $categories = [
            ['Sembako & Bahan Pokok', 'Beras, minyak goreng, gula, tepung, dan kebutuhan pokok'],
            ['Minuman & Kemasan', 'Air mineral, kopi, teh, susu, dan minuman ringan'],
            ['Alat & Bahan Bangunan', 'Semen, cat, paku, kuas, dan perlengkapan pertukangan'],
            ['Pakaian & Tekstil', 'Kaos, kemeja, celana, dan perlengkapan sandang'],
            ['Pakan & Peternakan', 'Pakan ayam, pakan ikan, vitamin ternak'],
            ['Kebutuhan Rumah Tangga', 'Sabun, deterjen, tisu, dan alat kebersihan'],
        ];
        $stmtCat = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        foreach ($categories as $c) {
            $stmtCat->execute($c);
        }

        $suppliers = [
            ['SUP-001', 'PT Sumber Pangan Nusantara', 'Budi Santoso', '0811-2233-4455', 'sales@sumberpangan.co.id', 'Kawasan Industri Jababeka Blok C-12, Bekasi'],
            ['SUP-002', 'CV Mitra Bangunan Perkasa', 'Hendra Wijaya', '0813-9876-5432', 'order@mitrabangunan.id', 'Jl. Soekarno Hatta No. 412, Bandung'],
            ['SUP-003', 'UD Agro Pakan Makmur', 'Dewi Lestari', '0857-1122-3344', 'agropakan@gmail.com', 'Jl. Raya Lembang KM 14, Bandung Barat'],
        ];
        $stmtSup = $pdo->prepare("INSERT INTO suppliers (code, name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($suppliers as $s) {
            $stmtSup->execute($s);
        }

        $customers = [
            ['CUST-001', 'Pelanggan Umum', '-', '-', '-', 'Pelanggan walk-in tanpa akun khusus'],
            ['CUST-002', 'Warung Bu Haji Aminah', '0812-8899-7766', 'aminah.warung@gmail.com', 'Jl. Melati No. 14, RT 03/05', 'Pelanggan grosir mingguan'],
            ['CUST-003', 'Bapak Dedi Kontraktor', '0819-3344-5566', 'dedi.karya@yahoo.com', 'Komp. Griya Asri Blok B4', 'Pembelian rutin material bangunan'],
        ];
        $stmtCust = $pdo->prepare("INSERT INTO customers (code, name, phone, email, address, notes) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($customers as $cu) {
            $stmtCust->execute($cu);
        }

        $products = [
            [1, 1, 'SMB-001', '8991002101012', 'Beras Premium Pandan Wangi 5kg', 'sak', 62000, 72500, 45, 10],
            [1, 1, 'SMB-002', '8991002101029', 'Minyak Goreng Sawit Tropical 2 Liter', 'pch', 31000, 36500, 60, 15],
            [1, 1, 'SMB-003', '8991002101036', 'Gula Pasir Kristal Putih 1kg', 'kg', 14500, 17500, 8, 20],
            [2, 1, 'MNM-001', '8992761136017', 'Air Mineral Aqua Botol 600ml', 'btl', 2800, 4000, 120, 24],
            [2, 1, 'MNM-002', '8992761136024', 'Kopi Kapal Api Special Mix (Renceng 10x24g)', 'rcg', 11500, 14500, 35, 10],
            [3, 2, 'BGN-001', '8993004400105', 'Semen Tiga Roda 50kg', 'sak', 58000, 66000, 25, 10],
            [3, 2, 'BGN-002', '8993004400112', 'Cat Tembok Dulux Catylac Putih 5kg', 'klg', 135000, 158000, 4, 5],
            [3, 2, 'BGN-003', '8993004400129', 'Paku Kayu 5cm Kualitas Super', 'kg', 16000, 22000, 18, 5],
            [4, null, 'PKN-001', '8994005500201', 'Kaos Polos Cotton Combed 30s Hitam XL', 'pcs', 32000, 55000, 22, 5],
            [5, 3, 'PKT-001', '8995006600309', 'Pakan Ayam Broiler Starter BR-1', 'kg', 9200, 11500, 3, 25],
            [5, 3, 'PKT-002', '8995006600316', 'Pakan Ikan Lele Hi-Pro-Vite 781', 'kg', 10500, 13000, 50, 15],
            [6, 1, 'RMT-001', '8996007700407', 'Deterjen Bubuk Rinso Anti Noda 770g', 'bks', 17500, 21500, 28, 8],
        ];
        $stmtProd = $pdo->prepare("
            INSERT INTO products (category_id, supplier_id, sku, barcode, name, unit, cost_price, selling_price, stock, min_stock, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        foreach ($products as $p) {
            $stmtProd->execute($p);
        }

        self::seedSampleTransactions($pdo);
    }

    private static function seedSampleTransactions(PDO $pdo): void
    {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
        if ($count > 0) {
            return;
        }

        // Buat sampel histori stok masuk awal dan beberapa transaksi 5 hari terakhir hingga hari ini
        $stmtMove = $pdo->prepare("
            INSERT INTO stock_movements (product_id, user_id, supplier_id, movement_type, reference_no, qty_change, stock_before, stock_after, unit_cost, notes, created_at)
            VALUES (?, 1, ?, 'in', ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtMove->execute([1, 1, 'PO-INIT-001', 50, 0, 50, 62000, 'Stok awal gudang', date('Y-m-d H:i:s', strtotime('-6 days 08:30:00'))]);
        $stmtMove->execute([2, 1, 'PO-INIT-002', 65, 0, 65, 31000, 'Stok awal gudang', date('Y-m-d H:i:s', strtotime('-6 days 08:35:00'))]);
        $stmtMove->execute([6, 2, 'PO-INIT-003', 30, 0, 30, 58000, 'Pengiriman supplier material', date('Y-m-d H:i:s', strtotime('-5 days 09:00:00'))]);

        $sampleSales = [
            [
                'days_ago' => 4,
                'time'     => '10:15:22',
                'seq'      => '0001',
                'user_id'  => 2,
                'cust_id'  => 2,
                'method'   => 'tunai',
                'ref'      => null,
                'discount' => 0,
                'paid'     => 250000,
                'items'    => [
                    [1, 'SMB-001', 'Beras Premium Pandan Wangi 5kg', 'sak', 2, 62000, 72500],
                    [2, 'SMB-002', 'Minyak Goreng Sawit Tropical 2 Liter', 'pch', 2, 31000, 36500],
                ],
            ],
            [
                'days_ago' => 3,
                'time'     => '14:20:10',
                'seq'      => '0001',
                'user_id'  => 2,
                'cust_id'  => 3,
                'method'   => 'transfer',
                'ref'      => 'BCA-8829102',
                'discount' => 10000,
                'paid'     => 474000,
                'items'    => [
                    [6, 'BGN-001', 'Semen Tiga Roda 50kg', 'sak', 5, 58000, 66000],
                    [7, 'BGN-002', 'Cat Tembok Dulux Catylac Putih 5kg', 'klg', 1, 135000, 158000],
                ],
            ],
            [
                'days_ago' => 2,
                'time'     => '11:05:45',
                'seq'      => '0001',
                'user_id'  => 1,
                'cust_id'  => 1,
                'method'   => 'qris',
                'ref'      => 'QRIS-99102341',
                'discount' => 0,
                'paid'     => 153000,
                'items'    => [
                    [9, 'PKN-001', 'Kaos Polos Cotton Combed 30s Hitam XL', 'pcs', 2, 32000, 55000],
                    [12, 'RMT-001', 'Deterjen Bubuk Rinso Anti Noda 770g', 'bks', 2, 17500, 21500],
                ],
            ],
            [
                'days_ago' => 1,
                'time'     => '16:40:12',
                'seq'      => '0001',
                'user_id'  => 2,
                'cust_id'  => 2,
                'method'   => 'tunai',
                'ref'      => null,
                'discount' => 5000,
                'paid'     => 250000,
                'items'    => [
                    [1, 'SMB-001', 'Beras Premium Pandan Wangi 5kg', 'sak', 2, 62000, 72500],
                    [5, 'MNM-002', 'Kopi Kapal Api Special Mix (Renceng 10x24g)', 'rcg', 4, 11500, 14500],
                    [3, 'SMB-003', 'Gula Pasir Kristal Putih 1kg', 'kg', 2, 14500, 17500],
                ],
            ],
            [
                'days_ago' => 0,
                'time'     => '09:12:30',
                'seq'      => '0001',
                'user_id'  => 2,
                'cust_id'  => 1,
                'method'   => 'tunai',
                'ref'      => null,
                'discount' => 0,
                'paid'     => 150000,
                'items'    => [
                    [1, 'SMB-001', 'Beras Premium Pandan Wangi 5kg', 'sak', 1, 62000, 72500],
                    [2, 'SMB-002', 'Minyak Goreng Sawit Tropical 2 Liter', 'pch', 1, 31000, 36500],
                    [4, 'MNM-001', 'Air Mineral Aqua Botol 600ml', 'btl', 5, 2800, 4000],
                ],
            ],
            [
                'days_ago' => 0,
                'time'     => '10:45:00',
                'seq'      => '0002',
                'user_id'  => 1,
                'cust_id'  => 2,
                'method'   => 'qris',
                'ref'      => 'QRIS-77482910',
                'discount' => 0,
                'paid'     => 130000,
                'items'    => [
                    [11, 'PKT-002', 'Pakan Ikan Lele Hi-Pro-Vite 781', 'kg', 10, 10500, 13000],
                ],
            ],
        ];

        $stmtSale = $pdo->prepare("
            INSERT INTO sales (invoice_no, user_id, customer_id, transaction_date, subtotal, discount_type, discount_value, discount_amount, grand_total, total_cost, gross_profit, payment_method, payment_reference, amount_paid, change_amount, notes, created_at)
            VALUES (?, ?, ?, ?, ?, 'nominal', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Transaksi Penjualan', ?)
        ");
        $stmtItem = $pdo->prepare("
            INSERT INTO sale_items (sale_id, product_id, product_sku, product_name, unit, qty, cost_price, selling_price, subtotal, profit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($sampleSales as $s) {
            $dateStr = date('Y-m-d', strtotime("-{$s['days_ago']} days"));
            $dateCompact = date('Ymd', strtotime("-{$s['days_ago']} days"));
            $dt = $dateStr . ' ' . $s['time'];
            $inv = "INV-{$dateCompact}-{$s['seq']}";

            $subtotal = 0;
            $totalCost = 0;
            foreach ($s['items'] as $it) {
                $subtotal += $it[4] * $it[6];
                $totalCost += $it[4] * $it[5];
            }
            $disc = $s['discount'];
            $grand = $subtotal - $disc;
            $profit = $grand - $totalCost;
            $change = max(0, $s['paid'] - $grand);

            $stmtSale->execute([
                $inv,
                $s['user_id'],
                $s['cust_id'],
                $dt,
                $subtotal,
                $disc,
                $disc,
                $grand,
                $totalCost,
                $profit,
                $s['method'],
                $s['ref'],
                $s['paid'],
                $change,
                $dt,
            ]);
            $saleId = (int) $pdo->lastInsertId();

            foreach ($s['items'] as $it) {
                $lineSub = $it[4] * $it[6];
                $lineProfit = $lineSub - ($it[4] * $it[5]);
                $stmtItem->execute([
                    $saleId,
                    $it[0],
                    $it[1],
                    $it[2],
                    $it[3],
                    $it[4],
                    $it[5],
                    $it[6],
                    $lineSub,
                    $lineProfit,
                ]);
            }
        }
    }
}

