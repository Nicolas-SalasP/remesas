<?php
    require_once '../../src/core/session.php';
    
    $pageTitle = 'Realizar Transacción';
    $pageScript = 'dashboard.js'; // Le decimos al footer que cargue el JS del dashboard
    require_once '../../src/templates/header.php';
?>

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
                            <div id="beneficiary-list" class="list-group">
                                </div>
                        </div>
                        <button type="button" id="add-account-btn" class="btn btn-success mt-3">+ Registrar Nueva Cuenta</button>
                        </div>
                    
                    <div class="form-step" id="step-3">
                        <h3 class="text-center mb-4">Paso 3: Ingresa el Monto</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="monto-origen" class="form-label">Tú envías (<span id="currency-label-origen">CLP</span>)</label>
                                <input type="number" id="monto-origen" class="form-control" placeholder="100000" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Tasa de cambio aplicada</label>
                                <input type="text" id="tasa-display" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Beneficiario recibe (Aprox.)</label>
                            <input type="text" id="monto-destino" class="form-control" readonly>
                        </div>
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

<?php
    require_once '../../src/templates/footer.php';
?>