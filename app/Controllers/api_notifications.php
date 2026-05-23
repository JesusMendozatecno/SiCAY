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
        json_success(['count' => (int)$count]);

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

    default:
        json_error('Acción no válida');
}
