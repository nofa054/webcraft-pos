<?php
declare(strict_types=1);

$authUser = current_user();
$storeName = $storeSettings['store_name'] ?? 'Webcraft POS';
$logoPath = $storeSettings['logo_path'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle ?? 'Dashboard') ?> &mdash; <?= e($storeName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="app-wrapper">
    <!-- Sidebar Navigasi -->
    <aside class="app-sidebar" id="appSidebar">
        <div class="d-flex align-items-center justify-content-between pe-2">
            <a href="<?= e(route_url('dashboard')) ?>" class="sidebar-brand flex-grow-1">
                <?php if (!empty($logoPath)): ?>
                    <img src="<?= e(asset_url($logoPath)) ?>" alt="Logo" class="rounded" style="width:40px;height:40px;object-fit:cover;">
                <?php else: ?>
                    <div class="sidebar-brand-icon">
                        <i class="bi bi-shop-window"></i>
                    </div>
                <?php endif; ?>
                <div class="overflow-hidden">
                    <div class="fw-bold text-truncate" style="font-size:0.95rem;"><?= e($storeName) ?></div>
                    <div class="text-secondary" style="font-size:0.72rem;">Webcraft POS v1.0</div>
                </div>
            </a>
            <button type="button" class="btn btn-sm btn-link text-light d-lg-none" id="btnCloseSidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-heading">Menu Utama</div>
            <a href="<?= e(route_url('dashboard')) ?>" class="sidebar-link <?= is_active_page('dashboard') ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= e(route_url('pos')) ?>" class="sidebar-link <?= is_active_page('pos') ?>">
                <i class="bi bi-calculator-fill"></i>
                <span>Mesin Kasir (POS)</span>
                <span class="badge bg-info text-dark ms-auto" style="font-size:0.65rem;">F2</span>
            </a>
            <a href="<?= e(route_url('transactions')) ?>" class="sidebar-link <?= is_active_page('transactions') ?>">
                <i class="bi bi-receipt-cutoff"></i>
                <span>Riwayat Transaksi</span>
            </a>

            <div class="sidebar-heading mt-2">Katalog &amp; Gudang</div>
            <a href="<?= e(route_url('products')) ?>" class="sidebar-link <?= is_active_page('products') ?>">
                <i class="bi bi-box-seam-fill"></i>
                <span>Data Produk</span>
            </a>
            <a href="<?= e(route_url('categories')) ?>" class="sidebar-link <?= is_active_page('categories') ?>">
                <i class="bi bi-tags-fill"></i>
                <span>Kategori Produk</span>
            </a>
            <a href="<?= e(route_url('stock')) ?>" class="sidebar-link <?= is_active_page('stock') ?>">
                <i class="bi bi-arrow-left-right"></i>
                <span>Manajemen Stok</span>
                <?php if (($lowStockCount ?? 0) > 0): ?>
                    <span class="badge rounded-pill bg-danger ms-auto" title="Stok Menipis"><?= (int) $lowStockCount ?></span>
                <?php endif; ?>
            </a>

            <div class="sidebar-heading mt-2">Mitra Usaha</div>
            <a href="<?= e(route_url('customers')) ?>" class="sidebar-link <?= is_active_page('customers') ?>">
                <i class="bi bi-people-fill"></i>
                <span>Data Pelanggan</span>
            </a>
            <a href="<?= e(route_url('suppliers')) ?>" class="sidebar-link <?= is_active_page('suppliers') ?>">
                <i class="bi bi-truck"></i>
                <span>Data Supplier</span>
            </a>

            <?php if (is_admin()): ?>
                <div class="sidebar-heading mt-2">Analitik &amp; Sistem</div>
                <a href="<?= e(route_url('reports')) ?>" class="sidebar-link <?= is_active_page('reports') ?>">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    <span>Laporan &amp; Laba</span>
                </a>
                <a href="<?= e(route_url('users')) ?>" class="sidebar-link <?= is_active_page('users') ?>">
                    <i class="bi bi-person-badge-fill"></i>
                    <span>Manajemen Pengguna</span>
                </a>
                <a href="<?= e(route_url('settings')) ?>" class="sidebar-link <?= is_active_page('settings') ?>">
                    <i class="bi bi-sliders"></i>
                    <span>Pengaturan Toko</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="p-3 border-top border-secondary border-opacity-25">
            <div class="d-flex align-items-center justify-content-between small text-secondary">
                <span>Role: <strong class="text-uppercase text-white"><?= e($authUser['role'] ?? 'kasir') ?></strong></span>
                <span class="badge bg-success bg-opacity-25 text-success"><i class="bi bi-circle-fill me-1" style="font-size:0.45rem;"></i>Online</span>
            </div>
        </div>
    </aside>

    <!-- Konten Utama -->
    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-outline-secondary btn-sm d-lg-none" id="btnToggleSidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h1 class="h5 mb-0 fw-bold text-dark"><?= e($pageTitle ?? 'Dashboard') ?></h1>
                    <div class="text-muted small d-none d-sm-block">
                        <i class="bi bi-calendar3 me-1"></i><?= e(tanggal_indo(date('Y-m-d'))) ?>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 gap-md-3">
                <!-- Tombol Cepat Buka Kasir -->
                <?php if (($_GET['page'] ?? '') !== 'pos'): ?>
                    <a href="<?= e(route_url('pos')) ?>" class="btn btn-primary btn-sm fw-semibold d-flex align-items-center gap-1 px-3 shadow-sm">
                        <i class="bi bi-cart-plus-fill"></i>
                        <span class="d-none d-md-inline">Buka Kasir</span>
                    </a>
                <?php endif; ?>

                <!-- Notifikasi Stok Minimum -->
                <div class="dropdown">
                    <button class="btn btn-light border btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi Stok Menipis">
                        <i class="bi bi-bell-fill text-secondary"></i>
                        <?php if (($lowStockCount ?? 0) > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;">
                                <?= (int) $lowStockCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0" style="width: 320px;">
                        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center rounded-top">
                            <span class="fw-bold small"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Notifikasi Stok Minimum</span>
                            <span class="badge bg-danger"><?= (int) ($lowStockCount ?? 0) ?> Item</span>
                        </div>
                        <div class="list-group list-group-flush" style="max-height: 260px; overflow-y: auto;">
                            <?php if (empty($lowStockPreview)): ?>
                                <div class="p-4 text-center text-muted small">
                                    <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                                    Seluruh stok produk berada di atas batas minimum.
                                </div>
                            <?php else: ?>
                                <?php foreach ($lowStockPreview as $lsItem): ?>
                                    <a href="<?= e(route_url('stock')) ?>" class="list-group-item list-group-item-action py-2 px-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold small text-truncate" style="max-width: 190px;"><?= e($lsItem['name']) ?></span>
                                            <span class="badge <?= (float) $lsItem['stock'] <= 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                                Sisa: <?= e(format_qty($lsItem['stock'])) ?> <?= e($lsItem['unit']) ?>
                                            </span>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.72rem;">
                                            SKU: <?= e($lsItem['sku']) ?> &bull; Min: <?= e(format_qty($lsItem['min_stock'])) ?> <?= e($lsItem['unit']) ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="p-2 border-top text-center bg-light rounded-bottom">
                            <a href="<?= e(route_url('products', ['stock_status' => 'alert'])) ?>" class="small text-decoration-none fw-semibold">
                                Lihat Semua Stok Menipis <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Akun Pengguna -->
                <div class="dropdown">
                    <button class="btn btn-light border btn-sm d-flex align-items-center gap-2 px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:28px;height:28px;font-size:0.78rem;">
                            <?= e(strtoupper(substr($authUser['name'] ?? 'U', 0, 1))) ?>
                        </div>
                        <div class="text-start d-none d-sm-block">
                            <div class="fw-semibold lh-1" style="font-size:0.8rem;"><?= e($authUser['name'] ?? 'User') ?></div>
                            <small class="text-muted text-uppercase" style="font-size:0.65rem;"><?= e($authUser['role'] ?? 'kasir') ?></small>
                        </div>
                        <i class="bi bi-chevron-down small text-muted"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold small"><?= e($authUser['name'] ?? '') ?></div>
                            <div class="text-muted" style="font-size:0.75rem;">@<?= e($authUser['username'] ?? '') ?></div>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item small py-2" data-bs-toggle="modal" data-bs-target="#modalEditProfile">
                                <i class="bi bi-person-gear me-2 text-primary"></i>Pengaturan Profil &amp; Password
                            </button>
                        </li>
                        <?php if (is_admin()): ?>
                            <li>
                                <a class="dropdown-item small py-2" href="<?= e(route_url('settings')) ?>">
                                    <i class="bi bi-gear me-2 text-secondary"></i>Pengaturan Toko
                                </a>
                            </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item small py-2 text-danger fw-semibold" href="<?= e(route_url('logout')) ?>">
                                <i class="bi bi-box-arrow-right me-2"></i>Keluar (Logout)
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Area Konten Halaman -->
        <main class="app-content">
            <?php foreach (get_flashes() as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm border-0 mb-3" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($flash['type'] === 'success'): ?>
                            <i class="bi bi-check-circle-fill fs-5"></i>
                        <?php elseif ($flash['type'] === 'danger'): ?>
                            <i class="bi bi-x-circle-fill fs-5"></i>
                        <?php else: ?>
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <?php endif; ?>
                        <div><?= e($flash['message']) ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>

