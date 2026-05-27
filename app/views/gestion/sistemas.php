<?php
/**
 * sistemas.php — Página de documentación técnica del sistema SICAY
 *
 * Propósito:
 *   Esta página explica la arquitectura del sistema: qué hace cada módulo
 *   del menú del dashboard, qué archivos lo componen, cómo están vinculados
 *   a través del sistema de rutas (routes/web.php) y qué tablas de la base
 *   de datos modifican.
 *
 * Vinculación:
 *   - Definida en routes/web.php con la ruta 'sistemas'
 *   - Accesible desde index.php?route=sistemas
 *   - Enlazada desde el sidebar de gestion_usuarios.php
 *   - Requiere sesión iniciada (verificar_sesion())
 *
 * Estructura:
 *   - Sidebar lateral fijo con botones de navegación (10 secciones)
 *   - Área de contenido que muestra/oculta paneles mediante tabs (JS)
 *   - Los estilos están en public/assets/css/gestion/sistemas.css
 *   - La interactividad está en public/assets/js/gestion/sistemas.js
 *
 * Cada sección documenta un módulo del dashboard e incluye:
 *   - Descripción general del módulo
 *   - Lista de archivos PHP/CSS/JS que lo componen
 *   - Rutas asociadas (desde web.php)
 *   - Tablas de base de datos que modifica o consulta
 */

verificar_sesion(); // Redirige al login si no hay sesión activa
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Archivos - SICAY</title>
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <!-- Estilos propios de la página (separados del HTML) -->
    <link rel="stylesheet" href="assets/css/gestion/sistemas.css">
</head>
<body>

<!--
  ═══════════════════════════════════════════════════════════
  SIDEBAR
  Contiene:
    - Cabecera con el título "Sistemas" y botón "Volver"
    - Navegación vertical con botones para cada sección
  ═══════════════════════════════════════════════════════════
  Cada botón tiene un atributo data-tab que corresponde al id
  del div.tab-panel que debe mostrar. El JavaScript en
  sistemas.js se encarga del cambio de pestañas.
-->
<aside class="sys-sidebar">
    <div class="sys-sidebar-header">
        <h2><i class="fas fa-sitemap"></i> Sistemas</h2>
        <a href="index.php?route=gestion_usuarios" class="back">
            <i class="fas fa-chevron-left"></i> Volver
        </a>
    </div>
    <nav class="sys-nav">
        <!--
          Cada sys-nav-btn tiene data-tab="tab-xxx".
          Al hacer clic, el JS oculta todos los .tab-panel
          y muestra solo el que tiene id = tab-xxx.
        -->
        <button class="sys-nav-btn active" data-tab="tab-navegacion">
            <i class="fas fa-route"></i> Rutas
        </button>
        <button class="sys-nav-btn" data-tab="tab-dashboard">
            <i class="fas fa-home"></i> Dashboard
        </button>
        <button class="sys-nav-btn" data-tab="tab-registros">
            <i class="fas fa-file-signature"></i> Registros
        </button>
        <button class="sys-nav-btn" data-tab="tab-gestion_usuarios">
            <i class="fas fa-user-cog"></i> Gestión Usuarios
        </button>
        <button class="sys-nav-btn" data-tab="tab-reportes">
            <i class="fas fa-chart-bar"></i> Reportes
        </button>
        <button class="sys-nav-btn" data-tab="tab-inventario">
            <i class="fas fa-boxes"></i> Inventario
        </button>
        <button class="sys-nav-btn" data-tab="tab-soporte">
            <i class="fas fa-headset"></i> Soporte
        </button>
        <button class="sys-nav-btn" data-tab="tab-historial">
            <i class="fas fa-history"></i> Historial
        </button>
        <hr class="sys-nav-divider">
        <button class="sys-nav-btn" data-tab="tab-otros">
            <i class="fas fa-cog"></i> Archivos Sistema
        </button>
    </nav>
</aside>

<!--
  ═══════════════════════════════════════════════════════════
  CONTENIDO PRINCIPAL
  Cada sección es un div.tab-panel con un id único.
  Solo el panel con clase "active" es visible.
  ═══════════════════════════════════════════════════════════
-->
<main class="sys-content">

    <!-- Título global de la página (visible siempre arriba) -->
    <h1><i class="fas fa-sitemap"></i> Sistema de Archivos — SICAY</h1>
    <p class="subtitle">
        Explicación de cada módulo del menú del dashboard, sus archivos,
        rutas y tablas que modifican.
    </p>

    <!-- ────────────────────────────────────────────
         SECCIÓN: SISTEMA DE RUTAS
         Describe cómo funciona el enrutamiento: el
         Front Controller (index.php) lee ?route= y
         busca en web.php el archivo a incluir.
         ──────────────────────────────────────────── -->
    <div id="tab-navegacion" class="tab-panel active">
        <div class="module">
            <h2><i class="fas fa-route"></i> Sistema de Rutas</h2>
            <p>
                Todas las páginas se cargan a través de <code>routes/web.php</code>,
                que mapea un nombre de ruta a un archivo PHP. El parámetro
                <code>?route=...</code> en la URL (ej: <code>index.php?route=dashboard</code>)
                es leído por <code>public/index.php</code>, que busca la ruta en el
                array de <code>web.php</code> e incluye el archivo correspondiente.
            </p>
            <p>
                Ejemplo: <code>?route=inventario</code> &rarr; incluye
                <code>app/views/inventario/inventario.php</code>.
            </p>
        </div>
    </div>

    <!-- ────────────────────────────────────────────
         SECCIÓN: DASHBOARD
         Página de inicio con tarjetas de estadísticas
         y el menú lateral de navegación principal.
         ──────────────────────────────────────────── -->
    <div id="tab-dashboard" class="tab-panel">
        <div class="module">
            <h2><i class="fas fa-home"></i> Dashboard <span class="badge">Menú principal</span></h2>
            <p>
                Página de inicio del sistema después del login. Muestra tarjetas
                con estadísticas (instalaciones, parámetros, insumos, personal,
                historial, soporte). Contiene el menú lateral con los 6 botones
                de navegación.
            </p>
            <div class="files">
                <ul>
                    <li><i class="fas fa-file"></i> <code>app/views/dashboard/dashboard.php</code> <span class="arrow">&mdash;</span> Vista y lógica de estadísticas</li>
                    <li><i class="fas fa-file-code"></i> <code>public/assets/css/dashboard/dashboard.css</code> <span class="arrow">&mdash;</span> Estilos</li>
                    <li><i class="fas fa-file-code"></i> <code>public/assets/js/dashboard/dashboard.js</code> <span class="arrow">&mdash;</span> Interactividad</li>
                </ul>
            </div>
            <p class="db"><i class="fas fa-database"></i> Tablas: <code>instalacion</code>, <code>parametro</code>, <code>sustancia_quimica</code>, <code>usuario</code>, <code>historial</code> (solo lecturas COUNT)</p>
        </div>
    </div>

    <!-- ────────────────────────────────────────────
         SECCIÓN: REGISTROS
         Menú de formularios de calidad y registros
         operativos del sistema.
         ──────────────────────────────────────────── -->
    <div id="tab-registros" class="tab-panel">
        <div class="module">
            <h2><i class="fas fa-file-signature"></i> Registros <span class="badge">Formularios</span></h2>
            <p>
                Menú que lista todos los formatos de calidad (GT-CA-001 al GT-CA-019)
                más formularios adicionales (Solicitud SQ, Resultados Laboratorio,
                Formatos Aplicación, Instalaciones). Cada tarjeta enlaza a una ruta
                específica definida en <code>web.php</code>.
            </p>
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
    </div>

    <!-- ────────────────────────────────────────────
         SECCIÓN: GESTIÓN DE DATOS
         Plantas, sustancias y demás datos maestros.
         ──────────────────────────────────────────── -->
    <div id="tab-tablas_maestras" class="tab-panel">
        <div class="module">
            <h2><i class="fas fa-database"></i> Gestión de Datos <span class="badge">Configuración</span></h2>
            <p>
                Páginas de gestión de datos maestros del sistema.
                <strong>Parámetros</strong> se encuentra ahora en
                <a href="index.php?route=registros">Registros</a>.
            </p>
            <div class="files">
                <ul>
                    <li><code>app/views/gestion/gestion_plantas.php</code> <span class="arrow">&mdash;</span> CRUD de plantas/instalaciones</li>
                    <li><code>app/views/gestion/gestion_sustancias.php</code> <span class="arrow">&mdash;</span> CRUD de sustancias químicas</li>
                    <li><code>app/views/gestion/gestion_parametros.php</code> <span class="arrow">&mdash;</span> CRUD de parámetros (accesible desde Registros)</li>
                    <li><code>public/assets/css/gestion/</code> <span class="arrow">&mdash;</span> Estilos de cada gestor</li>
                    <li><code>public/assets/js/gestion/</code> <span class="arrow">&mdash;</span> JavaScript de cada gestor</li>
                </ul>
            </div>
            <p class="db"><i class="fas fa-database"></i> Tablas: <code>instalacion</code>, <code>parametro</code>, <code>sustancia_quimica</code></p>
        </div>
    </div>

    <!-- ────────────────────────────────────────────
         SECCIÓN: GESTIÓN DE USUARIOS
         Perfil del usuario y panel de administración.
         Es la página más compleja del sistema.
         ──────────────────────────────────────────── -->
    <div id="tab-gestion_usuarios" class="tab-panel">
        <div class="module">
            <h2><i class="fas fa-user-cog"></i> Gestión de Usuarios <span class="badge">Perfil + Admin</span></h2>
            <p>
                Página de perfil de usuario y administración del sistema.
                Organizada en pestañas laterales y solo accesible con sesión
                iniciada (<code>verificar_sesion()</code>).
            </p>
            <div class="files">
                <ul>
                    <li><code>app/views/gestion/gestion_usuarios.php</code> <span class="arrow">&mdash;</span> Vista principal con toda la lógica de perfil y admin</li>
                    <li><code>public/assets/css/gestion/gestion_usuarios.css</code> <span class="arrow">&mdash;</span> Estilos</li>
                    <li><code>public/assets/js/gestion/gestion_usuarios.js</code> <span class="arrow">&mdash;</span> JavaScript (tabs, AJAX, avatar, notificaciones)</li>
                    <li><code>public/assets/js/cropper.min.js</code> <span class="arrow">&mdash;</span> Librería para recorte de avatar</li>
                </ul>
            </div>
            <p><strong>Pestañas del perfil:</strong></p>
            <div class="files">
                <ul>
                    <li><i class="fas fa-user"></i> <strong>Perfil</strong> <span class="arrow">&mdash;</span> Datos personales (nombre, usuario, correo), avatar con recorte (<code>cropper.js</code>), carga vía AJAX a <code>api_profile.php</code></li>
                    <li><i class="fas fa-shield-alt"></i> <strong>Seguridad</strong> <span class="arrow">&mdash;</span> Cambio de contraseña, 2FA (toggle), sesiones activas, cierre de otras sesiones</li>
                    <li><i class="fas fa-cog"></i> <strong>Configuración</strong> <span class="arrow">&mdash;</span> Tema claro/oscuro, idioma (es/en), color de acento, notificaciones (correo/sistema), privacidad de perfil. Guarda en <code>usuario</code> vía AJAX</li>
                    <li><i class="fas fa-chart-line"></i> <strong>Actividad</strong> <span class="arrow">&mdash;</span> Estadísticas y listado de actividad del usuario desde <code>activity_log</code>, cargado vía AJAX</li>
                    <li><i class="fas fa-user-shield"></i> <strong>Administración</strong> <span class="arrow">&mdash;</span> Solo visible para rol <code>Admin</code>. Sub-pestañas: usuarios (CRUD, cambio de rol, notificaciones), actividad global, configuración global (backup &rarr; <code>respaldo.php</code>, modo mantenimiento, especificaciones del sistema)</li>
                </ul>
            </div>
            <p><strong>Sidebar:</strong> Contiene acceso directo a <code>Documentación de Seguridad</code> (<code>?route=seguridad_documentacion</code>), <code>Sistemas</code> (<code>?route=sistemas</code>) y cierre de sesión.</p>
            <p class="db"><i class="fas fa-database"></i> Tablas: <code>usuario</code> (perfil, avatar, tema, idioma, 2FA, notificaciones), <code>activity_log</code> (actividad), <code>user_sessions</code> (sesiones activas), <code>system_config</code> (configuración global), <code>notifications</code></p>
        </div>
    </div>

    <!-- ────────────────────────────────────────────
         SECCIÓN: REPORTES
         Consulta y visualización de reportes con
         búsqueda, filtros, paginación y exportación.
         ──────────────────────────────────────────── -->
    <div id="tab-reportes" class="tab-panel">
        <div class="module">
            <h2><i class="fas fa-chart-bar"></i> Reportes <span class="badge">Consultas</span></h2>
            <p>
                Página de consulta y visualización de reportes del sistema.
                Incluye búsqueda, filtros, paginación y exportación de datos
                de movimientos de inventario y otros registros.
            </p>
            <div class="files">
                <ul>
                    <li><code>app/views/reportes/reportes.php</code> <span class="arrow">&mdash;</span> Vista y lógica de reportes</li>
                    <li><code>public/assets/css/reportes/reportes.css</code> <span class="arrow">&mdash;</span> Estilos</li>
                    <li><code>public/assets/js/reportes/reportes.js</code> <span class="arrow">&mdash;</span> JavaScript (búsqueda, paginación AJAX)</li>
                </ul>
            </div>
            <p class="db"><i class="fas fa-database"></i> Tablas: <code>inventario_movimiento</code>, <code>sustancia_quimica</code>, <code>gt_ca_*</code> (solo lecturas)</p>
        </div>
    </div>

    <!-- ────────────────────────────────────────────
         SECCIÓN: INVENTARIO
         Gestión de productos químicos y movimientos
         de almacén (entradas, salidas, ajustes).
         ──────────────────────────────────────────── -->
    <div id="tab-inventario" class="tab-panel">
        <div class="module">
            <h2><i class="fas fa-boxes"></i> Inventario <span class="badge">Almacén</span></h2>
            <p>
                Gestión de productos químicos (sustancias) y sus movimientos
                de almacén. Permite agregar/editar/eliminar productos, registrar
                entradas/salidas, y listar movimientos.
            </p>
            <div class="files">
                <ul>
                    <li><code>app/views/inventario/inventario.php</code> <span class="arrow">&mdash;</span> Vista y toda la lógica CRUD + listados</li>
                    <li><code>public/assets/css/inventario/inventario.css</code> <span class="arrow">&mdash;</span> Estilos</li>
                    <li><code>public/assets/js/inventario/inventario.js</code> <span class="arrow">&mdash;</span> JavaScript (tabs, filtros, modales)</li>
                </ul>
            </div>
            <p class="db"><i class="fas fa-database"></i> Tablas: <code>sustancia_quimica</code> (productos), <code>inventario_movimiento</code> (registro de entradas/salidas)</p>
        </div>
    </div>

    <!-- ────────────────────────────────────────────
         SECCIÓN: SOPORTE
         Página de soporte técnico y ajustes del sistema.
         ──────────────────────────────────────────── -->
    <div id="tab-soporte" class="tab-panel">
        <div class="module">
            <h2><i class="fas fa-headset"></i> Soporte <span class="badge">Ayuda</span></h2>
            <p>
                Página de soporte técnico y ajustes del sistema. Muestra
                información de contacto y enlaces a configuración adicional.
            </p>
            <div class="files">
                <ul>
                    <li><code>app/views/soporte/soporte.php</code> <span class="arrow">&mdash;</span> Vista de soporte técnico</li>
                    <li><code>app/views/soporte/ajustes.php</code> (ruta <code>ajustes</code>) <span class="arrow">&mdash;</span> Página de ajustes del sistema</li>
                    <li><code>public/assets/css/soporte/soporte.css</code> <span class="arrow">&mdash;</span> Estilos</li>
                    <li><code>public/assets/js/soporte/soporte.js</code> <span class="arrow">&mdash;</span> JavaScript</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ────────────────────────────────────────────
         SECCIÓN: HISTORIAL
         Registro de auditoría del sistema con todas
         las acciones de los usuarios.
         ──────────────────────────────────────────── -->
    <div id="tab-historial" class="tab-panel">
        <div class="module">
            <h2><i class="fas fa-history"></i> Historial <span class="badge">Auditoría</span></h2>
            <p>
                Registro de auditoría del sistema. Muestra todas las acciones
                realizadas por los usuarios (inserciones, actualizaciones,
                eliminaciones) con fecha, usuario y detalle.
            </p>
            <div class="files">
                <ul>
                    <li><code>app/views/reportes/historial.php</code> <span class="arrow">&mdash;</span> Vista del historial</li>
                    <li><code>public/assets/css/reportes/historial.css</code> <span class="arrow">&mdash;</span> Estilos</li>
                </ul>
            </div>
            <p class="db"><i class="fas fa-database"></i> Tabla: <code>historial</code> (solo lectura)</p>
        </div>
    </div>

    <!-- ────────────────────────────────────────────
         SECCIÓN: ARCHIVOS DEL SISTEMA
         Lista de archivos importantes que no aparecen
         en el menú del dashboard pero son esenciales.
         ──────────────────────────────────────────── -->
    <div id="tab-otros" class="tab-panel">
        <div class="module">
            <h2><i class="fas fa-cog"></i> Archivos del Sistema</h2>
            <p>
                Archivos que no aparecen en el menú del dashboard pero son
                parte fundamental del sistema:
            </p>
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
    </div>

</main>

<!-- JavaScript para el cambio de pestañas (separado del HTML) -->
<script src="assets/js/gestion/sistemas.js"></script>
</body>
</html>
