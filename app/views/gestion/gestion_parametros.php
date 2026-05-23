<?php
verificar_sesion();

if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $con->prepare("DELETE FROM parametro WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['msg'] = "eliminado";
    } else {
        $_SESSION['error_msg'] = "Este parámetro está siendo usado en reportes y no puede eliminarse.";
    }
    $stmt->close();
    redirigir("gestion_parametros");
}

if (isset($_POST['guardar'])) {
    verificar_csrf($_POST['csrf_token'] ?? '');

    $faltantes = validar_requeridos(['nombre', 'unidad'], $_POST);
    if (!empty($faltantes)) {
        $_SESSION['error_msg'] = "Faltan campos: " . implode(', ', $faltantes);
        redirigir("gestion_parametros");
    }

    $nombre = trim($_POST['nombre']);
    $unidad = trim($_POST['unidad']);

    if (!empty($_POST['id_editar'])) {
        $id_edit = intval($_POST['id_editar']);
        $stmt = $con->prepare("UPDATE parametro SET nombre=?, unidad_medida=? WHERE id=?");
        $stmt->bind_param("ssi", $nombre, $unidad, $id_edit);
        $status = "editado";
    } else {
        $stmt = $con->prepare("INSERT INTO parametro (nombre, unidad_medida) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $unidad);
        $status = "agregado";
    }

    if ($stmt->execute()) {
        $_SESSION['msg'] = $status;
    } else {
        error_log("Error en gestion_parametros: " . $stmt->error);
        $_SESSION['error_msg'] = "Error al procesar la solicitud";
    }
    $stmt->close();
    redirigir("gestion_parametros");
}

$resultado = mysqli_query($con, "SELECT * FROM parametro ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Parámetros - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/gestion/gestion_parametros.css">
</head>
<body>

<div id="bubbles"></div>

<div class="container">
    <a href="index.php?route=tablas_maestras" class="btn-volver"><i class="fa fa-arrow-left"></i> ← Volver a Tablas Maestras</a>
    <h2 id="titulo_display"><i class="fa fa-vial"></i> Gestión de Parámetros</h2>

    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alerta-exito">
            <i class="fas fa-check-circle"></i> 
            <?php 
                if($_SESSION['msg'] == "agregado") echo "¡Parámetro agregado con éxito!";
                if($_SESSION['msg'] == "eliminado") echo "¡Parámetro eliminado con éxito!";
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

    <form class="form-add" method="POST" id="form_param">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id_editar" id="id_editar">
        <div class="form-group">
            <label>Nombre del Parámetro:</label>
            <input type="text" name="nombre" id="nombre" placeholder="Ej: Cloro Residual" required>
        </div>
        <div class="form-group">
            <label>Unidad de Medida:</label>
            <input type="text" name="unidad" id="unidad" placeholder="Ej: mg/L" required>
        </div>
        <button type="submit" name="guardar" class="btn-add" id="btn_principal">Agregar</button>
        <a href="index.php?route=gestion_parametros" class="btn-cancelar" id="btn_cancelar">Cancelar</a>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Parámetro</th>
                    <th>Unidad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $n = 1; 
                while($row = mysqli_fetch_assoc($resultado)) { 
                ?>
                <tr>
                    <td><?php echo $n++; ?></td>
                    <td><strong><?php echo hsc($row['nombre']); ?></strong></td>
                    <td><span class="badge-param"><?php echo hsc($row['unidad_medida']); ?></span></td>
                    <td>
                        <button onclick="editarRegistro(<?php echo $row['id']; ?>, <?php echo json_encode($row['nombre'], JSON_HEX_TAG|JSON_HEX_AMP); ?>, <?php echo json_encode($row['unidad_medida'], JSON_HEX_TAG|JSON_HEX_AMP); ?>)" class="btn-accion" style="color:#f1c40f;" title="Editar">
                            <i class="fa fa-edit"></i>
                        </button>
                        
                        <a href="index.php?route=gestion_parametros&eliminar=<?php echo $row['id']; ?>" onclick="return confirm('¿Estás seguro de eliminar este parámetro?')" class="btn-accion" style="color:#ff6b6b; margin-left:15px;" title="Eliminar">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/gestion/gestion_parametros.js"></script>

</body>
</html>
