<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\ProductModel;
use App\Models\StockModel;
use App\Models\SupplierModel;

class StockController extends BaseController
{
    private StockModel $stockModel;
    private ProductModel $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->stockModel = new StockModel();
        $this->productModel = new ProductModel();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $filters = [
            'movement_type' => trim((string) ($_GET['movement_type'] ?? '')),
            'product_id'    => (int) ($_GET['product_id'] ?? 0),
            'start_date'    => trim((string) ($_GET['start_date'] ?? '')),
            'end_date'      => trim((string) ($_GET['end_date'] ?? '')),
            'search'        => trim((string) ($_GET['search'] ?? '')),
        ];

        $movements = $this->stockModel->getMovements($filters, 250);
        $products = $this->productModel->getAll(['only_active' => true]);
        $suppliers = (new SupplierModel())->getAll();
        $lowStockItems = $this->productModel->getLowStockProducts(50);

        $this->render('stock/index', [
            'movements'     => $movements,
            'products'      => $products,
            'suppliers'     => $suppliers,
            'lowStockItems' => $lowStockItems,
            'filters'       => $filters,
        ], 'Manajemen Stok & Riwayat Mutasi');
    }

    public function storeMovement(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        try {
            $this->stockModel->recordMovement($_POST, Auth::id());
            $typeLabel = match ($_POST['movement_type'] ?? '') {
                'in'         => 'Stok Masuk',
                'out'        => 'Stok Keluar',
                'adjustment' => 'Penyesuaian Stok (Opname)',
                default      => 'Mutasi Stok',
            };
            set_flash('success', "Pencatatan {$typeLabel} berhasil disimpan dan saldo stok telah diperbarui.");
        } catch (\Throwable $e) {
            set_flash('danger', 'Gagal mencatat perubahan stok: ' . $e->getMessage());
        }

        redirect($_SERVER['HTTP_REFERER'] ?? route_url('stock'));
    }
}

