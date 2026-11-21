<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Helpers\Exporter;
use App\Models\CategoryModel;
use App\Models\SaleModel;
use App\Models\UserModel;

class ReportController extends BaseController
{
    private SaleModel $saleModel;

    public function __construct()
    {
        parent::__construct();
        $this->saleModel = new SaleModel();
    }

    public function index(): void
    {
        Auth::requireRole('admin');

        $reportType = trim((string) ($_GET['type'] ?? 'daily'));
        $validTypes = ['daily', 'monthly', 'range', 'transactions', 'bestseller', 'inventory', 'profit'];
        if (!in_array($reportType, $validTypes, true)) {
            $reportType = 'daily';
        }

        $startDate = trim((string) ($_GET['start_date'] ?? date('Y-m-01')));
        $endDate = trim((string) ($_GET['end_date'] ?? date('Y-m-d')));
        $year = (int) ($_GET['year'] ?? date('Y'));
        $paymentMethod = trim((string) ($_GET['payment_method'] ?? ''));
        $categoryId = (int) ($_GET['category_id'] ?? 0);

        $reportData = $this->buildReportPayload($reportType, $startDate, $endDate, $year, $paymentMethod, $categoryId);

        $this->render('reports/index', [
            'reportType'    => $reportType,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'year'          => $year,
            'paymentMethod' => $paymentMethod,
            'categoryId'    => $categoryId,
            'reportData'    => $reportData,
            'categories'    => (new CategoryModel())->getAll(),
            'users'         => (new UserModel())->getAll(),
        ], 'Laporan Penjualan, Stok & Laba');
    }

    public function export(): void
    {
        Auth::requireRole('admin');

        $reportType = trim((string) ($_GET['type'] ?? 'daily'));
        $format = strtolower(trim((string) ($_GET['format'] ?? 'excel')));
        $startDate = trim((string) ($_GET['start_date'] ?? date('Y-m-01')));
        $endDate = trim((string) ($_GET['end_date'] ?? date('Y-m-d')));
        $year = (int) ($_GET['year'] ?? date('Y'));
        $paymentMethod = trim((string) ($_GET['payment_method'] ?? ''));
        $categoryId = (int) ($_GET['category_id'] ?? 0);

        $payload = $this->buildReportPayload($reportType, $startDate, $endDate, $year, $paymentMethod, $categoryId);
        $store = $this->settingModel->getSettings();

        $filename = 'Laporan_' . ucfirst($reportType) . '_' . date('Ymd_His');

        if ($format === 'pdf') {
            Exporter::toPdf(
                $filename,
                $payload['title'],
                $payload['subtitle'],
                $payload['headers'],
                $payload['export_rows'],
                $payload['summary'],
                $store
            );
        }

        Exporter::toExcel(
            $filename,
            $payload['title'],
            $payload['subtitle'],
            $payload['headers'],
            $payload['export_rows'],
            $payload['summary']
        );
    }

    private function buildReportPayload(
        string $type,
        string $startDate,
        string $endDate,
        int $year,
        string $paymentMethod,
        int $categoryId
    ): array {
        $periodeStr = 'Periode: ' . tanggal_indo($startDate) . ' s/d ' . tanggal_indo($endDate);

        switch ($type) {
            case 'daily':
                $rows = $this->saleModel->getDailySalesReport($startDate, $endDate);
                $totalTrx = 0;
                $totalRev = 0.0;
                $totalDisc = 0.0;
                $totalCost = 0.0;
                $totalProfit = 0.0;
                $exportRows = [];

                foreach ($rows as $idx => $r) {
                    $totalTrx += (int) $r['total_transactions'];
                    $totalRev += (float) $r['total_revenue'];
                    $totalDisc += (float) $r['total_discount'];
                    $totalCost += (float) $r['total_cost'];
                    $totalProfit += (float) $r['total_profit'];

                    $exportRows[] = [
                        $idx + 1,
                        tanggal_indo($r['sale_date']),
                        (int) $r['total_transactions'],
                        rupiah($r['total_subtotal']),
                        rupiah($r['total_discount']),
                        rupiah($r['total_revenue']),
                        rupiah($r['total_cost']),
                        rupiah($r['total_profit']),
                    ];
                }

                return [
                    'title'       => 'Laporan Penjualan Harian',
                    'subtitle'    => $periodeStr,
                    'headers'     => ['No', 'Tanggal', 'Jml Transaksi', 'Subtotal', 'Diskon', 'Omzet Bersih', 'Total HPP (Modal)', 'Laba Kotor'],
                    'raw_rows'    => $rows,
                    'export_rows' => $exportRows,
                    'summary'     => [
                        'Total Hari Transaksi' => count($rows) . ' Hari',
                        'Total Transaksi'      => $totalTrx . ' Transaksi',
                        'Total Diskon'         => rupiah($totalDisc),
                        'Total Omzet Penjualan'=> rupiah($totalRev),
                        'Total Modal (HPP)'    => rupiah($totalCost),
                        'Total Laba Kotor'     => rupiah($totalProfit),
                    ],
                ];

            case 'monthly':
                $rows = $this->saleModel->getMonthlySalesReport($year);
                $totalTrx = 0;
                $totalRev = 0.0;
                $totalCost = 0.0;
                $totalProfit = 0.0;
                $exportRows = [];

                $namaBulan = [
                    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                ];

                foreach ($rows as $idx => $r) {
                    $parts = explode('-', (string) $r['sale_month']);
                    $mCode = $parts[1] ?? '01';
                    $mLabel = ($namaBulan[$mCode] ?? $r['sale_month']) . ' ' . ($parts[0] ?? $year);

                    $totalTrx += (int) $r['total_transactions'];
                    $totalRev += (float) $r['total_revenue'];
                    $totalCost += (float) $r['total_cost'];
                    $totalProfit += (float) $r['total_profit'];

                    $exportRows[] = [
                        $idx + 1,
                        $mLabel,
                        (int) $r['total_transactions'],
                        rupiah($r['total_subtotal']),
                        rupiah($r['total_discount']),
                        rupiah($r['total_revenue']),
                        rupiah($r['total_cost']),
                        rupiah($r['total_profit']),
                    ];
                }

                return [
                    'title'       => 'Laporan Penjualan Bulanan',
                    'subtitle'    => 'Tahun Buku: ' . $year,
                    'headers'     => ['No', 'Bulan', 'Jml Transaksi', 'Subtotal', 'Diskon', 'Omzet Bersih', 'Total HPP (Modal)', 'Laba Kotor'],
                    'raw_rows'    => $rows,
                    'export_rows' => $exportRows,
                    'summary'     => [
                        'Tahun Laporan'         => (string) $year,
                        'Total Transaksi'       => $totalTrx . ' Transaksi',
                        'Total Omzet Tahunan'   => rupiah($totalRev),
                        'Total HPP Tahunan'     => rupiah($totalCost),
                        'Total Laba Kotor'      => rupiah($totalProfit),
                    ],
                ];

            case 'range':
            case 'transactions':
                $rows = $this->saleModel->getTransactionsReport([
                    'start_date'     => $startDate,
                    'end_date'       => $endDate,
                    'payment_method' => $paymentMethod,
                ]);
                $totalRev = 0.0;
                $totalDisc = 0.0;
                $totalProfit = 0.0;
                $exportRows = [];

                foreach ($rows as $idx => $r) {
                    $totalRev += (float) $r['grand_total'];
                    $totalDisc += (float) $r['discount_amount'];
                    $totalProfit += (float) $r['gross_profit'];

                    $exportRows[] = [
                        $idx + 1,
                        $r['invoice_no'],
                        tanggal_indo($r['transaction_date'], true),
                        $r['customer_name'],
                        $r['cashier_name'],
                        strtoupper($r['payment_method']),
                        format_qty($r['total_items']),
                        rupiah($r['discount_amount']),
                        rupiah($r['grand_total']),
                        rupiah($r['gross_profit']),
                    ];
                }

                $title = $type === 'range'
                    ? 'Laporan Penjualan Berdasarkan Rentang Tanggal'
                    : 'Laporan Rincian Transaksi Penjualan';

                return [
                    'title'       => $title,
                    'subtitle'    => $periodeStr . ($paymentMethod ? ' | Metode: ' . strtoupper($paymentMethod) : ''),
                    'headers'     => ['No', 'No. Invoice', 'Waktu Transaksi', 'Pelanggan', 'Kasir', 'Metode Bayar', 'Item', 'Diskon', 'Grand Total', 'Laba Kotor'],
                    'raw_rows'    => $rows,
                    'export_rows' => $exportRows,
                    'summary'     => [
                        'Jumlah Transaksi'    => count($rows) . ' Transaksi',
                        'Total Potongan Diskon'=> rupiah($totalDisc),
                        'Total Omzet Penjualan'=> rupiah($totalRev),
                        'Total Laba Kotor'    => rupiah($totalProfit),
                    ],
                ];

            case 'bestseller':
                $rows = $this->saleModel->getBestSellingProductsReport($startDate, $endDate);
                $totalQty = 0.0;
                $totalRev = 0.0;
                $totalProfit = 0.0;
                $exportRows = [];

                foreach ($rows as $idx => $r) {
                    $totalQty += (float) $r['total_qty_sold'];
                    $totalRev += (float) $r['total_revenue'];
                    $totalProfit += (float) $r['total_profit'];

                    $exportRows[] = [
                        $idx + 1,
                        $r['product_sku'],
                        $r['product_name'],
                        $r['category_name'] ?? '-',
                        format_qty($r['total_qty_sold']) . ' ' . $r['unit'],
                        (int) $r['transaction_count'] . 'x',
                        rupiah($r['total_revenue']),
                        rupiah($r['total_cost']),
                        rupiah($r['total_profit']),
                    ];
                }

                return [
                    'title'       => 'Laporan Produk Terlaris (Best Selling Products)',
                    'subtitle'    => $periodeStr,
                    'headers'     => ['Peringkat', 'SKU', 'Nama Produk', 'Kategori', 'Total Terjual', 'Frekuensi Trx', 'Total Penjualan', 'Total Modal', 'Kontribusi Laba'],
                    'raw_rows'    => $rows,
                    'export_rows' => $exportRows,
                    'summary'     => [
                        'Jumlah Produk Terjual' => count($rows) . ' Jenis Produk',
                        'Total Kuantitas Item'  => format_qty($totalQty) . ' Unit',
                        'Total Nilai Penjualan' => rupiah($totalRev),
                        'Total Laba Produk'     => rupiah($totalProfit),
                    ],
                ];

            case 'inventory':
                $rows = $this->saleModel->getInventoryReport($categoryId ?: null);
                $totalAssetCost = 0.0;
                $totalRetailVal = 0.0;
                $lowCount = 0;
                $exportRows = [];

                foreach ($rows as $idx => $r) {
                    $totalAssetCost += (float) $r['stock_cost_value'];
                    $totalRetailVal += (float) $r['stock_retail_value'];
                    $isLow = (float) $r['stock'] <= (float) $r['min_stock'];
                    if ($isLow) {
                        $lowCount++;
                    }

                    $exportRows[] = [
                        $idx + 1,
                        $r['sku'],
                        $r['name'],
                        $r['category_name'],
                        format_qty($r['stock']) . ' ' . $r['unit'],
                        format_qty($r['min_stock']) . ' ' . $r['unit'],
                        $isLow ? 'Stok Menipis' : 'Aman',
                        rupiah($r['cost_price']),
                        rupiah($r['selling_price']),
                        rupiah($r['stock_cost_value']),
                        rupiah($r['stock_retail_value']),
                    ];
                }

                return [
                    'title'       => 'Laporan Persediaan Barang & Valuasi Stok',
                    'subtitle'    => 'Posisi Stok Per: ' . tanggal_indo(date('Y-m-d'), true),
                    'headers'     => ['No', 'SKU', 'Nama Produk', 'Kategori', 'Stok Saat Ini', 'Batas Min', 'Status', 'Harga Modal', 'Harga Jual', 'Nilai Aset (Modal)', 'Estimasi Nilai Jual'],
                    'raw_rows'    => $rows,
                    'export_rows' => $exportRows,
                    'summary'     => [
                        'Total Jenis Produk'        => count($rows) . ' Produk',
                        'Produk Stok Menipis/Habis' => $lowCount . ' Produk',
                        'Total Nilai Aset Modal'    => rupiah($totalAssetCost),
                        'Total Estimasi Nilai Jual' => rupiah($totalRetailVal),
                        'Potensi Margin Kotor Stok' => rupiah($totalRetailVal - $totalAssetCost),
                    ],
                ];

            case 'profit':
            default:
                $rows = $this->saleModel->getBestSellingProductsReport($startDate, $endDate);
                $salesSummary = $this->saleModel->getDailySalesReport($startDate, $endDate);

                $totalSubtotal = 0.0;
                $totalDiscount = 0.0;
                $totalRevenue = 0.0;
                $totalCost = 0.0;
                $totalGrossProfit = 0.0;

                foreach ($salesSummary as $sRow) {
                    $totalSubtotal += (float) $sRow['total_subtotal'];
                    $totalDiscount += (float) $sRow['total_discount'];
                    $totalRevenue += (float) $sRow['total_revenue'];
                    $totalCost += (float) $sRow['total_cost'];
                    $totalGrossProfit += (float) $sRow['total_profit'];
                }

                $exportRows = [];
                foreach ($rows as $idx => $r) {
                    $rev = (float) $r['total_revenue'];
                    $prof = (float) $r['total_profit'];
                    $marginPct = $rev > 0 ? round(($prof / $rev) * 100, 1) : 0.0;

                    $exportRows[] = [
                        $idx + 1,
                        $r['product_sku'],
                        $r['product_name'],
                        format_qty($r['total_qty_sold']) . ' ' . $r['unit'],
                        rupiah($r['total_revenue']),
                        rupiah($r['total_cost']),
                        rupiah($r['total_profit']),
                        $marginPct . '%',
                    ];
                }

                $overallMargin = $totalRevenue > 0 ? round(($totalGrossProfit / $totalRevenue) * 100, 2) : 0.0;

                return [
                    'title'       => 'Laporan Keuntungan Kotor (Gross Profit)',
                    'subtitle'    => $periodeStr,
                    'headers'     => ['No', 'SKU', 'Nama Produk', 'Qty Terjual', 'Total Penjualan (Harga Jual)', 'Total Modal (Harga Pokok)', 'Laba Kotor Produk', 'Margin (%)'],
                    'raw_rows'    => $rows,
                    'export_rows' => $exportRows,
                    'summary'     => [
                        'Penjualan Kotor (Subtotal)'       => rupiah($totalSubtotal),
                        'Total Diskon Transaksi'           => rupiah($totalDiscount),
                        'Penjualan Bersih (Net Sales)'     => rupiah($totalRevenue),
                        'Harga Pokok Penjualan (Total HPP)'=> rupiah($totalCost),
                        'Keuntungan Kotor Bersih'          => rupiah($totalGrossProfit),
                        'Rasio Margin Keuntungan'          => $overallMargin . '%',
                    ],
                ];
        }
    }
}

