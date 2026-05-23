<?php
verificar_sesion();

// 2. Consulta a la tabla con INNER JOIN
$sql = "SELECT h.*, u.usuario FROM historial h 
        INNER JOIN usuario u ON h.id_usuario = u.id 
        ORDER BY h.fecha DESC";

$resultado = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Movimientos - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/reportes/historial.css">
</head>
<body>

<div id="bubbles"></div>

<div class="container">
    <div class="glass-card">
        <h2><i class="fas fa-history"></i> Historial de Movimientos</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><i class="far fa-calendar-alt"></i> Fecha</th>
                        <th><i class="far fa-user"></i> Usuario</th>
                        <th><i class="fas fa-bolt"></i> Acción</th>
                        <th><i class="fas fa-layer-group"></i> Módulo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($resultado && mysqli_num_rows($resultado) > 0):
                        while($row = mysqli_fetch_assoc($resultado)): ?>
                        <tr>
                            <td><span style="opacity: 0.8;"><?php echo $row['fecha']; ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['usuario']); ?></strong></td>
                            <td><span class="badge"><?php echo htmlspecialchars($row['accion']); ?></span></td>
                            <td><span style="color: var(--accent);"><?php echo htmlspecialchars($row['modulo']); ?></span></td>
                        </tr>
                    <?php endwhile; 
                    else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; opacity: 0.5;">
                                <i class="fas fa-info-circle"></i> No hay registros de movimientos todavía.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <a href="index.php?route=dashboard" class="volver">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard Principal
        </a>
    </div>
</div>

<script src="assets/js/reportes/historial.js"></script>

</body>
</html>
