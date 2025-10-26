<?php
require_once __DIR__ . '/../remesas_private/src/core/init.php';

$pageTitle = 'Ingresar o Registrarse';
$pageScripts = [
    'components/rut-validator.js',
    'login.js'
];
require_once __DIR__ . '/../remesas_private/src/templates/header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-tabs">
            <button class="tab-link active" data-target="login-form">Ingresar</button>
            <button class="tab-link" data-target="register-form">Registrarse</button>
        </div>

        <div id="login-form" class="auth-form active">
            <form id="form-login">
                <div class="mb-3">
                    <label for="login-email" class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" id="login-email" name="email" required autocomplete="username">
                </div>
                <div class="mb-3">
                    <label for="login-password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="login-password" name="password" required autocomplete="current-password">
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?php echo BASE_URL; ?>/forgot-password.php">¿Olvidaste tu contraseña?</a>
                    <button type="submit" class="btn btn-primary">Ingresar</button>
                </div>
                </form>
        </div>

        <div id="register-form" class="auth-form">
            <form id="form-registro">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="reg-firstname" class="form-label">Primer Nombre</label>
                        <input type="text" class="form-control" id="reg-firstname" name="primerNombre" required autocomplete="given-name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reg-secondname" class="form-label">Segundo Nombre</label>
                        <input type="text" class="form-control" id="reg-secondname" name="segundoNombre" autocomplete="additional-name">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="reg-lastname1" class="form-label">Primer Apellido</label>
                        <input type="text" class="form-control" id="reg-lastname1" name="primerApellido" required autocomplete="family-name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reg-lastname2" class="form-label">Segundo Apellido</label>
                        <input type="text" class="form-control" id="reg-lastname2" name="segundoApellido" autocomplete="family-name">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="reg-email" class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" id="reg-email" name="email" required autocomplete="email">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="reg-doc-type" class="form-label">Tipo de Documento</label>
                        <select id="reg-doc-type" name="tipoDocumento" class="form-select" required>
                            <option value="">Cargando...</option> {/* JS llenará esto */}
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reg-doc-number" class="form-label">Número de Documento</label>
                        <input type="text" class="form-control" id="reg-doc-number" name="numeroDocumento" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="reg-password" class="form-label">Crear Contraseña</label>
                    <input type="password" class="form-control" id="reg-password" name="password" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Crear Cuenta</button>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../remesas_private/src/templates/footer.php';
?>