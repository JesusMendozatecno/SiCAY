<?php
header('Content-Type: application/json');

verificar_sesion_json();

$action = $_GET['action'] ?? '';
$uid = intval($_SESSION['id_usuario']);
$rol = $_SESSION['rol'] ?? 'Operador';

// --- Auto-migration (runs once) ---
$GLOBALS['_historial_migrated'] = $GLOBALS['_historial_migrated'] ?? false;
if (!$GLOBALS['_historial_migrated']) {
    $GLOBALS['_historial_migrated'] = true;
    $con->query("CREATE TABLE IF NOT EXISTS historial (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT DEFAULT NULL,
        usuario VARCHAR(100) DEFAULT NULL,
        accion VARCHAR(255) DEFAULT NULL,
        tipo_accion VARCHAR(50) DEFAULT 'system',
        modulo VARCHAR(100) DEFAULT NULL,
        descripcion TEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add missing columns if table already existed
    $cols = [];
    $r = $con->query("SHOW COLUMNS FROM historial");
    if ($r) { while($f=$r->fetch_assoc()) $cols[] = $f['Field']; }

    if (!in_array('tipo_accion', $cols)) $con->query("ALTER TABLE historial ADD COLUMN tipo_accion VARCHAR(50) DEFAULT 'system' AFTER accion");
    if (!in_array('descripcion', $cols)) $con->query("ALTER TABLE historial ADD COLUMN descripcion TEXT DEFAULT NULL AFTER modulo");
    if (!in_array('ip_address', $cols)) $con->query("ALTER TABLE historial ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL AFTER descripcion");
    if (!in_array('usuario', $cols)) $con->query("ALTER TABLE historial ADD COLUMN usuario VARCHAR(100) DEFAULT NULL AFTER id_usuario");

    // Migrate activity_log data (once)
    $check = $con->query("SELECT COUNT(*) as c FROM historial WHERE tipo_accion = 'system'");
    if ($check && $check->fetch_assoc()['c'] == 0) {
        $con->query("INSERT INTO historial (id_usuario, usuario, accion, tipo_accion, modulo, descripcion, ip_address, fecha)
            SELECT al.user_id, COALESCE(u.usuario, 'Sistema'), al.action, 'system', NULL, al.details, al.ip_address, al.created_at
            FROM activity_log al
            LEFT JOIN usuario u ON al.user_id = u.id");
    }
}

// ---------- LIST ----------
if ($action === 'list') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 30;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];
    $types = '';

    if (!empty($_GET['usuario'])) {
        $where[] = "h.usuario LIKE ?";
        $params[] = '%' . $_GET['usuario'] . '%';
        $types .= 's';
    }
    if (!empty($_GET['accion'])) {
        $where[] = "h.accion LIKE ?";
        $params[] = '%' . $_GET['accion'] . '%';
        $types .= 's';
    }
    if (!empty($_GET['tipo_accion'])) {
        $where[] = "h.tipo_accion = ?";
        $params[] = $_GET['tipo_accion'];
        $types .= 's';
    }
    if (!empty($_GET['fecha_desde'])) {
        $where[] = "h.fecha >= ?";
        $params[] = $_GET['fecha_desde'] . ' 00:00:00';
        $types .= 's';
    }
    if (!empty($_GET['fecha_hasta'])) {
        $where[] = "h.fecha <= ?";
        $params[] = $_GET['fecha_hasta'] . ' 23:59:59';
        $types .= 's';
    }

    $where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $count_sql = "SELECT COUNT(*) as total FROM historial h $where_sql";
    $stmt = $con->prepare($count_sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = intval($stmt->get_result()->fetch_assoc()['total']);
    $stmt->close();

    // Fetch page
    $data_sql = "SELECT h.* FROM historial h $where_sql ORDER BY h.fecha DESC LIMIT ? OFFSET ?";
    $stmt = $con->prepare($data_sql);
    $all_types = $types . 'ii';
    $all_params = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($all_types, ...$all_params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'data' => $rows,
        'total' => $total,
        'pages' => max(1, ceil($total / $limit)),
        'page' => $page
    ]);
    exit;
}

// ---------- DETAIL ----------
if ($action === 'detail') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['ok' => false, 'error' => 'ID inválido']); exit; }

    $stmt = $con->prepare("SELECT h.*, u.usuario as uname FROM historial h LEFT JOIN usuario u ON h.id_usuario = u.id WHERE h.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        echo json_encode(['ok' => true, 'data' => $row]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Registro no encontrado']);
    }
    exit;
}

// ---------- STATS ----------
if ($action === 'stats') {
    $stats = [];
    $r = $con->query("SELECT COUNT(*) as c FROM historial");
    $stats['total'] = intval($r->fetch_assoc()['c']);

    $r = $con->query("SELECT COUNT(*) as c FROM historial WHERE DATE(fecha) = CURDATE()");
    $stats['today'] = intval($r->fetch_assoc()['c']);

    $r = $con->query("SELECT COUNT(*) as c FROM historial WHERE fecha >= NOW() - INTERVAL 7 DAY");
    $stats['week'] = intval($r->fetch_assoc()['c']);

    $r = $con->query("SELECT COUNT(*) as c FROM historial WHERE YEAR(fecha) = YEAR(NOW()) AND MONTH(fecha) = MONTH(NOW())");
    $stats['month'] = intval($r->fetch_assoc()['c']);

    echo json_encode(['ok' => true, 'data' => $stats]);
    exit;
}

// ---------- EXPORT PDF ----------
if ($action === 'export_pdf') {
    $fecha_desde = $_GET['fecha_desde'] ?? '';
    $fecha_hasta = $_GET['fecha_hasta'] ?? '';
    $tipo_accion = $_GET['tipo_accion'] ?? '';
    $usuario = $_GET['usuario'] ?? '';

    $where = []; $params = []; $types = '';
    if ($fecha_desde) { $where[] = "h.fecha >= ?"; $params[] = $fecha_desde . ' 00:00:00'; $types .= 's'; }
    if ($fecha_hasta) { $where[] = "h.fecha <= ?"; $params[] = $fecha_hasta . ' 23:59:59'; $types .= 's'; }
    if ($tipo_accion) { $where[] = "h.tipo_accion = ?"; $params[] = $tipo_accion; $types .= 's'; }
    if ($usuario) { $where[] = "h.usuario LIKE ?"; $params[] = '%' . $usuario . '%'; $types .= 's'; }
    $where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $con->prepare("SELECT h.* FROM historial h $where_sql ORDER BY h.fecha DESC LIMIT 5000");
    if ($params) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Generate HTML for PDF
    $html = '<html><head><meta charset="utf-8"><style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        h1 { text-align: center; color: #123C69; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #123C69; color: white; padding: 8px; text-align: left; font-size: 10px; }
        td { padding: 6px 8px; border-bottom: 1px solid #ddd; font-size: 10px; }
        tr:nth-child(even) td { background: #f5f5f5; }
        .fecha { color: #888; font-size: 9px; }
        .header-info { text-align: center; margin-bottom: 15px; color: #666; font-size: 10px; }
        @page { margin: 15mm; }
    </style></head><body>
    <h1>Historial de Actividades - SICAY</h1>
    <div class="header-info">Generado el ' . date('d/m/Y H:i') . ' | Total: ' . count($rows) . ' registros</div>
    <table>
        <thead><tr>
            <th>Fecha</th><th>Usuario</th><th>Acción</th><th>Tipo</th><th>Módulo</th><th>IP</th>
        </tr></thead><tbody>';

    foreach ($rows as $r) {
        $fecha = date('d/m/Y H:i', strtotime($r['fecha']));
        $usuario = htmlspecialchars($r['usuario'] ?? 'Sistema');
        $accion = htmlspecialchars($r['accion']);
        $tipo = htmlspecialchars($r['tipo_accion'] ?? '-');
        $modulo = htmlspecialchars($r['modulo'] ?? '-');
        $ip = htmlspecialchars($r['ip_address'] ?? '-');
        $html .= "<tr><td class='fecha'>$fecha</td><td>$usuario</td><td>$accion</td><td>$tipo</td><td>$modulo</td><td>$ip</td></tr>";
    }

    $html .= '</tbody></table></body></html>';

    echo json_encode(['ok' => true, 'html' => $html]);
    exit;
}

// ---------- CLEANUP (Admin only) ----------
if ($action === 'cleanup') {
    if ($rol !== 'Admin') {
        echo json_encode(['ok' => false, 'error' => 'Solo administradores pueden realizar limpieza']);
        exit;
    }

    $periodo = $_POST['periodo'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verificar_csrf($csrf)) {
        echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido']);
        exit;
    }

    $sql = '';
    $label = '';
    switch ($periodo) {
        case 'hora':
            $sql = "DELETE FROM historial WHERE fecha >= NOW() - INTERVAL 1 HOUR";
            $label = 'la última hora';
            break;
        case 'dia':
            $sql = "DELETE FROM historial WHERE fecha >= NOW() - INTERVAL 1 DAY";
            $label = 'el último día';
            break;
        case 'mes':
            $sql = "DELETE FROM historial WHERE fecha >= NOW() - INTERVAL 1 MONTH";
            $label = 'el último mes';
            break;
        case 'ano':
            $sql = "DELETE FROM historial WHERE fecha >= NOW() - INTERVAL 1 YEAR";
            $label = 'el último año';
            break;
        case 'total':
            $sql = "DELETE FROM historial";
            $label = 'todo el historial';
            break;
        default:
            echo json_encode(['ok' => false, 'error' => 'Período inválido']);
            exit;
    }

    $deleted = 0;
    if ($con->query($sql)) {
        $deleted = $con->affected_rows;
    }

    log_activity($uid, "Limpieza de historial ($label)", "Eliminó $deleted registros del historial correspondientes a $label", 'admin', 'Historial');

    echo json_encode(['ok' => true, 'message' => "Limpieza completada: $deleted registros eliminados de $label", 'deleted' => $deleted]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
