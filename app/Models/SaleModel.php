<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

class SaleModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Generate nomor invoice unik berformat INV-YYYYMMDD-XXXX
     */
    public function generateInvoiceNo(): string
    {
        $todayPrefix = 'INV-' . date('Ymd') . '-';
        $stmt = $this->db->prepare("SELECT invoice_no FROM sales WHERE invoice_no LIKE :prefix ORDER BY id DESC LIMIT 1");
        $stmt->execute(['prefix' => $todayPrefix . '%']);
        $last = $stmt->fetchColumn();

        $nextSeq = 1;
        if ($last) {
            $parts = explode('-', (string) $last);
            $lastNum = (int) end($parts);
            $nextSeq = $lastNum + 1;
        }

        return sprintf('%s%04d', $todayPrefix, $nextSeq);
    }

    /**
     * Simpan transaksi penjualan baru secara atomik beserta pengurangan stok & pencatatan riwayat stok
     */
    public function createTransaction(array $payload, int $userId): array
    {
        $items = $payload['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            throw new RuntimeException('Keranjang belanja masih kosong. Pilih minimal 1 produk.');
        }

        $customerId = !empty($payload['customer_id']) ? (int) $payload['customer_id'] : 1;
        $discountType = in_array($payload['discount_type'] ?? 'nominal', ['nominal', 'percent'], true)
            ? $payload['discount_type']
            : 'nominal';
        $discountValue = max(0.0, round((float) ($payload['discount_value'] ?? 0), 2));
        $paymentMethod = in_array($payload['payment_method'] ?? 'tunai', ['tunai', 'transfer', 'qris'], true)
            ? $payload['payment_method']
            : 'tunai';
        $paymentReference = trim((string) ($payload['payment_reference'] ?? '')) ?: null;
        $amountPaid = round((float) ($payload['amount_paid'] ?? 0), 2);
        $notes = trim((string) ($payload['notes'] ?? '')) ?: null;

        $this->db->beginTransaction();
        try {
            $invoiceNo = $this->generateInvoiceNo();
            $now = date('Y-m-d H:i:s');

            $subtotal = 0.0;
            $totalCost = 0.0;
            $preparedItems = [];

            $stmtGetProd = $this->db->prepare("SELECT * FROM products WHERE id = :id AND is_active = 1 LIMIT 1");

            foreach ($items as $cartItem) {
                $productId = (int) ($cartItem['product_id'] ?? 0);
                $qty = round((float) ($cartItem['qty'] ?? 0), 2);

                if ($productId <= 0 || $qty <= 0) {
                    throw new RuntimeException('Data item produk atau jumlah beli tidak valid.');
                }

                $stmtGetProd->execute(['id' => $productId]);
                $product = $stmtGetProd->fetch();

                if (!$product) {
                    throw new RuntimeException('Salah satu produk tidak ditemukan atau sudah dinonaktifkan.');
                }

                $currentStock = round((float) $product['stock'], 2);
                if ($qty > $currentStock) {
                    throw new RuntimeException(
                        sprintf(
                            'Stok "%s" tidak mencukupi! Tersedia: %s %s, diminta: %s %s.',
                            $product['name'],
                            format_qty($currentStock),
                            $product['unit'],
                            format_qty($qty),
                            $product['unit']
                        )
                    );
                }

                $costPrice = round((float) $product['cost_price'], 2);
                $sellingPrice = round((float) $product['selling_price'], 2);
                $lineSubtotal = round($qty * $sellingPrice, 2);
                $lineCost = round($qty * $costPrice, 2);
                $lineProfit = round($lineSubtotal - $lineCost, 2);

                $subtotal += $lineSubtotal;
                $totalCost += $lineCost;

                $preparedItems[] = [
                    'product_id'    => $productId,
                    'product_sku'   => $product['sku'],
                    'product_name'  => $product['name'],
                    'unit'          => $product['unit'],
                    'qty'           => $qty,
                    'cost_price'    => $costPrice,
                    'selling_price' => $sellingPrice,
                    'subtotal'      => $lineSubtotal,
                    'profit'        => $lineProfit,
                    'stock_before'  => $currentStock,
                    'stock_after'   => round($currentStock - $qty, 2),
                ];
            }

            if ($discountType === 'percent') {
                $discountValue = min(100.0, $discountValue);
                $discountAmount = round($subtotal * ($discountValue / 100.0), 2);
            } else {
                $discountAmount = min($subtotal, $discountValue);
            }

            $grandTotal = round(max(0.0, $subtotal - $discountAmount), 2);
            $grossProfit = round($grandTotal - $totalCost, 2);

            if ($paymentMethod !== 'tunai' && $amountPaid < $grandTotal) {
                $amountPaid = $grandTotal;
            }

            if ($amountPaid < $grandTotal) {
                throw new RuntimeException(
                    sprintf(
                        'Nominal pembayaran (%s) kurang dari total tagihan (%s).',
                        rupiah($amountPaid),
                        rupiah($grandTotal)
                    )
                );
            }

            $changeAmount = round($amountPaid - $grandTotal, 2);

            $stmtSale = $this->db->prepare("
                INSERT INTO sales (
                    invoice_no, user_id, customer_id, transaction_date,
                    subtotal, discount_type, discount_value, discount_amount,
                    grand_total, total_cost, gross_profit,
                    payment_method, payment_reference, amount_paid, change_amount,
                    notes, created_at
                ) VALUES (
                    :invoice_no, :user_id, :customer_id, :transaction_date,
                    :subtotal, :discount_type, :discount_value, :discount_amount,
                    :grand_total, :total_cost, :gross_profit,
                    :payment_method, :payment_reference, :amount_paid, :change_amount,
                    :notes, :created_at
                )
            ");

            $stmtSale->execute([
                'invoice_no'        => $invoiceNo,
                'user_id'           => $userId,
                'customer_id'       => $customerId ?: null,
                'transaction_date'  => $now,
                'subtotal'          => $subtotal,
                'discount_type'     => $discountType,
                'discount_value'    => $discountValue,
                'discount_amount'   => $discountAmount,
                'grand_total'       => $grandTotal,
                'total_cost'        => $totalCost,
                'gross_profit'      => $grossProfit,
                'payment_method'    => $paymentMethod,
                'payment_reference' => $paymentReference,
                'amount_paid'       => $amountPaid,
                'change_amount'     => $changeAmount,
                'notes'             => $notes,
                'created_at'        => $now,
            ]);

            $saleId = (int) $this->db->lastInsertId();

            $stmtInsertItem = $this->db->prepare("
                INSERT INTO sale_items (
                    sale_id, product_id, product_sku, product_name, unit,
                    qty, cost_price, selling_price, subtotal, profit
                ) VALUES (
                    :sale_id, :product_id, :product_sku, :product_name, :unit,
                    :qty, :cost_price, :selling_price, :subtotal, :profit
                )
            ");

            $stmtUpdateStock = $this->db->prepare("
                UPDATE products
                SET stock = :stock_after, updated_at = CURRENT_TIMESTAMP
                WHERE id = :product_id
            ");

            $stmtInsertMovement = $this->db->prepare("
                INSERT INTO stock_movements (
                    product_id, user_id, supplier_id, movement_type, reference_no,
                    qty_change, stock_before, stock_after, unit_cost, notes, created_at
                ) VALUES (
                    :product_id, :user_id, NULL, 'sale', :reference_no,
                    :qty_change, :stock_before, :stock_after, :unit_cost, :notes, :created_at
                )
            ");

            foreach ($preparedItems as $item) {
                $stmtInsertItem->execute([
                    'sale_id'       => $saleId,
                    'product_id'    => $item['product_id'],
                    'product_sku'   => $item['product_sku'],
                    'product_name'  => $item['product_name'],
                    'unit'          => $item['unit'],
                    'qty'           => $item['qty'],
                    'cost_price'    => $item['cost_price'],
                    'selling_price' => $item['selling_price'],
                    'subtotal'      => $item['subtotal'],
                    'profit'        => $item['profit'],
                ]);

                $stmtUpdateStock->execute([
                    'stock_after' => $item['stock_after'],
                    'product_id'  => $item['product_id'],
                ]);

                $stmtInsertMovement->execute([
                    'product_id'   => $item['product_id'],
                    'user_id'      => $userId,
                    'reference_no' => $invoiceNo,
                    'qty_change'   => -$item['qty'],
                    'stock_before' => $item['stock_before'],
                    'stock_after'  => $item['stock_after'],
                    'unit_cost'    => $item['cost_price'],
                    'notes'        => 'Penjualan Kasir #' . $invoiceNo,
                    'created_at'   => $now,
                ]);
            }

            $this->db->commit();

            return [
                'id'            => $saleId,
                'invoice_no'    => $invoiceNo,
                'grand_total'   => $grandTotal,
                'amount_paid'   => $amountPaid,
                'change_amount' => $changeAmount,
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function findByIdWithItems(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*,
                   u.name AS cashier_name,
                   u.username AS cashier_username,
                   COALESCE(c.name, 'Pelanggan Umum') AS customer_name,
                   c.phone AS customer_phone,
                   c.address AS customer_address
            FROM sales s
            INNER JOIN users u ON u.id = s.user_id
            LEFT JOIN customers c ON c.id = s.customer_id
            WHERE s.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $sale = $stmt->fetch();

        if (!$sale) {
            return null;
        }

        $stmtItems = $this->db->prepare("SELECT * FROM sale_items WHERE sale_id = :sale_id ORDER BY id ASC");
        $stmtItems->execute(['sale_id' => $id]);
        $sale['items'] = $stmtItems->fetchAll();

        return $sale;
    }

    public function getDashboardStats(): array
    {
        $today = date('Y-m-d');
        $monthPrefix = date('Y-m');

        // Ringkasan Hari Ini
        $stmtToday = $this->db->prepare("
            SELECT COUNT(*) AS total_trx,
                   COALESCE(SUM(grand_total), 0) AS total_omzet,
                   COALESCE(SUM(gross_profit), 0) AS total_profit
            FROM sales
            WHERE DATE(transaction_date) = :today
        ");
        $stmtToday->execute(['today' => $today]);
        $todayRow = $stmtToday->fetch() ?: ['total_trx' => 0, 'total_omzet' => 0, 'total_profit' => 0];

        // Ringkasan Bulan Ini
        $stmtMonth = $this->db->prepare("
            SELECT COUNT(*) AS total_trx,
                   COALESCE(SUM(grand_total), 0) AS total_omzet,
                   COALESCE(SUM(gross_profit), 0) AS total_profit
            FROM sales
            WHERE SUBSTR(transaction_date, 1, 7) = :month_prefix
        ");
        $stmtMonth->execute(['month_prefix' => $monthPrefix]);
        $monthRow = $stmtMonth->fetch() ?: ['total_trx' => 0, 'total_omzet' => 0, 'total_profit' => 0];

        // Jumlah produk & stok menipis
        $totalProducts = (int) $this->db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
        $lowStockCount = (int) $this->db->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock <= min_stock")->fetchColumn();

        // Grafik Penjualan 7 Hari Terakhir
        $chartLabels = [];
        $chartRevenue = [];
        $chartProfit = [];

        $stmtDay = $this->db->prepare("
            SELECT COALESCE(SUM(grand_total), 0) AS rev,
                   COALESCE(SUM(gross_profit), 0) AS prof
            FROM sales
            WHERE DATE(transaction_date) = :dt
        ");

        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $stmtDay->execute(['dt' => $d]);
            $res = $stmtDay->fetch();
            $chartLabels[] = tanggal_indo($d);
            $chartRevenue[] = (float) ($res['rev'] ?? 0);
            $chartProfit[] = (float) ($res['prof'] ?? 0);
        }

        // Jika 7 hari kalender terakhir belum ada transaksi sama sekali namun ada riwayat transaksi sebelumnya,
        // tampilkan 7 tanggal transaksi terakhir agar grafik tetap informatif
        if (array_sum($chartRevenue) <= 0) {
            $stmtLastDays = $this->db->query("
                SELECT DATE(transaction_date) AS dt,
                       COALESCE(SUM(grand_total), 0) AS rev,
                       COALESCE(SUM(gross_profit), 0) AS prof
                FROM sales
                GROUP BY DATE(transaction_date)
                ORDER BY dt DESC
                LIMIT 7
            ");
            $lastRows = array_reverse($stmtLastDays->fetchAll());
            if (!empty($lastRows)) {
                $chartLabels = [];
                $chartRevenue = [];
                $chartProfit = [];
                foreach ($lastRows as $lr) {
                    $chartLabels[] = tanggal_indo($lr['dt']);
                    $chartRevenue[] = (float) $lr['rev'];
                    $chartProfit[] = (float) $lr['prof'];
                }
            }
        }

        // Distribusi Metode Pembayaran
        $stmtPay = $this->db->query("
            SELECT payment_method, COUNT(*) AS cnt, COALESCE(SUM(grand_total), 0) AS total
            FROM sales
            GROUP BY payment_method
        ");
        $paymentStats = [
            'tunai'    => 0,
            'transfer' => 0,
            'qris'     => 0,
        ];
        foreach ($stmtPay->fetchAll() as $pRow) {
            $pm = $pRow['payment_method'];
            if (isset($paymentStats[$pm])) {
                $paymentStats[$pm] = (float) $pRow['total'];
            }
        }

        // Transaksi Terbaru
        $stmtRecent = $this->db->query("
            SELECT s.*, u.name AS cashier_name, COALESCE(c.name, 'Pelanggan Umum') AS customer_name
            FROM sales s
            INNER JOIN users u ON u.id = s.user_id
            LEFT JOIN customers c ON c.id = s.customer_id
            ORDER BY s.id DESC
            LIMIT 7
        ");

        return [
            'today_revenue'      => (float) $todayRow['total_omzet'],
            'today_profit'       => (float) $todayRow['total_profit'],
            'today_transactions' => (int) $todayRow['total_trx'],
            'month_revenue'      => (float) $monthRow['total_omzet'],
            'month_profit'       => (float) $monthRow['total_profit'],
            'month_transactions' => (int) $monthRow['total_trx'],
            'total_products'     => $totalProducts,
            'low_stock_count'    => $lowStockCount,
            'chart_labels'       => $chartLabels,
            'chart_revenue'      => $chartRevenue,
            'chart_profit'       => $chartProfit,
            'payment_stats'      => $paymentStats,
            'recent_sales'       => $stmtRecent->fetchAll(),
        ];
    }

    /**
     * Laporan Transaksi Detail (dengan filter tanggal, metode bayar, kasir, pencarian)
     */
    public function getTransactionsReport(array $filters = []): array
    {
        $sql = "
            SELECT s.*,
                   u.name AS cashier_name,
                   COALESCE(c.name, 'Pelanggan Umum') AS customer_name,
                   (SELECT COALESCE(SUM(si.qty), 0) FROM sale_items si WHERE si.sale_id = s.id) AS total_items
            FROM sales s
            INNER JOIN users u ON u.id = s.user_id
            LEFT JOIN customers c ON c.id = s.customer_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(s.transaction_date) >= :start_date ";
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(s.transaction_date) <= :end_date ";
            $params['end_date'] = $filters['end_date'];
        }

        if (!empty($filters['payment_method'])) {
            $sql .= " AND s.payment_method = :payment_method ";
            $params['payment_method'] = $filters['payment_method'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND s.user_id = :user_id ";
            $params['user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (s.invoice_no LIKE :q OR c.name LIKE :q2 OR s.payment_reference LIKE :q3) ";
            $like = '%' . trim((string) $filters['search']) . '%';
            $params['q'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        $sql .= " ORDER BY s.transaction_date DESC, s.id DESC ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Laporan Rekap Penjualan Harian berdasarkan rentang tanggal
     */
    public function getDailySalesReport(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare("
            SELECT DATE(s.transaction_date) AS sale_date,
                   COUNT(s.id) AS total_transactions,
                   COALESCE(SUM(s.subtotal), 0) AS total_subtotal,
                   COALESCE(SUM(s.discount_amount), 0) AS total_discount,
                   COALESCE(SUM(s.grand_total), 0) AS total_revenue,
                   COALESCE(SUM(s.total_cost), 0) AS total_cost,
                   COALESCE(SUM(s.gross_profit), 0) AS total_profit
            FROM sales s
            WHERE DATE(s.transaction_date) >= :start_date
              AND DATE(s.transaction_date) <= :end_date
            GROUP BY DATE(s.transaction_date)
            ORDER BY sale_date DESC
        ");
        $stmt->execute([
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Laporan Rekap Penjualan Bulanan berdasarkan tahun
     */
    public function getMonthlySalesReport(int $year): array
    {
        $yearStr = (string) $year;
        $stmt = $this->db->prepare("
            SELECT SUBSTR(s.transaction_date, 1, 7) AS sale_month,
                   COUNT(s.id) AS total_transactions,
                   COALESCE(SUM(s.subtotal), 0) AS total_subtotal,
                   COALESCE(SUM(s.discount_amount), 0) AS total_discount,
                   COALESCE(SUM(s.grand_total), 0) AS total_revenue,
                   COALESCE(SUM(s.total_cost), 0) AS total_cost,
                   COALESCE(SUM(s.gross_profit), 0) AS total_profit
            FROM sales s
            WHERE SUBSTR(s.transaction_date, 1, 4) = :yr
            GROUP BY SUBSTR(s.transaction_date, 1, 7)
            ORDER BY sale_month ASC
        ");
        $stmt->execute(['yr' => $yearStr]);
        return $stmt->fetchAll();
    }

    /**
     * Laporan Produk Terlaris (Best Selling Products)
     */
    public function getBestSellingProductsReport(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare("
            SELECT si.product_id,
                   si.product_sku,
                   si.product_name,
                   si.unit,
                   c.name AS category_name,
                   COUNT(DISTINCT si.sale_id) AS transaction_count,
                   COALESCE(SUM(si.qty), 0) AS total_qty_sold,
                   COALESCE(SUM(si.subtotal), 0) AS total_revenue,
                   COALESCE(SUM(si.qty * si.cost_price), 0) AS total_cost,
                   COALESCE(SUM(si.profit), 0) AS total_profit
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id
            LEFT JOIN products p ON p.id = si.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE DATE(s.transaction_date) >= :start_date
              AND DATE(s.transaction_date) <= :end_date
            GROUP BY si.product_id, si.product_sku, si.product_name, si.unit, c.name
            ORDER BY total_qty_sold DESC, total_revenue DESC
        ");
        $stmt->execute([
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Laporan Persediaan Barang & Nilai Aset Stok
     */
    public function getInventoryReport(?int $categoryId = null): array
    {
        $sql = "
            SELECT p.*,
                   c.name AS category_name,
                   s.name AS supplier_name,
                   (p.stock * p.cost_price) AS stock_cost_value,
                   (p.stock * p.selling_price) AS stock_retail_value,
                   ((p.stock * p.selling_price) - (p.stock * p.cost_price)) AS potential_profit
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            WHERE 1=1
        ";
        $params = [];
        if ($categoryId !== null && $categoryId > 0) {
            $sql .= " AND p.category_id = :category_id ";
            $params['category_id'] = $categoryId;
        }
        $sql .= " ORDER BY c.name ASC, p.name ASC ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

