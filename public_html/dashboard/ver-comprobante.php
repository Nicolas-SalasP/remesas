<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Acceso denegado. Debes iniciar sesión.");
}

$userId = (int)$_SESSION['user_id'];
$transactionId = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'user'; 

if ($transactionId <= 0) {
    http_response_code(400);
    die("ID de transacción no válido.");
}

$columnToSelect = ($type === 'admin') ? 'ComprobanteEnvioURL' : 'ComprobanteURL';

$sql = "SELECT $columnToSelect AS FilePath FROM transacciones WHERE TransaccionID = ? AND UserID = ?";
$stmt = $conexion->prepare($sql);
if ($stmt === false) {
     http_response_code(500);
     die("Error interno del servidor [DBP].");
}
$stmt->bind_param("ii", $transactionId, $userId);
$stmt->execute();
$resultado = $stmt->get_result();
$fila = $resultado->fetch_assoc();
$stmt->close();

if (!$fila || empty($fila['FilePath'])) {
    http_response_code(404);
    die("Archivo no encontrado o no tienes permiso para verlo.");
}

$relativePath = $fila['FilePath'];
$baseUploadPath = realpath(__DIR__ . '/../../remesas_private/uploads'); 
if ($baseUploadPath === false) {
     http_response_code(500);
     die("Error interno del servidor [FSP].");
}

$fullPath = $baseUploadPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
$realFullPath = realpath($fullPath);

if ($realFullPath === false || strpos($realFullPath, $baseUploadPath) !== 0 || !is_file($realFullPath)) {
    http_response_code(404);
    die("Archivo no encontrado o inválido.");
}

$mimeType = mime_content_type($realFullPath);
if ($mimeType === false) {
    $mimeType = 'application/octet-stream'; 
}

header("Content-Type: " . $mimeType);
header("Content-Length: " . filesize($realFullPath));
header('Content-Disposition: inline; filename="' . basename($realFullPath) . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

ob_clean();
flush();
readfile($realFullPath);
exit();
?>