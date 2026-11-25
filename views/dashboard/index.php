<?php
declare(strict_types=1);
?>
<!-- Kartu Ringkasan Utama -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold mb-1">Omzet Penjualan Hari Ini</div>
                    <h3 class="h4 fw-bold text-primary mb-1"><?= e(rupiah($stats['today_revenue'])) ?></h3>
                    <?php if (is_admin()): ?>
                        <div class="small text-success fw-medium">
                            <i class="bi bi-graph-up-arrow me-1"></i>Laba Kotor: <?= e(rupiah($stats['today_profit'])) ?>
                        </div>
                    <?php else: ?>
                        <div class="small text-muted">Penjualan harian kasir</div>
                    <?php endif; ?>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold mb-1">Jumlah Transaksi Hari Ini</div>
                    <h3 class="h4 fw-bold text-dark mb-1"><?= (int) $stats['today_transactions'] ?> <span class="fs-6 fw-normal text-muted">Transaksi</span></h3>
                    <div class="small text-muted">
                        Bulan ini: <strong><?= (int) $stats['month_transactions'] ?></strong> trx (<?= e(rupiah($stats['month_revenue'])) ?>)
                    </div>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold mb-1">Total Produk Aktif</div>
                    <h3 class="h4 fw-bold text-dark mb-1"><?= (int) $stats['total_products'] ?> <span class="fs-6 fw-normal text-muted">SKU</span></h3>
                    <div class="small">
                        <a href="<?= e(route_url('products')) ?>" class="text-decoration-none">Kelola Katalog Produk <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100 <?= $stats['low_stock_count'] > 0 ? 'border-warning' : '' ?>">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold mb-1">Informasi Stok Menipis</div>
                    <h3 class="h4 fw-bold <?= $stats['low_stock_count'] > 0 ? 'text-danger' : 'text-success' ?> mb-1">
                        <?= (int) $stats['low_stock_count'] ?> <span class="fs-6 fw-normal text-muted">Produk</span>
                    </h3>
                    <div class="small">
                        <a href="<?= e(route_url('stock')) ?>" class="text-decoration-none text-danger fw-medium">
                            Lihat &amp; Tambah Stok <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-15 text-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik Penjualan & Metode Pembayaran -->
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-bar-chart-fill text-primary me-2"></i>Grafik Tren Penjualan 7 Hari Terakhir
                </div>
                <?php if (is_admin()): ?>
                    <a href="<?= e(route_url('reports')) ?>" class="btn btn-outline-primary btn-sm">Laporan Lengkap</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div style="height: 290px;">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-pie-chart-fill text-info me-2"></i>Distribusi Metode Pembayaran
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <div style="height: 210px;">
                    <canvas id="paymentMethodChart"></canvas>
                </div>
                <div class="row text-center mt-3 pt-2 border-top g-2 small">
                    <div class="col-4">
                        <div class="text-muted">Tunai</div>
                        <div class="fw-bold text-dark"><?= e(rupiah($stats['payment_stats']['tunai'] ?? 0)) ?></div>
                    </div>
                    <div class="col-4 border-start border-end">
                        <div class="text-muted">Transfer</div>
                        <div class="fw-bold text-dark"><?= e(rupiah($stats['payment_stats']['transfer'] ?? 0)) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted">QRIS</div>
                        <div class="fw-bold text-dark"><?= e(rupiah($stats['payment_stats']['qris'] ?? 0)) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daftar Transaksi Terbaru & Peringatan Stok Menipis -->
<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history text-primary me-2"></i>Daftar Transaksi Terbaru</span>
                <a href="<?= e(route_url('transactions')) ?>" class="btn btn-sm btn-light border">Semua Transaksi</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Waktu</th>
                            <th>Pelanggan</th>
                            <th>Metode</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Struk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats['recent_sales'])): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada transaksi penjualan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stats['recent_sales'] as $sale): ?>
                                <tr>
                                    <td class="fw-semibold text-primary"><?= e($sale['invoice_no']) ?></td>
                                    <td><?= e(tanggal_indo($sale['transaction_date'], true)) ?></td>
                                    <td><?= e($sale['customer_name']) ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match ($sale['payment_method']) {
                                            'tunai'    => 'bg-success',
                                            'transfer' => 'bg-primary',
                                            'qris'     => 'bg-info text-dark',
                                            default    => 'bg-secondary',
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?> text-uppercase"><?= e($sale['payment_method']) ?></span>
                                    </td>
                                    <td class="text-end fw-bold"><?= e(rupiah($sale['grand_total'])) ?></td>
                                    <td class="text-center">
                                        <a href="<?= e(route_url('receipt', ['id' => $sale['id']])) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Cetak Struk">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>Produk dengan Stok Menipis</span>
                <a href="<?= e(route_url('stock')) ?>" class="btn btn-sm btn-outline-danger">Restock Barang</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-center">Stok Saat Ini</th>
                            <th class="text-center">Min. Stok</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lowStockItems)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="bi bi-check2-circle text-success fs-4 d-block mb-1"></i>
                                    Semua stok produk dalam kondisi aman.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lowStockItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($item['name']) ?></div>
                                        <div class="text-muted" style="font-size:0.72rem;"><?= e($item['sku']) ?> &bull; <?= e($item['category_name']) ?></div>
                                    </td>
                                    <td class="text-center fw-bold text-danger">
                                        <?= e(format_qty($item['stock'])) ?> <?= e($item['unit']) ?>
                                    </td>
                                    <td class="text-center text-muted">
                                        <?= e(format_qty($item['min_stock'])) ?> <?= e($item['unit']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ((float) $item['stock'] <= 0): ?>
                                            <span class="badge bg-danger">Habis</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Menipis</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = <?= json_encode($stats['chart_labels']) ?>;
    const revenueData = <?= json_encode($stats['chart_revenue']) ?>;
    const profitData = <?= json_encode($stats['chart_profit']) ?>;
    const isAdmin = <?= json_encode(is_admin()) ?>;

    const ctxSales = document.getElementById('salesTrendChart');
    if (ctxSales && typeof Chart !== 'undefined') {
        const datasets = [
            {
                label: 'Omzet Penjualan (Rp)',
                data: revenueData,
                backgroundColor: 'rgba(37, 99, 235, 0.18)',
                borderColor: '#2563eb',
                borderWidth: 2.5,
                tension: 0.35,
                fill: true
            }
        ];

        if (isAdmin) {
            datasets.push({
                label: 'Keuntungan Kotor (Rp)',
                data: profitData,
                backgroundColor: 'rgba(16, 185, 129, 0.12)',
                borderColor: '#10b981',
                borderWidth: 2,
                tension: 0.35,
                fill: true
            });
        }

        new Chart(ctxSales, {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': Rp ' + Number(ctx.raw).toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100000,
                        ticks: {
                            precision: 0,
                            callback: function (value) {
                                return 'Rp ' + Math.round(Number(value)).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    const ctxPay = document.getElementById('paymentMethodChart');
    if (ctxPay && typeof Chart !== 'undefined') {
        const payStats = <?= json_encode(array_values($stats['payment_stats'])) ?>;
        new Chart(ctxPay, {
            type: 'doughnut',
            data: {
                labels: ['Tunai', 'Transfer Bank', 'QRIS'],
                datasets: [{
                    data: payStats,
                    backgroundColor: ['#10b981', '#2563eb', '#06b6d4'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>

