<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;

/**
 * Escape string untuk mencegah XSS pada tampilan HTML
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format angka menjadi mata uang Rupiah
 */
function rupiah(float|int|string|null $amount, bool $withSymbol = true): string
{
    $num = (float) ($amount ?? 0);
    $formatted = number_format($num, 0, ',', '.');
    return $withSymbol ? 'Rp ' . $formatted : $formatted;
}

/**
 * Format kuantitas stok/item (menghilangkan .00 jika bilangan bulat)
 */
function format_qty(float|int|string|null $qty): string
{
    $num = (float) ($qty ?? 0);
    if (floor($num) == $num) {
        return number_format($num, 0, ',', '.');
    }
    return rtrim(rtrim(number_format($num, 2, ',', '.'), '0'), ',');
}

/**
 * Format tanggal ke format Indonesia (contoh: 14 Nov 2026)
 */
function tanggal_indo(?string $datetime, bool $withTime = false): string
{
    if (empty($datetime)) {
        return '-';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];
    $d = date('d', $ts);
    $m = $bulan[(int) date('n', $ts)] ?? date('M', $ts);
    $y = date('Y', $ts);
    $result = "{$d} {$m} {$y}";
    if ($withTime) {
        $result .= ' ' . date('H:i', $ts);
    }
    return $result;
}

/**
 * Mendapatkan base URL dinamis
 */
function base_url(string $path = ''): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $path = ltrim($path, '/');
    if ($path === '') {
        return $dir === '' ? '/' : $dir . '/';
    }
    return ($dir === '' ? '' : $dir) . '/' . $path;
}

/**
 * Menghasilkan URL aset statis (mendukung akses dari root maupun dari folder public/)
 */
function asset_url(string $assetPath): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $clean = ltrim($assetPath, '/');
    if (str_ends_with(dirname($scriptName), '/public')) {
        return base_url($clean);
    }
    return base_url('public/' . $clean);
}

/**
 * Buat URL halaman aplikasi (contoh: index.php?page=products)
 */
function route_url(string $page, array $params = []): string
{
    $query = array_merge(['page' => $page], $params);
    return base_url('index.php?' . http_build_query($query));
}

/**
 * Redirect ke halaman tertentu dan hentikan eksekusi
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Set flash message di dalam session
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Ambil dan bersihkan semua flash message
 */
function get_flashes(): array
{
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

/**
 * Menghasilkan input hidden CSRF token
 */
function csrf_field(): string
{
    return Csrf::inputField();
}

/**
 * Mendapatkan nilai CSRF token aktif
 */
function csrf_token(): string
{
    return Csrf::getToken();
}

/**
 * Cek apakah halaman aktif cocok dengan parameter
 */
function is_active_page(string|array $pages): string
{
    $current = $_GET['page'] ?? 'dashboard';
    $targets = is_array($pages) ? $pages : [$pages];
    return in_array($current, $targets, true) ? 'active' : '';
}

/**
 * Mendapatkan data user yang sedang login
 */
function current_user(): ?array
{
    return Auth::user();
}

/**
 * Cek apakah user saat ini adalah Admin
 */
function is_admin(): bool
{
    return Auth::isAdmin();
}
