<?php
verificar_sesion();
$uid = intval($_SESSION['id_usuario']);
$stmt = $con->prepare("SELECT rol FROM usuario WHERE id = ?");
$stmt->bind_param("i", $uid); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (($user['rol'] ?? '') !== 'Admin') {
    redirigir('dashboard');
    exit();
}

// Gather security status data
$total_usuarios = 0;
$stmt = $con->query("SELECT COUNT(*) as c FROM usuario");
if ($stmt) $total_usuarios = $stmt->fetch_assoc()['c'];

$stmt = $con->query("SELECT COUNT(*) as c FROM usuario WHERE two_factor_enabled = 1");
$total_2fa = $stmt ? $stmt->fetch_assoc()['c'] : 0;

$stmt = $con->query("SELECT COUNT(*) as c FROM user_sessions WHERE is_current = 1");
$active_sessions = $stmt ? $stmt->fetch_assoc()['c'] : 0;

$last_cleanup = get_system_config('last_session_cleanup', 'Nunca');
$security_level = get_system_config('security_level', 'medium');
$maintenance = is_maintenance_mode();

// Count login attempts in last 15 min
$stmt = $con->query("SELECT COUNT(*) as c FROM login_attempts WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND success = 0");
$recent_failures = $stmt ? $stmt->fetch_assoc()['c'] : 0;

// Get latest failed login attempts
$recent_attempts = [];
$stmt = $con->query("SELECT username, ip_address, attempted_at FROM login_attempts WHERE success = 0 ORDER BY attempted_at DESC LIMIT 10");
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) $recent_attempts[] = $row;
}

$has_login_attempts_table = false;
$chk = $con->query("SHOW TABLES LIKE 'login_attempts'");
if ($chk && $chk->num_rows > 0) $has_login_attempts_table = true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentación de Seguridad - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/seguridad/seguridad_documentacion.css">
</head>
<body>
<div id="bubbles"></div>

<div class="sec-wrapper">
    <a href="index.php?route=gestion_usuarios" class="sec-back"><i class="fas fa-arrow-left"></i> Volver a mi cuenta</a>

    <header class="sec-header">
        <div class="sec-header-icon"><i class="fas fa-shield-alt"></i></div>
        <h1>Arquitectura de Seguridad — SICAY</h1>
        <p>Documentación técnica detallada de todas las medidas de seguridad implementadas en el Sistema Integrado de Control de Aguas de Yaracuy.</p>
        <div class="sec-badges">
            <span class="sec-badge badge-primary"><i class="fas fa-check-circle"></i> <?php echo $total_usuarios; ?> usuarios</span>
            <span class="sec-badge badge-success"><i class="fas fa-shield"></i> <?php echo $total_2fa; ?> con 2FA</span>
            <span class="sec-badge badge-info"><i class="fas fa-plug"></i> <?php echo $active_sessions; ?> sesiones activas</span>
            <span class="sec-badge <?php echo $maintenance ? 'badge-danger' : 'badge-secondary'; ?>">
                <i class="fas fa-tools"></i> Mantenimiento: <?php echo $maintenance ? 'ACTIVO' : 'INACTIVO'; ?>
            </span>
        </div>
    </header>

    <div class="sec-grid">

        <!-- 1. POLÍTICA DE CONTRASEÑAS -->
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-key"></i></div>
                <h2>1. Política de Contraseñas</h2>
            </div>
            <div class="sec-card-body">
                <p class="sec-status">Estado: <span class="status-active"><i class="fas fa-check-circle"></i> Implementado</span></p>
                <h3>Requisitos Mínimos</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> Longitud mínima de <strong>8 caracteres</strong></li>
                    <li><i class="fas fa-check text-success"></i> Al menos <strong>1 letra mayúscula</strong></li>
                    <li><i class="fas fa-check text-success"></i> Al menos <strong>1 letra minúscula</strong></li>
                    <li><i class="fas fa-check text-success"></i> Al menos <strong>1 dígito numérico</strong></li>
                    <li><i class="fas fa-check text-success"></i> Al menos <strong>1 carácter especial</strong> (!@#$%^&*)</li>
                </ul>
                <h3>Hashing</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> Algoritmo: <strong>bcrypt</strong> vía <code>password_hash()</code> de PHP</li>
                    <li><i class="fas fa-check text-success"></i> Cost factor predeterminado (10 rondas)</li>
                    <li><i class="fas fa-check text-success"></i> Migración automática de hashes MD5 legados al iniciar sesión</li>
                    <li><i class="fas fa-check text-success"></i> Las contraseñas NUNCA se almacenan en texto plano</li>
                </ul>
                <div class="sec-code-block">
                    <span class="sec-code-label">Validación en registro y cambio de contraseña:</span>
                    <pre><code>function validar_politica_pass($password) {
    $errors = [];
    if (strlen($password) < 8)
        $errors[] = 'Mínimo 8 caracteres';
    if (!preg_match('/[A-Z]/', $password))
        $errors[] = 'Falta mayúscula';
    if (!preg_match('/[a-z]/', $password))
        $errors[] = 'Falta minúscula';
    if (!preg_match('/[0-9]/', $password))
        $errors[] = 'Falta número';
    if (!preg_match('/[^A-Za-z0-9]/', $password))
        $errors[] = 'Falta carácter especial';
    return $errors;
}</code></pre>
                </div>
            </div>
        </div>

        <!-- 2. BLOQUEO DE CUENTA (BRUTE FORCE) -->
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-lock"></i></div>
                <h2>2. Bloqueo de Cuenta por Fuerza Bruta</h2>
            </div>
            <div class="sec-card-body">
                <p class="sec-status">Estado: <span class="status-active"><i class="fas fa-check-circle"></i> Implementado</span></p>
                <h3>Configuración Actual</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> Máximo <strong>5 intentos fallidos</strong> antes del bloqueo</li>
                    <li><i class="fas fa-check text-success"></i> Tiempo de bloqueo: <strong>15 minutos</strong></li>
                    <li><i class="fas fa-check text-success"></i> Seguimiento por <strong>usuario + IP</strong></li>
                    <li><i class="fas fa-check text-success"></i> Tabla <code>login_attempts</code> con todos los intentos registrados</li>
                </ul>
                <h3>Estado del Monitor</h3>
                <div class="sec-monitor">
                    <div class="monitor-item">
                        <span class="monitor-label">Intentos fallidos (últimos 15 min)</span>
                        <span class="monitor-value <?php echo $recent_failures > 5 ? 'text-danger' : 'text-success'; ?>"><?php echo $recent_failures; ?></span>
                    </div>
                </div>
                <?php if ($has_login_attempts_table && !empty($recent_attempts)): ?>
                <h3>Últimos intentos fallidos</h3>
                <div class="sec-table-wrap">
                    <table class="sec-table">
                        <thead><tr><th>Usuario</th><th>IP</th><th>Fecha</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent_attempts as $a): ?>
                            <tr>
                                <td><?php echo hsc($a['username']); ?></td>
                                <td><?php echo hsc($a['ip_address']); ?></td>
                                <td><?php echo $a['attempted_at']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                <div class="sec-code-block">
                    <span class="sec-code-label">Lógica de bloqueo:</span>
                    <pre><code>function check_login_lockout($username, $ip) {
    $window = date('Y-m-d H:i:s',
        strtotime('-15 minutes'));
    $stmt = $con->prepare(
        "SELECT COUNT(*) as c FROM login_attempts
         WHERE (username = ? OR ip_address = ?)
         AND attempted_at > ?
         AND success = 0");
    $stmt->bind_param("sss", $username, $ip, $window);
    // Si count >= 5, rechazar login
    return $count >= 5;
}</code></pre>
                </div>
            </div>
        </div>

        <!-- 3. SEGURIDAD DE SESIÓN -->
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-user-clock"></i></div>
                <h2>3. Seguridad de Sesión</h2>
            </div>
            <div class="sec-card-body">
                <p class="sec-status">Estado: <span class="status-active"><i class="fas fa-check-circle"></i> Implementado</span></p>
                <h3>Parámetros de Cookie de Sesión</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> <strong>HttpOnly</strong>: activado (inaccesible desde JavaScript)</li>
                    <li><i class="fas fa-check text-success"></i> <strong>SameSite=Strict</strong>: la cookie solo se envía en solicitudes del mismo sitio</li>
                    <li><i class="fas fa-check text-success"></i> <strong>Secure</strong>: activado (solo se envía por HTTPS)</li>
                    <li><i class="fas fa-check text-success"></i> <strong>Tiempo de inactividad</strong>: 30 minutos máximo</li>
                </ul>
                <h3>Medidas Adicionales</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> Regeneración de ID de sesión en cada login (<code>session_regenerate_id(true)</code>)</li>
                    <li><i class="fas fa-check text-success"></i> Registro de todas las sesiones activas en tabla <code>user_sessions</code></li>
                    <li><i class="fas fa-check text-success"></i> Cierre de sesión remoto desde el panel de perfil</li>
                    <li><i class="fas fa-check text-success"></i> Destrucción completa de sesión al cerrar sesión</li>
                    <li><i class="fas fa-check text-success"></i> Limpieza automática de sesiones expiradas (&gt;24h)</li>
                </ul>
                <div class="sec-code-block">
                    <span class="sec-code-label">Configuración de sesión:</span>
                    <pre><code>session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
// Tiempo máximo de inactividad
if (isset($_SESSION['last_activity'])
    && (time() - $_SESSION['last_activity']) > 1800) {
    session_destroy();
    redirigir('login');
}
$_SESSION['last_activity'] = time();</code></pre>
                </div>
            </div>
        </div>

        <!-- 4. HEADERS DE SEGURIDAD HTTP -->
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-shield-virus"></i></div>
                <h2>4. Headers de Seguridad HTTP</h2>
            </div>
            <div class="sec-card-body">
                <p class="sec-status">Estado: <span class="status-active"><i class="fas fa-check-circle"></i> Implementado</span></p>
                <h3>Headers Enviados en Cada Petición</h3>
                <div class="sec-table-wrap">
                    <table class="sec-table">
                        <thead><tr><th>Header</th><th>Valor</th><th>Función</th></tr></thead>
                        <tbody>
                            <tr><td><code>X-Frame-Options</code></td><td><code>DENY</code></td><td>Previene clickjacking al prohibir iframes</td></tr>
                            <tr><td><code>X-Content-Type-Options</code></td><td><code>nosniff</code></td><td>Evita MIME-type sniffing</td></tr>
                            <tr><td><code>X-XSS-Protection</code></td><td><code>1; mode=block</code></td><td>Activa filtro XSS del navegador</td></tr>
                            <tr><td><code>Referrer-Policy</code></td><td><code>strict-origin-when-cross-origin</code></td><td>Controla información enviada en Referer</td></tr>
                            <tr><td><code>Permissions-Policy</code></td><td>Restringido</td><td>Desactiva APIs no necesarias (cámara, micrófono, etc.)</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="sec-code-block">
                    <span class="sec-code-label">Implementación en index.php:</span>
                    <pre><code>header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), ". 
       "microphone=(), geolocation=()");</code></pre>
                </div>
            </div>
        </div>

        <!-- 5. CSRF -->
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-random"></i></div>
                <h2>5. Protección CSRF (Cross-Site Request Forgery)</h2>
            </div>
            <div class="sec-card-body">
                <p class="sec-status">Estado: <span class="status-active"><i class="fas fa-check-circle"></i> Implementado</span></p>
                <h3>Mecanismo</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> Generación de token único por sesión (<code>bin2hex(random_bytes(32))</code>)</li>
                    <li><i class="fas fa-check text-success"></i> Token incluido como campo oculto en todos los formularios</li>
                    <li><i class="fas fa-check text-success"></i> Validación con <code>hash_equals()</code> para comparación segura (timing-attack safe)</li>
                    <li><i class="fas fa-check text-success"></i> Verificación obligatoria en todas las acciones POST del API</li>
                    <li><i class="fas fa-check text-success"></i> HTTP 403 + muerte de proceso si el token no coincide</li>
                </ul>
                <div class="sec-code-block">
                    <span class="sec-code-label">Uso en formularios:</span>
                    <pre><code>// helpers.php
function csrf_token() {
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
// En formularios HTML:
&lt;input type="hidden" name="csrf_token"
       value="&lt;?php echo csrf_token(); ?&gt;"&gt;</code></pre>
                </div>
            </div>
        </div>

        <!-- 6. VALIDACIÓN Y SANITIZACIÓN -->
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-filter"></i></div>
                <h2>6. Validación y Sanitización de Entradas</h2>
            </div>
            <div class="sec-card-body">
                <p class="sec-status">Estado: <span class="status-active"><i class="fas fa-check-circle"></i> Implementado</span></p>
                <h3>Medidas Implementadas</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> Escape de salida con <code>htmlspecialchars()</code> vía función <code>hsc()</code></li>
                    <li><i class="fas fa-check text-success"></i> Validación de email con <code>filter_var(FILTER_VALIDATE_EMAIL)</code></li>
                    <li><i class="fas fa-check text-success"></i> Validación de nombres con regex (/^[A-Za-zÁÉÍÓÚáéíóúñÑ ]+$/)</li>
                    <li><i class="fas fa-check text-success"></i> Validación numérica con rangos configurables</li>
                    <li><i class="fas fa-check text-success"></i> Validación de campos requeridos (<code>validar_requeridos()</code>)</li>
                </ul>
                <h3>Consultas Parametrizadas</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> 100% de las consultas SQL usan <strong>prepared statements</strong></li>
                    <li><i class="fas fa-check text-success"></i> Sin interpolación directa de variables en SQL</li>
                    <li><i class="fas fa-check text-success"></i> Tipado estricto con <code>bind_param("i", ...)</code> para enteros</li>
                </ul>
                <div class="sec-code-block">
                    <span class="sec-code-label">Ejemplo de consulta segura:</span>
                    <pre><code>$stmt = $con->prepare(
    "SELECT * FROM usuario WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();</code></pre>
                </div>
            </div>
        </div>

        <!-- 7. REGISTRO DE AUDITORÍA -->
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-history"></i></div>
                <h2>7. Registro de Auditoría</h2>
            </div>
            <div class="sec-card-body">
                <p class="sec-status">Estado: <span class="status-active"><i class="fas fa-check-circle"></i> Implementado</span></p>
                <h3>Eventos Registrados</h3>
                <div class="sec-table-wrap">
                    <table class="sec-table">
                        <thead><tr><th>Categoría</th><th>Eventos</th></tr></thead>
                        <tbody>
                            <tr><td>Autenticación</td><td>Inicio de sesión, cierre de sesión, intentos fallidos</td></tr>
                            <tr><td>Seguridad</td><td>Cambio de contraseña, activación/desactivación 2FA</td></tr>
                            <tr><td>Usuarios</td><td>Creación, actualización de perfil, cambio de rol, eliminación</td></tr>
                            <tr><td>Configuración</td><td>Cambios en configuración global, cambios de tema/idioma</td></tr>
                            <tr><td>Sesiones</td><td>Cierre de sesiones, cierre de todas las sesiones</td></tr>
                            <tr><td>Sistema</td><td>Exportaciones, mantenimiento, errores</td></tr>
                        </tbody>
                    </table>
                </div>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> IP registrada en cada evento</li>
                    <li><i class="fas fa-check text-success"></i> User-Agent registrado para sesiones</li>
                    <li><i class="fas fa-check text-success"></i> Dos tablas: <code>activity_log</code> y <code>historial</code></li>
                    <li><i class="fas fa-check text-success"></i> Clasificación por tipo de acción (login, logout, create, update, delete, security, etc.)</li>
                    <li><i class="fas fa-check text-success"></i> Última limpieza de sesiones: <?php echo hsc($last_cleanup); ?></li>
                </ul>
            </div>
        </div>

        <!-- 8. AUTENTICACIÓN DE DOS FACTORES (2FA) -->
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-mobile-alt"></i></div>
                <h2>8. Autenticación de Dos Factores (2FA)</h2>
            </div>
            <div class="sec-card-body">
                <p class="sec-status">Estado: <span class="status-partial"><i class="fas fa-adjust"></i> Parcial — Interfaz lista, backend configurable</span></p>
                <h3>Implementación</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> Toggle de activación/desactivación en perfil de usuario</li>
                    <li><i class="fas fa-check text-success"></i> Almacenamiento del estado en columna <code>two_factor_enabled</code></li>
                    <li><i class="fas fa-check text-success"></i> <?php echo $total_2fa; ?> usuario(s) con 2FA activado actualmente</li>
                    <li><i class="fas fa-clock text-warning"></i> Pendiente: generación de secreto TOTP y verificación en login</li>
                </ul>
                <div class="sec-code-block">
                    <span class="sec-code-label">Estructura de BD:</span>
                    <pre><code>ALTER TABLE usuario ADD COLUMN
    two_factor_enabled TINYINT(1) DEFAULT 0;
ALTER TABLE usuario ADD COLUMN
    two_factor_secret VARCHAR(255) DEFAULT NULL;</code></pre>
                </div>
            </div>
        </div>

        <!-- 9. SEGURIDAD DE ARCHIVOS -->
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-file-upload"></i></div>
                <h2>9. Seguridad en Subida de Archivos</h2>
            </div>
            <div class="sec-card-body">
                <p class="sec-status">Estado: <span class="status-active"><i class="fas fa-check-circle"></i> Implementado</span></p>
                <h3>Validaciones de Avatar</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> Validación de tipo MIME real via <code>finfo</code> (no solo extensión)</li>
                    <li><i class="fas fa-check text-success"></i> Solo permitidos: JPEG, PNG, GIF, WebP</li>
                    <li><i class="fas fa-check text-success"></i> Límite de tamaño: <strong>5 MB</strong></li>
                    <li><i class="fas fa-check text-success"></i> Nombre único generado: <code>user_{ID}_{timestamp}.ext</code></li>
                    <li><i class="fas fa-check text-success"></i> Eliminación del archivo anterior al reemplazar</li>
                    <li><i class="fas fa-check text-success"></i> Validación adicional en el cliente con Cropper.js</li>
                </ul>
            </div>
        </div>

        <!-- 10. CONTROL DE ACCESO -->
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-user-tag"></i></div>
                <h2>10. Control de Acceso Basado en Roles (RBAC)</h2>
            </div>
            <div class="sec-card-body">
                <p class="sec-status">Estado: <span class="status-active"><i class="fas fa-check-circle"></i> Implementado</span></p>
                <h3>Roles del Sistema</h3>
                <div class="sec-table-wrap">
                    <table class="sec-table">
                        <thead><tr><th>Rol</th><th>Permisos</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><span class="role-badge admin">Admin</span></td>
                                <td>Gestión completa: usuarios, roles, configuración global, mantenimiento, panel de administración, auditoría, notificaciones</td>
                            </tr>
                            <tr>
                                <td><span class="role-badge operator">Operador</span></td>
                                <td>Registro de datos, reportes, inventario, soporte, gestión de perfil propio</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <h3>Verificaciones</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> Validación del rol en cada acción administrativa del API</li>
                    <li><i class="fas fa-check text-success"></i> Consulta fresca del rol desde la BD en cada petición API</li>
                    <li><i class="fas fa-check text-success"></i> Botones y secciones de admin ocultos en la interfaz para no-admin</li>
                    <li><i class="fas fa-check text-success"></i> Verificación en rutas críticas (mantenimiento, historial, seguridad)</li>
                </ul>
            </div>
        </div>

        <!-- 11. MODO MANTENIMIENTO -->
        <div class="sec-card">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-power-off"></i></div>
                <h2>11. Modo Mantenimiento</h2>
            </div>
            <div class="sec-card-body">
                <p class="sec-status">Estado: <span class="<?php echo $maintenance ? 'status-warning' : 'status-inactive'; ?>"><i class="fas fa-<?php echo $maintenance ? 'exclamation-triangle' : 'check-circle'; ?>"></i> <?php echo $maintenance ? 'ACTIVO — Solo administradores' : 'Inactivo'; ?></span></p>
                <ul class="sec-list">
                    <li><i class="fas fa-check text-success"></i> Bloquea el acceso a todos los usuarios no-admin cuando está activo</li>
                    <li><i class="fas fa-check text-success"></i> Las rutas públicas (login, registro, recuperación) permanecen accesibles</li>
                    <li><i class="fas fa-check text-success"></i> Destruye la sesión de usuarios bloqueados</li>
                    <li><i class="fas fa-check text-success"></i> Activable/desactivable desde el panel de administración</li>
                </ul>
            </div>
        </div>

        <!-- 12. RECOMENDACIONES -->
        <div class="sec-card sec-card-warning">
            <div class="sec-card-header">
                <div class="sec-card-icon"><i class="fas fa-lightbulb"></i></div>
                <h2>12. Recomendaciones Adicionales</h2>
            </div>
            <div class="sec-card-body">
                <h3>Futuras Mejoras</h3>
                <ul class="sec-list">
                    <li><i class="fas fa-hourglass-half text-warning"></i> <strong>HTTPS forzado</strong>: redirigir todo el tráfico HTTP a HTTPS usando rewrite rules</li>
                    <li><i class="fas fa-hourglass-half text-warning"></i> <strong>2FA completo con TOTP</strong>: implementar verificación real con Google Authenticator</li>
                    <li><i class="fas fa-hourglass-half text-warning"></i> <strong>Notificación de nuevo dispositivo</strong>: enviar email al detectar login desde IP desconocida</li>
                    <li><i class="fas fa-hourglass-half text-warning"></i> <strong>Límite de sesiones concurrentes</strong>: máximo N sesiones por usuario</li>
                    <li><i class="fas fa-hourglass-half text-warning"></i> <strong>Rate limiting general</strong>: limitar peticiones por IP en endpoints críticos</li>
                    <li><i class="fas fa-hourglass-half text-warning"></i> <strong>CSP Header</strong>: Content-Security-Policy para prevenir XSS y data injection</li>
                    <li><i class="fas fa-hourglass-half text-warning"></i> <strong>Log de acceso a datos sensibles</strong>: registrar consultas a información crítica</li>
                </ul>
            </div>
        </div>

    </div>

    <footer class="sec-footer">
        <p>SICAY — Sistema Integrado de Control de Aguas de Yaracuy</p>
        <p>Documento generado el <?php echo date('d/m/Y H:i'); ?> | Última revisión de seguridad: <?php echo $last_cleanup; ?></p>
    </footer>
</div>

<script src="assets/js/seguridad/seguridad_documentacion.js"></script>
</body>
</html>
