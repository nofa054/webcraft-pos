<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\ProductModel;
use App\Models\SettingModel;

abstract class BaseController
{
    protected SettingModel $settingModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    /**
     * Render tampilan di dalam layout utama (sidebar + topbar + footer)
     */
    protected function render(string $viewFile, array $data = [], string $pageTitle = 'Dashboard'): void
    {
        $storeSettings = $this->settingModel->getSettings();
        $lowStockCount = 0;
        $lowStockPreview = [];

        if (Auth::check()) {
            $productModel = new ProductModel();
            $lowStockCount = $productModel->getLowStockCount();
            $lowStockPreview = $productModel->getLowStockProducts(5);
        }

        extract($data, EXTR_SKIP);

        $fullViewPath = VIEW_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $viewFile) . '.php';
        if (!is_file($fullViewPath)) {
            throw new \RuntimeException("File view tidak ditemukan: {$viewFile}");
        }

        require VIEW_PATH . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'header.php';
        require $fullViewPath;
        require VIEW_PATH . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'footer.php';
    }

    /**
     * Render tampilan mandiri tanpa layout dashboard (misal: halaman Login atau Cetak Struk)
     */
    protected function renderStandalone(string $viewFile, array $data = []): void
    {
        $storeSettings = $this->settingModel->getSettings();
        extract($data, EXTR_SKIP);

        $fullViewPath = VIEW_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $viewFile) . '.php';
        if (!is_file($fullViewPath)) {
            throw new \RuntimeException("File view tidak ditemukan: {$viewFile}");
        }

        require $fullViewPath;
    }

    /**
     * Kirim response JSON dan hentikan eksekusi
     */
    protected function json(array $payload, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

