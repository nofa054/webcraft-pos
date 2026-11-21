<?php
declare(strict_types=1);

$totalOmzet = 0.0;
$totalDiskon = 0.0;
$totalLaba = 0.0;
foreach ($sales as $s) {
    $totalOmzet += (float) $s['grand_total'];
    $totalDiskon += (float) $s['discount_amount'];
    $totalLaba += (float) $s['gross_profit'];
}
?>
<!-- Filter Pencarian Riwayat Transaksi -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= e(base_url('index.php')) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="transactions">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold mb-1">Pencarian</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($filters['search']) ?>" placeholder="No. Invoice / Pelanggan / Ref...">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($filters['start_date']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($filters['end_date']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Metode Bayar</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">Semua Metode</option>
                    <option value="tunai" <?= $filters['payment_method'] === 'tunai' ? 'selected' : '' ?>>Tunai</option>
                    <option value="transfer" <?= $filters['payment_method'] === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                    <option value="qris" <?= $filters['payment_method'] === 'qris' ? 'selected' : '' ?>>QRIS</option>
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="<?= e(route_url('transactions')) ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Ringkasan Periode -->
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="card bg-white">
            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted">Jumlah Transaksi</div>
                    <div class="h5 fw-bold mb-0"><?= count($sales) ?> Transaksi</div>
                </div>
                <i class="bi bi-receipt fs-3 text-primary opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card bg-white">
            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted">Total Nilai Penjualan</div>
                    <div class="h5 fw-bold text-primary mb-0"><?= e(rupiah($totalOmzet)) ?></div>
                </div>
                <i class="bi bi-cash-stack fs-3 text-success opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card bg-white">
            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted"><?= is_admin() ? 'Total Keuntungan Kotor' : 'Total Potongan Diskon' ?></div>
                    <div class="h5 fw-bold text-success mb-0">
                        <?= is_admin() ? e(rupiah($totalLaba)) : e(rupiah($totalDiskon)) ?>
                    </div>
                </div>
                <i class="bi bi-graph-up-arrow fs-3 text-info opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Riwayat Transaksi -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-check text-primary me-2"></i>Daftar Riwayat Transaksi</span>
        <a href="<?= e(route_url('pos')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Transaksi Baru
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Tanggal &amp; Waktu</th>
                    <th>Pelanggan</th>
                    <th>Kasir</th>
                    <th class="text-center">Jml Item</th>
                    <th>Metode Bayar</th>
                    <th class="text-end">Diskon</th>
                    <th class="text-end">Total Tagihan</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Tidak ada data transaksi pada rentang filter ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= e($sale['invoice_no']) ?></td>
                            <td><?= e(tanggal_indo($sale['transaction_date'], true)) ?></td>
                            <td><?= e($sale['customer_name']) ?></td>
                            <td><?= e($sale['cashier_name']) ?></td>
                            <td class="text-center"><span class="badge bg-light text-dark border"><?= e(format_qty($sale['total_items'])) ?></span></td>
                            <td>
                                <?php
                                $badge = match ($sale['payment_method']) {
                                    'tunai'    => 'bg-success',
                                    'transfer' => 'bg-primary',
                                    'qris'     => 'bg-info text-dark',
                                    default    => 'bg-secondary',
                                };
                                ?>
                                <span class="badge <?= $badge ?> text-uppercase"><?= e($sale['payment_method']) ?></span>
                                <?php if (!empty($sale['payment_reference'])): ?>
                                    <div class="text-muted" style="font-size: 0.68rem;">Ref: <?= e($sale['payment_reference']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-muted"><?= (float) $sale['discount_amount'] > 0 ? '-' . e(rupiah($sale['discount_amount'])) : '-' ?></td>
                            <td class="text-end fw-bold"><?= e(rupiah($sale['grand_total'])) ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" onclick="showTransactionDetail(<?= (int) $sale['id'] ?>)" title="Lihat Rincian Item">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="<?= e(route_url('receipt', ['id' => $sale['id']])) ?>" target="_blank" class="btn btn-outline-secondary" title="Cetak Struk">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detail Transaksi -->
<div class="modal fade" id="modalTransactionDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Rincian Transaksi <span id="mdlInvoiceNo" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="mdlTransactionBody">
                <div class="text-center py-4"><span class="spinner-border text-primary"></span></div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="mdlBtnPrintReceipt" target="_blank" class="btn btn-primary btn-sm">
                    <i class="bi bi-printer me-1"></i> Buka &amp; Cetak Struk
                </a>
            </div>
        </div>
    </div>
</div>

<script>
async function showTransactionDetail(saleId) {
    const modalEl = document.getElementById('modalTransactionDetail');
    const modal = new bootstrap.Modal(modalEl);
    const bodyEl = document.getElementById('mdlTransactionBody');
    bodyEl.innerHTML = '<div class="text-center py-4"><span class="spinner-border text-primary"></span></div>';
    modal.show();

    const detailUrl = <?= json_encode(route_url('transaction_detail_json')) ?> + '&id=' + encodeURIComponent(saleId);
    const receiptBaseUrl = <?= json_encode(route_url('receipt')) ?> + '&id=' + encodeURIComponent(saleId);
    document.getElementById('mdlBtnPrintReceipt').href = receiptBaseUrl;

    try {
        const resp = await fetch(detailUrl);
        const data = await resp.json();
        if (!data.success) {
            bodyEl.innerHTML = '<div class="alert alert-danger mb-0">' + (data.message || 'Gagal memuat detail.') + '</div>';
            return;
        }
        const s = data.sale;
        document.getElementById('mdlInvoiceNo').textContent = '#' + s.invoice_no;

        let itemsHtml = s.items.map((it, idx) => `
            <tr>
                <td>${idx + 1}</td>
                <td><span class="badge bg-light text-dark border">${it.product_sku}</span></td>
                <td class="fw-semibold">${it.product_name}</td>
                <td class="text-center">${it.qty} ${it.unit}</td>
                <td class="text-end">${formatRupiah(it.selling_price)}</td>
                <td class="text-end fw-bold">${formatRupiah(it.subtotal)}</td>
            </tr>
        `).join('');

        bodyEl.innerHTML = `
            <div class="row g-2 mb-3 small bg-light p-3 rounded border">
                <div class="col-sm-6">
                    <div><span class="text-muted">No. Invoice:</span> <strong>${s.invoice_no}</strong></div>
                    <div><span class="text-muted">Waktu Transaksi:</span> ${s.transaction_date}</div>
                    <div><span class="text-muted">Kasir:</span> ${s.cashier_name}</div>
                </div>
                <div class="col-sm-6">
                    <div><span class="text-muted">Pelanggan:</span> <strong>${s.customer_name}</strong></div>
                    <div><span class="text-muted">Metode Bayar:</span> <span class="badge bg-primary text-uppercase">${s.payment_method}</span> ${s.payment_reference ? '(' + s.payment_reference + ')' : ''}</div>
                    <div><span class="text-muted">Catatan:</span> ${s.notes || '-'}</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered small mb-3">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>SKU</th>
                            <th>Nama Produk</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
            </div>
            <div class="row justify-content-end small">
                <div class="col-sm-6 col-md-5">
                    <div class="d-flex justify-content-between mb-1"><span>Subtotal:</span><span>${formatRupiah(s.subtotal)}</span></div>
                    <div class="d-flex justify-content-between mb-1"><span>Diskon:</span><span class="text-danger">-${formatRupiah(s.discount_amount)}</span></div>
                    <div class="d-flex justify-content-between fw-bold fs-6 border-top pt-1 mb-1"><span>Grand Total:</span><span class="text-primary">${formatRupiah(s.grand_total)}</span></div>
                    <div class="d-flex justify-content-between mb-1"><span>Dibayar:</span><span>${formatRupiah(s.amount_paid)}</span></div>
                    <div class="d-flex justify-content-between fw-semibold text-success"><span>Kembalian:</span><span>${formatRupiah(s.change_amount)}</span></div>
                </div>
            </div>
        `;
    } catch (e) {
        bodyEl.innerHTML = '<div class="alert alert-danger mb-0">Terjadi kesalahan saat mengambil data transaksi.</div>';
    }
}
</script>

