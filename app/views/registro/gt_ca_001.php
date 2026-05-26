<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre, ubicacion, capacidad_diseno FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'cloro', 'ph'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $cloro = $_POST['cloro'];
        $ph = $_POST['ph'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        if (!validar_numeric($cloro) || !validar_numeric($ph)) { $mensaje = "error"; }
        else {
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $id_inst, $id_user, $fecha);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 1, ?, 'Salida')");
                $stmt1->bind_param("isd", $id_reg, $hora, $cloro);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 3, ?, 'Salida')");
                $stmt2->bind_param("isd", $id_reg, $hora, $ph);
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
    <title>GT-CA-001 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_001.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-tint"></i> Registro </h2>
        <p style="text-align: center; font-size: 0.8rem; margin-bottom: 20px;">Fuentes y Plantas de Tratamiento</p>
        
        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Registro guardado con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Ubicación y Capacidad:</label>
            <select name="planta" required>
                <option value="">-- Seleccione Planta --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre']; ?> (<?php echo $p['capacidad_diseno']; ?> L/s)</option>
                <?php } ?>
            </select>

            <label>Cloro Residual (mg/L):</label>
            <input type="number" step="0.01" name="cloro" required placeholder="Ej: 1.2">

            <label>pH (Acidez):</label>
            <input type="number" step="0.01" name="ph" required placeholder="Ej: 7.0">

            <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Registro</button>
            <a href="index.php?route=registros" class="back">← Volver</a>
        </form>
    </div>
    <script src="assets/js/registro/gt_ca_001.js"></script>
</body>
</html>