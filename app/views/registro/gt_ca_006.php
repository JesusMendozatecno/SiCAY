<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'unidad', 'perdida', 'duracion'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $unidad = $_POST['unidad'];
        $perdida = $_POST['perdida'];
        $duracion = $_POST['duracion'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $unidades_validas = ['Filtro 1', 'Filtro 2', 'Sedimentador 1', 'Desarenador'];
        if (!in_array($unidad, $unidades_validas)) { $mensaje = "error"; }
        elseif (!validar_numeric($perdida, 0) || !validar_numeric($duracion, 0)) { $mensaje = "error"; }
        else {
            $obs = "Lavado de Unidad: " . $unidad;
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 8, ?, 'Filtrada')");
                $stmt1->bind_param("isd", $id_reg, $hora, $perdida);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 9, ?, 'Filtrada')");
                $stmt2->bind_param("isd", $id_reg, $hora, $duracion);
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
    <title>GT-CA-006 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_006.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-tools"></i> Registro </h2>
        <p style="text-align: center; font-size: 0.8rem; margin-bottom: 20px;">Lavado de Filtros y Unidades</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Lavado registrado con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Estación:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>

            <label>Unidad a Lavar:</label>
            <select name="unidad" required>
                <option value="Filtro 1">Filtro 1</option>
                <option value="Filtro 2">Filtro 2</option>
                <option value="Sedimentador 1">Sedimentador 1</option>
                <option value="Desarenador">Desarenador</option>
            </select>

            <label>Pérdida de Carga (m):</label>
            <input type="number" step="0.01" name="perdida" required placeholder="0.00">

            <label>Duración (minutos):</label>
            <input type="number" name="duracion" required placeholder="Ej: 15">

            <button type="submit" class="btn"><i class="fas fa-save"></i> Registrar Lavado</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>

    <script src="assets/js/registro/gt_ca_006.js"></script>
</body>
</html>