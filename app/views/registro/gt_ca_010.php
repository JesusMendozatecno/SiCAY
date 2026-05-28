<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'volumen_lodo', 'destino'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $volumen = $_POST['volumen_lodo'];
        $destino = $_POST['destino'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d'); $hora = date('H:i:s');
        $destinos_validos = ['Laguna de Secado 1', 'Laguna de Secado 2', 'Lechos de Secado', 'Retiro Externo'];
        if (!in_array($destino, $destinos_validos)) { $mensaje = "error"; }
        elseif (!validar_numeric($volumen, 0)) { $mensaje = "error"; }
        else {
            $obs = "Disposición de Lodos - Destino: " . $destino;
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id; $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 17, ?, 'Sedimentada')");
                $stmt1->bind_param("isd", $id_reg, $hora, $volumen);
                if ($stmt1->execute()) { $mensaje = "exito"; }
                $stmt1->close();
            } else { $stmt->close(); }
        }
    }
}
$plantas_data = [];
mysqli_data_seek($plantas_query, 0);
while ($p = mysqli_fetch_assoc($plantas_query)) { $plantas_data[] = $p; }
$registros = mysqli_query($con, "
    SELECT rd.id, rd.fecha, i.nombre as planta, rd.observaciones_generales,
           m1.valor_medido as volumen
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    LEFT JOIN medicion_horaria m1 ON m1.id_registro_diario = rd.id AND m1.id_parametro = 17
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-010 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_010.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-trash-alt"></i> Gestión de Lodos <span class="badge-gt">GT-CA-010</span></h2>
        <p class="subtitle">Registro de Disposición de Lodos y Volumen Extraído</p>
    </div>
    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Registro de lodos guardado!</div>
    <?php elseif ($mensaje == "error"): ?>
        <div class="alerta-error"><i class="fas fa-exclamation-triangle"></i> Error al guardar. Verifique los datos.</div>
    <?php endif; ?>
    <div class="gtca-wrapper">
        <div class="tabla-toolbar">
            <div class="tabla-toolbar-left">
                <div class="tabla-buscar gtca-buscar"><i class="fas fa-search"></i><input type="text" placeholder="Buscar..." autocomplete="off"></div>
                <select class="tabla-filtro gtca-filtrar">
                    <option value="">Todas las plantas</option>
                    <?php foreach ($plantas_data as $p): ?>
                        <option value="<?php echo hsc($p['nombre']); ?>"><?php echo hsc($p['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nuevo Registro</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Planta</th><th>Destino</th><th>Volumen (m³)</th></tr></thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td><?php echo hsc(str_replace('Disposición de Lodos - Destino: ', '', $r['observaciones_generales'])); ?></td>
                        <td><?php echo number_format($r['volumen'], 1); ?></td>
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
                <h3><i class="fas fa-trash-alt"></i> <span>Registrar Disposición de Lodos</span></h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-campo">
                    <label>Planta / Unidad</label>
                    <select name="planta" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($plantas_data as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo hsc($p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Volumen Extraído (m³)</label>
                    <input type="number" step="0.1" name="volumen_lodo" required placeholder="0.0">
                </div>
                <div class="modal-campo">
                    <label>Destino Final</label>
                    <select name="destino" required>
                        <option value="Laguna de Secado 1">Laguna de Secado 1</option>
                        <option value="Laguna de Secado 2">Laguna de Secado 2</option>
                        <option value="Lechos de Secado">Lechos de Secado</option>
                        <option value="Retiro Externo">Retiro Externo (Camión)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar">Guardar Registro</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/registro/gt_ca_table.js"></script>
<script src="assets/js/registro/gt_ca_010.js"></script>
</body>
</html>
