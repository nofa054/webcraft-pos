<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class SupplierModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "
            SELECT s.*,
                   COUNT(p.id) AS product_count
            FROM suppliers s
            LEFT JOIN products p ON p.supplier_id = s.id
        ";
        $params = [];
        if ($search !== '') {
            $sql .= " WHERE s.name LIKE :q OR s.code LIKE :q2 OR s.contact_person LIKE :q3 OR s.phone LIKE :q4 ";
            $like = '%' . trim($search) . '%';
            $params = ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        $sql .= " GROUP BY s.id ORDER BY s.name ASC ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function generateNextCode(): string
    {
        $stmt = $this->db->query("SELECT MAX(id) FROM suppliers");
        $next = ((int) $stmt->fetchColumn()) + 1;
        return sprintf('SUP-%03d', $next);
    }

    public function create(array $data): int
    {
        $code = trim($data['code'] ?? '');
        if ($code === '') {
            $code = $this->generateNextCode();
        }
        $stmt = $this->db->prepare("
            INSERT INTO suppliers (code, name, contact_person, phone, email, address)
            VALUES (:code, :name, :contact_person, :phone, :email, :address)
        ");
        $stmt->execute([
            'code'           => $code,
            'name'           => trim($data['name']),
            'contact_person' => trim($data['contact_person'] ?? '') ?: null,
            'phone'          => trim($data['phone'] ?? '') ?: null,
            'email'          => trim($data['email'] ?? '') ?: null,
            'address'        => trim($data['address'] ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE suppliers
            SET name = :name,
                contact_person = :contact_person,
                phone = :phone,
                email = :email,
                address = :address,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'name'           => trim($data['name']),
            'contact_person' => trim($data['contact_person'] ?? '') ?: null,
            'phone'          => trim($data['phone'] ?? '') ?: null,
            'email'          => trim($data['email'] ?? '') ?: null,
            'address'        => trim($data['address'] ?? '') ?: null,
            'id'             => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

