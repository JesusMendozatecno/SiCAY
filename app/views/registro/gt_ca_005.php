<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'nro_serie', 'peso', 'presion', 'estado'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $nro_serie = trim($_POST['nro_serie']);
        $peso = $_POST['peso'];
        $presion = $_POST['presion'];
        $estado = $_POST['estado'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $estados_validos = ['Lleno', 'En Uso', 'Vacio'];
        if (!in_array($estado, $estados_validos)) { $mensaje = "error"; }
        elseif (!validar_numeric($peso, 0) || !validar_numeric($presion, 0)) { $mensaje = "error"; }
        else {
            $obs = "Cilindro Serie: $nro_serie - Estado: $estado";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 6, ?, 'Salida')");
                $stmt1->bind_param("isd", $id_reg, $hora, $peso);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 7, ?, 'Salida')");
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
    <title>GT-CA-005 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_005.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-gas-pump"></i> Registro </h2>
        <p style="text-align: center; font-size: 0.8rem; margin-bottom: 20px;">Control de Cilindros de Gas Cloro</p>
        
        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Cilindro registrado con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Ubicación:</label>
            <select name="planta" required>
                <option value="">-- Seleccione Planta --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>

            <label>Nro. de Serie:</label>
            <input type="text" name="nro_serie" required placeholder="Ej: CL-5040">

            <label>Estado del Cilindro:</label>
            <select name="estado" required>
                <option value="Lleno">Lleno (Reserva)</option>
                <option value="En Uso">En Uso (Conectado)</option>
                <option value="Vacio">Vacío (Para retirar)</option>
            </select>

            <label>Peso Actual (Kg):</label>
            <input type="number" step="0.1" name="peso" required placeholder="0.0">

            <label>Presión (PSI):</label>
            <input type="number" step="1" name="presion" required placeholder="0">

            <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Control</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>
    <script src="assets/js/registro/gt_ca_005.js"></script>
</body>
</html>