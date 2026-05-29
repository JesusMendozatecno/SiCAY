<?php
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$uid = intval($_SESSION['id_usuario']);
$stmt = $con->prepare("SELECT rol FROM usuario WHERE id = ?");
$stmt->bind_param("i", $uid); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (($user['rol'] ?? '') !== 'Admin') {
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado. Solo administradores.']);
    exit;
}

$backup_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
$mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";

function ensure_config_keys() {
    global $con;
    $keys = ['backup_enabled','backup_frequency','backup_retention','backup_last_run','backup_last_file','backup_next_run'];
    foreach ($keys as $k) {
        $stmt = $con->prepare("SELECT COUNT(*) as c FROM system_config WHERE config_key = ?");
        $stmt->bind_param("s", $k); $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (($r['c'] ?? 0) == 0) {
            $defaults = [
                'backup_enabled' => '0',
                'backup_frequency' => 'daily',
                'backup_retention' => '10',
                'backup_last_run' => 'Nunca',
                'backup_last_file' => '',
                'backup_next_run' => 'No programado'
            ];
            $v = $defaults[$k] ?? '';
            $ins = $con->prepare("INSERT INTO system_config (config_key, config_value) VALUES (?, ?)");
            $ins->bind_param("ss", $k, $v);
            $ins->execute(); $ins->close();
        }
    }
}

function ejecutar_respaldo($backup_dir, $mysqldump) {
    global $con;
    if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

    $fecha = date("Y-m-d_H-i-s");
    $archivo_sql = $backup_dir . "respaldo_SICAY_{$fecha}.sql";
    $archivo_zip = $backup_dir . "respaldo_SICAY_{$fecha}.zip";

    $comando = sprintf(
        '"%s" -h %s -u %s %s > "%s"',
        $mysqldump,
        escapeshellarg(DB_SERVER),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_NAME),
        $archivo_sql
    );

    system($comando, $resultado);
    if ($resultado !== 0) {
        return ['ok' => false, 'error' => 'Error al ejecutar mysqldump (código ' . $resultado . ')'];
    }

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($archivo_zip, ZipArchive::CREATE) === true) {
            $zip->addFile($archivo_sql, "respaldo_SICAY_{$fecha}.sql");
            $zip->close();
            unlink($archivo_sql);
            $archivo_final = $archivo_zip;
            $nombre_final = "respaldo_SICAY_{$fecha}.zip";
        } else {
            $archivo_final = $archivo_sql;
            $nombre_final = "respaldo_SICAY_{$fecha}.sql";
        }
    } else {
        $archivo_final = $archivo_sql;
        $nombre_final = "respaldo_SICAY_{$fecha}.sql";
    }

    $stmt = $con->prepare("UPDATE system_config SET config_value = NOW() WHERE config_key = 'backup_last_run'");
    if ($stmt) { $stmt->execute(); $stmt->close(); }

    $stmt = $con->prepare("UPDATE system_config SET config_value = ? WHERE config_key = 'backup_last_file'");
    if ($stmt) { $stmt->bind_param("s", $nombre_final); $stmt->execute(); $stmt->close(); }

    $freq = get_system_config('backup_frequency', 'daily');
    switch ($freq) {
        case 'hourly': $interval = '+1 hour'; break;
        case 'daily': $interval = '+1 day'; break;
        case 'weekly': $interval = '+1 week'; break;
        case 'monthly': $interval = '+1 month'; break;
        default: $interval = '+1 day';
    }
    $next = date('Y-m-d H:i:s', strtotime($interval));
    $stmt = $con->prepare("UPDATE system_config SET config_value = ? WHERE config_key = 'backup_next_run'");
    if ($stmt) { $stmt->bind_param("s", $next); $stmt->execute(); $stmt->close(); }

    $retention = intval(get_system_config('backup_retention', '10'));
    if ($retention > 0) {
        $files = glob($backup_dir . "respaldo_SICAY_*");
        usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
        while (count($files) > $retention) {
            $oldest = array_pop($files);
            if ($oldest && file_exists($oldest)) unlink($oldest);
        }
    }

    log_activity($uid, "Respaldo automático completado: $nombre_final", null, 'system', 'Backup');

    return ['ok' => true, 'file' => $nombre_final, 'size' => filesize($archivo_final)];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
ensure_config_keys();

switch ($action) {

    case 'run':
        $r = ejecutar_respaldo($backup_dir, $mysqldump);
        echo json_encode($r);
        exit;

    case 'status':
        $last_run = get_system_config('backup_last_run', 'Nunca');
        $next_run = get_system_config('backup_next_run', 'No programado');
        $last_file = get_system_config('backup_last_file', '');
        $enabled = get_system_config('backup_enabled', '0');
        $frequency = get_system_config('backup_frequency', 'daily');
        $retention = get_system_config('backup_retention', '10');

        $files = is_dir($backup_dir) ? glob($backup_dir . "respaldo_SICAY_*") : [];
        $total_size = 0;
        $backup_list = [];
        foreach ($files as $f) {
            $s = filesize($f);
            $total_size += $s;
            $backup_list[] = [
                'name' => basename($f),
                'size' => $s,
                'size_hr' => $s > 1048576 ? round($s / 1048576, 2) . ' MB' : round($s / 1024, 2) . ' KB',
                'date' => date("Y-m-d H:i:s", filemtime($f))
            ];
        }
        rsort($backup_list);

        echo json_encode([
            'ok' => true,
            'enabled' => $enabled === '1',
            'last_run' => $last_run,
            'next_run' => $next_run,
            'last_file' => $last_file,
            'frequency' => $frequency,
            'retention' => intval($retention),
            'total_backups' => count($backup_list),
            'total_size' => $total_size,
            'total_size_hr' => $total_size > 1048576 ? round($total_size / 1048576, 2) . ' MB' : round($total_size / 1024, 2) . ' KB',
            'backups' => array_slice($backup_list, 0, 30)
        ]);
        exit;

    case 'save_config':
        verificar_csrf($_POST['csrf_token'] ?? '');
        $configs = ['backup_enabled', 'backup_frequency', 'backup_retention'];
        foreach ($configs as $key) {
            if (isset($_POST[$key])) {
                $val = $_POST[$key];
                $stmt = $con->prepare("UPDATE system_config SET config_value = ? WHERE config_key = ?");
                if ($stmt) { $stmt->bind_param("ss", $val, $key); $stmt->execute(); $stmt->close(); }
            }
        }
        $enabled = get_system_config('backup_enabled', '0');
        if ($enabled === '1') {
            $freq = get_system_config('backup_frequency', 'daily');
            switch ($freq) {
                case 'hourly': $interval = '+1 hour'; break;
                case 'daily': $interval = '+1 day'; break;
                case 'weekly': $interval = '+1 week'; break;
                case 'monthly': $interval = '+1 month'; break;
                default: $interval = '+1 day';
            }
            $next = date('Y-m-d H:i:s', strtotime($interval));
            $stmt = $con->prepare("UPDATE system_config SET config_value = ? WHERE config_key = 'backup_next_run'");
            if ($stmt) { $stmt->bind_param("s", $next); $stmt->execute(); $stmt->close(); }
        }
        log_activity($uid, 'Configuración de respaldo automático actualizada', null, 'config', 'Backup');
        echo json_encode(['ok' => true, 'message' => 'Configuración guardada correctamente']);
        exit;

    case 'download':
        $file = basename($_GET['file'] ?? '');
        if (!$file || !preg_match('/^respaldo_SICAY_.+\.(sql|zip)$/', $file)) {
            echo json_encode(['ok' => false, 'error' => 'Archivo inválido']);
            exit;
        }
        $path = $backup_dir . $file;
        if (!file_exists($path)) {
            echo json_encode(['ok' => false, 'error' => 'Archivo no encontrado']);
            exit;
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;

    case 'delete':
        verificar_csrf($_POST['csrf_token'] ?? '');
        $file = basename($_POST['file'] ?? '');
        if (!$file || !preg_match('/^respaldo_SICAY_.+\.(sql|zip)$/', $file)) {
            echo json_encode(['ok' => false, 'error' => 'Archivo inválido']);
            exit;
        }
        $path = $backup_dir . $file;
        if (file_exists($path) && unlink($path)) {
            log_activity($uid, "Eliminó respaldo: $file", null, 'delete', 'Backup');
            echo json_encode(['ok' => true, 'message' => 'Archivo eliminado']);
        } else {
            echo json_encode(['ok' => false, 'error' => 'No se pudo eliminar']);
        }
        exit;

    default:
        echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
        exit;
}
