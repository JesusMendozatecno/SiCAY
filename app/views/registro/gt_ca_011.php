<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'luz', 'limpieza', 'cerca', 'fugas'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $luz = intval($_POST['luz']);
        $limpieza = intval($_POST['limpieza']);
        $cerca = intval($_POST['cerca']);
        $fugas = intval($_POST['fugas']);
        $detalles = trim($_POST['detalles'] ?? '');
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        if (!in_array($luz, [0,1]) || !in_array($limpieza, [0,1]) || !in_array($cerca, [0,1]) || !in_array($fugas, [0,1])) { $mensaje = "error"; }
        else {
            $obs = "Inspección Física: $detalles";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 18, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora, $luz);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 19, ?, 'Salida')");
                $stmt2->bind_param("isi", $id_reg, $hora, $limpieza);
                $stmt3 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 20, ?, 'Salida')");
                $stmt3->bind_param("isi", $id_reg, $hora, $cerca);
                $stmt4 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 21, ?, 'Salida')");
                $stmt4->bind_param("isi", $id_reg, $hora, $fugas);
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
    <title>GT-CA-011 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_011.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-eye"></i> Registro </h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 15px;">Inspección Física de Instalaciones</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Inspección guardada con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Estación:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>
            
            <div class="item"><span>Iluminación:</span><select name="luz"><option value="1">Óptima</option><option value="0">Deficiente</option></select></div>
            <div class="item"><span>Limpieza:</span><select name="limpieza"><option value="1">Limpio</option><option value="0">Monte alto</option></select></div>
            <div class="item"><span>Cerca/Seguridad:</span><select name="cerca"><option value="1">Seguro</option><option value="0">Dañado</option></select></div>
            <div class="item"><span>Fugas de Agua:</span><select name="fugas"><option value="0">Ninguna</option><option value="1">¡Detectada!</option></select></div>
            
            <label>Detalles y Novedades:</label>
            <textarea name="detalles" placeholder="Escriba hallazgos relevantes..."></textarea>

            <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Inspección</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>
    <script src="assets/js/registro/gt_ca_011.js"></script>
</body>
</html>