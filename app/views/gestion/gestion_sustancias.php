<?php
verificar_sesion();

if (isset($_POST['guardar'])) {
    verificar_csrf($_POST['csrf_token'] ?? '');

    $faltantes = validar_requeridos(['nombre', 'unidad', 'minimo'], $_POST);
    if (!empty($faltantes)) {
        $_SESSION['error_msg'] = "Faltan campos: " . implode(', ', $faltantes);
        redirigir("gestion_sustancias");
    }

    $nombre = trim($_POST['nombre']);
    $unidad = trim($_POST['unidad']);
    $minimo = $_POST['minimo'];

    if (!validar_numeric($minimo, 0)) {
        $_SESSION['error_msg'] = "El stock mínimo debe ser un número válido mayor o igual a 0";
        redirigir("gestion_sustancias");
    }

    if (!empty($_POST['id_editar'])) {
        $id = intval($_POST['id_editar']);
        $stmt = $con->prepare("UPDATE sustancia_quimica SET nombre=?, unidad_medida=?, inventario_minimo=? WHERE id=?");
        $stmt->bind_param("ssdi", $nombre, $unidad, $minimo, $id);
        $res_status = "editado";
    } else {
        $stmt = $con->prepare("INSERT INTO sustancia_quimica (nombre, unidad_medida, inventario_minimo) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $nombre, $unidad, $minimo);
        $res_status = "agregado";
    }

    if ($stmt->execute()) {
        $_SESSION['msg'] = $res_status;
    } else {
        error_log("Error en gestion_sustancias: " . $stmt->error);
        $_SESSION['error_msg'] = "Error al procesar la solicitud";
    }
    $stmt->close();
    redirigir("gestion_sustancias");
}

if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $con->prepare("DELETE FROM sustancia_quimica WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['msg'] = "eliminado";
    } else {
        $_SESSION['error_msg'] = "No se puede eliminar: esta sustancia tiene registros asociados en el inventario.";
    }
    $stmt->close();
    redirigir("gestion_sustancias");
}

$resultado = mysqli_query($con, "SELECT * FROM sustancia_quimica ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Sustancias - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/gestion/gestion_sustancias.css">
</head>
<body>

<div id="bubbles"></div>

<div class="container">
    <a href="index.php?route=tablas_maestras" class="btn-volver"><i class="fa fa-arrow-left"></i> ← Volver a Tablas Maestras</a>
    <h2><i class="fa fa-flask"></i> Gestión de Sustancias Químicas</h2>

    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alerta-exito">
            <i class="fas fa-check-circle"></i> 
            <?php 
                if($_SESSION['msg'] == "agregado") echo "¡Sustancia registrada con éxito!";
                if($_SESSION['msg'] == "eliminado") echo "¡Sustancia eliminada correctamente!";
                if($_SESSION['msg'] == "editado") echo "¡Cambios guardados con éxito!";
                unset($_SESSION['msg']); 
            ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alerta-error">
            <i class="fas fa-exclamation-triangle"></i> <?php echo hsc($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id_editar" id="id_editar">
        <div class="form-glass">
            <div class="form-group">
                <label>Nombre del Insumo</label>
                <input type="text" name="nombre" id="nombre_in" placeholder="Ej: Gas Cloro" required>
            </div>
            <div class="form-group">
                <label>Unidad</label>
                <input type="text" name="unidad" id="unidad_in" placeholder="Kg, Lts, Cilindros" required>
            </div>
            <div class="form-group">
                <label>Stock Mínimo</label>
                <input type="number" step="0.01" name="minimo" id="minimo_in" placeholder="0.00" required>
            </div>
            <button type="submit" name="guardar" id="btn_txt">Registrar</button>
        </div>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Sustancia</th>
                    <th>Unidad de Medida</th>
                    <th>Stock Mínimo</th>
                    <th style="width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($resultado)) { ?>
                <tr>
                    <td><strong><?php echo hsc($row['nombre']); ?></strong></td>
                    <td><?php echo hsc($row['unidad_medida']); ?></td>
                    <td><?php echo number_format($row['inventario_minimo'], 2); ?></td>
                    <td>
                        <div class="acciones-container">
                            <button type="button" class="btn-edit-table" title="Editar" 
                                onclick="llenar(<?php echo $row['id']; ?>, <?php echo json_encode($row['nombre'], JSON_HEX_TAG|JSON_HEX_AMP); ?>, <?php echo json_encode($row['unidad_medida'], JSON_HEX_TAG|JSON_HEX_AMP); ?>, <?php echo $row['inventario_minimo']; ?>)">
                                <i class="fa fa-edit"></i>
                            </button>
                            <a href="index.php?route=gestion_sustancias&eliminar=<?php echo $row['id']; ?>" class="btn-del-table" title="Eliminar" 
                                onclick="return confirm('¿Estás seguro de eliminar este insumo?')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/gestion/gestion_sustancias.js"></script>

</body>
</html>
