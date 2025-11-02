<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Acceso denegado. Debes iniciar sesión.');
}

if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    die('Archivo no especificado.');
}

$filePath = $_GET['file'];
if (strpos($filePath, '..') !== false || strpos($filePath, './') !== false) {
    http_response_code(403);
    die('Acceso denegado (path inválido).');
}

$filePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath), DIRECTORY_SEPARATOR);

$baseUploadPath = realpath(__DIR__ . '/../../remesas_private/uploads');
if (!$baseUploadPath) {
    error_log("Error crítico: El directorio base de uploads no existe.");
    http_response_code(500);
    die("Error interno del servidor.");
}

$fullPath = null;

if (strpos($filePath, 'verifications' . DIRECTORY_SEPARATOR) === 0) {
    $fullPath = $baseUploadPath . DIRECTORY_SEPARATOR . $filePath;
} elseif (strpos($filePath, 'receipts' . DIRECTORY_SEPARATOR) === 0) {
    $fullPath = $baseUploadPath . DIRECTORY_SEPARATOR . $filePath;
} elseif (strpos($filePath, 'proof_of_sending' . DIRECTORY_SEPARATOR) === 0) {
    $fullPath = $baseUploadPath . DIRECTORY_SEPARATOR . $filePath;
} elseif (strpos($filePath, 'profile_pics' . DIRECTORY_SEPARATOR) === 0) {
    $fullPath = $baseUploadPath . DIRECTORY_SEPARATOR . $filePath;
} else {
    http_response_code(403);
    die('Acceso denegado a esta ruta de archivo.');
}

if (!file_exists($fullPath) || !is_readable($fullPath)) {
    http_response_code(404);
    error_log("Archivo seguro no encontrado o no legible: " . $fullPath);
    die('Archivo no encontrado.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
if (!$finfo) {
     http_response_code(500); die('Error al abrir fileinfo.');
}
$mimeType = finfo_file($finfo, $fullPath);
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
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('X-Content-Type-Options: nosniff');

if (ob_get_level()) {
    ob_end_clean();
}

readfile($fullPath);
exit;