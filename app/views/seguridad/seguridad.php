<?php
verificar_sesion();
$mi_usuario = $_SESSION['usuario'];

if (isset($_POST['cambiar_clave'])) {
    verificar_csrf($_POST['csrf_token'] ?? '');

    $actual = $_POST['clave_actual'] ?? '';
    $nueva = $_POST['clave_nueva'] ?? '';

    if ($actual === '' || $nueva === '') {
        echo "<script>alert('Por favor completa ambos campos');</script>";
    } elseif (strlen($nueva) < 6) {
        echo "<script>alert('La nueva contraseña debe tener al menos 6 caracteres');</script>";
    } else {
        $stmt = $con->prepare("SELECT id, contraseña FROM usuario WHERE usuario = ?");
        $stmt->bind_param("s", $mi_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user && verificar_pass($actual, $user['contraseña'])) {
            $hash = hash_pass($nueva);
            $upd = $con->prepare("UPDATE usuario SET contraseña = ? WHERE id = ?");
            $upd->bind_param("si", $hash, $user['id']);
            if ($upd->execute()) {
                echo "<script>alert('Contraseña actualizada con éxito'); window.location='index.php?route=seguridad';</script>";
            }
            $upd->close();
        } else {
            echo "<script>alert('La contraseña actual es incorrecta');</script>";
        }
    }
}

$stmt = $con->prepare("SELECT * FROM usuario WHERE usuario = ?");
$stmt->bind_param("s", $mi_usuario);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seguridad y Perfil - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/seguridad/seguridad.css">
</head>
<body>

<div id="bubbles"></div>

<div class="wrapper">
    <a href="index.php?route=dashboard" class="btn-volver"><i class="fa fa-arrow-left"></i> Volver al Dashboard</a>

    <div class="container-grid">
        <div class="card">
            <div class="perfil-info">
                <i class="fa fa-user-shield"></i>
                <h3><?php echo hsc($datos['nombre']); ?></h3>
                <span class="badge-rol"><?php echo hsc($datos['rol']); ?></span>
            </div>
            
            <form method="POST" style="margin-top: 20px;">
                <?php echo csrf_field(); ?>
                <p style="font-size: 13px; text-align: center; opacity: 0.7;">Seguridad de la Cuenta</p>
                <label>Contraseña Actual</label>
                <input type="password" name="clave_actual" placeholder="••••••••" required>
                
                <label>Nueva Contraseña</label>
                <input type="password" name="clave_nueva" placeholder="Mínimo 6 caracteres" required>
                
                <button type="submit" name="cambiar_clave" class="btn-update">Actualizar Contraseña</button>
            </form>
        </div>

        <div class="card">
            <h2><i class="fa fa-key"></i> Mis Atribuciones </h2>
            <p style="font-size: 14px; opacity: 0.8; margin-bottom: 15px;">
                Basado en tu rol de <strong><?php echo hsc($datos['rol']); ?></strong>, tienes acceso a:
            </p>
            
            <ul class="permisos-list">
                <li><i class="fa fa-check-circle"></i> Visualización de indicadores en Dashboard</li>
                <li><i class="fa fa-check-circle"></i> Registro de recepciones (GT-CA-002)</li>
                <li><i class="fa fa-check-circle"></i> Registro de despachos y consumos (GT-CA-004)</li>
                <li><i class="fa fa-check-circle"></i> Consulta de stock y reportes detallados</li>
                
                <?php if($datos['rol'] == 'Admin') { ?>
                    <li><i class="fa fa-check-circle"></i> Gestión total de Usuarios y Personal</li>
                    <li><i class="fa fa-check-circle"></i> Configuración de Tablas Maestras</li>
                    <li><i class="fa fa-check-circle"></i> Auditoría y eliminación de registros</li>
                <?php } else { ?>
                    <li style="opacity: 0.5;"><i class="fa fa-times-circle"></i> Administración de Usuarios (Bloqueado)</li>
                    <li style="opacity: 0.5;"><i class="fa fa-times-circle"></i> Eliminación de movimientos (Bloqueado)</li>
                <?php } ?>
            </ul>
            
            <div class="aviso-seguridad">
                <small>
                    <i class="fa fa-info-circle"></i> <strong>Protocolo de Auditoría:</strong><br>
                    Su sesión actual está siendo monitoreada. Cualquier cambio en los registros de inventario será vinculado automáticamente a su usuario, hora del servidor y dirección IP de origen.
                </small>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/seguridad/seguridad.js"></script>

</body>
</html>
