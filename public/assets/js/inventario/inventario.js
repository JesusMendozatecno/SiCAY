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

function filtrarTabla() {
    let input = document.getElementById("busqueda");
    let filter = input.value.toLowerCase();
    let table = document.getElementById("tablaInventario");
    let tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        let tdText = tr[i].textContent.toLowerCase();
        if (tdText.indexOf(filter) > -1) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}
