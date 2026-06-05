// SiPeternakan — App JS v2.0

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

// ---- Sidebar toggle (mobile) ----
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            if (overlay) overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
        });
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
            a.style.opacity = '0';
            setTimeout(function() { a.remove(); }, 500);
        }, 5000);
    });

    // Highlight current nav item
    const cur = window.location.search;
    document.querySelectorAll('.nav-item a').forEach(function(link) {
        if (link.getAttribute('href') && cur.includes(link.getAttribute('href').split('?')[1])) {
            link.classList.add('active');
        }
    });
});

// ---- Number formatter ----
function formatRp(n) {
    return 'Rp ' + parseInt(n).toLocaleString('id-ID');
}
