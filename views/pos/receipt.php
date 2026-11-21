<?php
declare(strict_types=1);

$paperWidth = ($storeSettings['receipt_paper_width'] ?? '80mm') === '58mm' ? '58mm' : '80mm';
$maxWidthPx = $paperWidth === '58mm' ? '260px' : '340px';
$showLogo = !empty($storeSettings['show_logo_on_receipt']) && !empty($storeSettings['logo_path']);
$showCashier = !empty($storeSettings['show_cashier_on_receipt']);
$showCustomer = !empty($storeSettings['show_customer_on_receipt']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #<?= e($sale['invoice_no']) ?> &mdash; <?= e($storeSettings['store_name'] ?? 'Webcraft POS') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #cbd5e1;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12.5px;
            color: #000;
            padding: 20px 10px;
        }
        .receipt-container {
            width: 100%;
            max-width: <?= $maxWidthPx ?>;
            margin: 0 auto;
            background: #fff;
            padding: 18px 14px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.18);
        }
        .dashed-line {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .receipt-title {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .receipt-meta {
            font-size: 11.5px;
            line-height: 1.35;
        }
        .item-row {
            margin-bottom: 5px;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
                width: <?= $paperWidth ?>;
                padding: 4px;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<div class="text-center mb-3 no-print">
    <div class="btn-group shadow-sm">
        <button type="button" class="btn btn-primary btn-sm px-3" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i> Cetak Struk (<?= e($paperWidth) ?>)
        </button>
        <a href="<?= e(route_url('pos')) ?>" class="btn btn-success btn-sm px-3">
            <i class="bi bi-cart-plus me-1"></i> Kembali ke Kasir
        </a>
        <a href="<?= e(route_url('transactions')) ?>" class="btn btn-light border btn-sm px-3">
            <i class="bi bi-list-ul me-1"></i> Riwayat
        </a>
    </div>
</div>

<div class="receipt-container">
    <!-- Header Toko -->
    <div class="text-center">
        <?php if ($showLogo): ?>
            <img src="<?= e(asset_url($storeSettings['logo_path'])) ?>" alt="Logo" style="max-height: 52px; max-width: 140px; object-fit: contain;" class="mb-2">
        <?php endif; ?>
        <div class="receipt-title"><?= e($storeSettings['store_name'] ?? 'Webcraft POS') ?></div>
        <?php if (!empty($storeSettings['address'])): ?>
            <div class="receipt-meta"><?= e($storeSettings['address']) ?></div>
        <?php endif; ?>
        <?php if (!empty($storeSettings['phone'])): ?>
            <div class="receipt-meta">Telp: <?= e($storeSettings['phone']) ?></div>
        <?php endif; ?>
        <?php if (!empty($storeSettings['receipt_header'])): ?>
            <div class="receipt-meta mt-1 fst-italic"><?= e($storeSettings['receipt_header']) ?></div>
        <?php endif; ?>
    </div>

    <div class="dashed-line"></div>

    <!-- Metadata Transaksi -->
    <div class="receipt-meta">
        <div class="d-flex justify-content-between">
            <span>No. Struk:</span>
            <strong><?= e($sale['invoice_no']) ?></strong>
        </div>
        <div class="d-flex justify-content-between">
            <span>Tanggal :</span>
            <span><?= e(date('d/m/Y H:i', strtotime($sale['transaction_date']))) ?></span>
        </div>
        <?php if ($showCashier): ?>
            <div class="d-flex justify-content-between">
                <span>Kasir   :</span>
                <span><?= e($sale['cashier_name']) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($showCustomer): ?>
            <div class="d-flex justify-content-between">
                <span>Pelanggan:</span>
                <span><?= e($sale['customer_name']) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashed-line"></div>

    <!-- Daftar Barang -->
    <div>
        <?php foreach ($sale['items'] as $item): ?>
            <div class="item-row">
                <div class="fw-bold"><?= e($item['product_name']) ?></div>
                <div class="d-flex justify-content-between receipt-meta">
                    <span><?= e(format_qty($item['qty'])) ?> <?= e($item['unit']) ?> x <?= e(rupiah($item['selling_price'], false)) ?></span>
                    <span><?= e(rupiah($item['subtotal'], false)) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="dashed-line"></div>

    <!-- Total & Pembayaran -->
    <div class="receipt-meta">
        <div class="d-flex justify-content-between">
            <span>Subtotal</span>
            <span><?= e(rupiah($sale['subtotal'])) ?></span>
        </div>
        <?php if ((float) $sale['discount_amount'] > 0): ?>
            <div class="d-flex justify-content-between">
                <span>Diskon <?= $sale['discount_type'] === 'percent' ? '(' . format_qty($sale['discount_value']) . '%)' : '' ?></span>
                <span>-<?= e(rupiah($sale['discount_amount'])) ?></span>
            </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between fw-bold fs-6 my-1">
            <span>TOTAL</span>
            <span><?= e(rupiah($sale['grand_total'])) ?></span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Bayar (<?= e(strtoupper($sale['payment_method'])) ?>)</span>
            <span><?= e(rupiah($sale['amount_paid'])) ?></span>
        </div>
        <?php if (!empty($sale['payment_reference'])): ?>
            <div class="d-flex justify-content-between">
                <span>Ref Bayar</span>
                <span><?= e($sale['payment_reference']) ?></span>
            </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between fw-bold">
            <span>Kembali</span>
            <span><?= e(rupiah($sale['change_amount'])) ?></span>
        </div>
    </div>

    <?php if (!empty($sale['notes'])): ?>
        <div class="dashed-line"></div>
        <div class="receipt-meta">Catatan: <?= e($sale['notes']) ?></div>
    <?php endif; ?>

    <div class="dashed-line"></div>

    <!-- Footer Struk -->
    <div class="text-center receipt-meta mt-2">
        <?php if (!empty($storeSettings['receipt_footer'])): ?>
            <div><?= nl2br(e($storeSettings['receipt_footer'])) ?></div>
        <?php else: ?>
            <div>Terima kasih atas kunjungan Anda!</div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($autoPrint)): ?>
<script>
window.addEventListener('load', function () {
    setTimeout(function () {
        window.print();
    }, 300);
});
</script>
<?php endif; ?>
</body>
</html>

