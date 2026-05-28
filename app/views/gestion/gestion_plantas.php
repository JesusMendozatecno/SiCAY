<?php
verificar_sesion();

function mapear_tipo($tipo) {
    if (strcasecmp($tipo, 'Planta Potabilizadora') === 0) return 'Planta';
    if (strcasecmp($tipo, 'Pozo Profundo') === 0) return 'Pozo';
    if (stripos($tipo, 'Estación') !== false || stripos($tipo, 'Estacion') !== false) return 'Estación Cloradora';
    return 'Planta';
}

if (isset($_POST['guardar'])) {
    verificar_csrf($_POST['csrf_token'] ?? '');

    $faltantes = validar_requeridos(['nombre', 'ubicacion', 'tipo'], $_POST);
    if (!empty($faltantes)) {
        $_SESSION['error_msg'] = "Faltan campos: " . implode(', ', $faltantes);
        redirigir("gestion_plantas");
    }

    $nombre = trim($_POST['nombre']);
    $ubicacion = trim($_POST['ubicacion']);
    $tipo = mapear_tipo($_POST['tipo']);

    $stmt = $con->prepare("INSERT INTO instalacion (nombre, ubicacion, tipo, estado) VALUES (?, ?, ?, 'Activo')");
    $stmt->bind_param("sss", $nombre, $ubicacion, $tipo);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "agregado";
    } else {
        error_log("Error en gestion_plantas insert: " . $stmt->error);
        $_SESSION['error_msg'] = "Error al agregar la instalación";
    }
    $stmt->close();
    redirigir("gestion_plantas");
}

if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $con->prepare("UPDATE instalacion SET estado = 'Inactivo' WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['msg'] = "eliminado";
    }
    $stmt->close();
    redirigir("gestion_plantas");
}

if (isset($_POST['actualizar'])) {
    verificar_csrf($_POST['csrf_token'] ?? '');

    $faltantes = validar_requeridos(['id_edit', 'nombre_edit', 'ubicacion_edit', 'tipo_edit'], $_POST);
    if (!empty($faltantes)) {
        $_SESSION['error_msg'] = "Faltan campos para editar";
        redirigir("gestion_plantas");
    }

    $id = intval($_POST['id_edit']);
    $nombre = trim($_POST['nombre_edit']);
    $ubicacion = trim($_POST['ubicacion_edit']);
    $tipo = mapear_tipo($_POST['tipo_edit']);

    $stmt = $con->prepare("UPDATE instalacion SET nombre=?, ubicacion=?, tipo=? WHERE id=?");
    $stmt->bind_param("sssi", $nombre, $ubicacion, $tipo, $id);
    if ($stmt->execute()) {
        $_SESSION['msg'] = "editado";
    } else {
        error_log("Error en gestion_plantas update: " . $stmt->error);
        $_SESSION['error_msg'] = "Error al actualizar la instalación";
    }
    $stmt->close();
    redirigir("gestion_plantas");
}

$resultado = mysqli_query($con, "SELECT * FROM instalacion WHERE estado = 'Activo' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Plantas - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/gestion/gestion_plantas.css">
</head>
<body>

<div id="bubbles"></div>

<div class="container">
    <div class="page-header">
        <a href="index.php?route=dashboard" class="btn-back"><i class="fas fa-chevron-left"></i> Volver</a>
        <h1 class="page-title"><i class="fa fa-industry"></i> Gestión de Plantas e Instalaciones</h1>
    </div>

    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alerta-exito">
            <i class="fas fa-check-circle"></i> 
            <?php 
                if($_SESSION['msg'] == "agregado") echo "¡Instalación agregada con éxito!";
                if($_SESSION['msg'] == "eliminado") echo "¡Instalación eliminada con éxito!";
                if($_SESSION['msg'] == "editado") echo "¡Se guardaron los cambios correctamente!";
                unset($_SESSION['msg']); 
            ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alerta-error">
            <i class="fas fa-exclamation-triangle"></i> <?php echo hsc($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

    <form class="form-add" method="POST">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label>Nombre de Instalación:</label>
            <input type="text" name="nombre" required placeholder="Ej: Planta Los Naranjos">
        </div>
        <div class="form-group">
            <label>Ubicación:</label>
            <input type="text" name="ubicacion" required placeholder="Ej: San Felipe">
        </div>
        <div class="form-group">
            <label>Tipo de Sistema:</label>
            <select name="tipo" required>
                <option value="Planta Potabilizadora">Planta Potabilizadora</option>
                <option value="Pozo Profundo">Pozo Profundo</option>
                <option value="Estación Cloradora">Estación Cloradora</option>
            </select>
        </div>
        <button type="submit" name="guardar" class="btn-add"><i class="fa fa-plus-circle"></i> Agregar</button>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre de la Instalación</th>
                    <th>Ubicación</th>
                    <th>Tipo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $n = 1; 
                while($row = mysqli_fetch_assoc($resultado)) { 
                    $tipo_db = $row['tipo'] ?? '';
                    $tipo_lower = mb_strtolower($tipo_db, 'UTF-8');

                    if (strpos($tipo_lower, 'pozo') !== false) {
                        $color = '#17a2b8'; $etiqueta = 'Pozo Profundo';
                    } elseif (strpos($tipo_lower, 'estaci') !== false) {
                        $color = '#6f42c1'; $etiqueta = 'Estación Cloradora';
                    } else {
                        $color = '#004a87'; $etiqueta = 'Planta Potabilizadora';
                    }
                ?>
                <tr>
                    <td><?php echo $n++; ?></td>
                    <td><strong style="color: #fff;"><?php echo hsc($row['nombre']); ?></strong></td>
                    <td style="opacity: 0.8;"><?php echo hsc($row['ubicacion']); ?></td>
                    <td>
                        <span class="badge-tipo" style="background-color: <?php echo $color; ?>;">
                            <?php echo hsc($etiqueta); ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn-edit" title="Editar" onclick="abrirEditar(<?php echo $row['id']; ?>, <?php echo json_encode($row['nombre'], JSON_HEX_TAG|JSON_HEX_AMP); ?>, <?php echo json_encode($row['ubicacion'], JSON_HEX_TAG|JSON_HEX_AMP); ?>, <?php echo json_encode($row['tipo'], JSON_HEX_TAG|JSON_HEX_AMP); ?>)">
                            <i class="fa fa-edit"></i>
                        </button>
                        <a href="index.php?route=gestion_plantas&eliminar=<?php echo $row['id']; ?>" class="btn-del" title="Desactivar" onclick="return confirm('¿Seguro que desea desactivar esta instalación?')">
                            <i class="fa fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalEdit">
    <div class="modal-content">
        <h3><i class="fa fa-pen-to-square"></i> Editar Instalación</h3>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id_edit" id="id_edit">
            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" name="nombre_edit" id="nombre_edit" required>
            </div>
            <div class="form-group">
                <label>Ubicación:</label>
                <input type="text" name="ubicacion_edit" id="ubicacion_edit" required>
            </div>
            <div class="form-group">
                <label>Tipo:</label>
                <select name="tipo_edit" id="tipo_edit" required>
                    <option value="Planta Potabilizadora">Planta Potabilizadora</option>
                    <option value="Pozo Profundo">Pozo Profundo</option>
                    <option value="Estación Cloradora">Estación Cloradora</option>
                </select>
            </div>
            <button type="submit" name="actualizar" class="btn-save-modal">Guardar Cambios</button>
            <button type="button" class="btn-cancel" onclick="document.getElementById('modalEdit').style.display='none'">Cancelar</button>
        </form>
    </div>
</div>

<script src="assets/js/gestion/gestion_plantas.js"></script>
</body>
</html>
