<?php

declare(strict_types=1);

/**
 * Webcraft POS - File Konfigurasi Utama
 * Memuat variabel environment, pengaturan sesi aman, dan autoloader modular.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
    define('APP_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'app');
    define('VIEW_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'views');
    define('STORAGE_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'storage');
    define('UPLOAD_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads');
}

// Muat file .env jika tersedia
$envFile = BASE_PATH . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $val;
                putenv("{$key}={$val}");
            }
        }
    }
}

if (!function_exists('env_val')) {
    /**
     * Helper mengambil variabel environment
     */
    function env_val(string $key, mixed $default = null): mixed
    {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        $lower = strtolower((string) $val);
        if ($lower === 'true') {
            return true;
        }
        if ($lower === 'false') {
            return false;
        }
        return $val;
    }
}

// Pengaturan Zona Waktu
date_default_timezone_set((string) env_val('APP_TIMEZONE', 'Asia/Jakarta'));

// Pengaturan Error Reporting berdasarkan mode debug
$isDebug = (bool) env_val('APP_DEBUG', false);
if ($isDebug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Konfigurasi Sesi Aman
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('WEBCRAFT_POS_SESS');
    session_start();
}

// Muat Composer Autoload jika sudah terinstal
$composerAutoload = BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

if (!defined('WEBCRAFT_AUTOLOAD_REGISTERED')) {
    define('WEBCRAFT_AUTOLOAD_REGISTERED', true);
    // PSR-4 Native Autoloader untuk namespace App\
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        $baseDir = APP_PATH . DIRECTORY_SEPARATOR;

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    });
}

// Muat fungsi helper global
require_once APP_PATH . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'functions.php';

return [
    'app_name' => (string) env_val('APP_NAME', 'Webcraft POS'),
    'app_env'  => (string) env_val('APP_ENV', 'production'),
    'debug'    => $isDebug,
    'db'       => [
        'driver'          => (string) env_val('DB_DRIVER', 'mysql'),
        'host'            => (string) env_val('DB_HOST', '127.0.0.1'),
        'port'            => (string) env_val('DB_PORT', '3306'),
        'database'        => (string) env_val('DB_NAME', 'webcraft_pos'),
        'username'        => (string) env_val('DB_USER', 'root'),
        'password'        => (string) env_val('DB_PASS', ''),
        'charset'         => (string) env_val('DB_CHARSET', 'utf8mb4'),
        'fallback_sqlite' => (bool) env_val('DB_AUTO_FALLBACK_SQLITE', true),
        'sqlite_path'     => STORAGE_PATH . DIRECTORY_SEPARATOR . 'database.sqlite',
    ],
];
