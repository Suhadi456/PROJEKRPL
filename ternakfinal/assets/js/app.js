// SiPeternakan — App JS v3.0

// ---- Modal helpers ----
function openModal(id) {
    const el = document.getElementById(id);
    if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.style.display = 'none'; document.body.style.overflow = ''; }
}
function closeSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (sb) sb.classList.remove('open');
    if (ov) ov.style.display = 'none';
}

// ---- Number formatter ----
function formatRp(n) {
    return 'Rp ' + parseInt(n).toLocaleString('id-ID');
}

// ---- DOM Ready ----
document.addEventListener('DOMContentLoaded', function() {

    // Sidebar toggle button — label + tooltip (prompt 7a)
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');

    if (toggleBtn) {
        toggleBtn.title        = 'Menu';
        toggleBtn.setAttribute('aria-label', 'Buka/Tutup Menu');
        toggleBtn.innerHTML    = '☰ <span style="font-size:.75rem;vertical-align:middle;margin-left:2px">Menu</span>';

        if (sidebar) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                if (overlay) overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
            });
        }
    }

    // Close modal on outside click
    document.querySelectorAll('.modal-overlay').forEach(function(m) {
        m.addEventListener('click', function(e) {
            if (e.target === m) closeModal(m.id);
        });
    });

    // Auto dismiss alerts after 5s
    document.querySelectorAll('.alert').forEach(function(a) {
        setTimeout(function() {
            a.style.transition = 'opacity .5s';
            a.style.opacity    = '0';
            setTimeout(function() { a.remove(); }, 500);
        }, 5000);
    });

    // Highlight current nav item
    const cur = window.location.search;
    document.querySelectorAll('.nav-item a').forEach(function(link) {
        const href = link.getAttribute('href');
        if (href && href.includes('page=') && cur.includes(href.split('page=')[1])) {
            link.classList.add('active');
        }
    });
});
