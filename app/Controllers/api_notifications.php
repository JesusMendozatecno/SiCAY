<?php
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$uid = intval($_SESSION['id_usuario']);
$action = $_REQUEST['action'] ?? '';

function json_success($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit();
}

function json_error($msg) {
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

switch ($action) {

    case 'list':
        $stmt = $con->prepare("SELECT id, title, message, read_at, created_at FROM notifications WHERE to_user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
        $stmt2 = $con->prepare("SELECT COUNT(*) as c FROM notifications WHERE to_user_id = ? AND read_at IS NULL");
        $stmt2->bind_param("i", $uid);
        $stmt2->execute();
        $unread = $stmt2->get_result()->fetch_assoc()['c'];
        $stmt2->close();
        json_success(['notifications' => $notifications, 'unread_count' => (int)$unread]);

    case 'count':
        $stmt = $con->prepare("SELECT COUNT(*) as c FROM notifications WHERE to_user_id = ? AND read_at IS NULL");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
        $last = null;
        $stmt2 = $con->prepare("SELECT id, title FROM notifications WHERE to_user_id = ? AND read_at IS NULL ORDER BY created_at DESC LIMIT 1");
        $stmt2->bind_param("i", $uid);
        $stmt2->execute();
        $lastRow = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        if ($lastRow) $last = ['id' => (int)$lastRow['id'], 'title' => $lastRow['title']];
        json_success(['count' => (int)$count, 'last' => $last]);

    case 'mark_read':
        $nid = intval($_POST['id'] ?? 0);
        $stmt = $con->prepare("UPDATE notifications SET read_at = NOW() WHERE id = ? AND to_user_id = ?");
        $stmt->bind_param("ii", $nid, $uid);
        $stmt->execute();
        json_success(['message' => 'Marcada como leída']);

    case 'mark_all_read':
        $stmt = $con->prepare("UPDATE notifications SET read_at = NOW() WHERE to_user_id = ? AND read_at IS NULL");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        json_success(['message' => 'Todas marcadas como leídas']);

    case 'detail':
        $nid = intval($_GET['id'] ?? 0);
        $stmt = $con->prepare("SELECT n.id, n.title, n.message, n.read_at, n.created_at, n.from_user_id,
                                      u.usuario as sender_name, u.correo as sender_email
                               FROM notifications n
                               LEFT JOIN usuario u ON n.from_user_id = u.id
                               WHERE n.id = ? AND n.to_user_id = ?");
        $stmt->bind_param("ii", $nid, $uid);
        $stmt->execute();
        $notif = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$notif) json_error('Notificación no encontrada');
        // Mark as read when viewing details
        $stmt2 = $con->prepare("UPDATE notifications SET read_at = NOW() WHERE id = ? AND to_user_id = ? AND read_at IS NULL");
        $stmt2->bind_param("ii", $nid, $uid);
        $stmt2->execute();
        $stmt2->close();
        json_success(['notification' => $notif]);

    case 'reply':
        $nid = intval($_POST['id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if (!$message) json_error('El mensaje no puede estar vacío');
        // Get original notification to find the sender
        $stmt = $con->prepare("SELECT from_user_id, title FROM notifications WHERE id = ? AND to_user_id = ?");
        $stmt->bind_param("ii", $nid, $uid);
        $stmt->execute();
        $orig = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$orig) json_error('Notificación no encontrada');
        if (!$orig['from_user_id']) json_error('No se puede responder a notificaciones del sistema');
        // Create reply notification to the original sender
        $reply_title = 'Respuesta: ' . $orig['title'];
        $stmt2 = $con->prepare("INSERT INTO notifications (from_user_id, to_user_id, title, message) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("iiss", $uid, $orig['from_user_id'], $reply_title, $message);
        $stmt2->execute();
        $stmt2->close();
        json_success(['message' => 'Respuesta enviada']);

    case 'delete':
        $nid = intval($_POST['id'] ?? 0);
        if (!$nid) json_error('ID requerido');
        $stmt = $con->prepare("DELETE FROM notifications WHERE id = ? AND to_user_id = ?");
        $stmt->bind_param("ii", $nid, $uid);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            json_success(['message' => 'Notificación eliminada']);
        }
        json_error('No se pudo eliminar la notificación');

    default:
        json_error('Acción no válida');
}
