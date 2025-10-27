<?php
if (!defined('BASE_URL')) {
    die("Error de configuración: No se pudo cargar el entorno.");
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

if (!isset($_SESSION['user_rol_name']) || 
    ($_SESSION['user_rol_name'] !== 'Operador' && $_SESSION['user_rol_name'] !== 'Admin')) {
    
    header('Location: ' . BASE_URL . '/dashboard/');
    exit();
}

if ($_SESSION['user_rol_name'] === 'Operador') {
    
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    
    $allowed_scripts = [
        'pendientes.php',   
        'ver-comprobante.php',  
        'admin.js',            
        'logout.php'            
    ];
    
    if (!in_array($current_script, $allowed_scripts)) {
        header('Location: ' . BASE_URL . '/operador/pendientes.php');
        exit();
    }
}
?>