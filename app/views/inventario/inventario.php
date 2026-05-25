<?php
verificar_sesion();

// ── Movimientos detallados ──
$sql_mov = "SELECT m.id, s.nombre, m.tipo_movimiento, m.cantidad, s.unidad_medida, m.referencia_guia, m.fecha_movimiento 
            FROM inventario_movimiento m 
            JOIN sustancia_quimica s ON m.id_sustancia = s.id 
            ORDER BY m.fecha_movimiento DESC, m.id DESC";
$movimientos = mysqli_query($con, $sql_mov);

// ── Stock actual por producto ──
$sql_stock = "SELECT s.id, s.nombre, s.unidad_medida, s.inventario_minimo,
                     COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'Entrada' THEN m.cantidad ELSE 0 END), 0) -
                     COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'Salida' THEN m.cantidad ELSE 0 END), 0) +
                     COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'Ajuste' THEN m.cantidad ELSE 0 END), 0) as stock_total
              FROM sustancia_quimica s
              LEFT JOIN inventario_movimiento m ON s.id = m.id_sustancia
              GROUP BY s.id
              ORDER BY s.nombre ASC";
$stock = mysqli_query($con, $sql_stock);
$stock_data = [];
while ($row = mysqli_fetch_assoc($stock)) {
    $stock_data[] = $row;
}
$total_productos = count($stock_data);
$total_movimientos = mysqli_num_rows($movimientos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/inventario/inventario.css">
</head>
<body>

<div id="bubbles"></div>

<div class="inventario-layout">

    <!-- Sidebar -->
    <aside class="inventario-sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-warehouse"></i>
            Inventario
        </div>
        <nav class="sidebar-nav">
            <button class="tab-link active" data-tab="almacen">
                <i class="fas fa-boxes"></i> Almacén
                <span class="badge-count"><?php echo $total_productos; ?></span>
            </button>
            <button class="tab-link" data-tab="movimientos">
                <i class="fas fa-truck-loading"></i> Movimientos de almacén
                <span class="badge-count"><?php echo $total_movimientos; ?></span>
            </button>
        </nav>
        <div class="sidebar-footer">
            <a href="index.php?route=dashboard" class="btn-back-sidebar">
                <i class="fas fa-chevron-left"></i> Volver
            </a>
        </div>
    </aside>

    <!-- Content -->
    <div class="inventario-content">

        <!-- ══════ ALMACÉN ══════ -->
        <div id="tab-almacen" class="tab-panel active">

            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title"><i class="fas fa-cubes"></i> Inventario General</h3>
                </div>
                <p class="section-desc">Productos registrados en el sistema y sus cantidades actuales.</p>

                <!-- Filters -->
                <div class="inventario-filters">
                    <div class="filter-group">
                        <label>Filtro:</label>
                        <select id="filtroAlmacen" onchange="cambiarFiltroAlmacen()">
                            <option value="todo">Buscar todo</option>
                            <option value="letras">Por letras</option>
                            <option value="cantidad">Por cantidad</option>
                        </select>
                    </div>

                    <div class="filter-group" id="grupoLetras" style="display:none;">
                        <i class="fas fa-search"></i>
                        <input type="text" id="busquedaLetras" placeholder="Escribe para filtrar..." onkeyup="aplicarFiltrosAlmacen()">
                    </div>

                    <div class="filter-group" id="grupoCantidad" style="display:none;">
                        <label>Orden:</label>
                        <select id="ordenCantidad" onchange="aplicarFiltrosAlmacen()">
                            <option value="mayor">Mayor a menor</option>
                            <option value="menor">Menor a mayor</option>
                        </select>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="reportes-table" id="tablaAlmacen">
                        <thead>
                            <tr>
                                <th><i class="fas fa-flask"></i> Producto</th>
                                <th><i class="fas fa-ruler"></i> Unidad</th>
                                <th><i class="fas fa-weight-hanging"></i> Stock Actual</th>
                                <th><i class="fas fa-triangle-exclamation"></i> Stock Mínimo</th>
                                <th><i class="fas fa-check-circle"></i> Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyAlmacen">
                            <?php foreach ($stock_data as $p): 
                                $estado = $p['stock_total'] <= $p['inventario_minimo'] ? 'alerta' : 'ok';
                                $estado_texto = $p['stock_total'] <= $p['inventario_minimo'] ? 'Bajo stock' : 'Disponible';
                            ?>
                            <tr data-nombre="<?php echo hsc($p['nombre']); ?>" data-stock="<?php echo $p['stock_total']; ?>">
                                <td><strong><?php echo hsc($p['nombre']); ?></strong></td>
                                <td><?php echo hsc($p['unidad_medida']); ?></td>
                                <td><?php echo number_format($p['stock_total'], 2); ?></td>
                                <td><?php echo number_format($p['inventario_minimo'], 2); ?></td>
                                <td><span class="badge <?php echo $estado; ?>"><?php echo $estado_texto; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ══════ MOVIMIENTOS ══════ -->
        <div id="tab-movimientos" class="tab-panel">

            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title"><i class="fas fa-clipboard-list"></i> Movimientos del Almacén</h3>
                </div>
                <p class="section-desc">Auditoría completa de entradas, salidas y ajustes realizados en el inventario.</p>

                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="busquedaMov" onkeyup="filtrarMovimientos()" placeholder="Buscar por sustancia, operación o referencia...">
                </div>

                <div class="table-wrap">
                    <table class="reportes-table" id="tablaMovimientos">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar-day"></i> Fecha</th>
                                <th><i class="fas fa-flask"></i> Sustancia</th>
                                <th><i class="fas fa-exchange-alt"></i> Operación</th>
                                <th><i class="fas fa-weight-hanging"></i> Cantidad</th>
                                <th><i class="fas fa-hashtag"></i> Referencia / Guía</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($movimientos)): 
                                $clase = $row['tipo_movimiento'] == 'Entrada' ? 'bg-entrada' : ($row['tipo_movimiento'] == 'Salida' ? 'bg-salida' : 'bg-ajuste');
                                $fecha = date("d/m/Y", strtotime($row['fecha_movimiento']));
                            ?>
                            <tr>
                                <td class="td-fecha"><?php echo $fecha; ?></td>
                                <td><strong><?php echo hsc($row['nombre']); ?></strong></td>
                                <td><span class="badge <?php echo $clase; ?>"><?php echo hsc($row['tipo_movimiento']); ?></span></td>
                                <td><?php echo hsc($row['cantidad']) . ' ' . hsc($row['unidad_medida']); ?></td>
                                <td><?php echo $row['referencia_guia'] ? hsc($row['referencia_guia']) : '<span class="td-fecha">---</span>'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

<script src="assets/js/inventario/inventario.js"></script>
</body>
</html>
