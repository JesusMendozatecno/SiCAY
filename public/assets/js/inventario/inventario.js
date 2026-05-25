// ── Bubbles ──
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

// ── Tab switching ──
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

// ── Filtro Almacén ──
function cambiarFiltroAlmacen() {
    var val = document.getElementById('filtroAlmacen').value;
    document.getElementById('grupoLetras').style.display = val === 'letras' ? 'flex' : 'none';
    document.getElementById('grupoCantidad').style.display = val === 'cantidad' ? 'flex' : 'none';
    if (val === 'todo') {
        document.getElementById('busquedaLetras').value = '';
        aplicarFiltrosAlmacen();
    }
}

// Store original table rows order on first call
var filasOrdenOriginal = null;

function getFilas() {
    var tbody = document.getElementById('tbodyAlmacen');
    return Array.from(tbody.querySelectorAll('tr'));
}

function aplicarFiltrosAlmacen() {
    var filtro = document.getElementById('filtroAlmacen').value;
    var tbody = document.getElementById('tbodyAlmacen');
    var filas = Array.from(tbody.querySelectorAll('tr'));

    if (filtro === 'todo') {
        // Show all in alphabetical order
        filas.sort(function (a, b) {
            var na = a.getAttribute('data-nombre') || '';
            var nb = b.getAttribute('data-nombre') || '';
            return na.localeCompare(nb);
        });
        filas.forEach(function (f) { f.style.display = ''; });
    } else if (filtro === 'letras') {
        var query = document.getElementById('busquedaLetras').value.toLowerCase();
        // Show matching, keep alphabetical
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

    // Re-append in order
    filas.forEach(function (f) { tbody.appendChild(f); });
}

// ── Filtro Movimientos ──
function filtrarMovimientos() {
    var input = document.getElementById('busquedaMov');
    var filter = input.value.toLowerCase();
    var table = document.getElementById('tablaMovimientos');
    var tr = table.getElementsByTagName('tr');
    for (var i = 1; i < tr.length; i++) {
        tr[i].style.display = tr[i].textContent.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
    }
}
