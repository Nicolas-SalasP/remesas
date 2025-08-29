<?php
    $pageTitle = 'Ingresar o Registrarse';
    require_once '../src/templates/header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-tabs">
            <button class="tab-link active" data-target="login-form">Ingresar</button>
            <button class="tab-link" data-target="register-form">Registrarse</button>
        </div>

        <div id="login-form" class="auth-form active">
            <form>
                <div class="form-group">
                    <label for="login-email">Correo Electrónico</label>
                    <input type="email" id="login-email" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Contraseña</label>
                    <input type="password" id="login-password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full-width">Ingresar</button>
            </form>
        </div>

        <div id="register-form" class="auth-form">
            <form>
                <div class="form-row">
                    <div class="form-group">
                        <label for="reg-firstname">Primer Nombre</label>
                        <input type="text" id="reg-firstname" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-secondname">Segundo Nombre</label>
                        <input type="text" id="reg-secondname">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="reg-lastname1">Primer Apellido</label>
                        <input type="text" id="reg-lastname1" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-lastname2">Segundo Apellido</label>
                        <input type="text" id="reg-lastname2">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-email">Correo Electrónico</label>
                    <input type="email" id="reg-email" required>
                </div>
                <div class="form-group">
                    <label for="reg-doc-type">Tipo de Documento</label>
                    <select id="reg-doc-type" required>
                        <option value="">Selecciona...</option>
                        <option value="RUT">RUT</option>
                        <option value="Cédula Venezolana">Cédula Venezolana</option>
                        <option value="Cédula Colombiana">Cédula Colombiana</option>
                        <option value="Pasaporte">Pasaporte</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reg-doc-number">Número de Documento</label>
                    <input type="text" id="reg-doc-number" required>
                </div>
                <div class="form-group">
                    <label for="reg-password">Crear Contraseña</label>
                    <input type="password" id="reg-password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full-width">Crear Cuenta</button>
            </form>
        </div>
    </div>
</div>

<?php
    require_once '../src/templates/footer.php';
?>