<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'turno', 'entrega'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']); $turno = $_POST['turno'];
        $entrega = intval($_POST['entrega']);
        $novedades = trim($_POST['novedades'] ?? ''); $pendientes = trim($_POST['pendientes'] ?? '');
        $id_user = (int) $_SESSION['id_usuario'];
        $fecha = date('Y-m-d'); $hora = date('H:i:s');
        $turnos_validos = ['Mañana (07:00 - 15:00)', 'Tarde (15:00 - 23:00)', 'Noche (23:00 - 07:00)'];
        if (!in_array($turno, $turnos_validos) || !in_array($entrega, [0, 1])) { $mensaje = "error"; }
        else {
            $resumen = "Turno: $turno | Novedades: $novedades | Pendientes: $pendientes";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $resumen);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id; $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 28, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora, $entrega);
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
           m1.valor_medido as estado_entrega
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    JOIN medicion_horaria m1 ON m1.id_registro_diario = rd.id AND m1.id_parametro = 28
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-014 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_014.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-users"></i> Entrega de Guardia <span class="badge-gt">GT-CA-014</span></h2>
        <p class="subtitle">Registro de Entrega y Recepción de Turno</p>
    </div>
    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Relevo registrado con éxito!</div>
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
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nuevo Relevo</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Planta</th><th>Turno</th><th>Estado</th><th>Novedades / Pendientes</th></tr></thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <?php
                        $obs = $r['observaciones_generales'];
                        $turno_txt = '';
                        $detalles = $obs;
                        if (preg_match('/^Turno: (.+?) \| Novedades: (.+?) \| Pendientes: (.+)$/', $obs, $m)) {
                            $turno_txt = $m[1];
                            $detalles = "Novedades: {$m[2]} | Pendientes: {$m[3]}";
                        }
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td><?php echo hsc($turno_txt ?: $obs); ?></td>
                        <td style="color:<?php echo $r['estado_entrega']?'#2ecc71':'#f1c40f'; ?>"><?php echo $r['estado_entrega']?'✅ Operativa':'⚠️ Con Novedades'; ?></td>
                        <td style="font-size:13px;"><?php echo hsc($turno_txt ? $detalles : ''); ?></td>
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
                <h3><i class="fas fa-users"></i> <span>Registrar Relevo de Guardia</span></h3>
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
                    <label>Turno que entrega</label>
                    <select name="turno" required>
                        <option value="Mañana (07:00 - 15:00)">Mañana (07:00 - 15:00)</option>
                        <option value="Tarde (15:00 - 23:00)">Tarde (15:00 - 23:00)</option>
                        <option value="Noche (23:00 - 07:00)">Noche (23:00 - 07:00)</option>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Condición de Instalación</label>
                    <select name="entrega" required>
                        <option value="1">Operativa y Sin Novedad</option>
                        <option value="0">Operativa con Novedades</option>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Novedades</label>
                    <textarea name="novedades" placeholder="Resumen del turno..." rows="2"></textarea>
                </div>
                <div class="modal-campo">
                    <label>Pendientes para el relevo</label>
                    <textarea name="pendientes" placeholder="Tareas para el relevo..." rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar">Registrar Relevo</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/registro/gt_ca_table.js"></script>
<script src="assets/js/registro/gt_ca_014.js"></script>
</body>
</html>
