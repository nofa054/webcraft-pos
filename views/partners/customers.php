<?php
declare(strict_types=1);
?>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-people-fill text-primary me-2"></i>Data Pelanggan (<?= count($customers) ?> Pelanggan)</span>
        <div class="d-flex flex-wrap gap-2">
            <form method="GET" action="<?= e(base_url('index.php')) ?>" class="d-flex gap-1">
                <input type="hidden" name="page" value="customers">
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>" placeholder="Cari nama / telepon...">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i></button>
            </form>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddCustomer">
                <i class="bi bi-person-plus-fill me-1"></i> Tambah Pelanggan
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Pelanggan</th>
                    <th>No. Telepon</th>
                    <th>Alamat</th>
                    <th>Catatan</th>
                    <th class="text-center">Total Transaksi</th>
                    <th class="text-end">Total Belanja</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Tidak ada data pelanggan ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border"><?= e($c['code']) ?></span></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($c['name']) ?></div>
                                <?php if (!empty($c['email']) && $c['email'] !== '-'): ?>
                                    <div class="text-muted" style="font-size:0.7rem;"><?= e($c['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($c['phone'] ?: '-') ?></td>
                            <td><?= e($c['address'] ?: '-') ?></td>
                            <td class="text-muted"><?= e($c['notes'] ?: '-') ?></td>
                            <td class="text-center"><span class="badge bg-primary bg-opacity-10 text-primary"><?= (int) $c['total_transactions'] ?>x</span></td>
                            <td class="text-end fw-semibold"><?= e(rupiah($c['total_spent'])) ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary"
                                            onclick='openEditCustomerModal(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            title="Ubah Pelanggan">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <?php if (is_admin() && (int) $c['id'] !== 1): ?>
                                        <form method="POST" action="<?= e(route_url('customer_delete')) ?>" class="d-inline js-confirm-delete"
                                              data-confirm="Hapus pelanggan '<?= e($c['name']) ?>'?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pelanggan -->
<div class="modal fade" id="modalAddCustomer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(route_url('customer_store')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill text-primary me-2"></i>Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Kode Pelanggan</label>
                        <input type="text" name="code" class="form-control bg-light" value="<?= e($nextCode) ?>" readonly>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Nama lengkap / nama toko">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nomor Telepon / HP</label>
                        <input type="text" name="phone" class="form-control" placeholder="0812-xxxx-xxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email (Opsional)</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Alamat Lengkap</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Catatan Tambahan</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: Pelanggan grosir / langganan">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Pelanggan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pelanggan -->
<div class="modal fade" id="modalEditCustomer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(route_url('customer_update')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="editCustId">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Ubah Data Pelanggan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Pelanggan <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editCustName" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nomor Telepon</label>
                        <input type="text" name="phone" id="editCustPhone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" id="editCustEmail" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alamat</label>
                    <textarea name="address" id="editCustAddress" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Catatan</label>
                    <input type="text" name="notes" id="editCustNotes" class="form-control">
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
function openEditCustomerModal(c) {
    document.getElementById('editCustId').value = c.id;
    document.getElementById('editCustName').value = c.name || '';
    document.getElementById('editCustPhone').value = c.phone || '';
    document.getElementById('editCustEmail').value = (c.email && c.email !== '-') ? c.email : '';
    document.getElementById('editCustAddress').value = (c.address && c.address !== '-') ? c.address : '';
    document.getElementById('editCustNotes').value = c.notes || '';
    new bootstrap.Modal(document.getElementById('modalEditCustomer')).show();
}
</script>

