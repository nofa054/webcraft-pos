<?php
declare(strict_types=1);

$units = ['pcs', 'kg', 'liter', 'dus', 'sak', 'btl', 'rcg', 'klg', 'bks', 'pak', 'lusin', 'meter', 'set'];
?>
<!-- Filter & Pencarian Produk -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(base_url('index.php')) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="products">
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold mb-1">Cari Produk</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" value="<?= e($filters['search']) ?>" placeholder="Nama produk, SKU, atau Barcode...">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold mb-1">Kategori</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="0">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (int) $filters['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold mb-1">Status Stok</label>
                <select name="stock_status" class="form-select form-select-sm">
                    <option value="">Semua Status Stok</option>
                    <option value="safe" <?= $filters['stock_status'] === 'safe' ? 'selected' : '' ?>>Stok Aman</option>
                    <option value="alert" <?= $filters['stock_status'] === 'alert' ? 'selected' : '' ?>>Stok Menipis &amp; Habis</option>
                    <option value="low" <?= $filters['stock_status'] === 'low' ? 'selected' : '' ?>>Stok Menipis</option>
                    <option value="out" <?= $filters['stock_status'] === 'out' ? 'selected' : '' ?>>Stok Habis (0)</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="<?= e(route_url('products')) ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Daftar Produk -->
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <i class="bi bi-box-seam-fill text-primary me-2"></i>Katalog Data Produk (<?= count($products) ?> Item)
        </div>
        <?php if (is_admin()): ?>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddProduct">
                <i class="bi bi-plus-lg me-1"></i> Tambah Produk Baru
            </button>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead>
                <tr>
                    <th>SKU / Barcode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th class="text-end">Harga Modal</th>
                    <th class="text-end">Harga Jual</th>
                    <th class="text-center">Stok</th>
                    <th class="text-center">Min. Stok</th>
                    <th class="text-center">Status</th>
                    <?php if (is_admin()): ?>
                        <th class="text-center">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="<?= is_admin() ? 9 : 8 ?>" class="text-center py-5 text-muted">
                            <i class="bi bi-box2 fs-2 d-block mb-2"></i>
                            Belum ada data produk yang sesuai dengan pencarian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $prod): ?>
                        <?php
                        $stock = (float) $prod['stock'];
                        $minStock = (float) $prod['min_stock'];
                        $margin = (float) $prod['selling_price'] - (float) $prod['cost_price'];
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= e($prod['sku']) ?></div>
                                <?php if (!empty($prod['barcode'])): ?>
                                    <div class="text-muted" style="font-size:0.72rem;"><i class="bi bi-upc me-1"></i><?= e($prod['barcode']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($prod['name']) ?></div>
                                <?php if (!empty($prod['supplier_name'])): ?>
                                    <div class="text-muted" style="font-size:0.72rem;">Supplier: <?= e($prod['supplier_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-secondary border"><?= e($prod['category_name']) ?></span></td>
                            <td class="text-end text-muted"><?= e(rupiah($prod['cost_price'])) ?></td>
                            <td class="text-end">
                                <div class="fw-bold text-primary"><?= e(rupiah($prod['selling_price'])) ?></div>
                                <div class="text-success" style="font-size:0.68rem;">Margin: +<?= e(rupiah($margin)) ?></div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold <?= $stock <= $minStock ? 'text-danger' : 'text-dark' ?>">
                                    <?= e(format_qty($stock)) ?>
                                </span>
                                <span class="text-muted"><?= e($prod['unit']) ?></span>
                            </td>
                            <td class="text-center text-muted"><?= e(format_qty($minStock)) ?> <?= e($prod['unit']) ?></td>
                            <td class="text-center">
                                <?php if ((int) $prod['is_active'] !== 1): ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php elseif ($stock <= 0): ?>
                                    <span class="badge bg-danger">Stok Habis</span>
                                <?php elseif ($stock <= $minStock): ?>
                                    <span class="badge bg-warning text-dark">Menipis</span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success">Tersedia</span>
                                <?php endif; ?>
                            </td>
                            <?php if (is_admin()): ?>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary"
                                                onclick='openEditProductModal(<?= json_encode($prod, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                title="Edit Produk">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="POST" action="<?= e(route_url('product_delete')) ?>" class="d-inline js-confirm-delete"
                                              data-confirm="Hapus produk '<?= e($prod['name']) ?>' dari sistem?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $prod['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus Produk">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (is_admin()): ?>
<!-- Modal Tambah Produk -->
<div class="modal fade" id="modalAddProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= e(route_url('product_store')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Tambah Produk Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Kode Produk / SKU <span class="text-danger">*</span></label>
                        <input type="text" name="sku" class="form-control" value="<?= e($nextSku) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Barcode Produk (Opsional)</label>
                        <input type="text" name="barcode" class="form-control" placeholder="Scan / ketik kode EAN-13">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Satuan Jual <span class="text-danger">*</span></label>
                        <input type="text" name="unit" class="form-control" list="unitOptions" value="pcs" required placeholder="pcs, kg, liter, dus...">
                        <datalist id="unitOptions">
                            <?php foreach ($units as $u): ?>
                                <option value="<?= e($u) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Contoh: Beras Pandan Wangi 5kg">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Kategori Produk <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Harga Modal / Beli (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="cost_price" class="form-control" min="0" step="any" value="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Harga Jual Kasir (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="selling_price" class="form-control" min="0" step="any" value="0" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Stok Awal</label>
                        <input type="number" name="stock" class="form-control" min="0" step="any" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Batas Minimum Stok</label>
                        <input type="number" name="min_stock" class="form-control" min="0" step="any" value="5">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Supplier Utama (Opsional)</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">-- Tanpa Supplier --</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= (int) $sup['id'] ?>"><?= e($sup['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Produk -->
<div class="modal fade" id="modalEditProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= e(route_url('product_update')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="editProdId">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Ubah Data Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Kode Produk / SKU <span class="text-danger">*</span></label>
                        <input type="text" name="sku" id="editProdSku" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Barcode Produk</label>
                        <input type="text" name="barcode" id="editProdBarcode" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Satuan Jual <span class="text-danger">*</span></label>
                        <input type="text" name="unit" id="editProdUnit" class="form-control" list="unitOptions" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editProdName" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Kategori Produk <span class="text-danger">*</span></label>
                        <select name="category_id" id="editProdCategory" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Harga Modal / Beli (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="cost_price" id="editProdCost" class="form-control" min="0" step="any" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Harga Jual Kasir (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="selling_price" id="editProdSelling" class="form-control" min="0" step="any" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Batas Minimum Stok</label>
                        <input type="number" name="min_stock" id="editProdMinStock" class="form-control" min="0" step="any" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Supplier Utama</label>
                        <select name="supplier_id" id="editProdSupplier" class="form-select">
                            <option value="">-- Tanpa Supplier --</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= (int) $sup['id'] ?>"><?= e($sup['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status Produk</label>
                        <select name="is_active" id="editProdActive" class="form-select">
                            <option value="1">Aktif (Tampil di Kasir)</option>
                            <option value="0">Nonaktif (Arsip)</option>
                        </select>
                    </div>
                </div>
                <div class="alert alert-info small mb-0 mt-3 py-2">
                    <i class="bi bi-info-circle me-1"></i>Untuk menambah atau menyesuaikan jumlah stok fisik, gunakan menu <a href="<?= e(route_url('stock')) ?>" class="fw-semibold">Manajemen Stok</a> agar riwayat mutasi tercatat otomatis.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditProductModal(prod) {
    document.getElementById('editProdId').value = prod.id;
    document.getElementById('editProdSku').value = prod.sku || '';
    document.getElementById('editProdBarcode').value = prod.barcode || '';
    document.getElementById('editProdUnit').value = prod.unit || 'pcs';
    document.getElementById('editProdName').value = prod.name || '';
    document.getElementById('editProdCategory').value = prod.category_id;
    document.getElementById('editProdCost').value = prod.cost_price;
    document.getElementById('editProdSelling').value = prod.selling_price;
    document.getElementById('editProdMinStock').value = prod.min_stock;
    document.getElementById('editProdSupplier').value = prod.supplier_id || '';
    document.getElementById('editProdActive').value = prod.is_active;

    new bootstrap.Modal(document.getElementById('modalEditProduct')).show();
}
</script>
<?php endif; ?>

