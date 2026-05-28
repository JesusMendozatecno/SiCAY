<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'nombre_visitante', 'cedula', 'motivo', 'quien_autoriza', 'estado_acceso'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $visitante = trim($_POST['nombre_visitante']); $cedula = trim($_POST['cedula']);
        $motivo = trim($_POST['motivo']); $autoriza = trim($_POST['quien_autoriza']);
        $estado_acceso = intval($_POST['estado_acceso']);
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d'); $hora_ingreso = date('H:i:s');
        if (!in_array($estado_acceso, [0, 1])) { $mensaje = "error"; }
        else {
            $detalles = "Visitante: $visitante (V-$cedula) | Motivo: $motivo | Autoriza: $autoriza";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $detalles);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id; $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 29, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora_ingreso, $estado_acceso);
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
           m1.valor_medido as estado_acceso
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    LEFT JOIN medicion_horaria m1 ON m1.id_registro_diario = rd.id AND m1.id_parametro = 29
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-015 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_015.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-id-card"></i> Control de Acceso <span class="badge-gt">GT-CA-015</span></h2>
        <p class="subtitle">Registro de Ingreso de Visitantes a Instalaciones</p>
    </div>
    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Acceso registrado exitosamente!</div>
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
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nuevo Ingreso</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Planta</th><th>Visitante</th><th>Motivo</th><th>Autoriza</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <?php
                        $obs = $r['observaciones_generales'];
                        $visitante = $motivo = $autoriza = $obs;
                        if (preg_match('/^Visitante: (.+?) \| Motivo: (.+?) \| Autoriza: (.+)$/', $obs, $m)) {
                            $visitante = $m[1]; $motivo = $m[2]; $autoriza = $m[3];
                        }
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td><?php echo hsc($visitante); ?></td>
                        <td><?php echo hsc($motivo); ?></td>
                        <td><?php echo hsc($autoriza); ?></td>
                        <td style="color:<?php echo $r['estado_acceso']?'#2ecc71':'#f1c40f'; ?>"><?php echo $r['estado_acceso']?'✅ Normal':'⚠️ Incidencia'; ?></td>
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
                <h3><i class="fas fa-id-card"></i> <span>Registrar Ingreso</span></h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-campo">
                    <label>Planta / Instalación</label>
                    <select name="planta" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($plantas_data as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo hsc($p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Visitante</label><input type="text" name="nombre_visitante" placeholder="Nombre" required></div>
                    <div class="modal-campo"><label>Cédula</label><input type="text" name="cedula" placeholder="Nro. Cédula" required></div>
                </div>
                <div class="modal-campo">
                    <label>Motivo de la Visita</label>
                    <input type="text" name="motivo" placeholder="Ej: Mantenimiento Preventivo" required>
                </div>
                <div class="modal-campo">
                    <label>¿Quién Autoriza?</label>
                    <input type="text" name="quien_autoriza" placeholder="Nombre de autoridad" required>
                </div>
                <div class="modal-campo">
                    <label>Estado de Ingreso</label>
                    <select name="estado_acceso">
                        <option value="1">Ingreso Normal</option>
                        <option value="0">Incidencia en Puerta</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar">Registrar Ingreso</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/registro/gt_ca_table.js"></script>
<script src="assets/js/registro/gt_ca_015.js"></script>
</body>
</html>
