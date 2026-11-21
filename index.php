<?php

declare(strict_types=1);

/**
 * Webcraft POS - Entry Point Utama
 * Sistem Kasir dan Manajemen Stok Berbasis Web untuk UMKM
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

use App\Core\Router;

Router::dispatch();

