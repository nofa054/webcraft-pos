<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\CategoryModel;

class CategoryController extends BaseController
{
    private CategoryModel $categoryModel;

    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new CategoryModel();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $search = trim((string) ($_GET['search'] ?? ''));
        $categories = $this->categoryModel->getAll($search);

        $this->render('categories/index', [
            'categories' => $categories,
            'search'     => $search,
        ], 'Manajemen Kategori Produk');
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($name === '') {
            set_flash('danger', 'Nama kategori wajib diisi.');
            redirect(route_url('categories'));
        }

        if ($this->categoryModel->findByName($name)) {
            set_flash('danger', "Kategori '{$name}' sudah terdaftar.");
            redirect(route_url('categories'));
        }

        $this->categoryModel->create([
            'name'        => $name,
            'description' => $description,
        ]);

        set_flash('success', "Kategori '{$name}' berhasil ditambahkan.");
        redirect(route_url('categories'));
    }

    public function update(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($id <= 0 || $name === '') {
            set_flash('danger', 'Nama kategori wajib diisi.');
            redirect(route_url('categories'));
        }

        if ($this->categoryModel->findByName($name, $id)) {
            set_flash('danger', "Nama kategori '{$name}' sudah digunakan.");
            redirect(route_url('categories'));
        }

        $this->categoryModel->update($id, [
            'name'        => $name,
            'description' => $description,
        ]);

        set_flash('success', "Kategori '{$name}' berhasil diperbarui.");
        redirect(route_url('categories'));
    }

    public function delete(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValidToken();

        $id = (int) ($_POST['id'] ?? 0);
        $category = $this->categoryModel->findById($id);

        if (!$category) {
            set_flash('danger', 'Kategori tidak ditemukan.');
            redirect(route_url('categories'));
        }

        $usedCount = $this->categoryModel->getProductCount($id);
        if ($usedCount > 0) {
            set_flash('warning', "Kategori '{$category['name']}' tidak dapat dihapus karena masih digunakan oleh {$usedCount} produk.");
            redirect(route_url('categories'));
        }

        $this->categoryModel->delete($id);
        set_flash('success', "Kategori '{$category['name']}' berhasil dihapus.");
        redirect(route_url('categories'));
    }
}

