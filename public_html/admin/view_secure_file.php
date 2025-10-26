<?php

require_once __DIR__ . '/../remesas_private/src/core/serve_secure_file.php';

if (!isset($_SESSION['user_rol_name']) || $_SESSION['user_rol_name'] !== 'Admin') {
    http_response_code(403); 
    die("Acceso denegado. Se requiere rol de administrador.");
}