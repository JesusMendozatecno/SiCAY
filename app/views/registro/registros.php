<?php
verificar_sesion();

$grupos = [
    [
        'titulo' => 'Calidad del Agua',
        'icono' => 'fa-tint',
        'items' => [
            ['n' => 'GT-CA-001', 'r' => 'gt_ca_001', 'd' => 'Cloro y pH'],
            ['n' => 'GT-CA-002', 'r' => 'gt_ca_002', 'd' => 'Inventario Químico'],
            ['n' => 'GT-CA-003', 'r' => 'gt_ca_003', 'd' => 'Calidad del Agua'],
            ['n' => 'GT-CA-004', 'r' => 'gt_ca_004', 'd' => 'Consumo Químico'],
        ]
    ],
    [
        'titulo' => 'Operación de Planta',
        'icono' => 'fa-cogs',
        'items' => [
            ['n' => 'GT-CA-005', 'r' => 'gt_ca_005', 'd' => 'Cilindros Cloro'],
            ['n' => 'GT-CA-006', 'r' => 'gt_ca_006', 'd' => 'Lavado Filtros'],
            ['n' => 'GT-CA-007', 'r' => 'gt_ca_007', 'd' => 'Equipos y Bombas'],
            ['n' => 'GT-CA-008', 'r' => 'gt_ca_008', 'd' => 'Niveles de Agua'],
        ]
    ],
    [
        'titulo' => 'Monitoreo Técnico',
        'icono' => 'fa-chart-line',
        'items' => [
            ['n' => 'GT-CA-009', 'r' => 'gt_ca_009', 'd' => 'Medición Caudales'],
            ['n' => 'GT-CA-010', 'r' => 'gt_ca_010', 'd' => 'Gestión Lodos'],
            ['n' => 'GT-CA-011', 'r' => 'gt_ca_011', 'd' => 'Inspección Planta'],
            ['n' => 'GT-CA-012', 'r' => 'gt_ca_012', 'd' => 'Grupo Electrógeno'],
            ['n' => 'GT-CA-013', 'r' => 'gt_ca_013', 'd' => 'Calibración Equipos'],
        ]
    ],
    [
        'titulo' => 'Seguridad y Personal',
        'icono' => 'fa-shield-alt',
        'items' => [
            ['n' => 'GT-CA-014', 'r' => 'gt_ca_014', 'd' => 'Entrega Guardia'],
            ['n' => 'GT-CA-015', 'r' => 'gt_ca_015', 'd' => 'Control Acceso'],
            ['n' => 'GT-CA-016', 'r' => 'gt_ca_016', 'd' => 'Herramientas'],
            ['n' => 'GT-CA-017', 'r' => 'gt_ca_017', 'd' => 'Consumo Eléctrico'],
        ]
    ],
    [
        'titulo' => 'Equipos de Emergencia',
        'icono' => 'fa-fire-extinguisher',
        'items' => [
            ['n' => 'GT-CA-018', 'r' => 'gt_ca_018', 'd' => 'Extintores'],
            ['n' => 'GT-CA-019', 'r' => 'gt_ca_019', 'd' => 'Equipos Protección'],
        ]
    ],
    [
        'titulo' => 'Gestión General',
        'icono' => 'fa-folder-open',
        'items' => [
            ['n' => 'Solicitud SQ', 'r' => 'solicitud_sq', 'd' => 'Formato de Solicitud Químicos'],
            ['n' => 'Laboratorio', 'r' => 'resultados_laboratorio', 'd' => 'Resultados de Análisis'],
            ['n' => 'Aplicación', 'r' => 'formatos_aplicacion', 'd' => 'Formatos de Aplicación Técnica'],
            ['n' => 'Instalaciones', 'r' => 'instalaciones', 'd' => 'Registro de Instalaciones de Agua'],
            ['n' => 'Parámetros', 'r' => 'gestion_parametros', 'd' => 'Configuración de estándares de calidad'],
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SICAY - Gestión de Registros</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/registros.css">
</head>
<body>

<div id="bubble-container"></div>

<div class="registros-layout">
    <div class="reg-sidebar">
        <div class="reg-sidebar-header">
            <h2><i class="fas fa-file-signature"></i> Registros</h2>
        </div>
        <nav class="reg-sidebar-nav">
            <button class="reg-nav-item reg-nav-home" id="btnHome">
                <i class="fas fa-th-large"></i> Panel General
            </button>
            <?php foreach ($grupos as $g): ?>
            <div class="reg-nav-grupo">
                <button class="reg-nav-item reg-nav-head">
                    <i class="fas <?php echo $g['icono']; ?>"></i>
                    <span><?php echo $g['titulo']; ?></span>
                    <i class="fas fa-chevron-down reg-nav-arrow"></i>
                </button>
                <div class="reg-nav-sub">
                    <?php foreach ($g['items'] as $it): ?>
                    <button class="reg-nav-subitem" data-route="<?php echo $it['r']; ?>">
                        <span class="sub-num"><?php echo $it['n']; ?></span>
                        <span class="sub-desc"><?php echo $it['d']; ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </nav>
        <a href="index.php?route=dashboard" class="reg-sidebar-back"><i class="fas fa-chevron-left"></i> Volver al Dashboard</a>
    </div>

    <div class="reg-content" id="regContent">
        <div class="reg-dashboard" id="regDashboard">
            <div class="reg-dash-header">
                <h1><i class="fas fa-file-signature"></i> Gestión de Registros</h1>
                <p>Seleccione un formato del menú lateral para comenzar a registrar datos en el sistema SICAY. Cada formulario corresponde a un aspecto específico del control de calidad y operación.</p>
            </div>
            <div class="reg-dash-grid">
                <?php foreach ($grupos as $g): ?>
                <div class="reg-dash-card">
                    <div class="reg-dash-card-icon"><i class="fas <?php echo $g['icono']; ?>"></i></div>
                    <h3><?php echo $g['titulo']; ?></h3>
                    <ul>
                        <?php foreach ($g['items'] as $it): ?>
                        <li><strong><?php echo $it['n']; ?>:</strong> <?php echo $it['d']; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="reg-dash-footer">
                <i class="fas fa-info-circle"></i> Total de formatos disponibles: <strong>24</strong>
            </div>
        </div>
        <div class="reg-loaded" id="regLoaded">
            <div class="reg-loading" id="regLoading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalVer">
    <div class="modal-contenido modal-ver">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> Detalle del Registro</h3>
            <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
        </div>
        <div class="modal-body" id="modalVerBody"></div>
        <div class="modal-footer">
            <button type="button" class="btn-cancelar-modal" data-modal-close>Cerrar</button>
        </div>
    </div>
</div>

<script src="assets/js/registro/registros.js"></script>
</body>
</html>
