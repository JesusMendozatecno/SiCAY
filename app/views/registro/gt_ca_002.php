<?php
verificar_sesion();
$sustancias_query = mysqli_query($con, "SELECT id, nombre, unidad_medida FROM sustancia_quimica");
$mensaje = "";
$is_ajax = isset($_GET['embed']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $faltantes = validar_requeridos(['sustancia', 'tipo', 'cantidad'], $_POST);
    if (!empty($faltantes)) {
        $mensaje = "error";
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Complete todos los campos requeridos.']);
            exit;
        }
    } else {
        $id_sustancia = intval($_POST['sustancia']);
        $tipo = $_POST['tipo'];
        $cantidad = $_POST['cantidad'];
        $referencia = trim($_POST['referencia'] ?? '');
        $tipos_validos = ['Entrada', 'Salida', 'Ajuste'];
        if (!in_array($tipo, $tipos_validos)) {
            $mensaje = "error";
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Tipo de movimiento inválido.']);
                exit;
            }
        } elseif (!validar_numeric($cantidad, 0)) {
            $mensaje = "error";
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Cantidad inválida.']);
                exit;
            }
        } else {
            $stmt = $con->prepare("INSERT INTO inventario_movimiento (id_sustancia, tipo_movimiento, cantidad, referencia_guia) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $id_sustancia, $tipo, $cantidad, $referencia);
            if ($stmt->execute()) {
                $stmt->close();
                $mensaje = "exito";
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'message' => 'Movimiento registrado con éxito.']);
                    exit;
                }
            } else {
                $stmt->close();
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => 'Error de base de datos.']);
                    exit;
                }
            }
        }
    }
}

$sustancias_data = [];
mysqli_data_seek($sustancias_query, 0);
while ($s = mysqli_fetch_assoc($sustancias_query)) { $sustancias_data[] = $s; }

$movimientos = mysqli_query($con, "
    SELECT im.id, im.fecha_movimiento, im.tipo_movimiento, im.cantidad, im.referencia_guia,
           s.nombre as sustancia, s.unidad_medida
    FROM inventario_movimiento im
    JOIN sustancia_quimica s ON im.id_sustancia = s.id
    ORDER BY im.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GT-CA-002 - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/tabla.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca.css">
    <link rel="stylesheet" href="assets/css/registro/gt_ca_002.css">
</head>
<body>
<div id="bubbles"></div>
<div class="contenedor">
    <div class="page-hdr">
        <h2><i class="fas fa-boxes"></i> Inventario Químico <span class="badge-gt">GT-CA-002</span></h2>
        <p class="subtitle">Control de Entradas, Salidas y Ajustes de Sustancias Químicas</p>
    </div>

    <?php if ($mensaje == "exito"): ?>
        <div class="alerta-exito"><i class="fas fa-check-circle"></i> ¡Movimiento registrado con éxito!</div>
    <?php elseif ($mensaje == "error"): ?>
        <div class="alerta-error"><i class="fas fa-exclamation-triangle"></i> Error al registrar. Verifique los datos.</div>
    <?php endif; ?>

    <div class="gtca-wrapper">
        <div class="tabla-toolbar">
            <div class="tabla-toolbar-left">
                <div class="tabla-buscar gtca-buscar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar..." autocomplete="off">
                </div>
                <select class="tabla-filtro gtca-filtrar">
                    <option value="">Todas las sustancias</option>
                    <?php foreach ($sustancias_data as $s): ?>
                        <option value="<?php echo hsc($s['nombre']); ?>"><?php echo hsc($s['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="tabla-btn-agregar" data-modal-open="modalRegistro"><i class="fas fa-plus"></i> Nuevo Movimiento</button>
        </div>
        <div class="tabla-wrapper">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Sustancia</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($movimientos)): ?>
                    <tr>
                        <td><?php echo $r['fecha_movimiento'] ? date('d/m/Y H:i', strtotime($r['fecha_movimiento'])) : '-'; ?></td>
                        <td><strong><?php echo hsc($r['sustancia']); ?></strong> (<?php echo hsc($r['unidad_medida']); ?>)</td>
                        <td><span style="color:<?php echo $r['tipo_movimiento']=='Entrada'?'#2ecc71':($r['tipo_movimiento']=='Salida'?'#e74c3c':'#f1c40f'); ?>"><?php echo hsc($r['tipo_movimiento']); ?></span></td>
                        <td><?php echo number_format($r['cantidad'], 2); ?></td>
                        <td><?php echo hsc($r['referencia_guia'] ?? '-'); ?></td>
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
        <form method="POST" data-route="gt_ca_002">
            <?php echo csrf_field(); ?>
            <div class="modal-header">
                <h3><i class="fas fa-boxes"></i> <span>Nuevo Movimiento de Inventario</span></h3>
                <button type="button" class="modal-cerrar" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-campo">
                    <label>Sustancia Química</label>
                    <select name="sustancia" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($sustancias_data as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo hsc($s['nombre']); ?> (<?php echo hsc($s['unidad_medida']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Tipo de Movimiento</label>
                    <select name="tipo" required>
                        <option value="Entrada">Entrada (Ingreso)</option>
                        <option value="Salida">Salida (Consumo)</option>
                        <option value="Ajuste">Ajuste de Inventario</option>
                    </select>
                </div>
                <div class="modal-campo">
                    <label>Cantidad</label>
                    <input type="number" step="0.01" name="cantidad" required placeholder="0.00">
                </div>
                <div class="modal-campo">
                    <label>Nro. Guía / Referencia</label>
                    <input type="text" name="referencia" placeholder="Ej: G-5542">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar-modal" data-modal-close>Cancelar</button>
                <button type="submit" name="guardar" class="btn-guardar">Registrar Movimiento</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/registro/gt_ca_table.js"></script>
<script>
(function () {
    // ── Burbujas ──
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

    // ── Envío AJAX del formulario ──
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
                notif.innerHTML = '<i class="fas fa-check-circle"></i> ' + (res.message || 'Movimiento registrado con éxito.');
                document.body.appendChild(notif);
                setTimeout(function () { if (notif.parentNode) notif.parentNode.removeChild(notif); }, 4000);

                if (typeof window.reloadRegRoute === 'function') {
                    window.reloadRegRoute();
                }
            } else {
                var err = document.createElement('div');
                err.className = 'alerta-error';
                err.style.cssText = 'position:fixed;top:100px;right:24px;z-index:9999;animation:fadeInRight 0.3s ease;box-shadow:0 8px 32px rgba(0,0,0,0.15);';
                err.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + (res.message || 'Error al registrar.');
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
