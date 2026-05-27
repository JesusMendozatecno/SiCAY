function abrirRegistro() {
    document.getElementById('modal-registro').classList.add('mostrar');
}

function editar(id, nombre, tipo, ubicacion, capacidad, estado) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nombre').value = nombre;
    document.getElementById('edit-tipo').value = tipo;
    document.getElementById('edit-ubicacion').value = ubicacion;
    document.getElementById('edit-capacidad').value = capacidad;
    document.getElementById('edit-estado').value = estado;
    document.getElementById('modal-editar').classList.add('mostrar');
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('mostrar');
}

document.querySelectorAll('.modal-overlay').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('mostrar');
    });
});

var alerta = document.getElementById('alerta');
if (alerta) {
    setTimeout(function() {
        alerta.style.transition = 'opacity 0.5s';
        alerta.style.opacity = '0';
        setTimeout(function() { alerta.remove(); }, 500);
    }, 3000);
}
