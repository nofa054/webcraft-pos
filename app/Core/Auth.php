<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Kelas Autentikasi & Otorisasi Berbasis Role (RBAC)
 */
class Auth
{
    private const USER_SESSION_KEY = 'auth_user';

    public static function check(): bool
    {
        return !empty($_SESSION[self::USER_SESSION_KEY]['id']);
    }

    public static function user(): ?array
    {
        return $_SESSION[self::USER_SESSION_KEY] ?? null;
    }

    public static function id(): int
    {
        return (int) ($_SESSION[self::USER_SESSION_KEY]['id'] ?? 0);
    }

    public static function role(): string
    {
        return (string) ($_SESSION[self::USER_SESSION_KEY]['role'] ?? '');
    }

    public static function isAdmin(): bool
    {
        return self::check() && self::role() === 'admin';
    }

    public static function login(string $username, string $password): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => trim($username)]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Username atau password yang Anda masukkan salah.',
            ];
        }

        if ((int) $user['is_active'] !== 1) {
            return [
                'success' => false,
                'message' => 'Akun Anda sedang dinonaktifkan. Hubungi Administrator toko.',
            ];
        }

        // Regenerate session ID untuk mencegah session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $now = date('Y-m-d H:i:s');
        $upd = $pdo->prepare("UPDATE users SET last_login = :now WHERE id = :id");
        $upd->execute(['now' => $now, 'id' => $user['id']]);

        $_SESSION[self::USER_SESSION_KEY] = [
            'id'         => (int) $user['id'],
            'name'       => $user['name'],
            'username'   => $user['username'],
            'email'      => $user['email'],
            'role'       => $user['role'],
            'last_login' => $now,
        ];

        return [
            'success' => true,
            'user'    => $_SESSION[self::USER_SESSION_KEY],
        ];
    }

    public static function refreshSessionUser(int $userId): void
    {
        if (self::id() !== $userId) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, username, email, role, last_login FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION[self::USER_SESSION_KEY] = [
                'id'         => (int) $user['id'],
                'name'       => $user['name'],
                'username'   => $user['username'],
                'email'      => $user['email'],
                'role'       => $user['role'],
                'last_login' => $user['last_login'],
            ];
        }
    }

    public static function logout(): void
    {
        unset($_SESSION[self::USER_SESSION_KEY]);
        unset($_SESSION['_csrf_token']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            set_flash('warning', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
            redirect(route_url('login'));
        }
    }

    public static function requireRole(array|string $roles): void
    {
        self::requireLogin();
        $allowed = is_array($roles) ? $roles : [$roles];
        if (!in_array(self::role(), $allowed, true)) {
            set_flash('danger', 'Akses ditolak: Anda tidak memiliki hak akses (Role) untuk membuka fitur tersebut.');
            redirect(route_url('dashboard'));
        }
    }
}
