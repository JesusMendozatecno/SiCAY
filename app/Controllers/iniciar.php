<?php
$conexion = $con;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = trim($_POST['usuario'] ?? '');
    $passInput = $_POST['pass'] ?? '';

    if ($userInput === '' || $passInput === '') {
        mostrar_error('Por favor completa todos los campos.');
        exit();
    }

    // Verificar bloqueo por fuerza bruta
    if (check_login_lockout($userInput)) {
        registrar_intento_login($userInput, false);
        echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>
        <script>
            Swal.fire({
                title: 'Cuenta Bloqueada',
                text: 'Demasiados intentos fallidos. Espera 15 minutos antes de intentar de nuevo.',
                icon: 'warning',
                confirmButtonColor: '#e74c3c',
                confirmButtonText: 'Entendido'
            }).then(() => { window.location.href = 'index.php?route=login'; });
        </script></body></html>";
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
        </style>
    </head>
    <body>";

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (verificar_pass($passInput, $user['contraseña'])) {
            registrar_intento_login($userInput, true);
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

            // Fetch and store role
            $rStmt = $conexion->prepare("SELECT rol FROM usuario WHERE id = ?");
            $rStmt->bind_param("i", $user['id']); $rStmt->execute();
            $rRow = $rStmt->get_result()->fetch_assoc(); $rStmt->close();
            $_SESSION['rol'] = $rRow['rol'] ?? '';

            // Update last login
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $updLog = $conexion->prepare("UPDATE usuario SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
            $updLog->bind_param("si", $ip, $user['id']); $updLog->execute(); $updLog->close();

            // Record session
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $sessId = session_id();
            // Set all previous sessions as not current
            $conexion->query("UPDATE user_sessions SET is_current = 0 WHERE user_id = " . intval($user['id']));
            $insSess = $conexion->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, is_current) VALUES (?, ?, ?, ?, 1)");
            $insSess->bind_param("isss", $user['id'], $sessId, $ip, $ua); $insSess->execute(); $insSess->close();

            // Log activity
            log_activity($user['id'], 'Inició sesión', null, 'login', 'Sistema');

            echo "<script>
                Swal.fire({
                    title: '¡Acceso Concedido!',
                    text: 'Iniciando sesión en SICAY...',
                    icon: 'success',
                    background: '#ffffff',
                    color: '#123C69',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didOpen: () => { Swal.showLoading() }
                }).then(() => {
                    window.location.href = 'index.php?route=dashboard';
                });
            </script>";
        } else {
            registrar_intento_login($userInput, false);
            echo "<script>
                Swal.fire({
                    title: 'Error de Seguridad',
                    text: 'La contraseña ingresada es incorrecta.',
                    icon: 'error',
                    confirmButtonColor: '#1F6AE1',
                    confirmButtonText: 'Reintentar'
                }).then(() => {
                    window.location.href = 'index.php?route=login';
                });
            </script>";
        }
    } else {
        registrar_intento_login($userInput, false);
        echo "<script>
            Swal.fire({
                title: 'Usuario no encontrado',
                text: 'El nombre de usuario no está registrado en el sistema.',
                icon: 'warning',
                confirmButtonColor: '#123C69',
                confirmButtonText: 'Regresar'
            }).then(() => {
                window.location.href = 'index.php?route=login';
            });
        </script>";
    }

    echo "</body></html>";
    $stmt->close();
}

function mostrar_error($msg) {
    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>
    <script>
        Swal.fire({
            title: 'Error',
            text: " . json_encode($msg) . ",
            icon: 'error',
            confirmButtonColor: '#1F6AE1',
            confirmButtonText: 'Regresar'
        }).then(() => { window.location.href = 'index.php?route=login'; });
    </script></body></html>";
}
