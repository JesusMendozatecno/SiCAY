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
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $destinos_validos = ['Laguna de Secado 1', 'Laguna de Secado 2', 'Lechos de Secado', 'Retiro Externo'];
        if (!in_array($destino, $destinos_validos)) { $mensaje = "error"; }
        elseif (!validar_numeric($volumen, 0)) { $mensaje = "error"; }
        else {
            $obs = "Disposición de Lodos - Destino: " . $destino;
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 17, ?, 'Sedimentada')");
                $stmt1->bind_param("isd", $id_reg, $hora, $volumen);
                if ($stmt1->execute()) {
                    $mensaje = "exito";
                }
                $stmt1->close();
            } else {
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-010 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_010.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-trash-alt"></i> Registro </h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 15px;">Gestión y Disposición de Lodos</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Registro de lodos guardado!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Unidad:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>
            <label>Volumen Extraído (m³):</label>
            <input type="number" step="0.1" name="volumen_lodo" required placeholder="0.0">
            <label>Destino Final:</label>
            <select name="destino" required>
                <option value="Laguna de Secado 1">Laguna de Secado 1</option>
                <option value="Laguna de Secado 2">Laguna de Secado 2</option>
                <option value="Lechos de Secado">Lechos de Secado</option>
                <option value="Retiro Externo">Retiro Externo (Camión)</option>
            </select>
            <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Registro</button>
            <a href="index.php?route=registros" class="back">← Volver</a>
        </form>
    </div>
    <script src="assets/js/registro/gt_ca_010.js"></script>
</body>
</html>