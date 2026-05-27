<?php
verificar_sesion();

$r = $con->query("SHOW COLUMNS FROM sustancia_quimica LIKE 'cantidad_maxima'");
if ($r && $r->num_rows === 0) {
    $con->query("ALTER TABLE sustancia_quimica ADD COLUMN cantidad_maxima DECIMAL(10,2) DEFAULT 0 AFTER inventario_minimo");
}
$r2 = $con->query("SHOW COLUMNS FROM sustancia_quimica WHERE `Field` = 'id' AND `Extra` LIKE '%auto_increment%'");
if ($r2 && $r2->num_rows === 0) {
    $con->query("DELETE FROM sustancia_quimica WHERE id = 0");
    $max = $con->query("SELECT COALESCE(MAX(id),0)+1 as next FROM sustancia_quimica")->fetch_assoc()['next'];
    $con->query("ALTER TABLE sustancia_quimica MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
    $con->query("ALTER TABLE sustancia_quimica AUTO_INCREMENT = $max");
}

$error_db = $_SESSION['flash_inventario_error'] ?? '';
unset($_SESSION['flash_inventario_error']);
$mensaje = $_SESSION['flash_inventario'] ?? '';
unset($_SESSION['flash_inventario']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');

    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'agregar') {
            $nombre = trim($_POST['nombre']);
            $unidad = trim($_POST['unidad']);
            $cantidad_maxima = $_POST['cantidad_maxima'];
            $faltantes = validar_requeridos(['nombre', 'unidad', 'cantidad_maxima'], $_POST);
            if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúñÑ ]+$/', $nombre)) {
                $_SESSION['flash_inventario'] = 'nombre_invalido';
            } elseif (empty($faltantes) && validar_numeric($cantidad_maxima, 0)) {
                $check = $con->prepare("SELECT id FROM sustancia_quimica WHERE nombre = ?");
                $check->bind_param("s", $nombre);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $_SESSION['flash_inventario'] = 'duplicado';
                } else {
                    $stmt = $con->prepare("INSERT INTO sustancia_quimica (nombre, unidad_medida, inventario_minimo, cantidad_maxima) VALUES (?, ?, 0, ?)");
                    $stmt->bind_param("ssd", $nombre, $unidad, $cantidad_maxima);
                    if ($stmt->execute()) {
                        $_SESSION['flash_inventario'] = 'agregado';
                    } else {
                        $_SESSION['flash_inventario'] = 'error';
                        $_SESSION['flash_inventario_error'] = $stmt->error;
                    }
                    $stmt->close();
                }
                $check->close();
            } else {
                $_SESSION['flash_inventario'] = 'error';
            }
            header("Location: index.php?route=inventario");
            exit;
        } elseif ($_POST['action'] == 'editar') {
            $id = intval($_POST['id']);
            $nueva_cantidad = $_POST['nueva_cantidad'];
            if (validar_numeric($nueva_cantidad, 0)) {
                $stmt = $con->prepare("UPDATE sustancia_quimica SET cantidad_maxima = cantidad_maxima + ? WHERE id = ?");
                $stmt->bind_param("di", $nueva_cantidad, $id);
                if ($stmt->execute()) {
                    $_SESSION['flash_inventario'] = 'editado';
                } else {
                    $_SESSION['flash_inventario'] = 'error';
                    $_SESSION['flash_inventario_error'] = $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['flash_inventario'] = 'error';
            }
            header("Location: index.php?route=inventario");
            exit;
        }
    }
}

if (isset($_GET['eliminar_mov'])) {
    $id_mov = intval($_GET['eliminar_mov']);
    $stmt = $con->prepare("DELETE FROM inventario_movimiento WHERE id = ?");
    $stmt->bind_param("i", $id_mov);
    if ($stmt->execute()) {
        $mensaje = 'mov_eliminado';
    } else {
        $mensaje = 'error_eliminar_mov';
    }
    $stmt->close();
}

if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $con->prepare("DELETE FROM sustancia_quimica WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $mensaje = 'eliminado';
    } else {
        $mensaje = 'error_eliminar';
    }
    $stmt->close();
}

$sql_mov = "SELECT m.id, s.nombre, m.tipo_movimiento, m.cantidad, s.unidad_medida, m.referencia_guia, m.fecha_movimiento 
            FROM inventario_movimiento m 
            JOIN sustancia_quimica s ON m.id_sustancia = s.id 
            ORDER BY m.fecha_movimiento DESC, m.id DESC";
$movimientos = mysqli_query($con, $sql_mov);
if (!$movimientos) die("Error SQL: " . mysqli_error($con) . "<br>Query: " . $sql_mov);

$sql_stock = "SELECT s.id, s.nombre, s.unidad_medida, s.inventario_minimo, COALESCE(s.cantidad_maxima,0) as stock_total,
                     COALESCE(s.cantidad_maxima,0) as cantidad_maxima,
                     (SELECT m2.cantidad FROM inventario_movimiento m2 
                      WHERE m2.id_sustancia = s.id AND m2.tipo_movimiento = 'Salida' 
                      ORDER BY m2.id DESC LIMIT 1) as ultima_salida
              FROM sustancia_quimica s
              ORDER BY s.nombre ASC";
$stock = mysqli_query($con, $sql_stock);
if (!$stock) die("Error SQL: " . mysqli_error($con) . "<br>Query: " . $sql_stock);
$stock_data = [];
while ($row = mysqli_fetch_assoc($stock)) {
    $stock_data[] = $row;
}
$total_productos = count($stock_data);
$total_movimientos = mysqli_num_rows($movimientos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/inventario/inventario.css">
</head>
<body>

<div id="bubbles"></div>

<div class="inventario-layout">

    <!-- Sidebar -->
    <aside class="inventario-sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-warehouse"></i>
            Inventario
        </div>
        <nav class="sidebar-nav">
            <button class="tab-link active" data-tab="almacen">
                <i class="fas fa-boxes"></i> Almacén
                <span class="badge-count"><?php echo $total_productos; ?></span>
            </button>
            <button class="tab-link" data-tab="movimientos">
                <i class="fas fa-truck-loading"></i> Movimientos de almacén
                <span class="badge-count"><?php echo $total_movimientos; ?></span>
            </button>
        </nav>
        <div class="sidebar-footer">
            <a href="index.php?route=dashboard" class="btn-back-sidebar">
                <i class="fas fa-chevron-left"></i> Volver
            </a>
        </div>
    </aside>

    <!-- Content -->
    <div class="inventario-content">

        <!-- ══════ ALMACÉN ══════ -->
        <div id="tab-almacen" class="tab-panel active">

            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title"><i class="fas fa-cubes"></i> Inventario General</h3>
                    <button class="btn-agregar" onclick="abrirModalAgregar()"><i class="fas fa-plus"></i> Agregar Producto</button>
                </div>
                <p class="section-desc">Productos registrados en el sistema y sus cantidades actuales.</p>

                <?php if ($mensaje == 'agregado'): ?>
                    <div class="alerta-exito"><i class="fas fa-check-circle"></i> Producto agregado con éxito</div>
                <?php elseif ($mensaje == 'editado'): ?>
                    <div class="alerta-exito"><i class="fas fa-check-circle"></i> Cantidad máxima actualizada</div>
                <?php elseif ($mensaje == 'eliminado'): ?>
                    <div class="alerta-exito"><i class="fas fa-check-circle"></i> Producto eliminado</div>
                <?php elseif ($mensaje == 'duplicado'): ?>
                    <div class="alerta-error"><i class="fas fa-exclamation-triangle"></i> Ya existe un producto con ese nombre</div>
                <?php elseif ($mensaje == 'nombre_invalido'): ?>
                    <div class="alerta-error"><i class="fas fa-exclamation-triangle"></i> El nombre solo puede contener letras</div>
                <?php elseif ($mensaje == 'mov_eliminado'): ?>
                    <div class="alerta-exito"><i class="fas fa-check-circle"></i> Movimiento eliminado correctamente</div>
                <?php elseif ($mensaje == 'error_eliminar_mov'): ?>
                    <div class="alerta-error"><i class="fas fa-exclamation-triangle"></i> Error al eliminar el movimiento</div>
                <?php elseif ($mensaje == 'error_eliminar'): ?>
                    <div class="alerta-error"><i class="fas fa-exclamation-triangle"></i> No se puede eliminar: el producto tiene movimientos asociados</div>
                <?php elseif ($mensaje == 'error'): ?>
                    <div class="alerta-error"><i class="fas fa-exclamation-triangle"></i> Error al procesar la solicitud<?php echo $error_db ? ': ' . hsc($error_db) : ''; ?></div>
                <?php endif; ?>

                <div class="table-wrap">
                    <table class="reportes-table" id="tablaAlmacen">
                        <thead>
                            <tr>
                                <th><i class="fas fa-flask"></i> Producto</th>
                                <th><i class="fas fa-ruler"></i> Unidad</th>
                                <th><i class="fas fa-weight-hanging"></i> Stock Actual</th>
                                <th><i class="fas fa-arrow-left"></i> Última Salida</th>
                                <th><i class="fas fa-check-circle"></i> Estado</th>
                                <th><i class="fas fa-cogs"></i> Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyAlmacen">
                            <?php foreach ($stock_data as $p):
                                $estado = $p['stock_total'] <= $p['inventario_minimo'] ? 'alerta' : 'ok';
                                $estado_texto = $p['stock_total'] <= $p['inventario_minimo'] ? 'Bajo stock' : 'Disponible';
                            ?>
                            <tr data-nombre="<?php echo hsc($p['nombre']); ?>" data-stock="<?php echo $p['stock_total']; ?>">
                                <td><strong><?php echo hsc($p['nombre']); ?></strong></td>
                                <td><?php echo hsc($p['unidad_medida']); ?></td>
                                <td><?php echo number_format($p['stock_total'], 2); ?></td>
                                <td><?php echo $p['ultima_salida'] !== null ? number_format($p['ultima_salida'], 2) : '---'; ?></td>
                                <td><span class="badge <?php echo $estado; ?>"><?php echo $estado_texto; ?></span></td>
                                <td>
                                    <div class="acciones-container">
                                        <button class="btn-accion btn-editar" title="Editar" onclick="abrirModalEditar(<?php echo $p['id']; ?>, '<?php echo hsc($p['nombre']); ?>', <?php echo $p['cantidad_maxima']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="index.php?route=inventario&eliminar=<?php echo $p['id']; ?>" class="btn-accion btn-eliminar" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este producto?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ══════ MOVIMIENTOS ══════ -->
        <div id="tab-movimientos" class="tab-panel">

            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title"><i class="fas fa-clipboard-list"></i> Movimientos del Almacén</h3>
                </div>
                <p class="section-desc">Auditoría completa de entradas, salidas y ajustes realizados en el inventario.</p>

                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="busquedaMov" onkeyup="filtrarMovimientos()" placeholder="Buscar por sustancia, operación o referencia...">
                </div>

                <div class="table-wrap">
                    <table class="reportes-table" id="tablaMovimientos">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar-day"></i> Fecha</th>
                                <th><i class="fas fa-flask"></i> Sustancia</th>
                                <th><i class="fas fa-exchange-alt"></i> Operación</th>
                                <th><i class="fas fa-weight-hanging"></i> Cantidad</th>
                                <th><i class="fas fa-hashtag"></i> Referencia / Guía</th>
                                <th><i class="fas fa-cogs"></i> Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($movimientos)):
                                $clase = $row['tipo_movimiento'] == 'Entrada' ? 'bg-entrada' : ($row['tipo_movimiento'] == 'Salida' ? 'bg-salida' : 'bg-ajuste');
                                $fecha = date("d/m/Y", strtotime($row['fecha_movimiento']));
                            ?>
                            <tr>
                                <td class="td-fecha"><?php echo $fecha; ?></td>
                                <td><strong><?php echo hsc($row['nombre']); ?></strong></td>
                                <td><span class="badge <?php echo $clase; ?>"><?php echo hsc($row['tipo_movimiento']); ?></span></td>
                                <td><?php echo hsc($row['cantidad']) . ' ' . hsc($row['unidad_medida']); ?></td>
                                <td><?php echo $row['referencia_guia'] ? hsc($row['referencia_guia']) : '<span class="td-fecha">---</span>'; ?></td>
                                <td>
                                    <a href="index.php?route=inventario&eliminar_mov=<?php echo $row['id']; ?>" class="btn-accion btn-eliminar" title="Eliminar movimiento" onclick="return confirm('¿Estás seguro de eliminar este movimiento? Esto no afectará al producto.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- Modal Agregar Producto -->
<div id="modalAgregar" class="modal-overlay" style="display:none;">
    <div class="modal-contenido">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Agregar Producto</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modalAgregar')">&times;</button>
        </div>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="agregar">
            <div class="modal-body">
                <label>Nombre del Producto</label>
                <input type="text" name="nombre" required pattern="[A-Za-zÁÉÍÓÚáéíóúñÑ ]+" title="Solo letras" placeholder="Ej: Cloro" oninput="this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúñÑ ]/g, '')">
                <label>Unidad de Medida</label>
                <select name="unidad" required>
                    <option value="">-- Seleccione --</option>
                    <option value="Litros">Litros (L)</option>
                    <option value="Mililitros">Mililitros (mL)</option>
                    <option value="Metros cúbicos">Metros cúbicos (m³)</option>
                    <option value="Galones">Galones (gal)</option>
                    <option value="Kilogramos">Kilogramos (kg)</option>
                    <option value="Gramos">Gramos (g)</option>
                    <option value="Toneladas">Toneladas (t)</option>
                    <option value="Miligramos">Miligramos (mg)</option>
                    <option value="Unidades">Unidades (ud)</option>
                    <option value="Cilindros">Cilindros</option>
                    <option value="Barriles">Barriles</option>
                    <option value="Sacos">Sacos</option>
                    <option value="Cajas">Cajas</option>
                    <option value="Piezas">Piezas</option>
                </select>
                <label>Cantidad Máxima</label>
                <input type="number" step="0.01" name="cantidad_maxima" required placeholder="0.00">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarModal('modalAgregar')">Cancelar</button>
                <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Producto -->
<div id="modalEditar" class="modal-overlay" style="display:none;">
    <div class="modal-contenido">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Cantidad Máxima</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modalEditar')">&times;</button>
        </div>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="editar">
            <input type="hidden" name="id" id="editId">
            <div class="modal-body">
                <p class="modal-info">Producto: <strong id="editNombre"></strong></p>
                <p class="modal-info">Cantidad máxima actual: <strong id="editMaxActual"></strong></p>
                <label>Nueva cantidad recibida</label>
                <input type="number" step="0.01" name="nueva_cantidad" required placeholder="0.00">
                <p class="modal-nota"><i class="fas fa-info-circle"></i> Se sumará a la cantidad máxima actual</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarModal('modalEditar')">Cancelar</button>
                <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Actualizar</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/inventario/inventario.js"></script>
<script>
setTimeout(function(){
    var alerts = document.querySelectorAll('.alerta-exito, .alerta-error');
    alerts.forEach(function(el){
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(function(){ el.style.display = 'none'; }, 500);
    });
}, 3000);
</script>
</body>
</html>
