<?php
declare(strict_types=1);
?>
<div class="row g-3">
    <?php if (is_admin()): ?>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-plus-circle-fill text-primary me-2"></i>Tambah Kategori Baru
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= e(route_url('category_store')) ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="Contoh: Sembako, Alat Bangunan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Keterangan / Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi singkat pengelompokan produk..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 btn-sm py-2 fw-semibold">
                            <i class="bi bi-check-lg me-1"></i> Simpan Kategori
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="col-12 <?= is_admin() ? 'col-lg-8' : '' ?>">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-tags-fill text-primary me-2"></i>Daftar Kategori Produk (<?= count($categories) ?>)</span>
                <form method="GET" action="<?= e(base_url('index.php')) ?>" class="d-flex gap-1">
                    <input type="hidden" name="page" value="categories">
                    <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>" placeholder="Cari kategori...">
                    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th class="text-center">Jumlah Produk</th>
                            <th class="text-center">Total Unit Stok</th>
                            <?php if (is_admin()): ?>
                                <th class="text-center">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="<?= is_admin() ? 6 : 5 ?>" class="text-center py-4 text-muted">Tidak ada data kategori ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $idx => $cat): ?>
                                <?php $prodCount = (int) $cat['product_count']; ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td class="fw-bold text-dark">
                                        <a href="<?= e(route_url('products', ['category_id' => $cat['id']])) ?>" class="text-decoration-none">
                                            <?= e($cat['name']) ?>
                                        </a>
                                    </td>
                                    <td class="text-muted"><?= e($cat['description'] ?: '-') ?></td>
                                    <td class="text-center">
                                        <a href="<?= e(route_url('products', ['category_id' => $cat['id']])) ?>" class="badge bg-primary bg-opacity-10 text-primary text-decoration-none">
                                            <?= $prodCount ?> Produk
                                        </a>
                                    </td>
                                    <td class="text-center fw-semibold"><?= e(format_qty($cat['total_stock'])) ?></td>
                                    <?php if (is_admin()): ?>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary"
                                                        onclick='openEditCategoryModal(<?= json_encode($cat, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                        title="Ubah Kategori">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <?php if ($prodCount === 0): ?>
                                                    <form method="POST" action="<?= e(route_url('category_delete')) ?>" class="d-inline js-confirm-delete"
                                                          data-confirm="Hapus kategori '<?= e($cat['name']) ?>'?">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus Kategori">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Kategori sedang digunakan oleh <?= $prodCount ?> produk">
                                                        <i class="bi bi-lock"></i>
                                                    </button>
                                                <?php endif; ?>
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
    </div>
</div>

<?php if (is_admin()): ?>
<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEditCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(route_url('category_update')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="editCatId">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Ubah Kategori Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editCatName" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Keterangan / Deskripsi</label>
                    <textarea name="description" id="editCatDesc" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditCategoryModal(cat) {
    document.getElementById('editCatId').value = cat.id;
    document.getElementById('editCatName').value = cat.name || '';
    document.getElementById('editCatDesc').value = cat.description || '';
    new bootstrap.Modal(document.getElementById('modalEditCategory')).show();
}
</script>
<?php endif; ?>

