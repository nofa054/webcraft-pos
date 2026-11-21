<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

class StockModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getMovements(array $filters = [], int $limit = 200): array
    {
        $sql = "
            SELECT sm.*,
                   p.sku AS product_sku,
                   p.name AS product_name,
                   p.unit AS product_unit,
                   u.name AS user_name,
                   s.name AS supplier_name
            FROM stock_movements sm
            INNER JOIN products p ON p.id = sm.product_id
            INNER JOIN users u ON u.id = sm.user_id
            LEFT JOIN suppliers s ON s.id = sm.supplier_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['movement_type'])) {
            $sql .= " AND sm.movement_type = :movement_type ";
            $params['movement_type'] = $filters['movement_type'];
        }

        if (!empty($filters['product_id'])) {
            $sql .= " AND sm.product_id = :product_id ";
            $params['product_id'] = (int) $filters['product_id'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(sm.created_at) >= :start_date ";
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(sm.created_at) <= :end_date ";
            $params['end_date'] = $filters['end_date'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :q OR p.sku LIKE :q2 OR sm.reference_no LIKE :q3 OR sm.notes LIKE :q4) ";
            $like = '%' . trim((string) $filters['search']) . '%';
            $params['q'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }

        $sql .= " ORDER BY sm.id DESC LIMIT :lim ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Proses mutasi stok secara konsisten dengan transaksi database:
     * - in: Stok Masuk (+qty)
     * - out: Stok Keluar (-qty)
     * - adjustment: Penyesuaian Stok Opname (set stok fisik aktual)
     */
    public function recordMovement(array $data, int $userId): bool
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $type = (string) ($data['movement_type'] ?? '');
        $inputQty = round((float) ($data['qty'] ?? 0), 2);
        $supplierId = !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;
        $unitCost = isset($data['unit_cost']) && $data['unit_cost'] !== '' ? round((float) $data['unit_cost'], 2) : null;
        $updateCostPrice = !empty($data['update_cost_price']);
        $referenceNo = trim($data['reference_no'] ?? '');
        $notes = trim($data['notes'] ?? '');

        if (!in_array($type, ['in', 'out', 'adjustment'], true)) {
            throw new RuntimeException('Jenis mutasi stok tidak valid.');
        }

        $this->db->beginTransaction();
        try {
            $stmtProd = $this->db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
            $stmtProd->execute(['id' => $productId]);
            $product = $stmtProd->fetch();

            if (!$product) {
                throw new RuntimeException('Data produk tidak ditemukan.');
            }

            $stockBefore = round((float) $product['stock'], 2);

            if ($type === 'in') {
                if ($inputQty <= 0) {
                    throw new RuntimeException('Jumlah stok masuk harus lebih besar dari 0.');
                }
                $qtyChange = $inputQty;
                $stockAfter = round($stockBefore + $inputQty, 2);
                if ($referenceNo === '') {
                    $referenceNo = 'IN-' . date('YmdHis');
                }
            } elseif ($type === 'out') {
                if ($inputQty <= 0) {
                    throw new RuntimeException('Jumlah stok keluar harus lebih besar dari 0.');
                }
                if ($inputQty > $stockBefore) {
                    throw new RuntimeException(
                        sprintf('Stok produk "%s" tidak mencukupi (Tersedia: %s %s).', $product['name'], format_qty($stockBefore), $product['unit'])
                    );
                }
                $qtyChange = -$inputQty;
                $stockAfter = round($stockBefore - $inputQty, 2);
                if ($referenceNo === '') {
                    $referenceNo = 'OUT-' . date('YmdHis');
                }
            } else {
                // adjustment (inputQty adalah jumlah stok fisik aktual hasil stock opname)
                if ($inputQty < 0) {
                    throw new RuntimeException('Stok aktual hasil penyesuaian tidak boleh bernilai negatif.');
                }
                $stockAfter = $inputQty;
                $qtyChange = round($stockAfter - $stockBefore, 2);
                if ($qtyChange == 0.0) {
                    throw new RuntimeException('Stok fisik aktual sama dengan stok sistem saat ini (tidak ada selisih).');
                }
                if ($referenceNo === '') {
                    $referenceNo = 'ADJ-' . date('YmdHis');
                }
            }

            if ($type === 'in' && $updateCostPrice && $unitCost !== null && $unitCost > 0) {
                $updProd = $this->db->prepare("
                    UPDATE products
                    SET stock = :stock,
                        cost_price = :cost_price,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $updProd->execute([
                    'stock'      => $stockAfter,
                    'cost_price' => $unitCost,
                    'id'         => $productId,
                ]);
            } else {
                $updProd = $this->db->prepare("
                    UPDATE products
                    SET stock = :stock,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $updProd->execute([
                    'stock' => $stockAfter,
                    'id'    => $productId,
                ]);
            }

            $stmtMove = $this->db->prepare("
                INSERT INTO stock_movements (
                    product_id, user_id, supplier_id, movement_type, reference_no,
                    qty_change, stock_before, stock_after, unit_cost, notes, created_at
                ) VALUES (
                    :product_id, :user_id, :supplier_id, :movement_type, :reference_no,
                    :qty_change, :stock_before, :stock_after, :unit_cost, :notes, :created_at
                )
            ");

            $stmtMove->execute([
                'product_id'    => $productId,
                'user_id'       => $userId,
                'supplier_id'   => $supplierId,
                'movement_type' => $type,
                'reference_no'  => $referenceNo,
                'qty_change'    => $qtyChange,
                'stock_before'  => $stockBefore,
                'stock_after'   => $stockAfter,
                'unit_cost'     => $unitCost ?? (float) $product['cost_price'],
                'notes'         => $notes ?: null,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}

