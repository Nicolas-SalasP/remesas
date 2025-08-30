<?php
// api/index.php
// Este es el único punto de entrada para toda la lógica del backend.

// Iniciar la sesión al principio de todo para la gestión de usuarios.
session_start();

// Cargar el archivo de conexión a la base de datos.
require_once '../../src/database/connection.php'; 

// Leer la acción solicitada desde la URL (ej: ?accion=loginUser).
$accion = $_GET['accion'] ?? '';

// Router principal: dirige la petición a la función correspondiente.
switch ($accion) {
    case 'test': 
        echo json_encode(['success' => true, 'message' => 'API conectada correctamente.']);
        break; 

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

    default:
        // Si la acción no es reconocida, se devuelve un error.
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

// Se cierra la conexión a la base de datos al final de la ejecución.
$conexion->close();

// =====================================================================
// --- SECCIÓN DE FUNCIONES DE LA API ---
// =====================================================================


/**
 * Obtiene el valor actual del Dólar BCV haciendo web scraping.
 */
function getDolarBcv() {
    $url = 'https://www.bcv.org.ve/';
    $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $html = @file_get_contents($url, false, $context);

    if ($html === false) {
        echo json_encode(['success' => false, 'error' => 'No se pudo conectar con el sitio del BCV.']);
        return;
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
        echo json_encode(['success' => false, 'error' => 'No se pudo encontrar el valor del dólar en la página del BCV.']);
    }
}


/**
 * Registra un nuevo usuario en la base de datos, incluyendo la imagen de su documento.
 * @param mysqli $conexion - La conexión a la base de datos.
 */
function registerUser($conexion) {
    // Los datos vienen de un FormData, se leen con $_POST y $_FILES.
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Email y contraseña son obligatorios.']);
        return;
    }

    // Verificar si el email ya existe
    $stmt_check = $conexion->prepare("SELECT UserID FROM Usuarios WHERE Email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'El correo electrónico ya está registrado.']);
        $stmt_check->close();
        return;
    }
    $stmt_check->close();
    
    // Manejo de la subida del archivo de documento
    $docImagenURL = null;
    if (isset($_FILES['docImage']) && $_FILES['docImage']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = uniqid() . '-' . basename($_FILES['docImage']['name']);
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['docImage']['tmp_name'], $uploadFile)) {
            $docImagenURL = 'uploads/documents/' . $fileName; // Ruta relativa para guardar en la BD
        }
    }

    // Encriptar la contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar el nuevo usuario
    $stmt = $conexion->prepare("INSERT INTO Usuarios (PrimerNombre, SegundoNombre, PrimerApellido, SegundoApellido, Email, PasswordHash, TipoDocumento, NumeroDocumento, DocumentoImagenURL, VerificacionEstado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')");
    $stmt->bind_param("sssssssss", 
        $_POST['primerNombre'], $_POST['segundoNombre'], $_POST['primerApellido'], $_POST['segundoApellido'],
        $email, $passwordHash, $_POST['tipoDocumento'], $_POST['numeroDocumento'], $docImagenURL
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al registrar el usuario: ' . $stmt->error]);
    }
    $stmt->close();
}


/**
 * Verifica las credenciales de un usuario y crea una sesión.
 * @param mysqli $conexion - La conexión a la base de datos.
 */
function loginUser($conexion) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['email']) || empty($data['password'])) {
        echo json_encode(['success' => false, 'error' => 'Correo y contraseña son obligatorios.']);
        return;
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
            echo json_encode(['success' => true, 'redirect' => '/remesas/public/dashboard/']);
        } else {
            echo json_encode(['success' => false, 'error' => 'La contraseña es incorrecta.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado o no verificado.']);
    }
    $stmt->close();
}


/**
 * Obtiene la lista de países según su rol (Origen o Destino).
 * @param mysqli $conexion - La conexión a la base de datos.
 */
function getPaises($conexion) {
    $rol = $_GET['rol'] ?? 'Ambos';
    $stmt = $conexion->prepare("SELECT PaisID, NombrePais FROM Paises WHERE Rol = ? OR Rol = 'Ambos'");
    $stmt->bind_param("s", $rol);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $paises = $resultado->fetch_all(MYSQLI_ASSOC);
    echo json_encode($paises);
    $stmt->close();
}


/**
 * Obtiene las cuentas de beneficiario de un usuario para un país específico.
 * @param mysqli $conexion - La conexión a la base de datos.
 */
function getCuentas($conexion) {
    $userID = $_GET['userID'] ?? 0;
    $paisID = $_GET['paisID'] ?? 0;
    $stmt = $conexion->prepare("SELECT CuentaID, Alias FROM CuentasBeneficiarias WHERE UserID = ? AND PaisID = ?");
    $stmt->bind_param("ii", $userID, $paisID);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cuentas = $resultado->fetch_all(MYSQLI_ASSOC);
    echo json_encode($cuentas);
    $stmt->close();
}


/**
 * Obtiene la tasa de cambio más reciente para una ruta (origen -> destino).
 * @param mysqli $conexion - La conexión a la base de datos.
 */
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
}


/**
 * Añade una nueva cuenta de beneficiario para un usuario.
 * @param mysqli $conexion - La conexión a la base de datos.
 */
function addCuenta($conexion) {
    $data = json_decode(file_get_contents('php://input'), true);
    // (Aquí iría la validación de datos para la nueva cuenta)
    $stmt = $conexion->prepare("INSERT INTO CuentasBeneficiarias (UserID, PaisID, Alias, TipoBeneficiario, TitularPrimerNombre, TitularSegundoNombre, TitularPrimerApellido, TitularSegundoApellido, TitularTipoDocumento, TitularNumeroDocumento, NombreBanco, NumeroCuenta, NumeroTelefono) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssssssssss", 
        $data['userID'], $data['paisID'], $data['alias'], $data['tipoBeneficiario'], 
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
}


/**
 * Crea una nueva transacción en la base de datos.
 * @param mysqli $conexion - La conexión a la base de datos.
 */
function createTransaccion($conexion) {
    $data = json_decode(file_get_contents('php://input'), true);
    // (Aquí iría la validación de datos para la transacción)
    $stmt = $conexion->prepare("INSERT INTO Transacciones (UserID, CuentaBeneficiariaID, TasaID_Al_Momento, MontoOrigen, MonedaOrigen, MontoDestino, MonedaDestino, Estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente de Pago')");
    $stmt->bind_param("iiidssd", 
        $data['userID'], $data['cuentaID'], $data['tasaID'],
        $data['montoOrigen'], $data['monedaOrigen'],
        $data['montoDestino'], $data['monedaDestino']
    );
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'transaccionID' => $conexion->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}
?>