<?php
include("conexion.php");
include("functions.php");
session_init();

$conexion = (new conectar())->conexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = trim($_POST['usuario'] ?? '');
    $passInput = $_POST['pass'] ?? '';

    if ($userInput === '' || $passInput === '') {
        mostrar_error('Por favor completa todos los campos.');
        exit();
    }

    $stmt = $conexion->prepare("SELECT id, nombre, usuario, correo, contraseña FROM usuario WHERE usuario = ?");
    $stmt->bind_param("s", $userInput);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap' rel='stylesheet'>
        <style>
            body { font-family: 'Poppins', sans-serif; background-color: #0B1C2D; }
            .swal-acceso { background: #1a2d3e !important; color: #e0e0e0 !important; border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 18px !important; }
            .swal-acceso .swal2-title { color: #ffffff !important; font-family: 'Poppins', sans-serif !important; font-weight: 600 !important; }
            .swal-acceso .swal2-html-container { color: #c0c0c0 !important; font-family: 'Poppins', sans-serif !important; }
            .swal-acceso .swal2-timer-progress-bar { background: #00cec9 !important; border-radius: 0 0 18px 18px !important; }
            .swal-acceso .swal2-icon.swal2-success { border-color: #2ecc71 !important; }
            .swal-acceso .swal2-icon.swal2-success .swal2-success-ring { border-color: rgba(46,204,113,0.3) !important; }
            .swal-acceso .swal2-icon.swal2-success [class^=swal2-success-line] { background-color: #2ecc71 !important; }
            .swal-acceso .swal2-icon.swal2-error { border-color: #ff7675 !important; }
            .swal-acceso .swal2-icon.swal2-error [class^=swal2-x-mark-line] { background-color: #ff7675 !important; }
            .swal-acceso .swal2-icon.swal2-warning { border-color: #f1c40f !important; color: #f1c40f !important; }
            .swal-acceso .swal2-confirm { border-radius: 10px !important; font-family: 'Poppins', sans-serif !important; font-weight: 600 !important; padding: 10px 24px !important; }
        </style>
    </head>
    <body>";

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (verificar_pass($passInput, $user['contraseña'])) {
            if (strlen($user['contraseña']) === 32 && ctype_xdigit($user['contraseña'])) {
                $nuevo_hash = hash_pass($passInput);
                $upd = $conexion->prepare("UPDATE usuario SET contraseña = ? WHERE id = ?");
                $upd->bind_param("si", $nuevo_hash, $user['id']);
                $upd->execute();
                $upd->close();
            }

            session_regenerate_id(true);
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['id_usuario'] = $user['id'];

            echo "<script>
                Swal.fire({
                    title: '¡Acceso Concedido!',
                    text: 'Iniciando sesión en SICAY...',
                    icon: 'success',
                    background: '#ffffff',
                    color: '#000000',
                    backdrop: 'rgba(255,255,255,0.15)',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    customClass: { popup: 'swal-acceso' }
                }).then(() => {
                    window.location.href = '../dashboard.php';
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    title: 'Error de Seguridad',
                    text: 'La contraseña ingresada es incorrecta.',
                    icon: 'error',
                    background: '#ffffff',
                    color: '#000000',
                    backdrop: 'rgba(255,255,255,0.15)',
                    confirmButtonColor: '#2ecc71',
                    confirmButtonText: 'Reintentar'
                }).then(() => {
                    window.location.href = '../login.php';
                });
            </script>";
        }
    } else {
        echo "<script>
            Swal.fire({
                title: 'Usuario no encontrado',
                text: 'El nombre de usuario no está registrado en el sistema.',
                icon: 'warning',
                background: '#ffffff',
                color: '#000000',
                backdrop: 'rgba(255,255,255,0.15)',
                confirmButtonColor: '#2ecc71',
                confirmButtonText: 'Regresar'
            }).then(() => {
                window.location.href = '../login.php';
            });
        </script>";
    }

    echo "</body></html>";
    $stmt->close();
    $conexion->close();
}

function mostrar_error($msg) {
    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>
    <script>
        Swal.fire({
            title: 'Error',
            text: " . json_encode($msg) . ",
            icon: 'error',
            background: '#ffffff',
            color: '#000000',
            backdrop: 'rgba(255,255,255,0.15)',
            confirmButtonColor: '#2ecc71',
            confirmButtonText: 'Regresar'
        }).then(() => { window.location.href = '../login.php'; });
    </script></body></html>";
}
?>
