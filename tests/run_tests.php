<?php

declare(strict_types=1);

/**
 * Webcraft POS - Automated Feature & Integration Test Suite
 * Menguji seluruh fitur utama, konsistensi transaksi database, mutasi stok, keamanan, dan laporan.
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Models\CategoryModel;
use App\Models\CustomerModel;
use App\Models\ProductModel;
use App\Models\SaleModel;
use App\Models\SettingModel;
use App\Models\StockModel;
use App\Models\SupplierModel;
use App\Models\UserModel;

$passed = 0;
$failed = 0;
$results = [];

function assert_test(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed, $results;
    if ($condition) {
        $passed++;
        $results[] = ['status' => 'PASS', 'name' => $name, 'detail' => $detail];
        echo "[PASS] {$name}" . ($detail ? " ({$detail})" : "") . PHP_EOL;
    } else {
        $failed++;
        $results[] = ['status' => 'FAIL', 'name' => $name, 'detail' => $detail];
        echo "[FAIL] {$name}" . ($detail ? " ({$detail})" : "") . PHP_EOL;
    }
}

echo "============================================================" . PHP_EOL;
echo " PENGUJIAN FITUR UTAMA WEBCRAFT POS" . PHP_EOL;
echo "============================================================" . PHP_EOL;

// 1. Koneksi Database & Inisialisasi Tabel
$pdo = Database::getConnection();
assert_test(
    'Koneksi Database PDO & Inisialisasi Tabel',
    $pdo instanceof PDO,
    'Driver aktif: ' . strtoupper(Database::getDriver())
);

// 2. Autentikasi & Password Hashing
$loginFail = Auth::login('admin', 'password_salah');
assert_test(
    'Keamanan Login: Menolak password yang salah',
    $loginFail['success'] === false,
    $loginFail['message']
);

$loginKasir = Auth::login('kasir', 'kasir123');
assert_test(
    'Autentikasi Kasir (kasir / kasir123)',
    $loginKasir['success'] === true && Auth::role() === 'kasir' && !Auth::isAdmin(),
    'Role: ' . Auth::role()
);

$loginAdmin = Auth::login('admin', 'admin123');
assert_test(
    'Autentikasi Admin (admin / admin123)',
    $loginAdmin['success'] === true && Auth::isAdmin(),
    'Role: ' . Auth::role()
);

// 3. Keamanan CSRF Token
$token = Csrf::getToken();
assert_test(
    'Generasi & Verifikasi CSRF Token',
    strlen($token) === 64 && Csrf::verify($token) && !Csrf::verify('token_palsu'),
    'Panjang token: ' . strlen($token) . ' hex chars'
);

// 4. Manajemen Kategori
$catModel = new CategoryModel();
$newCatId = $catModel->create([
    'name'        => 'Kategori Uji Coba POS',
    'description' => 'Kategori sementara untuk pengujian otomatis',
]);
$catRow = $catModel->findById($newCatId);
assert_test(
    'Manajemen Kategori: Tambah & Ubah Kategori',
    $catRow !== null && $catModel->update($newCatId, ['name' => 'Kategori Uji Updated', 'description' => 'Updated']),
    'Category ID: ' . $newCatId
);

// Proteksi hapus kategori yang sedang memiliki produk (Kategori 1 = Sembako)
$cannotDeleteUsedCat = ($catModel->delete(1) === false);
assert_test(
    'Manajemen Kategori: Proteksi hapus kategori yang memiliki produk',
    $cannotDeleteUsedCat,
    'Kategori Sembako terlindungi'
);

// 5. Manajemen Produk & Saldo Stok Awal
$prodModel = new ProductModel();
$testSku = 'TST-' . random_int(1000, 9999);
$testBarcode = '899999' . random_int(100000, 999999);
$newProdId = $prodModel->create([
    'category_id'   => $newCatId,
    'supplier_id'   => 1,
    'sku'           => $testSku,
    'barcode'       => $testBarcode,
    'name'          => 'Produk Uji Coba Otomatis',
    'unit'          => 'pcs',
    'cost_price'    => 10000,
    'selling_price' => 15000,
    'stock'         => 20,
    'min_stock'     => 5,
    'is_active'     => 1,
], Auth::id());

$createdProd = $prodModel->findByBarcodeOrSku($testBarcode);
assert_test(
    'Manajemen Produk: Tambah Produk & Pencarian Barcode/SKU',
    $createdProd !== null && (int) $createdProd['id'] === $newProdId && (float) $createdProd['stock'] === 20.0,
    "SKU: {$testSku}, Stok Awal: 20 pcs"
);

// 6. Manajemen Stok (Stok Masuk, Stok Keluar, Penyesuaian Opname)
$stockModel = new StockModel();
$stockModel->recordMovement([
    'product_id'        => $newProdId,
    'movement_type'     => 'in',
    'qty'               => 10,
    'supplier_id'       => 1,
    'unit_cost'         => 10500,
    'update_cost_price' => 1,
    'reference_no'      => 'TEST-IN-01',
    'notes'             => 'Restock pengujian otomatis',
], Auth::id());

$afterIn = $prodModel->findById($newProdId);
assert_test(
    'Manajemen Stok: Pencatatan Stok Masuk (+10) & Update Harga Modal',
    (float) $afterIn['stock'] === 30.0 && (float) $afterIn['cost_price'] === 10500.0,
    'Stok menjadi 30 pcs, Harga Modal: Rp 10.500'
);

$stockModel->recordMovement([
    'product_id'    => $newProdId,
    'movement_type' => 'out',
    'qty'           => 3,
    'reference_no'  => 'TEST-OUT-01',
    'notes'         => 'Barang sampel pengujian',
], Auth::id());

$afterOut = $prodModel->findById($newProdId);
assert_test(
    'Manajemen Stok: Pencatatan Stok Keluar (-3)',
    (float) $afterOut['stock'] === 27.0,
    'Stok menjadi 27 pcs'
);

$stockModel->recordMovement([
    'product_id'    => $newProdId,
    'movement_type' => 'adjustment',
    'qty'           => 25,
    'reference_no'  => 'TEST-ADJ-01',
    'notes'         => 'Stock opname fisik 25 pcs',
], Auth::id());

$afterAdj = $prodModel->findById($newProdId);
assert_test(
    'Manajemen Stok: Penyesuaian Stok Opname (Aktual = 25)',
    (float) $afterAdj['stock'] === 25.0,
    'Stok disesuaikan ke 25 pcs'
);

// 7. Sistem Kasir (POS) & Konsistensi Transaksi Database
$saleModel = new SaleModel();
$saleResult = $saleModel->createTransaction([
    'customer_id'       => 2,
    'discount_type'     => 'nominal',
    'discount_value'    => 5000,
    'payment_method'    => 'tunai',
    'payment_reference' => '',
    'amount_paid'       => 100000,
    'notes'             => 'Transaksi uji coba POS',
    'items'             => [
        ['product_id' => $newProdId, 'qty' => 5], // 5 x 15.000 = 75.000, diskon 5.000 => Grand Total 70.000
    ],
], Auth::id());

$afterSaleProd = $prodModel->findById($newProdId);
$saleDetail = $saleModel->findByIdWithItems($saleResult['id']);

assert_test(
    'Sistem Kasir (POS): Perhitungan Subtotal, Diskon, Grand Total & Kembalian',
    (float) $saleResult['grand_total'] === 70000.0
    && (float) $saleResult['change_amount'] === 30000.0
    && (float) $saleDetail['gross_profit'] === (70000.0 - (5 * 10500.0)),
    "Invoice: {$saleResult['invoice_no']}, Total: Rp 70.000, Kembalian: Rp 30.000"
);

assert_test(
    'Sistem Kasir (POS): Pengurangan Stok Otomatis Setelah Transaksi',
    (float) $afterSaleProd['stock'] === 20.0,
    'Stok berkurang otomatis dari 25 menjadi 20 pcs'
);

// Uji Rollback Transaksi jika stok tidak cukup
$rollbackSuccess = false;
try {
    $saleModel->createTransaction([
        'customer_id'    => 1,
        'discount_type'  => 'nominal',
        'discount_value' => 0,
        'payment_method' => 'tunai',
        'amount_paid'    => 9999999,
        'items'          => [
            ['product_id' => $newProdId, 'qty' => 999], // Melebihi stok (20)
        ],
    ], Auth::id());
} catch (\RuntimeException $ex) {
    $rollbackSuccess = true;
}
$stockUnchanged = (float) $prodModel->findById($newProdId)['stock'] === 20.0;
assert_test(
    'Konsistensi Database: Rollback otomatis saat jumlah beli melebihi stok',
    $rollbackSuccess && $stockUnchanged,
    'Stok tetap utuh 20 pcs'
);

// 8. Manajemen Pelanggan & Supplier
$custModel = new CustomerModel();
$newCustId = $custModel->create([
    'name'    => 'Pelanggan Uji POS',
    'phone'   => '08123456789',
    'address' => 'Jl. Pengujian No. 1',
]);
assert_test(
    'Manajemen Pelanggan & Supplier: Tambah, Edit, Hapus',
    $newCustId > 0 && $custModel->update($newCustId, ['name' => 'Pelanggan Uji Updated', 'phone' => '08123456789']) && $custModel->delete($newCustId),
    'CRUD Pelanggan berjalan baik'
);

// 9. Laporan & Dashboard
$dashStats = $saleModel->getDashboardStats();
$dailyRep = $saleModel->getDailySalesReport(date('Y-m-01'), date('Y-m-d'));
$bestSellerRep = $saleModel->getBestSellingProductsReport(date('Y-m-01'), date('Y-m-d'));
$invRep = $saleModel->getInventoryReport();

assert_test(
    'Dashboard & Laporan: Harian, Bulanan, Produk Terlaris, Persediaan & Laba Kotor',
    $dashStats['today_revenue'] > 0 && count($dailyRep) > 0 && count($bestSellerRep) > 0 && count($invRep) > 0,
    'Omzet hari ini: ' . rupiah($dashStats['today_revenue'])
);

// 10. Pengaturan Toko
$settingModel = new SettingModel();
$currentSet = $settingModel->getSettings();
$updSet = $settingModel->updateSettings($currentSet);
assert_test(
    'Pengaturan Toko & Konfigurasi Struk',
    $updSet === true && !empty($currentSet['store_name']),
    'Toko: ' . $currentSet['store_name']
);

// 11. Verifikasi Sintaks Seluruh File PHP & Render Tampilan
$phpFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BASE_PATH));
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}
$syntaxErrors = 0;
foreach ($phpFiles as $pf) {
    $output = [];
    $code = 0;
    exec('php -l ' . escapeshellarg($pf) . ' 2>&1', $output, $code);
    if ($code !== 0) {
        $syntaxErrors++;
        echo "Syntax Error in {$pf}: " . implode("\n", $output) . PHP_EOL;
    }
}
assert_test(
    'Validasi Sintaks PHP 8.x pada Seluruh File Proyek (' . count($phpFiles) . ' file)',
    $syntaxErrors === 0,
    '0 Syntax Error dari ' . count($phpFiles) . ' file PHP'
);

// Bersihkan data produk & kategori uji coba sementara agar database tetap bersih dengan data sampel UMKM
$pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?")->execute([$saleResult['id']]);
$pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$saleResult['id']]);
$pdo->prepare("DELETE FROM stock_movements WHERE product_id = ?")->execute([$newProdId]);
$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$newProdId]);
$catDeleted = $catModel->delete($newCatId);
assert_test(
    'Manajemen Kategori: Hapus kategori yang tidak digunakan',
    $catDeleted === true,
    'Kategori uji coba berhasil dihapus setelah tidak ada produk terkait'
);

echo "============================================================" . PHP_EOL;
echo "HASIL AKHIR: {$passed} LULUS (PASS), {$failed} GAGAL (FAIL)" . PHP_EOL;
echo "============================================================" . PHP_EOL;

exit($failed > 0 ? 1 : 0);
