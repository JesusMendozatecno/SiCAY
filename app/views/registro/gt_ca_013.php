<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'equipo', 'val4', 'val7', 'valTurb', 'estado'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $equipo = $_POST['equipo'];
        $val4 = $_POST['val4'];
        $val7 = $_POST['val7'];
        $valTurb = $_POST['valTurb'];
        $estado = $_POST['estado'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $equipos_validos = ['pH-metro Portátil', 'Turbidímetro de Mesa', 'Colorímetro'];
        $estados_validos = ['Calibrado', 'Descalibrado', 'Falla'];
        if (!in_array($equipo, $equipos_validos)) { $mensaje = "error"; }
        elseif (!in_array($estado, $estados_validos)) { $mensaje = "error"; }
        elseif (!validar_numeric($val4) || !validar_numeric($val7) || !validar_numeric($valTurb)) { $mensaje = "error"; }
        else {
            $obs = "Calibración Equipo: $equipo - Estado: $estado";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 25, ?, 'Salida')");
                $stmt1->bind_param("isd", $id_reg, $hora, $val4);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 26, ?, 'Salida')");
                $stmt2->bind_param("isd", $id_reg, $hora, $val7);
                $stmt3 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 27, ?, 'Salida')");
                $stmt3->bind_param("isd", $id_reg, $hora, $valTurb);
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
    <title>GT-CA-013 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_013.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-sliders-h"></i> Calibraci&oacute;n </h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 15px;">Calibración de Equipos de Laboratorio</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Calibración registrada con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Laboratorio / Ubicación:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>
            
            <label>Equipo a Calibrar:</label>
            <select name="equipo" required>
                <option value="pH-metro Portátil">pH-metro Portátil</option>
                <option value="Turbidímetro de Mesa">Turbidímetro de Mesa</option>
                <option value="Colorímetro">Colorímetro</option>
            </select>

            <div class="grid">
                <div><label>Buffer pH 4.0:</label><input type="number" step="0.01" name="val4" required placeholder="0.00"></div>
                <div><label>Buffer pH 7.0:</label><input type="number" step="0.01" name="val7" required placeholder="0.00"></div>
            </div>

            <label>Estándar Turbiedad (10 UNT):</label>
            <input type="number" step="0.01" name="valTurb" required placeholder="Lectura del equipo">

            <label>Estado Final:</label>
            <select name="estado" required>
                <option value="Calibrado">✅ Calibrado y Operativo</option>
                <option value="Descalibrado">⚠️ Requiere Ajuste</option>
                <option value="Falla">❌ Fuera de Servicio</option>
            </select>

            <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Calibración</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>

    <script src="assets/js/registro/gt_ca_013.js"></script>
</body>
</html>