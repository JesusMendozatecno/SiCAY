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

function abrirSoporte(tipo) {
    const modal = document.getElementById('modalAyuda');
    const contenedor = document.getElementById('contenidoDinamic');
    let html = "";

    if (tipo === 'accesos') {
        html = `
            <h2 style="color: #123c69;"><i class="fas fa-lock"></i> Accesos y Login</h2>
            <div class="opcion-ayuda">
                <strong>¿Olvidaste tu contraseña?</strong><br>
                Contacta al supervisor de área para solicitar el restablecimiento manual de tu clave.
            </div>
            <div class="opcion-ayuda">
                <strong>¿Usuario Bloqueado?</strong><br>
                Tras 3 intentos fallidos el sistema bloquea la IP. Espera 15 minutos e intenta de nuevo.
            </div>`;
    } 
    else if (tipo === 'datos') {
        html = `
            <h2 style="color: #123c69;"><i class="fas fa-database"></i> Datos y Registros</h2>
            <div class="opcion-ayuda">
                <strong>Error al Guardar</strong><br>
                Asegúrate de que todos los campos marcados con (*) tengan valores válidos.
            </div>
            <div class="opcion-ayuda">
                <strong>Historial no carga</strong><br>
                Verifica tu conexión a la red local de la planta o recarga la página (F5).
            </div>`;
    }
    else if (tipo === 'reportes') {
        html = `
            <h2 style="color: #123c69;"><i class="fas fa-file-pdf"></i> Reportes y PDF</h2>
            <div class="opcion-ayuda">
                <strong>PDF en blanco</strong><br>
                Esto sucede si intentas generar un reporte de una fecha sin datos registrados.
            </div>
            <div class="opcion-ayuda">
                <strong>Error de descarga</strong><br>
                Asegúrate de permitir ventanas emergentes (pop-ups) en tu navegador.
            </div>`;
    }
    else if (tipo === 'sistema') {
        html = `
            <h2 style="color: #123c69;"><i class="fas fa-server"></i> Errores de Sistema</h2>
            <div class="opcion-ayuda">
                <strong>Lentitud General</strong><br>
                Limpia el historial y caché de tu navegador para mejorar la respuesta.
            </div>
            <div class="opcion-ayuda">
                <strong>Servidor Desconectado</strong><br>
                Si ve un error 500, informe de inmediato al departamento de IT de Aguas de Yaracuy.
            </div>`;
    }
    else if (tipo === 'contacto') {
        html = `
            <h2 style="color: #123c69;"><i class="fas fa-user-code"></i> Equipo de Desarrollo</h2>
            <div class="opcion-ayuda">
                <strong>Paola Inojosa</strong><br>
                <i class="fas fa-envelope"></i> inojosapaola4@gmail.com<br>
                <i class="fas fa-clock"></i> Horario: 7:00 AM - 11:00 AM
            </div>
            <div class="opcion-ayuda">
                <strong>Zoivett Aponte</strong><br>
                <i class="fas fa-envelope"></i> zoivetta21@gmail.com<br>
                <i class="fas fa-clock"></i> Horario: 2:00 PM - 5:00 PM
            </div>`;
    }

    contenedor.innerHTML = html + '<br><button onclick="cerrarSoporte()" class="btn-ticket" style="width:100%">Entendido</button>';
    modal.style.display = "block";
}

function cerrarSoporte() {
    document.getElementById('modalAyuda').style.display = "none";
}

window.onclick = function(event) {
    const modal = document.getElementById('modalAyuda');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
