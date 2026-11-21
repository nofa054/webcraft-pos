<?php
declare(strict_types=1);
?>
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-person-badge-fill text-primary me-2"></i>Daftar Akun Pengguna Sistem (<?= count($users) ?> Akun)</span>
                <div class="d-flex flex-wrap gap-2">
                    <form method="GET" action="<?= e(base_url('index.php')) ?>" class="d-flex gap-1">
                        <input type="hidden" name="page" value="users">
                        <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>" placeholder="Cari nama / username...">
                        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i></button>
                    </form>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddUser">
                        <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna Baru
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Role Akses</th>
                            <th>Status Akun</th>
                            <th class="text-center">Total Transaksi</th>
                            <th>Login Terakhir</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <?php $isSelf = (int) $u['id'] === \App\Core\Auth::id(); ?>
                            <tr>
                                <td>#<?= (int) $u['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?= e($u['name']) ?>
                                        <?php if ($isSelf): ?>
                                            <span class="badge bg-info text-dark ms-1">Anda</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.72rem;"><?= e($u['email'] ?: '-') ?></div>
                                </td>
                                <td class="fw-semibold text-primary">@<?= e($u['username']) ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge bg-primary"><i class="bi bi-shield-lock me-1"></i>ADMIN</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-person-workspace me-1"></i>KASIR</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int) $u['is_active'] === 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= (int) $u['total_transactions'] ?> Trx</span>
                                </td>
                                <td class="text-muted"><?= e($u['last_login'] ? tanggal_indo($u['last_login'], true) : 'Belum pernah') ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary"
                                                onclick='openEditUserModal(<?= json_encode([
                                                    "id"        => $u["id"],
                                                    "name"      => $u["name"],
                                                    "username"  => $u["username"],
                                                    "email"     => $u["email"],
                                                    "role"      => $u["role"],
                                                    "is_active" => $u["is_active"],
                                                ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                title="Edit Akun">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <?php if (!$isSelf): ?>
                                            <form method="POST" action="<?= e(route_url('user_delete')) ?>" class="d-inline js-confirm-delete"
                                                  data-confirm="Hapus akun pengguna '<?= e($u['name']) ?>'?">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus Akun">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Info Matriks Hak Akses -->
<div class="card bg-light border-0">
    <div class="card-body py-3">
        <div class="fw-bold small mb-2"><i class="bi bi-info-circle-fill text-primary me-1"></i>Informasi Pembagian Hak Akses (Role-Based Access Control):</div>
        <div class="row g-2 small text-muted">
            <div class="col-md-6">
                <strong class="text-dark">Role ADMIN:</strong> Memiliki akses penuh ke Dashboard, Kasir POS, Katalog Produk &amp; Kategori (Tambah/Ubah/Hapus), Manajemen Stok Masuk/Keluar/Opname, Pelanggan &amp; Supplier, Laporan Laba Kotor + Ekspor Excel/PDF, Manajemen Pengguna, serta Pengaturan Toko.
            </div>
            <div class="col-md-6">
                <strong class="text-dark">Role KASIR:</strong> Memiliki akses operasional harian ke Dashboard, Mesin Kasir (POS), Cetak Struk, Riwayat Transaksi, Lihat Katalog &amp; Notifikasi Stok Menipis, serta Pendaftaran/Pengelolaan Pelanggan.
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Pengguna -->
<div class="modal fade" id="modalAddUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(route_url('user_store')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill text-primary me-2"></i>Tambah Akun Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="Nama lengkap staf / admin">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Username Login <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required placeholder="tanpa spasi">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Role Hak Akses <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="kasir">Kasir</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email (Opsional)</label>
                    <input type="email" name="email" class="form-control" placeholder="user@toko.com">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password Login <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" minlength="6" required placeholder="Minimal 6 karakter">
                </div>
                <div class="mb-1">
                    <label class="form-label small fw-semibold">Status Akun</label>
                    <select name="is_active" class="form-select">
                        <option value="1">Aktif (Dapat Login)</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pengguna -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(route_url('user_update')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="editUserId">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Ubah Akun Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editUserName" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Username Login <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="editUserUsername" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Role Hak Akses <span class="text-danger">*</span></label>
                        <select name="role" id="editUserRole" class="form-select" required>
                            <option value="kasir">Kasir</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" id="editUserEmail" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Reset Password Baru (Opsional)</label>
                    <input type="password" name="password" class="form-control" minlength="6" placeholder="Kosongkan jika password tidak diubah">
                </div>
                <div class="mb-1">
                    <label class="form-label small fw-semibold">Status Akun</label>
                    <select name="is_active" id="editUserActive" class="form-select">
                        <option value="1">Aktif (Dapat Login)</option>
                        <option value="0">Nonaktif</option>
                    </select>
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
function openEditUserModal(u) {
    document.getElementById('editUserId').value = u.id;
    document.getElementById('editUserName').value = u.name || '';
    document.getElementById('editUserUsername').value = u.username || '';
    document.getElementById('editUserEmail').value = u.email || '';
    document.getElementById('editUserRole').value = u.role || 'kasir';
    document.getElementById('editUserActive').value = u.is_active;
    new bootstrap.Modal(document.getElementById('modalEditUser')).show();
}
</script>

