<?php
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$uid = intval($_SESSION['id_usuario']);
$action = $_POST['action'] ?? '';

// Fetch user role from DB for fresh checks
$user_role = '';
$rStmt = $con->prepare("SELECT rol FROM usuario WHERE id = ?");
if ($rStmt) {
    $rStmt->bind_param("i", $uid); $rStmt->execute();
    $rRow = $rStmt->get_result()->fetch_assoc();
    $user_role = $rRow['rol'] ?? '';
    $rStmt->close();
}

function is_admin() {
    global $user_role;
    return $user_role === 'Admin';
}

function json_success($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit();
}

function json_error($msg) {
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

// Verify CSRF for non-GET actions
$csrf_actions = ['update_profile','update_password','toggle_2fa','get_sessions','close_session','close_other_sessions',
    'update_config','upload_avatar','delete_avatar','get_activity','get_profile',
    'get_users','update_user_role','delete_user','send_notification','get_global_activity','save_global_config'];
if (in_array($action, $csrf_actions)) {
    verificar_csrf($_POST['csrf_token'] ?? '');
}

switch ($action) {

    case 'get_profile':
        $stmt = $con->prepare("SELECT id, nombre, usuario, correo, rol FROM usuario WHERE id = ?");
        $stmt->bind_param("i", $uid); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc(); $stmt->close();
        json_success(['data' => $user]);

    case 'update_profile':
        $nombre = trim($_POST['nombre'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        if (!validar_nombre($nombre)) json_error('El nombre solo puede contener letras y espacios');
        if (!validar_email($correo)) json_error('Correo no válido');
        $stmt = $con->prepare("UPDATE usuario SET nombre = ?, usuario = ?, correo = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nombre, $usuario, $correo, $uid);
        if ($stmt->execute()) {
            $_SESSION['usuario'] = $usuario;
            log_activity($uid, 'Actualizó su perfil');
            json_success(['message' => 'Perfil actualizado correctamente']);
        }
        json_error('Error al actualizar perfil');

    case 'update_password':
        $current = $_POST['current_password'] ?? '';
        $new_pwd = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new_pwd !== $confirm) json_error('Las contraseñas no coinciden');
        if (strlen($new_pwd) < 6) json_error('La contraseña debe tener al menos 6 caracteres');
        $stmt = $con->prepare("SELECT contraseña FROM usuario WHERE id = ?");
        $stmt->bind_param("i", $uid); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!verificar_pass($current, $row['contraseña'])) json_error('La contraseña actual no es correcta');
        $hash = hash_pass($new_pwd);
        $upd = $con->prepare("UPDATE usuario SET contraseña = ? WHERE id = ?");
        $upd->bind_param("si", $hash, $uid);
        if ($upd->execute()) {
            log_activity($uid, 'Cambió su contraseña');
            json_success(['message' => 'Contraseña actualizada correctamente']);
        }
        json_error('Error al actualizar contraseña');

    case 'toggle_2fa':
        $enabled = intval($_POST['enabled'] ?? 0);
        $stmt = $con->prepare("UPDATE usuario SET two_factor_enabled = ? WHERE id = ?");
        $stmt->bind_param("ii", $enabled, $uid);
        $stmt->execute();
        log_activity($uid, $enabled ? 'Activó 2FA' : 'Desactivó 2FA');
        json_success(['message' => $enabled ? '2FA activado' : '2FA desactivado']);

    case 'get_sessions':
        $stmt = $con->prepare("SELECT id, ip_address, user_agent, created_at, is_current FROM user_sessions WHERE user_id = ? ORDER BY is_current DESC, last_activity DESC LIMIT 20");
        $stmt->bind_param("i", $uid); $stmt->execute();
        $result = $stmt->get_result();
        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        $stmt->close();
        json_success(['sessions' => $sessions]);

    case 'close_session':
        $sid = intval($_POST['session_id'] ?? 0);
        $stmt = $con->prepare("DELETE FROM user_sessions WHERE id = ? AND user_id = ? AND is_current = 0");
        $stmt->bind_param("ii", $sid, $uid);
        $stmt->execute();
        log_activity($uid, 'Cerró una sesión activa');
        json_success(['message' => 'Sesión cerrada']);

    case 'close_other_sessions':
        $stmt = $con->prepare("DELETE FROM user_sessions WHERE user_id = ? AND is_current = 0");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        log_activity($uid, 'Cerró todas las sesiones activas');
        json_success(['message' => 'Sesiones cerradas correctamente']);

    case 'update_config':
        $fields = ['tema', 'language', 'accent_color', 'notification_email', 'notification_system', 'profile_public'];
        $updates = [];
        $types = '';
        $vals = [];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $updates[] = "$f = ?";
                $types .= 's';
                $vals[] = $_POST[$f];
            }
        }
        if (empty($updates)) json_error('Sin cambios');
        $types .= 'i';
        $vals[] = $uid;
        $sql = "UPDATE usuario SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        log_activity($uid, 'Actualizó configuración');
        json_success(['message' => 'Configuración actualizada']);

    case 'upload_avatar':
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            json_error('Error al subir archivo');
        }
        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowedMime)) json_error('Formato no permitido (jpg, png, gif, webp)');
        $extMap = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
        $ext = $extMap[$mime];
        $name = 'user_' . $uid . '_' . time() . '.' . $ext;
        $dest = BASE_PATH . 'public/assets/img/avatars/' . $name;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
            // Delete old avatar
            $stmt = $con->prepare("SELECT avatar FROM usuario WHERE id = ?");
            $stmt->bind_param("i", $uid); $stmt->execute();
            $old = $stmt->get_result()->fetch_assoc()['avatar']; $stmt->close();
            if ($old && $old !== $name) {
                $oldFile = BASE_PATH . 'public/assets/img/avatars/' . $old;
                if (file_exists($oldFile)) unlink($oldFile);
            }
            $stmt = $con->prepare("UPDATE usuario SET avatar = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $uid);
            $stmt->execute();
            log_activity($uid, 'Cambió su foto de perfil');
            json_success(['message' => 'Foto actualizada', 'avatar' => $name]);
        }
        json_error('Error al guardar archivo');

    case 'delete_avatar':
        $stmt = $con->prepare("SELECT avatar FROM usuario WHERE id = ?");
        $stmt->bind_param("i", $uid); $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc()['avatar']; $stmt->close();
        if ($old) {
            $oldFile = BASE_PATH . 'public/assets/img/avatars/' . $old;
            if (file_exists($oldFile)) unlink($oldFile);
        }
        $stmt = $con->prepare("UPDATE usuario SET avatar = NULL WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        log_activity($uid, 'Eliminó su foto de perfil');
        json_success(['message' => 'Foto eliminada']);

    case 'get_activity':
        $page = max(1, intval($_POST['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $stmt = $con->prepare("SELECT action, details, created_at FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $uid, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
        $stmt->close();
        // Check if more
        $stmt2 = $con->prepare("SELECT COUNT(*) as c FROM activity_log WHERE user_id = ?");
        $stmt2->bind_param("i", $uid); $stmt2->execute();
        $total = $stmt2->get_result()->fetch_assoc()['c']; $stmt2->close();
        // Stats
        $stmt3 = $con->prepare("SELECT created_at, last_login_at FROM usuario WHERE id = ?");
        $stmt3->bind_param("i", $uid); $stmt3->execute();
        $u = $stmt3->get_result()->fetch_assoc(); $stmt3->close();
        $member_since = $u['created_at'] ? date("d/m/Y", strtotime($u['created_at'])) : '—';
        $last_login = $u['last_login_at'] ? date("d/m/Y H:i", strtotime($u['last_login_at'])) : '—';
        json_success([
            'activities' => $activities,
            'has_more' => ($offset + $limit) < $total,
            'stats' => ['total' => $total, 'member_since' => $member_since, 'last_login' => $last_login]
        ]);

    // --- ADMIN ACTIONS ---
    case 'get_users':
        if (!is_admin()) json_error('Acceso denegado');
        $search = trim($_POST['search'] ?? '');
        $role_filter = trim($_POST['role'] ?? '');
        $sql = "SELECT id, nombre, correo, usuario, rol, last_login_at FROM usuario WHERE 1=1";
        $types = ''; $params = [];
        if ($search !== '') {
            $sql .= " AND (nombre LIKE ? OR correo LIKE ? OR usuario LIKE ?)";
            $s = "%$search%";
            $types .= 'sss'; $params = [$s, $s, $s];
        }
        if ($role_filter !== '') {
            $sql .= " AND rol = ?";
            $types .= 's'; $params[] = $role_filter;
        }
        $sql .= " ORDER BY id DESC LIMIT 100";
        $stmt = $con->prepare($sql);
        if ($types) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();
        json_success(['users' => $users]);

    case 'update_user_role':
        if (!is_admin()) json_error('Acceso denegado');
        $target_id = intval($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? '';
        $valid_roles = ['Admin', 'Operador'];
        if (!in_array($role, $valid_roles)) json_error('Rol no válido');
        $stmt = $con->prepare("UPDATE usuario SET rol = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $target_id);
        if ($stmt->execute()) {
            log_activity($uid, "Cambió rol del usuario #$target_id a $role");
            json_success(['message' => 'Rol actualizado']);
        }
        json_error('Error al actualizar rol');

    case 'delete_user':
        if (!is_admin()) json_error('Acceso denegado');
        $target_id = intval($_POST['user_id'] ?? 0);
        if ($target_id === $uid) json_error('No puedes eliminarte a ti mismo');
        $stmt = $con->prepare("DELETE FROM usuario WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        if ($stmt->execute()) {
            log_activity($uid, "Eliminó al usuario #$target_id");
            json_success(['message' => 'Usuario eliminado']);
        }
        json_error('Error al eliminar usuario');

    case 'send_notification':
        if (!is_admin()) json_error('Acceso denegado');
        $target_id = intval($_POST['user_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if (!$title || !$message) json_error('Título y mensaje requeridos');
        $stmt = $con->prepare("INSERT INTO notifications (from_user_id, to_user_id, title, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $uid, $target_id, $title, $message);
        if ($stmt->execute()) {
            log_activity($uid, "Envió notificación al usuario #$target_id");
            json_success(['message' => 'Notificación enviada']);
        }
        json_error('Error al enviar notificación');

    case 'get_global_activity':
        if (!is_admin()) json_error('Acceso denegado');
        $stmt = $con->query("SELECT a.*, u.nombre as user_name FROM activity_log a LEFT JOIN usuario u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 100");
        $activities = [];
        while ($row = $stmt->fetch_assoc()) {
            $activities[] = $row;
        }
        json_success(['activities' => $activities]);

    case 'save_global_config':
        if (!is_admin()) json_error('Acceso denegado');
        $configs = ['app_name', 'default_theme', 'security_level', 'maintenance_mode'];
        foreach ($configs as $key) {
            if (isset($_POST[$key])) {
                $val = $_POST[$key];
                $stmt = $con->prepare("UPDATE system_config SET config_value = ? WHERE config_key = ?");
                $stmt->bind_param("ss", $val, $key);
                $stmt->execute();
                $stmt->close();
            }
        }
        log_activity($uid, 'Actualizó configuración global del sistema');
        json_success(['message' => 'Configuración global guardada']);

    default:
        json_error('Acción no válida');
}
