<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Models\SupplierModel;

class ProductController extends BaseController
{
    private ProductModel $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new ProductModel();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $filters = [
            'search'       => trim((string) ($_GET['search'] ?? '')),
            'category_id'  => (int) ($_GET['category_id'] ?? 0),
            'stock_status' => trim((string) ($_GET['stock_status'] ?? '')),
        ];

        $products = $this->productModel->getAll($filters);
        $categories = (new CategoryModel())->getAll();
        $suppliers = (new SupplierModel())->getAll();
        $nextSku = $this->productModel->generateNextSku();

        $this->render('products/index', [
            'products'   => $products,
            'categories' => $categories,
            'suppliers'  => $suppliers,
            'filters'    => $filters,
            'nextSku'    => $nextSku,
        ], 'Manajemen Produk');
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $name = trim((string) ($_POST['name'] ?? ''));
        $sku = trim((string) ($_POST['sku'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $costPrice = (float) ($_POST['cost_price'] ?? 0);
        $sellingPrice = (float) ($_POST['selling_price'] ?? 0);

        if ($name === '' || $categoryId <= 0) {
            set_flash('danger', 'Nama produk dan kategori wajib diisi.');
            redirect(route_url('products'));
        }

        if ($costPrice < 0 || $sellingPrice < 0) {
            set_flash('danger', 'Harga modal dan harga jual tidak boleh bernilai negatif.');
            redirect(route_url('products'));
        }

        if ($sku !== '' && $this->productModel->findBySku($sku)) {
            set_flash('danger', "Kode produk / SKU '{$sku}' sudah digunakan oleh produk lain.");
            redirect(route_url('products'));
        }

        try {
            $this->productModel->create($_POST, Auth::id());
            set_flash('success', "Produk '{$name}' berhasil ditambahkan ke katalog.");
        } catch (\Throwable $e) {
            set_flash('danger', 'Gagal menyimpan produk: ' . $e->getMessage());
        }

        redirect(route_url('products'));
    }

    public function update(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $id = (int) ($_POST['id'] ?? 0);
        $product = $this->productModel->findById($id);
        if (!$product) {
            set_flash('danger', 'Data produk tidak ditemukan.');
            redirect(route_url('products'));
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $sku = trim((string) ($_POST['sku'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        if ($name === '' || $sku === '' || $categoryId <= 0) {
            set_flash('danger', 'Kode SKU, Nama Produk, dan Kategori wajib diisi.');
            redirect(route_url('products'));
        }

        if ($this->productModel->findBySku($sku, $id)) {
            set_flash('danger', "Kode SKU '{$sku}' sudah digunakan oleh produk lain.");
            redirect(route_url('products'));
        }

        try {
            $this->productModel->update($id, $_POST);
            set_flash('success', "Data produk '{$name}' berhasil diperbarui.");
        } catch (\Throwable $e) {
            set_flash('danger', 'Gagal memperbarui produk: ' . $e->getMessage());
        }

        redirect(route_url('products'));
    }

    public function delete(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $id = (int) ($_POST['id'] ?? 0);
        $product = $this->productModel->findById($id);
        if (!$product) {
            set_flash('danger', 'Produk tidak ditemukan.');
            redirect(route_url('products'));
        }

        if ($this->productModel->hasSalesHistory($id)) {
            set_flash('warning', "Produk '{$product['name']}' sudah memiliki riwayat transaksi penjualan sehingga tidak dapat dihapus. Anda dapat menonaktifkan status produk tersebut.");
            redirect(route_url('products'));
        }

        if ($this->productModel->delete($id)) {
            set_flash('success', "Produk '{$product['name']}' berhasil dihapus.");
        } else {
            set_flash('danger', 'Gagal menghapus produk.');
        }

        redirect(route_url('products'));
    }
}

