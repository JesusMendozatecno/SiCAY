<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre, ubicacion FROM instalacion");
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'turbiedad', 'color', 'etapa'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $turbiedad = $_POST['turbiedad'];
        $color = $_POST['color'];
        $etapa = $_POST['etapa'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $etapas_validas = ['Cruda', 'Sedimentada', 'Filtrada', 'Salida'];
        if (!in_array($etapa, $etapas_validas)) { $mensaje = "error"; }
        elseif (!validar_numeric($turbiedad, 0) || !validar_numeric($color, 0)) { $mensaje = "error"; }
        else {
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $id_inst, $id_user, $fecha);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 4, ?, ?)");
                $stmt1->bind_param("isds", $id_reg, $hora, $turbiedad, $etapa);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 5, ?, ?)");
                $stmt2->bind_param("isds", $id_reg, $hora, $color, $etapa);
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
    SELECT rd.id, rd.fecha, i.nombre as planta,
           m1.valor_medido as turbiedad, m1.etapa_proceso as etapa,
           m2.valor_medido as color
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    LEFT JOIN medicion_horaria m1 ON m1.id_registro_diario = rd.id AND m1.id_parametro = 4
    LEFT JOIN medicion_horaria m2 ON m2.id_registro_diario = rd.id AND m2.id_parametro = 5
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-003 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_003.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-microscope"></i> Turbiedad y Color <span class="badge-gt">GT-CA-003</span></h2>
        <p class="subtitle">Análisis de Calidad de Agua por Etapa del Proceso</p>
    </div>

    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Registro guardado con éxito!</div>
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
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nuevo Análisis</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Planta</th>
                        <th>Etapa</th>
                        <th>Turbiedad (UNT)</th>
                        <th>Color (UC)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td><span class="badge-gt"><?php echo hsc($r['etapa']); ?></span></td>
                        <td><?php echo number_format($r['turbiedad'], 2); ?></td>
                        <td><?php echo number_format($r['color'], 2); ?></td>
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
                <h3><i class="fas fa-microscope"></i> <span>Nuevo Análisis - Turbiedad y Color</span></h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-campo">
                    <label>Planta / Fuente</label>
                    <select name="planta" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($plantas_data as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo hsc($p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Etapa del Proceso</label>
                    <select name="etapa" required>
                        <option value="Cruda">Agua Cruda (Entrada)</option>
                        <option value="Sedimentada">Agua Sedimentada</option>
                        <option value="Filtrada">Agua Filtrada</option>
                        <option value="Salida">Agua de Salida (Tanque)</option>
                    </select>
                </div>
                <div class="modal-grid">
                    <div class="modal-campo">
                        <label>Turbiedad (UNT)</label>
                        <input type="number" step="0.01" name="turbiedad" required placeholder="Ej: 2.50">
                    </div>
                    <div class="modal-campo">
                        <label>Color (UC)</label>
                        <input type="number" step="0.01" name="color" required placeholder="Ej: 10.00">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar">Guardar Análisis</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/registro/gt_ca_table.js"></script>
<script src="assets/js/registro/gt_ca_003.js"></script>
</body>
</html>
