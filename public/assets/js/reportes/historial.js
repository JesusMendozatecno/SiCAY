var paginaActual = 1;
var debounceTimer = null;

document.addEventListener('DOMContentLoaded', function() {
    cargarStats();
    cargarHistorial();
});

function cargarHistorial() {
    var params = new URLSearchParams();
    params.set('action', 'list');
    params.set('page', paginaActual);

    var usuario = document.getElementById('filterUsuario').value.trim();
    var accion = document.getElementById('filterAccion').value.trim();
    var tipo = document.getElementById('filterTipo').value;
    var desde = document.getElementById('filterDesde').value;
    var hasta = document.getElementById('filterHasta').value;

    if (usuario) params.set('usuario', usuario);
    if (accion) params.set('accion', accion);
    if (tipo) params.set('tipo_accion', tipo);
    if (desde) params.set('fecha_desde', desde);
    if (hasta) params.set('fecha_hasta', hasta);

    document.getElementById('historialBody').innerHTML =
        '<tr><td colspan="7" class="loading-row"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

    fetch('index.php?route=api_historial&' + params.toString())
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.ok) { mostrarError(res.error); return; }
            renderizarTabla(res.data);
            renderizarPaginacion(res.pages, res.page, res.total);
            document.getElementById('resultCount').textContent =
                'Mostrando ' + res.data.length + ' de ' + res.total + ' registros';
        })
        .catch(function(e) {
            mostrarError('Error al cargar: ' + e.message);
        });
}

function cargarStats() {
    fetch('index.php?route=api_historial&action=stats')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.ok) return;
            document.getElementById('statTotal').textContent = res.data.total.toLocaleString();
            document.getElementById('statToday').textContent = res.data.today.toLocaleString();
            document.getElementById('statWeek').textContent = res.data.week.toLocaleString();
            document.getElementById('statMonth').textContent = res.data.month.toLocaleString();
        });
}

function renderizarTabla(data) {
    var tbody = document.getElementById('historialBody');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;opacity:0.5;">' +
            '<i class="fas fa-info-circle"></i> No hay registros de actividad.</td></tr>';
        return;
    }
    var html = '';
    data.forEach(function(r) {
        var fecha = formatearFecha(r.fecha);
        var usuario = esc(r.usuario || 'Sistema');
        var accion = esc(r.accion);
        var tipo = r.tipo_accion || 'system';
        var modulo = r.modulo ? esc(r.modulo) : '-';
        var ip = r.ip_address ? esc(r.ip_address) : '-';
        html += '<tr>' +
            '<td><span class="fecha-cell">' + fecha + '</span></td>' +
            '<td><strong>' + usuario + '</strong></td>' +
            '<td>' + accion + '</td>' +
            '<td><span class="tipo-badge tipo-' + tipo + '">' + tipo + '</span></td>' +
            '<td>' + (modulo !== '-' ? '<span class="module-badge">' + modulo + '</span>' : '-') + '</td>' +
            '<td><code style="font-size:0.75rem;opacity:0.6;">' + ip + '</code></td>' +
            '<td style="text-align:center;"><button class="btn-detail" onclick="verDetalle(' + r.id + ')" title="Ver detalle"><i class="fas fa-eye"></i></button></td>' +
            '</tr>';
    });
    tbody.innerHTML = html;
}

function renderizarPaginacion(totalPages, currentPage, total) {
    var container = document.getElementById('pagination');
    if (totalPages <= 1) { container.innerHTML = ''; return; }

    var html = '';
    html += '<button class="page-btn" onclick="irPagina(1)"' + (currentPage <= 1 ? ' disabled' : '') + '><i class="fas fa-angle-double-left"></i></button>';
    html += '<button class="page-btn" onclick="irPagina(' + (currentPage - 1) + ')"' + (currentPage <= 1 ? ' disabled' : '') + '><i class="fas fa-angle-left"></i></button>';

    var start = Math.max(1, currentPage - 2);
    var end = Math.min(totalPages, currentPage + 2);
    for (var i = start; i <= end; i++) {
        html += '<button class="page-btn' + (i === currentPage ? ' active' : '') + '" onclick="irPagina(' + i + ')">' + i + '</button>';
    }

    html += '<button class="page-btn" onclick="irPagina(' + (currentPage + 1) + ')"' + (currentPage >= totalPages ? ' disabled' : '') + '><i class="fas fa-angle-right"></i></button>';
    html += '<button class="page-btn" onclick="irPagina(' + totalPages + ')"' + (currentPage >= totalPages ? ' disabled' : '') + '><i class="fas fa-angle-double-right"></i></button>';

    container.innerHTML = html;
}

function irPagina(p) {
    paginaActual = p;
    cargarHistorial();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function aplicarFiltros() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() {
        paginaActual = 1;
        cargarHistorial();
    }, 300);
}

function limpiarFiltros() {
    document.getElementById('filterUsuario').value = '';
    document.getElementById('filterAccion').value = '';
    document.getElementById('filterTipo').value = '';
    document.getElementById('filterDesde').value = '';
    document.getElementById('filterHasta').value = '';
    paginaActual = 1;
    cargarHistorial();
}

function verDetalle(id) {
    fetch('index.php?route=api_historial&action=detail&id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.ok) { alert(res.error); return; }
            mostrarDetalle(res.data);
        });
}

function mostrarDetalle(d) {
    var body = document.getElementById('detailBody');
    var fecha = formatearFecha(d.fecha);
    var tipo = d.tipo_accion || 'system';
    var desc = d.descripcion ? esc(d.descripcion) : '<em style="opacity:0.4;">Sin descripción</em>';

    body.innerHTML =
    '<div class="detail-grid">' +
        '<div class="detail-field"><div class="df-label">Fecha y Hora</div><div class="df-value">' + fecha + '</div></div>' +
        '<div class="detail-field"><div class="df-label">Usuario</div><div class="df-value"><strong>' + esc(d.usuario || 'Sistema') + '</strong></div></div>' +
        '<div class="detail-field"><div class="df-label">Acción</div><div class="df-value">' + esc(d.accion) + '</div></div>' +
        '<div class="detail-field"><div class="df-label">Tipo de Acción</div><div class="df-value"><span class="tipo-badge tipo-' + tipo + '">' + tipo + '</span></div></div>' +
        '<div class="detail-field"><div class="df-label">Módulo</div><div class="df-value">' + esc(d.modulo || '-') + '</div></div>' +
        '<div class="detail-field"><div class="df-label">Dirección IP</div><div class="df-value"><code>' + esc(d.ip_address || '-') + '</code></div></div>' +
        '<div class="detail-field detail-field-full"><div class="df-label">Descripción</div><div class="df-value">' + desc + '</div></div>' +
    '</div>';

    document.getElementById('detailModal').classList.add('show');
}

function cerrarModal() {
    document.getElementById('detailModal').classList.remove('show');
}

function exportarPDF() {
    var params = new URLSearchParams();
    params.set('action', 'export_pdf');

    var usuario = document.getElementById('filterUsuario').value.trim();
    var tipo = document.getElementById('filterTipo').value;
    var desde = document.getElementById('filterDesde').value;
    var hasta = document.getElementById('filterHasta').value;

    if (usuario) params.set('usuario', usuario);
    if (tipo) params.set('tipo_accion', tipo);
    if (desde) params.set('fecha_desde', desde);
    if (hasta) params.set('fecha_hasta', hasta);

    fetch('index.php?route=api_historial&' + params.toString())
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.ok) { alert(res.error); return; }
            // Open PDF in new window
            var win = window.open('', '_blank');
            win.document.write(res.html);
            win.document.close();
            win.focus();
            win.print();
        })
        .catch(function(e) { alert('Error al exportar: ' + e.message); });
}

function limpiarHistorial(periodo) {
    var msg = '';
    var labels = { hora: 'la última hora', dia: 'el último día', mes: 'el último mes', ano: 'el último año', total: 'TODO el historial' };
    msg = '¿Estás seguro de eliminar los registros de ' + (labels[periodo] || periodo) + '?\n\nEsta acción NO se puede deshacer.';

    if (periodo === 'total') {
        msg = '⚠️ ADVERTENCIA: Estás a punto de ELIMINAR TODO el historial del sistema.\n\n¿Estás ABSOLUTAMENTE seguro? Esta acción es irreversible.';
    }

    if (!confirm(msg)) return;

    var fd = new FormData();
    fd.append('action', 'cleanup');
    fd.append('periodo', periodo);
    fd.append('csrf_token', document.querySelector('#formProfile [name=csrf_token]')?.value || '');

    fetch('index.php?route=api_historial', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.ok) { alert('Error: ' + res.error); return; }
            alert(res.message);
            cargarHistorial();
            cargarStats();
        })
        .catch(function(e) { alert('Error: ' + e.message); });
}

// ----- Helpers -----
function formatearFecha(fecha) {
    if (!fecha) return '-';
    var d = new Date(fecha.replace(' ', 'T') + (fecha.includes('Z') ? '' : 'Z'));
    if (isNaN(d.getTime())) return fecha;
    var dia = String(d.getDate()).padStart(2, '0');
    var mes = String(d.getMonth() + 1).padStart(2, '0');
    var anio = d.getFullYear();
    var hora = String(d.getHours()).padStart(2, '0');
    var min = String(d.getMinutes()).padStart(2, '0');
    return '<span class="fecha-date">' + dia + '/' + mes + '/' + anio + '</span> <span class="fecha-time">' + hora + ':' + min + '</span>';
}

function esc(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function mostrarError(msg) {
    document.getElementById('historialBody').innerHTML =
        '<tr><td colspan="7" style="text-align:center;padding:40px;color:#ff7675;">' +
        '<i class="fas fa-exclamation-triangle"></i> ' + esc(msg) + '</td></tr>';
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) cerrarModal();
});
