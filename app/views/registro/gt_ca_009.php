<?php
verificar_sesion();
$plantas_query = mysqli_query($con, "SELECT id, nombre FROM instalacion");
$mensaje = "";
$is_ajax = isset($_GET['embed']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['planta', 'caudal_entrada', 'caudal_salida'], $_POST);
    if (!empty($faltantes)) {
        $mensaje = "error";
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Complete todos los campos requeridos.']);
            exit;
        }
    } else {
        $id_inst = intval($_POST['planta']);
        $entrada = $_POST['caudal_entrada'];
        $salida = $_POST['caudal_salida'];
        $id_user = (int) $_SESSION['id_usuario'];
        $fecha = date('Y-m-d'); $hora = date('H:i:s');
        if (!validar_numeric($entrada, 0) || !validar_numeric($salida, 0)) {
            $mensaje = "error";
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Valores numéricos inválidos.']);
                exit;
            }
        } else {
            $stmt = $con->prepare("INSERT INTO registro_diario (id_instalacion, id_usuario, fecha, observaciones_generales) VALUES (?, ?, ?, 'Medición de Caudales Operativos')");
            $stmt->bind_param("iis", $id_inst, $id_user, $fecha);
            if ($stmt->execute()) {
                $id_reg = $stmt->insert_id; $stmt->close();
                $stmt1 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 15, ?, 'Cruda')");
                $stmt1->bind_param("isd", $id_reg, $hora, $entrada);
                $stmt2 = $con->prepare("INSERT INTO medicion_horaria (id_registro_diario, hora, id_parametro, valor_medido, etapa_proceso) VALUES (?, ?, 16, ?, 'Salida')");
                $stmt2->bind_param("isd", $id_reg, $hora, $salida);
                if ($stmt1->execute() && $stmt2->execute()) {
                    $stmt1->close(); $stmt2->close();
                    $mensaje = "exito";
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'success', 'message' => 'Medición de caudal registrada con éxito.']);
                        exit;
                    }
                } else {
                    $stmt1->close(); $stmt2->close();
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'error', 'message' => 'Error al registrar los caudales.']);
                        exit;
                    }
                }
            } else { $stmt->close(); if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Error de base de datos.']);
                exit;
            } }
        }
    }
}
$plantas_data = [];
mysqli_data_seek($plantas_query, 0);
while ($p = mysqli_fetch_assoc($plantas_query)) { $plantas_data[] = $p; }
$registros = mysqli_query($con, "
    SELECT rd.id, rd.fecha, i.nombre as planta,
           m1.valor_medido as entrada, m2.valor_medido as salida
    FROM registro_diario rd
    JOIN instalacion i ON rd.id_instalacion = i.id
    JOIN medicion_horaria m1 ON m1.id_registro_diario = rd.id AND m1.id_parametro = 15
    JOIN medicion_horaria m2 ON m2.id_registro_diario = rd.id AND m2.id_parametro = 16
    ORDER BY rd.fecha DESC, rd.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-009 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_009.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-chart-line"></i> Medición de Caudales <span class="badge-gt">GT-CA-009</span></h2>
        <p class="subtitle">Registro de Caudales de Entrada y Salida (Lps)</p>
    </div>
    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Caudales registrados con éxito!</div>
    <?php elseif ($mensaje == "error"): ?>
        <div class="alerta-error"><i class="fas fa-exclamation-triangle"></i> Error al guardar. Verifique los datos.</div>
    <?php endif; ?>
    <div class="gtca-wrapper">
        <div class="tabla-toolbar">
            <div class="tabla-toolbar-left">
                <div class="tabla-buscar gtca-buscar"><i class="fas fa-search"></i><input type="text" placeholder="Buscar..." autocomplete="off"></div>
                <select class="tabla-filtro gtca-filtrar">
                    <option value="">Todas las plantas</option>
                    <?php foreach ($plantas_data as $p): ?>
                        <option value="<?php echo hsc($p['nombre']); ?>"><?php echo hsc($p['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nueva Medición</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Planta</th><th>Entrada (Lps)</th><th>Salida (Lps)</th><th>Diferencia</th></tr></thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($registros)): ?>
                    <?php $dif = $r['entrada'] - $r['salida']; ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></td>
                        <td><strong><?php echo hsc($r['planta']); ?></strong></td>
                        <td><?php echo number_format((float)($r['entrada'] ?? 0), 1); ?></td>
                        <td><?php echo number_format((float)($r['salida'] ?? 0), 1); ?></td>
                        <td style="color:<?php echo $dif < 0 ? '#e74c3c' : '#2ecc71'; ?>"><?php echo number_format($dif, 1); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="gtca-info tabla-info"></div>
        <div class="gtca-paginar tabla-paginar"></div>
    </div>
</div>

<div class="modal-overlay" id="modalRegistro">
    <div class="modal-contenido">
        <form method="POST" data-route="gt_ca_009">
            <?php echo csrf_field(); ?>
            <div class="modal-header">
                <h3><i class="fas fa-chart-line"></i> <span>Nueva Medición de Caudales</span></h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-campo">
                    <label>Planta / Sistema</label>
                    <select name="planta" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($plantas_data as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo hsc($p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-grid">
                    <div class="modal-campo"><label>Caudal de Entrada (Lps)</label><input type="number" step="0.1" name="caudal_entrada" required placeholder="0.0"></div>
                    <div class="modal-campo"><label>Caudal de Salida (Lps)</label><input type="number" step="0.1" name="caudal_salida" required placeholder="0.0"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar">Guardar Medición</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/registro/gt_ca_table.js"></script>
<script>
(function () {
    for (var i = 0; i < 15; i++) {
        var b = document.createElement('div');
        b.className = 'bubble';
        var size = Math.random() * 50 + 20 + 'px';
        b.style.width = size;
        b.style.height = size;
        b.style.left = Math.random() * 100 + 'vw';
        b.style.animationDuration = Math.random() * 5 + 5 + 's';
        var bubbles = document.getElementById('bubbles');
        if (bubbles) bubbles.appendChild(b);
    }
    var form = document.querySelector('#modalRegistro form');
    if (!form || form._ajaxBound) return;
    form._ajaxBound = true;
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var ruta = form.getAttribute('data-route');
        if (!ruta) return;
        var url = 'index.php?route=' + encodeURIComponent(ruta) + '&embed=1';
        var data = new FormData(form);
        var loading = document.getElementById('regLoading');
        if (loading) loading.style.display = 'flex';
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: data
        })
        .then(function (r) { return r.text(); })
        .then(function (text) {
            if (loading) loading.style.display = 'none';
            var res;
            try { res = JSON.parse(text); } catch (e) {
                var err = document.createElement('div');
                err.className = 'alerta-error';
                err.style.cssText = 'position:fixed;top:100px;right:24px;z-index:9999;box-shadow:0 8px 32px rgba(0,0,0,0.15);';
                err.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error del servidor: ' + text.substring(0, 200);
                document.body.appendChild(err);
                setTimeout(function () { if (err.parentNode) err.parentNode.removeChild(err); }, 8000);
                return;
            }
            if (res.status === 'success') {
                var modal = document.getElementById('modalRegistro');
                if (modal) modal.classList.remove('mostrar');
                form.reset();
                var notif = document.createElement('div');
                notif.className = 'alerta-exito';
                notif.style.cssText = 'position:fixed;top:100px;right:24px;z-index:9999;animation:fadeInRight 0.3s ease;box-shadow:0 8px 32px rgba(0,0,0,0.15);';
                notif.innerHTML = '<i class="fas fa-check-circle"></i> ' + (res.message || 'Registro guardado exitosamente.');
                document.body.appendChild(notif);
                setTimeout(function () { if (notif.parentNode) notif.parentNode.removeChild(notif); }, 4000);
                if (typeof window.reloadRegRoute === 'function') {
                    window.reloadRegRoute();
                }
            } else {
                var err = document.createElement('div');
                err.className = 'alerta-error';
                err.style.cssText = 'position:fixed;top:100px;right:24px;z-index:9999;animation:fadeInRight 0.3s ease;box-shadow:0 8px 32px rgba(0,0,0,0.15);';
                err.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + (res.message || 'Error al procesar los datos.');
                document.body.appendChild(err);
                setTimeout(function () { if (err.parentNode) err.parentNode.removeChild(err); }, 5000);
            }
        })
        .catch(function (err) {
            if (loading) loading.style.display = 'none';
            var errDiv = document.createElement('div');
            errDiv.className = 'alerta-error';
            errDiv.style.cssText = 'position:fixed;top:100px;right:24px;z-index:9999;box-shadow:0 8px 32px rgba(0,0,0,0.15);';
            errDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error de conexión. Intente de nuevo.';
            document.body.appendChild(errDiv);
            setTimeout(function () { if (errDiv.parentNode) errDiv.parentNode.removeChild(errDiv); }, 5000);
        });
    });
})();
</script>
</body>
</html>
