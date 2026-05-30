<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'nombre_trabajador', 'tipo_epp', 'entrega', 'estado'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $trabajador = trim($_POST['nombre_trabajador']); $tipo_epp = $_POST['tipo_epp'];
        $entrega = intval($_POST['entrega']); $estado = intval($_POST['estado']);
        $observacion = trim($_POST['observacion'] ?? '');
        $id_user = (int) $_SESSION['id_usuario'];
        $fecha = date('Y-m-d'); $hora = date('H:i:s');
        $epp_validos = ['Botas de Seguridad', 'Casco e Iluminación', 'Guantes de Nitrilo/Químicos', 'Máscara de Gas Cloro'];
        if (!in_array($tipo_epp, $epp_validos) || !in_array($entrega, [0, 1]) || !in_array($estado, [0, 1])) { $mensaje = "error"; }
        else {
            $detalles = "Trabajador: $trabajador | EPP: $tipo_epp | Nota: $observacion";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $detalles);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id; $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 36, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora, $entrega);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 37, ?, 'Salida')");
                $stmt2->bind_param("isi", $id_reg, $hora, $estado);
                if ($stmt1->execute() && $stmt2->execute()) { $mensaje = "exito"; }
                $stmt1->close(); $stmt2->close();
            } else { $stmt->close(); }
        }
    }
}
$plantas_data = [];
mysqli_data_seek($plantas_query, 0);
while ($p = mysqli_fetch_assoc($plantas_query)) { $plantas_data[] = $p; }
$registros = mysqli_query($con, "
    SELECT rd.id, rd.fecha, i.nombre as planta, rd.observaciones_generales,
           m1.valor_medido as entrega, m2.valor_medido as estado_epp
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    JOIN medicion_horaria m1 ON m1.id_registro_diario = rd.id AND m1.id_parametro = 36
    JOIN medicion_horaria m2 ON m2.id_registro_diario = rd.id AND m2.id_parametro = 37
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-019 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_019.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-user-shield"></i> Equipos de Protección <span class="badge-gt">GT-CA-019</span></h2>
        <p class="subtitle">Control de Entrega de Equipos de Protección Personal (EPP)</p>
    </div>
    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Registro de EPP guardado con éxito!</div>
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
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nueva Entrega</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Planta</th><th>Trabajador</th><th>EPP</th><th>Entrega</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <?php
                        $obs = $r['observaciones_generales'];
                        $trab = $epp = $nota = $obs;
                        if (preg_match('/^Trabajador: (.+?) \| EPP: (.+?) \| Nota: (.+)$/', $obs, $m)) {
                            $trab = $m[1]; $epp = $m[2]; $nota = $m[3];
                        }
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td><?php echo hsc($trab); ?></td>
                        <td><?php echo hsc($epp); ?></td>
                        <td style="color:<?php echo $r['entrega']?'#2ecc71':'#f1c40f'; ?>"><?php echo $r['entrega']?'✅ Entregado':'⏳ Pendiente'; ?></td>
                        <td style="color:<?php echo $r['estado_epp']?'#2ecc71':'#e74c3c'; ?>"><?php echo $r['estado_epp']?'🆕 Operativo':'♻️ Recambio'; ?></td>
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
                <h3><i class="fas fa-user-shield"></i> <span>Registrar Entrega de EPP</span></h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-campo">
                    <label>Planta / Adscripción</label>
                    <select name="planta" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($plantas_data as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo hsc($p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Nombre del Trabajador</label>
                    <input type="text" name="nombre_trabajador" required placeholder="Nombre y Apellido">
                </div>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Equipo</label><select name="tipo_epp" required><option value="Botas de Seguridad">Botas de Seguridad</option><option value="Casco e Iluminación">Casco e Iluminación</option><option value="Guantes de Nitrilo/Químicos">Guantes de Trabajo</option><option value="Máscara de Gas Cloro">Máscara de Gas Cloro</option></select></div>
                    <div class="modal-campo"><label>Entrega</label><select name="entrega" required><option value="1">Entregado</option><option value="0">Pendiente</option></select></div>
                </div>
                <div class="modal-campo">
                    <label>Condición del Equipo</label>
                    <select name="estado" required>
                        <option value="1">Nuevo / Operativo</option>
                        <option value="0">Usado / Requiere Recambio</option>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Observaciones</label>
                    <textarea name="observacion" placeholder="Notas sobre la entrega o estado..." rows="2"></textarea>
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
<script src="assets/js/registro/gt_ca_019.js"></script>
</body>
</html>
