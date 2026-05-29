(function () {
    var c = document.getElementById('bubbles');
    if (c) {
        for (var i = 0; i < 15; i++) {
            var b = document.createElement('div');
            b.className = 'bubble';
            var s = Math.random() * 50 + 20 + 'px';
            b.style.width = s;
            b.style.height = s;
            b.style.left = Math.random() * 100 + 'vw';
            b.style.animationDuration = Math.random() * 5 + 5 + 's';
            b.style.animationDelay = Math.random() * 5 + 's';
            c.appendChild(b);
        }
    }

    // ── Paginación Almacén ──
    var PAGE_SIZE = 10;
    var tbody = document.getElementById('tbodyAlmacen');
    var searchInput = document.getElementById('buscarAlmacen');
    var info = document.getElementById('infoAlmacen');
    var paginator = document.getElementById('paginarAlmacen');
    var allRows = [];
    var currentPage = 1;

    if (tbody) {
        function collectRows() {
            allRows = [];
            tbody.querySelectorAll('tr').forEach(function (tr) {
                var data = { el: tr };
                tr.querySelectorAll('td').forEach(function (td, i) {
                    data['col' + i] = (td.textContent || '').toLowerCase().trim();
                });
                allRows.push(data);
            });
        }

        function getFilteredRows() {
            var q = searchInput ? (searchInput.value || '').toLowerCase().trim() : '';
            return allRows.filter(function (r) {
                if (!q) return true;
                for (var k in r) {
                    if (k !== 'el' && r[k].indexOf(q) !== -1) return true;
                }
                return false;
            });
        }

        function renderPage(page) {
            var filtered = getFilteredRows();
            var total = filtered.length;
            var pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
            if (page < 1) page = 1;
            if (page > pages) page = pages;
            currentPage = page;
            var start = (page - 1) * PAGE_SIZE;
            var end = Math.min(start + PAGE_SIZE, total);

            allRows.forEach(function (r) { r.el.style.display = 'none'; });
            for (var i = start; i < end; i++) {
                filtered[i].el.style.display = '';
            }

            if (info) {
                info.textContent = total > 0 ? (start + 1) + '-' + end + ' de ' + total + ' registros' : '0 registros';
            }

            if (paginator) {
                paginator.innerHTML = '';
                if (pages > 1) {
                    var prev = document.createElement('button');
                    prev.textContent = '\u276E';
                    prev.style.cssText = 'min-width:36px;height:36px;border:1px solid rgba(255,255,255,0.1);border-radius:8px;background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.7);cursor:pointer;font-size:14px;display:inline-flex;align-items:center;justify-content:center;transition:0.2s;';
                    prev.disabled = page === 1;
                    prev.addEventListener('click', function () { renderPage(currentPage - 1); });
                    paginator.appendChild(prev);

                    var maxVis = 7, half = Math.floor(maxVis / 2);
                    var sp = Math.max(1, page - half);
                    var ep = Math.min(pages, sp + maxVis - 1);
                    if (ep - sp < maxVis - 1) sp = Math.max(1, ep - maxVis + 1);

                    if (sp > 1) {
                        paginator.appendChild(btnPage(1));
                        if (sp > 2) paginator.appendChild(ellipsis());
                    }
                    for (var i = sp; i <= ep; i++) paginator.appendChild(btnPage(i));
                    if (ep < pages) {
                        if (ep < pages - 1) paginator.appendChild(ellipsis());
                        paginator.appendChild(btnPage(pages));
                    }

                    var next = document.createElement('button');
                    next.textContent = '\u276F';
                    next.style.cssText = 'min-width:36px;height:36px;border:1px solid rgba(255,255,255,0.1);border-radius:8px;background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.7);cursor:pointer;font-size:14px;display:inline-flex;align-items:center;justify-content:center;transition:0.2s;';
                    next.disabled = page === pages;
                    next.addEventListener('click', function () { renderPage(currentPage + 1); });
                    paginator.appendChild(next);
                }
            }
        }

        function btnPage(p) {
            var b = document.createElement('button');
            b.textContent = p;
            var isActive = p === currentPage;
            b.style.cssText = 'min-width:36px;height:36px;border:1px solid ' + (isActive ? '#00cec9' : 'rgba(255,255,255,0.1)') + ';border-radius:8px;background:' + (isActive ? '#00cec9' : 'rgba(255,255,255,0.06)') + ';color:' + (isActive ? '#000' : 'rgba(255,255,255,0.7)') + ';cursor:pointer;font-size:14px;font-weight:' + (isActive ? '700' : '400') + ';display:inline-flex;align-items:center;justify-content:center;transition:0.2s;';
            b.addEventListener('mouseenter', function () { if (!isActive) b.style.background = 'rgba(0,206,201,0.15)'; });
            b.addEventListener('mouseleave', function () { if (!isActive) b.style.background = 'rgba(255,255,255,0.06)'; });
            b.addEventListener('click', function () { renderPage(p); });
            return b;
        }

        function ellipsis() {
            var s = document.createElement('span');
            s.textContent = '\u2026';
            s.style.cssText = 'min-width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.4);font-size:14px;';
            return s;
        }

        collectRows();
        renderPage(1);

        if (searchInput) {
            searchInput.addEventListener('input', function () { renderPage(1); });
        }
    }
})();

var tabLinks = document.querySelectorAll('.tab-link');
tabLinks.forEach(function (btn) {
    btn.addEventListener('click', function () {
        tabLinks.forEach(function (b) { b.classList.remove('active'); });
        this.classList.add('active');
        var tab = this.getAttribute('data-tab');
        document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
        var panel = document.getElementById('tab-' + tab);
        if (panel) panel.classList.add('active');
    });
});

function filtrarMovimientos() {
    var input = document.getElementById('busquedaMov');
    var filter = input.value.toLowerCase();
    var table = document.getElementById('tablaMovimientos');
    var tr = table.getElementsByTagName('tr');
    for (var i = 1; i < tr.length; i++) {
        tr[i].style.display = tr[i].textContent.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
    }
}

function abrirModalAgregar() {
    document.getElementById('modalAgregar').style.display = 'flex';
}

function abrirModalEditar(id, nombre, maxActual) {
    document.getElementById('editId').value = id;
    document.getElementById('editNombre').textContent = nombre;
    document.getElementById('editMaxActual').textContent = maxActual;
    document.getElementById('modalEditar').style.display = 'flex';
}

function cerrarModal(id) {
    document.getElementById(id).style.display = 'none';
}

window.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
    }
});
