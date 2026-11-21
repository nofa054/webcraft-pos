<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\ProductModel;
use App\Models\SaleModel;

class DashboardController extends BaseController
{
    public function index(): void
    {
        Auth::requireLogin();

        $saleModel = new SaleModel();
        $productModel = new ProductModel();

        $stats = $saleModel->getDashboardStats();
        $lowStockItems = $productModel->getLowStockProducts(8);

        $this->render('dashboard/index', [
            'stats'         => $stats,
            'lowStockItems' => $lowStockItems,
        ], 'Dashboard Utama');
    }
}

