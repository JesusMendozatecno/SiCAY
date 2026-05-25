document.addEventListener('DOMContentLoaded', function () {

    // ── Tab switching with persistence ──
    var tabLinks = document.querySelectorAll('.tab-link');
    var tabPanels = {
        disponibilidad: document.getElementById('tab-disponibilidad'),
        movimientos: document.getElementById('tab-movimientos'),
        graficas: document.getElementById('tab-graficas')
    };

    // Restore last active tab from localStorage
    var savedTab = localStorage.getItem('reportes_active_tab') || 'disponibilidad';

    function activarTab(tabId) {
        tabLinks.forEach(function (b) {
            b.classList.toggle('active', b.dataset.tab === tabId);
        });
        Object.keys(tabPanels).forEach(function (k) {
            tabPanels[k].classList.toggle('active', k === tabId);
        });
        localStorage.setItem('reportes_active_tab', tabId);
    }

    // Apply saved tab on load
    activarTab(savedTab);

    tabLinks.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activarTab(btn.dataset.tab);
        });
    });

    // ── Charts ──
    if (typeof CHART_DATA !== 'undefined' && document.getElementById('chartMovements')) {
        var chartColors = {
            entradas: 'rgba(46, 204, 113, 0.9)',
            entradasBg: 'rgba(46, 204, 113, 0.15)',
            salidas: 'rgba(231, 76, 60, 0.9)',
            salidasBg: 'rgba(231, 76, 60, 0.15)',
            stock: 'rgba(0, 206, 201, 0.9)',
            stockBg: 'rgba(0, 206, 201, 0.15)',
            minimo: 'rgba(255, 118, 117, 0.8)',
            pie: [
                'rgba(46, 204, 113, 0.8)',
                'rgba(231, 76, 60, 0.8)',
                'rgba(241, 196, 15, 0.8)'
            ]
        };
        var fontColor = '#ccc';

        new Chart(document.getElementById('chartMovements'), {
            type: 'line',
            data: {
                labels: CHART_DATA.meses,
                datasets: [
                    {
                        label: 'Entradas',
                        data: CHART_DATA.entradas,
                        borderColor: chartColors.entradas,
                        backgroundColor: chartColors.entradasBg,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Salidas',
                        data: CHART_DATA.salidas,
                        borderColor: chartColors.salidas,
                        backgroundColor: chartColors.salidasBg,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { labels: { color: fontColor, font: { size: 11 } } }
                },
                scales: {
                    x: { ticks: { color: fontColor, font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                    y: { ticks: { color: fontColor, font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true }
                }
            }
        });

        new Chart(document.getElementById('chartStock'), {
            type: 'bar',
            data: {
                labels: CHART_DATA.stock_labels,
                datasets: [
                    {
                        label: 'Stock Actual',
                        data: CHART_DATA.stock_data,
                        backgroundColor: CHART_DATA.stock_data.map(function (v, i) {
                            return v <= CHART_DATA.stock_minimo[i] ? 'rgba(255, 118, 117, 0.7)' : 'rgba(0, 206, 201, 0.7)';
                        }),
                        borderColor: CHART_DATA.stock_data.map(function (v, i) {
                            return v <= CHART_DATA.stock_minimo[i] ? 'rgba(255, 118, 117, 1)' : 'rgba(0, 206, 201, 1)';
                        }),
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Mínimo Requerido',
                        data: CHART_DATA.stock_minimo,
                        type: 'line',
                        borderColor: 'rgba(255, 118, 117, 0.8)',
                        fill: false,
                        tension: 0,
                        pointRadius: 3,
                        pointStyle: 'dash',
                        borderDash: [5, 5],
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { labels: { color: fontColor, font: { size: 11 } } }
                },
                scales: {
                    x: { ticks: { color: fontColor, font: { size: 9 } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                    y: { ticks: { color: fontColor, font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true }
                }
            }
        });

        new Chart(document.getElementById('chartPie'), {
            type: 'pie',
            data: {
                labels: CHART_DATA.tipo_labels,
                datasets: [{
                    data: CHART_DATA.tipo_data,
                    backgroundColor: chartColors.pie,
                    borderColor: 'rgba(11, 28, 45, 0.5)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: fontColor, font: { size: 11 }, padding: 14 }
                    }
                }
            }
        });
    }
});

// ── AJAX Pagination ──
function irPaginaAjax(n) {
    var tbody = document.querySelector('#tab-movimientos .reportes-table tbody');
    var pagiEl = document.querySelector('#tab-movimientos .paginacion');
    if (!tbody) return;

    var url = new URL(window.location.href);
    url.searchParams.set('ajax_movimientos', '1');
    url.searchParams.set('page', n);

    fetch(url.toString())
        .then(function (res) { return res.json(); })
        .then(function (data) {
            tbody.innerHTML = data.tabla;
            if (pagiEl) pagiEl.outerHTML = data.paginacion;
            var u = new URL(window.location.href);
            u.searchParams.set('page', n);
            u.searchParams.delete('ajax_movimientos');
            window.history.replaceState({ page: n }, '', u.toString());
        })
        .catch(function () {
            var u = new URL(window.location.href);
            u.searchParams.set('page', n);
            window.location.href = u.toString();
        });
}

// ── Section-specific PDF ──
function imprimirSeccion(seccion) {
    var panel = document.getElementById('tab-' + seccion);
    if (!panel) return;

    var titulo = panel.querySelector('.section-title');
    var texto = titulo ? titulo.textContent.trim() : 'Reporte';

    var tabla = panel.querySelector('.reportes-table');
    if (!tabla) return;

    var printDiv = document.createElement('div');
    printDiv.className = 'print-area';
    var header = document.createElement('div');
    header.className = 'print-header';
    header.textContent = 'SICAY - ' + texto;
    printDiv.appendChild(header);

    var clonedTable = tabla.cloneNode(true);
    clonedTable.querySelectorAll('.paginacion, .page-btn, .page-info, .btn-print-section').forEach(function (el) {
        if (el) el.remove();
    });
    printDiv.appendChild(clonedTable);

    document.body.classList.add('print-mode');
    document.body.appendChild(printDiv);

    // Small delay to let CSS apply before print dialog
    setTimeout(function () {
        window.print();
    }, 100);
}

window.addEventListener('afterprint', function () {
    document.body.classList.remove('print-mode');
    var printArea = document.querySelector('.print-area');
    if (printArea && printArea.parentNode) printArea.parentNode.removeChild(printArea);
});
