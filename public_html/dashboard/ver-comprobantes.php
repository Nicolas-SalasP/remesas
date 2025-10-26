<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';
require_once __DIR__ . '/../../remesas_private/src/App/Database/Database.php';
require_once __DIR__ . '/../../remesas_private/src/App/Repositories/TransactionRepository.php';
require_once __DIR__ . '/../../remesas_private/src/App/Services/FileHandlerService.php';

use App\Database\Database;
use App\Repositories\TransactionRepository;
use App\Services\FileHandlerService; 

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Acceso denegado. Debes iniciar sesión.");
}

$loggedInUserId = (int)$_SESSION['user_id'];
$isAdmin = (isset($_SESSION['user_rol_name']) && $_SESSION['user_rol_name'] === 'Admin');

$transactionId = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'user';

if (!is_numeric($transactionId) || $transactionId <= 0 || !in_array($type, ['user', 'admin'])) {
    http_response_code(400);
    die("Parámetros inválidos (ID de transacción o tipo).");
}
$transactionId = (int)$transactionId;

try {
    $db = Database::getInstance();
    $txRepository = new TransactionRepository($db);
    $fileHandler = new FileHandlerService();

    $columnToSelect = ($type === 'admin') ? 'ComprobanteEnvioURL' : 'ComprobanteURL';

    $sql = "SELECT UserID, $columnToSelect AS FilePath FROM transacciones WHERE TransaccionID = ?";
    $params = [$transactionId];
    $types = "i";

    if (!$isAdmin) {
        $sql .= " AND UserID = ?";
        $params[] = $loggedInUserId;
        $types .= "i";
    }

    $stmt = $conexion->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error al preparar consulta [DBP]: " . $conexion->error, 500);
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
         throw new Exception("Error al ejecutar consulta [DBE]: " . $stmt->error, 500);
    }

    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $stmt->close();

    if (!$fila || empty($fila['FilePath'])) {
        if ($isAdmin) {
            $sqlAdminCheck = "SELECT $columnToSelect AS FilePath FROM transacciones WHERE TransaccionID = ?";
            $stmtAdminCheck = $conexion->prepare($sqlAdminCheck);
            if($stmtAdminCheck) {
                $stmtAdminCheck->bind_param("i", $transactionId);
                $stmtAdminCheck->execute();
                $resultAdminCheck = $stmtAdminCheck->get_result();
                $filaAdminCheck = $resultAdminCheck->fetch_assoc();
                $stmtAdminCheck->close();
                if ($filaAdminCheck && !empty($filaAdminCheck['FilePath'])) {
                     $fila = $filaAdminCheck;
                } else {
                     throw new Exception("Archivo no encontrado para esta transacción.", 404);
                }
            } else {
                 throw new Exception("Error al preparar consulta [DBP2]: " . $conexion->error, 500);
            }
        } else {
            throw new Exception("Archivo no encontrado o no tienes permiso para verlo.", 404);
        }
    }

    $relativePath = $fila['FilePath'];

    $realFullPath = $fileHandler->getAbsolutePath($relativePath);
    if (!file_exists($realFullPath) || !is_file($realFullPath)) {
        error_log("Archivo no encontrado FÍSICAMENTE. Relativa: {$relativePath}, Absoluta calculada: {$realFullPath}");
        throw new Exception("Archivo no encontrado o inválido en el servidor.", 404);
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

    readfile($realFullPath);
    exit();

} catch (Exception $e) {
    $errorCode = $e->getCode();
    $httpCode = ($errorCode >= 400 && $errorCode < 600) ? $errorCode : 500;
    http_response_code($httpCode);
    error_log("Error en ver-comprobante.php (TX ID: {$transactionId}): " . $e->getMessage());
    die("Error al procesar la solicitud del archivo.");
}
?>