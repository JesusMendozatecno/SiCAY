<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'herramienta', 'cantidad', 'accion', 'responsable'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $herramienta = trim($_POST['herramienta']);
        $cantidad = $_POST['cantidad'];
        $estado = intval($_POST['estado'] ?? 0);
        $responsable = trim($_POST['responsable']);
        $accion = $_POST['accion'];
        
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $acciones_validas = ['Préstamo / Salida', 'Devolución / Entrada'];
        if (!in_array($accion, $acciones_validas)) { $mensaje = "error"; }
        elseif (!in_array($estado, [0, 1])) { $mensaje = "error"; }
        elseif (!validar_numeric($cantidad, 0)) { $mensaje = "error"; }
        else {
            $movimiento = "Acción: $accion | Equipo: $herramienta | Responsable: $responsable";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $movimiento);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 30, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora, $estado);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 31, ?, 'Salida')");
                $stmt2->bind_param("isd", $id_reg, $hora, $cantidad);
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
    <title>Herramientas GT-CA-016 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_016.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-wrench"></i> Herramientas</h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 10px;">Control de Herramientas y Equipos</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Movimiento registrado con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Ubicación:</label>
            <select name="planta" required>
                <option value="">-- Seleccione Planta --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>

            <label>Tipo de Movimiento:</label>
            <select name="accion" required>
                <option value="Préstamo / Salida">📤 Préstamo / Salida</option>
                <option value="Devolución / Entrada">📥 Devolución / Entrada</option>
            </select>

            <label>Herramienta y Cantidad:</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="herramienta" placeholder="Nombre del Equipo" style="flex: 3;" required>
                <input type="number" name="cantidad" value="1" style="flex: 1;" required>
            </div>

            <label>Estado del Equipo:</label>
            <select name="estado">
                <option value="1">✅ Operativo / Buen Estado</option>
                <option value="0">❌ Dañado / Mal Estado</option>
            </select>

            <label>Responsable del Movimiento:</label>
            <input type="text" name="responsable" placeholder="Nombre del trabajador" required>

            <button type="submit" class="btn"><i class="fas fa-save"></i> Registrar Movimiento</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>

    <script src="assets/js/registro/gt_ca_016.js"></script>
</body>
</html>