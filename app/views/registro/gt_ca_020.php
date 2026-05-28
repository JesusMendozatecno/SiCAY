<?php
verificar_sesion();

$r = $con->query("SHOW COLUMNS FROM instalacion LIKE 'capacidad_diseno'");
if ($r && $r->num_rows === 0) {
    $con->query("ALTER TABLE instalacion ADD COLUMN capacidad_diseno DECIMAL(10,2) DEFAULT NULL AFTER ubicacion");
}

$mensaje = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['nombre', 'tipo', 'ubicacion'], $_POST);
    if (!empty($faltantes)) {
        $mensaje = "error";
        $error_msg = "Faltan campos: " . implode(', ', $faltantes);
    } else {
        $nombre = trim($_POST['nombre']);
        $tipo = $_POST['tipo'];
        $ubicacion = trim($_POST['ubicacion']);
        $capacidad = $_POST['capacidad'] !== '' ? $_POST['capacidad'] : null;
        $estado = $_POST['estado'] ?? 'Activo';

        if (!empty($_POST['editar_id'])) {
            $id = intval($_POST['editar_id']);
            $stmt = $con->prepare("UPDATE instalacion SET nombre=?, tipo=?, ubicacion=?, capacidad_diseno=?, estado=? WHERE id=?");
            $stmt->bind_param("sssssi", $nombre, $tipo, $ubicacion, $capacidad, $estado, $id);
            $mensaje = $stmt->execute() ? "editado" : "error";
            if ($mensaje == "error") $error_msg = $stmt->error;
            $stmt->close();
        } else {
            $check = $con->prepare("SELECT id FROM instalacion WHERE nombre = ?");
            $check->bind_param("s", $nombre);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $mensaje = "duplicado";
            } else {
                $stmt = $con->prepare("INSERT INTO instalacion (nombre, tipo, ubicacion, capacidad_diseno, estado) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $nombre, $tipo, $ubicacion, $capacidad, $estado);
                $mensaje = $stmt->execute() ? "exito" : "error";
                if ($mensaje == "error") $error_msg = $stmt->error;
                $stmt->close();
            }
            $check->close();
        }
    }
}

if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $con->prepare("DELETE FROM instalacion WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?route=instalaciones");
    exit;
}

$instalaciones = mysqli_query($con, "SELECT * FROM instalacion ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Instalaciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_020.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div id="bubbles"></div>

<div class="contenedor">
    <h2 style="color:#fff;font-size:18px;font-weight:600;margin:0 0 16px;"><i class="fas fa-industry"></i> Instalaciones</h2>

    <?php if ($mensaje): ?>
    <div class="alerta alerta-<?php echo $mensaje == 'exito' ? 'exito' : ($mensaje == 'editado' ? 'info' : ($mensaje == 'duplicado' ? 'duplicado' : 'error')); ?>" id="alerta">
        <i class="fas fa-<?php echo $mensaje == 'exito' ? 'check-circle' : ($mensaje == 'editado' ? 'check-circle' : ($mensaje == 'duplicado' ? 'exclamation-triangle' : 'exclamation-triangle')); ?>"></i>
        <?php
        if ($mensaje == 'exito') echo 'Instalación registrada con éxito';
        elseif ($mensaje == 'editado') echo 'Instalación actualizada con éxito';
        elseif ($mensaje == 'duplicado') echo 'Ya existe una instalación con ese nombre';
        else echo 'Error: ' . hsc($error_msg);
        ?>
    </div>
    <?php endif; ?>

    <div class="gtca-wrapper">
        <div class="tabla-toolbar">
            <div class="tabla-toolbar-left">
                <div class="tabla-buscar gtca-buscar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar..." autocomplete="off">
                </div>
                <select class="tabla-filtro gtca-filtrar">
                    <option value="">Todos los tipos</option>
                    <option value="Planta">Planta</option>
                    <option value="Pozo">Pozo</option>
                    <option value="Estación Cloradora">Estación Cloradora</option>
                    <option value="Tanque de Almacenamiento">Tanque de Almacenamiento</option>
                    <option value="Estación de Bombeo">Estación de Bombeo</option>
                </select>
            </div>
            <button class="tabla-btn-agregar" data-modal-open="modal-registro"><i class="fas fa-plus"></i> Agregar</button>
        </div>

        <div class="tabla-wrapper">
            <?php if (mysqli_num_rows($instalaciones) > 0): ?>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Ubicación</th>
                        <th>Cap. Diseño</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while($row = mysqli_fetch_assoc($instalaciones)): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><strong><?php echo hsc($row['nombre']); ?></strong></td>
                        <td><?php echo hsc($row['tipo']); ?></td>
                        <td><?php echo hsc($row['ubicacion']); ?></td>
                        <td><?php echo $row['capacidad_diseno'] ? number_format($row['capacidad_diseno'], 2) : '---'; ?></td>
                        <td><span class="badge-estado <?php echo $row['estado'] == 'Activo' ? 'badge-activo' : 'badge-inactivo'; ?>"><?php echo hsc($row['estado']); ?></span></td>
                        <td>
                            <div class="tabla-acciones">
                                <button class="btn-editar" title="Editar" onclick="editar(<?php echo $row['id']; ?>, '<?php echo hsc(addslashes($row['nombre'])); ?>', '<?php echo hsc(addslashes($row['tipo'])); ?>', '<?php echo hsc(addslashes($row['ubicacion'])); ?>', '<?php echo $row['capacidad_diseno'] ? $row['capacidad_diseno'] : ''; ?>', '<?php echo hsc($row['estado']); ?>')"><i class="fas fa-pen"></i></button>
                                <button class="btn-eliminar" title="Eliminar" data-modal-open="modal-eliminar" data-eliminar-url="index.php?route=instalaciones&eliminar=<?php echo $row['id']; ?>" data-eliminar-nombre="<?php echo hsc($row['nombre']); ?>"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="vacio"><i class="fas fa-inbox"></i> No hay instalaciones registradas</div>
            <?php endif; ?>
        </div>

        <div class="gtca-info tabla-info"></div>
        <div class="gtca-paginar tabla-paginar"></div>
    </div>
</div>

<div class="modal-overlay" id="modal-registro">
    <div class="modal-contenido">
        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Nueva Instalación</h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-grid">
                    <div class="modal-campo">
                        <label>Nombre</label>
                        <input type="text" name="nombre" required placeholder="Ej: Planta Los Naranjos">
                    </div>
                    <div class="modal-campo">
                        <label>Tipo</label>
                        <select name="tipo" required>
                            <option value="">-- Seleccione --</option>
                            <option value="Planta">Planta</option>
                            <option value="Pozo">Pozo</option>
                            <option value="Estación Cloradora">Estación Cloradora</option>
                            <option value="Tanque de Almacenamiento">Tanque de Almacenamiento</option>
                            <option value="Estación de Bombeo">Estación de Bombeo</option>
                        </select>
                    </div>
                    <div class="modal-campo full-w">
                        <label>Ubicación</label>
                        <input type="text" name="ubicacion" required placeholder="Ej: San Felipe, Yaracuy">
                    </div>
                    <div class="modal-campo">
                        <label>Capacidad (L/s)</label>
                        <input type="number" step="0.01" name="capacidad" placeholder="Ej: 500.00">
                    </div>
                    <div class="modal-campo">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Registrar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modal-editar">
    <div class="modal-contenido">
        <form method="POST" id="form-editar">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="editar_id" id="edit-id">
            <div class="modal-header">
                <h3><i class="fas fa-pen"></i> Editar Instalación</h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-grid">
                    <div class="modal-campo">
                        <label>Nombre</label>
                        <input type="text" name="nombre" id="edit-nombre" required>
                    </div>
                    <div class="modal-campo">
                        <label>Tipo</label>
                        <select name="tipo" id="edit-tipo" required>
                            <option value="Planta">Planta</option>
                            <option value="Pozo">Pozo</option>
                            <option value="Estación Cloradora">Estación Cloradora</option>
                            <option value="Tanque de Almacenamiento">Tanque de Almacenamiento</option>
                            <option value="Estación de Bombeo">Estación de Bombeo</option>
                        </select>
                    </div>
                    <div class="modal-campo full-w">
                        <label>Ubicación</label>
                        <input type="text" name="ubicacion" id="edit-ubicacion" required>
                    </div>
                    <div class="modal-campo">
                        <label>Capacidad (L/s)</label>
                        <input type="number" step="0.01" name="capacidad" id="edit-capacidad">
                    </div>
                    <div class="modal-campo">
                        <label>Estado</label>
                        <select name="estado" id="edit-estado">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modal-eliminar">
    <div class="modal-contenido" style="max-width:420px;">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i> Confirmar Eliminación</h3>
            <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
        </div>
        <div class="modal-body" style="text-align:center;">
            <p style="color:rgba(255,255,255,0.7);font-size:14px;margin:0;">
                ¿Eliminar <strong id="eliminar-nombre"></strong>?
            </p>
        </div>
        <div class="modal-footer" style="justify-content:center;">
            <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
            <a id="eliminar-confirmar" href="#" class="btn-guardar" style="background:#e74c3c;"><i class="fas fa-trash"></i> Eliminar</a>
        </div>
    </div>
</div>

<script src="assets/js/registro/gt_ca_020.js"></script>
</body>
</html>
