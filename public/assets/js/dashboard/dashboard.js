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
