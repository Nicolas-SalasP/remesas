<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_rol_name']) || $_SESSION['user_rol_name'] !== 'Admin') {
    die("Acceso denegado.");
}

$pageTitle = 'Transacciones Pendientes';
$pageScript = 'admin.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';

$registrosPorPagina = 100;
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
if ($paginaActual < 1)
    $paginaActual = 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

$sqlCount = "
    SELECT COUNT(*) as total 
    FROM transacciones T
    JOIN estados_transaccion ET ON T.EstadoID = ET.EstadoID
    WHERE ET.NombreEstado IN ('En Verificación', 'En Proceso')
";
$totalRegistros = $conexion->query($sqlCount)->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

$transacciones = $conexion->query("
    SELECT T.*,
        U.PrimerNombre, U.PrimerApellido,
        T.BeneficiarioNombre AS BeneficiarioNombreCompleto,
        ET.NombreEstado AS EstadoNombre
    FROM transacciones T
    JOIN usuarios U ON T.UserID = U.UserID
    JOIN estados_transaccion ET ON T.EstadoID = ET.EstadoID
    WHERE ET.NombreEstado IN ('En Verificación', 'En Proceso')
    ORDER BY 
        CASE WHEN T.FechaSubidaComprobante IS NOT NULL THEN T.FechaSubidaComprobante ELSE T.FechaTransaccion END ASC
    LIMIT $registrosPorPagina OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mt-4">
    <h1 class="mb-4">Transacciones Pendientes</h1>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
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
                    <tr>
                        <td colspan="5" class="text-center">¡Excelente! No hay transacciones que requieran tu atención.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transacciones as $tx): ?>
                        <tr>
                            <td><?php echo $tx['TransaccionID']; ?></td>
                            <td><?php echo htmlspecialchars($tx['PrimerNombre'] . ' ' . $tx['PrimerApellido']); ?></td>
                            <td><?php echo htmlspecialchars($tx['BeneficiarioNombreCompleto']); ?></td>
                            <td>
                                <?php if (!empty($tx['ComprobanteURL'])): ?>
                                    <button class="btn btn-sm btn-info view-comprobante-btn-admin" data-bs-toggle="modal"
                                        data-bs-target="#viewComprobanteModal" data-tx-id="<?php echo $tx['TransaccionID']; ?>"
                                        data-comprobante-url="<?php echo BASE_URL . htmlspecialchars($tx['ComprobanteURL']); ?>"
                                        data-envio-url="<?php echo !empty($tx['ComprobanteEnvioURL']) ? BASE_URL . htmlspecialchars($tx['ComprobanteEnvioURL']) : ''; ?>"
                                        data-start-type="user" title="Ver Comprobante de Pago">
                                        Ver Comprobante
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">No subido</span>
                                <?php endif; ?>
                            </td>
                            <td class="d-flex flex-wrap gap-1">
                                <a href="<?php echo BASE_URL; ?>/generar-factura.php?id=<?php echo $tx['TransaccionID']; ?>"
                                    target="_blank" class="btn btn-sm btn-info" title="Ver Orden PDF">
                                    <i class="bi bi-file-earmark-pdf"></i> Orden
                                </a>

                                <?php if ($tx['EstadoNombre'] == 'En Verificación'): ?>
                                    <button class="btn btn-sm btn-success process-btn"
                                        data-tx-id="<?php echo $tx['TransaccionID']; ?>">Confirmar</button>
                                    <button class="btn btn-sm btn-danger reject-action-btn" data-bs-toggle="modal"
                                        data-bs-target="#rejectionModal" data-tx-id="<?php echo $tx['TransaccionID']; ?>">
                                        Rechazar
                                    </button>
                                <?php elseif ($tx['EstadoNombre'] == 'En Proceso'): ?>
                                    <button class="btn btn-sm btn-primary admin-upload-btn" data-bs-toggle="modal"
                                        data-bs-target="#adminUploadModal" data-tx-id="<?php echo $tx['TransaccionID']; ?>">
                                        Subir Envío
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPaginas > 1): ?>
        <nav aria-label="Navegación de páginas">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($paginaActual <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $paginaActual - 1; ?>">Anterior</a>
                </li>
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <li class="page-item <?php echo ($i == $paginaActual) ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($paginaActual >= $totalPaginas) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $paginaActual + 1; ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
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
                        <input class="form-control" type="file" id="adminReceiptFile" name="receiptFile" required
                            accept="image/png, image/jpeg, application/pdf">
                    </div>

                    <div class="mb-3">
                        <label for="adminComisionDestino" class="form-label">Comisión Pagada (en divisa de
                            destino)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="adminComisionDestino"
                            name="comisionDestino" placeholder="Ej: 1.50" value="0" required>
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

<div class="modal fade" id="rejectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Rechazar Transacción #<span id="reject-tx-id-label"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reject-form">
                    <input type="hidden" id="reject-tx-id" name="transactionId">
                    <div class="mb-3">
                        <label for="reject-reason" class="form-label">Motivo del rechazo (Visible para el
                            cliente)</label>
                        <textarea class="form-control" id="reject-reason" name="reason" rows="3" required
                            placeholder="Ej: El comprobante es ilegible. Por favor suba una foto más clara."></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-warning confirm-reject-btn" data-type="retry">
                            <i class="bi bi-arrow-counterclockwise"></i> Solicitar Corrección
                        </button>
                        <button type="button" class="btn btn-outline-danger confirm-reject-btn" data-type="cancel">
                            <i class="bi bi-x-circle"></i> Cancelar Definitivamente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>