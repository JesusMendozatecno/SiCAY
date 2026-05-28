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

$resultado = mysqli_query($con, "SELECT id, nombre, unidad_medida FROM parametro ORDER BY id DESC");

$parametros = [];
$unidades = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    $parametros[] = $row;
    if (!in_array($row['unidad_medida'], $unidades)) {
        $unidades[] = $row['unidad_medida'];
    }
}
sort($unidades);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Parámetros - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/gestion/gestion_parametros.css">
</head>
<body>

<div id="bubbles"></div>

<div class="container">
    <div class="page-header">
        <a href="index.php?route=registros" class="btn-back"><i class="fas fa-chevron-left"></i> Volver</a>
        <h1 class="page-title"><i class="fa fa-vial"></i> Gestión de Parámetros</h1>
    </div>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alerta-exito">
            <i class="fas fa-check-circle"></i> 
            <?php
                if ($_SESSION['msg'] == "agregado") echo "¡Parámetro agregado con éxito!";
                if ($_SESSION['msg'] == "eliminado") echo "¡Parámetro eliminado con éxito!";
                if ($_SESSION['msg'] == "editado") echo "¡Se guardaron los cambios correctamente!";
                unset($_SESSION['msg']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alerta-error">
            <i class="fas fa-exclamation-triangle"></i> <?php echo hsc($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="tabla-toolbar">
        <div class="tabla-toolbar-left">
            <div class="tabla-buscar">
                <i class="fas fa-search"></i>
                <input type="text" id="buscarParam" placeholder="Buscar parámetro..." autocomplete="off">
            </div>
            <select id="filtroUnidad" class="tabla-filtro">
                <option value="">Todas las unidades</option>
                <?php foreach ($unidades as $u): ?>
                    <option value="<?php echo hsc($u); ?>"><?php echo hsc($u); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="tabla-btn-agregar" id="btnAgregar">
            <i class="fas fa-plus"></i> Agregar Parámetro
        </button>
    </div>

    <!-- Tabla -->
    <div class="tabla-wrapper">
        <table class="tabla" id="tablaParametros">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Parámetro</th>
                    <th>Unidad</th>
                    <th style="width:110px;">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaBody">
                <?php foreach ($parametros as $i => $p): ?>
                <tr data-id="<?php echo $p['id']; ?>"
                    data-nombre="<?php echo hsc($p['nombre']); ?>"
                    data-unidad="<?php echo hsc($p['unidad_medida']); ?>">
                    <td class="col-num"><?php echo $i + 1; ?></td>
                    <td><strong><?php echo hsc($p['nombre']); ?></strong></td>
                    <td><span class="badge-param"><?php echo hsc($p['unidad_medida']); ?></span></td>
                    <td>
                        <div class="tabla-acciones">
                            <button class="btn-editar" title="Editar" data-id="<?php echo $p['id']; ?>">
                                <i class="fa fa-edit"></i>
                            </button>
                            <a href="index.php?route=gestion_parametros&eliminar=<?php echo $p['id']; ?>"
                               class="btn-eliminar" title="Eliminar"
                               onclick="return confirm('¿Estás seguro de eliminar este parámetro?')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Info + Paginación -->
    <div class="tabla-info" id="tablaInfo">Mostrando 0 de 0 registros</div>
    <div class="tabla-paginar" id="tablaPaginar"></div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalParam">
    <div class="modal-contenido">
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id_editar" id="id_editar" value="">

            <div class="modal-header">
                <h3 id="modalTitulo"><i class="fa fa-plus-circle"></i> <span id="modalTituloText">Agregar Parámetro</span></h3>
                <button type="button" class="modal-cerrar" id="btnCerrarModal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="modal-campo">
                    <label for="nombre">Nombre del Parámetro</label>
                    <input type="text" name="nombre" id="nombre" placeholder="Ej: Cloro Residual" required>
                </div>
                <div class="modal-campo">
                    <label for="unidad">Unidad de Medida</label>
                    <input type="text" name="unidad" id="unidad" placeholder="Ej: mg/L" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" id="btnCancelarModal">Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar" id="btnGuardar">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/gestion/gestion_parametros.js"></script>
</body>
</html>
