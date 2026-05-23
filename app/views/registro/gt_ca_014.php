<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'turno', 'entrega'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $turno = $_POST['turno'];
        $entrega = intval($_POST['entrega']);
        $novedades = trim($_POST['novedades'] ?? '');
        $pendientes = trim($_POST['pendientes'] ?? '');
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $turnos_validos = ['Mañana (07:00 - 15:00)', 'Tarde (15:00 - 23:00)', 'Noche (23:00 - 07:00)'];
        if (!in_array($turno, $turnos_validos)) { $mensaje = "error"; }
        elseif (!in_array($entrega, [0, 1])) { $mensaje = "error"; }
        else {
            $resumen = "Turno: $turno | Novedades: $novedades | Pendientes: $pendientes";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $resumen);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 28, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora, $entrega);
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
    <title>GT-CA-014 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_014.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-users"></i> Registro </h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 15px;">Entrega y Recepción de Guardia</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Relevo registrado con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Estación:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>
            
            <label>Turno que entrega:</label>
            <select name="turno" required>
                <option value="Mañana (07:00 - 15:00)">Mañana (07:00 - 15:00)</option>
                <option value="Tarde (15:00 - 23:00)">Tarde (15:00 - 23:00)</option>
                <option value="Noche (23:00 - 07:00)">Noche (23:00 - 07:00)</option>
            </select>
            
            <label>Condición de Instalación:</label>
            <select name="entrega" required>
                <option value="1">✅ Operativa y Sin Novedad</option>
                <option value="0">⚠️ Operativa con Novedades</option>
            </select>
            
            <label>Novedades y Pendientes:</label>
            <textarea name="novedades" placeholder="Resumen del turno..."></textarea>
            <textarea name="pendientes" style="margin-top:10px" placeholder="Tareas para el relevo..."></textarea>
            
            <button type="submit" class="btn"><i class="fas fa-save"></i> Registrar Relevo</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>

    <script src="assets/js/registro/gt_ca_014.js"></script>
</body>
</html>