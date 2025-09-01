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

    $stmt = $conexion->prepare("SELECT UserID, PasswordHash, PrimerNombre FROM Usuarios WHERE Email = ? AND VerificacionEstado = 'Aprobado'");
    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        if (password_verify($data['password'], $usuario['PasswordHash'])) {
            $_SESSION['user_id'] = $usuario['UserID'];
            $_SESSION['user_name'] = $usuario['PrimerNombre'];
            echo json_encode(['success' => true, 'redirect' => BASE_URL . '/dashboard/']);
        } else {
            echo json_encode(['success' => false, 'error' => 'La contraseña es incorrecta.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado o no verificado.']);
    }
    $stmt->close();
    exit();
}

function getPaises($conexion) {
    $rol = $_GET['rol'] ?? 'Ambos';
    $stmt = $conexion->prepare("SELECT PaisID, NombrePais, CodigoMoneda FROM Paises WHERE Rol = ? OR Rol = 'Ambos'");
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
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
        exit();
    }
    $userID = $_SESSION['user_id'];

    $stmt = $conexion->prepare(
        "INSERT INTO CuentasBeneficiarias (UserID, PaisID, Alias, TipoBeneficiario, TitularPrimerNombre, TitularSegundoNombre, TitularPrimerApellido, TitularSegundoApellido, TitularTipoDocumento, TitularNumeroDocumento, NombreBanco, NumeroCuenta, NumeroTelefono) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    $stmt->bind_param(
        "iisssssssssss", 
        $userID, 
        $data['paisID'], $data['alias'], $data['tipoBeneficiario'], 
        $data['primerNombre'], $data['segundoNombre'], $data['primerApellido'], $data['segundoApellido'], 
        $data['tipoDocumento'], $data['numeroDocumento'], $data['nombreBanco'], 
        $data['numeroCuenta'], $data['numeroTelefono']
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conexion->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
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
?>