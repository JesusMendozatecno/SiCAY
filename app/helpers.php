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
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
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

function log_activity($user_id, $action, $details = null) {
    global $con;
    if (!$con) return false;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $con->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
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
