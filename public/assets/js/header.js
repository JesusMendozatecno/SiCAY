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
        h += '<div class="notif-item' + cls + '" onclick="abrirDetalleNotificacion(' + n.id + ')">' +
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

// ══ Notification detail modal ══
function abrirDetalleNotificacion(id) {
    var overlay = document.getElementById('notifDetailOverlay');
    if (!overlay) return;
    overlay.querySelector('.notif-detail-body').innerHTML = '<div class="detail-loading">Cargando...</div>';
    overlay.classList.add('show');
    var panel = document.getElementById('notifPanel');
    if (panel) panel.classList.remove('open');
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'index.php?route=api_notifications&action=detail&id=' + id, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.success && r.notification) {
                    renderDetalleNotificacion(r.notification);
                    cargarNotificaciones();
                } else {
                    overlay.querySelector('.notif-detail-body').innerHTML = '<div class="detail-loading">Error al cargar</div>';
                }
            } catch(e) {
                overlay.querySelector('.notif-detail-body').innerHTML = '<div class="detail-loading">Error de datos</div>';
            }
        }
    };
    xhr.send();
}

function renderDetalleNotificacion(n) {
    var body = document.getElementById('notifDetailBody');
    var fecha = n.created_at || '';
    var sender = n.sender_name ? 'De: ' + hsc(n.sender_name) : 'Sistema';
    body.innerHTML =
        '<div class="nd-header">' +
            '<div class="nd-sender">' + sender + '</div>' +
            '<div class="nd-date">' + fecha + '</div>' +
        '</div>' +
        '<h3 class="nd-title">' + hsc(n.title) + '</h3>' +
        '<div class="nd-message">' + hsc(n.message || 'Sin contenido') + '</div>' +
        (n.from_user_id ? '' : '') +
        '<div class="nd-actions" id="ndActions">' +
            '<button class="nd-btn nd-btn-read" onclick="marcarLeidaNotif(' + n.id + ')"><i class="fas fa-check"></i> Marcar como leída</button>' +
            (n.from_user_id ? '<button class="nd-btn nd-btn-reply" onclick="mostrarRespuesta(' + n.id + ')"><i class="fas fa-reply"></i> Responder</button>' : '') +
            '<button class="nd-btn nd-btn-delete" onclick="eliminarNotificacion(' + n.id + ')"><i class="fas fa-trash"></i> Eliminar</button>' +
        '</div>' +
        '<div class="nd-reply-box" id="ndReplyBox" style="display:none;">' +
            '<textarea id="ndReplyMsg" class="nd-textarea" placeholder="Escribe tu respuesta..." rows="3"></textarea>' +
            '<div class="nd-reply-actions">' +
                '<button class="nd-btn nd-btn-cancel" onclick="cancelarRespuesta()">Cancelar</button>' +
                '<button class="nd-btn nd-btn-send" onclick="enviarRespuesta(' + n.id + ')"><i class="fas fa-paper-plane"></i> Enviar</button>' +
            '</div>' +
        '</div>';
}

function mostrarRespuesta(id) {
    var box = document.getElementById('ndReplyBox');
    if (box) box.style.display = 'block';
}

function cancelarRespuesta() {
    var box = document.getElementById('ndReplyBox');
    if (box) { box.style.display = 'none'; document.getElementById('ndReplyMsg').value = ''; }
}

function enviarRespuesta(id) {
    var msg = document.getElementById('ndReplyMsg');
    if (!msg || !msg.value.trim()) return;
    var btn = document.querySelector('.nd-btn-send');
    if (btn) btn.disabled = true;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php?route=api_notifications', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (btn) btn.disabled = false;
        if (xhr.status === 200) {
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.success) {
                    cancelarRespuesta();
                    document.querySelector('.nd-actions').innerHTML = '<div class="nd-reply-sent"><i class="fas fa-check-circle"></i> Respuesta enviada</div>';
                } else {
                    alert(r.error || 'Error al enviar respuesta');
                }
            } catch(e) { alert('Error de conexión'); }
        }
    };
    xhr.send('action=reply&id=' + id + '&message=' + encodeURIComponent(msg.value.trim()));
}

function marcarLeidaNotif(id) {
    marcarLeida(id);
    cerrarDetalleNotificacion();
}

function eliminarNotificacion(id) {
    if (!confirm('¿Eliminar esta notificación?')) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php?route=api_notifications', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.success) {
                    cerrarDetalleNotificacion();
                    cargarNotificaciones();
                    pollNotificaciones();
                } else {
                    alert(r.error || 'Error al eliminar');
                }
            } catch(e) { alert('Error de conexión'); }
        }
    };
    xhr.send('action=delete&id=' + id);
}

function cerrarDetalleNotificacion() {
    var overlay = document.getElementById('notifDetailOverlay');
    if (overlay) overlay.classList.remove('show');
}

// ── Polling with new-notification detection ──
var prevUnreadCount = -1;
var lastNotifId = 0;

function pollNotificaciones() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'index.php?route=api_notifications&action=count', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.success) {
                    var count = r.count || 0;
                    actualizarBadge(count);
                    if (prevUnreadCount >= 0 && count > prevUnreadCount && r.last && r.last.id !== lastNotifId) {
                        lastNotifId = r.last.id;
                        reproducirSonidoNotif();
                        mostrarToastNotificacion(r.last.title);
                    }
                    prevUnreadCount = count;
                }
            } catch(e) {}
        }
    };
    xhr.send();
}

function reproducirSonidoNotif() {
    try {
        var actx = new (window.AudioContext || window.webkitAudioContext)();
        var osc = actx.createOscillator();
        var gain = actx.createGain();
        osc.connect(gain);
        gain.connect(actx.destination);
        osc.frequency.value = 880;
        osc.type = 'sine';
        gain.gain.setValueAtTime(0.3, actx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, actx.currentTime + 0.3);
        osc.start(actx.currentTime);
        osc.stop(actx.currentTime + 0.3);
        // Second beep
        var osc2 = actx.createOscillator();
        var gain2 = actx.createGain();
        osc2.connect(gain2);
        gain2.connect(actx.destination);
        osc2.frequency.value = 1108;
        osc2.type = 'sine';
        gain2.gain.setValueAtTime(0.3, actx.currentTime + 0.15);
        gain2.gain.exponentialRampToValueAtTime(0.001, actx.currentTime + 0.45);
        osc2.start(actx.currentTime + 0.15);
        osc2.stop(actx.currentTime + 0.45);
        // Cleanup
        setTimeout(function() { actx.close(); }, 500);
    } catch(e) { /* Audio not available */ }
}

function mostrarToastNotificacion(title) {
    var existing = document.querySelector('.notif-toast');
    if (existing) existing.remove();
    var toast = document.createElement('div');
    toast.className = 'notif-toast';
    toast.innerHTML =
        '<div class="notif-toast-icon"><i class="fas fa-bell"></i></div>' +
        '<div class="notif-toast-content">' +
            '<div class="notif-toast-title">Nueva notificación</div>' +
            '<div class="notif-toast-msg">' + hsc(title || '') + '</div>' +
        '</div>' +
        '<button class="notif-toast-close" onclick="event.stopPropagation();cerrarToastNotificacion(this)">&times;</button>';
    toast.addEventListener('click', function() {
        cerrarToastNotificacion(null);
        toggleNotifPanel();
    });
    document.body.appendChild(toast);
    setTimeout(function() { toast.classList.add('show'); }, 50);
    var autoClose = setTimeout(function() { cerrarToastNotificacion(null); }, 6000);
    toast.dataset.timer = autoClose;
}

function cerrarToastNotificacion(btn) {
    var toast;
    if (btn && btn.nodeType === 1) {
        toast = btn.closest('.notif-toast');
    } else {
        toast = document.querySelector('.notif-toast');
    }
    if (!toast) return;
    if (toast.dataset.timer) clearTimeout(parseInt(toast.dataset.timer));
    toast.classList.remove('show');
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 400);
}

setInterval(pollNotificaciones, 15000);
setTimeout(pollNotificaciones, 5000);

// ── Global handler for instant notification arrival (called from other pages) ──
window.notificacionRecibida = function(id, title) {
    if (id) lastNotifId = id;
    prevUnreadCount = Math.max(0, prevUnreadCount) + 1;
    actualizarBadge(prevUnreadCount);
    reproducirSonidoNotif();
    mostrarToastNotificacion(title || 'Nueva notificación');
};

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
    var no = document.getElementById('notifDetailOverlay');
    if (no && e.target === no) {
        cerrarDetalleNotificacion();
    }
});

function hsc(s) { if (!s) return ''; var d = document.createElement('div'); d.appendChild(document.createTextNode(s)); return d.innerHTML; }
