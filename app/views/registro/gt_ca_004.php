<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$insumos_query = mysqli_query($con, "SELECT id, nombre, unidad_medida FROM sustancia_quimica");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'sustancia', 'cantidad'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $id_sust = intval($_POST['sustancia']);
        $cantidad = $_POST['cantidad'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        if (!validar_numeric($cantidad, 0)) { $mensaje = "error"; }
        else {
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $id_inst, $id_user, $fecha);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO consumo_sustancia (id_registro_diario, id_sustancia, cantidad_consumida) VALUES (?, ?, ?)");
                $stmt1->bind_param("iid", $id_reg, $id_sust, $cantidad);
                $stmt2 = $con->prepare("INSERT INTO inventario_movimiento (id_sustancia, tipo_movimiento, cantidad, referencia_guia) VALUES (?, 'Salida', ?, 'Consumo Diario Planilla 004')");
                $stmt2->bind_param("id", $id_sust, $cantidad);
                if ($stmt1->execute() && $stmt2->execute()) {
                    $mensaje = "exito";
                }
                $stmt1->close();
                $stmt2->close();
            } else {
                $stmt->close();
            }
        }
    }
}

$plantas_data = [];
mysqli_data_seek($plantas_query, 0);
while ($p = mysqli_fetch_assoc($plantas_query)) { $plantas_data[] = $p; }

$insumos_data = [];
mysqli_data_seek($insumos_query, 0);
while ($s = mysqli_fetch_assoc($insumos_query)) { $insumos_data[] = $s; }

$registros = mysqli_query($con, "
    SELECT rd.id, rd.fecha, i.nombre as planta, s.nombre as sustancia, s.unidad_medida, cs.cantidad_consumida
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    JOIN consumo_sustancia cs ON cs.id_registro_diario = rd.id
    JOIN sustancia_quimica s ON cs.id_sustancia = s.id
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-004 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_004.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-weight-hanging"></i> Consumo Químico <span class="badge-gt">GT-CA-004</span></h2>
        <p class="subtitle">Control Diario de Consumo de Sustancias Químicas</p>
    </div>

    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Gasto registrado y descontado del inventario!</div>
    <?php elseif ($mensaje == "error"): ?>
        <div class="alerta-error"><i class="fas fa-exclamation-triangle"></i> Error al registrar. Verifique los datos.</div>
    <?php endif; ?>

    <div class="gtca-wrapper">
        <div class="tabla-toolbar">
            <div class="tabla-toolbar-left">
                <div class="tabla-buscar gtca-buscar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar..." autocomplete="off">
                </div>
                <select class="tabla-filtro gtca-filtrar">
                    <option value="">Todas las plantas</option>
                    <?php foreach ($plantas_data as $p): ?>
                        <option value="<?php echo hsc($p['nombre']); ?>"><?php echo hsc($p['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nuevo Consumo</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Planta</th>
                        <th>Sustancia</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td><?php echo hsc($r['sustancia']); ?></td>
                        <td><?php echo number_format($r['cantidad_consumida'], 2); ?></td>
                        <td><?php echo hsc($r['unidad_medida']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="gtca-info tabla-info"></div>
        <div class="gtca-paginar tabla-paginar"></div>
    </div>
</div>

<div class="modal-overlay" id="modalRegistro">
    <div class="modal-contenido">
        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="modal-header">
                <h3><i class="fas fa-weight-hanging"></i> <span>Registrar Consumo Químico</span></h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-campo">
                    <label>Planta / Estación</label>
                    <select name="planta" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($plantas_data as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo hsc($p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Sustancia Utilizada</label>
                    <select name="sustancia" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($insumos_data as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo hsc($s['nombre']); ?> (<?php echo hsc($s['unidad_medida']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Cantidad Consumida</label>
                    <input type="number" step="0.01" name="cantidad" required placeholder="0.00">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar">Registrar Gasto</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/registro/gt_ca_table.js"></script>
<script src="assets/js/registro/gt_ca_004.js"></script>
</body>
</html>
