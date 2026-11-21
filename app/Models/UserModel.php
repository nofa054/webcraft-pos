<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class UserModel
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
                SELECT u.*,
                       (SELECT COUNT(*) FROM sales s WHERE s.user_id = u.id) AS total_transactions
                FROM users u
                WHERE u.name LIKE :q OR u.username LIKE :q2 OR u.email LIKE :q3
                ORDER BY u.id ASC
            ");
            $like = '%' . trim($search) . '%';
            $stmt->execute(['q' => $like, 'q2' => $like, 'q3' => $like]);
            return $stmt->fetchAll();
        }

        $stmt = $this->db->query("
            SELECT u.*,
                   (SELECT COUNT(*) FROM sales s WHERE s.user_id = u.id) AS total_transactions
            FROM users u
            ORDER BY u.id ASC
        ");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUsername(string $username, ?int $excludeId = null): ?array
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND id != :id LIMIT 1");
            $stmt->execute(['username' => trim($username), 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => trim($username)]);
        }
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (name, username, email, password_hash, role, is_active)
            VALUES (:name, :username, :email, :password_hash, :role, :is_active)
        ");
        $stmt->execute([
            'name'          => trim($data['name']),
            'username'      => trim($data['username']),
            'email'         => trim($data['email'] ?? '') ?: null,
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role'          => in_array($data['role'], ['admin', 'kasir'], true) ? $data['role'] : 'kasir',
            'is_active'     => isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (!empty($data['password'])) {
            $stmt = $this->db->prepare("
                UPDATE users
                SET name = :name,
                    username = :username,
                    email = :email,
                    password_hash = :password_hash,
                    role = :role,
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            return $stmt->execute([
                'name'          => trim($data['name']),
                'username'      => trim($data['username']),
                'email'         => trim($data['email'] ?? '') ?: null,
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role'          => in_array($data['role'], ['admin', 'kasir'], true) ? $data['role'] : 'kasir',
                'is_active'     => isset($data['is_active']) ? (int) $data['is_active'] : 1,
                'id'            => $id,
            ]);
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET name = :name,
                username = :username,
                email = :email,
                role = :role,
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'name'      => trim($data['name']),
            'username'  => trim($data['username']),
            'email'     => trim($data['email'] ?? '') ?: null,
            'role'      => in_array($data['role'], ['admin', 'kasir'], true) ? $data['role'] : 'kasir',
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            'id'        => $id,
        ]);
    }

    public function hasRelatedRecords(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM sales WHERE user_id = :id");
        $stmt->execute(['id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return true;
        }
        $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM stock_movements WHERE user_id = :id");
        $stmt2->execute(['id' => $id]);
        return (int) $stmt2->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

