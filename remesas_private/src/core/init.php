<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

$cookie_lifetime = 60 * 60 * 24 * 30;

$session_lifetime = 4 * 60 * 60;
ini_set('session.gc_maxlifetime', $session_lifetime);

session_set_cookie_params([
    'lifetime' => $cookie_lifetime,
    'path' => '/',
    'domain' => defined('SESSION_DOMAIN') ? SESSION_DOMAIN : $_SERVER['HTTP_HOST'],
    'secure' => defined('IS_HTTPS') ? IS_HTTPS : isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

header('X-Content-Type-Options: nosniff');

$cspHost = '';
if (defined('BASE_URL')) {
    $parsedUrl = parse_url(BASE_URL);
    if (isset($parsedUrl['host'])) {
        $host = $parsedUrl['host'];
        $scheme = $parsedUrl['scheme'] ?? 'https';
        if (strpos($host, 'www.') === 0) {
            $cspHost = $scheme . '://' . $host . ' ' . $scheme . '://' . substr($host, 4);
        } else {
            $cspHost = $scheme . '://' . $host . ' ' . $scheme . '://www.' . $host;
        }
    }
}

$cspDirectives = [
    "default-src 'self'",
    "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'",
    "style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'",
    "font-src 'self' https://cdn.jsdelivr.net",
    "img-src 'self' data:",
    "frame-src 'self' http://googleusercontent.com/maps.google.com/ https://www.google.com/",
    "connect-src 'self' " . $cspHost . " https://cdn.jsdelivr.net",
    "object-src 'none'",
    "frame-ancestors 'self'",
    "base-uri 'self'",
    "form-action 'self'"
];
$cspHeader = "Content-Security-Policy: " . implode('; ', $cspDirectives);
header($cspHeader);

session_start();

require_once __DIR__ . '/ErrorHandler.php';
set_exception_handler('App\\Core\\exception_handler');

$tiempo_limite = $session_lifetime;

if (isset($_SESSION['user_id'])) {
    
    if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > $tiempo_limite)) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php?session_expired=1');
        exit();
    }
    $_SESSION['ultima_actividad'] = time();

    $is_admin_or_operador = (isset($_SESSION['user_rol_name']) && ($_SESSION['user_rol_name'] === 'Admin' || $_SESSION['user_rol_name'] === 'Operador'));
    $two_fa_enabled = (isset($_SESSION['twofa_enabled']) && $_SESSION['twofa_enabled'] == 1);

    if ($is_admin_or_operador && $two_fa_enabled) {
        
        $two_fa_grace_period = $tiempo_limite; 
        
        $current_page = basename($_SERVER['SCRIPT_NAME']);
        $is_on_auth_page = in_array($current_page, ['verify-2fa.php', 'logout.php']);
        $is_api_call = (strpos($_SERVER['REQUEST_URI'], '/api/') !== false);

        if (isset($_SESSION['2fa_verified_at']) && (time() - $_SESSION['2fa_verified_at'] < $two_fa_grace_period)) {
        } else {
            $_SESSION['2fa_user_id'] = $_SESSION['user_id'];
            unset($_SESSION['user_id']);
            
            if (!$is_api_call && !$is_on_auth_page) {
                header('Location: ' . BASE_URL . '/verify-2fa.php?grace_expired=1');
                exit();
            }
        }
    }
}

$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conexion->connect_error) {
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos.']));
    } else {
        die("Error de conexión: " . $conexion->connect_error);
    }
}

$conexion->set_charset("utf8mb4");

function logAction($conexion, $accion, $userId = null, $detalles = '') {
    $stmt = $conexion->prepare("INSERT INTO logs (UserID, Accion, Detalles) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $accion, $detalles);
    $stmt->execute();
    $stmt->close();
}