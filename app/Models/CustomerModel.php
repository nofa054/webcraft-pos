<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class CustomerModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "
            SELECT c.*,
                   COUNT(s.id) AS total_transactions,
                   COALESCE(SUM(s.grand_total), 0) AS total_spent
            FROM customers c
            LEFT JOIN sales s ON s.customer_id = c.id
        ";
        $params = [];
        if ($search !== '') {
            $sql .= " WHERE c.name LIKE :q OR c.code LIKE :q2 OR c.phone LIKE :q3 OR c.address LIKE :q4 ";
            $like = '%' . trim($search) . '%';
            $params = ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        $sql .= " GROUP BY c.id ORDER BY c.id ASC ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function generateNextCode(): string
    {
        $stmt = $this->db->query("SELECT MAX(id) FROM customers");
        $next = ((int) $stmt->fetchColumn()) + 1;
        return sprintf('CUST-%03d', $next);
    }

    public function create(array $data): int
    {
        $code = trim($data['code'] ?? '');
        if ($code === '') {
            $code = $this->generateNextCode();
        }
        $stmt = $this->db->prepare("
            INSERT INTO customers (code, name, phone, email, address, notes)
            VALUES (:code, :name, :phone, :email, :address, :notes)
        ");
        $stmt->execute([
            'code'    => $code,
            'name'    => trim($data['name']),
            'phone'   => trim($data['phone'] ?? '') ?: null,
            'email'   => trim($data['email'] ?? '') ?: null,
            'address' => trim($data['address'] ?? '') ?: null,
            'notes'   => trim($data['notes'] ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE customers
            SET name = :name,
                phone = :phone,
                email = :email,
                address = :address,
                notes = :notes,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'name'    => trim($data['name']),
            'phone'   => trim($data['phone'] ?? '') ?: null,
            'email'   => trim($data['email'] ?? '') ?: null,
            'address' => trim($data['address'] ?? '') ?: null,
            'notes'   => trim($data['notes'] ?? '') ?: null,
            'id'      => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        if ($id === 1) {
            return false; // Jangan hapus Pelanggan Umum default
        }
        $stmt = $this->db->prepare("DELETE FROM customers WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

