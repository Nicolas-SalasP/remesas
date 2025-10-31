<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

use App\Services\FileHandlerService;
use App\Database\Database;

try {
    if (!isset($_SESSION['user_rol_name']) || 
        ($_SESSION['user_rol_name'] !== 'Admin' && $_SESSION['user_rol_name'] !== 'Operador')) {
        
        http_response_code(403); 
        die("Acceso denegado. Se requiere rol de administrador u operador.");
    }

    $requestedFile = $_GET['file'] ?? '';
    if (empty($requestedFile)) {
        http_response_code(400);
        die("No se especificó ningún archivo.");
    }
    
    $requestedFile = urldecode($requestedFile);

    $fileHandler = new FileHandlerService();
    $realFullPath = $fileHandler->getAbsolutePath($requestedFile);

    if ($realFullPath === false || !is_file($realFullPath)) {
        error_log("view_secure_file: Archivo no encontrado. Relativa: " . $requestedFile . " | Ruta calculada: " . $realFullPath);
        http_response_code(404); 
        die("Archivo no encontrado o acceso no permitido.");
    }

    $mimeType = mime_content_type($realFullPath);
    if ($mimeType === false) {
        $mimeType = 'application/octet-stream';
    }
    
    if (ob_get_level()) {
        ob_end_clean();
    }

    header("Content-Type: " . $mimeType);
    header("Content-Length: " . filesize($realFullPath));
    header('Content-Disposition: inline; filename="' . basename($realFullPath) . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('X-Content-Type-Options: nosniff');

    flush();
    readfile($realFullPath);
    exit();

} catch (\Throwable $e) {
    error_log("Error fatal en view_secure_file.php: " . $e->getMessage());
    http_response_code(500);
    die("Error interno del servidor al procesar el archivo.");
}
?>