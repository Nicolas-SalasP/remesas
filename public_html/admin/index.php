<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';
if (!isset($_SESSION['user_rol_name']) || $_SESSION['user_rol_name'] !== 'Admin') { die("Acceso denegado."); }

function getStatusBadgeClass($statusName) {
    switch ($statusName) {
        case 'Pagado': return 'bg-success';
        case 'En Proceso': return 'bg-primary';
        case 'En Verificación': return 'bg-info text-dark';
        case 'Cancelado': return 'bg-danger';
        case 'Pendiente de Pago': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}

$pageTitle = 'Panel de Administración';
$pageScript = 'admin.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';

$transacciones = $conexion->query("
    SELECT T.*, U.PrimerNombre, U.PrimerApellido,
           CONCAT(CB.TitularPrimerNombre, ' ', CB.TitularPrimerApellido) AS BeneficiarioNombreCompleto,
           ET.NombreEstado AS EstadoNombre
    FROM transacciones T
    JOIN usuarios U ON T.UserID = U.UserID
    JOIN cuentas_beneficiarias CB ON T.CuentaBeneficiariaID = CB.CuentaID
    LEFT JOIN estados_transaccion ET ON T.EstadoID = ET.EstadoID
    ORDER BY T.FechaTransaccion DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Panel de Administración</h1>
        <div>
            <a href="<?php echo BASE_URL; ?>/admin/pendientes.php" class="btn btn-primary">Ver Transacciones Pendientes</a>
            <a href="<?php echo BASE_URL; ?>/admin/logs.php" class="btn btn-secondary">Ver Logs</a>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Beneficiario</th>
                    <th>Estado</th>
                    <th>Orden</th> <?php // ?>
                    <th>Comp. Usuario</th>
                    <th>Comp. Admin</th>
                </tr>
            </thead>
            <tbody id="transactionsTableBody">
                <?php foreach ($transacciones as $tx): ?>
                    <tr>
                        <td><?php echo $tx['TransaccionID']; ?></td>
                        <td><?php echo date("d/m/y H:i", strtotime($tx['FechaTransaccion'])); ?></td>
                        <td class="search-user"><?php echo htmlspecialchars($tx['PrimerNombre'] . ' ' . $tx['PrimerApellido']); ?></td>
                        <td class="search-beneficiary"><?php echo htmlspecialchars($tx['BeneficiarioNombreCompleto']); ?></td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($tx['EstadoNombre'] ?? ''); ?>">
                                <?php echo htmlspecialchars($tx['EstadoNombre'] ?? 'Desconocido'); ?>
                            </span>
                        </td>
                        <?php ?>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/generar-factura.php?id=<?php echo $tx['TransaccionID']; ?>" 
                               target="_blank" class="btn btn-sm btn-info" title="Ver Orden PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        </td>
                        <?php ?>
                        <td>
                            <?php if (!empty($tx['ComprobanteURL'])): ?>
                                <button class="btn btn-sm btn-info view-comprobante-btn-admin"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewComprobanteModal"
                                        data-tx-id="<?php echo $tx['TransaccionID']; ?>"
                                        data-comprobante-url="<?php echo BASE_URL . htmlspecialchars($tx['ComprobanteURL']); ?>"
                                        data-envio-url="<?php echo !empty($tx['ComprobanteEnvioURL']) ? BASE_URL . htmlspecialchars($tx['ComprobanteEnvioURL']) : ''; ?>"
                                        data-start-type="user"
                                        title="Ver Comprobante de Pago">
                                    <i class="bi bi-eye"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($tx['ComprobanteEnvioURL'])): ?>
                                <button class="btn btn-sm btn-success view-comprobante-btn-admin"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewComprobanteModal"
                                        data-tx-id="<?php echo $tx['TransaccionID']; ?>"
                                        data-comprobante-url="<?php echo !empty($tx['ComprobanteURL']) ? BASE_URL . htmlspecialchars($tx['ComprobanteURL']) : ''; ?>"
                                        data-envio-url="<?php echo BASE_URL . htmlspecialchars($tx['ComprobanteEnvioURL']); ?>"
                                        data-start-type="admin"
                                        title="Ver Comprobante de Envío">
                                    <i class="bi bi-receipt"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>