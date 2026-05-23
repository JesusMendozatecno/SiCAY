<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Hidroven Yaracuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/inicio/olvide_pass.css">
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
    <div class="recovery-card">
        <div class="icon-header"><i class="fas fa-key"></i></div>
        <h2>Recuperar Contraseña</h2>
        <p>Ingresa tu correo electrónico registrado y te enviaremos las instrucciones para restablecer tu contraseña.</p>

        <?php if (isset($_SESSION['reset_msg'])): ?>
            <div class="alerta <?php echo $_SESSION['reset_msg']['tipo']; ?>">
                <i class="fas <?php echo $_SESSION['reset_msg']['tipo'] === 'exito' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo hsc($_SESSION['reset_msg']['texto']); ?>
            </div>
            <?php if (!empty($_SESSION['reset_msg']['link'])): ?>
                <div class="debug-link"><?php echo $_SESSION['reset_msg']['link']; ?></div>
            <?php endif; ?>
            <?php unset($_SESSION['reset_msg']); ?>
        <?php endif; ?>

        <form action="index.php?route=forgot_password" method="POST">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Correo Electrónico</label>
                <input type="email" name="correo" placeholder="ejemplo@correo.com" required autofocus>
            </div>
            <button type="submit" class="btn-submit">Enviar Instrucciones</button>
        </form>

        <div class="back-to-login">
            <a href="index.php?route=login"><i class="fas fa-chevron-left"></i> Regresar al Inicio de Sesión</a>
        </div>
    </div>
</main>

<footer>
    &copy; 2026 Hidroven Yaracuy - Gestión Integral de Calidad
</footer>

<script src="assets/js/inicio/olvide_pass.js"></script>

</body>
</html>
