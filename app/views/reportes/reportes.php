<?php
verificar_sesion();

// 1. CONSULTA PARA EL STOCK ACTUAL
$sqlStock = "SELECT 
    s.nombre, 
    s.unidad_medida, 
    s.inventario_minimo,
    COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'Entrada' THEN m.cantidad ELSE 0 END), 0) - 
    COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'Salida' THEN m.cantidad ELSE 0 END), 0) +
    COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'Ajuste' THEN m.cantidad ELSE 0 END), 0) as stock_total
    FROM sustancia_quimica s
    LEFT JOIN inventario_movimiento m ON s.id = m.id_sustancia
    GROUP BY s.id";

$resStock = mysqli_query($con, $sqlStock);

// 2. CONSULTA PARA ÚLTIMOS MOVIMIENTOS
$sqlHistorial = "SELECT m.*, s.nombre, s.unidad_medida 
    FROM inventario_movimiento m 
    JOIN sustancia_quimica s ON m.id_sustancia = s.id 
    ORDER BY m.id DESC LIMIT 10";
$resHistorial = mysqli_query($con, $sqlHistorial);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes de Inventario - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/reportes/reportes.css">
</head>
<body>

<div id="bubbles"></div>

<div class="container">
    <button class="btn-print" onclick="window.print()"><i class="fa fa-print"></i> Generar PDF / Imprimir</button>
    <a href="index.php?route=dashboard" class="btn-volver">← Volver al Dashboard</a>
    
    <div class="card">
        <h2><i class="fa fa-boxes-stacked"></i> Disponibilidad de Insumos (Stock Actual)</h2>
        <table>
            <thead>
                <tr>
                    <th>Sustancia</th>
                    <th>Mínimo Requerido</th>
                    <th>Existencia Actual</th>
                    <th>Estado de Inventario</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($resStock)) { 
                    $es_critico = ($row['stock_total'] <= $row['inventario_minimo']);
                    $clase_estado = ($es_critico) ? 'alerta' : 'ok';
                    $texto_estado = ($es_critico) ? '<i class="fa fa-warning"></i> REPOSICIÓN CRÍTICA' : '<i class="fa fa-check-circle"></i> Suficiente';
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                    <td><?php echo $row['inventario_minimo'] . " " . htmlspecialchars($row['unidad_medida']); ?></td>
                    <td><span class="<?php echo $clase_estado; ?>"><?php echo $row['stock_total'] . " " . htmlspecialchars($row['unidad_medida']); ?></span></td>
                    <td class="<?php echo $clase_estado; ?>"><?php echo $texto_estado; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2><i class="fa fa-clock-rotate-left"></i> Historial Reciente de Movimientos</h2>
        <table>
            <thead>
                <tr>
                    <th>Sustancia</th>
                    <th>Tipo de Movimiento</th>
                    <th>Cantidad Transada</th>
                    <th>Referencia / Guía</th>
                </tr>
            </thead>
            <tbody>
                <?php while($h = mysqli_fetch_assoc($resHistorial)) { 
                    $color_badge = ($h['tipo_movimiento'] == 'Entrada') ? 'bg-entrada' : (($h['tipo_movimiento'] == 'Salida') ? 'bg-salida' : 'bg-ajuste');
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($h['nombre']); ?></td>
                    <td><span class="badge <?php echo $color_badge; ?>"><?php echo hsc($h['tipo_movimiento']); ?></span></td>
                    <td><strong><?php echo hsc($h['cantidad']) . " " . hsc($h['unidad_medida']); ?></strong></td>
                    <td><?php echo htmlspecialchars($h['referencia_guia']); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/reportes/reportes.js"></script>

</body>
</html>
