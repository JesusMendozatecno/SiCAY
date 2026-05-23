<?php
verificar_sesion();

$sustancias_query = mysqli_query($con, "SELECT id, nombre, unidad_medida FROM sustancia_quimica");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['sustancia', 'tipo', 'cantidad'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_sustancia = intval($_POST['sustancia']);
        $tipo = $_POST['tipo'];
        $cantidad = $_POST['cantidad'];
        $referencia = trim($_POST['referencia'] ?? '');
        $tipos_validos = ['Entrada', 'Salida', 'Ajuste'];
        if (!in_array($tipo, $tipos_validos)) { $mensaje = "error"; }
        elseif (!validar_numeric($cantidad, 0)) { $mensaje = "error"; }
        else {
            $stmt = $con->prepare("INSERT INTO inventario_movimiento (id_sustancia, tipo_movimiento, cantidad, referencia_guia) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $id_sustancia, $tipo, $cantidad, $referencia);
            if ($stmt->execute()) { $mensaje = "exito"; }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-002 - Inventario</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_002.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-boxes"></i> Inventario </h2>
        
        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Movimiento Registrado con éxito!
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Sustancia Química:</label>
            <select name="sustancia" required>
                <option value="">-- Seleccione Químico --</option>
                <?php while($s = mysqli_fetch_assoc($sustancias_query)) { ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo $s['nombre']; ?> (<?php echo $s['unidad_medida']; ?>)</option>
                <?php } ?>
            </select>

            <label>Tipo de Operación:</label>
            <select name="tipo" required>
                <option value="Entrada">Entrada (Ingreso)</option>
                <option value="Salida">Salida (Consumo)</option>
                <option value="Ajuste">Ajuste de Inventario</option>
            </select>

            <label>Cantidad:</label>
            <input type="number" step="0.01" name="cantidad" required placeholder="0.00">

            <label>Nro. Guía o Referencia:</label>
            <input type="text" name="referencia" placeholder="Ej: G-5542">

            <button type="submit" class="btn"><i class="fas fa-truck-loading"></i> Registrar Movimiento</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>
    <script src="assets/js/registro/gt_ca_002.js"></script>
</body>
</html>