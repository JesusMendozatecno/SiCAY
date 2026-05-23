<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'alcalinidad', 'dureza', 'hierro', 'coliformes', 'analista'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $alcalinidad = $_POST['alcalinidad'];
        $dureza = $_POST['dureza'];
        $hierro = $_POST['hierro'];
        $coliformes = $_POST['coliformes'];
        $analista = trim($_POST['analista']);
        
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        
        if (!validar_numeric($alcalinidad, 0) || !validar_numeric($dureza, 0) || !validar_numeric($hierro, 0) || !validar_numeric($coliformes, 0)) { $mensaje = "error"; }
        else {
            $obs = "ANÁLISIS DE LABORATORIO - Analista: $analista";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 41, ?, 'Salida')");
                $stmt1->bind_param("isd", $id_reg, $hora, $alcalinidad);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 42, ?, 'Salida')");
                $stmt2->bind_param("isd", $id_reg, $hora, $dureza);
                $stmt3 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 43, ?, 'Salida')");
                $stmt3->bind_param("isd", $id_reg, $hora, $coliformes);
                $stmt4 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 44, ?, 'Salida')");
                $stmt4->bind_param("isd", $id_reg, $hora, $hierro);
                if ($stmt1->execute() && $stmt2->execute() && $stmt3->execute() && $stmt4->execute()) {
                    $mensaje = "exito";
                }
                $stmt1->close();
                $stmt2->close();
                $stmt3->close();
                $stmt4->close();
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
    <title>Laboratorio Central - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/resultados_laboratorio.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-microscope"></i> Reporte de Laboratorio</h2>
        <div class="alert-lab">Certificación de Calidad del Agua - Aguas de Yaracuy</div>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Análisis guardado con éxito!
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta Evaluada:</label>
            <select name="planta" required>
                <option value="">-- Seleccione Planta --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre']; ?></option>
                <?php } ?>
            </select>

            <div class="grid">
                <div>
                    <label>Alcalinidad (mg/L):</label>
                    <input type="number" step="0.01" name="alcalinidad" required placeholder="0.00">
                </div>
                <div>
                    <label>Dureza Total (mg/L):</label>
                    <input type="number" step="0.01" name="dureza" required placeholder="0.00">
                </div>
            </div>

            <div class="grid">
                <div>
                    <label>Hierro Total (mg/L):</label>
                    <input type="number" step="0.001" name="hierro" required placeholder="0.000">
                </div>
                <div>
                    <label>Coliformes (NMP):</label>
                    <input type="number" name="coliformes" required placeholder="Norma: 0">
                </div>
            </div>

            <label>Nombre del Analista Químico:</label>
            <input type="text" name="analista" required placeholder="Quien certifica el análisis">

            <button type="submit" class="btn"><i class="fas fa-vial"></i> Registrar Certificación</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>

    <script src="assets/js/registro/resultados_laboratorio.js"></script>
</body>
</html>