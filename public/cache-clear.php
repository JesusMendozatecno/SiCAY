<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    $msg = 'OPcache resetado correctamente.';
} else {
    $msg = 'OPcache no está activo en este servidor.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cache Limpio</title>
    <style>
        body { font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #0B1C2D; color: #fff; text-align: center; }
        .card { background: rgba(255,255,255,0.95); padding: 30px; border-radius: 18px; color: #123C69; max-width: 400px; }
        .card i { font-size: 3rem; color: #2ecc71; }
        .card h2 { margin: 10px 0; }
        .card p { color: #666; font-size: 0.9rem; }
        .card a { display: inline-block; margin-top: 15px; padding: 10px 25px; background: #1F6AE1; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .card a:hover { background: #123C69; }
    </style>
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
</head>
<body>
    <div class="card">
        <i class="fas fa-check-circle"></i>
        <h2>Cache Limpio</h2>
        <p><?php echo $msg; ?></p>
        <p style="font-size:0.8rem;">Las versiones de CSS/JS se actualizan automáticamente cada minuto.</p>
        <a href="index.php">Volver al inicio</a>
    </div>
</body>
</html>
