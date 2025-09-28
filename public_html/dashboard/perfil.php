<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . '/login.php'); exit(); }

$pageTitle = 'Mi Perfil';
$pageScript = 'perfil.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';
?>

<div class="container mt-4">
    <div class="card p-4 p-md-5 shadow-sm">
        <h1 class="mb-4">Mi Perfil</h1>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Nombre:</strong> <span id="profile-nombre">Cargando...</span></li>
            <li class="list-group-item"><strong>Email:</strong> <span id="profile-email">Cargando...</span></li>
            <li class="list-group-item"><strong>Documento:</strong> <span id="profile-documento">Cargando...</span></li>
            <li class="list-group-item"><strong>Estado de Verificacion:</strong> <span id="profile-estado" class="badge">Cargando...</span></li>
        </ul>
        <div id="verification-link-container" class="mt-4"></div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>