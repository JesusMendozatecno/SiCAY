<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'luz', 'limpieza', 'cerca', 'fugas'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $luz = intval($_POST['luz']); $limpieza = intval($_POST['limpieza']);
        $cerca = intval($_POST['cerca']); $fugas = intval($_POST['fugas']);
        $detalles = trim($_POST['detalles'] ?? '');
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d'); $hora = date('H:i:s');
        if (!in_array($luz, [0,1]) || !in_array($limpieza, [0,1]) || !in_array($cerca, [0,1]) || !in_array($fugas, [0,1])) { $mensaje = "error"; }
        else {
            $obs = "Inspección Física: $detalles";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id; $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 18, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora, $luz);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 19, ?, 'Salida')");
                $stmt2->bind_param("isi", $id_reg, $hora, $limpieza);
                $stmt3 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 20, ?, 'Salida')");
                $stmt3->bind_param("isi", $id_reg, $hora, $cerca);
                $stmt4 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 21, ?, 'Salida')");
                $stmt4->bind_param("isi", $id_reg, $hora, $fugas);
                if ($stmt1->execute() && $stmt2->execute() && $stmt3->execute() && $stmt4->execute()) { $mensaje = "exito"; }
                $stmt1->close(); $stmt2->close(); $stmt3->close(); $stmt4->close();
            } else { $stmt->close(); }
        }
    }
}
$plantas_data = [];
mysqli_data_seek($plantas_query, 0);
while ($p = mysqli_fetch_assoc($plantas_query)) { $plantas_data[] = $p; }
$registros = mysqli_query($con, "
    SELECT rd.id, rd.fecha, i.nombre as planta, rd.observaciones_generales,
           m1.valor_medido as iluminacion, m2.valor_medido as limpieza,
           m3.valor_medido as cerca, m4.valor_medido as fugas
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    LEFT JOIN medicion_horaria m1 ON m1.id_registro_diario = rd.id AND m1.id_parametro = 18
    LEFT JOIN medicion_horaria m2 ON m2.id_registro_diario = rd.id AND m2.id_parametro = 19
    LEFT JOIN medicion_horaria m3 ON m3.id_registro_diario = rd.id AND m3.id_parametro = 20
    LEFT JOIN medicion_horaria m4 ON m4.id_registro_diario = rd.id AND m4.id_parametro = 21
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-011 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_011.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-eye"></i> Inspección de Planta <span class="badge-gt">GT-CA-011</span></h2>
        <p class="subtitle">Inspección Física de Instalaciones — Iluminación, Limpieza, Seguridad y Fugas</p>
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
                <thead><tr><th>Fecha</th><th>Planta</th><th>Iluminación</th><th>Limpieza</th><th>Cerca</th><th>Fugas</th><th>Detalles</th></tr></thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td style="color:<?php echo $r['iluminacion']?'#2ecc71':'#e74c3c'; ?>"><?php echo $r['iluminacion']?'✅':'❌'; ?></td>
                        <td style="color:<?php echo $r['limpieza']?'#2ecc71':'#e74c3c'; ?>"><?php echo $r['limpieza']?'✅':'❌'; ?></td>
                        <td style="color:<?php echo $r['cerca']?'#2ecc71':'#e74c3c'; ?>"><?php echo $r['cerca']?'✅':'❌'; ?></td>
                        <td style="color:<?php echo $r['fugas']?'#e74c3c':'#2ecc71'; ?>"><?php echo $r['fugas']?'⚠️':'✅'; ?></td>
                        <td style="font-size:13px;"><?php echo hsc(str_replace('Inspección Física: ', '', $r['observaciones_generales'])); ?></td>
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
                <h3><i class="fas fa-eye"></i> <span>Nueva Inspección de Planta</span></h3>
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
                <div class="modal-grid">
                    <div class="modal-campo"><label>Iluminación</label><select name="luz"><option value="1">Óptima</option><option value="0">Deficiente</option></select></div>
                    <div class="modal-campo"><label>Limpieza</label><select name="limpieza"><option value="1">Limpio</option><option value="0">Monte alto</option></select></div>
                    <div class="modal-campo"><label>Cerca / Seguridad</label><select name="cerca"><option value="1">Seguro</option><option value="0">Dañado</option></select></div>
                    <div class="modal-campo"><label>Fugas de Agua</label><select name="fugas"><option value="0">Ninguna</option><option value="1">Detectada</option></select></div>
                </div>
                <div class="modal-campo">
                    <label>Detalles y Novedades</label>
                    <textarea name="detalles" placeholder="Escriba hallazgos relevantes..." rows="3"></textarea>
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
<script src="assets/js/registro/gt_ca_011.js"></script>
</body>
</html>
