<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'error' => 'no_session']);
    exit;
}

actualizar_sesion();

echo json_encode(['ok' => true, 't' => $_SESSION['last_activity']]);
