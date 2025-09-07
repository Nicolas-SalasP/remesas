<?php
// ARCHIVO: public/api/index.php

// Carga la configuración, inicia la sesión y crea la conexión a la BD.
require_once __DIR__ . '/../../src/core/init.php';

// Establece la cabecera para asegurar que la respuesta sea siempre JSON.
header('Content-Type: application/json');

// Lee la acción solicitada.
$accion = $_GET['accion'] ?? '';

switch ($accion) {
    case 'getDolarBcv':
        getDolarBcv();
        break;
    case 'registerUser':
        registerUser($conexion);
        break;
    case 'loginUser':
        loginUser($conexion);
        break;
    case 'getPaises':
        getPaises($conexion);
        break;
    case 'getCuentas':
        getCuentas($conexion);
        break;
    case 'getTasa':
        getTasa($conexion);
        break;
    case 'addCuenta':
        addCuenta($conexion);
        break;
    case 'createTransaccion':
        createTransaccion($conexion);
        break;
    case 'uploadReceipt':
        uploadReceipt($conexion);
        break;
    case 'getFormasDePago':
        getFormasDePago($conexion);
        break;
    case 'updateTransactionStatus': 
        updateTransactionStatus($conexion);
        break;
    case 'updateRate':
        updateRate($conexion);
        break;
    case 'cancelTransaction':
        cancelTransaction($conexion);
        break;
    case 'adminUploadProof':
        adminUploadProof($conexion);
        break;
     case 'processTransaction':
        processTransaction($conexion);
        break;
    case 'rejectTransaction': 
        rejectTransaction($conexion);
        break;
    case 'addPais':
        addPais($conexion);
        break;
    case 'updatePaisRol':
        updatePaisRol($conexion);
        break;
    case 'togglePaisStatus':
        togglePaisStatus($conexion);
        break;
    case 'requestPasswordReset':
        requestPasswordReset($conexion);
        break;
    case 'performPasswordReset':
        performPasswordReset($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        exit();
}

function getDolarBcv() {
    $url = 'https://www.bcv.org.ve/';
    $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $html = @file_get_contents($url, false, $context);

    if ($html === false) {
        echo json_encode(['success' => false, 'error' => 'No se pudo conectar con el sitio del BCV.']);
        exit();
    }

    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    $query = "//div[@id='dolar']//strong";
    $nodos = $xpath->query($query);

    if ($nodos->length > 0) {
        $valorString = $nodos[0]->nodeValue;
        $valorLimpio = str_replace(',', '.', trim($valorString));
        $valorFloat = (float)$valorLimpio;
        echo json_encode(['success' => true, 'valor' => $valorFloat]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo encontrar el valor del dólar.']);
    }
    exit();
}

function registerUser($conexion) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Email y contraseña son obligatorios.']);
        exit();
    }

    $stmt_check = $conexion->prepare("SELECT UserID FROM Usuarios WHERE Email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'El correo electrónico ya está registrado.']);
        $stmt_check->close();
        exit();
    }
    $stmt_check->close();
    
    $docImagenURL = null; 
    // Aquí iría tu lógica completa para subir la imagen del documento de identidad
    // y asignar la ruta a $docImagenURL.

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conexion->prepare("INSERT INTO Usuarios (PrimerNombre, SegundoNombre, PrimerApellido, SegundoApellido, Email, PasswordHash, TipoDocumento, NumeroDocumento, DocumentoImagenURL, VerificacionEstado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')");
    $stmt->bind_param("sssssssss", $_POST['primerNombre'], $_POST['segundoNombre'], $_POST['primerApellido'], $_POST['segundoApellido'], $email, $passwordHash, $_POST['tipoDocumento'], $_POST['numeroDocumento'], $docImagenURL);

    if ($stmt->execute()) {
        $nuevoUserID = $conexion->insert_id;
        logAction($conexion, 'Registro de Usuario', $nuevoUserID);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al registrar el usuario: ' . $stmt->error]);
    }

    $stmt->close();

    
    exit();
}

function loginUser($conexion) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['email']) || empty($data['password'])) {
        echo json_encode(['success' => false, 'error' => 'Correo y contraseña son obligatorios.']);
        exit();
    }

    $stmt = $conexion->prepare("SELECT UserID, PasswordHash, PrimerNombre, Rol, FailedLoginAttempts, LockoutUntil FROM Usuarios WHERE Email = ?");
    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        if ($usuario['LockoutUntil'] && strtotime($usuario['LockoutUntil']) > time()) {
            $tiempoRestante = strtotime($usuario['LockoutUntil']) - time();
            echo json_encode(['success' => false, 'error' => "Cuenta bloqueada. Por favor, intenta de nuevo en " . ceil($tiempoRestante / 60) . " minutos."]);
            exit();
        }

        if (password_verify($data['password'], $usuario['PasswordHash'])) {
            $stmt_reset = $conexion->prepare("UPDATE Usuarios SET FailedLoginAttempts = 0, LockoutUntil = NULL WHERE UserID = ?");
            $stmt_reset->bind_param("i", $usuario['UserID']);
            $stmt_reset->execute();
            $stmt_reset->close();

            $_SESSION['user_id'] = $usuario['UserID'];
            $_SESSION['user_name'] = $usuario['PrimerNombre'];
            $_SESSION['user_rol'] = $usuario['Rol'];
            
            logAction($conexion, 'Inicio de Sesión Exitoso', $usuario['UserID']);
            echo json_encode(['success' => true, 'redirect' => BASE_URL . '/dashboard/']);
        } else {
            $nuevosIntentos = $usuario['FailedLoginAttempts'] + 1;
            $lockoutTime = NULL;

            if ($nuevosIntentos >= 6) {
                $lockoutTime = date("Y-m-d H:i:s", time() + 30 * 60);
            } elseif ($nuevosIntentos >= 3) {
                $lockoutTime = date("Y-m-d H:i:s", time() + 10 * 60);
            }
            
            $stmt_fail = $conexion->prepare("UPDATE Usuarios SET FailedLoginAttempts = ?, LockoutUntil = ? WHERE UserID = ?");
            $stmt_fail->bind_param("isi", $nuevosIntentos, $lockoutTime, $usuario['UserID']);
            $stmt_fail->execute();
            $stmt_fail->close();

            logAction($conexion, 'Fallo de Inicio de Sesión', $usuario['UserID'], "Contraseña incorrecta");
            echo json_encode(['success' => false, 'error' => 'Correo o contraseña incorrectos.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Correo o contraseña incorrectos.']);
    }
    $stmt->close();
    exit();
}

function getPaises($conexion) {
    $rol = $_GET['rol'] ?? 'Ambos';
    $stmt = $conexion->prepare("SELECT PaisID, NombrePais, CodigoMoneda FROM Paises WHERE (Rol = ? OR Rol = 'Ambos') AND Activo = 1");
    
    $stmt->bind_param("s", $rol);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $paises = $resultado->fetch_all(MYSQLI_ASSOC);
    echo json_encode($paises);
    $stmt->close();
    exit();
}

function getCuentas($conexion) {
    $userID = $_SESSION['user_id'] ?? 0;
    $paisID = $_GET['paisID'] ?? 0;
    
    if (empty($userID)) {
        echo json_encode(['success' => false, 'error' => 'Usuario no autenticado.']);
        exit();
    }

    $stmt = $conexion->prepare("SELECT CuentaID, Alias FROM CuentasBeneficiarias WHERE UserID = ? AND PaisID = ?");
    $stmt->bind_param("ii", $userID, $paisID);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cuentas = $resultado->fetch_all(MYSQLI_ASSOC);
    echo json_encode($cuentas);
    $stmt->close();
    exit();
}

function getTasa($conexion) {
    $origenID = $_GET['origenID'] ?? 0;
    $destinoID = $_GET['destinoID'] ?? 0;
    $stmt = $conexion->prepare("SELECT TasaID, ValorTasa FROM Tasas WHERE PaisOrigenID = ? AND PaisDestinoID = ? ORDER BY FechaEfectiva DESC LIMIT 1");
    $stmt->bind_param("ii", $origenID, $destinoID);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $tasa = $resultado->fetch_assoc();
    echo json_encode($tasa);
    $stmt->close();
    exit();
}

function addCuenta($conexion) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
        exit();
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $userID = $_SESSION['user_id'];

    $requiredFields = ['paisID', 'alias', 'tipoBeneficiario', 'primerNombre', 'primerApellido', 'tipoDocumento', 'numeroDocumento', 'nombreBanco', 'numeroCuenta'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'error' => "El campo '$field' es obligatorio."]);
            exit();
        }
    }
    
    $stmt = $conexion->prepare(
        "INSERT INTO CuentasBeneficiarias (UserID, PaisID, Alias, TipoBeneficiario, TitularPrimerNombre, TitularSegundoNombre, TitularPrimerApellido, TitularSegundoApellido, TitularTipoDocumento, TitularNumeroDocumento, NombreBanco, NumeroCuenta, NumeroTelefono) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    $stmt->bind_param(
        "iisssssssssss", 
        $userID, 
        $data['paisID'], 
        $data['alias'], 
        $data['tipoBeneficiario'], 
        $data['primerNombre'], 
        $data['segundoNombre'] ?? null,
        $data['primerApellido'], 
        $data['segundoApellido'] ?? null,
        $data['tipoDocumento'], 
        $data['numeroDocumento'], 
        $data['nombreBanco'], 
        $data['numeroCuenta'], 
        $data['numeroTelefono'] ?? null
    );

    if ($stmt->execute()) {
        logAction($conexion, 'Usuario añadió cuenta beneficiaria', $userID, "Alias: " . $data['alias']);
        echo json_encode(['success' => true, 'id' => $conexion->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar la cuenta: ' . $stmt->error]);
    }
    $stmt->close();
    exit();
}


function createTransaccion($conexion) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data === null) {
        echo json_encode(['success' => false, 'error' => 'Error: No se recibieron datos del formulario.']);
        exit();
    }

    if (!isset($_SESSION['user_id']) || !isset($data['userID']) || $_SESSION['user_id'] != $data['userID']) {
        echo json_encode(['success' => false, 'error' => 'Acceso no autorizado. La sesión no coincide.']);
        exit();
    }
    
    $monedaDestino = 'USD'; 
    $stmt_moneda = $conexion->prepare("SELECT p.CodigoMoneda FROM Paises p JOIN CuentasBeneficiarias cb ON p.PaisID = cb.PaisID WHERE cb.CuentaID = ?");
    $stmt_moneda->bind_param("i", $data['cuentaID']);
    $stmt_moneda->execute();
    $resultado = $stmt_moneda->get_result();
    if ($fila = $resultado->fetch_assoc()) {
        $monedaDestino = $fila['CodigoMoneda'];
    }
    $stmt_moneda->close();

    $stmt = $conexion->prepare("INSERT INTO Transacciones (UserID, CuentaBeneficiariaID, TasaID_Al_Momento, MontoOrigen, MonedaOrigen, MontoDestino, MonedaDestino, Estado, FormaDePago) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente de Pago', ?)");
    
    $stmt->bind_param("iiidsdss", 
        $data['userID'],                   
        $data['cuentaID'],                 
        $data['tasaID'],                   
        $data['montoOrigen'],              
        $data['monedaOrigen'],             
        $data['montoDestino'],             
        $monedaDestino,                                     
        $data['formaDePago']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'transaccionID' => $conexion->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit();
}

function uploadReceipt($conexion) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Usuario no autenticado.']);
        exit();
    }

    $transactionId = $_POST['transactionId'] ?? 0;
    $userId = $_SESSION['user_id'];

    if (empty($transactionId) || !isset($_FILES['receiptFile']) || $_FILES['receiptFile']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos o error en la subida del archivo.']);
        exit();
    }

    $stmt_check = $conexion->prepare("SELECT UserID, ComprobanteURL FROM Transacciones WHERE TransaccionID = ?");
    $stmt_check->bind_param("i", $transactionId);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $tx = $result->fetch_assoc();
    $stmt_check->close();

    if (!$tx || $tx['UserID'] != $userId) {
        echo json_encode(['success' => false, 'error' => 'Permiso denegado.']);
        exit();
    }
    
    if (!empty($tx['ComprobanteURL'])) {
        $oldFilePath = __DIR__ . '/../../' . $tx['ComprobanteURL'];
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
    }

    $uploadDir = 'uploads/receipts/';
    $absoluteUploadDir = __DIR__ . '/../' . $uploadDir; 
    
    if (!is_dir($absoluteUploadDir)) {
        mkdir($absoluteUploadDir, 0755, true);
    }

    $fileExtension = pathinfo($_FILES['receiptFile']['name'], PATHINFO_EXTENSION);
    $fileName = 'tx_' . $transactionId . '_' . uniqid() . '.' . $fileExtension;
    $uploadFile = $absoluteUploadDir . $fileName;
    $dbPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['receiptFile']['tmp_name'], $uploadFile)) {
        $stmt_update = $conexion->prepare("UPDATE Transacciones SET ComprobanteURL = ?, Estado = 'En Verificación' WHERE TransaccionID = ?");
        $stmt_update->bind_param("si", $dbPath, $transactionId);
        
        if ($stmt_update->execute()) {
            logAction($conexion, 'Subida de Comprobante', $userId, "Transaccion ID: " . $transactionId);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar la base de datos.']);
        }
        $stmt_update->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo guardar el archivo subido.']);
    }
    exit();
}

function updateTransactionStatus($conexion) {
    if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
        echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $transactionId = $data['transactionId'] ?? 0;
    $newStatus = $data['newStatus'] ?? '';

    $estadosValidos = ['Pendiente de Pago', 'En Verificación', 'En Proceso', 'Pagado', 'Cancelado'];
    if (empty($transactionId) || !in_array($newStatus, $estadosValidos)) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
        exit();
    }
    
    $stmt = $conexion->prepare("UPDATE Transacciones SET Estado = ? WHERE TransaccionID = ?");
    $stmt->bind_param("si", $newStatus, $transactionId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar la base de datos.']);
    }
    $stmt->close();
    exit();
}

function updateRate($conexion) {
    if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
        echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $tasaId = $data['tasaId'] ?? 0;
    $nuevoValor = $data['nuevoValor'] ?? 0;

    if (empty($tasaId) || !is_numeric($nuevoValor) || $nuevoValor <= 0) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
        exit();
    }
    
    $stmt = $conexion->prepare("UPDATE Tasas SET ValorTasa = ? WHERE TasaID = ?");
    $stmt->bind_param("di", $nuevoValor, $tasaId);

    if ($stmt->execute()) {
        logAction($conexion, 'Admin actualizó tasa', $_SESSION['user_id'], "Tasa ID: $tasaId, Nuevo Valor: $nuevoValor");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar la base de datos.']);
    }
    $stmt->close();
    exit();
}

function cancelTransaction($conexion) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Usuario no autenticado.']);
        exit();
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $transactionId = $data['transactionId'] ?? 0;
    $userId = $_SESSION['user_id'];

    if (empty($transactionId)) {
        echo json_encode(['success' => false, 'error' => 'ID de transacción no válido.']);
        exit();
    }

    $stmt = $conexion->prepare("UPDATE Transacciones SET Estado = 'Cancelado' WHERE TransaccionID = ? AND UserID = ? AND Estado = 'Pendiente de Pago'");
    $stmt->bind_param("ii", $transactionId, $userId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        logAction($conexion, 'Usuario canceló transacción', $userId, "Transaccion ID: " . $transactionId);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo cancelar la transacción. Es posible que ya haya sido procesada.']);
    }
    $stmt->close();
    exit();
}

function adminUploadProof($conexion) {
    if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
        echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
        exit();
    }

    $transactionId = $_POST['transactionId'] ?? 0;

    if (empty($transactionId) || !isset($_FILES['receiptFile']) || $_FILES['receiptFile']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos o error en el archivo.']);
        exit();
    }

    $uploadDir = 'uploads/proof_of_sending/';
    $absoluteUploadDir = __DIR__ . '/../' . $uploadDir;
    
    if (!is_dir($absoluteUploadDir)) {
        mkdir($absoluteUploadDir, 0755, true);
    }

    $fileExtension = pathinfo($_FILES['receiptFile']['name'], PATHINFO_EXTENSION);
    $fileName = 'tx_envio_' . $transactionId . '_' . uniqid() . '.' . $fileExtension;
    $uploadFile = $absoluteUploadDir . $fileName;
    $dbPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['receiptFile']['tmp_name'], $uploadFile)) {
        $stmt_update = $conexion->prepare("UPDATE Transacciones SET ComprobanteEnvioURL = ?, Estado = 'Pagado' WHERE TransaccionID = ?");
        $stmt_update->bind_param("si", $dbPath, $transactionId);
        
        if ($stmt_update->execute()) {
            logAction($conexion, 'Admin subió comprobante de envío', $_SESSION['user_id'], "Transaccion ID: " . $transactionId);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar la base de datos.']);
        }
        $stmt_update->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo guardar el archivo subido.']);
    }
    exit();
}

function getFormasDePago($conexion) {
    $query = "SHOW COLUMNS FROM Transacciones LIKE 'FormaDePago'";
    $resultado = $conexion->query($query);
    $fila = $resultado->fetch_assoc();

    $enumList = $fila['Type'];

    preg_match('/enum\((.*)\)$/', $enumList, $matches);
    $enumValues = explode(',', str_replace("'", "", $matches[1]));
    
    echo json_encode($enumValues);
    exit();
}

function addPais($conexion) {
    if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
        echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $nombrePais = $data['nombrePais'] ?? '';
    $codigoMoneda = strtoupper($data['codigoMoneda'] ?? '');
    $rol = $data['rol'] ?? '';

    if (empty($nombrePais) || empty($codigoMoneda) || empty($rol) || strlen($codigoMoneda) !== 3) {
        echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios y el código de moneda debe tener 3 letras.']);
        exit();
    }

    $stmt = $conexion->prepare("INSERT INTO Paises (NombrePais, CodigoMoneda, Rol) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombrePais, $codigoMoneda, $rol);

    if ($stmt->execute()) {
        logAction($conexion, 'Admin añadió país', $_SESSION['user_id'], "País: $nombrePais ($codigoMoneda)");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar el país en la base de datos.']);
    }
    $stmt->close();
    exit();
}

function togglePaisStatus($conexion) {
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });

    try {
        if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
            echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
            exit();
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $paisId = $data['paisId'] ?? 0;
        $newStatus = isset($data['newStatus']) ? (int)$data['newStatus'] : 0;

        if (empty($paisId)) {
            echo json_encode(['success' => false, 'error' => 'ID de país no válido.']);
            exit();
        }

        $stmt = $conexion->prepare("UPDATE Paises SET Activo = ? WHERE PaisID = ?");
        $stmt->bind_param("ii", $newStatus, $paisId);

        if ($stmt->execute()) {
            $statusText = $newStatus == 1 ? 'Activado' : 'Desactivado';
            logAction($conexion, "Admin cambió estado de país", $_SESSION['user_id'], "País ID: $paisId, Nuevo Estado: $statusText");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar el estado.']);
        }
        $stmt->close();

    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Error de PHP: ' . $e->getMessage() . ' en la línea ' . $e->getLine()]);
    } finally {
        restore_error_handler();
    }
    exit();
}

function updatePaisRol($conexion) {
    if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
        echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $paisId = $data['paisId'] ?? 0;
    $newRole = $data['newRole'] ?? '';
    
    $rolesValidos = ['Origen', 'Destino', 'Ambos'];
    if (empty($paisId) || !in_array($newRole, $rolesValidos)) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
        exit();
    }

    $stmt = $conexion->prepare("UPDATE Paises SET Rol = ? WHERE PaisID = ?");
    $stmt->bind_param("si", $newRole, $paisId);

    if ($stmt->execute()) {
        logAction($conexion, "Admin cambió rol de país", $_SESSION['user_id'], "País ID: $paisId, Nuevo Rol: $newRole");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar el rol.']);
    }
    $stmt->close();
    exit();
}

function processTransaction($conexion) {
    if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
        echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $transactionId = $data['transactionId'] ?? 0;
    
    $stmt = $conexion->prepare("UPDATE Transacciones SET Estado = 'En Proceso' WHERE TransaccionID = ? AND Estado = 'En Verificación'");
    $stmt->bind_param("i", $transactionId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        logAction($conexion, 'Admin procesó transacción', $_SESSION['user_id'], "Transaccion ID: $transactionId");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo procesar la transacción o ya no estaba en estado de verificación.']);
    }
    $stmt->close();
    exit();
}

function rejectTransaction($conexion) {
    if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
        echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $transactionId = $data['transactionId'] ?? 0;
    
    $stmt = $conexion->prepare("UPDATE Transacciones SET Estado = 'Cancelado' WHERE TransaccionID = ? AND Estado = 'En Verificación'");
    $stmt->bind_param("i", $transactionId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        logAction($conexion, 'Admin rechazó pago de transacción', $_SESSION['user_id'], "Transaccion ID: $transactionId");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo rechazar la transacción.']);
    }
    $stmt->close();
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function requestPasswordReset($conexion) {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';

    $stmt = $conexion->prepare("SELECT UserID FROM Usuarios WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($usuario = $resultado->fetch_assoc()) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date("Y-m-d H:i:s", time() + 10 * 60); 
        
        $stmt_insert = $conexion->prepare("INSERT INTO PasswordResets (UserID, Token, ExpiresAt) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iss", $usuario['UserID'], $token, $expiresAt);
        $stmt_insert->execute();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(SMTP_USER, 'Tu Empresa de Remesas');
            $mail->addAddress($email);

            $resetLink = BASE_URL . '/reset-password.php?token=' . $token;
            $mail->isHTML(true);
            $mail->Subject = 'Restablecimiento de Contraseña';
            $mail->Body    = "Hola,<br><br>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:<br><a href='{$resetLink}'>Restablecer mi contraseña</a><br><br>Este enlace expirará en 10 minutos.<br><br>Si no solicitaste esto, puedes ignorar este correo.";
            
            $mail->send();
        } catch (Exception $e) {
        }
    }
    echo json_encode(['success' => true, 'message' => 'Si tu correo está en nuestro sistema, recibirás un enlace para restablecer tu contraseña.']);
    exit();
}

function performPasswordReset($conexion) {
    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'] ?? '';
    $newPassword = $data['newPassword'] ?? '';

    $stmt = $conexion->prepare("SELECT * FROM PasswordResets WHERE Token = ? AND Used = FALSE AND ExpiresAt > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($reset = $resultado->fetch_assoc()) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt_user = $conexion->prepare("UPDATE Usuarios SET PasswordHash = ? WHERE UserID = ?");
        $stmt_user->bind_param("si", $passwordHash, $reset['UserID']);
        $stmt_user->execute();
        
        $stmt_token = $conexion->prepare("UPDATE PasswordResets SET Used = TRUE WHERE ResetID = ?");
        $stmt_token->bind_param("i", $reset['ResetID']);
        $stmt_token->execute();

        logAction($conexion, 'Contraseña Restablecida', $reset['UserID']);
        echo json_encode(['success' => true, 'message' => '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'El enlace no es válido o ha expirado.']);
    }
    exit();
}
?>