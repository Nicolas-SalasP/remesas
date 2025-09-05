<?php
// ARCHIVO: /src/core/init.php

// 1. Cargar la configuración central
require_once __DIR__ . '/../../config.php';

// 2. Iniciar la sesión
session_start();

// 3. Crear la conexión a la base de datos
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

/**
 * Registra una acción en la tabla de Logs.
 * @param mysqli $conexion La conexión a la base de datos.
 * @param string $accion La descripción de la acción.
 * @param int|null $userId El ID del usuario que realiza la acción (opcional).
 * @param string $detalles Información adicional (opcional).
 */
function logAction($conexion, $accion, $userId = null, $detalles = '') {
    $stmt = $conexion->prepare("INSERT INTO Logs (UserID, Accion, Detalles) VALUES (?, ?, ?)");

    $stmt->bind_param("iss", $userId, $accion, $detalles);
    $stmt->execute();
    $stmt->close();
}