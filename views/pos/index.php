<?php
declare(strict_types=1);
?>
<div class="row g-3">
    <!-- Kolom Kiri: Pencarian, Filter Kategori & Katalog Produk -->
    <div class="col-12 col-lg-7 col-xl-8">
        <div class="card mb-3">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-7">
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white"><i class="bi bi-upc-scan"></i></span>
                            <input type="text" id="posSearchInput" class="form-control" placeholder="Cari nama produk, SKU, atau scan Barcode (Tekan Enter)..." autofocus>
                            <button class="btn btn-outline-secondary" type="button" id="btnClearSearch" title="Reset Pencarian">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-5">
                        <select id="posCategoryFilter" class="form-select">
                            <option value="0">Semua Kategori Produk</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>"><?= e($cat['name']) ?> (<?= (int) $cat['product_count'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Produk -->
        <div class="pos-product-grid">
            <div class="row g-2" id="posProductContainer">
                <?php foreach ($products as $prod): ?>
                    <?php
                    $stock = (float) $prod['stock'];
                    $minStock = (float) $prod['min_stock'];
                    $isOut = $stock <= 0;
                    $isLow = !$isOut && $stock <= $minStock;
                    ?>
                    <div class="col-6 col-sm-4 col-md-4 col-xl-3 pos-product-item"
                         data-id="<?= (int) $prod['id'] ?>"
                         data-sku="<?= e(strtolower($prod['sku'])) ?>"
                         data-barcode="<?= e(strtolower((string) ($prod['barcode'] ?? ''))) ?>"
                         data-name="<?= e(strtolower($prod['name'])) ?>"
                         data-category="<?= (int) $prod['category_id'] ?>">
                        <div class="pos-product-card p-3 d-flex flex-column justify-content-between <?= $isOut ? 'out-of-stock' : '' ?>"
                             onclick="posAddToCart(<?= (int) $prod['id'] ?>)">
                            <div>
                                <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                                    <span class="badge bg-light text-secondary border" style="font-size:0.68rem;"><?= e($prod['sku']) ?></span>
                                    <?php if ($isOut): ?>
                                        <span class="badge bg-danger" style="font-size:0.68rem;">Habis</span>
                                    <?php elseif ($isLow): ?>
                                        <span class="badge bg-warning text-dark" style="font-size:0.68rem;">Stok: <?= e(format_qty($stock)) ?> <?= e($prod['unit']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success" style="font-size:0.68rem;">Stok: <?= e(format_qty($stock)) ?> <?= e($prod['unit']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="fw-semibold text-dark small mb-1" style="line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= e($prod['name']) ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= e($prod['category_name']) ?></div>
                            </div>
                            <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-primary" style="font-size: 0.92rem;"><?= e(rupiah($prod['selling_price'])) ?></span>
                                <span class="text-muted" style="font-size: 0.72rem;">/ <?= e($prod['unit']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="posEmptySearch" class="card p-5 text-center text-muted d-none">
                <i class="bi bi-search fs-1 mb-2"></i>
                <p class="mb-0">Produk yang dicari tidak ditemukan.</p>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Keranjang Belanja & Pembayaran -->
    <div class="col-12 col-lg-5 col-xl-4">
        <div class="card pos-cart-panel shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-cart4 fs-5"></i>
                    <div>
                        <div class="fw-bold small">Keranjang Transaksi</div>
                        <div style="font-size: 0.7rem; opacity: 0.85;">Est. No: <?= e($nextInvoicePreview) ?></div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-light py-0 px-2 fw-semibold" id="btnClearCart" title="Kosongkan Keranjang">
                    <i class="bi bi-trash3 text-danger me-1"></i>Reset
                </button>
            </div>

            <div class="card-body p-3">
                <!-- Pilih Pelanggan -->
                <div class="mb-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-person-check"></i></span>
                        <select id="posCustomerId" class="form-select">
                            <?php foreach ($customers as $cust): ?>
                                <option value="<?= (int) $cust['id'] ?>">
                                    <?= e($cust['name']) ?><?= (!empty($cust['phone']) && $cust['phone'] !== '-') ? ' (' . e($cust['phone']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalQuickCustomer" title="Tambah Pelanggan Baru">
                            <i class="bi bi-person-plus"></i>
                        </button>
                    </div>
                </div>

                <!-- Daftar Item di Keranjang -->
                <div class="cart-items-container border rounded mb-3 bg-light bg-opacity-50" id="cartItemsWrapper">
                    <div class="p-4 text-center text-muted small" id="cartEmptyState">
                        <i class="bi bi-basket2 fs-2 d-block mb-1 text-secondary"></i>
                        Keranjang belanja masih kosong.<br>Klik produk atau scan barcode untuk menambahkan.
                    </div>
                    <table class="table table-sm align-middle mb-0 small d-none" id="cartTable">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-center" style="width: 115px;">Qty</th>
                                <th class="text-end">Subtotal</th>
                                <th style="width: 30px;"></th>
                            </tr>
                        </thead>
                        <tbody id="cartTableBody"></tbody>
                    </table>
                </div>

                <!-- Perhitungan Diskon & Total -->
                <div class="border-top pt-2">
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">Subtotal (<span id="cartTotalItems">0</span> item)</span>
                        <span class="fw-semibold" id="cartSubtotalText">Rp 0</span>
                    </div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-5">
                            <label class="small text-muted mb-0">Diskon Transaksi</label>
                        </div>
                        <div class="col-7">
                            <div class="input-group input-group-sm">
                                <select id="posDiscountType" class="form-select" style="max-width: 72px;">
                                    <option value="nominal">Rp</option>
                                    <option value="percent">%</option>
                                </select>
                                <input type="number" id="posDiscountValue" class="form-control text-end" value="0" min="0" step="any">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-2 rounded bg-primary bg-opacity-10 mb-3">
                        <span class="fw-bold text-dark">TOTAL TAGIHAN</span>
                        <span class="h4 fw-bold text-primary mb-0" id="cartGrandTotalText">Rp 0</span>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Metode Pembayaran</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="posPaymentMethod" id="pmTunai" value="tunai" checked>
                            <label class="btn btn-outline-primary btn-sm" for="pmTunai"><i class="bi bi-cash-stack me-1"></i>Tunai</label>

                            <input type="radio" class="btn-check" name="posPaymentMethod" id="pmTransfer" value="transfer">
                            <label class="btn btn-outline-primary btn-sm" for="pmTransfer"><i class="bi bi-bank me-1"></i>Transfer</label>

                            <input type="radio" class="btn-check" name="posPaymentMethod" id="pmQris" value="qris">
                            <label class="btn btn-outline-primary btn-sm" for="pmQris"><i class="bi bi-qr-code-scan me-1"></i>QRIS</label>
                        </div>
                    </div>

                    <!-- Input Referensi untuk Transfer / QRIS -->
                    <div class="mb-2 d-none" id="paymentRefWrapper">
                        <input type="text" id="posPaymentRef" class="form-control form-control-sm" placeholder="No. Referensi Transfer / RRN QRIS (Opsional)">
                    </div>

                    <!-- Jumlah Uang Diterima -->
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="posAmountPaid" class="form-label small fw-semibold mb-0">Jumlah Uang Diterima (Rp)</label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btnExactCash" style="font-size:0.75rem;">Uang Pas</button>
                        </div>
                        <input type="number" id="posAmountPaid" class="form-control fw-bold text-end fs-5" placeholder="0" min="0" step="any">
                    </div>

                    <!-- Tombol Nominal Uang Cepat -->
                    <div class="d-flex flex-wrap gap-1 mb-3" id="quickCashButtons">
                        <button type="button" class="btn btn-light border btn-sm flex-fill quick-cash-btn" data-amount="20000">20.000</button>
                        <button type="button" class="btn btn-light border btn-sm flex-fill quick-cash-btn" data-amount="50000">50.000</button>
                        <button type="button" class="btn btn-light border btn-sm flex-fill quick-cash-btn" data-amount="100000">100.000</button>
                        <button type="button" class="btn btn-light border btn-sm flex-fill quick-cash-btn" data-amount="200000">200.000</button>
                    </div>

                    <!-- Uang Kembalian -->
                    <div class="d-flex justify-content-between align-items-center p-2 rounded border bg-light mb-2">
                        <span class="small fw-semibold text-secondary">Uang Kembalian</span>
                        <span class="fw-bold fs-5 text-success" id="cartChangeText">Rp 0</span>
                    </div>

                    <div class="mb-3">
                        <input type="text" id="posNotes" class="form-control form-control-sm" placeholder="Catatan transaksi (opsional)...">
                    </div>

                    <button type="button" id="btnSubmitTransaction" class="btn btn-success w-100 py-2 fw-bold shadow-sm" disabled>
                        <i class="bi bi-check2-circle me-1"></i> Proses Pembayaran &amp; Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Pelanggan Cepat dari Kasir -->
<div class="modal fade" id="modalQuickCustomer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(route_url('customer_store')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill text-primary me-2"></i>Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Pelanggan <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="Contoh: Toko Berkah / Ibu Sari">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" class="form-control" placeholder="0812xxxx">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Alamat Singkat</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Pelanggan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sukses Transaksi & Cetak Struk -->
<div class="modal fade" id="modalTransactionSuccess" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 68px; height: 68px; font-size: 2.2rem;">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Transaksi Berhasil!</h4>
                <p class="text-muted small mb-3">Nomor Invoice: <strong class="text-primary" id="successInvoiceNo">-</strong></p>

                <div class="bg-light rounded p-3 mb-4 text-start small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Total Tagihan:</span>
                        <span class="fw-bold" id="successGrandTotal">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Jumlah Dibayar:</span>
                        <span class="fw-semibold" id="successAmountPaid">Rp 0</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Uang Kembalian:</span>
                        <span class="fs-5 fw-bold text-success" id="successChangeAmount">Rp 0</span>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="#" id="btnPrintReceiptModal" target="_blank" class="btn btn-primary py-2 fw-semibold">
                        <i class="bi bi-printer-fill me-1"></i> Cetak Struk Penjualan
                    </a>
                    <button type="button" class="btn btn-outline-secondary py-2" onclick="window.location.reload()">
                        <i class="bi bi-plus-circle me-1"></i> Transaksi Baru
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const POS_PRODUCTS = <?= json_encode(array_map(function ($p) {
    return [
        'id'            => (int) $p['id'],
        'sku'           => $p['sku'],
        'barcode'       => $p['barcode'] ?? '',
        'name'          => $p['name'],
        'unit'          => $p['unit'],
        'selling_price' => (float) $p['selling_price'],
        'stock'         => (float) $p['stock'],
    ];
}, $products)) ?>;

const POS_CHECKOUT_URL = <?= json_encode(route_url('pos_checkout')) ?>;
const POS_CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;

let posCart = [];

function findProductById(id) {
    return POS_PRODUCTS.find(p => p.id === Number(id));
}

window.posAddToCart = function (productId) {
    const prod = findProductById(productId);
    if (!prod) return;

    if (prod.stock <= 0) {
        alert('Maaf, stok produk "' + prod.name + '" sedang habis!');
        return;
    }

    const existing = posCart.find(item => item.product_id === prod.id);
    if (existing) {
        if (existing.qty + 1 > prod.stock) {
            alert('Jumlah melebihi stok tersedia (' + prod.stock + ' ' + prod.unit + ')!');
            return;
        }
        existing.qty = Math.round((existing.qty + 1) * 100) / 100;
    } else {
        posCart.push({
            product_id: prod.id,
            sku: prod.sku,
            name: prod.name,
            unit: prod.unit,
            price: prod.selling_price,
            max_stock: prod.stock,
            qty: 1
        });
    }
    renderPosCart();
};

window.posUpdateQty = function (productId, newQty) {
    const item = posCart.find(i => i.product_id === Number(productId));
    if (!item) return;

    let parsed = parseFloat(newQty);
    if (isNaN(parsed) || parsed <= 0) {
        posRemoveItem(productId);
        return;
    }
    if (parsed > item.max_stock) {
        alert('Stok maksimal untuk "' + item.name + '" adalah ' + item.max_stock + ' ' + item.unit);
        parsed = item.max_stock;
    }
    item.qty = Math.round(parsed * 100) / 100;
    renderPosCart();
};

window.posStepQty = function (productId, delta) {
    const item = posCart.find(i => i.product_id === Number(productId));
    if (!item) return;
    posUpdateQty(productId, Math.round((item.qty + delta) * 100) / 100);
};

window.posRemoveItem = function (productId) {
    posCart = posCart.filter(i => i.product_id !== Number(productId));
    renderPosCart();
};

function calculateTotals() {
    let subtotal = 0;
    let totalItems = 0;
    posCart.forEach(item => {
        subtotal += item.qty * item.price;
        totalItems += item.qty;
    });

    const discType = document.getElementById('posDiscountType').value;
    let discVal = parseFloat(document.getElementById('posDiscountValue').value) || 0;
    if (discVal < 0) discVal = 0;

    let discAmount = 0;
    if (discType === 'percent') {
        if (discVal > 100) discVal = 100;
        discAmount = Math.round(subtotal * (discVal / 100));
    } else {
        discAmount = Math.min(subtotal, discVal);
    }

    const grandTotal = Math.max(0, subtotal - discAmount);
    const paymentMethod = document.querySelector('input[name="posPaymentMethod"]:checked').value;
    const paidInput = document.getElementById('posAmountPaid');

    if (paymentMethod !== 'tunai') {
        paidInput.value = grandTotal > 0 ? grandTotal : '';
    }

    const amountPaid = parseFloat(paidInput.value) || 0;
    const change = amountPaid - grandTotal;

    return {
        subtotal,
        totalItems,
        discAmount,
        grandTotal,
        amountPaid,
        change
    };
}

function renderPosCart() {
    const emptyState = document.getElementById('cartEmptyState');
    const cartTable = document.getElementById('cartTable');
    const tbody = document.getElementById('cartTableBody');
    const btnSubmit = document.getElementById('btnSubmitTransaction');

    if (posCart.length === 0) {
        emptyState.classList.remove('d-none');
        cartTable.classList.add('d-none');
        tbody.innerHTML = '';
        btnSubmit.disabled = true;
    } else {
        emptyState.classList.add('d-none');
        cartTable.classList.remove('d-none');
        btnSubmit.disabled = false;

        tbody.innerHTML = posCart.map(item => {
            const lineTotal = item.qty * item.price;
            return `
                <tr>
                    <td>
                        <div class="fw-semibold text-dark text-truncate" style="max-width: 135px;" title="${item.name}">${item.name}</div>
                        <div class="text-muted" style="font-size:0.7rem;">${formatRupiah(item.price)} / ${item.unit}</div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary px-2" type="button" onclick="posStepQty(${item.product_id}, -1)">-</button>
                            <input type="number" class="form-control text-center px-1" value="${item.qty}" min="0.1" max="${item.max_stock}" step="any"
                                   onchange="posUpdateQty(${item.product_id}, this.value)">
                            <button class="btn btn-outline-secondary px-2" type="button" onclick="posStepQty(${item.product_id}, 1)">+</button>
                        </div>
                    </td>
                    <td class="text-end fw-semibold">${formatRupiah(lineTotal)}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="posRemoveItem(${item.product_id})">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    updateSummaryDisplay();
}

function updateSummaryDisplay() {
    const t = calculateTotals();
    document.getElementById('cartTotalItems').textContent = Math.round(t.totalItems * 100) / 100;
    document.getElementById('cartSubtotalText').textContent = formatRupiah(t.subtotal);
    document.getElementById('cartGrandTotalText').textContent = formatRupiah(t.grandTotal);

    const changeEl = document.getElementById('cartChangeText');
    if (t.grandTotal === 0) {
        changeEl.textContent = 'Rp 0';
        changeEl.className = 'fw-bold fs-5 text-secondary';
    } else if (t.change < 0) {
        changeEl.textContent = 'Kurang ' + formatRupiah(Math.abs(t.change));
        changeEl.className = 'fw-bold fs-6 text-danger';
    } else {
        changeEl.textContent = formatRupiah(t.change);
        changeEl.className = 'fw-bold fs-5 text-success';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('posSearchInput');
    const categoryFilter = document.getElementById('posCategoryFilter');
    const productItems = document.querySelectorAll('.pos-product-item');
    const emptySearch = document.getElementById('posEmptySearch');

    function filterCatalog() {
        const q = searchInput.value.trim().toLowerCase();
        const catId = Number(categoryFilter.value);
        let visibleCount = 0;

        productItems.forEach(el => {
            const name = el.dataset.name || '';
            const sku = el.dataset.sku || '';
            const barcode = el.dataset.barcode || '';
            const itemCat = Number(el.dataset.category);

            const matchText = q === '' || name.includes(q) || sku.includes(q) || barcode.includes(q);
            const matchCat = catId === 0 || itemCat === catId;

            if (matchText && matchCat) {
                el.classList.remove('d-none');
                visibleCount++;
            } else {
                el.classList.add('d-none');
            }
        });

        if (visibleCount === 0) {
            emptySearch.classList.remove('d-none');
        } else {
            emptySearch.classList.add('d-none');
        }
    }

    searchInput.addEventListener('input', filterCatalog);
    categoryFilter.addEventListener('change', filterCatalog);

    // Mendukung Barcode Scanner: Tekan Enter pada kolom pencarian langsung menambahkan produk jika kode cocok
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const q = searchInput.value.trim().toLowerCase();
            if (q === '') return;

            const exactMatch = POS_PRODUCTS.find(
                p => (p.barcode && p.barcode.toLowerCase() === q) || p.sku.toLowerCase() === q
            );
            if (exactMatch) {
                posAddToCart(exactMatch.id);
                searchInput.value = '';
                filterCatalog();
                return;
            }

            // Jika hanya ada 1 produk yang tampil di hasil filter, tambahkan produk tersebut
            const visibleEls = Array.from(productItems).filter(el => !el.classList.contains('d-none'));
            if (visibleEls.length === 1) {
                posAddToCart(Number(visibleEls[0].dataset.id));
                searchInput.value = '';
                filterCatalog();
            }
        }
    });

    document.getElementById('btnClearSearch').addEventListener('click', function () {
        searchInput.value = '';
        categoryFilter.value = '0';
        filterCatalog();
        searchInput.focus();
    });

    document.getElementById('btnClearCart').addEventListener('click', function () {
        if (posCart.length === 0) return;
        if (confirm('Kosongkan seluruh item di keranjang belanja?')) {
            posCart = [];
            document.getElementById('posDiscountValue').value = '0';
            document.getElementById('posAmountPaid').value = '';
            renderPosCart();
        }
    });

    document.getElementById('posDiscountType').addEventListener('change', updateSummaryDisplay);
    document.getElementById('posDiscountValue').addEventListener('input', updateSummaryDisplay);
    document.getElementById('posAmountPaid').addEventListener('input', updateSummaryDisplay);

    document.querySelectorAll('input[name="posPaymentMethod"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const refWrapper = document.getElementById('paymentRefWrapper');
            if (this.value === 'tunai') {
                refWrapper.classList.add('d-none');
            } else {
                refWrapper.classList.remove('d-none');
            }
            updateSummaryDisplay();
        });
    });

    document.getElementById('btnExactCash').addEventListener('click', function () {
        const t = calculateTotals();
        document.getElementById('posAmountPaid').value = t.grandTotal;
        updateSummaryDisplay();
    });

    document.querySelectorAll('.quick-cash-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('posAmountPaid').value = this.dataset.amount;
            updateSummaryDisplay();
        });
    });

    // Submit Transaksi
    document.getElementById('btnSubmitTransaction').addEventListener('click', async function () {
        if (posCart.length === 0) return;
        const t = calculateTotals();

        if (t.amountPaid < t.grandTotal) {
            alert('Jumlah pembayaran masih kurang dari total tagihan (' + formatRupiah(t.grandTotal) + ')!');
            document.getElementById('posAmountPaid').focus();
            return;
        }

        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses Transaksi...';

        const payload = {
            csrf_token: POS_CSRF_TOKEN,
            customer_id: Number(document.getElementById('posCustomerId').value),
            discount_type: document.getElementById('posDiscountType').value,
            discount_value: parseFloat(document.getElementById('posDiscountValue').value) || 0,
            payment_method: document.querySelector('input[name="posPaymentMethod"]:checked').value,
            payment_reference: document.getElementById('posPaymentRef').value.trim(),
            amount_paid: t.amountPaid,
            notes: document.getElementById('posNotes').value.trim(),
            items: posCart.map(i => ({
                product_id: i.product_id,
                qty: i.qty
            }))
        };

        try {
            const response = await fetch(POS_CHECKOUT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            const res = await response.json();
            if (!response.ok || !res.success) {
                alert(res.message || 'Gagal menyimpan transaksi.');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                return;
            }

            document.getElementById('successInvoiceNo').textContent = res.data.invoice_no;
            document.getElementById('successGrandTotal').textContent = formatRupiah(res.data.grand_total);
            document.getElementById('successAmountPaid').textContent = formatRupiah(res.data.amount_paid);
            document.getElementById('successChangeAmount').textContent = formatRupiah(res.data.change_amount);
            document.getElementById('btnPrintReceiptModal').href = res.receipt_url + '&print=1';

            const successModal = new bootstrap.Modal(document.getElementById('modalTransactionSuccess'));
            successModal.show();
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat memproses transaksi.');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
});
</script>

