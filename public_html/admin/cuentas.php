<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_rol_name']) || $_SESSION['user_rol_name'] !== 'Admin') {
    die("Acceso denegado.");
}

$pageTitle = 'Cuentas Bancarias (Empresa)';
$pageScript = 'admin-cuentas.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';

$formasPago = $conexion->query("SELECT FormaPagoID, Nombre FROM formas_pago WHERE Activo = 1")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Cuentas Bancarias</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cuentaModal" id="btn-nueva-cuenta">
            <i class="bi bi-plus-circle"></i> Nueva Cuenta
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="cuentas-table">
                    <thead class="table-light">
                        <tr>
                            <th>Forma de Pago</th>
                            <th>Banco / Titular</th>
                            <th>Datos de Cuenta</th>
                            <th>Color</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cuentaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cuentaModalLabel">Nueva Cuenta Bancaria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="cuenta-form">
                    <input type="hidden" id="cuenta-id" name="id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Asignar a Forma de Pago</label>
                            <select class="form-select" id="forma-pago-id" name="formaPagoId" required>
                                <?php foreach ($formasPago as $fp): ?>
                                    <option value="<?= $fp['FormaPagoID'] ?>"><?= $fp['Nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Banco</label>
                            <input type="text" class="form-control" id="banco" name="banco" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Titular</label>
                            <input type="text" class="form-control" id="titular" name="titular" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RUT</label>
                            <input type="text" class="form-control" id="rut" name="rut" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Cuenta</label>
                            <input type="text" class="form-control" id="tipo-cuenta" name="tipoCuenta" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de Cuenta</label>
                            <input type="text" class="form-control" id="numero-cuenta" name="numeroCuenta" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email (Opcional)</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Color del Título (PDF)</label>
                            <input type="color" class="form-control form-control-color w-100" id="color-hex"
                                name="colorHex" value="#000000">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Estado</label>
                            <select class="form-select" id="activo" name="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Instrucciones Adicionales (PDF)</label>
                        <textarea class="form-control" id="instrucciones" name="instrucciones" rows="3"></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Guardar Cuenta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>