<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'equipo', 'voltaje', 'amperaje', 'horas'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $equipo = $_POST['equipo'];
        $voltaje = $_POST['voltaje'];
        $amperaje = $_POST['amperaje'];
        $horas = $_POST['horas'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $equipos_validos = ['Bomba 1', 'Bomba 2', 'Bomba Booster'];
        if (!in_array($equipo, $equipos_validos)) { $mensaje = "error"; }
        elseif (!validar_numeric($voltaje, 0) || !validar_numeric($amperaje, 0) || !validar_numeric($horas, 0)) { $mensaje = "error"; }
        else {
            $obs = "Operación de Equipo: " . $equipo;
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 10, ?, 'Salida')");
                $stmt1->bind_param("isd", $id_reg, $hora, $voltaje);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 11, ?, 'Salida')");
                $stmt2->bind_param("isd", $id_reg, $hora, $amperaje);
                $stmt3 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 12, ?, 'Salida')");
                $stmt3->bind_param("isd", $id_reg, $hora, $horas);
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
    <title>GT-CA-007 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_007.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-bolt"></i> Registro </h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 15px;">Operación de Equipos y Bombeo</p>
        
        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Bombeo registrado con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Ubicación:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>
            <label>Equipo:</label>
            <select name="equipo" required>
                <option value="Bomba 1">Bomba 1</option>
                <option value="Bomba 2">Bomba 2</option>
                <option value="Bomba Booster">Bomba Booster</option>
            </select>
            <label>Voltaje (V):</label>
            <input type="number" step="0.1" name="voltaje" required placeholder="0.0">
            <label>Amperaje (A):</label>
            <input type="number" step="0.1" name="amperaje" required placeholder="0.0">
            <label>Horas de Marcha:</label>
            <input type="number" step="0.1" name="horas" required placeholder="0.0">
            <button type="submit" class="btn"><i class="fas fa-save"></i> Registrar Operación</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>
    <script src="assets/js/registro/gt_ca_007.js"></script>
</body>
</html>