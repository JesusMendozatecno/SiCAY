for(let i=0; i<15; i++){
    let b = document.createElement('div'); 
    b.className = 'bubble';
    let size = Math.random()*50 + 20 + 'px';
    b.style.width = size; b.style.height = size; 
    b.style.left = Math.random()*100 + 'vw';
    b.style.animationDuration = Math.random()*5 + 5 + 's';
    b.style.animationDelay = Math.random()*5 + 's';
    document.getElementById('bubbles').appendChild(b);
}

function abrirEditar(id, nombre, ubicacion, tipo) {
    document.getElementById('id_edit').value = id;
    document.getElementById('nombre_edit').value = nombre;
    document.getElementById('ubicacion_edit').value = ubicacion;
    document.getElementById('tipo_edit').value = tipo;
    document.getElementById('modalEdit').style.display = 'block';
}

window.onclick = function(event) {
    let modal = document.getElementById('modalEdit');
    if (event.target == modal) { modal.style.display = "none"; }
}
