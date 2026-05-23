<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'quimico_aplicado', 'carrera_bomba', 'frecuencia', 'punto_aplicacion'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $quimico = $_POST['quimico_aplicado'];
        $carrera = $_POST['carrera_bomba'];
        $frecuencia = $_POST['frecuencia'];
        $punto = trim($_POST['punto_aplicacion']);
        $observacion = trim($_POST['observacion'] ?? '');
        
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $quimicos_validos = ['Cloro Gas', 'Sulfato de Aluminio', 'Policloruro de Aluminio (PAC)'];
        if (!in_array($quimico, $quimicos_validos)) { $mensaje = "error"; }
        elseif (!validar_numeric($carrera, 0) || !validar_numeric($frecuencia, 0)) { $mensaje = "error"; }
        else {
            $detalles = "Aplicación de $quimico en: $punto | Obs: $observacion";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $detalles);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 45, ?, 'Pre-cloracion')");
                $stmt1->bind_param("isd", $id_reg, $hora, $carrera);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 46, ?, 'Pre-cloracion')");
                $stmt2->bind_param("isd", $id_reg, $hora, $frecuencia);
                $stmt3 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 47, ?, 'Pre-cloracion')");
                $stmt3->bind_param("iss", $id_reg, $hora, $punto);
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
    <title>Configuración Química - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/formatos_aplicacion.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-vials"></i> Aplicación Química</h2>
        <p class="subtitle">Dosificación y Configuración de Bombas</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Configuración guardada exitosamente!
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Unidad de Tratamiento:</label>
            <select name="planta" required>
                <option value="">-- Seleccione Planta --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre']; ?></option>
                <?php } ?>
            </select>

            <label>Químico Aplicado:</label>
            <select name="quimico_aplicado">
                <option value="Cloro Gas">Cloro Gas</option>
                <option value="Sulfato de Aluminio">Sulfato de Aluminio</option>
                <option value="Policloruro de Aluminio (PAC)">Policloruro de Aluminio (PAC)</option>
            </select>

            <div class="grid">
                <div>
                    <label>Carrera Bomba (%):</label>
                    <input type="number" step="0.1" name="carrera_bomba" required placeholder="0.0">
                </div>
                <div>
                    <label>Frecuencia (Hz):</label>
                    <input type="number" step="0.1" name="frecuencia" required placeholder="0.0">
                </div>
            </div>

            <label>Punto de Aplicación:</label>
            <input type="text" name="punto_aplicacion" required placeholder="Ej: Canal de mezcla rápida">

            <label>Notas de Configuración:</label>
            <textarea name="observacion" placeholder="Observaciones sobre la dosis o ajustes..."></textarea>

            <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Ajustes</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>

    <script src="assets/js/registro/formatos_aplicacion.js"></script>
</body>
</html>