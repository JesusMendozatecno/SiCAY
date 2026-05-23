// User dropdown
function toggleDropdown() {
    document.querySelector('.user-dropdown').classList.toggle('open');
}

// Notification panel
function toggleNotifPanel() {
    var panel = document.getElementById('notifPanel');
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) {
        cargarNotificaciones();
    }
}

function cargarNotificaciones() {
    var body = document.getElementById('notifPanelBody');
    body.innerHTML = '<p class="text-muted" style="padding:20px;text-align:center;">Cargando...</p>';
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'index.php?route=api_notifications&action=list', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.success && r.notifications) {
                    renderNotificaciones(r.notifications, r.unread_count || 0);
                } else {
                    body.innerHTML = '<div class="notif-empty">Error al cargar</div>';
                }
            } catch(e) {
                body.innerHTML = '<div class="notif-empty">Error de datos</div>';
            }
        }
    };
    xhr.onerror = function() { body.innerHTML = '<div class="notif-empty">Error de red</div>'; };
    xhr.send();
}

function renderNotificaciones(notifs, unreadCount) {
    var body = document.getElementById('notifPanelBody');
    if (notifs.length === 0) {
        body.innerHTML = '<div class="notif-empty">No tienes notificaciones</div>';
        actualizarBadge(0);
        return;
    }
    var h = '';
    notifs.forEach(function(n) {
        var cls = n.read_at ? '' : ' unread';
        h += '<div class="notif-item' + cls + '" onclick="marcarLeida(' + n.id + ')">' +
            '<div class="notif-item-title">' + hsc(n.title) + '</div>' +
            (n.message ? '<div class="notif-item-msg">' + hsc(n.message) + '</div>' : '') +
            '<div class="notif-item-date">' + n.created_at + '</div>' +
            '</div>';
    });
    body.innerHTML = h;
    actualizarBadge(unreadCount);
}

function marcarLeida(id) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php?route=api_notifications', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            cargarNotificaciones();
        }
    };
    xhr.send('action=mark_read&id=' + id);
}

function marcarTodasLeidas() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php?route=api_notifications', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            cargarNotificaciones();
        }
    };
    xhr.send('action=mark_all_read');
}

function actualizarBadge(count) {
    var badge = document.getElementById('notifBadgeHeader');
    if (!badge) return;
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'flex';
    } else {
        badge.textContent = '';
        badge.style.display = 'none';
    }
}

// Auto-poll notifications every 30 seconds
function pollNotificaciones() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'index.php?route=api_notifications&action=count', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.success) {
                    actualizarBadge(r.count || 0);
                }
            } catch(e) {}
        }
    };
    xhr.send();
}

// Start polling
setInterval(pollNotificaciones, 30000);
setTimeout(pollNotificaciones, 5000);

// Close panels on outside click
document.addEventListener('click', function(e) {
    var dd = document.querySelector('.user-dropdown');
    if (dd && !dd.contains(e.target)) {
        dd.classList.remove('open');
    }
    var nd = document.querySelector('.notif-dropdown');
    if (nd && !nd.contains(e.target)) {
        var panel = document.getElementById('notifPanel');
        if (panel) panel.classList.remove('open');
    }
});

function hsc(s) { if (!s) return ''; var d = document.createElement('div'); d.appendChild(document.createTextNode(s)); return d.innerHTML; }
