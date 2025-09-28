<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
    die("Acceso denegado.");
}

$pageTitle = 'Transacciones Pendientes';
$pageScript = 'admin.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';

$transacciones = $conexion->query("
    SELECT T.*, U.PrimerNombre, U.PrimerApellido, CONCAT(CB.TitularPrimerNombre, ' ', CB.TitularPrimerApellido) AS BeneficiarioNombreCompleto
    FROM transacciones T
    JOIN usuarios U ON T.UserID = U.UserID
    JOIN cuentasbeneficiarias CB ON T.CuentaBeneficiariaID = CB.CuentaID
    WHERE T.Estado IN ('En Verificación', 'En Proceso')
    ORDER BY T.FechaTransaccion ASC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mt-4">
    <h1 class="mb-4">Transacciones Pendientes</h1>
    <p><a href="<?php echo BASE_URL; ?>/admin/">Volver al panel principal</a></p>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Beneficiario</th>
                    <th>Comprobante de Pago</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transacciones)): ?>
                    <tr><td colspan="5" class="text-center">¡Excelente! No hay transacciones que requieran tu atención.</td></tr>
                <?php else: ?>
                    <?php foreach ($transacciones as $tx): ?>
                        <tr>
                            <td><?php echo $tx['TransaccionID']; ?></td>
                            <td><?php echo htmlspecialchars($tx['PrimerNombre'] . ' ' . $tx['PrimerApellido']); ?></td>
                            <td><?php echo htmlspecialchars($tx['BeneficiarioNombreCompleto']); ?></td>
                            <td>
                                <a href="<?php echo BASE_URL . '/' . $tx['ComprobanteURL']; ?>" target="_blank" class="btn btn-sm btn-info">Ver Comprobante</a>
                            </td>
                            <td>
                                <?php if ($tx['Estado'] == 'En Verificación'): ?>
                                    <button class="btn btn-sm btn-success process-btn" data-tx-id="<?php echo $tx['TransaccionID']; ?>">Confirmar y Procesar</button>
                                    <button class="btn btn-sm btn-danger reject-btn" data-tx-id="<?php echo $tx['TransaccionID']; ?>">Rechazar Pago</button>
                                <?php elseif ($tx['Estado'] == 'En Proceso'): ?>
                                    <button class="btn btn-sm btn-primary admin-upload-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#adminUploadModal" 
                                            data-tx-id="<?php echo $tx['TransaccionID']; ?>">
                                        Subir Comprobante de Envío
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="adminUploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Subir Comprobante de Envío</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Estás subiendo el comprobante para la transacción <strong id="modal-admin-tx-id"></strong>.</p>
        <form id="admin-upload-form" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="adminReceiptFile" class="form-label">Selecciona el archivo</label>
                <input class="form-control" type="file" id="adminReceiptFile" name="receiptFile" required>
            </div>
            <input type="hidden" id="adminTransactionIdField" name="transactionId">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" form="admin-upload-form">Confirmar Envío</button>
      </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>