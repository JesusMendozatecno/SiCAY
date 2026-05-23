<?php
verificar_sesion();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "SICAY";

$mensaje = "";

if (isset($_POST['generar_respaldo'])) {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $fecha = date("Y-m-d_H-i-s");
    $nombre_archivo = dirname(__DIR__, 2) . "/respaldo_SICAY_$fecha.sql";
    
    $comando = sprintf(
        '"%s" -h %s -u %s %s > "%s"',
        "C:\\xampp\\mysql\\bin\\mysqldump.exe",
        escapeshellarg($host),
        escapeshellarg($user),
        escapeshellarg($db),
        $nombre_archivo
    );
    
    system($comando, $resultado);

    if ($resultado === 0) {
        $url_archivo = "respaldo_SICAY_$fecha.sql";
        $mensaje = "<div class='alert success'>
                        <i class='fas fa-check-circle'></i> Respaldo creado con éxito.<br>
                        <a href='$url_archivo' download class='btn-download-link'>
                            <i class='fas fa-file-download'></i> DESCARGAR SQL
                        </a>
                    </div>";
    } else {
        $mensaje = "<div class='alert error'>
                        <i class='fas fa-exclamation-triangle'></i> Error al generar el respaldo. Verifique los permisos de mysqldump.
                    </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Copia de Seguridad - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/seguridad/respaldo.css">
</head>
<body>

<div id="bubbles"></div>

<div class="card">
    <i class="fas fa-database icon-db"></i>
    <h2>Copia de Seguridad</h2>
    <p>Este proceso generará un archivo SQL con toda la estructura y datos del sistema SICAY (Usuarios, Inventario y Reportes).</p>

    <?php echo $mensaje; ?>

    <form method="POST">
        <?php echo csrf_field(); ?>
        <button type="submit" name="generar_respaldo" class="btn-respaldo">
            <i class="fas fa-shield-alt"></i> Iniciar Respaldo Ahora
        </button>
    </form>

    <a href="index.php?route=ajustes" class="btn-volver">
        <i class="fas fa-arrow-left"></i> Volver a Configuración
    </a>
</div>

<script src="assets/js/seguridad/respaldo.js"></script>

</body>
</html>
