<?php
declare(strict_types=1);

$authUser = current_user();
?>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-top py-3 px-4 mt-auto">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center small text-muted gap-2">
                <div>
                    &copy; <?= date('Y') ?> <strong><?= e($storeSettings['store_name'] ?? 'Webcraft POS') ?></strong> &mdash; Sistem Kasir &amp; Inventori Berbasis Web
                </div>
                <div>
                    <span class="badge bg-light text-secondary border">PHP <?= PHP_VERSION ?></span>
                    <span class="badge bg-light text-secondary border">DB: <?= strtoupper(\App\Core\Database::getDriver()) ?></span>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Modal Pengaturan Profil Akun Sendiri -->
<div class="modal fade" id="modalEditProfile" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(route_url('profile_update')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-gear me-2 text-primary"></i>Pengaturan Profil Akun</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Username Login</label>
                    <input type="text" class="form-control bg-light" value="<?= e($authUser['username'] ?? '') ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($authUser['name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alamat Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e($authUser['email'] ?? '') ?>">
                </div>
                <hr>
                <div class="mb-1">
                    <label class="form-label small fw-semibold">Ganti Password Baru (Opsional)</label>
                    <input type="password" name="new_password" class="form-control" minlength="6" placeholder="Kosongkan jika tidak ingin mengubah password">
                    <div class="form-text">Minimal 6 karakter.</div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
<script>
document.addEventListener('keydown', function(e) {
    if (e.key === 'F2') {
        e.preventDefault();
        window.location.href = <?= json_encode(route_url('pos')) ?>;
    }
});
</script>
</body>
</html>

