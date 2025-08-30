<?php
    $pageTitle = 'Ingresar o Registrarse';
    $pageScript = 'login.js'; 
    require_once '../src/templates/header.php';
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
                    <input type="email" class="form-control" id="login-email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="login-password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="login-password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Ingresar</button>
            </form>
        </div>

        <div id="register-form" class="auth-form">
            <form id="form-registro" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="reg-firstname" class="form-label">Primer Nombre</label>
                        <input type="text" class="form-control" id="reg-firstname" name="primerNombre" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reg-secondname" class="form-label">Segundo Nombre</label>
                        <input type="text" class="form-control" id="reg-secondname" name="segundoNombre">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="reg-lastname1" class="form-label">Primer Apellido</label>
                        <input type="text" class="form-control" id="reg-lastname1" name="primerApellido" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reg-lastname2" class="form-label">Segundo Apellido</label>
                        <input type="text" class="form-control" id="reg-lastname2" name="segundoApellido">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="reg-email" class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" id="reg-email" name="email" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="reg-doc-type" class="form-label">Tipo de Documento</label>
                        <select id="reg-doc-type" name="tipoDocumento" class="form-select" required>
                            <option value="">Selecciona...</option>
                            <option value="RUT">RUT</option>
                            <option value="Cédula Venezolana">Cédula Venezolana</option>
                            <option value="Cédula Colombiana">Cédula Colombiana</option>
                            <option value="Pasaporte">Pasaporte</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reg-doc-number" class="form-label">Número de Documento</label>
                        <input type="text" class="form-control" id="reg-doc-number" name="numeroDocumento" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="reg-doc-image" class="form-label">Imagen de tu Documento (JPG, PNG)</label>
                    <input class="form-control" type="file" id="reg-doc-image" name="docImage" accept="image/png, image/jpeg" required>
                    <div class="form-text">Asegúrate de que la imagen sea clara y legible.</div>
                </div>
                <div class="mb-3">
                    <label for="reg-password" class="form-label">Crear Contraseña</label>
                    <input type="password" class="form-control" id="reg-password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Crear Cuenta</button>
            </form>
        </div>
    </div>
</div>

<?php
    require_once '../src/templates/footer.php';
?>