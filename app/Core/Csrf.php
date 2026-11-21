<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Kelas Keamanan CSRF Token
 * Melindungi seluruh formulir POST dan request mutasi dari serangan Cross-Site Request Forgery.
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function getToken(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function inputField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $submittedToken = null): bool
    {
        if ($submittedToken === null) {
            $submittedToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';
        if ($sessionToken === '' || $submittedToken === '') {
            return false;
        }
        return hash_equals((string) $sessionToken, (string) $submittedToken);
    }

    public static function requireValidToken(): void
    {
        if (!self::verify()) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

            if ($isAjax) {
                http_response_code(419);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Sesi keamanan CSRF tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.',
                ]);
                exit;
            }

            set_flash('danger', 'Permintaan ditolak: Token keamanan (CSRF) tidak valid atau sesi telah berakhir.');
            redirect($_SERVER['HTTP_REFERER'] ?? route_url('dashboard'));
        }
    }
}

