<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre, ubicacion FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'turbiedad', 'color', 'etapa'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $turbiedad = $_POST['turbiedad'];
        $color = $_POST['color'];
        $etapa = $_POST['etapa'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $etapas_validas = ['Cruda', 'Sedimentada', 'Filtrada', 'Salida'];
        if (!in_array($etapa, $etapas_validas)) { $mensaje = "error"; }
        elseif (!validar_numeric($turbiedad, 0) || !validar_numeric($color, 0)) { $mensaje = "error"; }
        else {
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $id_inst, $id_user, $fecha);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 4, ?, ?)");
                $stmt1->bind_param("isds", $id_reg, $hora, $turbiedad, $etapa);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 5, ?, ?)");
                $stmt2->bind_param("isds", $id_reg, $hora, $color, $etapa);
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
    <title>GT-CA-003 - Análisis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_003.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-microscope"></i> Análisis </h2>
        
        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Registro de Análisis Guardado!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Fuente:</label>
            <select name="planta" required>
                <option value="">-- Seleccione Planta --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre']; ?></option>
                <?php } ?>
            </select>

            <label>Etapa del Proceso:</label>
            <select name="etapa" required>
                <option value="Cruda">Agua Cruda (Entrada)</option>
                <option value="Sedimentada">Agua Sedimentada</option>
                <option value="Filtrada">Agua Filtrada</option>
                <option value="Salida">Agua de Salida (Tanque)</option>
            </select>

            <label>Turbiedad (UNT):</label>
            <input type="number" step="0.01" name="turbiedad" required placeholder="Norma: < 5.0">

            <label>Color (UC):</label>
            <input type="number" step="0.01" name="color" required placeholder="Norma: < 15.0">

            <button type="submit" class="btn"><i class="fas fa-flask"></i> Guardar Análisis</button>
            <a href="index.php?route=registros" class="back">← Volver</a>
        </form>
    </div>
    <script src="assets/js/registro/gt_ca_003.js"></script>
</body>
</html>