(function () {
    var c = document.getElementById('bubbles');
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

function cambiarFiltroAlmacen() {
    var val = document.getElementById('filtroAlmacen').value;
    document.getElementById('grupoLetras').style.display = val === 'letras' ? 'flex' : 'none';
    document.getElementById('grupoCantidad').style.display = val === 'cantidad' ? 'flex' : 'none';
    if (val === 'todo') {
        document.getElementById('busquedaLetras').value = '';
        aplicarFiltrosAlmacen();
    }
}

function getFilas() {
    var tbody = document.getElementById('tbodyAlmacen');
    return Array.from(tbody.querySelectorAll('tr'));
}

function aplicarFiltrosAlmacen() {
    var filtro = document.getElementById('filtroAlmacen').value;
    var tbody = document.getElementById('tbodyAlmacen');
    var filas = Array.from(tbody.querySelectorAll('tr'));

    if (filtro === 'todo') {
        filas.sort(function (a, b) {
            var na = a.getAttribute('data-nombre') || '';
            var nb = b.getAttribute('data-nombre') || '';
            return na.localeCompare(nb);
        });
        filas.forEach(function (f) { f.style.display = ''; });
    } else if (filtro === 'letras') {
        var query = document.getElementById('busquedaLetras').value.toLowerCase();
        filas.sort(function (a, b) {
            var na = a.getAttribute('data-nombre') || '';
            var nb = b.getAttribute('data-nombre') || '';
            return na.localeCompare(nb);
        });
        filas.forEach(function (f) {
            var nombre = (f.getAttribute('data-nombre') || '').toLowerCase();
            f.style.display = nombre.indexOf(query) > -1 ? '' : 'none';
        });
    } else if (filtro === 'cantidad') {
        var orden = document.getElementById('ordenCantidad').value;
        filas.sort(function (a, b) {
            var sa = parseFloat(a.getAttribute('data-stock')) || 0;
            var sb = parseFloat(b.getAttribute('data-stock')) || 0;
            return orden === 'mayor' ? sb - sa : sa - sb;
        });
        filas.forEach(function (f) { f.style.display = ''; });
    }

    filas.forEach(function (f) { tbody.appendChild(f); });
}

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
