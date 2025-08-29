<?php
session_start(); // Iniciar la sesión para poder acceder a ella
session_unset(); // Liberar todas las variables de sesión
session_destroy(); // Destruir la sesión

// Redirigir al usuario a la página de inicio
header('Location: /remesas/public/index.php');
exit();
?>