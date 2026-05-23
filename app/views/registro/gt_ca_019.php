<?php
verificar_sesion();

$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'nombre_trabajador', 'tipo_epp', 'entrega', 'estado'], $_POST);
    if (!empty($faltantes)) { $mensaje = "error"; }
    else {
        $id_inst = intval($_POST['planta']);
        $trabajador = trim($_POST['nombre_trabajador']);
        $tipo_epp = $_POST['tipo_epp'];
        $entrega = intval($_POST['entrega']);
        $estado = intval($_POST['estado']);
        $observacion = trim($_POST['observacion'] ?? '');
        
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        $epp_validos = ['Botas de Seguridad', 'Casco e Iluminación', 'Guantes de Nitrilo/Químicos', 'Máscara de Gas Cloro'];
        if (!in_array($tipo_epp, $epp_validos)) { $mensaje = "error"; }
        elseif (!in_array($entrega, [0, 1]) || !in_array($estado, [0, 1])) { $mensaje = "error"; }
        else {
            $detalles = "Trabajador: $trabajador | EPP: $tipo_epp | Nota: $observacion";
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_inst, $id_user, $fecha, $detalles);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id;
                $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 36, ?, 'Salida')");
                $stmt1->bind_param("isi", $id_reg, $hora, $entrega);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 37, ?, 'Salida')");
                $stmt2->bind_param("isi", $id_reg, $hora, $estado);
                if ($stmt1->execute() && $stmt2->execute()) {
                    $mensaje = "exito";
                }
                $stmt1->close();
                $stmt2->close();
            } else {
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Protección GT-CA-019 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_019.css">
</head>
<body>
    <div id="bubbles"></div>
    <div class="contenedor">
        <h2><i class="fas fa-user-shield"></i> Registro </h2>
        <p style="text-align:center; font-size: 0.8rem; margin-bottom: 15px;">Control de Protección Personal (EPP)</p>

        <?php if($mensaje == "exito"): ?>
            <div class="alerta-exito">
                <i class="fas fa-check-circle"></i> ¡Registro de EPP guardado con éxito!
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Planta / Adscripción:</label>
            <select name="planta" required>
                <option value="">-- Seleccione --</option>
                <?php while($p = mysqli_fetch_assoc($plantas_query)) { echo "<option value='{$p['id']}'>{$p['nombre']}</option>"; } ?>
            </select>

            <label>Nombre del Trabajador:</label>
            <input type="text" name="nombre_trabajador" required placeholder="Nombre y Apellido">

            <label>Equipo / Estatus:</label>
            <div style="display: flex; gap: 10px;">
                <select name="tipo_epp" required style="flex: 1.5;">
                    <option value="Botas de Seguridad">Botas de Seguridad</option>
                    <option value="Casco e Iluminación">Casco e Iluminación</option>
                    <option value="Guantes de Nitrilo/Químicos">Guantes de Trabajo</option>
                    <option value="Máscara de Gas Cloro">Máscara de Gas Cloro</option>
                </select>
                <select name="entrega" required style="flex: 1;">
                    <option value="1">Entregado</option>
                    <option value="0">Pendiente</option>
                </select>
            </div>

            <label>Condición del Equipo:</label>
            <select name="estado" required>
                <option value="1">🆕 Nuevo / Operativo</option>
                <option value="0">♻️ Usado / Requiere Recambio</option>
            </select>

            <label>Observaciones Adicionales:</label>
            <textarea name="observacion" placeholder="Notas sobre la entrega o estado..."></textarea>

            <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Registro EPP</button>
            <a href="index.php?route=registros" class="back">← Volver a Registros</a>
        </form>
    </div>

    <script src="assets/js/registro/gt_ca_019.js"></script>
</body>
</html>