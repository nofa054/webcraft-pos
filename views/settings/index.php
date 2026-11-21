<?php
declare(strict_types=1);
?>
<form method="POST" action="<?= e(route_url('settings_update')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row g-3">
        <!-- Kolom Kiri: Profil & Identitas Toko -->
        <div class="col-12 col-lg-7">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-shop text-primary me-2"></i>Profil &amp; Identitas Toko UMKM
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Nama Toko / Usaha <span class="text-danger">*</span></label>
                            <input type="text" name="store_name" class="form-control" value="<?= e($settings['store_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($settings['phone'] ?? '') ?>" placeholder="0812-xxxx-xxxx">
                        </div>

                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Tagline / Slogan Toko</label>
                            <input type="text" name="store_tagline" class="form-control" value="<?= e($settings['store_tagline'] ?? '') ?>" placeholder="Contoh: Retail, Sembako & Kebutuhan Harian">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Alamat Email Toko</label>
                            <input type="email" name="email" class="form-control" value="<?= e($settings['email'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Alamat Lengkap Toko</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Alamat lengkap yang akan dicetak pada kepala struk..."><?= e($settings['address'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Logo Toko (Format: JPG, PNG, WEBP, SVG - Maks. 2MB)</label>
                            <div class="d-flex align-items-center gap-3 p-3 border rounded bg-light">
                                <?php if (!empty($settings['logo_path'])): ?>
                                    <img src="<?= e(asset_url($settings['logo_path'])) ?>" alt="Logo Toko" class="rounded border bg-white p-1" style="width:68px;height:68px;object-fit:contain;">
                                <?php else: ?>
                                    <div class="rounded border bg-white d-flex align-items-center justify-content-center text-secondary" style="width:68px;height:68px;">
                                        <i class="bi bi-image fs-3"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <input type="file" name="store_logo" class="form-control form-control-sm mb-2" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                                    <?php if (!empty($settings['logo_path'])): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="chkRemoveLogo">
                                            <label class="form-check-label small text-danger" for="chkRemoveLogo">
                                                Hapus logo saat ini dan gunakan ikon standar
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Pengaturan Struk Penjualan -->
        <div class="col-12 col-lg-5">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-printer-fill text-primary me-2"></i>Pengaturan Cetakan Struk Kasir
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ukuran Lebar Kertas Printer Thermal</label>
                        <select name="receipt_paper_width" class="form-select">
                            <option value="80mm" <?= ($settings['receipt_paper_width'] ?? '80mm') === '80mm' ? 'selected' : '' ?>>Thermal 80mm (Standar Kasir Retail)</option>
                            <option value="58mm" <?= ($settings['receipt_paper_width'] ?? '') === '58mm' ? 'selected' : '' ?>>Thermal 58mm (Mini / Portable Bluetooth)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Teks Sub-Header Struk (Di bawah alamat)</label>
                        <input type="text" name="receipt_header" class="form-control" value="<?= e($settings['receipt_header'] ?? '') ?>" placeholder="Contoh: Melayani Eceran & Grosir">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan Kaki / Footer Struk</label>
                        <textarea name="receipt_footer" class="form-control" rows="3" placeholder="Contoh: Terima kasih telah berbelanja di toko kami!"><?= e($settings['receipt_footer'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold d-block mb-2">Informasi yang Ditampilkan pada Struk:</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="show_logo_on_receipt" value="1" id="swLogo" <?= !empty($settings['show_logo_on_receipt']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="swLogo">Tampilkan Logo Toko di bagian atas struk</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="show_cashier_on_receipt" value="1" id="swCashier" <?= !empty($settings['show_cashier_on_receipt']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="swCashier">Tampilkan Nama Kasir yang melayani</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_customer_on_receipt" value="1" id="swCustomer" <?= !empty($settings['show_customer_on_receipt']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="swCustomer">Tampilkan Nama Pelanggan pada struk</label>
                        </div>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                        <i class="bi bi-save2 me-1"></i> Simpan Pengaturan Toko &amp; Struk
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

