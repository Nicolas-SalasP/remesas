<?php
// src/core/session.php

// Iniciar la sesión para poder leer las variables de sesión.
session_start();

// Verificar si la variable de sesión del usuario NO está definida.
if (!isset($_SESSION['user_id'])) {
    // Si no ha iniciado sesión, redirigir a la página de login.
    header('Location: /remesas/public/login.php');
    // Detener la ejecución del script para que no cargue el resto de la página protegida.
    exit();
}

// Si la sesión existe, el script termina y la página protegida puede continuar cargándose.
?>