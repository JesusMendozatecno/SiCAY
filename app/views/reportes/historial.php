<?php
verificar_sesion();
$rol = $_SESSION['rol'] ?? 'Operador';
// Track page view with full URL
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
log_activity($_SESSION['id_usuario'], 'Consultó el historial del sistema', "URL: $current_url", 'view', 'Historial');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial del Sistema - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/reportes/historial.css">
</head>
<body>

<div class="historial-page">
    <div class="historial-container">

        <!-- Header -->
        <div class="historial-header">
            <div class="header-left">
                <a href="index.php?route=dashboard" class="btn-back" title="V">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1><i class="fas fa-history"></i> Historial</h1>
                    <p class="header-subtitle">Registro completo de todas las actividades del sistema SICAY</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="exportarPDF()">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </button>
                <button class="btn btn-refresh" onclick="cargarHistorial()">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>
        </div>

        <!-- Stats Panels -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-total"><i class="fas fa-database"></i></div>
                <div class="stat-info">
                    <span class="stat-number" id="statTotal">-</span>
                    <span class="stat-label">Total Registros</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-today"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-info">
                    <span class="stat-number" id="statToday">-</span>
                    <span class="stat-label">Hoy</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-week"><i class="fas fa-calendar-week"></i></div>
                <div class="stat-info">
                    <span class="stat-number" id="statWeek">-</span>
                    <span class="stat-label">Últimos 7 Días</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-month"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <span class="stat-number" id="statMonth">-</span>
                    <span class="stat-label">Este Mes</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <div class="filters-top">
                <div class="filter-group">
                    <label><i class="fas fa-user"></i> Usuario</label>
                    <input type="text" id="filterUsuario" class="filter-input" placeholder="Filtrar por usuario..." oninput="aplicarFiltros()">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-bolt"></i> Acción</label>
                    <input type="text" id="filterAccion" class="filter-input" placeholder="Buscar acción..." oninput="aplicarFiltros()">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-tag"></i> Tipo</label>
                    <select id="filterTipo" class="filter-input" onchange="aplicarFiltros()">
                        <option value="">Todos los tipos</option>
                        <option value="login">Inicio de Sesión</option>
                        <option value="logout">Cierre de Sesión</option>
                        <option value="create">Creación</option>
                        <option value="update">Actualización</option>
                        <option value="delete">Eliminación</option>
                        <option value="view">Visualización</option>
                        <option value="export">Exportación</option>
                        <option value="config">Configuración</option>
                        <option value="security">Seguridad</option>
                        <option value="notification">Notificación</option>
                        <option value="admin">Administración</option>
                        <option value="system">Sistema</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar-start"></i> Desde</label>
                    <input type="date" id="filterDesde" class="filter-input" onchange="aplicarFiltros()">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar-end"></i> Hasta</label>
                    <input type="date" id="filterHasta" class="filter-input" onchange="aplicarFiltros()">
                </div>
            </div>
            <div class="filters-info">
                <span id="resultCount">Cargando...</span>
                <button class="btn btn-clear" onclick="limpiarFiltros()"><i class="fas fa-eraser"></i> Limpiar filtros</button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-wrapper">
                <table class="historial-table">
                    <thead>
                        <tr>
                            <th class="th-fecha">Fecha / Hora</th>
                            <th class="th-usuario">Usuario</th>
                            <th class="th-accion">Acción</th>
                            <th class="th-tipo">Tipo</th>
                            <th class="th-modulo">Módulo</th>
                            <th class="th-ip">IP</th>
                            <th class="th-detalle">Detalle</th>
                        </tr>
                    </thead>
                    <tbody id="historialBody">
                        <tr><td colspan="7" class="loading-row"><i class="fas fa-spinner fa-spin"></i> Cargando historial...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination" id="pagination"></div>
        </div>

        <!-- Cleanup (Admin only) -->
        <?php if ($rol === 'Admin'): ?>
        <div class="cleanup-card">
            <div class="cleanup-header">
                <i class="fas fa-trash-alt"></i>
                <div>
                    <h3>Limpieza del Historial</h3>
                    <p>Elimina registros del historial de forma permanente. Esta acción no se puede deshacer.</p>
                </div>
            </div>
            <div class="cleanup-actions">
                <button class="btn btn-cleanup" data-periodo="hora" onclick="limpiarHistorial('hora')">
                    <i class="fas fa-clock"></i> Última hora
                </button>
                <button class="btn btn-cleanup" data-periodo="dia" onclick="limpiarHistorial('dia')">
                    <i class="fas fa-sun"></i> Último día
                </button>
                <button class="btn btn-cleanup" data-periodo="mes" onclick="limpiarHistorial('mes')">
                    <i class="fas fa-calendar"></i> Último mes
                </button>
                <button class="btn btn-cleanup" data-periodo="ano" onclick="limpiarHistorial('ano')">
                    <i class="fas fa-calendar-year"></i> Último año
                </button>
                <button class="btn btn-cleanup btn-cleanup-danger" data-periodo="total" onclick="limpiarHistorial('total')">
                    <i class="fas fa-radiation"></i> Limpiar todo
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Back link -->
        <a href="index.php?route=dashboard" class="volver-link">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard Principal
        </a>
    </div>
</div>

<!-- Modal Detalle -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-search-plus"></i> Detalle del Registro</h3>
            <button class="modal-close" onclick="cerrarModal()">&times;</button>
        </div>
        <div class="modal-body" id="detailBody">
            <div class="detail-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
        </div>
    </div>
</div>

<script src="assets/js/reportes/historial.js"></script>
</body>
</html>
