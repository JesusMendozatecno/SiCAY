<?php
verificar_sesion();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Soporte Técnico - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/soporte/soporte.css">
</head>
<body>

<div id="bubbles"></div>

<div class="container">
    <div class="page-header">
        <a href="index.php?route=dashboard" class="btn-back"><i class="fas fa-chevron-left"></i> Volver</a>
        <h1 class="page-title"><i class="fas fa-headset"></i> Centro de Soporte</h1>
    </div>
    <p class="page-subtitle">Soluciones rápidas para el personal</p>

    <div class="soporte-container">
        <div class="soporte-card" onclick="abrirSoporte('accesos')">
            <i class="fas fa-key"></i>
            <h3>Accesos y Login</h3>
            <p>Problemas con contraseñas, recuperación de cuentas o usuarios bloqueados.</p>
        </div>

        <div class="soporte-card" onclick="abrirSoporte('datos')">
            <i class="fas fa-table"></i>
            <h3>Datos y Registros</h3>
            <p>Errores al intentar guardar parámetros o visualizar el historial.</p>
        </div>

        <div class="soporte-card" onclick="abrirSoporte('reportes')">
            <i class="fas fa-file-pdf"></i>
            <h3>Reportes y PDF</h3>
            <p>Dificultades al generar formatos GT-CA o exportar inventarios.</p>
        </div>

        <div class="soporte-card" onclick="abrirSoporte('sistema')">
            <i class="fas fa-microchip"></i>
            <h3>Errores de Sistema</h3>
            <p>Fallos en la conexión o lentitud persistente en el servidor.</p>
        </div>

        <div class="soporte-card" onclick="abrirSoporte('contacto')">
            <i class="fas fa-user-code"></i>
            <h3>Contacto Técnico</h3>
            <p>Números y correos de las desarrolladoras en caso de fallas críticas.</p>
        </div>
    </div>

    <div class="ticket-box">
        <div>
            <h3 style="color: #fff; margin: 0; font-size: 22px;">¿No encontraste solución?</h3>
            <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.8);">Envía un ticket directo al equipo de desarrollo de Yaracuy.</p>
        </div>
        <a href="mailto:soporte@aguasdeyaracuy.com" class="btn-ticket">Enviar Reporte <i class="fas fa-paper-plane"></i></a>
    </div>
</div>

<div id="modalAyuda" class="modal-soporte">
    <div class="modal-content">
        <span class="close-btn" onclick="cerrarSoporte()">&times;</span>
        <div id="contenidoDinamic"></div>
    </div>
</div>

<script src="assets/js/soporte/soporte.js"></script>

</body>
</html>
