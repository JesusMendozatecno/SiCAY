<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'combustible', 'bateria', 'temp'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $combustible = $_POST['combustible'];
        $bateria = $_POST['bateria'];
        $temp = $_POST['temp'];
        $novedad = trim($_POST['novedad'] ?? '');
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        if (!validar_numeric($combustible, 0) || !validar_numeric($bateria, 0) || !validar_numeric($temp, 0)) { $mensaje = "error"; }
        else {
            $obs = "Chequeo Generador: " . $novedad;
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 22, ?, 'Salida')");
                $stmt1->bind_param("isd", $id_reg, $hora, $combustible);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 23, ?, 'Salida')");
                $stmt2->bind_param("isd", $id_reg, $hora, $bateria);
                $stmt3 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 24, ?, 'Salida')");
                $stmt3->bind_param("isd", $id_reg, $hora, $temp);
                if ($stmt1->execute() && $stmt2->execute() && $stmt3->execute()) {
                    $mensaje = "exito";
                }
                $stmt1->close();
                $stmt2->close();
                $stmt3->close();
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
    <title>GT-CA-012 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_012.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-plug"></i> Registro </h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 15px;">Control de Grupo Electrógeno</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Reporte guardado con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Ubicación:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>
            <label>Combustible (%):</label>
            <input type="number" name="combustible" required placeholder="Ej: 80">
            <label>Voltaje Batería (Vdc):</label>
            <input type="number" step="0.1" name="bateria" required placeholder="Ej: 13.5">
            <label>Temperatura Motor (°C):</label>
            <input type="number" step="0.1" name="temp" required placeholder="Ej: 75">
            <label>Observaciones:</label>
            <input type="text" name="novedad" placeholder="Opcional...">
            
            <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Reporte</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>
    <script src="assets/js/registro/gt_ca_012.js"></script>
</body>
</html>