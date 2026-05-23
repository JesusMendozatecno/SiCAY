<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$insumos_query = mysqli_query($con, "SELECT id, nombre, unidad_medida FROM sustancia_quimica");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'sustancia', 'cantidad'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $id_sust = intval($_POST['sustancia']);
        $cantidad = $_POST['cantidad'];
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        if (!validar_numeric($cantidad, 0)) { $mensaje = "error"; }
        else {
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $id_inst, $id_user, $fecha);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO consumo_sustancia (id_registro_diario, id_sustancia, cantidad_consumida) VALUES (?, ?, ?)");
                $stmt1->bind_param("iid", $id_reg, $id_sust, $cantidad);
                $stmt2 = $con->prepare("INSERT INTO inventario_movimiento (id_sustancia, tipo_movimiento, cantidad, referencia_guia) VALUES (?, 'Salida', ?, 'Consumo Diario Planilla 004')");
                $stmt2->bind_param("id", $id_sust, $cantidad);
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
    <title>GT-CA-004 - Consumo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_004.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-weight-hanging"></i> Consumo </h2>
        <p style="text-align: center; font-size: 0.8rem; opacity: 0.7; margin-bottom: 10px;">Control Diario de Consumo Químico</p>
        
        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Gasto registrado y descontado del inventario!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Estación:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>
            <label>Sustancia Utilizada:</label>
            <select name="sustancia" required>
                <option value="">-- Seleccione Insumo --</option>
                <?php while($s = mysqli_fetch_assoc($insumos_query)) { echo "<option value='{$s['id']}'>{$s['nombre']} ({$s['unidad_medida']})</option>"; } ?>
            </select>
            <label>Cantidad Consumida:</label>
            <input type="number" step="0.01" name="cantidad" required placeholder="0.00">
            <button type="submit" class="btn"><i class="fas fa-save"></i> Registrar Gasto</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>
    <script src="assets/js/registro/gt_ca_004.js"></script>
</body>
</html>