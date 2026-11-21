<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ProductModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT p.*,
                   c.name AS category_name,
                   s.name AS supplier_name
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :q OR p.sku LIKE :q2 OR p.barcode LIKE :q3) ";
            $like = '%' . trim((string) $filters['search']) . '%';
            $params['q'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = :category_id ";
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low') {
                $sql .= " AND p.stock <= p.min_stock AND p.stock > 0 ";
            } elseif ($filters['stock_status'] === 'out') {
                $sql .= " AND p.stock <= 0 ";
            } elseif ($filters['stock_status'] === 'safe') {
                $sql .= " AND p.stock > p.min_stock ";
            } elseif ($filters['stock_status'] === 'alert') {
                $sql .= " AND p.stock <= p.min_stock ";
            }
        }

        if (isset($filters['only_active']) && $filters['only_active'] === true) {
            $sql .= " AND p.is_active = 1 ";
        }

        $sql .= " ORDER BY p.name ASC ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name AS category_name, s.name AS supplier_name
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            WHERE p.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBySku(string $sku, ?int $excludeId = null): ?array
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE LOWER(sku) = LOWER(:sku) AND id != :id LIMIT 1");
            $stmt->execute(['sku' => trim($sku), 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE LOWER(sku) = LOWER(:sku) LIMIT 1");
            $stmt->execute(['sku' => trim($sku)]);
        }
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByBarcodeOrSku(string $code): ?array
    {
        $clean = trim($code);
        if ($clean === '') {
            return null;
        }
        $stmt = $this->db->prepare("
            SELECT p.*, c.name AS category_name
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            WHERE p.is_active = 1 AND (p.barcode = :code OR LOWER(p.sku) = LOWER(:code2))
            LIMIT 1
        ");
        $stmt->execute(['code' => $clean, 'code2' => $clean]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function generateNextSku(): string
    {
        $stmt = $this->db->query("SELECT MAX(id) FROM products");
        $next = ((int) $stmt->fetchColumn()) + 1;
        return sprintf('PRD-%04d', $next);
    }

    public function getLowStockProducts(int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name AS category_name, s.name AS supplier_name
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            WHERE p.is_active = 1 AND p.stock <= p.min_stock
            ORDER BY (p.stock - p.min_stock) ASC, p.stock ASC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getLowStockCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock <= min_stock");
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data, int $userId): int
    {
        $this->db->beginTransaction();
        try {
            $sku = trim($data['sku'] ?? '');
            if ($sku === '') {
                $sku = $this->generateNextSku();
            }

            $initialStock = round((float) ($data['stock'] ?? 0), 2);
            $costPrice = round((float) ($data['cost_price'] ?? 0), 2);

            $stmt = $this->db->prepare("
                INSERT INTO products (
                    category_id, supplier_id, sku, barcode, name, unit,
                    cost_price, selling_price, stock, min_stock, is_active
                ) VALUES (
                    :category_id, :supplier_id, :sku, :barcode, :name, :unit,
                    :cost_price, :selling_price, :stock, :min_stock, :is_active
                )
            ");

            $stmt->execute([
                'category_id'   => (int) $data['category_id'],
                'supplier_id'   => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null,
                'sku'           => $sku,
                'barcode'       => trim($data['barcode'] ?? '') ?: null,
                'name'          => trim($data['name']),
                'unit'          => trim($data['unit'] ?: 'pcs'),
                'cost_price'    => $costPrice,
                'selling_price' => round((float) ($data['selling_price'] ?? 0), 2),
                'stock'         => $initialStock,
                'min_stock'     => round((float) ($data['min_stock'] ?? 5), 2),
                'is_active'     => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            ]);

            $productId = (int) $this->db->lastInsertId();

            if ($initialStock > 0) {
                $stmtMove = $this->db->prepare("
                    INSERT INTO stock_movements (
                        product_id, user_id, supplier_id, movement_type, reference_no,
                        qty_change, stock_before, stock_after, unit_cost, notes
                    ) VALUES (
                        :product_id, :user_id, :supplier_id, 'in', :reference_no,
                        :qty_change, 0, :stock_after, :unit_cost, :notes
                    )
                ");
                $stmtMove->execute([
                    'product_id'   => $productId,
                    'user_id'      => $userId,
                    'supplier_id'  => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null,
                    'reference_no' => 'INIT-' . $sku,
                    'qty_change'   => $initialStock,
                    'stock_after'  => $initialStock,
                    'unit_cost'    => $costPrice,
                    'notes'        => 'Saldo stok awal saat pendaftaran produk baru',
                ]);
            }

            $this->db->commit();
            return $productId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE products
            SET category_id = :category_id,
                supplier_id = :supplier_id,
                sku = :sku,
                barcode = :barcode,
                name = :name,
                unit = :unit,
                cost_price = :cost_price,
                selling_price = :selling_price,
                min_stock = :min_stock,
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        return $stmt->execute([
            'category_id'   => (int) $data['category_id'],
            'supplier_id'   => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            'sku'           => trim($data['sku']),
            'barcode'       => trim($data['barcode'] ?? '') ?: null,
            'name'          => trim($data['name']),
            'unit'          => trim($data['unit'] ?: 'pcs'),
            'cost_price'    => round((float) ($data['cost_price'] ?? 0), 2),
            'selling_price' => round((float) ($data['selling_price'] ?? 0), 2),
            'min_stock'     => round((float) ($data['min_stock'] ?? 5), 2),
            'is_active'     => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            'id'            => $id,
        ]);
    }

    public function hasSalesHistory(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = :id");
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        if ($this->hasSalesHistory($id)) {
            return false;
        }
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

