<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'ubicacion_exacta', 'id_extintor', 'presion', 'precinto', 'vencimiento'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $ubicacion = trim($_POST['ubicacion_exacta']); $id_extintor = trim($_POST['id_extintor']);
        $presion = intval($_POST['presion']); $precinto = intval($_POST['precinto']);
        $vencimiento = trim($_POST['vencimiento']);
        $id_user = (int) $_SESSION['id_usuario'];
        $fecha = date('Y-m-d'); $hora = date('H:i:s');
        if (!in_array($presion, [0, 1]) || !in_array($precinto, [0, 1])) { $mensaje = "error"; }
        else {
            $obs = "Extintor ID: $id_extintor | Ubicación: $ubicacion | Vence: $vencimiento";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id; $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 34, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora, $presion);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 35, ?, 'Salida')");
                $stmt2->bind_param("isi", $id_reg, $hora, $precinto);
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
           m1.valor_medido as presion, m2.valor_medido as precinto
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    JOIN medicion_horaria m1 ON m1.id_registro_diario = rd.id AND m1.id_parametro = 34
    JOIN medicion_horaria m2 ON m2.id_registro_diario = rd.id AND m2.id_parametro = 35
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-018 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_018.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-fire-extinguisher"></i> Extintores <span class="badge-gt">GT-CA-018</span></h2>
        <p class="subtitle">Inspección Técnica de Extintores — Presión, Precinto y Vencimiento</p>
    </div>
    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Inspección guardada con éxito!</div>
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
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nueva Inspección</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Planta</th><th>Extintor / Ubicación</th><th>Presión</th><th>Precinto</th><th>Vence</th></tr></thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <?php
                        $obs = $r['observaciones_generales'];
                        $id_ext = $ubicacion = $vence = $obs;
                        if (preg_match('/^Extintor ID: (.+?) \| Ubicación: (.+?) \| Vence: (.+)$/', $obs, $m)) {
                            $id_ext = $m[1]; $ubicacion = $m[2]; $vence = $m[3];
                        }
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td><strong><?php echo hsc($id_ext); ?></strong><br><small><?php echo hsc($ubicacion); ?></small></td>
                        <td style="color:<?php echo $r['presion']?'#2ecc71':'#e74c3c'; ?>"><?php echo $r['presion']?'🟢 OK':'🔴 Recarga'; ?></td>
                        <td style="color:<?php echo $r['precinto']?'#2ecc71':'#e74c3c'; ?>"><?php echo $r['precinto']?'✅ Bueno':'⚠️ Dañado'; ?></td>
                        <td><?php echo hsc($vence); ?></td>
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
                <h3><i class="fas fa-fire-extinguisher"></i> <span>Nueva Inspección de Extintor</span></h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-grid">
                    <div class="modal-campo"><label>Planta</label><select name="planta" required><option value="">--</option><?php foreach ($plantas_data as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo hsc($p['nombre']); ?></option><?php endforeach; ?></select></div>
                    <div class="modal-campo"><label>Ubicación Exacta</label><input type="text" name="ubicacion_exacta" placeholder="Ej: Tableros" required></div>
                </div>
                <div class="modal-grid">
                    <div class="modal-campo"><label>ID Extintor</label><input type="text" name="id_extintor" placeholder="Código" required></div>
                    <div class="modal-campo"><label>Vencimiento</label><input type="date" name="vencimiento" required></div>
                </div>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Presión</label><select name="presion" required><option value="1">Zona Verde (OK)</option><option value="0">Requiere Recarga</option></select></div>
                    <div class="modal-campo"><label>Precinto y Manguera</label><select name="precinto" required><option value="1">Buen Estado</option><option value="0">Roto o Dañado</option></select></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar">Guardar Inspección</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/registro/gt_ca_table.js"></script>
<script src="assets/js/registro/gt_ca_018.js"></script>
</body>
</html>
