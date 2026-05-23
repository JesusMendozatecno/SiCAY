<?php
verificar_sesion();

// 1. CONSULTA DE MOVIMIENTOS DETALLADOS
$sql = "SELECT m.id, s.nombre, m.tipo_movimiento, m.cantidad, s.unidad_medida, m.referencia_guia, m.fecha_movimiento 
        FROM inventario_movimiento m 
        JOIN sustancia_quimica s ON m.id_sustancia = s.id 
        ORDER BY m.fecha_movimiento DESC, m.id DESC";
$resultado = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario Detallado - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/inventario/inventario.css">
</head>
<body>

<div id="bubbles"></div>

<div class="container">
    <a href="index.php?route=dashboard" class="btn-volver"><i class="fa fa-arrow-left"></i> Volver al Dashboard</a>
    
    <h2><i class="fa fa-clipboard-list"></i> Historial de Movimientos de Almacén</h2>
    <p>Auditoría completa de entradas, salidas y ajustes realizados en el inventario.</p>

    <div class="search-box">
        <i class="fa fa-search"></i>
        <input type="text" id="busqueda" onkeyup="filtrarTabla()" placeholder="Buscar por sustancia, tipo de operación o referencia...">
    </div>

    <div class="table-container">
        <table id="tablaInventario">
            <thead>
                <tr>
                    <th><i class="fa fa-calendar-day"></i> Fecha</th>
                    <th><i class="fa fa-flask"></i> Sustancia</th>
                    <th><i class="fa fa-exchange-alt"></i> Operación</th>
                    <th><i class="fa fa-weight-hanging"></i> Cantidad</th>
                    <th><i class="fa fa-hashtag"></i> Referencia / Guía</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($resultado)) { 
                    $clase = ($row['tipo_movimiento'] == 'Entrada') ? 'bg-entrada' : (($row['tipo_movimiento'] == 'Salida') ? 'bg-salida' : 'bg-ajuste');
                    $fecha = date("d/m/Y", strtotime($row['fecha_movimiento']));
                ?>
                <tr>
                    <td><?php echo $fecha; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                    <td><span class="badge <?php echo $clase; ?>"><?php echo hsc($row['tipo_movimiento']); ?></span></td>
                    <td><?php echo hsc($row['cantidad']) . " " . hsc($row['unidad_medida']); ?></td>
                    <td><?php echo $row['referencia_guia'] ? hsc($row['referencia_guia']) : '<span style="opacity:0.5">---</span>'; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/inventario/inventario.js"></script>

</body>
</html>
