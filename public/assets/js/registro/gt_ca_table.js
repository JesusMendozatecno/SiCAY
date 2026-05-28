(function () {
    var PAGE_SIZE = 10;

    document.querySelectorAll('.gtca-wrapper').forEach(function (wrapper) {
        var table = wrapper.querySelector('.tabla');
        if (!table) return;
        var tbody = table.querySelector('tbody');
        if (!tbody) return;

        var searchInput = wrapper.querySelector('.gtca-buscar');
        var filterSelect = wrapper.querySelector('.gtca-filtrar');
        var paginator = wrapper.querySelector('.gtca-paginar');
        var info = wrapper.querySelector('.gtca-info');
        var rows = [];
        var currentPage = 1;

        // ── Añadir columna Acción ──
        var theadRow = table.querySelector('thead tr');
        if (theadRow) {
            var th = document.createElement('th');
            th.textContent = 'Acción';
            theadRow.appendChild(th);
        }
        tbody.querySelectorAll('tr').forEach(function (tr) {
            var td = document.createElement('td');
            td.innerHTML = '<button type="button" class="btn-ver" title="Ver detalle"><i class="fas fa-eye"></i></button>';
            tr.appendChild(td);
        });

        function collect() {
            rows = [];
            tbody.querySelectorAll('tr').forEach(function (tr) {
                var data = { el: tr };
                tr.querySelectorAll('td').forEach(function (td, i) {
                    data['col' + i] = (td.textContent || '').toLowerCase().trim();
                });
                rows.push(data);
            });
        }

        function filtered() {
            var q = searchInput ? (searchInput.value || '').toLowerCase().trim() : '';
            var f = filterSelect ? filterSelect.value.toLowerCase() : '';
            return rows.filter(function (r) {
                if (q) {
                    var match = false;
                    for (var k in r) {
                        if (k !== 'el' && r[k].indexOf(q) !== -1) { match = true; break; }
                    }
                    if (!match) return false;
                }
                if (f && r.col1 && r.col1.indexOf(f) === -1) return false;
                return true;
            });
        }

        function render(page) {
            var f = filtered();
            var total = f.length;
            var pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
            if (page < 1) page = 1;
            if (page > pages) page = pages;
            currentPage = page;
            var start = (page - 1) * PAGE_SIZE;
            var end = Math.min(start + PAGE_SIZE, total);

            rows.forEach(function (r) { r.el.style.display = 'none'; });
            for (var i = start; i < end; i++) {
                f[i].el.style.display = '';
            }

            if (info) {
                info.textContent = total > 0 ? (start + 1) + '-' + end + ' de ' + total + ' registros' : '0 registros';
            }

            if (paginator) {
                paginator.innerHTML = '';
                if (pages > 1) {
                    var prev = document.createElement('button');
                    prev.className = 'pagina-btn';
                    prev.innerHTML = '<i class="fas fa-chevron-left"></i>';
                    prev.disabled = page === 1;
                    prev.addEventListener('click', function () { render(currentPage - 1); });
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
                    next.className = 'pagina-btn';
                    next.innerHTML = '<i class="fas fa-chevron-right"></i>';
                    next.disabled = page === pages;
                    next.addEventListener('click', function () { render(currentPage + 1); });
                    paginator.appendChild(next);
                }
            }
        }

        function btnPage(p) {
            var b = document.createElement('button');
            b.className = 'pagina-btn' + (p === currentPage ? ' activo' : '');
            b.textContent = p;
            b.addEventListener('click', function () { render(p); });
            return b;
        }

        function ellipsis() {
            var s = document.createElement('span');
            s.className = 'pagina-btn';
            s.style.cssText = 'border:none;background:none;cursor:default;';
            s.textContent = '…';
            return s;
        }

        collect();
        render(1);

        if (searchInput) {
            searchInput.addEventListener('input', function () { render(1); });
        }
        if (filterSelect) {
            filterSelect.addEventListener('change', function () { render(1); });
        }
    });

    // ── Modal handling ──
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-modal-open]');
        if (btn) {
            var id = btn.getAttribute('data-modal-open');
            var modal = document.getElementById(id);
            if (modal) modal.classList.add('mostrar');
            return;
        }
        var close = e.target.closest('[data-modal-close]');
        if (close) {
            var modal = close.closest('.modal-overlay');
            if (modal) modal.classList.remove('mostrar');
            return;
        }
    });

    document.querySelectorAll('.modal-overlay').forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) modal.classList.remove('mostrar');
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.mostrar').forEach(function (m) {
                m.classList.remove('mostrar');
            });
        }
    });

    // ── Ver detalle ──
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-ver');
        if (!btn) return;
        var row = btn.closest('tr');
        if (!row) return;
        var table = row.closest('.tabla');
        if (!table) return;
        var headers = table.querySelectorAll('thead th');
        var cells = row.querySelectorAll('td');
        var modal = document.getElementById('modalVer');
        var body = document.getElementById('modalVerBody');
        if (!modal || !body) return;

        body.innerHTML = '';
        for (var i = 0; i < cells.length - 1; i++) {
            var label = headers[i] ? headers[i].textContent.trim() : '';
            if (!label || label === 'Acción') continue;
            var val = cells[i] ? cells[i].innerHTML : '';
            var div = document.createElement('div');
            div.className = 'detalle-row';
            div.innerHTML = '<span class="detalle-label">' + label + '</span><span class="detalle-valor">' + val + '</span>';
            body.appendChild(div);
        }
        modal.classList.add('mostrar');
    });
})();
