<?php
require_once __DIR__ . '/../../config.php';

session_start();

$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

function logAction($conexion, $accion, $userId = null, $detalles = '') {
    $stmt = $conexion->prepare("INSERT INTO Logs (UserID, Accion, Detalles) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $accion, $detalles);
    $stmt->execute();
    $stmt->close();
}
?>