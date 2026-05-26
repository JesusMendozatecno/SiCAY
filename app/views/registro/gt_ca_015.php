<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'nombre_visitante', 'cedula', 'motivo', 'quien_autoriza', 'estado_acceso'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $visitante = trim($_POST['nombre_visitante']);
        $cedula = trim($_POST['cedula']);
        $motivo = trim($_POST['motivo']);
        $autoriza = trim($_POST['quien_autoriza']);
        $estado_acceso = intval($_POST['estado_acceso']);
        
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora_ingreso = date('H:i:s');
        
        if (!in_array($estado_acceso, [0, 1])) { $mensaje = "error"; }
        else {
            $detalles = "Visitante: $visitante (V-$cedula) | Motivo: $motivo | Autoriza: $autoriza";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $detalles);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 29, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora_ingreso, $estado_acceso);
                if ($stmt1->execute()) {
                    $mensaje = "exito";
                }
                $stmt1->close();
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
    <title>Acceso GT-CA-015 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_015.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-id-card"></i> Registro </h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 10px;">Control de Acceso a Instalación</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Acceso registrado exitosamente!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Instalación:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>

            <label>Visitante / Ente y Cédula:</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="nombre_visitante" placeholder="Nombre" required>
                <input type="text" name="cedula" placeholder="Cédula" required>
            </div>

            <label>Motivo de la Visita:</label>
            <input type="text" name="motivo" placeholder="Ej: Mantenimiento Preventivo" required>

            <label>¿Quién Autoriza?</label>
            <input type="text" name="quien_autoriza" placeholder="Nombre de autoridad" required>

            <label>Estado de Ingreso:</label>
            <select name="estado_acceso">
                <option value="1">✅ Ingreso Normal</option>
                <option value="0">⚠️ Incidencia en Puerta</option>
            </select>

            <button type="submit" class="btn"><i class="fas fa-door-open"></i> Registrar Ingreso</button>
            <a href="index.php?route=registros" class="back">← Volver</a>
        </form>
    </div>

    <script src="assets/js/registro/gt_ca_015.js"></script>
</body>
</html>