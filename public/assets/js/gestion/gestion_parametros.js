for(let i=0; i<15; i++){
    let b = document.createElement('div'); 
    b.className = 'bubble';
    let size = Math.random()*50 + 20 + 'px';
    b.style.width = size; 
    b.style.height = size; 
    b.style.left = Math.random()*100 + 'vw';
    b.style.animationDuration = Math.random()*5 + 5 + 's';
    b.style.animationDelay = Math.random()*5 + 's';
    document.getElementById('bubbles').appendChild(b);
}

function editarRegistro(id, nombre, unidad) {
    document.getElementById('id_editar').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('unidad').value = unidad;
    document.getElementById('btn_principal').innerText = "Actualizar";
    document.getElementById('btn_principal').style.background = "#007bff";
    document.getElementById('titulo_display').innerHTML = "<i class='fa fa-edit'></i> Editando Parámetro";
    document.getElementById('btn_cancelar').style.display = "inline-block";
    window.scrollTo({top: 0, behavior: 'smooth'});
}
