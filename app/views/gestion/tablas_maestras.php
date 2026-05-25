<?php
verificar_sesion();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Tablas - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/gestion/tablas_maestras.css">
</head>
<body>

<div id="bubbles"></div>

<div class="contenedor-principal">
    <div class="page-header">
        <a href="index.php?route=dashboard" class="btn-back"><i class="fas fa-chevron-left"></i> Volver</a>
        <h1 class="page-title"><i class="fa fa-database"></i> Tablas Maestras</h1>
    </div>
    <p class="page-subtitle">Configuración y gestión de los pilares del sistema SICAY</p>

    <div class="grid-tablas">

        <a href="index.php?route=gestion_plantas" class="card-tabla">
            <i class="fa fa-industry"></i>
            <h3>Plantas</h3>
            <p>Administrar acueductos, plantas de tratamiento y pozos.</p>
        </a>

        <a href="index.php?route=gestion_parametros" class="card-tabla">
            <i class="fa fa-vial"></i>
            <h3>Parámetros</h3>
            <p>Configurar rangos de norma, unidades y tipos de medición.</p>
        </a>

        <a href="index.php?route=gestion_sustancias" class="card-tabla">
            <i class="fa fa-box-open"></i>
            <h3>Sustancias</h3>
            <p>Catálogo de químicos y materiales de dosificación.</p>
        </a>
    </div>
</div>

    <a href="index.php?route=dashboard" class="btn-volver">← Volver</a>
</div>

<script src="assets/js/gestion/tablas_maestras.js"></script>

</body>
</html>
