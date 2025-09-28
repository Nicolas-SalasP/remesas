<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - JC Envios' : 'Tu Empresa de Remesas'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/img/SoloLogoNegroSinFondo.png">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<header class="main-header shadow-sm">
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand logo" href="<?php echo BASE_URL; ?>/index.php">
                <img src="<?php echo BASE_URL; ?>/assets/img/SoloLogoNegroSinFondo.png" alt="Logo JC Envios" height="50">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/index.php">Inicio</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_rol'] === 'Admin'): ?>
                            <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/usuarios.php">Gestionar Usuarios</a></li>
                            <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/">Ver Transacciones</a></li>
                            <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/tasas.php">Gestionar Tasas</a></li>
                            <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/paises.php">Gestionar Países</a></li> 
                            <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/verificaciones.php">Verificaciones</a></li>
                            <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/logs.php">Ver Logs</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/">Realizar Transacción</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/historial.php">Mi Historial</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/perfil.php">Mi Perfil</a></li> <li><hr class="dropdown-divider"></li>
                            
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/quienes-somos.php">Quiénes Somos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/contacto.php">Contacto</a></li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="navbar-text me-3">Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-secondary">Salir</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-primary">Ingresar / Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>
<main class="flex-grow-1 py-5">