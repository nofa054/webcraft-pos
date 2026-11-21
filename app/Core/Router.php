<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\DashboardController;
use App\Controllers\PartnerController;
use App\Controllers\PosController;
use App\Controllers\ProductController;
use App\Controllers\ReportController;
use App\Controllers\SettingController;
use App\Controllers\StockController;
use App\Controllers\UserController;

class Router
{
    public static function dispatch(): void
    {
        $page = trim((string) ($_GET['page'] ?? ''));
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($page === '') {
            $page = Auth::check() ? 'dashboard' : 'login';
        }

        switch ($page) {
            // Autentikasi & Profil
            case 'login':
                $ctrl = new AuthController();
                if ($method === 'POST') {
                    $ctrl->processLogin();
                } else {
                    $ctrl->showLogin();
                }
                break;

            case 'logout':
                (new AuthController())->logout();
                break;

            case 'profile_update':
                if ($method === 'POST') {
                    (new AuthController())->updateProfile();
                } else {
                    redirect(route_url('dashboard'));
                }
                break;

            // Dashboard
            case 'dashboard':
                (new DashboardController())->index();
                break;

            // Sistem Kasir (POS) & Transaksi
            case 'pos':
                (new PosController())->index();
                break;

            case 'pos_checkout':
                if ($method === 'POST') {
                    (new PosController())->checkout();
                } else {
                    redirect(route_url('pos'));
                }
                break;

            case 'receipt':
                (new PosController())->receipt();
                break;

            case 'transactions':
                (new PosController())->history();
                break;

            case 'transaction_detail_json':
                (new PosController())->detailJson();
                break;

            // Manajemen Produk
            case 'products':
                (new ProductController())->index();
                break;

            case 'product_store':
                if ($method === 'POST') {
                    (new ProductController())->store();
                } else {
                    redirect(route_url('products'));
                }
                break;

            case 'product_update':
                if ($method === 'POST') {
                    (new ProductController())->update();
                } else {
                    redirect(route_url('products'));
                }
                break;

            case 'product_delete':
                if ($method === 'POST') {
                    (new ProductController())->delete();
                } else {
                    redirect(route_url('products'));
                }
                break;

            // Manajemen Kategori
            case 'categories':
                (new CategoryController())->index();
                break;

            case 'category_store':
                if ($method === 'POST') {
                    (new CategoryController())->store();
                } else {
                    redirect(route_url('categories'));
                }
                break;

            case 'category_update':
                if ($method === 'POST') {
                    (new CategoryController())->update();
                } else {
                    redirect(route_url('categories'));
                }
                break;

            case 'category_delete':
                if ($method === 'POST') {
                    (new CategoryController())->delete();
                } else {
                    redirect(route_url('categories'));
                }
                break;

            // Manajemen Stok
            case 'stock':
                (new StockController())->index();
                break;

            case 'stock_store':
                if ($method === 'POST') {
                    (new StockController())->storeMovement();
                } else {
                    redirect(route_url('stock'));
                }
                break;

            // Pelanggan & Supplier
            case 'customers':
                (new PartnerController())->customers();
                break;

            case 'customer_store':
                if ($method === 'POST') {
                    (new PartnerController())->storeCustomer();
                } else {
                    redirect(route_url('customers'));
                }
                break;

            case 'customer_update':
                if ($method === 'POST') {
                    (new PartnerController())->updateCustomer();
                } else {
                    redirect(route_url('customers'));
                }
                break;

            case 'customer_delete':
                if ($method === 'POST') {
                    (new PartnerController())->deleteCustomer();
                } else {
                    redirect(route_url('customers'));
                }
                break;

            case 'suppliers':
                (new PartnerController())->suppliers();
                break;

            case 'supplier_store':
                if ($method === 'POST') {
                    (new PartnerController())->storeSupplier();
                } else {
                    redirect(route_url('suppliers'));
                }
                break;

            case 'supplier_update':
                if ($method === 'POST') {
                    (new PartnerController())->updateSupplier();
                } else {
                    redirect(route_url('suppliers'));
                }
                break;

            case 'supplier_delete':
                if ($method === 'POST') {
                    (new PartnerController())->deleteSupplier();
                } else {
                    redirect(route_url('suppliers'));
                }
                break;

            // Laporan & Ekspor
            case 'reports':
                (new ReportController())->index();
                break;

            case 'reports_export':
                (new ReportController())->export();
                break;

            // Manajemen Pengguna (Admin Only)
            case 'users':
                (new UserController())->index();
                break;

            case 'user_store':
                if ($method === 'POST') {
                    (new UserController())->store();
                } else {
                    redirect(route_url('users'));
                }
                break;

            case 'user_update':
                if ($method === 'POST') {
                    (new UserController())->update();
                } else {
                    redirect(route_url('users'));
                }
                break;

            case 'user_delete':
                if ($method === 'POST') {
                    (new UserController())->delete();
                } else {
                    redirect(route_url('users'));
                }
                break;

            // Pengaturan Toko (Admin Only)
            case 'settings':
                (new SettingController())->index();
                break;

            case 'settings_update':
                if ($method === 'POST') {
                    (new SettingController())->update();
                } else {
                    redirect(route_url('settings'));
                }
                break;

            default:
                set_flash('warning', 'Halaman yang Anda tuju tidak ditemukan.');
                redirect(route_url(Auth::check() ? 'dashboard' : 'login'));
        }
    }
}

