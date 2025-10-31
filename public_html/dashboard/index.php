<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

if (isset($_SESSION['user_rol_name']) && $_SESSION['user_rol_name'] === 'Admin') {
    header('Location: ' . BASE_URL . '/admin/');
    exit();
}
if (isset($_SESSION['user_rol_name']) && $_SESSION['user_rol_name'] === 'Operador') {
    header('Location: ' . BASE_URL . '/operador/pendientes.php');
    exit();
}

if (!isset($_SESSION['twofa_enabled']) || $_SESSION['twofa_enabled'] === false) {
    header('Location: ' . BASE_URL . '/dashboard/seguridad.php');
    exit();
}

$estadosBloqueados = ['No Verificado', 'Rechazado'];
if (!isset($_SESSION['verification_status']) || in_array($_SESSION['verification_status'], $estadosBloqueados)) {
    header('Location: ' . BASE_URL . '/dashboard/verificar.php'); 
    exit();
}

$pageTitle = 'Realizar Transacción';
$pageScript = 'dashboard.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';
?>

<?php ?>
<div class="container">
    <div class="row">
        <div class="col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
            <div class="card p-4 p-md-5 shadow-sm">
                
                <form id="remittance-form" novalidate>
                    <input type="hidden" id="user-id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
                    <input type="hidden" id="selected-tasa-id">
                    <input type="hidden" id="selected-cuenta-id">

                    <div class="form-step active" id="step-1">
                        <h3 class="text-center mb-4">Paso 1: Selecciona la Ruta del Envío</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pais-origen" class="form-label">País de Origen</label>
                                <select id="pais-origen" class="form-select" required><option>Cargando...</option></select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pais-destino" class="form-label">País de Destino</label>
                                <select id="pais-destino" class="form-select" required><option>Selecciona un origen</option></select>
                            </div>
                        </div>
                    </div>

                    <div class="form-step" id="step-2">
                        <h3 class="text-center mb-4">Paso 2: Selecciona el Beneficiario</h3>
                        <div class="form-group">
                            <label class="form-label">Cuentas Guardadas</label>
                            <div id="beneficiary-list" class="list-group"></div>
                        </div>
                        <button type="button" id="add-account-btn" class="btn btn-success mt-3">+ Registrar Nueva Cuenta</button>
                    </div>
                    
                    <div class="form-step" id="step-3">
                        <h3 class="text-center mb-4">Paso 3: Ingresa el Monto y Forma de Pago</h3>
                        <div class="row align-items-center">
                            <div class="col-md-5"><label for="monto-origen" class="form-label">Tú envías</label><div class="input-group"><input type="text" inputmode="decimal" id="monto-origen" class="form-control" placeholder="100.000,00" required><span class="input-group-text">CLP</span></div></div>
                            <div class="col-md-2 text-center"><button type="button" id="swap-currency-btn" class="btn btn-light mt-4" title="Invertir cálculo"><i class="bi bi-arrow-left-right"></i></button></div>
                            <div class="col-md-5"><label for="monto-destino" class="form-label">Beneficiario recibe</label><div class="input-group"><input type="text" inputmode="decimal" id="monto-destino" class="form-control" placeholder="0,00"><span class="input-group-text" id="currency-label-destino">VES</span></div></div>
                        </div>
                        <div class="row mt-3"><div class="col-md-12"><label>Tasa de cambio aplicada</label><input type="text" id="tasa-display" class="form-control" readonly></div></div>
                        <div class="mt-3"><label for="forma-pago" class="form-label">¿Cómo nos transferirás?</label><select id="forma-pago" class="form-select" required><option value="">Cargando opciones...</option></select></div>
                    </div>

                    <div class="form-step" id="step-4">
                        <h3 class="text-center mb-4">Paso 4: Resumen de la Orden</h3>
                        <div id="summary-container" class="mb-4"></div>
                        <div class="alert alert-info">Por favor, revisa que todos los datos sean correctos antes de continuar.</div>
                    </div>

                    <div class="form-step" id="step-5">
                        <h3 class="text-center text-success">¡Orden Registrada!</h3>
                        <p class="text-center">Tu orden ha sido registrada con éxito con el ID: <strong id="transaccion-id-final"></strong>. <br>Por favor, ve a tu <a href="historial.php">historial de transacciones</a> para subir el comprobante de pago.</p>
                    </div>

                    <div class="navigation-buttons mt-4 pt-4 border-top">
                        <button type="button" id="prev-btn" class="btn btn-secondary d-none">Anterior</button>
                        <button type="button" id="next-btn" class="btn btn-primary ms-auto">Siguiente</button>
                        <button type="button" id="submit-order-btn" class="btn btn-primary ms-auto d-none">Confirmar y Generar Orden</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addAccountModalLabel">Registrar Nueva Cuenta de Beneficiario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="add-beneficiary-form">
            <div class="row">
                <div class="col-md-6 mb-3"><label for="benef-alias" class="form-label">Alias de la cuenta</label><input type="text" class="form-control" id="benef-alias" name="alias" required></div>
                <div class="col-md-6 mb-3"><label for="benef-tipo" class="form-label">Tipo de Beneficiario</label><select id="benef-tipo" name="tipoBeneficiario" class="form-select" required><option value="">Cargando...</option></select></div>
            </div>
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
            <div class="mb-3">
                <label for="benef-phone-number" class="form-label">Número de Teléfono</label>
                <div class="input-group">
                    <select class="input-group-text" id="benef-phone-code" name="phoneCode"></select>
                    <input type="tel" class="form-control" id="benef-phone-number" name="phoneNumber" required>
                </div>
            </div>
            <div class="row">
                 <div class="col-md-6 mb-3"><label for="benef-bank" class="form-label">Nombre del Banco</label><input type="text" class="form-control" id="benef-bank" name="nombreBanco" required></div>
                <div class="col-md-6 mb-3"><label for="benef-account-num" class="form-label">Número de Cuenta</label><input type="text" class="form-control" id="benef-account-num" name="numeroCuenta" required></div>
            </div>
            <input type="hidden" id="benef-pais-id" name="paisID">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-primary" form="add-beneficiary-form">Guardar Cuenta</button>
      </div>
    </div>
  </div>
</div>
<?php ?>

<?php
require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>