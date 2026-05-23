<?php

define('DB_SERVER', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'SICAY');

function conexion_db() {
    static $conexion = null;
    if ($conexion === null) {
        $conexion = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
        if ($conexion->connect_error) {
            die("Error de conexión: " . $conexion->connect_error);
        }
        $conexion->set_charset("utf8");
    }
    return $conexion;
}
