<?php
verificar_sesion();

// --- LÓGICA DE DATOS ---
$resInst = mysqli_query($con, "SELECT COUNT(*) as total FROM instalacion WHERE estado = 'Activo'");
$totalInstalaciones = mysqli_fetch_assoc($resInst)['total'];

$resPlantas = mysqli_query($con, "SELECT COUNT(*) as total FROM instalacion WHERE tipo LIKE '%Planta%' AND estado = 'Activo'");
$totalPlantas = mysqli_fetch_assoc($resPlantas)['total'];

$resPozos = mysqli_query($con, "SELECT COUNT(*) as total FROM instalacion WHERE tipo LIKE '%Pozo%' AND estado = 'Activo'");
$totalPozos = mysqli_fetch_assoc($resPozos)['total'];

$resEstaciones = mysqli_query($con, "SELECT COUNT(*) as total FROM instalacion WHERE (tipo LIKE '%Estación%' OR tipo LIKE '%Estacion%') AND estado = 'Activo'");
$totalEstaciones = mysqli_fetch_assoc($resEstaciones)['total'];

$resPara = mysqli_query($con, "SELECT COUNT(*) as total FROM parametro");
$totalParametros = ($resPara) ? mysqli_fetch_assoc($resPara)['total'] : 0;

$resSust = mysqli_query($con, "SELECT COUNT(*) as total FROM sustancia_quimica");
$totalSustancias = ($resSust) ? mysqli_fetch_assoc($resSust)['total'] : 0;

$resUser = mysqli_query($con, "SELECT COUNT(*) as total FROM usuario");
$totalUsuarios = ($resUser) ? mysqli_fetch_assoc($resUser)['total'] : 1;

$resHist = mysqli_query($con, "SELECT COUNT(*) as total FROM historial");
$totalHistorial = ($resHist) ? mysqli_fetch_assoc($resHist)['total'] : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SICAY - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard/dashboard.css">
</head>
<body>

<div id="bubble-container"></div>

<aside class="sidebar">
   

    <nav class="menu-nav">
        <button onclick="location.href='index.php?route=registros'"><i class="fas fa-file-signature"></i> Registros</button>
        <button onclick="location.href='index.php?route=tablas_maestras'"><i class="fas fa-table"></i> Tablas Maestras</button>
        <button onclick="location.href='index.php?route=reportes'"><i class="fas fa-chart-bar"></i> Reportes</button>
        <button onclick="location.href='index.php?route=inventario'"><i class="fas fa-boxes"></i> Inventario</button>
        <button onclick="location.href='index.php?route=seguridad'"><i class="fas fa-shield-alt"></i> Seguridad</button>
        <button onclick="location.href='index.php?route=soporte'"><i class="fas fa-headset"></i> Soporte</button>
        <button onclick="location.href='index.php?route=ajustes'"><i class="fas fa-cog"></i> Ajustes</button>
        <button onclick="location.href='index.php?route=historial'"><i class="fas fa-history"></i> Historial</button>
    </nav>
</aside>

<main class="main-content">
    <div class="top-bar">
        <h2>Panel de Control Informativo</h2>
      
    </div>

    <div class="grid-stats">
        <div class="card">
            <div class="card-header">
                <h4>Instalaciones</h4>
                <i class="fas fa-tint"></i>
            </div>
            <div class="card-body">
                <div class="number"><?php echo $totalInstalaciones; ?></div>
                <p>Plantas, Pozos y Estaciones activas en el estado.</p>
                <div class="sub-stats">
                    <span>PLT: <?php echo $totalPlantas; ?></span>
                    <span>POZ: <?php echo $totalPozos; ?></span>
                    <span>EST: <?php echo $totalEstaciones; ?></span>
                </div>
            </div>
        </div>

        <div class="card" style="border-color: #00cec9;">
            <div class="card-header">
                <h4>Parámetros</h4>
                <i class="fas fa-microscope"></i>
            </div>
            <div class="card-body">
                <div class="number"><?php echo $totalParametros; ?></div>
                <p>Estándares de calidad del agua monitoreados.</p>
            </div>
        </div>

        <div class="card" style="border-color: #fdcb6e;">
            <div class="card-header">
                <h4>Insumos Químicos</h4>
                <i class="fas fa-flask"></i>
            </div>
            <div class="card-body">
                <div class="number"><?php echo $totalSustancias; ?></div>
                <p>Sustancias disponibles para el tratamiento.</p>
            </div>
        </div>

        <div class="card" style="border-color: #a29bfe;">
            <div class="card-header">
                <h4>Personal</h4>
                <i class="fas fa-users"></i>
            </div>
            <div class="card-body">
                <div class="number"><?php echo $totalUsuarios; ?></div>
                <p>Usuarios con acceso al sistema SICAY.</p>
            </div>
        </div>

        <div class="card" style="border-color: #55efc4;">
            <div class="card-header">
                <h4>Historial de Actividad</h4>
                <i class="fas fa-clock"></i>
            </div>
            <div class="card-body">
                <div class="number"><?php echo $totalHistorial; ?></div>
                <p>Movimientos y auditorías registradas.</p>
            </div>
        </div>

        <div class="card" style="border-color: #fab1a0;">
            <div class="card-header">
                <h4>Ayuda y Soporte</h4>
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="card-body">
                <div class="number">9</div>
                <p>Solicitudes de soporte técnico atendidas.</p>
            </div>
        </div>
    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> Hidroven Yaracuy
    </footer>
</main>

<script src="assets/js/dashboard/dashboard.js"></script>

</body>
</html>
