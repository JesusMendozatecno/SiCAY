<?php
$conexion = $con;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirigir("login");
}

$faltantes = validar_requeridos(['nombre', 'usuario', 'correo', 'pass', 'pass2'], $_POST);
if (!empty($faltantes)) {
    $_SESSION['registro_errores'] = ['Completa todos los campos obligatorios.'];
    redirigir("registro");
}

$nombre  = trim($_POST['nombre']);
$usuario = trim($_POST['usuario']);
$correo  = trim($_POST['correo']);
$pass    = $_POST['pass'];
$pass2   = $_POST['pass2'];

if (!validar_nombre($nombre)) {
    $_SESSION['registro_errores'] = ['El nombre solo puede contener letras y espacios.'];
    redirigir("registro");
}

if (!validar_email($correo)) {
    $_SESSION['registro_errores'] = ['El correo electrónico no tiene un formato válido.'];
    redirigir("registro");
}

if (strlen($usuario) < 3 || strlen($usuario) > 50) {
    $_SESSION['registro_errores'] = ['El usuario debe tener entre 3 y 50 caracteres.'];
    redirigir("registro");
}

$pass_errors = [];
if (strlen($pass) < 8) $pass_errors[] = 'length';
if (!preg_match('/[A-Z]/', $pass)) $pass_errors[] = 'upper';
if (!preg_match('/[0-9]/', $pass)) $pass_errors[] = 'number';
if (!preg_match('/[^A-Za-z0-9]/', $pass)) $pass_errors[] = 'symbol';

if (!empty($pass_errors)) {
    $_SESSION['registro_errores'] = $pass_errors;
    redirigir("registro");
}

if ($pass !== $pass2) {
    $_SESSION['registro_errores'] = ['Las contraseñas no coinciden.'];
    redirigir("registro");
}

$errores_dup = [];

$stmt_u = $conexion->prepare("SELECT id FROM usuario WHERE usuario = ?");
$stmt_u->bind_param("s", $usuario);
$stmt_u->execute();
if ($stmt_u->get_result()->num_rows > 0) {
    $errores_dup[] = 'El usuario "' . hsc($usuario) . '" ya está en uso.';
}
$stmt_u->close();

$stmt_c = $conexion->prepare("SELECT id FROM usuario WHERE correo = ?");
$stmt_c->bind_param("s", $correo);
$stmt_c->execute();
if ($stmt_c->get_result()->num_rows > 0) {
    $errores_dup[] = 'El correo "' . hsc($correo) . '" ya está registrado.';
}
$stmt_c->close();

if (!empty($errores_dup)) {
    $_SESSION['registro_errores'] = $errores_dup;
    redirigir("registro");
}

$clave = hash_pass($pass);
$rol = "Operador";

$insertar = $conexion->prepare("INSERT INTO usuario (nombre, usuario, correo, contraseña, rol) VALUES (?, ?, ?, ?, ?)");
$insertar->bind_param("sssss", $nombre, $usuario, $correo, $clave, $rol);

if ($insertar->execute()) {
    flash_exito('¡Te registraste exitosamente en SICAY!');
    redirigir("login");
} else {
    error_log("Error al registrar usuario: " . $insertar->error);
    flash_error('Ocurrió un error al registrar. Intenta de nuevo.');
    redirigir("registro");
}

$insertar->close();
