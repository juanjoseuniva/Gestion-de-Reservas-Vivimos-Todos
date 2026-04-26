// ── NOTIFICATIONS ──────────────────────────────────
async function loadNotifications() {
    try {
        const res = await fetch('/vivimostodos/api/notificaciones.php');
        const data = await res.json();
        const badge = document.getElementById('notif-badge');
        const list  = document.getElementById('notif-list');
        if (!badge || !list) return;

        if (data.count > 0) {
            badge.textContent = data.count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }

        list.innerHTML = '';
        if (data.notificaciones.length === 0) {
            list.innerHTML = '<div class="p-3 text-center text-muted" style="font-size:.82rem">Sin notificaciones nuevas</div>';
        } else {
            data.notificaciones.forEach(n => {
                const div = document.createElement('div');
                div.className = 'notif-item' + (n.leido ? '' : ' unread');
                div.dataset.id = n.id_notificacion;
                div.innerHTML = `<div>${n.mensaje}</div><div class="notif-time">${n.fecha}</div>`;
                div.addEventListener('click', () => markRead(n.id_notificacion, div));
                list.appendChild(div);
            });
        }
    } catch(e) { console.error('Notifications error', e); }
}

async function markRead(id, el) {
    await fetch('/vivimostodos/api/marcar_leida.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id})
    });
    el.classList.remove('unread');
    loadNotifications();
}

// Toggle dropdown
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('notif-btn');
    const dropdown = document.getElementById('notif-dropdown');
    if (btn && dropdown) {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });
        document.addEventListener('click', () => dropdown.classList.remove('show'));
    }

    loadNotifications();
    setInterval(loadNotifications, 30000);

    // Mobile sidebar toggle
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
    }

    // Flash auto-dismiss
    setTimeout(() => {
        document.querySelectorAll('.alert-dismissible').forEach(a => {
            a.style.transition = 'opacity .5s';
            a.style.opacity = '0';
            setTimeout(() => a.remove(), 500);
        });
    }, 4000);
});

// ── DISPONIBILIDAD CALENDAR ─────────────────────────
async function initCalendar(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const today = new Date().toISOString().split('T')[0];
    input.min = today;

    input.addEventListener('change', async () => {
        const val = input.value;
        if (!val) return;
        try {
            const res = await fetch(`/vivimostodos/api/disponibilidad.php?fecha=${val}`);
            const data = await res.json();
            const msg = document.getElementById('disponibilidad-msg');
            if (!msg) return;
            if (data.disponible) {
                msg.className = 'mt-2 text-success fw-semibold';
                msg.textContent = '✓ Fecha disponible';
            } else {
                msg.className = 'mt-2 text-danger fw-semibold';
                msg.textContent = '✗ Fecha no disponible — ya existe una reserva';
                input.value = '';
            }
        } catch(e) {}
    });
}

// ── HELPERS ────────────────────────────────────────
function confirmAction(msg, formId) {
    if (confirm(msg)) {
        document.getElementById(formId).submit();
    }
}

function showToast(msg, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed`;
    toast.style.cssText = 'bottom:20px;right:20px;z-index:9999;min-width:280px;box-shadow:0 4px 20px rgba(0,0,0,.15)';
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .5s'; setTimeout(() => toast.remove(), 500); }, 3000);
}
