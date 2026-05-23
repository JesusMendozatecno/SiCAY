<?php
if (isset($_SESSION['id_usuario'])) {
    $sid = session_id();
    global $con;
    if ($con) {
        $stmt = $con->prepare("UPDATE user_sessions SET is_current = 0 WHERE session_id = ?");
        $stmt->bind_param("s", $sid); $stmt->execute(); $stmt->close();
        $stmt2 = $con->prepare("DELETE FROM user_sessions WHERE user_id = ? AND is_current = 0 AND last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt2->bind_param("i", $_SESSION['id_usuario']); $stmt2->execute(); $stmt2->close();
        log_activity($_SESSION['id_usuario'], 'Cerró sesión');
    }
}
$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
redirigir("index");
