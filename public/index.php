<?php

declare(strict_types=1);

/**
 * Webcraft POS - Public DocumentRoot Entry Point
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

use App\Core\Router;

Router::dispatch();

