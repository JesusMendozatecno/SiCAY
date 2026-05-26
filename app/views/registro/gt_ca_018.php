<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'ubicacion_exacta', 'id_extintor', 'presion', 'precinto', 'vencimiento'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $ubicacion = trim($_POST['ubicacion_exacta']);
        $id_extintor = trim($_POST['id_extintor']);
        $presion = intval($_POST['presion']);
        $precinto = intval($_POST['precinto']);
        $vencimiento = trim($_POST['vencimiento']);
        
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        
        if (!in_array($presion, [0, 1]) || !in_array($precinto, [0, 1])) { $mensaje = "error"; }
        else {
            $obs = "Extintor ID: $id_extintor | Ubicación: $ubicacion | Vence: $vencimiento";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 34, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora, $presion);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 35, ?, 'Salida')");
                $stmt2->bind_param("isi", $id_reg, $hora, $precinto);
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
    <title>Seguridad GT-CA-018 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_018.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-fire-extinguisher"></i> Registro </h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 15px;">Inspección Técnica de Extintores</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Inspección guardada con éxito!
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Ubicación Exacta:</label>
            <div style="display: flex; gap: 10px;">
                <select name="planta" required style="flex: 1.5;">
                    <option value="">-- Planta --</option>
                    <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
                </select>
                <input type="text" name="ubicacion_exacta" placeholder="Ej: Tableros" style="flex: 1;" required>
            </div>

            <label>ID Extintor / Vencimiento:</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="id_extintor" placeholder="Código" required>
                <input type="date" name="vencimiento" required>
            </div>

            <label>Estado Presión:</label>
            <select name="presion" required>
                <option value="1">🟢 Zona Verde (OK)</option>
                <option value="0">🔴 Requiere Recarga</option>
            </select>

            <label>Precinto y Manguera:</label>
            <select name="precinto" required>
                <option value="1">✅ Buen Estado</option>
                <option value="0">⚠️ Roto o Dañado</option>
            </select>

            <button type="submit" class="btn"><i class="fas fa-shield-alt"></i> Guardar Inspección Técnica</button>
            <a href="index.php?route=registros" class="back">← Volver</a>
        </form>
    </div>

    <script src="assets/js/registro/gt_ca_018.js"></script>
</body>
</html>