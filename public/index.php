<?php

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('BASE_URL', 'index.php');

include BASE_PATH . 'config/database.php';
include BASE_PATH . 'app/helpers.php';

session_init();

$con = conexion_db();

$routes = include BASE_PATH . 'routes/web.php';

$route = $_GET['route'] ?? 'index';

// API routes: skip output buffering and HTML injection
if (strpos($route, 'api_') === 0) {
    if (isset($routes[$route])) {
        $api_file = BASE_PATH . $routes[$route];
        if (file_exists($api_file)) {
            include $api_file;
        }
    }
    exit();
}

// Load user language preference and accent color
$current_lang = 'es';
$accent_color = '#123C69';
$GLOBALS['current_lang'] = 'es';
if (isset($_SESSION['usuario']) && isset($_SESSION['id_usuario'])) {
    $r = $con->query("SELECT language, accent_color FROM usuario WHERE id = " . intval($_SESSION['id_usuario']));
    if ($r && $f = $r->fetch_assoc()) {
        $current_lang = $f['language'] ?? 'es';
        $accent_color = $f['accent_color'] ?? '#123C69';
        $GLOBALS['current_lang'] = $current_lang;
    }
}

// Maintenance mode check
$public_routes = ['index', 'login', 'registro', 'olvide_pass', 'forgot_password', 'reset_password', 'enviar_reset', 'salir'];
if (is_maintenance_mode() && !in_array($route, $public_routes) && isset($_SESSION['usuario'])) {
    $user_role = $_SESSION['rol'] ?? '';
    if ($user_role !== 'Admin') {
        session_destroy();
        include BASE_PATH . 'app/views/inicio/mantenimiento.php';
        $html = ob_get_clean();
        echo $html;
        exit();
    }
}

ob_start();

if (isset($routes[$route])) {
    $view_file = BASE_PATH . $routes[$route];
    if (file_exists($view_file)) {
        include $view_file;
    } else {
        include BASE_PATH . 'app/views/404.php';
    }
} else {
    include BASE_PATH . 'app/views/404.php';
}

$html = ob_get_clean();

$v = '?v=' . date('YmdHi');

$viewport = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
$responsive_link = '<link rel="stylesheet" href="assets/css/responsive.css' . $v . '">';

$inject = "\n    " . $responsive_link . "\n";

if (strpos($html, 'name="viewport"') === false && strpos($html, "name='viewport'") === false) {
    $inject = "\n    " . $viewport . "\n    " . $responsive_link . "\n";
}

$html = str_replace('</head>', $inject . '</head>', $html);

$loading_link = '<link rel="stylesheet" href="assets/css/loading.css' . $v . '">';
$html = str_replace('</head>', "\n    " . $loading_link . "\n" . '</head>', $html);

// Theme and accent color injection
$theme_class = '';
$user_tema = 'claro';
if (isset($_SESSION['usuario'])) {
    $r = $con->query("SELECT tema, accent_color FROM usuario WHERE id = " . intval($_SESSION['id_usuario']));
    if ($r && $f = $r->fetch_assoc()) {
        $user_tema = $f['tema'] ?? 'claro';
        $accent_color = $f['accent_color'] ?? '#123C69';
        if ($user_tema === 'oscuro') {
            $theme_class = ' dark-mode';
        }
    }
    $accent_style = '<style>:root { --profile-accent: ' . $accent_color . '; }</style>';
    $html = str_replace('</head>', "\n" . $accent_style . "\n" . '</head>', $html);
}
$theme_link = "\n" . '    <link rel="stylesheet" href="assets/css/theme.css' . $v . '">';
$html = str_replace('</head>', $theme_link . "\n" . '</head>', $html);

// --- Global header for authenticated users ---
$header_html = '';
$header_css_link = '';
$header_js_link = '';
if (isset($_SESSION['usuario'])) {
    $header_css_link = "\n" . '    <link rel="stylesheet" href="assets/css/header.css' . $v . '">';
    $header_js_link = "\n" . '<script src="assets/js/header.js' . $v . '"></script>';
    ob_start();
    include BASE_PATH . 'app/views/partials/header.php';
    $header_html = ob_get_clean();
    $body_classes = trim('admin-page' . $theme_class);
    $body_attrs = 'class="' . $body_classes . '" style="padding-top:60px;"';
    $html = str_replace('<body>', '<body ' . $body_attrs . '>' . "\n" . $header_html, $html);
}
$html = str_replace('</head>', $header_css_link . "\n" . '</head>', $html);

// --- Flash messages and loading overlay ---
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$flash_js = '<script>var flashSuccess = ' . json_encode($flash_success) . '; var flashError = ' . json_encode($flash_error) . ';</script>';

$overlay_html = '
<div id="loading-overlay">
    <div class="loading-card">
        <div class="loading-icon"><i class="fas fa-spinner fa-spin"></i></div>
        <p class="loading-text">Procesando...</p>
    </div>
</div>';

$body_end = $flash_js . "\n" . '<script src="assets/js/loading.js' . $v . '"></script>' . $header_js_link . "\n" . $overlay_html . "\n";

$html = str_replace('</body>', $body_end . '</body>', $html);

$html = preg_replace('/href="assets\/css\/([^"?]+)"/', 'href="assets/css/$1' . $v . '"', $html);
$html = preg_replace('/src="assets\/js\/([^"?]+)"/', 'src="assets/js/$1' . $v . '"', $html);
$html = preg_replace('/href="assets\/fontawesome\/([^"?]+)"/', 'href="assets/fontawesome/$1' . $v . '"', $html);

echo $html;
