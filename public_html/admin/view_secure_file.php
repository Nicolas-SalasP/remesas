<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (isset($_SERVER['HTTP_REFERER'])) {
    $refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $serverHost = $_SERVER['HTTP_HOST'];

    if (substr($refererHost, -strlen($serverHost)) !== $serverHost && $refererHost !== $serverHost) {
        http_response_code(403);
        die("Acceso denegado (hotlinking).");
    }
} elseif (php_sapi_name() !== 'cli' && empty($_SERVER['HTTP_REFERER'])) {
    http_response_code(403);
    die("Acceso denegado.");
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Acceso denegado. Debes iniciar sesión.');
}

if (!isset($_SESSION['user_rol_name']) || $_SESSION['user_rol_name'] !== 'Admin') {
    http_response_code(403);
    die('Acceso denegado. Se requiere rol de administrador.');
}

if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    die('Archivo no especificado.');
}

$filePath = $_GET['file'];

$baseUploadPath = realpath(__DIR__ . '/../../remesas_private/uploads');
if (!$baseUploadPath) {
    error_log("Error crítico: El directorio base de uploads no existe.");
    http_response_code(500);
    die("Error interno del servidor.");
}

$filePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath), DIRECTORY_SEPARATOR);

$fullPathAttempt = $baseUploadPath . DIRECTORY_SEPARATOR . $filePath;
$realFullPath = realpath($fullPathAttempt);

if ($realFullPath === false || strpos($realFullPath, $baseUploadPath) !== 0 || !is_file($realFullPath) || !is_readable($realFullPath)) {
    http_response_code(404);
    error_log("Intento de Path Traversal o archivo no encontrado: " . $fullPathAttempt);
    die("Archivo no encontrado o acceso no permitido.");
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
if (!$finfo) {
    http_response_code(500); die('Error al abrir fileinfo.');
}
$mimeType = finfo_file($finfo, $realFullPath);
finfo_close($finfo);

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'application/pdf'
];

if (!in_array($mimeType, $allowedMimeTypes)) {
    http_response_code(403);
    die('Tipo de archivo no permitido.');
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($realFullPath));
header('Content-Disposition: inline; filename="' . basename($realFullPath) . '"');
header('X-Content-Type-Options: nosniff');

if (ob_get_level()) {
    ob_end_clean();
}

readfile($realFullPath);
exit;
?>