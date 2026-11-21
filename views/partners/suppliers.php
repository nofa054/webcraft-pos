<?php
declare(strict_types=1);
?>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-truck text-primary me-2"></i>Data Supplier / Pemasok Barang (<?= count($suppliers) ?>)</span>
        <div class="d-flex flex-wrap gap-2">
            <form method="GET" action="<?= e(base_url('index.php')) ?>" class="d-flex gap-1">
                <input type="hidden" name="page" value="suppliers">
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>" placeholder="Cari supplier / kontak...">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i></button>
            </form>
            <?php if (is_admin()): ?>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddSupplier">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Supplier
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Perusahaan / Supplier</th>
                    <th>Kontak Person</th>
                    <th>No. Telepon</th>
                    <th>Email</th>
                    <th>Alamat</th>
                    <th class="text-center">Jml Produk</th>
                    <?php if (is_admin()): ?>
                        <th class="text-center">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="<?= is_admin() ? 8 : 7 ?>" class="text-center py-4 text-muted">Tidak ada data supplier ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border"><?= e($s['code']) ?></span></td>
                            <td class="fw-bold text-dark"><?= e($s['name']) ?></td>
                            <td><?= e($s['contact_person'] ?: '-') ?></td>
                            <td><?= e($s['phone'] ?: '-') ?></td>
                            <td><?= e($s['email'] ?: '-') ?></td>
                            <td class="text-muted"><?= e($s['address'] ?: '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-info bg-opacity-10 text-info"><?= (int) $s['product_count'] ?> SKU</span>
                            </td>
                            <?php if (is_admin()): ?>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary"
                                                onclick='openEditSupplierModal(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                title="Ubah Supplier">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="POST" action="<?= e(route_url('supplier_delete')) ?>" class="d-inline js-confirm-delete"
                                              data-confirm="Hapus supplier '<?= e($s['name']) ?>'?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus">
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
<!-- Modal Tambah Supplier -->
<div class="modal fade" id="modalAddSupplier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(route_url('supplier_store')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-truck text-primary me-2"></i>Tambah Supplier Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Kode Supplier</label>
                        <input type="text" name="code" class="form-control bg-light" value="<?= e($nextCode) ?>" readonly>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Nama Supplier / Distributor <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="PT / CV / UD...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nama Kontak Person (Sales)</label>
                        <input type="text" name="contact_person" class="form-control" placeholder="Nama PIC">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="phone" class="form-control" placeholder="0811-xxxx-xxxx">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="sales@supplier.co.id">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Alamat Gudang / Kantor</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Supplier -->
<div class="modal fade" id="modalEditSupplier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(route_url('supplier_update')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="editSupId">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Ubah Data Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Supplier <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editSupName" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nama Kontak Person</label>
                        <input type="text" name="contact_person" id="editSupContact" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nomor Telepon</label>
                        <input type="text" name="phone" id="editSupPhone" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" id="editSupEmail" class="form-control">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Alamat</label>
                    <textarea name="address" id="editSupAddress" class="form-control" rows="2"></textarea>
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
function openEditSupplierModal(s) {
    document.getElementById('editSupId').value = s.id;
    document.getElementById('editSupName').value = s.name || '';
    document.getElementById('editSupContact').value = s.contact_person || '';
    document.getElementById('editSupPhone').value = s.phone || '';
    document.getElementById('editSupEmail').value = s.email || '';
    document.getElementById('editSupAddress').value = s.address || '';
    new bootstrap.Modal(document.getElementById('modalEditSupplier')).show();
}
</script>
<?php endif; ?>

