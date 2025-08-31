<?php
session_start();

require_once '../../src/database/connection.php';

$accion = $_GET['accion'] ?? '';

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

    case 'updateTasa':
        require_once '../../src/core/adminGuard.php';
        updateTasa($conexion);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

$conexion->close();

function registerUser($conexion) {

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
    $stmt = $conexion->prepare("SELECT UserID, PasswordHash, PrimerNombre, Rol FROM Usuarios WHERE Email = ? AND VerificacionEstado = 'Aprobado'");
    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        if (password_verify($data['password'], $usuario['PasswordHash'])) {
            $_SESSION['user_id'] = $usuario['UserID'];
            $_SESSION['user_name'] = $usuario['PrimerNombre'];
            $_SESSION['user_rol'] = $usuario['Rol']; // <-- GUARDAMOS EL ROL EN LA SESIÓN

            echo json_encode(['success' => true, 'redirect' => '/remesas/public/dashboard/']);
        } else {
            echo json_encode(['success' => false, 'error' => 'La contraseña es incorrecta.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado o no verificado.']);
    }
    $stmt->close();
}

function updateTasa($conexion) {
    // Como esta función solo se ejecuta si adminGuard.php lo permite, podemos estar seguros de que solo un admin llegará aquí.
    echo json_encode(['success' => true, 'message' => 'Tasa actualizada correctamente por un administrador.']);
}


function getDolarBcv() {

}
function getPaises($conexion) {
}
?>