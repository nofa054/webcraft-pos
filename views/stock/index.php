<?php
declare(strict_types=1);
?>
<!-- Panel Notifikasi Produk Stok Menipis -->
<?php if (!empty($lowStockItems)): ?>
    <div class="card border-warning mb-3">
        <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold text-dark small">
                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Peringatan Stok Minimum (<?= count($lowStockItems) ?> Produk Perlu Restock)
            </span>
            <button class="btn btn-sm btn-link text-dark text-decoration-none py-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLowStock">
                Tampilkan / Sembunyikan <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="collapseLowStock">
            <div class="card-body py-2 px-3">
                <div class="row g-2">
                    <?php foreach (array_slice($lowStockItems, 0, 6) as $ls): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="border rounded p-2 d-flex justify-content-between align-items-center bg-light">
                                <div class="overflow-hidden me-2">
                                    <div class="fw-semibold small text-truncate"><?= e($ls['name']) ?></div>
                                    <div class="text-muted" style="font-size:0.72rem;">
                                        Sisa: <strong class="text-danger"><?= e(format_qty($ls['stock'])) ?> <?= e($ls['unit']) ?></strong> (Min: <?= e(format_qty($ls['min_stock'])) ?>)
                                    </div>
                                </div>
                                <?php if (is_admin()): ?>
                                    <button type="button" class="btn btn-sm btn-primary py-1 px-2 flex-shrink-0"
                                            onclick="openStockModal('in', <?= (int) $ls['id'] ?>)">
                                        <i class="bi bi-plus-circle me-1"></i>Isi Stok
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Tombol Aksi Pencatatan Stok & Filter Riwayat -->
<div class="card mb-3">
    <div class="card-body">
        <?php if (is_admin()): ?>
            <div class="d-flex flex-wrap gap-2 mb-3 pb-3 border-bottom">
                <button type="button" class="btn btn-success btn-sm fw-semibold px-3" onclick="openStockModal('in')">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Catat Stok Masuk (Pembelian)
                </button>
                <button type="button" class="btn btn-danger btn-sm fw-semibold px-3" onclick="openStockModal('out')">
                    <i class="bi bi-box-arrow-up me-1"></i> Catat Stok Keluar (Rusak / Retur)
                </button>
                <button type="button" class="btn btn-warning btn-sm fw-semibold text-dark px-3" onclick="openStockModal('adjustment')">
                    <i class="bi bi-clipboard2-check me-1"></i> Penyesuaian Stok (Stock Opname)
                </button>
            </div>
        <?php endif; ?>

        <form method="GET" action="<?= e(base_url('index.php')) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="stock">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold mb-1">Cari Referensi / Produk</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($filters['search']) ?>" placeholder="Nama produk, SKU, No. Ref...">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Jenis Mutasi</label>
                <select name="movement_type" class="form-select form-select-sm">
                    <option value="">Semua Jenis</option>
                    <option value="in" <?= $filters['movement_type'] === 'in' ? 'selected' : '' ?>>Stok Masuk (+)</option>
                    <option value="out" <?= $filters['movement_type'] === 'out' ? 'selected' : '' ?>>Stok Keluar (-)</option>
                    <option value="adjustment" <?= $filters['movement_type'] === 'adjustment' ? 'selected' : '' ?>>Penyesuaian Opname</option>
                    <option value="sale" <?= $filters['movement_type'] === 'sale' ? 'selected' : '' ?>>Penjualan Kasir</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($filters['start_date']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($filters['end_date']) ?>">
            </div>
            <div class="col-6 col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="<?= e(route_url('stock')) ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Riwayat Perubahan Stok -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history text-primary me-2"></i>Riwayat Perubahan &amp; Kartu Stok (<?= count($movements) ?> Aktivitas)
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>No. Referensi</th>
                    <th>Produk</th>
                    <th class="text-center">Jenis</th>
                    <th class="text-center">Stok Awal</th>
                    <th class="text-center">Perubahan</th>
                    <th class="text-center">Stok Akhir</th>
                    <th>Petugas</th>
                    <th>Catatan / Supplier</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">Belum ada catatan riwayat perubahan stok.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <?php
                        $change = (float) $m['qty_change'];
                        [$typeText, $typeBadge] = match ($m['movement_type']) {
                            'in'         => ['Stok Masuk', 'bg-success'],
                            'out'        => ['Stok Keluar', 'bg-danger'],
                            'adjustment' => ['Penyesuaian', 'bg-warning text-dark'],
                            'sale'       => ['Penjualan', 'bg-primary'],
                            default      => [$m['movement_type'], 'bg-secondary'],
                        };
                        ?>
                        <tr>
                            <td><?= e(tanggal_indo($m['created_at'], true)) ?></td>
                            <td class="fw-semibold text-secondary"><?= e($m['reference_no'] ?: '-') ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($m['product_name']) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;"><?= e($m['product_sku']) ?></div>
                            </td>
                            <td class="text-center"><span class="badge <?= $typeBadge ?>"><?= e($typeText) ?></span></td>
                            <td class="text-center text-muted"><?= e(format_qty($m['stock_before'])) ?></td>
                            <td class="text-center fw-bold <?= $change >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= ($change >= 0 ? '+' : '') . e(format_qty($change)) ?> <?= e($m['product_unit']) ?>
                            </td>
                            <td class="text-center fw-bold text-dark"><?= e(format_qty($m['stock_after'])) ?> <?= e($m['product_unit']) ?></td>
                            <td><?= e($m['user_name']) ?></td>
                            <td>
                                <div><?= e($m['notes'] ?: '-') ?></div>
                                <?php if (!empty($m['supplier_name'])): ?>
                                    <div class="text-muted" style="font-size:0.7rem;">Supplier: <?= e($m['supplier_name']) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (is_admin()): ?>
<!-- Modal Mutasi Stok (Stok Masuk, Stok Keluar, Penyesuaian Opname) -->
<div class="modal fade" id="modalStockMovement" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(route_url('stock_store')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="movement_type" id="stkMovementType" value="in">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="stkModalTitle">Catat Stok Masuk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Pilih Produk <span class="text-danger">*</span></label>
                    <select name="product_id" id="stkProductId" class="form-select" required onchange="updateSelectedProductStockInfo()">
                        <option value="">-- Pilih Produk --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"
                                    data-stock="<?= (float) $p['stock'] ?>"
                                    data-unit="<?= e($p['unit']) ?>"
                                    data-cost="<?= (float) $p['cost_price'] ?>">
                                [<?= e($p['sku']) ?>] <?= e($p['name']) ?> (Stok: <?= e(format_qty($p['stock'])) ?> <?= e($p['unit']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-primary fw-medium" id="stkCurrentStockInfo"></div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold" id="stkQtyLabel">Jumlah Stok Masuk <span class="text-danger">*</span></label>
                        <input type="number" name="qty" id="stkQtyInput" class="form-control" min="0" step="any" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">No. Referensi / Faktur</label>
                        <input type="text" name="reference_no" class="form-control" placeholder="Otomatis jika kosong">
                    </div>
                </div>

                <!-- Khusus Stok Masuk: Pilih Supplier & Harga Beli -->
                <div id="stkSupplierSection">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Supplier / Pemasok (Opsional)</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">-- Pilih Supplier --</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= (int) $sup['id'] ?>"><?= e($sup['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Harga Beli Satuan Saat Ini (Rp)</label>
                        <input type="number" name="unit_cost" id="stkUnitCost" class="form-control" min="0" step="any">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="update_cost_price" value="1" id="chkUpdateCost" checked>
                            <label class="form-check-label small text-muted" for="chkUpdateCost">
                                Perbarui harga modal produk di katalog dengan harga beli ini
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold">Catatan / Alasan Mutasi <span class="text-danger">*</span></label>
                    <textarea name="notes" id="stkNotes" class="form-control" rows="2" required placeholder="Contoh: Barang masuk PO supplier / Barang rusak / Stok opname bulanan"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Simpan Mutasi Stok</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateSelectedProductStockInfo() {
    const sel = document.getElementById('stkProductId');
    const opt = sel.options[sel.selectedIndex];
    const infoEl = document.getElementById('stkCurrentStockInfo');
    const costInput = document.getElementById('stkUnitCost');

    if (!opt || !opt.value) {
        infoEl.textContent = '';
        return;
    }

    const stock = opt.getAttribute('data-stock');
    const unit = opt.getAttribute('data-unit');
    const cost = opt.getAttribute('data-cost');

    infoEl.textContent = 'Stok sistem saat ini: ' + stock + ' ' + unit;
    if (costInput) {
        costInput.value = cost;
    }
}

function openStockModal(type, preselectProductId = 0) {
    document.getElementById('stkMovementType').value = type;
    const titleEl = document.getElementById('stkModalTitle');
    const qtyLabel = document.getElementById('stkQtyLabel');
    const supSection = document.getElementById('stkSupplierSection');
    const notesInput = document.getElementById('stkNotes');
    const prodSelect = document.getElementById('stkProductId');

    if (preselectProductId > 0) {
        prodSelect.value = String(preselectProductId);
    }

    if (type === 'in') {
        titleEl.innerHTML = '<i class="bi bi-box-arrow-in-down text-success me-2"></i>Catat Stok Masuk (Penambahan)';
        qtyLabel.innerHTML = 'Jumlah Stok Masuk (+) <span class="text-danger">*</span>';
        supSection.classList.remove('d-none');
        notesInput.placeholder = 'Contoh: Pembelian stok barang dari supplier';
    } else if (type === 'out') {
        titleEl.innerHTML = '<i class="bi bi-box-arrow-up text-danger me-2"></i>Catat Stok Keluar (Pengurangan)';
        qtyLabel.innerHTML = 'Jumlah Stok Keluar (-) <span class="text-danger">*</span>';
        supSection.classList.add('d-none');
        notesInput.placeholder = 'Contoh: Barang rusak / kadaluarsa / sampel';
    } else {
        titleEl.innerHTML = '<i class="bi bi-clipboard2-check text-warning me-2"></i>Penyesuaian Stok Fisik (Stock Opname)';
        qtyLabel.innerHTML = 'Stok Fisik Aktual Sebenarnya <span class="text-danger">*</span>';
        supSection.classList.add('d-none');
        notesInput.placeholder = 'Contoh: Penyesuaian hasil stock opname gudang';
    }

    updateSelectedProductStockInfo();
    new bootstrap.Modal(document.getElementById('modalStockMovement')).show();
}
</script>
<?php endif; ?>

