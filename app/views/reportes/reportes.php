<?php
verificar_sesion();

// AJAX: return only movements table + pagination
if (isset($_GET['ajax_movimientos'])) {
    $por_pagina = 10;
    $pagina = max(1, intval($_GET['page'] ?? 1));
    $offset = ($pagina - 1) * $por_pagina;
    $total_mov = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM inventario_movimiento"))['total'];
    $total_paginas = max(1, ceil($total_mov / $por_pagina));
    $sqlMov = "SELECT m.id, s.nombre, m.tipo_movimiento, m.cantidad, s.unidad_medida, m.referencia_guia, m.fecha_movimiento 
        FROM inventario_movimiento m 
        JOIN sustancia_quimica s ON m.id_sustancia = s.id 
        ORDER BY m.fecha_movimiento DESC, m.id DESC LIMIT $offset, $por_pagina";
    $resMov = mysqli_query($con, $sqlMov);
    $tabla = '';
    if (mysqli_num_rows($resMov) > 0) {
        while($h = mysqli_fetch_assoc($resMov)) {
            $color_badge = ($h['tipo_movimiento'] == 'Entrada') ? 'bg-entrada' : (($h['tipo_movimiento'] == 'Salida') ? 'bg-salida' : 'bg-ajuste');
            $tabla .= '<tr>';
            $tabla .= '<td class="td-fecha">' . date('d/m/Y', strtotime($h['fecha_movimiento'])) . '</td>';
            $tabla .= '<td>' . htmlspecialchars($h['nombre']) . '</td>';
            $tabla .= '<td><span class="badge ' . $color_badge . '">' . hsc($h['tipo_movimiento']) . '</span></td>';
            $tabla .= '<td><strong>' . hsc($h['cantidad']) . ' ' . hsc($h['unidad_medida']) . '</strong></td>';
            $tabla .= '<td>' . htmlspecialchars($h['referencia_guia']) . '</td>';
            $tabla .= '</tr>';
        }
    } else {
        $tabla = '<tr><td colspan="5" class="empty-row">No hay movimientos registrados.</td></tr>';
    }
    $pagi = '';
    if ($total_paginas > 1) {
        $pagi .= '<button class="page-btn" onclick="irPaginaAjax(1)"' . ($pagina <= 1 ? ' disabled' : '') . '><i class="fa fa-angle-double-left"></i></button>';
        $pagi .= '<button class="page-btn" onclick="irPaginaAjax(' . ($pagina - 1) . ')"' . ($pagina <= 1 ? ' disabled' : '') . '><i class="fa fa-angle-left"></i></button>';
        $inicio = max(1, $pagina - 2);
        $fin = min($total_paginas, $pagina + 2);
        for ($i = $inicio; $i <= $fin; $i++) {
            $act = ($i == $pagina) ? ' active' : '';
            $pagi .= '<button class="page-btn' . $act . '" onclick="irPaginaAjax(' . $i . ')">' . $i . '</button>';
        }
        $pagi .= '<button class="page-btn" onclick="irPaginaAjax(' . ($pagina + 1) . ')"' . ($pagina >= $total_paginas ? ' disabled' : '') . '><i class="fa fa-angle-right"></i></button>';
        $pagi .= '<button class="page-btn" onclick="irPaginaAjax(' . $total_paginas . ')"' . ($pagina >= $total_paginas ? ' disabled' : '') . '><i class="fa fa-angle-double-right"></i></button>';
        $pagi .= '<span class="page-info">Página ' . $pagina . ' de ' . $total_paginas . ' (' . $total_mov . ' registros)</span>';
    }
    echo json_encode(['tabla' => $tabla, 'paginacion' => $pagi, 'pagina' => $pagina, 'total_paginas' => $total_paginas]);
    exit;
}

// 1. STOCK DATA
$sqlStock = "SELECT 
    s.nombre, 
    s.unidad_medida, 
    s.inventario_minimo,
    COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'Entrada' THEN m.cantidad ELSE 0 END), 0) - 
    COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'Salida' THEN m.cantidad ELSE 0 END), 0) +
    COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'Ajuste' THEN m.cantidad ELSE 0 END), 0) as stock_total
    FROM sustancia_quimica s
    LEFT JOIN inventario_movimiento m ON s.id = m.id_sustancia
    GROUP BY s.id ORDER BY s.nombre";
$resStock = mysqli_query($con, $sqlStock);

// 2. MOVEMENTS WITH PAGINATION
$por_pagina = 10;
$pagina = max(1, intval($_GET['page'] ?? 1));
$offset = ($pagina - 1) * $por_pagina;

$total_mov = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM inventario_movimiento"))['total'];
$total_paginas = max(1, ceil($total_mov / $por_pagina));

$sqlMov = "SELECT m.id, s.nombre, m.tipo_movimiento, m.cantidad, s.unidad_medida, m.referencia_guia, m.fecha_movimiento 
    FROM inventario_movimiento m 
    JOIN sustancia_quimica s ON m.id_sustancia = s.id 
    ORDER BY m.fecha_movimiento DESC, m.id DESC LIMIT $offset, $por_pagina";
$resMov = mysqli_query($con, $sqlMov);

// 3. CHART DATA — Monthly movement totals
$sqlMensual = "SELECT 
    DATE_FORMAT(fecha_movimiento, '%Y-%m') as mes,
    SUM(CASE WHEN tipo_movimiento = 'Entrada' THEN cantidad ELSE 0 END) as total_entradas,
    SUM(CASE WHEN tipo_movimiento = 'Salida' THEN cantidad ELSE 0 END) as total_salidas
    FROM inventario_movimiento 
    GROUP BY DATE_FORMAT(fecha_movimiento, '%Y-%m')
    ORDER BY mes ASC LIMIT 12";
$resMensual = mysqli_query($con, $sqlMensual);
$meses = [];
$entradas = [];
$salidas = [];
while ($r = mysqli_fetch_assoc($resMensual)) {
    $meses[] = $r['mes'];
    $entradas[] = floatval($r['total_entradas']);
    $salidas[] = floatval($r['total_salidas']);
}

// 4. CHART DATA — Movement type distribution
$sqlTipos = "SELECT tipo_movimiento, COUNT(*) as total FROM inventario_movimiento GROUP BY tipo_movimiento";
$resTipos = mysqli_query($con, $sqlTipos);
$tipo_labels = [];
$tipo_data = [];
while ($r = mysqli_fetch_assoc($resTipos)) {
    $tipo_labels[] = $r['tipo_movimiento'];
    $tipo_data[] = intval($r['total']);
}

// 5. CHART DATA — Stock levels for all substances
$labels_stock = [];
$data_stock = [];
$data_minimo = [];
mysqli_data_seek($resStock, 0);
while ($r = mysqli_fetch_assoc($resStock)) {
    $labels_stock[] = $r['nombre'];
    $data_stock[] = floatval($r['stock_total']);
    $data_minimo[] = floatval($r['inventario_minimo']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes de Inventario - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/reportes/reportes.css">
</head>
<body>

<div id="bubbles"></div>

<div class="reportes-layout">
    <!-- Sidebar -->
    <aside class="reportes-sidebar">
        <div class="sidebar-brand">
            <i class="fa fa-chart-bar"></i>
            <span>Reportes</span>
        </div>
        <nav class="sidebar-nav">
            <button class="tab-link active" data-tab="disponibilidad">
                <i class="fa fa-boxes-stacked"></i> Disponibilidad
            </button>
            <button class="tab-link" data-tab="movimientos">
                <i class="fa fa-clock-rotate-left"></i> Movimientos
                <span class="badge-count"><?php echo $total_mov; ?></span>
            </button>
            <button class="tab-link" data-tab="graficas">
                <i class="fa fa-chart-line"></i> Gráficas
            </button>
        </nav>
        <div class="sidebar-footer">
            <a href="index.php?route=dashboard" class="btn-back-sidebar"><i class="fas fa-chevron-left"></i> Volver</a>
        </div>
    </aside>

    <!-- Main content -->
    <div class="reportes-content">
        <div class="page-header">
            <h1 class="page-title"><i class="fa fa-chart-bar"></i> Reportes de Inventario</h1>
        </div>

        <!-- SECTION: Disponibilidad -->
        <div id="tab-disponibilidad" class="tab-panel active">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title"><i class="fa fa-boxes-stacked"></i> Disponibilidad de Insumos (Stock Actual)</h2>
                    <button class="btn-print-section" onclick="imprimirSeccion('disponibilidad')"><i class="fa fa-file-pdf"></i> PDF</button>
                </div>
                <div class="table-wrap">
                    <table class="reportes-table">
                        <thead>
                            <tr>
                                <th>Sustancia</th>
                                <th>Unidad</th>
                                <th>Mínimo Requerido</th>
                                <th>Existencia Actual</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php mysqli_data_seek($resStock, 0); while($row = mysqli_fetch_assoc($resStock)) { 
                                $es_critico = ($row['stock_total'] <= $row['inventario_minimo']);
                                $clase_estado = ($es_critico) ? 'alerta' : 'ok';
                                $texto_estado = ($es_critico) ? '<i class="fa fa-warning"></i> Reposición Crítica' : '<i class="fa fa-check-circle"></i> Suficiente';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['unidad_medida']); ?></td>
                                <td><?php echo $row['inventario_minimo']; ?></td>
                                <td><span class="<?php echo $clase_estado; ?>"><?php echo $row['stock_total']; ?></span></td>
                                <td class="<?php echo $clase_estado; ?>"><?php echo $texto_estado; ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECTION: Movimientos -->
        <div id="tab-movimientos" class="tab-panel">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title"><i class="fa fa-clock-rotate-left"></i> Historial de Movimientos</h2>
                    <button class="btn-print-section" onclick="imprimirSeccion('movimientos')"><i class="fa fa-file-pdf"></i> PDF</button>
                </div>
                <div class="table-wrap">
                    <table class="reportes-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Sustancia</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($resMov) > 0): while($h = mysqli_fetch_assoc($resMov)) {
                                $color_badge = ($h['tipo_movimiento'] == 'Entrada') ? 'bg-entrada' : (($h['tipo_movimiento'] == 'Salida') ? 'bg-salida' : 'bg-ajuste');
                            ?>
                            <tr>
                                <td class="td-fecha"><?php echo date('d/m/Y', strtotime($h['fecha_movimiento'])); ?></td>
                                <td><?php echo htmlspecialchars($h['nombre']); ?></td>
                                <td><span class="badge <?php echo $color_badge; ?>"><?php echo hsc($h['tipo_movimiento']); ?></span></td>
                                <td><strong><?php echo hsc($h['cantidad']) . " " . hsc($h['unidad_medida']); ?></strong></td>
                                <td><?php echo htmlspecialchars($h['referencia_guia']); ?></td>
                            </tr>
                            <?php } else: ?>
                            <tr><td colspan="5" class="empty-row">No hay movimientos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_paginas > 1): ?>
                <div class="paginacion">
                    <button class="page-btn" onclick="irPaginaAjax(1)" <?php echo $pagina <= 1 ? 'disabled' : ''; ?>><i class="fa fa-angle-double-left"></i></button>
                    <button class="page-btn" onclick="irPaginaAjax(<?php echo $pagina - 1; ?>)" <?php echo $pagina <= 1 ? 'disabled' : ''; ?>><i class="fa fa-angle-left"></i></button>
                    <?php 
                    $inicio = max(1, $pagina - 2);
                    $fin = min($total_paginas, $pagina + 2);
                    for ($i = $inicio; $i <= $fin; $i++): ?>
                    <button class="page-btn <?php echo $i == $pagina ? 'active' : ''; ?>" onclick="irPaginaAjax(<?php echo $i; ?>)"><?php echo $i; ?></button>
                    <?php endfor; ?>
                    <button class="page-btn" onclick="irPaginaAjax(<?php echo $pagina + 1; ?>)" <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>><i class="fa fa-angle-right"></i></button>
                    <button class="page-btn" onclick="irPaginaAjax(<?php echo $total_paginas; ?>)" <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>><i class="fa fa-angle-double-right"></i></button>
                    <span class="page-info">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?> (<?php echo $total_mov; ?> registros)</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SECTION: Gráficas -->
        <div id="tab-graficas" class="tab-panel">
            <div class="charts-grid">
                <div class="chart-card">
                    <h3><i class="fa fa-chart-line"></i> Entradas vs Salidas por Mes</h3>
                    <div class="chart-wrap">
                        <canvas id="chartMovements"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3><i class="fa fa-chart-bar"></i> Stock Actual por Sustancia</h3>
                    <div class="chart-wrap">
                        <canvas id="chartStock"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3><i class="fa fa-chart-pie"></i> Distribución por Tipo de Movimiento</h3>
                    <div class="chart-wrap">
                        <canvas id="chartPie"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const CHART_DATA = {
    meses: <?php echo json_encode($meses); ?>,
    entradas: <?php echo json_encode($entradas); ?>,
    salidas: <?php echo json_encode($salidas); ?>,
    stock_labels: <?php echo json_encode($labels_stock); ?>,
    stock_data: <?php echo json_encode($data_stock); ?>,
    stock_minimo: <?php echo json_encode($data_minimo); ?>,
    tipo_labels: <?php echo json_encode($tipo_labels); ?>,
    tipo_data: <?php echo json_encode($tipo_data); ?>
};
</script>
<script src="assets/js/reportes/reportes.js"></script>

</body>
</html>
