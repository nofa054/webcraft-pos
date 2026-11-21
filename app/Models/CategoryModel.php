<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class CategoryModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(string $search = ''): array
    {
        if ($search !== '') {
            $stmt = $this->db->prepare("
                SELECT c.*,
                       COUNT(p.id) AS product_count,
                       COALESCE(SUM(p.stock), 0) AS total_stock
                FROM categories c
                LEFT JOIN products p ON p.category_id = c.id
                WHERE c.name LIKE :q OR c.description LIKE :q2
                GROUP BY c.id
                ORDER BY c.name ASC
            ");
            $like = '%' . trim($search) . '%';
            $stmt->execute(['q' => $like, 'q2' => $like]);
            return $stmt->fetchAll();
        }

        $stmt = $this->db->query("
            SELECT c.*,
                   COUNT(p.id) AS product_count,
                   COALESCE(SUM(p.stock), 0) AS total_stock
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByName(string $name, ?int $excludeId = null): ?array
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE LOWER(name) = LOWER(:name) AND id != :id LIMIT 1");
            $stmt->execute(['name' => trim($name), 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE LOWER(name) = LOWER(:name) LIMIT 1");
            $stmt->execute(['name' => trim($name)]);
        }
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO categories (name, description)
            VALUES (:name, :description)
        ");
        $stmt->execute([
            'name'        => trim($data['name']),
            'description' => trim($data['description'] ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE categories
            SET name = :name,
                description = :description,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'name'        => trim($data['name']),
            'description' => trim($data['description'] ?? '') ?: null,
            'id'          => $id,
        ]);
    }

    public function getProductCount(int $id): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id");
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        if ($this->getProductCount($id) > 0) {
            return false;
        }
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

