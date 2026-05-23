<?php
include("conexion.php");
include("functions.php");
session_init();

$conexion = (new conectar())->conexion();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$faltantes = validar_requeridos(['nombre', 'usuario', 'correo', 'pass'], $_POST);
if (!empty($faltantes)) {
    die("Faltan campos obligatorios: " . implode(', ', $faltantes));
}

$nombre  = trim($_POST['nombre']);
$usuario = trim($_POST['usuario']);
$correo  = trim($_POST['correo']);
$pass    = $_POST['pass'];

if (!validar_nombre($nombre)) {
    die("El nombre solo puede contener letras y espacios");
}

if (!validar_email($correo)) {
    die("El correo electrónico no tiene un formato válido");
}

if (strlen($usuario) < 3 || strlen($usuario) > 50) {
    die("El usuario debe tener entre 3 y 50 caracteres");
}

if (strlen($pass) < 6) {
    die("La contraseña debe tener al menos 6 caracteres");
}

$stmt = $conexion->prepare("SELECT id FROM usuario WHERE usuario = ? OR correo = ?");
$stmt->bind_param("ss", $usuario, $correo);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die("El usuario o correo ya está registrado");
}
$stmt->close();

$clave = hash_pass($pass);
$rol = "Operador";

$insertar = $conexion->prepare("INSERT INTO usuario (nombre, usuario, correo, contraseña, rol) VALUES (?, ?, ?, ?, ?)");
$insertar->bind_param("sssss", $nombre, $usuario, $correo, $clave, $rol);

if ($insertar->execute()) {
    echo '
        <script>
            alert("¡Se registró exitosamente en SICAY!");
            window.location="../login.php";
        </script>
    ';
} else {
    error_log("Error al registrar usuario: " . $insertar->error);
    echo "Ocurrió un error al registrar. Intenta de nuevo más tarde.";
}

$insertar->close();
$conexion->close();
?>
