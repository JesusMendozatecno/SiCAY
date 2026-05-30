<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'cant_cloro', 'cant_sulfato', 'cant_hipo', 'prioridad'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $cloro = $_POST['cant_cloro'];
        $sulfato = $_POST['cant_sulfato'];
        $hipo = $_POST['cant_hipo'];
        $prioridad = $_POST['prioridad'];
        
        $id_user = (int) $_SESSION['id_usuario'];
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $prioridades_validas = ['Normal', 'Urgente', 'Emergencia'];
        if (!in_array($prioridad, $prioridades_validas)) { $mensaje = "error"; }
        elseif (!validar_numeric($cloro, 0) || !validar_numeric($sulfato, 0) || !validar_numeric($hipo, 0)) { $mensaje = "error"; }
        else {
            $obs = "SOLICITUD SQ - PRIORIDAD: $prioridad";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $obs);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 38, ?, 'Cruda')");
                $stmt1->bind_param("isd", $id_reg, $hora, $cloro);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 39, ?, 'Cruda')");
                $stmt2->bind_param("isd", $id_reg, $hora, $sulfato);
                $stmt3 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 40, ?, 'Cruda')");
                $stmt3->bind_param("isd", $id_reg, $hora, $hipo);
                if ($stmt1->execute() && $stmt2->execute() && $stmt3->execute()) {
                    $mensaje = "exito";
                }
                $stmt1->close();
                $stmt2->close();
                $stmt3->close();
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
    <title>Solicitud SQ - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/solicitud_sq.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-truck-moving"></i> Solicitud de Químicos</h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 15px;">Gestión de Sustancias Químicas (SQ)</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Solicitud enviada exitosamente!
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta Solicitante:</label>
            <select name="planta" required>
                <option value="">-- Seleccione Planta --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre']; ?></option>
                <?php } ?>
            </select>

            <label>Prioridad del Pedido:</label>
            <select name="prioridad">
                <option value="Normal">Normal (Reposición)</option>
                <option value="Urgente">Urgente (Stock Crítico)</option>
                <option value="Emergencia">Emergencia (Sin Inventario)</option>
            </select>

            <hr>

            <label>Gas Cloro (Cilindros 68kg/900kg):</label>
            <input type="number" name="cant_cloro" value="0" min="0">

            <div class="grid">
                <div>
                    <label>Sulfato de Aluminio (Kg):</label>
                    <input type="number" name="cant_sulfato" value="0" min="0">
                </div>
                <div>
                    <label>Hipoclorito (Kg):</label>
                    <input type="number" name="cant_hipo" value="0" min="0">
                </div>
            </div>

            <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Enviar Solicitud SQ</button>
        </form>
    </div>

    <script src="assets/js/registro/solicitud_sq.js"></script>
</body>
</html>