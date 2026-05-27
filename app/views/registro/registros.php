<?php
verificar_sesion();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SICAY - Gestión de Registros</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/registro/registros.css">
</head>
<body>

<div id="bubble-container"></div>

<div class="main-container">
    <div class="page-header">
        <a href="index.php?route=dashboard" class="btn-back"><i class="fas fa-chevron-left"></i> Volver</a>
        <h1 class="page-title"><i class="fas fa-file-signature"></i> Gestión de Registros</h1>
    </div>
    <p class="page-subtitle">Seleccione el formato de calidad para ingresar nuevos datos al sistema SICAY.</p>

    <div class="grid-planillas">
        
        <?php 
        $iconos = [
            1 => 'fa-tint', 2 => 'fa-boxes', 3 => 'fa-microscope',
            4 => 'fa-weight-hanging', 5 => 'fa-gas-pump', 6 => 'fa-tools',
            7 => 'fa-bolt', 8 => 'fa-water', 9 => 'fa-chart-line',
            10 => 'fa-trash-alt', 11 => 'fa-eye', 12 => 'fa-plug',
            13 => 'fa-sliders-h', 14 => 'fa-users', 15 => 'fa-id-card',
            16 => 'fa-wrench', 17 => 'fa-chart-bar', 18 => 'fa-fire-extinguisher',
            19 => 'fa-user-shield'
        ];
        $descripciones = [
            1 => 'Cloro y pH', 2 => 'Inventario Qu&iacute;mico', 3 => 'Calidad del Agua',
            4 => 'Consumo Qu&iacute;mico', 5 => 'Cilindros Cloro', 6 => 'Lavado Filtros',
            7 => 'Equipos y Bombas', 8 => 'Niveles de Agua', 9 => 'Medici&oacute;n Caudales',
            10 => 'Gesti&oacute;n Lodos', 11 => 'Inspecci&oacute;n Planta', 12 => 'Grupo Electr&oacute;geno',
            13 => 'Calibraci&oacute;n Equipos', 14 => 'Entrega Guardia', 15 => 'Control Acceso',
            16 => 'Herramientas', 17 => 'Consumo El&eacute;ctrico', 18 => 'Extintores',
            19 => 'Equipos Protecci&oacute;n'
        ];
        for ($i = 1; $i <= 19; $i++) {
            $num = str_pad($i, 3, "0", STR_PAD_LEFT);
            echo '
            <a href="index.php?route=gt_ca_'.$num.'" class="card-planilla">
                <div class="icon-box"><i class="fas '.$iconos[$i].'"></i></div>
                <div class="info-box">
                    <h3>GT-CA-'.$num.'</h3>
                    <p>'.$descripciones[$i].'</p>
                </div>
            </a>';
        }
        ?>

        <a href="index.php?route=solicitud_sq" class="card-planilla" style="border-left-color: #fab1a0;">
            <div class="icon-box" style="color: #fab1a0;"><i class="fas fa-paper-plane"></i></div>
            <div class="info-box">
                <h3>Solicitud SQ</h3>
                <p>Formato de Solicitud Químicos</p>
            </div>
        </a>

        <a href="index.php?route=resultados_laboratorio" class="card-planilla" style="border-left-color: #55efc4;">
            <div class="icon-box" style="color: #55efc4;"><i class="fas fa-flask"></i></div>
            <div class="info-box">
                <h3>Laboratorio</h3>
                <p>Resultados de Análisis</p>
            </div>
        </a>

        <a href="index.php?route=formatos_aplicacion" class="card-planilla" style="border-left-color: #a29bfe;">
            <div class="icon-box" style="color: #a29bfe;"><i class="fas fa-check-double"></i></div>
            <div class="info-box">
                <h3>Aplicación</h3>
                <p>Formatos de Aplicación Técnica</p>
            </div>
        </a>

        <a href="index.php?route=instalaciones" class="card-planilla" style="border-left-color: #00cec9;">
            <div class="icon-box" style="color: #00cec9;"><i class="fas fa-industry"></i></div>
            <div class="info-box">
                <h3>Instalaciones</h3>
                <p>Registro de Instalaciones de Agua</p>
            </div>
        </a>

    </div>
</div>

<script src="assets/js/registro/registros.js"></script>

</body>
</html>
