function createBubble() {
    const container = document.getElementById('bubble-container');
    const bubble = document.createElement('div');
    const size = Math.random() * 60 + 20 + 'px';
    bubble.classList.add('bubble');
    bubble.style.width = size;
    bubble.style.height = size;
    bubble.style.left = Math.random() * 100 + 'vw';
    bubble.style.animationDuration = Math.random() * 5 + 7 + 's';
    container.appendChild(bubble);
    setTimeout(() => { bubble.remove(); }, 10000);
}
setInterval(createBubble, 800);

function togglePass(id, btn) {
    var input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i class="fas fa-eye"></i>';
    }
}
