const imagenes = [
    "assets/img/Aguas_de_Yaracuy_fachada.png",
    "assets/img/Imagen-de-WhatsApp-2025-10-07-a-las-12.42.26_13820f50.jpg"
];
let i = 0;
const bgSlide = document.getElementById("bg-slide");
function changeBg() {
    bgSlide.style.backgroundImage = `linear-gradient(rgba(11,28,45,0.6), rgba(11,28,45,0.6)), url('${imagenes[i]}')`;
    i = (i + 1) % imagenes.length;
}
setInterval(changeBg, 4000);
changeBg();

function createBubble() {
    const bubble = document.createElement('div');
    const size = Math.random() * 60 + 20 + 'px';
    bubble.classList.add('bubble');
    bubble.style.width = size;
    bubble.style.height = size;
    bubble.style.left = Math.random() * 100 + 'vw';
    bubble.style.animationDuration = Math.random() * 5 + 5 + 's';
    document.body.appendChild(bubble);
    setTimeout(() => { bubble.remove(); }, 10000);
}
setInterval(createBubble, 800);

document.getElementById('navToggle').addEventListener('click', function() {
    document.getElementById('navLinks').classList.toggle('open');
});

document.querySelectorAll('#navLinks a').forEach(link => {
    link.addEventListener('click', function() {
        document.getElementById('navLinks').classList.remove('open');
    });
});

const sections = document.querySelectorAll('section, footer');
const navLinks = document.querySelectorAll('.nav-link');

function updateActiveLink() {
    let current = '';
    sections.forEach(section => {
        const top = section.offsetTop - 100;
        if (window.scrollY >= top) {
            current = section.getAttribute('id');
        }
    });
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) {
            link.classList.add('active');
        }
    });
}

window.addEventListener('scroll', updateActiveLink);
updateActiveLink();
