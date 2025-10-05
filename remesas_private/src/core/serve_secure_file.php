<?php

require_once __DIR__ . '/init.php';

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
    http_response_code(403); 
    die("Acceso denegado. Se requiere rol de administrador.");
}

$requestedFile = $_GET['file'] ?? '';
if (empty($requestedFile)) {
    http_response_code(400);
    die("No se especificó ningún archivo.");
}


$secureBasePath = realpath(__DIR__ . '/../../../uploads');
$fullPathAttempt = $secureBasePath . DIRECTORY_SEPARATOR . $requestedFile;

$realFullPath = realpath($fullPathAttempt);

if ($realFullPath === false || strpos($realFullPath, $secureBasePath) !== 0 || !is_file($realFullPath)) {
    http_response_code(404); 
    die("Archivo no encontrado o acceso no permitido.");
}


$mimeType = mime_content_type($realFullPath);
header("Content-Type: " . $mimeType);
header("Content-Length: " . filesize($realFullPath));
header('Content-Disposition: inline; filename="' . basename($realFullPath) . '"');


ob_clean();
flush();
readfile($realFullPath);
exit();
