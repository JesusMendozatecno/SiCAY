(function () {
    var PAGE_SIZE = 10;
    var currentPage = 1;

    var tbody = document.getElementById('tablaBody');
    var buscarInput = document.getElementById('buscarParam');
    var filtroUnidad = document.getElementById('filtroUnidad');
    var paginarEl = document.getElementById('tablaPaginar');
    var infoEl = document.getElementById('tablaInfo');
    var modal = document.getElementById('modalParam');
    var btnAgregar = document.getElementById('btnAgregar');
    var btnCerrar = document.getElementById('btnCerrarModal');
    var btnCancelar = document.getElementById('btnCancelarModal');
    var idEditar = document.getElementById('id_editar');
    var nombreInput = document.getElementById('nombre');
    var unidadInput = document.getElementById('unidad');
    var modalTituloText = document.getElementById('modalTituloText');
    var btnGuardar = document.getElementById('btnGuardar');

    var allRows = [];

    function collectRows() {
        allRows = [];
        var trs = tbody.querySelectorAll('tr');
        trs.forEach(function (tr) {
            allRows.push({
                el: tr,
                id: tr.getAttribute('data-id'),
                nombre: (tr.getAttribute('data-nombre') || '').toLowerCase(),
                unidad: (tr.getAttribute('data-unidad') || '').toLowerCase()
            });
        });
    }

    function getFilteredRows() {
        var q = (buscarInput.value || '').toLowerCase().trim();
        var u = filtroUnidad.value.toLowerCase();

        return allRows.filter(function (r) {
            if (q && r.nombre.indexOf(q) === -1) return false;
            if (u && r.unidad !== u) return false;
            return true;
        });
    }

    function renderPage(page) {
        var filtered = getFilteredRows();
        var total = filtered.length;
        var totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

        if (page < 1) page = 1;
        if (page > totalPages) page = totalPages;
        currentPage = page;

        var start = (page - 1) * PAGE_SIZE;
        var end = Math.min(start + PAGE_SIZE, total);

        allRows.forEach(function (r) {
            r.el.style.display = 'none';
        });

        for (var i = start; i < end; i++) {
            filtered[i].el.style.display = '';
        }

        infoEl.textContent = 'Mostrando ' + (total > 0 ? (start + 1) + '-' + end : '0') + ' de ' + total + ' registros';
        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        paginarEl.innerHTML = '';

        if (totalPages <= 1) return;

        var prevBtn = document.createElement('button');
        prevBtn.className = 'pagina-btn';
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', function () {
            renderPage(currentPage - 1);
        });
        paginarEl.appendChild(prevBtn);

        var maxVisible = 7;
        var half = Math.floor(maxVisible / 2);
        var startP = Math.max(1, currentPage - half);
        var endP = Math.min(totalPages, startP + maxVisible - 1);
        if (endP - startP < maxVisible - 1) {
            startP = Math.max(1, endP - maxVisible + 1);
        }

        if (startP > 1) {
            paginarEl.appendChild(createPageBtn(1));
            if (startP > 2) {
                var dots = document.createElement('span');
                dots.className = 'pagina-btn';
                dots.style.border = 'none';
                dots.style.background = 'none';
                dots.style.cursor = 'default';
                dots.textContent = '…';
                paginarEl.appendChild(dots);
            }
        }

        for (var i = startP; i <= endP; i++) {
            paginarEl.appendChild(createPageBtn(i));
        }

        if (endP < totalPages) {
            if (endP < totalPages - 1) {
                var dots2 = document.createElement('span');
                dots2.className = 'pagina-btn';
                dots2.style.border = 'none';
                dots2.style.background = 'none';
                dots2.style.cursor = 'default';
                dots2.textContent = '…';
                paginarEl.appendChild(dots2);
            }
            paginarEl.appendChild(createPageBtn(totalPages));
        }

        var nextBtn = document.createElement('button');
        nextBtn.className = 'pagina-btn';
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', function () {
            renderPage(currentPage + 1);
        });
        paginarEl.appendChild(nextBtn);
    }

    function createPageBtn(page) {
        var btn = document.createElement('button');
        btn.className = 'pagina-btn' + (page === currentPage ? ' activo' : '');
        btn.textContent = page;
        btn.addEventListener('click', function () {
            renderPage(page);
        });
        return btn;
    }

    function openModal(mode, data) {
        if (mode === 'edit') {
            modalTituloText.textContent = 'Editar Parámetro';
            btnGuardar.textContent = 'Actualizar';
            idEditar.value = data.id;
            nombreInput.value = data.nombre;
            unidadInput.value = data.unidad;
        } else {
            modalTituloText.textContent = 'Agregar Parámetro';
            btnGuardar.textContent = 'Guardar';
            idEditar.value = '';
            nombreInput.value = '';
            unidadInput.value = '';
        }
        modal.classList.add('mostrar');
        setTimeout(function () {
            nombreInput.focus();
        }, 100);
    }

    function closeModal() {
        modal.classList.remove('mostrar');
    }

    btnAgregar.addEventListener('click', function () {
        openModal('add');
    });

    btnCerrar.addEventListener('click', closeModal);
    btnCancelar.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('mostrar')) {
            closeModal();
        }
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-editar');
        if (!btn) return;
        var tr = btn.closest('tr');
        openModal('edit', {
            id: tr.getAttribute('data-id'),
            nombre: tr.getAttribute('data-nombre'),
            unidad: tr.getAttribute('data-unidad')
        });
    });

    buscarInput.addEventListener('input', function () {
        renderPage(1);
    });

    filtroUnidad.addEventListener('change', function () {
        renderPage(1);
    });

    collectRows();
    renderPage(1);

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
            b.style.animationDelay = Math.random() * 5 + 's';
            bubbles.appendChild(b);
        }
    }
})();
