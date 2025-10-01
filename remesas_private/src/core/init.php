<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

session_start();

require_once __DIR__ . '/ErrorHandler.php';
set_exception_handler('App\\Core\\exception_handler');

$tiempo_limite = 15 * 60; 

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > $tiempo_limite)) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php?session_expired=1');
        exit();
    }
    $_SESSION['ultima_actividad'] = time();
}

$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conexion->connect_error) {
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos.']));
    } else {
        die("Error de conexión: " . $conexion->connect_error);
    }
}

$conexion->set_charset("utf8mb4");

function logAction($conexion, $accion, $userId = null, $detalles = '') {
    $stmt = $conexion->prepare("INSERT INTO logs (UserID, Accion, Detalles) VALUES (?, ?, ?)");

    $stmt->bind_param("iss", $userId, $accion, $detalles);
    $stmt->execute();
    $stmt->close();
}