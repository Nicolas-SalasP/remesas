<?php
require_once __DIR__ . '/../remesas_private/src/core/init.php';

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_rol_name']) && $_SESSION['user_rol_name'] === 'Admin') {
        header('Location: ' . BASE_URL . '/admin/');
    } elseif (isset($_SESSION['user_rol_name']) && $_SESSION['user_rol_name'] === 'Operador') {
        header('Location: ' . BASE_URL . '/operador/pendientes.php');
    } else {
        header('Location: ' . BASE_URL . '/dashboard/');
    }
    exit();
}

if (!isset($_SESSION['2fa_user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$pageTitle = 'Verificar Identidad';
$pageScript = 'verify-2fa.js';
require_once __DIR__ . '/../remesas_private/src/templates/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow-sm p-4">
                <h3 class="card-title text-center mb-3">Verificación de Dos Pasos</h3>
                <p class="text-center text-muted">Ingresa el código de 6 dígitos de tu aplicación de autenticación.</p>
                
                <form id="form-2fa-verify">
                    <div class="mb-3">
                        <label for="2fa-code" class="form-label">Código de Verificación</label>
                        <input type="text" class="form-control form-control-lg text-center" 
                               id="2fa-code" name="code" 
                               required 
                               inputmode="numeric" 
                               pattern="\d{6}" 
                               maxlength="6"
                               autocomplete="one-time-code"
                               placeholder="123456">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Verificar</button>
                    </div>
                </form>
                
                <p class="text-center text-muted mt-3 mb-0">
                    <small>¿Problemas? Intenta con un <a href="#backup-code-form" data-bs-toggle="collapse">código de respaldo</a>.</small>
                </p>
                
                <div class="collapse" id="backup-code-form">
                    <hr>
                    <p class="text-center text-muted">Ingresa uno de tus códigos de respaldo de un solo uso.</p>
                    <form id="form-2fa-backup">
                         <div class="mb-3">
                            <label for="2fa-backup-code" class="form-label">Código de Respaldo</label>
                            <input type="text" class="form-control" 
                                   id="2fa-backup-code" name="code" 
                                   required 
                                   autocomplete="off"
                                   placeholder="abc123xyz">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-secondary">Usar Código de Respaldo</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../remesas_private/src/templates/footer.php';
?>