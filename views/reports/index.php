<?php
declare(strict_types=1);

$tabs = [
    'daily'        => ['icon' => 'bi-calendar-day',    'label' => 'Penjualan Harian'],
    'monthly'      => ['icon' => 'bi-calendar-month',  'label' => 'Penjualan Bulanan'],
    'range'        => ['icon' => 'bi-calendar-range',  'label' => 'Rentang Tanggal'],
    'transactions' => ['icon' => 'bi-receipt',         'label' => 'Rincian Transaksi'],
    'bestseller'   => ['icon' => 'bi-trophy',          'label' => 'Produk Terlaris'],
    'inventory'    => ['icon' => 'bi-boxes',           'label' => 'Persediaan Barang'],
    'profit'       => ['icon' => 'bi-cash-coin',       'label' => 'Keuntungan Kotor'],
];

$exportQueryBase = [
    'type'           => $reportType,
    'start_date'     => $startDate,
    'end_date'       => $endDate,
    'year'           => $year,
    'payment_method' => $paymentMethod,
    'category_id'    => $categoryId,
];

$excelUrl = route_url('reports_export', array_merge($exportQueryBase, ['format' => 'excel']));
$pdfUrl = route_url('reports_export', array_merge($exportQueryBase, ['format' => 'pdf']));
?>
<!-- Tab Pilihan Jenis Laporan -->
<div class="card mb-3">
    <div class="card-body p-2">
        <div class="nav nav-pills flex-nowrap overflow-auto gap-1 pb-1">
            <?php foreach ($tabs as $key => $tab): ?>
                <a href="<?= e(route_url('reports', ['type' => $key, 'start_date' => $startDate, 'end_date' => $endDate, 'year' => $year])) ?>"
                   class="nav-link btn-sm text-nowrap d-flex align-items-center gap-1 <?= $reportType === $key ? 'active fw-semibold' : 'text-secondary' ?>">
                    <i class="bi <?= e($tab['icon']) ?>"></i>
                    <span><?= e($tab['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Filter & Tombol Ekspor Laporan -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end justify-content-between">
            <div class="col-12 col-xl-8">
                <form method="GET" action="<?= e(base_url('index.php')) ?>" class="row g-2 align-items-end">
                    <input type="hidden" name="page" value="reports">
                    <input type="hidden" name="type" value="<?= e($reportType) ?>">

                    <?php if ($reportType === 'monthly'): ?>
                        <div class="col-6 col-sm-4">
                            <label class="form-label small fw-semibold mb-1">Pilih Tahun Buku</label>
                            <select name="year" class="form-select form-select-sm">
                                <?php for ($y = (int) date('Y') + 1; $y >= 2023; $y--): ?>
                                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>>Tahun <?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php elseif ($reportType === 'inventory'): ?>
                        <div class="col-8 col-sm-5">
                            <label class="form-label small fw-semibold mb-1">Filter Kategori Produk</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="0">Semua Kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-6 col-sm-3">
                            <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
                        </div>
                        <div class="col-6 col-sm-3">
                            <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
                        </div>
                        <?php if ($reportType === 'transactions' || $reportType === 'range'): ?>
                            <div class="col-6 col-sm-3">
                                <label class="form-label small fw-semibold mb-1">Metode Bayar</label>
                                <select name="payment_method" class="form-select form-select-sm">
                                    <option value="">Semua Metode</option>
                                    <option value="tunai" <?= $paymentMethod === 'tunai' ? 'selected' : '' ?>>Tunai</option>
                                    <option value="transfer" <?= $paymentMethod === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                                    <option value="qris" <?= $paymentMethod === 'qris' ? 'selected' : '' ?>>QRIS</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="col-6 col-sm-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-funnel-fill me-1"></i> Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tombol Ekspor Excel & PDF -->
            <div class="col-12 col-xl-4 d-flex justify-content-xl-end gap-2 mt-2 mt-xl-0">
                <a href="<?= e($excelUrl) ?>" class="btn btn-success btn-sm fw-semibold d-flex align-items-center gap-1">
                    <i class="bi bi-file-earmark-excel-fill"></i> Ekspor Excel (.xlsx)
                </a>
                <a href="<?= e($pdfUrl) ?>" class="btn btn-danger btn-sm fw-semibold d-flex align-items-center gap-1">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Ekspor PDF (.pdf)
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Kartu Ringkasan Laporan -->
<?php if (!empty($reportData['summary'])): ?>
    <div class="row g-2 mb-3">
        <?php foreach ($reportData['summary'] as $label => $value): ?>
            <div class="col-6 col-md-4 col-xl-2 flex-grow-1">
                <div class="card h-100 bg-white border-start border-4 border-primary">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted" style="font-size: 0.72rem;"><?= e($label) ?></div>
                        <div class="fw-bold text-dark mt-1" style="font-size: 0.95rem;"><?= e($value) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Tabel Data Laporan -->
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="h6 fw-bold mb-0 text-dark"><?= e($reportData['title']) ?></h5>
            <div class="text-muted small"><?= e($reportData['subtitle']) ?></div>
        </div>
        <span class="badge bg-light text-secondary border"><?= count($reportData['export_rows']) ?> Baris Data</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 small">
            <thead>
                <tr>
                    <?php foreach ($reportData['headers'] as $h): ?>
                        <th><?= e($h) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reportData['export_rows'])): ?>
                    <tr>
                        <td colspan="<?= count($reportData['headers']) ?>" class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard-data fs-2 d-block mb-2"></i>
                            Tidak ada data laporan untuk periode atau filter yang dipilih.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportData['export_rows'] as $row): ?>
                        <tr>
                            <?php foreach ($row as $idx => $cell): ?>
                                <td class="<?= $idx === 0 ? 'text-muted' : ($idx >= count($row) - 2 ? 'fw-semibold' : '') ?>">
                                    <?= e($cell) ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

