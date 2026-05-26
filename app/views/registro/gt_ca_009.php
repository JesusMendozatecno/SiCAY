<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'caudal_entrada', 'caudal_salida'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $entrada = $_POST['caudal_entrada'];
        $salida = $_POST['caudal_salida'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        if (!validar_numeric($entrada, 0) || !validar_numeric($salida, 0)) { $mensaje = "error"; }
        else {
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, 'Medición de Caudales Operativos')");
            $stmt->bind_param("iis", $id_inst, $id_user, $fecha);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 15, ?, 'Cruda')");
                $stmt1->bind_param("isd", $id_reg, $hora, $entrada);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 16, ?, 'Salida')");
                $stmt2->bind_param("isd", $id_reg, $hora, $salida);
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
    <title>GT-CA-009 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_009.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-chart-line"></i> Registro </h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 15px;">Medición de Caudales (Lps)</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Caudales registrados con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Sistema:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>
            <label>Caudal de Entrada (Lps):</label>
            <input type="number" step="0.1" name="caudal_entrada" required placeholder="0.0">
            <label>Caudal de Salida (Lps):</label>
            <input type="number" step="0.1" name="caudal_salida" required placeholder="0.0">
            <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Medición</button>
            <a href="index.php?route=registros" class="back">← Volver</a>
        </form>
    </div>
    <script src="assets/js/registro/gt_ca_009.js"></script>
</body>
</html>