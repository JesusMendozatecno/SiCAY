<?php
$token = $_GET['token'] ?? '';

$error_token = false;
$mensaje = '';
$exito = false;

if (empty($token)) {
    $error_token = true;
    $error_msg = 'No se proporcionó un token de recuperación.';
} else {
    $stmt = $con->prepare("SELECT id, correo FROM usuario WHERE reset_token = ? AND token_expira > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $error_token = true;
        $error_msg = 'El enlace de recuperación no es válido o ha expirado. Solicita uno nuevo.';
    }
}

if (!$error_token && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $pass = $_POST['pass'] ?? '';
    $pass2 = $_POST['pass2'] ?? '';

    $errores = [];
    if (strlen($pass) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if (!preg_match('/[A-Z]/', $pass)) $errores[] = 'Debe contener al menos una mayúscula.';
    if (!preg_match('/[0-9]/', $pass)) $errores[] = 'Debe contener al menos un número.';
    if (!preg_match('/[^A-Za-z0-9]/', $pass)) $errores[] = 'Debe contener al menos un símbolo especial.';
    if ($pass !== $pass2) $errores[] = 'Las contraseñas no coinciden.';

    if (empty($errores)) {
        $hash = hash_pass($pass);
        $upd = $con->prepare("UPDATE usuario SET contraseña = ?, reset_token = NULL, token_expira = NULL WHERE id = ?");
        $upd->bind_param("si", $hash, $user['id']);
        if ($upd->execute()) {
            $exito = true;
        } else {
            $mensaje = ['tipo' => 'error', 'texto' => 'Error al actualizar la contraseña. Intenta de nuevo.'];
        }
        $upd->close();
    } else {
        $mensaje = ['tipo' => 'error', 'texto' => implode(' ', $errores)];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $exito ? 'Contraseña actualizada' : ($error_token ? 'Enlace inválido' : 'Restablecer Contraseña'); ?> - Hidroven Yaracuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/inicio/olvide_pass.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <img src="assets/img/EUhOGzfWAAAHZC4-removebg-preview.png" alt="Logo" class="logo-img">
            <span class="brand-text">Aguas de Yaracuy</span>
        </div>
        <a href="index.php?route=login" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
</nav>

<main class="main-content">
    <div class="recovery-card">
        <?php if ($exito): ?>
            <div class="icon-header"><i class="fas fa-check-circle" style="color:#2ecc71;"></i></div>
            <h2>Contraseña Actualizada</h2>
            <p>Tu contraseña se ha restablecido correctamente.</p>
            <div class="alerta exito"><i class="fas fa-check-circle"></i> Redirigiendo al inicio de sesión...</div>
            <a href="index.php?route=login" class="btn-submit" style="display:inline-block;text-decoration:none;">Ir al Inicio de Sesión</a>
            <meta http-equiv="refresh" content="3;url=index.php?route=login">

        <?php elseif ($error_token): ?>
            <div class="icon-header"><i class="fas fa-exclamation-triangle" style="color:#ff7675;"></i></div>
            <h2>Enlace inválido o expirado</h2>
            <p><?php echo hsc($error_msg); ?></p>
            <a href="index.php?route=olvide_pass" class="btn-submit" style="display:inline-block;text-decoration:none;">Solicitar nuevo enlace</a>
            <div class="back-to-login"><a href="index.php?route=login"><i class="fas fa-chevron-left"></i> Ir al Inicio de Sesión</a></div>

        <?php else: ?>
            <div class="icon-header"><i class="fas fa-lock"></i></div>
            <h2>Restablecer Contraseña</h2>
            <p>Crea una nueva contraseña para tu cuenta.</p>

            <?php if (!empty($mensaje)): ?>
                <div class="alerta <?php echo $mensaje['tipo']; ?>">
                    <i class="fas <?php echo $mensaje['tipo'] === 'exito' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo hsc($mensaje['texto']); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Nueva Contraseña</label>
                    <div class="input-group">
                        <input type="password" name="pass" id="pass" placeholder="Mín. 8 caracteres" required minlength="8">
                        <button type="button" class="toggle-pass" onclick="togglePass('pass', this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                    <ul class="pass-requirements">
                        <li data-req="length">Mínimo 8 caracteres</li>
                        <li data-req="upper">Una mayúscula</li>
                        <li data-req="number">Un número</li>
                        <li data-req="symbol">Un símbolo especial</li>
                    </ul>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i> Confirmar Contraseña</label>
                    <div class="input-group">
                        <input type="password" name="pass2" id="pass2" placeholder="Repite la contraseña" required minlength="8">
                        <button type="button" class="toggle-pass" onclick="togglePass('pass2', this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Cambiar Contraseña</button>
            </form>

            <div class="back-to-login">
                <a href="index.php?route=login"><i class="fas fa-chevron-left"></i> Ir al Inicio de Sesión</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer>
    &copy; 2026 Aguas de Yaracuy - Gestión Integral de Calidad
</footer>

<script>
function togglePass(id, btn) {
    var input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i class="fas fa-eye"></i>';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    var passInput = document.getElementById('pass');
    if (passInput) {
        passInput.addEventListener('input', function() {
            var val = this.value;
            document.querySelectorAll('.pass-requirements li').forEach(function(li) {
                var req = li.getAttribute('data-req');
                var ok = false;
                if (req === 'length') ok = val.length >= 8;
                else if (req === 'upper') ok = /[A-Z]/.test(val);
                else if (req === 'number') ok = /[0-9]/.test(val);
                else if (req === 'symbol') ok = /[^A-Za-z0-9]/.test(val);
                li.classList.toggle('met', ok);
            });
        });
    }
});
</script>

</body>
</html>
