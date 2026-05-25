<?php

function hsc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function validar_requeridos($campos, $datos) {
    $faltantes = [];
    foreach ($campos as $campo) {
        if (!isset($datos[$campo]) || trim($datos[$campo]) === '') {
            $faltantes[] = $campo;
        }
    }
    return $faltantes;
}

function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validar_nombre($nombre) {
    return preg_match("/^[A-Za-zÁÉÍÓÚáéíóúñÑ ]+$/", $nombre) === 1;
}

function validar_numeric($valor, $min = null, $max = null) {
    if (!is_numeric($valor)) return false;
    $v = $valor + 0;
    if ($min !== null && $v < $min) return false;
    if ($max !== null && $v > $max) return false;
    return true;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verificar_csrf($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token ?? '')) {
        http_response_code(403);
        die('Error de validación CSRF. Intenta recargar la página.');
    }
}

function redirigir($ruta) {
    header("Location: " . BASE_URL . "?route=$ruta");
    exit();
}

function asset_url($path) {
    return "assets/$path";
}

function route_url($route) {
    return BASE_URL . "?route=$route";
}

function mensaje_exito($texto) {
    return '<div class="alerta-exito"><i class="fas fa-check-circle"></i> ' . hsc($texto) . '</div>';
}

function mensaje_error($texto) {
    return '<div class="alerta-error"><i class="fas fa-exclamation-triangle"></i> ' . hsc($texto) . '</div>';
}

function hash_pass($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verificar_pass($password, $hash) {
    if (strlen($hash) === 32 && ctype_xdigit($hash)) {
        if (md5($password) === $hash) {
            return true;
        }
    }
    return password_verify($password, $hash);
}

function flash_exito($msg) {
    $_SESSION['flash_success'] = $msg;
}

function flash_error($msg) {
    $_SESSION['flash_error'] = $msg;
}

function session_init() {
    if (session_status() === PHP_SESSION_NONE) {
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $is_https,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

function verificar_sesion() {
    if (!isset($_SESSION['usuario'])) {
        redirigir('login');
    }
    verificar_tiempo_sesion();
}

function verificar_sesion_json() {
    if (!isset($_SESSION['usuario'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Sesión no iniciada']);
        exit;
    }
}

function __($key, $lang = null) {
    if ($lang === null) {
        global $current_lang;
        $lang = $current_lang ?? 'es';
    }
    static $translations = [];
    if (!isset($translations[$lang])) {
        $file = dirname(__DIR__) . "/app/lang/$lang.php";
        if (file_exists($file)) {
            $translations[$lang] = include $file;
        } else {
            $file = dirname(__DIR__) . "/app/lang/es.php";
            $translations[$lang] = file_exists($file) ? include $file : [];
        }
    }
    return $translations[$lang][$key] ?? $key;
}

function log_activity($user_id, $action, $details = null, $tipo_accion = null, $modulo = null) {
    global $con;
    if (!$con) return false;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // Ensure url column exists (auto-migration)
    static $url_col_checked = false;
    if (!$url_col_checked) {
        $url_col_checked = true;
        try {
            $r = $con->query("SHOW COLUMNS FROM historial LIKE 'url'");
            if ($r && $r->num_rows === 0) {
                $con->query("ALTER TABLE historial ADD COLUMN url VARCHAR(500) DEFAULT NULL AFTER ip_address");
            }
        } catch (\Throwable $e) {}
    }

    // Backward compat: write to activity_log
    $stmt = $con->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }

    // Auto-detect type/module if not provided
    if ($tipo_accion === null) {
        $lower = mb_strtolower($action);
        if (strpos($lower, 'inició') !== false || strpos($lower, 'inicio') !== false) $tipo_accion = 'login';
        elseif (strpos($lower, 'cerró') !== false || strpos($lower, 'cerro') !== false) $tipo_accion = 'logout';
        elseif (strpos($lower, 'elimin') !== false) $tipo_accion = 'delete';
        elseif (strpos($lower, 'actualiz') !== false || strpos($lower, 'cambió') !== false || strpos($lower, 'cambio') !== false) $tipo_accion = 'update';
        elseif (strpos($lower, 'cre') !== false || strpos($lower, 'registr') !== false) $tipo_accion = 'create';
        elseif (strpos($lower, 'export') !== false || strpos($lower, 'pdf') !== false) $tipo_accion = 'export';
        elseif (strpos($lower, 'config') !== false) $tipo_accion = 'config';
        elseif (strpos($lower, 'seguridad') !== false || strpos($lower, 'contrase') !== false || strpos($lower, '2fa') !== false) $tipo_accion = 'security';
        elseif (strpos($lower, 'notific') !== false) $tipo_accion = 'notification';
        else $tipo_accion = 'system';
    }

    // Write to unified historial table
    $usuario = $_SESSION['usuario'] ?? 'Sistema';
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $stmt2 = $con->prepare("INSERT INTO historial (id_usuario, usuario, accion, tipo_accion, modulo, descripcion, ip_address, url, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt2) {
        $desc = $details ?? $action;
        $stmt2->bind_param("isssssss", $user_id, $usuario, $action, $tipo_accion, $modulo, $desc, $ip, $url);
        $stmt2->execute();
        $stmt2->close();
    }
}

function is_maintenance_mode() {
    global $con;
    if (!$con) return false;
    $r = $con->query("SELECT config_value FROM system_config WHERE config_key = 'maintenance_mode'");
    if ($r && $f = $r->fetch_assoc()) {
        return $f['config_value'] === '1';
    }
    return false;
}

function get_system_config($key, $default = null) {
    global $con;
    if (!$con) return $default;
    $stmt = $con->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
    if (!$stmt) return $default;
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $r = $stmt->get_result();
    $val = $default;
    if ($f = $r->fetch_assoc()) {
        $val = $f['config_value'];
    }
    $stmt->close();
    return $val;
}

/* =============================================
   SEGURIDAD — POLÍTICA DE CONTRASEÑAS
   ============================================= */

function validar_politica_pass($password) {
    $errors = [];
    if (strlen($password) < 8)
        $errors[] = 'Mínimo 8 caracteres';
    if (!preg_match('/[A-Z]/', $password))
        $errors[] = 'Debe contener al menos una mayúscula';
    if (!preg_match('/[a-z]/', $password))
        $errors[] = 'Debe contener al menos una minúscula';
    if (!preg_match('/[0-9]/', $password))
        $errors[] = 'Debe contener al menos un número';
    if (!preg_match('/[^A-Za-z0-9]/', $password))
        $errors[] = 'Debe contener al menos un carácter especial';
    return $errors;
}

/* =============================================
   SEGURIDAD — BLOQUEO POR FUERZA BRUTA
   ============================================= */

function crear_tabla_login_attempts() {
    global $con;
    if (!$con) return;
    $con->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        success TINYINT(1) DEFAULT 0,
        attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_lookup (username, ip_address, attempted_at),
        INDEX idx_cleanup (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
}

function registrar_intento_login($username, $success) {
    global $con;
    if (!$con) return;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $con->prepare("INSERT INTO login_attempts (username, ip_address, user_agent, success) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $s = $success ? 1 : 0;
        $stmt->bind_param("sssi", $username, $ip, $ua, $s);
        $stmt->execute();
        $stmt->close();
    }
}

function check_login_lockout($username) {
    global $con;
    if (!$con) return false;
    crear_tabla_login_attempts();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $max_attempts = 5;
    $stmt = $con->prepare("SELECT COUNT(*) as c FROM login_attempts WHERE (username = ? OR ip_address = ?) AND attempted_at > ? AND success = 0");
    if (!$stmt) return false;
    $stmt->bind_param("sss", $username, $ip, $window);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($row['c'] ?? 0) >= $max_attempts;
}

/* =============================================
   SEGURIDAD — TIEMPO DE SESIÓN
   ============================================= */

function verificar_tiempo_sesion() {
    $timeout = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
        redirigir('login');
    }
    $_SESSION['last_activity'] = time();
}

/* =============================================
   SEGURIDAD — LIMPIEZA PROGRAMADA
   ============================================= */

function limpiar_sesiones_expiradas() {
    global $con;
    if (!$con) return;
    $stmt = $con->prepare("DELETE FROM user_sessions WHERE is_current = 0 AND last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
        $stmt2 = $con->prepare("UPDATE system_config SET config_value = NOW() WHERE config_key = 'last_session_cleanup'");
        if ($stmt2) {
            $stmt2->execute();
            $stmt2->close();
        }
    }
    // Clean old login attempts (>7 days)
    $con->query("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
}
