<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Hidroven Yaracuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/inicio/login.css">
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
    <div class="login-card">
        <div class="logo-circular">
            <img src="assets/img/EUhOGzfWAAAHZC4-removebg-preview.png" alt="Logo Empresa">
        </div>
        <h2>Bienvenido</h2>
        <p>Inicia sesión para continuar</p>

        <form action="index.php?route=iniciar" method="POST">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Usuario</label>
                <input type="text" name="usuario" placeholder="Ingresa tu usuario" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> Contraseña</label>
                <div class="input-group">
                    <input type="password" name="pass" id="pass" placeholder="••••••••" required>
                    <button type="button" class="toggle-pass" onclick="togglePass('pass', this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                </div>
            </div>

            <button type="submit" class="btn-submit">Ingresar al Sistema</button>
        </form>

        <div class="extra-links">
            <a href="index.php?route=olvide_pass">¿Olvidaste tu contraseña?</a>
            <span>¿No tienes cuenta? <a href="index.php?route=registro">Regístrate aquí</a></span>
        </div>
    </div>
</main>

<footer>
    &copy; 2026 Hidroven Yaracuy - Gestión Integral de Calidad
</footer>

<script src="assets/js/inicio/login.js"></script>

</body>
</html>
