<?php
declare(strict_types=1);

$storeName = $storeSettings['store_name'] ?? 'Webcraft POS UMKM';
$storeTagline = $storeSettings['store_tagline'] ?? 'Sistem Kasir dan Manajemen Stok Berbasis Web';
$logoPath = $storeSettings['logo_path'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; <?= e($storeName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #1d4ed8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 1.15rem;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
            overflow: hidden;
        }
        .login-header {
            background: #f8fafc;
            padding: 2rem 2rem 1.4rem;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .brand-badge {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #fff;
            font-size: 1.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.85rem;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.35);
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <?php if (!empty($logoPath)): ?>
            <img src="<?= e(asset_url($logoPath)) ?>" alt="Logo Toko" class="rounded mb-2" style="width:64px;height:64px;object-fit:cover;">
        <?php else: ?>
            <div class="brand-badge">
                <i class="bi bi-shop-window"></i>
            </div>
        <?php endif; ?>
        <h1 class="h5 fw-bold text-dark mb-1"><?= e($storeName) ?></h1>
        <p class="text-muted small mb-0"><?= e($storeTagline) ?></p>
    </div>

    <div class="p-4">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show small py-2" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>

        <form method="POST" action="<?= e(route_url('login')) ?>" id="loginForm">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="username" class="form-label small fw-semibold text-secondary">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person text-secondary"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock text-secondary"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePwd" title="Tampilkan Password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
            </button>
        </form>

        <div class="mt-4 pt-3 border-top">
            <div class="small text-muted fw-semibold mb-2 text-center">Akun Default Pengujian (Klik untuk Isi Otomatis):</div>
            <div class="row g-2">
                <div class="col-6">
                    <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="fillDemo('admin', 'admin123')">
                        <i class="bi bi-shield-lock me-1"></i>Role Admin
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="fillDemo('kasir', 'kasir123')">
                        <i class="bi bi-person-workspace me-1"></i>Role Kasir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillDemo(user, pass) {
    document.getElementById('username').value = user;
    document.getElementById('password').value = pass;
}
document.getElementById('togglePwd').addEventListener('click', function () {
    const pwdInput = document.getElementById('password');
    const icon = this.querySelector('i');
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwdInput.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>
</body>
</html>

