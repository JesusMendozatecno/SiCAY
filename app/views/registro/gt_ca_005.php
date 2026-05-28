<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'nro_serie', 'peso', 'presion', 'estado'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $nro_serie = trim($_POST['nro_serie']);
        $peso = $_POST['peso'];
        $presion = $_POST['presion'];
        $estado = $_POST['estado'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $estados_validos = ['Lleno', 'En Uso', 'Vacio'];
        if (!in_array($estado, $estados_validos)) { $mensaje = "error"; }
        elseif (!validar_numeric($peso, 0) || !validar_numeric($presion, 0)) { $mensaje = "error"; }
        else {
            $obs = "Cilindro Serie: $nro_serie - Estado: $estado";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 6, ?, 'Salida')");
                $stmt1->bind_param("isd", $id_reg, $hora, $peso);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 7, ?, 'Salida')");
                $stmt2->bind_param("isd", $id_reg, $hora, $presion);
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

$registros = mysqli_query($con, "
    SELECT rd.id, rd.fecha, i.nombre as planta, rd.observaciones_generales,
           m1.valor_medido as peso, m2.valor_medido as presion
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    LEFT JOIN medicion_horaria m1 ON m1.id_registro_diario = rd.id AND m1.id_parametro = 6
    LEFT JOIN medicion_horaria m2 ON m2.id_registro_diario = rd.id AND m2.id_parametro = 7
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-005 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_005.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-gas-pump"></i> Cilindros de Cloro <span class="badge-gt">GT-CA-005</span></h2>
        <p class="subtitle">Control de Cilindros de Gas Cloro — Peso, Presión y Estado</p>
    </div>

    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Cilindro registrado con éxito!</div>
    <?php elseif ($mensaje == "error"): ?>
        <div class="alerta-error"><i class="fas fa-exclamation-triangle"></i> Error al guardar. Verifique los datos.</div>
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
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nuevo Registro</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Planta</th>
                        <th>Serie / Estado</th>
                        <th>Peso (Kg)</th>
                        <th>Presión (PSI)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td style="font-size:13px;"><?php echo hsc($r['observaciones_generales']); ?></td>
                        <td><?php echo number_format($r['peso'], 1); ?></td>
                        <td><?php echo number_format($r['presion'], 0); ?></td>
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
                <h3><i class="fas fa-gas-pump"></i> <span>Nuevo Control de Cilindro</span></h3>
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
                <div class="modal-grid">
                    <div class="modal-campo">
                        <label>Nro. de Serie</label>
                        <input type="text" name="nro_serie" required placeholder="Ej: CL-5040">
                    </div>
                    <div class="modal-campo">
                        <label>Estado</label>
                        <select name="estado" required>
                            <option value="Lleno">Lleno (Reserva)</option>
                            <option value="En Uso">En Uso (Conectado)</option>
                            <option value="Vacio">Vacío (Para retirar)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-grid">
                    <div class="modal-campo">
                        <label>Peso Actual (Kg)</label>
                        <input type="number" step="0.1" name="peso" required placeholder="0.0">
                    </div>
                    <div class="modal-campo">
                        <label>Presión (PSI)</label>
                        <input type="number" step="1" name="presion" required placeholder="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar">Guardar Control</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/registro/gt_ca_table.js"></script>
<script src="assets/js/registro/gt_ca_005.js"></script>
</body>
</html>
