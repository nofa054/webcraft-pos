/**
 * Webcraft POS - Global Frontend Utilities
 */
document.addEventListener('DOMContentLoaded', function () {
    // Sidebar Toggle untuk Tablet & Smartphone
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const btnToggle = document.getElementById('btnToggleSidebar');
    const btnClose = document.getElementById('btnCloseSidebar');

    function toggleSidebar(show) {
        if (!sidebar || !backdrop) return;
        if (show) {
            sidebar.classList.add('show');
            backdrop.classList.add('show');
        } else {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        }
    }

    if (btnToggle) {
        btnToggle.addEventListener('click', function () {
            toggleSidebar(!sidebar.classList.contains('show'));
        });
    }
    if (btnClose) {
        btnClose.addEventListener('click', function () {
            toggleSidebar(false);
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () {
            toggleSidebar(false);
        });
    }

    // Konfirmasi hapus otomatis pada form dengan class .js-confirm-delete
    document.querySelectorAll('.js-confirm-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const msg = form.getAttribute('data-confirm') || 'Apakah Anda yakin ingin menghapus data ini?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    });
});

/**
 * Format angka menjadi string Rupiah di sisi klien
 */
window.formatRupiah = function (num) {
    const n = Number(num) || 0;
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
};

