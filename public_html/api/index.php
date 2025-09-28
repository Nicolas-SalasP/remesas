<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../remesas_private/src/core/init.php';

header('Content-Type: application/json');

// ===================================================================
// FUNCIONES DE AYUDA (HELPERS) PARA SIMPLIFICAR Y MEJORAR EL CÓDIGO
// ===================================================================
function sendJsonResponse(array $data, int $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function ensureAdmin() {
    if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
        sendJsonResponse(['success' => false, 'error' => 'Acceso denegado. Se requiere rol de administrador.'], 403);
    }
}

function ensureLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(['success' => false, 'error' => 'Usuario no autenticado.'], 401);
    }
}

function getJsonInput(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['success' => false, 'error' => 'JSON mal formado.'], 400);
    }
    return $data ?? [];
}

// ===================================================================
// ENRUTADOR PRINCIPAL DE LA API
// ===================================================================

$accion = $_GET['accion'] ?? '';

$adminActions = [
    'updateTransactionStatus', 'updateRate', 'adminUploadProof', 'processTransaction', 
    'rejectTransaction', 'addPais', 'updatePaisRol', 'togglePaisStatus', 
    'updateVerificationStatus', 'toggleUserBlock'
];
$userActions = [
    'getCuentas', 'addCuenta', 'createTransaccion', 'uploadReceipt', 'cancelTransaction',
    'getUserVerificationStatus', 'uploadVerificationDocs', 'getUserProfile'
];
$publicActions = [
    'getDolarBcv', 'registerUser', 'loginUser', 'getPaises', 'getTasa', 
    'getFormasDePago', 'requestPasswordReset', 'performPasswordReset', 'getBeneficiaryTypes'
];

if (in_array($accion, $adminActions)) {
    ensureAdmin();
} elseif (in_array($accion, $userActions)) {
    ensureLoggedIn();
} elseif (!in_array($accion, $publicActions)) {
    sendJsonResponse(['success' => false, 'error' => 'Acción no válida o no encontrada.'], 404);
}

switch ($accion) {
    // Públicas
    case 'getDolarBcv': getDolarBcv(); break;
    case 'registerUser': registerUser($conexion); break;
    case 'loginUser': loginUser($conexion); break;
    case 'getPaises': getPaises($conexion); break;
    case 'getTasa': getTasa($conexion); break;
    case 'getFormasDePago': getFormasDePago($conexion); break;
    case 'getBeneficiaryTypes': getBeneficiaryTypes($conexion); break;
    case 'requestPasswordReset': requestPasswordReset($conexion); break;
    case 'performPasswordReset': performPasswordReset($conexion); break;
    
    // Usuario Logueado
    case 'getCuentas': getCuentas($conexion); break;
    case 'addCuenta': addCuenta($conexion); break;
    case 'createTransaccion': createTransaccion($conexion); break;
    case 'uploadReceipt': uploadReceipt($conexion); break;
    case 'cancelTransaction': cancelTransaction($conexion); break;
    case 'getUserVerificationStatus': getUserVerificationStatus($conexion); break;
    case 'uploadVerificationDocs': uploadVerificationDocs($conexion); break;
    case 'getUserProfile': getUserProfile($conexion); break;

    // Admin
    case 'updateTransactionStatus': updateTransactionStatus($conexion); break;
    case 'updateRate': updateRate($conexion); break;
    case 'adminUploadProof': adminUploadProof($conexion); break;
    case 'processTransaction': processTransaction($conexion); break;
    case 'rejectTransaction': rejectTransaction($conexion); break;
    case 'addPais': addPais($conexion); break;
    case 'updatePaisRol': updatePaisRol($conexion); break;
    case 'togglePaisStatus': togglePaisStatus($conexion); break;
    case 'updateVerificationStatus': updateVerificationStatus($conexion); break;
    case 'toggleUserBlock': toggleUserBlock($conexion); break;
}

// ===================================================================
// DEFINICIÓN DE TODAS LAS FUNCIONES DE LA API (VERSIÓN MEJORADA)
// ===================================================================

// --- FUNCIONES PÚBLICAS ---

function getDolarBcv() {
    $url = 'https://www.bcv.org.ve/';
    $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $html = @file_get_contents($url, false, $context);

    if ($html === false) {
        sendJsonResponse(['success' => false, 'error' => 'No se pudo conectar con el sitio del BCV.'], 503); // Service Unavailable
    }

    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    $nodos = $xpath->query("//div[@id='dolar']//strong");

    if ($nodos->length > 0) {
        $valorLimpio = str_replace(',', '.', trim($nodos[0]->nodeValue));
        sendJsonResponse(['success' => true, 'valor' => (float)$valorLimpio]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'No se pudo encontrar el valor del dólar.'], 404);
    }
}

function registerUser($conexion) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $primerNombre = $_POST['primerNombre'] ?? '';
    $segundoNombre = $_POST['segundoNombre'] ?? '';
    $primerApellido = $_POST['primerApellido'] ?? '';
    $segundoApellido = $_POST['segundoApellido'] ?? '';
    $tipoDocumento = $_POST['tipoDocumento'] ?? '';
    $numeroDocumento = $_POST['numeroDocumento'] ?? '';
    
    if (empty($email) || empty($password) || empty($primerNombre) || empty($primerApellido) || empty($tipoDocumento) || empty($numeroDocumento)) {
        sendJsonResponse(['success' => false, 'error' => 'Todos los campos, incluyendo el documento, son obligatorios.'], 400);
    }

    $stmt_check = $conexion->prepare("SELECT UserID FROM usuarios WHERE Email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        sendJsonResponse(['success' => false, 'error' => 'El correo electrónico ya está registrado.'], 409);
    }
    $stmt_check->close();

    $stmt_doc_check = $conexion->prepare("SELECT UserID FROM usuarios WHERE NumeroDocumento = ?");
    $stmt_doc_check->bind_param("s", $numeroDocumento);
    $stmt_doc_check->execute();
    $stmt_doc_check->store_result();
    if ($stmt_doc_check->num_rows > 0) {
        $stmt_doc_check->close();
        sendJsonResponse(['success' => false, 'error' => 'El número de documento ya está registrado.'], 409);
    }
    $stmt_doc_check->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conexion->prepare("INSERT INTO usuarios (PrimerNombre, SegundoNombre, PrimerApellido, SegundoApellido, Email, PasswordHash, TipoDocumento, NumeroDocumento, VerificacionEstado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'No Verificado')");
    $stmt->bind_param("ssssssss", $primerNombre, $segundoNombre, $primerApellido, $segundoApellido, $email, $passwordHash, $tipoDocumento, $numeroDocumento);

    if ($stmt->execute()) {
        $nuevoUserID = $conexion->insert_id;
        logAction($conexion, 'Registro de Usuario', $nuevoUserID, "Email: " . $email);

        $_SESSION['user_id'] = $nuevoUserID;
        $_SESSION['user_name'] = $primerNombre;
        $_SESSION['user_rol'] = 'User'; 
        $_SESSION['verification_status'] = 'No Verificado';

        sendJsonResponse([
            'success' => true,
            'redirect' => BASE_URL . '/dashboard/',
            'verificationStatus' => 'No Verificado'
        ], 201);

    } else {
        $error = $stmt->error;
        if (str_contains($error, 'Duplicate entry')) {
            sendJsonResponse(['success' => false, 'error' => 'El email o número de documento ya se encuentra registrado.'], 409);
        } else {
            sendJsonResponse(['success' => false, 'error' => 'Error al registrar el usuario.'], 500);
        }
    }
    $stmt->close();
}

function loginUser($conexion) {
    $data = getJsonInput();
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        sendJsonResponse(['success' => false, 'error' => 'Correo y contraseña son obligatorios.'], 400);
    }

    $stmt = $conexion->prepare("SELECT UserID, PasswordHash, PrimerNombre, Rol, VerificacionEstado, FailedLoginAttempts, LockoutUntil FROM usuarios WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        sendJsonResponse(['success' => false, 'error' => 'Correo o contraseña incorrectos.'], 401);
    }

    if ($usuario['LockoutUntil'] && strtotime($usuario['LockoutUntil']) > time()) {
        $tiempoRestante = ceil((strtotime($usuario['LockoutUntil']) - time()) / 60);
        sendJsonResponse(['success' => false, 'error' => "Cuenta bloqueada. Intenta de nuevo en {$tiempoRestante} minutos."], 429); // Too Many Requests
    }

    if (password_verify($password, $usuario['PasswordHash'])) {
        $stmt_reset = $conexion->prepare("UPDATE usuarios SET FailedLoginAttempts = 0, LockoutUntil = NULL WHERE UserID = ?");
        $stmt_reset->bind_param("i", $usuario['UserID']);
        $stmt_reset->execute();
        $stmt_reset->close();
        
        $_SESSION['user_id'] = $usuario['UserID'];
        $_SESSION['user_name'] = $usuario['PrimerNombre'];
        $_SESSION['user_rol'] = $usuario['Rol'];
        $_SESSION['verification_status'] = $usuario['VerificacionEstado'];
        
        logAction($conexion, 'Inicio de Sesión Exitoso', $usuario['UserID']);
        sendJsonResponse([
            'success' => true,
            'redirect' => BASE_URL . '/dashboard/',
            'verificationStatus' => $usuario['VerificacionEstado']
        ]);
    } else {
        $nuevosIntentos = $usuario['FailedLoginAttempts'] + 1;
        $lockoutTime = NULL;
        if ($nuevosIntentos >= 6) {
            $lockoutTime = date("Y-m-d H:i:s", time() + 30 * 60);
        } elseif ($nuevosIntentos >= 3) {
            $lockoutTime = date("Y-m-d H:i:s", time() + 10 * 60);
        }
        
        $stmt_fail = $conexion->prepare("UPDATE usuarios SET FailedLoginAttempts = ?, LockoutUntil = ? WHERE UserID = ?");
        $stmt_fail->bind_param("isi", $nuevosIntentos, $lockoutTime, $usuario['UserID']);
        $stmt_fail->execute();
        $stmt_fail->close();

        logAction($conexion, 'Fallo de Inicio de Sesión', $usuario['UserID'], "Contraseña incorrecta");
        sendJsonResponse(['success' => false, 'error' => 'Correo o contraseña incorrectos.'], 401);
    }
}

function getPaises($conexion) {
    $rol = $_GET['rol'] ?? 'Ambos';
    $stmt = $conexion->prepare("SELECT PaisID, NombrePais, CodigoMoneda FROM paises WHERE (Rol = ? OR Rol = 'Ambos') AND Activo = 1");
    $stmt->bind_param("s", $rol);
    $stmt->execute();
    $paises = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    sendJsonResponse($paises);
}

function getTasa($conexion) {
    $origenID = $_GET['origenID'] ?? 0;
    $destinoID = $_GET['destinoID'] ?? 0;
    $stmt = $conexion->prepare("SELECT TasaID, ValorTasa FROM tasas WHERE PaisOrigenID = ? AND PaisDestinoID = ? ORDER BY FechaEfectiva DESC LIMIT 1");
    $stmt->bind_param("ii", $origenID, $destinoID);
    $stmt->execute();
    $tasa = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    sendJsonResponse($tasa ? $tasa : []);
}

function getFormasDePago($conexion) {
    $resultado = $conexion->query("SHOW COLUMNS FROM transacciones LIKE 'FormaDePago'");
    $fila = $resultado->fetch_assoc();
    preg_match('/enum\((.*)\)$/', $fila['Type'], $matches);
    $enumValues = explode(',', str_replace("'", "", $matches[1]));
    sendJsonResponse($enumValues);
}

function getBeneficiaryTypes($conexion) {
    $resultado = $conexion->query("SHOW COLUMNS FROM cuentasbeneficiarias LIKE 'TipoBeneficiario'");
    $fila = $resultado->fetch_assoc();
    preg_match('/enum\((.*)\)$/', $fila['Type'], $matches);
    $enumValues = explode(',', str_replace("'", "", $matches[1]));
    sendJsonResponse($enumValues);
}

function requestPasswordReset($conexion) {
    $data = getJsonInput();
    $email = $data['email'] ?? '';
    if (empty($email)) {
        sendJsonResponse(['success' => false, 'error' => 'El correo es obligatorio.'], 400);
    }

    $stmt = $conexion->prepare("SELECT UserID FROM usuarios WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($usuario) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date("Y-m-d H:i:s", time() + 10 * 60);
        $stmt_insert = $conexion->prepare("INSERT INTO PasswordResets (UserID, Token, ExpiresAt) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iss", $usuario['UserID'], $token, $expiresAt);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Lógica de envío de email...
    }
    // Siempre dar una respuesta genérica para no revelar si un email existe o no
    sendJsonResponse(['success' => true, 'message' => 'Si tu correo está en nuestro sistema, recibirás un enlace para restablecer tu contraseña.']);
}

function performPasswordReset($conexion) {
    $data = getJsonInput();
    $token = $data['token'] ?? '';
    $newPassword = $data['newPassword'] ?? '';
    if (empty($token) || empty($newPassword)) {
        sendJsonResponse(['success' => false, 'error' => 'El token y la nueva contraseña son obligatorios.'], 400);
    }

    $stmt = $conexion->prepare("SELECT * FROM PasswordResets WHERE Token = ? AND Used = FALSE AND ExpiresAt > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($reset) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt_user = $conexion->prepare("UPDATE usuarios SET PasswordHash = ? WHERE UserID = ?");
        $stmt_user->bind_param("si", $passwordHash, $reset['UserID']);
        $stmt_user->execute();
        $stmt_user->close();
        
        $stmt_token = $conexion->prepare("UPDATE PasswordResets SET Used = TRUE WHERE ResetID = ?");
        $stmt_token->bind_param("i", $reset['ResetID']);
        $stmt_token->execute();
        $stmt_token->close();

        logAction($conexion, 'Contraseña Restablecida', $reset['UserID']);
        sendJsonResponse(['success' => true, 'message' => '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.']);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'El enlace no es válido o ha expirado.'], 400);
    }
}


// --- FUNCIONES DE USUARIO AUTENTICADO ---

function getCuentas($conexion) {
    $userID = $_SESSION['user_id'];
    $paisID = $_GET['paisID'] ?? 0;
    if (empty($paisID)) {
        sendJsonResponse(['success' => false, 'error' => 'El ID del país es requerido.'], 400);
    }

    $stmt = $conexion->prepare("SELECT CuentaID, Alias FROM cuentasbeneficiarias WHERE UserID = ? AND PaisID = ?");
    $stmt->bind_param("ii", $userID, $paisID);
    $stmt->execute();
    $cuentas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    sendJsonResponse($cuentas);
}

function addCuenta($conexion) {
    $data = getJsonInput();
    $userID = $_SESSION['user_id'];

    $requiredFields = ['paisID', 'alias', 'tipoBeneficiario', 'primerNombre', 'primerApellido', 'tipoDocumento', 'numeroDocumento', 'nombreBanco', 'numeroCuenta'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendJsonResponse(['success' => false, 'error' => "El campo '$field' es obligatorio."], 400);
        }
    }

    $stmt = $conexion->prepare("INSERT INTO cuentasbeneficiarias (UserID, PaisID, Alias, TipoBeneficiario, TitularPrimerNombre, TitularSegundoNombre, TitularPrimerApellido, TitularSegundoApellido, TitularTipoDocumento, TitularNumeroDocumento, NombreBanco, NumeroCuenta, NumeroTelefono) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssssssssss", $userID, $data['paisID'], $data['alias'], $data['tipoBeneficiario'], $data['primerNombre'], $data['segundoNombre'], $data['primerApellido'], $data['segundoApellido'], $data['tipoDocumento'], $data['numeroDocumento'], $data['nombreBanco'], $data['numeroCuenta'], $data['numeroTelefono']);

    if ($stmt->execute()) {
        logAction($conexion, 'Usuario añadió cuenta beneficiaria', $userID, "Alias: " . $data['alias']);
        sendJsonResponse(['success' => true, 'id' => $conexion->insert_id], 201);
    } else {
        error_log("Error al añadir cuenta: " . $stmt->error);
        sendJsonResponse(['success' => false, 'error' => 'No se pudo guardar la cuenta.'], 500);
    }
    $stmt->close();
}

function createTransaccion($conexion) {
    $data = getJsonInput();
    $userID = $_SESSION['user_id'];

    $stmt_check = $conexion->prepare("SELECT VerificacionEstado FROM usuarios WHERE UserID = ?");
    $stmt_check->bind_param("i", $userID);
    $stmt_check->execute();
    $estado = $stmt_check->get_result()->fetch_assoc()['VerificacionEstado'];
    $stmt_check->close();

    if ($estado !== 'Verificado') {
        sendJsonResponse(['success' => false, 'error' => 'Tu cuenta debe estar verificada para realizar transacciones.'], 403);
    }
    
    // ... Tu lógica para obtener moneda destino aquí ...
    $monedaDestino = 'USD'; // Valor por defecto

    $stmt = $conexion->prepare("INSERT INTO transacciones (UserID, CuentaBeneficiariaID, TasaID_Al_Momento, MontoOrigen, MonedaOrigen, MontoDestino, MonedaDestino, Estado, FormaDePago) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente de Pago', ?)");
    $stmt->bind_param("iiidsdss", $userID, $data['cuentaID'], $data['tasaID'], $data['montoOrigen'], $data['monedaOrigen'], $data['montoDestino'], $monedaDestino, $data['formaDePago']);
    
    if ($stmt->execute()) {
        sendJsonResponse(['success' => true, 'transaccionID' => $conexion->insert_id], 201);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'No se pudo crear la transacción: ' . $stmt->error], 500);
    }
    $stmt->close();
}

function uploadReceipt($conexion) {
    $transactionId = $_POST['transactionId'] ?? 0;
    $userId = $_SESSION['user_id'];

    if (empty($transactionId) || !isset($_FILES['receiptFile']) || $_FILES['receiptFile']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(['success' => false, 'error' => 'Datos incompletos o error en la subida del archivo.'], 400);
    }
    
    // ... Tu lógica para verificar permisos y guardar el archivo aquí ...
    $uploadDir = 'uploads/receipts/';
    // ...

    if (move_uploaded_file($_FILES['receiptFile']['tmp_name'], $uploadFile)) {
        $stmt_update = $conexion->prepare("UPDATE transacciones SET ComprobanteURL = ?, Estado = 'En Verificación' WHERE TransaccionID = ? AND UserID = ?");
        $stmt_update->bind_param("sii", $dbPath, $transactionId, $userId);
        
        if ($stmt_update->execute()) {
            logAction($conexion, 'Subida de Comprobante', $userId, "Transaccion ID: " . $transactionId);
            sendJsonResponse(['success' => true]);
        } else {
            sendJsonResponse(['success' => false, 'error' => 'Error al actualizar la base de datos.'], 500);
        }
        $stmt_update->close();
    } else {
        sendJsonResponse(['success' => false, 'error' => 'No se pudo guardar el archivo subido.'], 500);
    }
}

function cancelTransaction($conexion) {
    $data = getJsonInput();
    $transactionId = $data['transactionId'] ?? 0;
    $userId = $_SESSION['user_id'];

    if (empty($transactionId)) {
        sendJsonResponse(['success' => false, 'error' => 'ID de transacción no válido.'], 400);
    }

    $stmt = $conexion->prepare("UPDATE transacciones SET Estado = 'Cancelado' WHERE TransaccionID = ? AND UserID = ? AND Estado = 'Pendiente de Pago'");
    $stmt->bind_param("ii", $transactionId, $userId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        logAction($conexion, 'Usuario canceló transacción', $userId, "Transaccion ID: " . $transactionId);
        sendJsonResponse(['success' => true]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'No se pudo cancelar la transacción. Es posible que ya haya sido procesada.'], 409); // Conflict
    }
    $stmt->close();
}

function getUserVerificationStatus($conexion) {
    $userID = $_SESSION['user_id'];
    $stmt = $conexion->prepare("SELECT VerificacionEstado FROM usuarios WHERE UserID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    sendJsonResponse(['success' => true, 'status' => $resultado['VerificacionEstado'] ?? 'No Verificado']);
}

function uploadVerificationDocs($conexion) {
    $userID = $_SESSION['user_id'];
    
    if (!isset($_FILES['docFrente']) || $_FILES['docFrente']['error'] !== UPLOAD_ERR_OK ||
        !isset($_FILES['docReverso']) || $_FILES['docReverso']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(['success' => false, 'error' => 'Debes subir ambos lados del documento y sin errores.'], 400);
    }

    $uploadDir = 'uploads/verifications/';
    $absoluteUploadDir = dirname(__DIR__) . '/' . $uploadDir;

    if (!is_dir($absoluteUploadDir)) {
        if (!@mkdir($absoluteUploadDir, 0755, true)) {
            sendJsonResponse(['success' => false, 'error' => 'Error de servidor: No se pudo crear el directorio de destino. Verifica los permisos de la carpeta /uploads.'], 500);
        }
    }

    function moverArchivo($file, $dir, $prefix) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'pdf'])) {
            return false; 
        }
        $fileName = $prefix . '_' . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($file['tmp_name'], $dir . $fileName)) {
            return 'uploads/verifications/' . $fileName; // Retorna la ruta relativa para la BD
        }
        return false;
    }

    $rutaFrente = moverArchivo($_FILES['docFrente'], $absoluteUploadDir, 'frente_' . $userID);
    $rutaReverso = moverArchivo($_FILES['docReverso'], $absoluteUploadDir, 'reverso_' . $userID);

    if ($rutaFrente && $rutaReverso) {
        $stmt = $conexion->prepare("UPDATE usuarios SET DocumentoImagenURL_Frente = ?, DocumentoImagenURL_Reverso = ?, VerificacionEstado = 'Pendiente' WHERE UserID = ?");
        $stmt->bind_param("ssi", $rutaFrente, $rutaReverso, $userID);
        
        if ($stmt->execute()) {
            sendJsonResponse(['success' => true, 'message' => 'Documentos subidos correctamente.']);
        } else {
            sendJsonResponse(['success' => false, 'error' => 'Error al actualizar la base de datos.'], 500);
        }
        $stmt->close();
    } else {
        if ($rutaFrente && file_exists(dirname(__DIR__) . '/' . $rutaFrente)) {
            unlink(dirname(__DIR__) . '/' . $rutaFrente);
        }
        sendJsonResponse(['success' => false, 'error' => 'Error al guardar los archivos. Asegúrate de que sean imágenes (jpg, png) o PDF válidos.'], 400);
    }
}

function getUserProfile($conexion) {
    $userID = $_SESSION['user_id'];
    $stmt = $conexion->prepare("SELECT PrimerNombre, SegundoNombre, PrimerApellido, SegundoApellido, Email, TipoDocumento, NumeroDocumento, VerificacionEstado FROM usuarios WHERE UserID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($profile) {
        sendJsonResponse(['success' => true, 'profile' => $profile]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'Perfil no encontrado.'], 404);
    }
}


// --- FUNCIONES DE ADMINISTRADOR ---

function updateTransactionStatus($conexion) {
    $data = getJsonInput();
    $transactionId = $data['transactionId'] ?? 0;
    $newStatus = $data['newStatus'] ?? '';
    $estadosValidos = ['Pendiente de Pago', 'En Verificación', 'En Proceso', 'Pagado', 'Cancelado'];

    if (empty($transactionId) || !in_array($newStatus, $estadosValidos)) {
        sendJsonResponse(['success' => false, 'error' => 'Datos inválidos.'], 400);
    }
    
    $stmt = $conexion->prepare("UPDATE transacciones SET Estado = ? WHERE TransaccionID = ?");
    $stmt->bind_param("si", $newStatus, $transactionId);
    if ($stmt->execute()) {
        sendJsonResponse(['success' => true]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'Error al actualizar la base de datos.'], 500);
    }
    $stmt->close();
}

function updateRate($conexion) {
    $data = getJsonInput();
    $tasaId = $data['tasaId'] ?? 0;
    $nuevoValor = $data['nuevoValor'] ?? 0;

    if (empty($tasaId) || !is_numeric($nuevoValor) || $nuevoValor <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Datos inválidos.'], 400);
    }
    
    $stmt = $conexion->prepare("UPDATE tasas SET ValorTasa = ? WHERE TasaID = ?");
    $stmt->bind_param("di", $nuevoValor, $tasaId);
    if ($stmt->execute()) {
        logAction($conexion, 'Admin actualizó tasa', $_SESSION['user_id'], "Tasa ID: $tasaId, Nuevo Valor: $nuevoValor");
        sendJsonResponse(['success' => true]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'Error al actualizar la base de datos.'], 500);
    }
    $stmt->close();
}

function adminUploadProof($conexion) {
    $transactionId = $_POST['transactionId'] ?? 0;
    if (empty($transactionId) || !isset($_FILES['receiptFile']) || $_FILES['receiptFile']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(['success' => false, 'error' => 'Datos incompletos o error en el archivo.'], 400);
    }
    
    // ... Tu lógica de guardado de archivos ...
    
    if (move_uploaded_file($_FILES['receiptFile']['tmp_name'], $uploadFile)) {
        $stmt_update = $conexion->prepare("UPDATE transacciones SET ComprobanteEnvioURL = ?, Estado = 'Pagado' WHERE TransaccionID = ?");
        $stmt_update->bind_param("si", $dbPath, $transactionId);
        
        if ($stmt_update->execute()) {
            logAction($conexion, 'Admin subió comprobante de envío', $_SESSION['user_id'], "Transaccion ID: " . $transactionId);
            sendJsonResponse(['success' => true]);
        } else {
            sendJsonResponse(['success' => false, 'error' => 'Error al actualizar la base de datos.'], 500);
        }
        $stmt_update->close();
    } else {
        sendJsonResponse(['success' => false, 'error' => 'No se pudo guardar el archivo subido.'], 500);
    }
}

function processTransaction($conexion) {
    $data = getJsonInput();
    $transactionId = $data['transactionId'] ?? 0;
    if(empty($transactionId)) {
        sendJsonResponse(['success' => false, 'error' => 'ID de transacción inválido.'], 400);
    }
    
    $stmt = $conexion->prepare("UPDATE transacciones SET Estado = 'En Proceso' WHERE TransaccionID = ? AND Estado = 'En Verificación'");
    $stmt->bind_param("i", $transactionId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        logAction($conexion, 'Admin procesó transacción', $_SESSION['user_id'], "Transaccion ID: $transactionId");
        sendJsonResponse(['success' => true]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'No se pudo procesar la transacción o ya no estaba en estado de verificación.'], 409);
    }
    $stmt->close();
}

function rejectTransaction($conexion) {
    $data = getJsonInput();
    $transactionId = $data['transactionId'] ?? 0;
     if(empty($transactionId)) {
        sendJsonResponse(['success' => false, 'error' => 'ID de transacción inválido.'], 400);
    }
    
    $stmt = $conexion->prepare("UPDATE transacciones SET Estado = 'Cancelado' WHERE TransaccionID = ? AND Estado = 'En Verificación'");
    $stmt->bind_param("i", $transactionId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        logAction($conexion, 'Admin rechazó pago de transacción', $_SESSION['user_id'], "Transaccion ID: $transactionId");
        sendJsonResponse(['success' => true]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'No se pudo rechazar la transacción.'], 409);
    }
    $stmt->close();
}

function addPais($conexion) {
    $data = getJsonInput();
    $nombrePais = $data['nombrePais'] ?? '';
    $codigoMoneda = strtoupper($data['codigoMoneda'] ?? '');
    $rol = $data['rol'] ?? '';

    if (empty($nombrePais) || empty($codigoMoneda) || empty($rol) || strlen($codigoMoneda) !== 3) {
        sendJsonResponse(['success' => false, 'error' => 'Todos los campos son obligatorios y el código de moneda debe tener 3 letras.'], 400);
    }

    $stmt = $conexion->prepare("INSERT INTO paises (NombrePais, CodigoMoneda, Rol) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombrePais, $codigoMoneda, $rol);

    if ($stmt->execute()) {
        logAction($conexion, 'Admin añadió país', $_SESSION['user_id'], "País: $nombrePais ($codigoMoneda)");
        sendJsonResponse(['success' => true], 201);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'Error al guardar el país en la base de datos.'], 500);
    }
    $stmt->close();
}

function updatePaisRol($conexion) {
    $data = getJsonInput();
    $paisId = $data['paisId'] ?? 0;
    $newRole = $data['newRole'] ?? '';
    $rolesValidos = ['Origen', 'Destino', 'Ambos'];

    if (empty($paisId) || !in_array($newRole, $rolesValidos)) {
        sendJsonResponse(['success' => false, 'error' => 'Datos inválidos.'], 400);
    }

    $stmt = $conexion->prepare("UPDATE paises SET Rol = ? WHERE PaisID = ?");
    $stmt->bind_param("si", $newRole, $paisId);

    if ($stmt->execute()) {
        logAction($conexion, "Admin cambió rol de país", $_SESSION['user_id'], "País ID: $paisId, Nuevo Rol: $newRole");
        sendJsonResponse(['success' => true]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'Error al actualizar el rol.'], 500);
    }
    $stmt->close();
}

function togglePaisStatus($conexion) {
    $data = getJsonInput();
    $paisId = $data['paisId'] ?? 0;
    $newStatus = isset($data['newStatus']) ? (int)$data['newStatus'] : -1;


    if (empty($paisId) || !in_array($newStatus, [0, 1])) {
        sendJsonResponse(['success' => false, 'error' => 'ID de país o estado no válido.'], 400);
    }

    $stmt = $conexion->prepare("UPDATE paises SET Activo = ? WHERE PaisID = ?");
    $stmt->bind_param("ii", $newStatus, $paisId);

    if ($stmt->execute()) {
        $statusText = $newStatus == 1 ? 'Activado' : 'Desactivado';
        logAction($conexion, "Admin cambió estado de país", $_SESSION['user_id'], "País ID: $paisId, Nuevo Estado: $statusText");
        sendJsonResponse(['success' => true]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'Error al actualizar el estado.'], 500);
    }
    $stmt->close();
}

function updateVerificationStatus($conexion) {
    $data = getJsonInput();
    $userId = $data['userId'] ?? 0;
    $newStatus = $data['newStatus'] ?? '';
    $estadosValidos = ['Verificado', 'Rechazado'];
    
    if (empty($userId) || !in_array($newStatus, $estadosValidos)) {
        sendJsonResponse(['success' => false, 'error' => 'Datos inválidos.'], 400);
    }

    $stmt = $conexion->prepare("UPDATE usuarios SET VerificacionEstado = ? WHERE UserID = ?");
    $stmt->bind_param("si", $newStatus, $userId);

    if ($stmt->execute()) {
        logAction($conexion, 'Admin actualizó estado de verificación', $_SESSION['user_id'], "Usuario ID: $userId, Nuevo Estado: $newStatus");
        sendJsonResponse(['success' => true]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'No se pudo actualizar el estado en la base de datos.'], 500);
    }
    $stmt->close();
}

function toggleUserBlock($conexion) {
    $data = getJsonInput();
    $userId = $data['userId'] ?? 0;
    $newStatus = $data['newStatus'] ?? '';

    if (empty($userId) || !in_array($newStatus, ['active', 'blocked'])) {
        sendJsonResponse(['success' => false, 'error' => 'Datos de entrada inválidos.'], 400);
    }

    $lockoutUntil = ($newStatus === 'blocked') ? date('Y-m-d H:i:s', strtotime('+100 years')) : NULL;
    $stmt = $conexion->prepare("UPDATE usuarios SET LockoutUntil = ? WHERE UserID = ?");
    $stmt->bind_param("si", $lockoutUntil, $userId);

    if ($stmt->execute()) {
        $actionText = ($newStatus === 'blocked') ? 'Bloqueado' : 'Desbloqueado';
        logAction($conexion, "Admin cambió estado de usuario", $_SESSION['user_id'], "Usuario ID: $userId, Nuevo Estado: $actionText");
        sendJsonResponse(['success' => true]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'Error al actualizar el estado del usuario.'], 500);
    }
    $stmt->close();
}

?>