const bubblesContainer = document.getElementById('bubbles');
for(let i=0; i<15; i++){
    let b = document.createElement('div'); 
    b.className = 'bubble';
    let size = Math.random()*50 + 20 + 'px';
    b.style.width = size; 
    b.style.height = size; 
    b.style.left = Math.random()*100 + 'vw';
    b.style.animationDuration = Math.random()*5 + 5 + 's';
    b.style.animationDelay = Math.random()*5 + 's';
    bubblesContainer.appendChild(b);
}

function llenar(id, nom, uni, min) {
    document.getElementById('id_editar').value = id;
    document.getElementById('nombre_in').value = nom;
    document.getElementById('unidad_in').value = uni;
    document.getElementById('minimo_in').value = min;
    document.getElementById('btn_txt').innerText = "Actualizar";
    document.getElementById('btn_txt').style.background = "#2980b9";
    window.scrollTo({top: 0, behavior: 'smooth'});
}
