<?php
    // Debemos iniciar la sesión en la cabecera para poder leer si el usuario está logueado
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Tu Empresa' : 'Tu Empresa de Remesas'; ?></title>
    <link rel="stylesheet" href="/remesas/public/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <a href="/remesas/public/index.php" class="logo">
                TuLogo
            </a>
            <nav class="main-nav">
                <ul>
                    <li><a href="/remesas/public/index.php">Inicio</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="/remesas/public/dashboard/">Realizar Transacción</a></li>
                        <li><a href="/remesas/public/dashboard/historial.php">Mi Historial</a></li>
                    <?php else: ?>
                        <li><a href="/remesas/public/quienes-somos.php">Quiénes Somos</a></li>
                        <li><a href="/remesas/public/contacto.php">Contacto</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="header-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="welcome-user">Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="/remesas/public/logout.php" class="btn btn-secondary">Salir</a>
                <?php else: ?>
                    <a href="/remesas/public/login.php" class="btn btn-primary">Ingresar / Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>