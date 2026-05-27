<?php
verificar_sesion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Archivos - SICAY</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0f1923;
            color: #e0e0e0;
            padding: 30px;
        }
        h1 { color: #00cec9; font-size: 1.6rem; margin-bottom: 6px; }
        .subtitle { color: rgba(255,255,255,0.5); margin-bottom: 30px; font-size: 0.9rem; }
        .module { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px; padding: 20px; margin-bottom: 20px; }
        .module h2 { color: white; font-size: 1.1rem; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
        .module h2 .badge { font-size: 0.6rem; background: #00cec9; color: #000; padding: 2px 10px; border-radius: 20px; font-weight: 700; }
        .module p { color: rgba(255,255,255,0.7); font-size: 0.85rem; line-height: 1.6; margin-bottom: 10px; }
        .files { background: rgba(0,0,0,0.3); border-radius: 10px; padding: 12px 16px; margin: 8px 0; }
        .files li { list-style: none; padding: 4px 0; font-size: 0.82rem; color: rgba(255,255,255,0.75); }
        .files li code { color: #00cec9; background: rgba(0,206,201,0.1); padding: 1px 6px; border-radius: 4px; font-size: 0.78rem; }
        .files li .arrow { color: rgba(255,255,255,0.3); margin: 0 6px; }
        .db { color: #fdcb6e; font-size: 0.78rem; }
        .link { color: #74b9ff; text-decoration: none; border-bottom: 1px dotted rgba(116,185,255,0.3); }
        .link:hover { border-color: #74b9ff; }
        hr { border: none; border-top: 1px solid rgba(255,255,255,0.06); margin: 16px 0; }
        .back { display: inline-block; margin-bottom: 20px; color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.85rem; }
        .back:hover { color: white; }
    </style>
</head>
<body>

<a href="index.php?route=dashboard" class="back">&larr; Volver al Dashboard</a>
<h1><i class="fas fa-sitemap"></i> Sistema de Archivos — SICAY</h1>
<p class="subtitle">Explicación de cada módulo del menú del dashboard, sus archivos, rutas y tablas que modifican.</p>

<!-- ─── NAVEGACIÓN ─── -->
<div class="module">
    <h2><i class="fas fa-route"></i> Sistema de Rutas</h2>
    <p>Todas las páginas se cargan a través de <code>routes/web.php</code>, que mapea un nombre de ruta a un archivo PHP. El parámetro <code>?route=...</code> en la URL (ej: <code>index.php?route=dashboard</code>) es leído por <code>public/index.php</code>, que busca la ruta en el array de <code>web.php</code> e incluye el archivo correspondiente.</p>
    <p>Ejemplo: <code>?route=inventario</code> &rarr; incluye <code>app/views/inventario/inventario.php</code>.</p>
</div>

<!-- ─── DASHBOARD ─── -->
<div class="module">
    <h2><i class="fas fa-home"></i> Dashboard <span class="badge">Menú principal</span></h2>
    <p>Página de inicio del sistema después del login. Muestra tarjetas con estadísticas (instalaciones, parámetros, insumos, personal, historial, soporte). Contiene el menú lateral con los 6 botones de navegación.</p>
    <div class="files">
        <ul>
            <li><i class="fas fa-file"></i> <code>app/views/dashboard/dashboard.php</code> <span class="arrow">&mdash;</span> Vista y lógica de estadísticas</li>
            <li><i class="fas fa-file-code"></i> <code>public/assets/css/dashboard/dashboard.css</code> <span class="arrow">&mdash;</span> Estilos</li>
            <li><i class="fas fa-file-code"></i> <code>public/assets/js/dashboard/dashboard.js</code> <span class="arrow">&mdash;</span> Interactividad</li>
        </ul>
    </div>
    <p class="db"><i class="fas fa-database"></i> Tablas: <code>instalacion</code>, <code>parametro</code>, <code>sustancia_quimica</code>, <code>usuario</code>, <code>historial</code> (solo lecturas COUNT)</p>
</div>

<!-- ─── REGISTROS ─── -->
<div class="module">
    <h2><i class="fas fa-file-signature"></i> Registros <span class="badge">Formularios</span></h2>
    <p>Menú que lista todos los formatos de calidad (GT-CA-001 al GT-CA-019) más formularios adicionales (Solicitud SQ, Resultados Laboratorio, Formatos Aplicación, Instalaciones). Cada tarjeta enlaza a una ruta específica.</p>
    <div class="files">
        <ul>
            <li><code>app/views/registro/registros.php</code> <span class="arrow">&mdash;</span> Página principal del menú Registros</li>
            <li><code>app/views/registro/gt_ca_001.php</code> al <code>gt_ca_019.php</code> <span class="arrow">&mdash;</span> Formularios de calidad</li>
            <li><code>app/views/registro/gt_ca_020.php</code> (ruta <code>instalaciones</code>) <span class="arrow">&mdash;</span> Registro de instalaciones</li>
            <li><code>app/views/registro/solicitud_sq.php</code> <span class="arrow">&mdash;</span> Solicitud de químicos</li>
            <li><code>app/views/registro/resultados_laboratorio.php</code> <span class="arrow">&mdash;</span> Resultados de análisis</li>
            <li><code>app/views/registro/formatos_aplicacion.php</code> <span class="arrow">&mdash;</span> Formatos de aplicación técnica</li>
            <li><code>public/assets/css/registro/registros.css</code> <span class="arrow">&mdash;</span> Estilos</li>
            <li><code>public/assets/js/registro/registros.js</code> <span class="arrow">&mdash;</span> JavaScript</li>
        </ul>
    </div>
    <p class="db"><i class="fas fa-database"></i> Tablas: Cada GT-CA modifica su propia tabla <code>gt_ca_xxx</code> y escribe en <code>historial</code>. <code>solicitud_sq</code> escribe en <code>solicitud_sq</code>. <code>instalaciones</code> modifica <code>instalacion</code>.</p>
</div>

<!-- ─── TABLAS MAESTRAS ─── -->
<div class="module">
    <h2><i class="fas fa-table"></i> Tablas Maestras <span class="badge">Configuración</span></h2>
    <p>Menú que agrupa la gestión de Plantas, Parámetros, Sustancias y Usuarios. Cada opción redirige a su propio gestor CRUD.</p>
    <div class="files">
        <ul>
            <li><code>app/views/gestion/tablas_maestras.php</code> <span class="arrow">&mdash;</span> Menú principal de tablas maestras</li>
            <li><code>app/views/gestion/gestion_plantas.php</code> <span class="arrow">&mdash;</span> CRUD de plantas/instalaciones</li>
            <li><code>app/views/gestion/gestion_parametros.php</code> <span class="arrow">&mdash;</span> CRUD de parámetros de calidad</li>
            <li><code>app/views/gestion/gestion_sustancias.php</code> <span class="arrow">&mdash;</span> CRUD de sustancias químicas</li>
            <li><code>app/views/gestion/gestion_usuarios.php</code> <span class="arrow">&mdash;</span> Perfil de usuario y administración de usuarios</li>
            <li><code>public/assets/css/gestion/</code> <span class="arrow">&mdash;</span> Estilos de cada gestor</li>
            <li><code>public/assets/js/gestion/</code> <span class="arrow">&mdash;</span> JavaScript de cada gestor</li>
        </ul>
    </div>
    <p class="db"><i class="fas fa-database"></i> Tablas: <code>instalacion</code>, <code>parametro</code>, <code>sustancia_quimica</code>, <code>usuario</code></p>
</div>

<!-- ─── REPORTES ─── -->
<div class="module">
    <h2><i class="fas fa-chart-bar"></i> Reportes <span class="badge">Consultas</span></h2>
    <p>Página de consulta y visualización de reportes del sistema. Incluye búsqueda, filtros, paginación y exportación de datos de movimientos de inventario y otros registros.</p>
    <div class="files">
        <ul>
            <li><code>app/views/reportes/reportes.php</code> <span class="arrow">&mdash;</span> Vista y lógica de reportes</li>
            <li><code>public/assets/css/reportes/reportes.css</code> <span class="arrow">&mdash;</span> Estilos</li>
            <li><code>public/assets/js/reportes/reportes.js</code> <span class="arrow">&mdash;</span> JavaScript (búsqueda, paginación AJAX)</li>
        </ul>
    </div>
    <p class="db"><i class="fas fa-database"></i> Tablas: <code>inventario_movimiento</code>, <code>sustancia_quimica</code>, <code>gt_ca_*</code> (solo lecturas)</p>
</div>

<!-- ─── INVENTARIO ─── -->
<div class="module">
    <h2><i class="fas fa-boxes"></i> Inventario <span class="badge">Almacén</span></h2>
    <p>Gestión de productos químicos (sustancias) y sus movimientos de almacén. Permite agregar/editar/eliminar productos, registrar entradas/salidas, y listar movimientos.</p>
    <div class="files">
        <ul>
            <li><code>app/views/inventario/inventario.php</code> <span class="arrow">&mdash;</span> Vista y toda la lógica CRUD + listados</li>
            <li><code>public/assets/css/inventario/inventario.css</code> <span class="arrow">&mdash;</span> Estilos</li>
            <li><code>public/assets/js/inventario/inventario.js</code> <span class="arrow">&mdash;</span> JavaScript (tabs, filtros, modales)</li>
        </ul>
    </div>
    <p class="db"><i class="fas fa-database"></i> Tablas: <code>sustancia_quimica</code> (productos), <code>inventario_movimiento</code> (registro de entradas/salidas)</p>
</div>

<!-- ─── SOPORTE ─── -->
<div class="module">
    <h2><i class="fas fa-headset"></i> Soporte <span class="badge">Ayuda</span></h2>
    <p>Página de soporte técnico y ajustes del sistema. Muestra información de contacto y enlaces a configuración adicional.</p>
    <div class="files">
        <ul>
            <li><code>app/views/soporte/soporte.php</code> <span class="arrow">&mdash;</span> Vista de soporte técnico</li>
            <li><code>app/views/soporte/ajustes.php</code> (ruta <code>ajustes</code>) <span class="arrow">&mdash;</span> Página de ajustes del sistema</li>
            <li><code>public/assets/css/soporte/soporte.css</code> <span class="arrow">&mdash;</span> Estilos</li>
            <li><code>public/assets/js/soporte/soporte.js</code> <span class="arrow">&mdash;</span> JavaScript</li>
        </ul>
    </div>
</div>

<!-- ─── HISTORIAL ─── -->
<div class="module">
    <h2><i class="fas fa-history"></i> Historial <span class="badge">Auditoría</span></h2>
    <p>Registro de auditoría del sistema. Muestra todas las acciones realizadas por los usuarios (inserciones, actualizaciones, eliminaciones) con fecha, usuario y detalle.</p>
    <div class="files">
        <ul>
            <li><code>app/views/reportes/historial.php</code> <span class="arrow">&mdash;</span> Vista del historial</li>
            <li><code>public/assets/css/reportes/historial.css</code> <span class="arrow">&mdash;</span> Estilos</li>
        </ul>
    </div>
    <p class="db"><i class="fas fa-database"></i> Tabla: <code>historial</code> (solo lectura)</p>
</div>

<!-- ─── OTROS ─── -->
<div class="module">
    <h2><i class="fas fa-cog"></i> Archivos del Sistema</h2>
    <p>Archivos que no aparecen en el menú del dashboard pero son parte fundamental del sistema:</p>
    <div class="files">
        <ul>
            <li><code>public/index.php</code> <span class="arrow">&mdash;</span> Punto de entrada único (Front Controller), lee <code>?route=</code> y carga el archivo desde <code>routes/web.php</code></li>
            <li><code>routes/web.php</code> <span class="arrow">&mdash;</span> Array de enrutamiento: nombre_de_ruta &rarr; archivo PHP</li>
            <li><code>app/Controllers/iniciar.php</code> <span class="arrow">&mdash;</span> Controlador de login (valida credenciales, inicia sesión)</li>
            <li><code>app/Controllers/registrar.php</code> <span class="arrow">&mdash;</span> Controlador de registro de usuarios</li>
            <li><code>app/Controllers/forgot_password.php</code> <span class="arrow">&mdash;</span> Envío de correo para recuperar contraseña</li>
            <li><code>app/Controllers/enviar_reset.php</code> <span class="arrow">&mdash;</span> Procesa el reseteo de contraseña</li>
            <li><code>app/Controllers/api_profile.php</code> <span class="arrow">&mdash;</span> API para actualizar perfil de usuario (AJAX)</li>
            <li><code>app/Controllers/api_notifications.php</code> <span class="arrow">&mdash;</span> API para notificaciones (AJAX)</li>
            <li><code>app/Controllers/api_historial.php</code> <span class="arrow">&mdash;</span> API para historial (AJAX)</li>
            <li><code>app/views/inicio/login.php</code> <span class="arrow">&mdash;</span> Página de inicio de sesión</li>
            <li><code>app/views/inicio/registro.php</code> <span class="arrow">&mdash;</span> Página de registro de usuarios</li>
            <li><code>app/views/inicio/olvide_pass.php</code> <span class="arrow">&mdash;</span> Formulario "olvidé contraseña"</li>
            <li><code>app/views/inicio/reset_password.php</code> <span class="arrow">&mdash;</span> Formulario para nueva contraseña</li>
            <li><code>app/views/seguridad/seguridad.php</code> <span class="arrow">&mdash;</span> Página de seguridad</li>
            <li><code>app/views/seguridad/respaldo.php</code> <span class="arrow">&mdash;</span> Copias de seguridad</li>
            <li><code>app/views/seguridad/documentacion_seguridad.php</code> <span class="arrow">&mdash;</span> Documentación de seguridad</li>
            <li><code>app/views/salir/salir.php</code> <span class="arrow">&mdash;</span> Cierre de sesión</li>
        </ul>
    </div>
</div>

</body>
</html>
