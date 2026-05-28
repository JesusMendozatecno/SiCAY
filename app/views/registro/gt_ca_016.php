<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'herramienta', 'cantidad', 'accion', 'responsable'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $herramienta = trim($_POST['herramienta']); $cantidad = $_POST['cantidad'];
        $estado = intval($_POST['estado'] ?? 0); $responsable = trim($_POST['responsable']);
        $accion = $_POST['accion'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d'); $hora = date('H:i:s');
        $acciones_validas = ['Préstamo / Salida', 'Devolución / Entrada'];
        if (!in_array($accion, $acciones_validas) || !in_array($estado, [0, 1]) || !validar_numeric($cantidad, 0)) { $mensaje = "error"; }
        else {
            $movimiento = "Acción: $accion | Equipo: $herramienta | Responsable: $responsable";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $movimiento);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id; $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 30, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora, $estado);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 31, ?, 'Salida')");
                $stmt2->bind_param("isd", $id_reg, $hora, $cantidad);
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
           m1.valor_medido as estado_eq, m2.valor_medido as cantidad
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    LEFT JOIN medicion_horaria m1 ON m1.id_registro_diario = rd.id AND m1.id_parametro = 30
    LEFT JOIN medicion_horaria m2 ON m2.id_registro_diario = rd.id AND m2.id_parametro = 31
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-016 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_016.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-wrench"></i> Herramientas <span class="badge-gt">GT-CA-016</span></h2>
        <p class="subtitle">Control de Préstamo y Devolución de Herramientas y Equipos</p>
    </div>
    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Movimiento registrado con éxito!</div>
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
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nuevo Movimiento</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Planta</th><th>Acción / Equipo</th><th>Cantidad</th><th>Estado</th><th>Responsable</th></tr></thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <?php
                        $obs = $r['observaciones_generales'];
                        $accion = $equipo = $responsable = $obs;
                        if (preg_match('/^Acción: (.+?) \| Equipo: (.+?) \| Responsable: (.+)$/', $obs, $m)) {
                            $accion = $m[1]; $equipo = $m[2]; $responsable = $m[3];
                        }
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td><span style="color:<?php echo strpos($accion,'Salida')!==false?'#e74c3c':'#2ecc71'; ?>"><?php echo hsc($accion); ?></span><br><small><?php echo hsc($equipo); ?></small></td>
                        <td><?php echo number_format($r['cantidad'], 0); ?></td>
                        <td style="color:<?php echo $r['estado_eq']?'#2ecc71':'#e74c3c'; ?>"><?php echo $r['estado_eq']?'✅ Operativo':'❌ Dañado'; ?></td>
                        <td><?php echo hsc($responsable); ?></td>
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
                <h3><i class="fas fa-wrench"></i> <span>Registrar Movimiento de Herramienta</span></h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-campo">
                    <label>Ubicación</label>
                    <select name="planta" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($plantas_data as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo hsc($p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Tipo de Movimiento</label>
                    <select name="accion" required>
                        <option value="Préstamo / Salida">Préstamo / Salida</option>
                        <option value="Devolución / Entrada">Devolución / Entrada</option>
                    </select>
                </div>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Herramienta</label><input type="text" name="herramienta" placeholder="Nombre del Equipo" required></div>
                    <div class="modal-campo"><label>Cantidad</label><input type="number" name="cantidad" value="1" required></div>
                </div>
                <div class="modal-campo">
                    <label>Estado del Equipo</label>
                    <select name="estado">
                        <option value="1">Operativo / Buen Estado</option>
                        <option value="0">Dañado / Mal Estado</option>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Responsable</label>
                    <input type="text" name="responsable" placeholder="Nombre del trabajador" required>
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
<script src="assets/js/registro/gt_ca_016.js"></script>
</body>
</html>
