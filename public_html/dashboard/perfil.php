<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . '/login.php'); exit(); }

$pageTitle = 'Mi Perfil';

$pageScript = ''; 

$pageScripts = [
    'components/rut-validator.js',
    'pages/perfil.js'
];

require_once __DIR__ . '/../../remesas_private/src/templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card p-4 p-md-5 shadow-sm">
                <h1 class="mb-4">Mi Perfil</h1>
                <div id="profile-loading" class="text-center">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
                
                <form id="profile-form" class="d-none" enctype="multipart/form-data">
                    <div class="text-center mb-4">
                        <img id="profile-img-preview" src="<?php echo BASE_URL; ?>/assets/img/SoloLogoNegroSinFondo.png" alt="Foto de perfil" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #eee;">
                        <div class="mt-2">
                            <label for="profile-foto-input" class="btn btn-sm btn-outline-primary">Cambiar Foto</label>
                            <input type="file" class="d-none" id="profile-foto-input" name="fotoPerfil" accept="image/png, image/jpeg, image/webp">
                        </div>
                    </div>
                
                    <div class="mb-3">
                        <label for="profile-nombre" class="form-label">Nombre</label>
                        <input type="text" id="profile-nombre" class="form-control" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label for="profile-email" class="form-label">Email</label>
                        <input type="email" id="profile-email" class="form-control" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label for="profile-documento" class="form-label">Documento</label>
                        <input type="text" id="profile-documento" class="form-control" readonly disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile-telefono" class="form-label">Teléfono</label>
                        <div class="input-group">
                            <select class="input-group-text" id="profile-phone-code" name="profilePhoneCode" style="max-width: 130px;"></select>
                            <input type="tel" id="profile-telefono" name="telefono" class="form-control" required placeholder="Número">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estado de Verificación</label>
                        <div><span id="profile-estado" class="badge">Cargando...</span></div>
                    </div>
                    <div id="verification-link-container" class="mt-2 mb-3"></div>

                    <button type="submit" id="profile-save-btn" class="btn btn-primary w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card p-4 p-md-5 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0">Mis Beneficiarios</h2>
                    <button id="add-account-btn" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                        <i class="bi bi-plus-circle"></i> Nuevo Beneficiario
                    </button>
                </div>
                <div id="beneficiarios-loading" class="text-center">
                    <div class="spinner-border text-secondary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
                <div id="beneficiary-list-container" class="list-group">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addAccountModalLabel">Registrar Beneficiario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="add-beneficiary-form" novalidate>
            <input type="hidden" id="benef-cuenta-id" name="cuentaId">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="benef-pais-id" class="form-label">País de Destino</label>
                    <select id="benef-pais-id" name="paisID" class="form-select" required>
                        <option value="">Cargando...</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="benef-alias" class="form-label">Alias de la cuenta</label>
                    <input type="text" class="form-control" id="benef-alias" name="alias" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="benef-tipo" class="form-label">Tipo de Beneficiario</label>
                    <select id="benef-tipo" name="tipoBeneficiario" class="form-select" required>
                        <option value="">Cargando...</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="benef-bank" class="form-label">Nombre del Banco</label>
                    <input type="text" class="form-control" id="benef-bank" name="nombreBanco" required>
                </div>
            </div>
            <hr>
            <h6 class="text-muted">Datos del Titular</h6>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="benef-firstname" class="form-label">Primer Nombre</label><input type="text" class="form-control" id="benef-firstname" name="primerNombre" required></div>
                <div class="col-md-6 mb-3"><label for="benef-secondname" class="form-label">Segundo Nombre</label><input type="text" class="form-control" id="benef-secondname" name="segundoNombre"></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="benef-lastname" class="form-label">Primer Apellido</label><input type="text" class="form-control" id="benef-lastname" name="primerApellido" required></div>
                <div class="col-md-6 mb-3"><label for="benef-secondlastname" class="form-label">Segundo Apellido</label><input type="text" class="form-control" id="benef-secondlastname" name="segundoApellido"></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="benef-doc-type" class="form-label">Tipo de Documento</label><select id="benef-doc-type" name="tipoDocumento" class="form-select" required><option value="">Cargando...</option></select></div>
                <div class="col-md-6 mb-3"><label for="benef-doc-number" class="form-label">Número de Documento</label><input type="text" class="form-control" id="benef-doc-number" name="numeroDocumento" required></div>
            </div>
             <div class="row">
                 <div class="col-md-6 mb-3"><label for="benef-account-num" class="form-label">Número de Cuenta</label><input type="text" class="form-control" id="benef-account-num" name="numeroCuenta" required></div>
                 <div class="col-md-6 mb-3">
                    <label for="benef-phone-number" class="form-label">Teléfono</label>
                    <div class="input-group">
                        <select class="input-group-text" id="benef-phone-code" name="phoneCode" style="max-width: 130px;"></select>
                        <input type="tel" class="form-control" id="benef-phone-number" name="phoneNumber" required placeholder="Número">
                    </div>
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-primary" form="add-beneficiary-form">Guardar Beneficiario</button>
      </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>