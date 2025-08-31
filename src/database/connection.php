<?php
// --- 1. Credenciales de la Base de Datos ---
// Para XAMPP (local):
$servidor = "localhost";
$usuario_db = "root";
$password_db = "";
$nombre_db = "remesas_bd";

// Para cPanel (en vivo):
// $usuario_db = "usuario_cpanel_db";
// $password_db = "contraseña_cpanel";
// $nombre_db = "usuario_cpanel_remesas_db";

// --- 2. Crear la Conexión ---
$conexion = new mysqli($servidor, $usuario_db, $password_db, $nombre_db);

// --- 3. Verificar si Hubo un Error ---
if ($conexion->connect_error) {
    die(json_encode(['error' => 'Error de conexión: ' . $conexion->connect_error]));
}

// --- 4. Configurar el Juego de Caracteres ---
$conexion->set_charset("utf8mb4");

// --- 5. Establecer Encabezado de Respuesta (Opcional pero recomendado aquí) ---
header('Content-Type: application/json');
?>