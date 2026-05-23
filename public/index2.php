<?php

include_once dirname(__DIR__) . '/config/database.php';
include_once dirname(__DIR__) . '/app/helpers.php';

session_init();

$con = conexion_db();

$routes = include dirname(__DIR__) . '/routes/web.php';

$route = $_GET['route'] ?? 'dashboard';

if (isset($routes[$route])) {
    $view_file = dirname(__DIR__) . '/' . $routes[$route];
    if (file_exists($view_file)) {
        include $view_file;
        exit();
    }
}

include dirname(__DIR__) . '/app/views/404.php';
