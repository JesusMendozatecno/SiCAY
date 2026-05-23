<?php
verificar_sesion();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ajustes de Sistema - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/soporte/ajustes.css">
</head>
<body>

<div id="bubbles"></div>

<div class="container">
    <header>
        <div class="header-titulo">
            <h1><i class="fas fa-cogs"></i> Panel de Configuración Técnica</h1>
        </div>
        <div class="usuario-pill">
            <i class="fas fa-user-shield"></i> Admin: <strong><?php echo hsc($_SESSION['usuario']); ?></strong>
        </div>
    </header>

    <div class="section-header-title">HERRAMIENTAS DE MANTENIMIENTO Y ESTADO</div>
    
    <div class="settings-wrapper">
        
        <div class="settings-card">
            <h3><i class="fas fa-cloud-download-alt"></i> Copia de Seguridad</h3>
            <p>Genera un respaldo instantáneo de toda la base de datos SICAY, incluyendo inventarios y parámetros químicos.</p>
            <a href="index.php?route=respaldo" class="btn-action"><i class="fas fa-database"></i> Ejecutar Backup</a>
        </div>

        <div class="settings-card">
            <h3><i class="fas fa-broom"></i> Mantenimiento</h3>
            <p>Optimiza la base de datos depurando registros del historial con más de 6 meses de antigüedad.</p>
            <button onclick="confirmarLimpieza()" class="btn-action btn-danger"><i class="fas fa-trash-alt"></i> Depurar Historial</button>
        </div>

        <div class="settings-card">
            <h3><i class="fas fa-microchip"></i> Especificaciones</h3>
            <ul class="info-list">
                <li><span>PHP:</span> <strong>8.2.12</strong></li>
                <li><span>Servidor:</span> <strong>Localhost</strong></li>
                <li><span>Estado:</span> <span class="status-pill">OPERACIONAL</span></li>
            </ul>
            <p style="margin:0; font-size: 12px; opacity: 0.5;">Última sincronización: Hoy</p>
        </div>

        <div class="settings-card">
            <h3><i class="fas fa-door-open"></i> Navegación</h3>
            <p>Finaliza la sesión técnica y regresa al panel operativo principal del sistema .</p>
            <a href="index.php?route=dashboard" class="btn-action btn-volver"><i class="fas fa-arrow-left"></i> Volver al Inicio</a>
        </div>

    </div>
</div>

<script src="assets/js/soporte/ajustes.js"></script>

</body>
</html>
