/* ============================================================
   SiPeternakan — Global JavaScript
   ============================================================ */

// Format angka ke Rupiah (client-side)
function formatRupiah(angka) {
    return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
}

// Auto-format input nominal saat mengetik
document.addEventListener('DOMContentLoaded', function () {

    // Tanggal hari ini sebagai default untuk date input kosong
    document.querySelectorAll('input[type="date"]:not([value])').forEach(el => {
        if (!el.value) el.value = new Date().toISOString().split('T')[0];
    });

    // Konfirmasi hapus global
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });

    // Auto-dismiss alert
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(() => el.remove(), 600);
        });
    }, 4000);

    // Highlight active nav
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
    document.querySelectorAll('.nav-item a').forEach(link => {
        const linkPage = new URLSearchParams(link.search).get('page');
        if (linkPage === currentPage) link.classList.add('active');
        else link.classList.remove('active');
    });

    // Tooltip sederhana
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.title = el.dataset.tooltip;
    });
});

// Modal helpers
function openModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}

// Tutup modal klik backdrop
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
});

// ESC tutup modal
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => {
            m.classList.remove('open');
            document.body.style.overflow = '';
        });
    }
});

// Sidebar mobile toggle
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function () {
        sidebar.classList.toggle('open');
        if (overlay) overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
    });
}

function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.style.display = 'none';
}

// Responsive sidebar show/hide toggle button
function checkMobile() {
    if (sidebarToggle) {
        sidebarToggle.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
    }
}
window.addEventListener('resize', checkMobile);
checkMobile();
