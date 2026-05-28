(function () {
    var PAGE_SIZE = 10;

    // ── Paginación ──
    var wrapper = document.querySelector('.gtca-wrapper');
    if (wrapper) {
        var table = wrapper.querySelector('.tabla');
        if (table) {
            var tbody = table.querySelector('tbody');
            var searchInput = wrapper.querySelector('.gtca-buscar input');
            var filterSelect = wrapper.querySelector('.gtca-filtrar');
            var paginator = wrapper.querySelector('.gtca-paginar');
            var info = wrapper.querySelector('.gtca-info');
            var rows = [];
            var currentPage = 1;

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
                    if (f && r.col2 && r.col2.indexOf(f) === -1) return false;
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
                s.textContent = '\u2026';
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
        }
    }

    // ── Burbujas ──
    var bubbles = document.getElementById('bubbles');
    if (bubbles) {
        for (var i = 0; i < 15; i++) {
            var b = document.createElement('div');
            b.className = 'bubble';
            var size = Math.random() * 50 + 20 + 'px';
            b.style.width = size;
            b.style.height = size;
            b.style.left = Math.random() * 100 + 'vw';
            b.style.animationDuration = Math.random() * 5 + 5 + 's';
            bubbles.appendChild(b);
        }
    }
})();

// ── Funciones globales (editar se llama desde HTML onclick) ──
function editar(id, nombre, tipo, ubicacion, capacidad, estado) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nombre').value = nombre;
    document.getElementById('edit-tipo').value = tipo;
    document.getElementById('edit-ubicacion').value = ubicacion;
    document.getElementById('edit-capacidad').value = capacidad;
    document.getElementById('edit-estado').value = estado;
    document.getElementById('modal-editar').classList.add('mostrar');
}

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
    var del = e.target.closest('[data-eliminar-url]');
    if (del) {
        var url = del.getAttribute('data-eliminar-url');
        var nombre = del.getAttribute('data-eliminar-nombre');
        document.getElementById('eliminar-nombre').textContent = nombre;
        document.getElementById('eliminar-confirmar').href = url;
    }
});

document.querySelectorAll('.modal-overlay').forEach(function (el) {
    el.addEventListener('click', function (e) {
        if (e.target === this) this.classList.remove('mostrar');
    });
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.mostrar').forEach(function (m) {
            m.classList.remove('mostrar');
        });
    }
});

var alerta = document.getElementById('alerta');
if (alerta) {
    setTimeout(function () {
        alerta.style.transition = 'opacity 0.5s';
        alerta.style.opacity = '0';
        setTimeout(function () { alerta.remove(); }, 500);
    }, 3000);
}
