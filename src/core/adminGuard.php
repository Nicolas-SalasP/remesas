<?php
// Iniciar sesión si no está activa para poder leer las variables.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verificar si el usuario ha iniciado sesión.
if (!isset($_SESSION['user_id'])) {
    // Si no ha iniciado sesión, se le niega el acceso.
    http_response_code(401); // 401 Unauthorized
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado. Se requiere iniciar sesión.']);
    exit();
}

// 2. Verificar si el rol del usuario es 'Admin'.
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
    // Si no es admin, se le niega el acceso.
    http_response_code(403); // 403 Forbidden
    echo json_encode(['success' => false, 'error' => 'Acceso denegado. Permisos insuficientes.']);
    exit();
}
?>