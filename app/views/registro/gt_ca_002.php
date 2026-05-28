<?php
verificar_sesion();
$sustancias_query = mysqli_query($con, "SELECT id, nombre, unidad_medida FROM sustancia_quimica");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['sustancia', 'tipo', 'cantidad'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_sustancia = intval($_POST['sustancia']);
        $tipo = $_POST['tipo'];
        $cantidad = $_POST['cantidad'];
        $referencia = trim($_POST['referencia'] ?? '');
        $tipos_validos = ['Entrada', 'Salida', 'Ajuste'];
        if (!in_array($tipo, $tipos_validos)) { $mensaje = "error"; }
        elseif (!validar_numeric($cantidad, 0)) { $mensaje = "error"; }
        else {
            $stmt = $con->prepare("INSERT INTO inventario_movimiento (id_sustancia, tipo_movimiento, cantidad, referencia_guia) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $id_sustancia, $tipo, $cantidad, $referencia);
            if ($stmt->execute()) { $mensaje = "exito"; }
            $stmt->close();
        }
    }
}

$sustancias_data = [];
mysqli_data_seek($sustancias_query, 0);
while ($s = mysqli_fetch_assoc($sustancias_query)) { $sustancias_data[] = $s; }

$movimientos = mysqli_query($con, "
    SELECT im.id, im.fecha_movimiento, im.tipo_movimiento, im.cantidad, im.referencia_guia,
           s.nombre as sustancia, s.unidad_medida
    FROM inventario_movimiento im
    JOIN sustancia_quimica s ON im.id_sustancia = s.id
    ORDER BY im.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-002 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_002.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-boxes"></i> Inventario Químico <span class="badge-gt">GT-CA-002</span></h2>
        <p class="subtitle">Control de Entradas, Salidas y Ajustes de Sustancias Químicas</p>
    </div>

    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Movimiento registrado con éxito!</div>
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
                    <option value="">Todas las sustancias</option>
                    <?php foreach ($sustancias_data as $s): ?>
                        <option value="<?php echo hsc($s['nombre']); ?>"><?php echo hsc($s['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nuevo Movimiento</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Sustancia</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($movimientos)): ?>
                    <tr>
                        <td><?php echo $r['fecha_movimiento'] ? date('d/m/Y H:i', strtotime($r['fecha_movimiento'])) : '-'; ?></td>
                        <td><strong><?php echo hsc($r['sustancia']); ?></strong> (<?php echo hsc($r['unidad_medida']); ?>)</td>
                        <td><span style="color:<?php echo $r['tipo_movimiento']=='Entrada'?'#2ecc71':($r['tipo_movimiento']=='Salida'?'#e74c3c':'#f1c40f'); ?>"><?php echo hsc($r['tipo_movimiento']); ?></span></td>
                        <td><?php echo number_format($r['cantidad'], 2); ?></td>
                        <td><?php echo hsc($r['referencia_guia'] ?? '-'); ?></td>
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
                <h3><i class="fas fa-boxes"></i> <span>Nuevo Movimiento de Inventario</span></h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-campo">
                    <label>Sustancia Química</label>
                    <select name="sustancia" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($sustancias_data as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo hsc($s['nombre']); ?> (<?php echo hsc($s['unidad_medida']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Tipo de Movimiento</label>
                    <select name="tipo" required>
                        <option value="Entrada">Entrada (Ingreso)</option>
                        <option value="Salida">Salida (Consumo)</option>
                        <option value="Ajuste">Ajuste de Inventario</option>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Cantidad</label>
                    <input type="number" step="0.01" name="cantidad" required placeholder="0.00">
                </div>
                <div class="modal-campo">
                    <label>Nro. Guía / Referencia</label>
                    <input type="text" name="referencia" placeholder="Ej: G-5542">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar">Registrar Movimiento</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/registro/gt_ca_table.js"></script>
<script src="assets/js/registro/gt_ca_002.js"></script>
</body>
</html>
