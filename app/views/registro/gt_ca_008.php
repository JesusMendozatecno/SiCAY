<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'tanque', 'nivel', 'presion'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $tanque = trim($_POST['tanque']);
        $nivel = $_POST['nivel'];
        $presion = $_POST['presion'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        if (!validar_numeric($nivel, 0) || !validar_numeric($presion, 0)) { $mensaje = "error"; }
        else {
            $obs = "Control de Tanque: " . $tanque;
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 13, ?, 'Salida')");
                $stmt1->bind_param("isd", $id_reg, $hora, $nivel);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 14, ?, 'Salida')");
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-008 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_008.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-water"></i> Registro </h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 15px;">Control de Niveles y Presión</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Niveles registrados con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Sistema:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>
            <label>Identificación del Tanque:</label>
            <input type="text" name="tanque" required placeholder="Ej: Tanque Elevado">
            <label>Nivel de Agua (Metros):</label>
            <input type="number" step="0.01" name="nivel" required placeholder="0.00">
            <label>Presión de Salida (PSI):</label>
            <input type="number" step="0.1" name="presion" required placeholder="0.0">
            <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Reporte</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>
    <script src="assets/js/registro/gt_ca_008.js"></script>
</body>
</html>