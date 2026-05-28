<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'equipo', 'val4', 'val7', 'valTurb', 'estado'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']); $equipo = $_POST['equipo'];
        $val4 = $_POST['val4']; $val7 = $_POST['val7']; $valTurb = $_POST['valTurb'];
        $estado = $_POST['estado'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d'); $hora = date('H:i:s');
        $equipos_validos = ['pH-metro Portátil', 'Turbidímetro de Mesa', 'Colorímetro'];
        $estados_validos = ['Calibrado', 'Descalibrado', 'Falla'];
        if (!in_array($equipo, $equipos_validos) || !in_array($estado, $estados_validos)) { $mensaje = "error"; }
        elseif (!validar_numeric($val4) || !validar_numeric($val7) || !validar_numeric($valTurb)) { $mensaje = "error"; }
        else {
            $obs = "Calibración Equipo: $equipo - Estado: $estado";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id; $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 25, ?, 'Salida')");
                $stmt1->bind_param("isd", $id_reg, $hora, $val4);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 26, ?, 'Salida')");
                $stmt2->bind_param("isd", $id_reg, $hora, $val7);
                $stmt3 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 27, ?, 'Salida')");
                $stmt3->bind_param("isd", $id_reg, $hora, $valTurb);
                if ($stmt1->execute() && $stmt2->execute() && $stmt3->execute()) { $mensaje = "exito"; }
                $stmt1->close(); $stmt2->close(); $stmt3->close();
            } else { $stmt->close(); }
        }
    }
}
$plantas_data = [];
mysqli_data_seek($plantas_query, 0);
while ($p = mysqli_fetch_assoc($plantas_query)) { $plantas_data[] = $p; }
$registros = mysqli_query($con, "
    SELECT rd.id, rd.fecha, i.nombre as planta, rd.observaciones_generales,
           m1.valor_medido as val4, m2.valor_medido as val7, m3.valor_medido as valTurb
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    LEFT JOIN medicion_horaria m1 ON m1.id_registro_diario = rd.id AND m1.id_parametro = 25
    LEFT JOIN medicion_horaria m2 ON m2.id_registro_diario = rd.id AND m2.id_parametro = 26
    LEFT JOIN medicion_horaria m3 ON m3.id_registro_diario = rd.id AND m3.id_parametro = 27
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-013 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_013.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-sliders-h"></i> Calibración de Equipos <span class="badge-gt">GT-CA-013</span></h2>
        <p class="subtitle">Calibración de Equipos de Laboratorio — pH-metro, Turbidímetro, Colorímetro</p>
    </div>
    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Calibración registrada con éxito!</div>
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
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nueva Calibración</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Planta</th><th>Equipo / Estado</th><th>Buffer pH 4</th><th>Buffer pH 7</th><th>Turbiedad (UNT)</th></tr></thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td style="font-size:13px;"><?php echo hsc($r['observaciones_generales']); ?></td>
                        <td><?php echo number_format($r['val4'], 2); ?></td>
                        <td><?php echo number_format($r['val7'], 2); ?></td>
                        <td><?php echo number_format($r['valTurb'], 2); ?></td>
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
                <h3><i class="fas fa-sliders-h"></i> <span>Nueva Calibración</span></h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-campo">
                    <label>Laboratorio / Ubicación</label>
                    <select name="planta" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($plantas_data as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo hsc($p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Equipo a Calibrar</label>
                    <select name="equipo" required>
                        <option value="pH-metro Portátil">pH-metro Portátil</option>
                        <option value="Turbidímetro de Mesa">Turbidímetro de Mesa</option>
                        <option value="Colorímetro">Colorímetro</option>
                    </select>
                </div>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Buffer pH 4.0</label><input type="number" step="0.01" name="val4" required placeholder="0.00"></div>
                    <div class="modal-campo"><label>Buffer pH 7.0</label><input type="number" step="0.01" name="val7" required placeholder="0.00"></div>
                    <div class="modal-campo full-w"><label>Estándar Turbiedad (10 UNT)</label><input type="number" step="0.01" name="valTurb" required placeholder="Lectura del equipo"></div>
                </div>
                <div class="modal-campo">
                    <label>Estado Final</label>
                    <select name="estado" required>
                        <option value="Calibrado">Calibrado y Operativo</option>
                        <option value="Descalibrado">Requiere Ajuste</option>
                        <option value="Falla">Fuera de Servicio</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar">Guardar Calibración</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/registro/gt_ca_table.js"></script>
<script src="assets/js/registro/gt_ca_013.js"></script>
</body>
</html>
