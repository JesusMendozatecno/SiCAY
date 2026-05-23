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

function redirigir($url) {
    header("Location: $url");
    exit();
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

function obtener_conexion() {
    static $con = null;
    if ($con === null) {
        $obj = new conectar();
        $con = $obj->conexion();
    }
    return $con;
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
