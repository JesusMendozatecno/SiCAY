<?php

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('BASE_URL', 'index.php');

include BASE_PATH . 'config/database.php';
include BASE_PATH . 'app/helpers.php';
require BASE_PATH . 'vendor/autoload.php';

session_init();

// Security Headers
header('Content-Security-Policy: frame-ancestors \'self\'');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

$con = conexion_db();

// Periodic session cleanup (1% chance per request)
if (mt_rand(1, 100) === 1) {
    limpiar_sesiones_expiradas();
}

// Periodic automated backup check (0.5% chance per request)
if (mt_rand(1, 200) === 1) {
    $bk_enabled = get_system_config('backup_enabled', '0');
    if ($bk_enabled === '1') {
        $bk_next = get_system_config('backup_next_run', '');
        if ($bk_next && $bk_next !== 'No programado' && strtotime($bk_next) <= time()) {
            $_local_get = $_GET;
            $_GET['action'] = 'run';
            $_POST = [];
            ob_start();
            include BASE_PATH . 'app/Controllers/backup_auto.php';
            ob_end_clean();
            $_GET = $_local_get;
        }
    }
}

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

// Embed mode: return raw page content without headers/themes/overlay
if (isset($_GET['embed'])) {
    echo $html;
    exit;
}

// Auto-log page views for authenticated users
if (isset($_SESSION['id_usuario'])) {
    $skip_routes = ['index', 'login', 'registro', 'olvide_pass', 'forgot_password', 'reset_password', 'enviar_reset', 'salir', 'iniciar', 'registrar'];
    if (!in_array($route, $skip_routes)) {
        list($accion, $modulo) = route_label($route);
        log_activity($_SESSION['id_usuario'], $accion, null, 'view', $modulo);
    }
}

$v = '?v=' . date('YmdHis');

$viewport = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
$favicon = "\n" . '    <link rel="icon" href="assets/img/EUhOGzfWAAAHZC4-removebg-preview.png" type="image/png">';
$favicon .= "\n" . '    <link rel="shortcut icon" href="assets/img/EUhOGzfWAAAHZC4-removebg-preview.png" type="image/png">';
$favicon .= "\n" . '    <link rel="apple-touch-icon" href="assets/img/EUhOGzfWAAAHZC4-removebg-preview.png">';

$inject = "\n    " . $favicon . "\n";

if (strpos($html, 'name="viewport"') === false && strpos($html, "name='viewport'") === false) {
    $inject .= "\n    " . $viewport . "\n";
}

$responsive_link = '<link rel="stylesheet" href="assets/css/responsive.css' . $v . '">';
$inject .= "\n    " . $responsive_link . "\n";

$html = str_replace('</head>', $inject . '</head>', $html);

$loading_link = '<link rel="stylesheet" href="assets/css/loading.css' . $v . '">';
$html = str_replace('</head>', "\n    " . $loading_link . "\n" . '</head>', $html);

// Theme and accent color injection
$theme_class = '';
$user_tema = 'claro';
$accent_color = '#123C69';
if (isset($_SESSION['usuario'])) {
    $r = $con->query("SELECT tema, accent_color FROM usuario WHERE id = " . intval($_SESSION['id_usuario']));
    if ($r && $f = $r->fetch_assoc()) {
        $user_tema = $f['tema'] ?? 'claro';
        $accent_color = $f['accent_color'] ?? '#123C69';
        if ($user_tema === 'oscuro') {
            $theme_class = ' dark-mode';
        }
    }
}
$accent_style = '<style>:root { --profile-accent: ' . $accent_color . '; }</style>';
$html = str_replace('</head>', "\n" . $accent_style . "\n" . '</head>', $html);
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
    $route_suffix = ($route === 'dashboard') ? ' route-dashboard' : '';
    $body_classes = trim('admin-page' . $theme_class . $route_suffix);
    $body_attrs = 'class="' . $body_classes . '" style="padding-top:85px;"';
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

$heartbeat_js = isset($_SESSION['usuario']) ? '<script src="assets/js/heartbeat.js' . $v . '"></script>' . "\n" : '';
$body_end = $flash_js . "\n" . $heartbeat_js . '<script src="assets/js/loading.js' . $v . '"></script>' . $header_js_link . "\n" . $overlay_html . "\n";

$html = str_replace('</body>', $body_end . '</body>', $html);

$html = preg_replace('/href="assets\/css\/([^"?]+)"/', 'href="assets/css/$1' . $v . '"', $html);
$html = preg_replace('/src="assets\/js\/([^"?]+)"/', 'src="assets/js/$1' . $v . '"', $html);
$html = preg_replace('/href="assets\/fontawesome\/([^"?]+)"/', 'href="assets/fontawesome/$1' . $v . '"', $html);

echo $html;
