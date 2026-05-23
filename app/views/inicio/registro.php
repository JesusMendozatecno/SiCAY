<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Hidroven Yaracuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/inicio/registro.css">
</head>
<body>

<div id="bubble-container"></div>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <img src="assets/img/EUhOGzfWAAAHZC4-removebg-preview.png" alt="Logo" class="logo-img">
            <span class="brand-text">Hidroven Yaracuy</span>
        </div>
        <a href="index.php?route=index" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
</nav>

<main class="main-content">
    <div class="register-card">
        <h2>Crear Cuenta</h2>
        <p>Departamento de Calidad de Agua</p>

        <form action="index.php?route=registrar" method="POST">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre" id="nombre" placeholder="Ej. Paola Inojosa" pattern="[A-Za-zÁÉÍÓÚáéíóúñÑ ]+"
    title="Solo se permiten letras y espacios" required>

            </div>

            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" placeholder="Nombre de usuario" required>
            </div>

            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="correo" placeholder="correo@ejemplo.com" required>
            </div>

            <div class="form-group">
                <label>Contraseña</label>
                <div class="input-group">
                    <input type="password" name="pass" id="pass" placeholder="Mín. 8 caracteres" required minlength="8">
                    <button type="button" class="toggle-pass" onclick="togglePass('pass', this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                </div>
                <ul class="pass-requirements" id="passReqs">
                    <li data-req="length">Mínimo 8 caracteres</li>
                    <li data-req="upper">Una mayúscula</li>
                    <li data-req="number">Un número</li>
                    <li data-req="symbol">Un símbolo especial</li>
                </ul>
                <div id="toast-container"></div>
            </div>

            <div class="form-group" id="confirm-pass-group" style="display:none;">
                <label>Confirmar Contraseña</label>
                <div class="input-group">
                    <input type="password" name="pass2" id="pass2" placeholder="Repite la contraseña" required minlength="8">
                    <button type="button" class="toggle-pass" onclick="togglePass('pass2', this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                </div>
            </div>

            <button type="submit" class="btn-submit">Registrarme Ahora</button>
        </form>

        <div class="login-link">
            ¿Ya tienes cuenta? <a href="index.php?route=login">Inicia sesión</a>
        </div>
    </div>
</main>

<footer>
    &copy; 2026 Hidroven Yaracuy - Gestión Integral de Calidad
</footer>

<script>
var registroErrores = <?php echo isset($_SESSION['registro_errores']) ? json_encode($_SESSION['registro_errores']) : 'undefined'; ?>;
<?php unset($_SESSION['registro_errores']); ?>
</script>
<script src="assets/js/inicio/registro.js"></script>

</body>
</html>
