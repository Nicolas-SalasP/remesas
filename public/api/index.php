<?php
// api/index.php
// Este archivo es el único punto de entrada. Actúa como un controlador de tráfico.

session_start();

// Cargar la conexión y los controladores necesarios
require_once '../../src/database/connection.php';
require_once '../../src/Controllers/UserController.php';
require_once '../../src/Controllers/TransactionController.php';

// Leer la acción solicitada de la URL
$accion = $_GET['accion'] ?? '';

// Instanciar los controladores, pasándoles la conexión a la BD
$userController = new UserController($conexion);
$transactionController = new TransactionController($conexion);

// Router principal: dirige la petición al método del controlador adecuado
switch ($accion) {
    // --- Rutas de Usuario ---
    case 'registerUser':
        $userController->register();
        break;
    case 'loginUser':
        $userController->login();
        break;

    // --- Rutas de Transacciones y Datos ---
    case 'getPaises':
        $transactionController->getPaises();
        break;
    case 'getCuentas':
        $transactionController->getCuentas();
        break;
    case 'getTasa':
        $transactionController->getTasa();
        break;
    case 'createTransaccion':
        $transactionController->create();
        break;
    case 'getDolarBcv':
        $transactionController->getBcvRate();
        break;
    
    // --- Rutas de prueba ---
    case 'test': 
        echo json_encode(['success' => true, 'message' => 'API conectada correctamente.']);
        break; 

    default:
        // Si la acción no es reconocida, se devuelve un error.
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

// Se cierra la conexión a la base de datos al final.
$conexion->close();
?>