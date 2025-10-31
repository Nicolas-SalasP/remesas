<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - JC Envios' : 'Tu Empresa de Remesas'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/img/SoloLogoNegroSinFondo.png">
    
    <?php ?>
    <?php if (isset($pageScript) && $pageScript === 'seguridad.js'): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <?php endif; ?>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<header class="main-header shadow-sm">
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand logo" href="<?php echo BASE_URL; ?>/index.php">
                <img src="<?php echo BASE_URL; ?>/assets/img/SoloLogoNegroSinFondo.png" alt="Logo JC Envios" height="50">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        
                        <?php  ?>
                        <?php if (isset($_SESSION['twofa_enabled']) && $_SESSION['twofa_enabled'] == 1): ?>
                            
                            <?php ?>
                            <?php if (isset($_SESSION['user_rol_name']) && $_SESSION['user_rol_name'] === 'Admin'): ?>
                                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/index.php">Inicio</a></li>
                                <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/usuarios.php">Gestionar Usuarios</a></li>
                                <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/">Ver Ordenes</a></li>
                                <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/tasas.php">Gestionar Tasas</a></li>
                                <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/paises.php">Gestionar Países</a></li> 
                                <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/verificaciones.php">Verificaciones</a></li>
                                <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/logs.php">Ver Logs</a></li>
                                <li class="nav-item"><a class="nav-link fw-bold text-danger" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a></li>

                            <?php elseif (isset($_SESSION['user_rol_name']) && $_SESSION['user_rol_name'] === 'Operador'): ?>
                                <li class="nav-item"><a class="nav-link fw-bold text-primary" href="<?php echo BASE_URL; ?>/operador/pendientes.php">Transacciones Pendientes</a></li>
                                <li class="nav-item"><a class="nav-link fw-bold text-primary" href="<?php echo BASE_URL; ?>/operador/index.php">Todas las Transacciones</a></li>
                            
                            <?php else: ?>
                                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/index.php">Inicio</a></li>
                                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/">Realizar Transacción</a></li>
                                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/historial.php">Mi Historial</a></li>
                                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/perfil.php">Mi Perfil</a></li>
                                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/seguridad.php">Seguridad</a></li>
                            <?php endif; ?>
                            <?php ?>

                        <?php else: ?>
                            <?php ?>
                            <li class="nav-item"><a class="nav-link active fw-bold text-danger" href="<?php echo BASE_URL; ?>/dashboard/seguridad.php">Configurar Seguridad (2FA)</a></li>
                        <?php endif; ?>
                        <?php ?>

                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/index.php">Inicio</a></li>
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
<?php ?>