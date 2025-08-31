<?php
// /remesas/public/api/index.php

// --- CABECERAS DE PERMISOS (CORS) ---
// Permiten que tu JavaScript se comunique con este archivo de forma segura.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Iniciar la sesión para la gestión de usuarios.
session_start();

// --- CÁLCULO DE RUTA ABSOLUTA A PRUEBA DE ERRORES ---
// 1. Obtiene el directorio del archivo actual (ej: D:\...\public\api)
$currentDir = __DIR__;
// 2. Sube dos niveles para llegar a la raíz del proyecto (ej: D:\...\remesas)
$projectRoot = dirname($currentDir, 2);

// --- INCLUSIÓN DE ARCHIVOS CON LA RUTA ABSOLUTA ---
// Ahora usamos la variable $projectRoot para construir la ruta completa y correcta.
require_once $projectRoot . '/src/database/connection.php';
require_once $projectRoot . '/src/controllers/userController.php';
require_once $projectRoot . '/src/controllers/transactionController.php';
require_once $projectRoot . '/src/controllers/adminController.php';

// Leer la acción solicitada de la URL.
$accion = $_GET['accion'] ?? '';

// Instanciar los controladores, pasándoles la conexión a la BD.
$userController = new UserController($conexion);
$transactionController = new TransactionController($conexion);
$adminController = new AdminController($conexion);

// --- ROUTER PRINCIPAL ---
// Dirige la petición al método del controlador adecuado.
switch ($accion) {
    // Rutas de Usuario
    case 'registerUser':
        $userController->register();
        break;
    case 'loginUser':
        $userController->login();
        break;

    // Rutas de Transacciones y Datos
    case 'getDolarBcv':
        $transactionController->getBcvRate();
        break;
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
    
    // Rutas de Administrador (Protegidas)
    case 'adminUpdateTasa':
        // El guardián también debe usar la ruta absoluta
        require_once $projectRoot . '/src/core/adminGuard.php';
        $adminController->updateTasa();
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Acción no válida o no encontrada.']);
}

// Se cierra la conexión a la base de datos al final de la ejecución.
$conexion->close();
?>