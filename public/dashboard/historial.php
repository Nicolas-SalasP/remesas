<?php
require_once __DIR__ . '/../../src/core/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Pagado': return 'bg-success';
        case 'En Proceso': return 'bg-primary';
        case 'En Verificación': return 'bg-info text-dark';
        case 'Cancelado': return 'bg-danger';
        default: return 'bg-warning text-dark';
    }
}
$pageTitle = 'Mi Historial';
$pageScript = 'historial.js'; 
require_once __DIR__ . '/../../src/templates/header.php';

$userID = $_SESSION['user_id'];
$transacciones = [];
$sql = "SELECT 
            T.*, C.Alias AS BeneficiarioAlias, P.NombrePais AS PaisDestino
        FROM Transacciones AS T
        JOIN CuentasBeneficiarias AS C ON T.CuentaBeneficiariaID = C.CuentaID
        JOIN Paises AS P ON C.PaisID = P.PaisID
        WHERE T.UserID = ?
        ORDER BY T.FechaTransaccion DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado) {
    $transacciones = $resultado->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();
?>

<div class="container mt-4">
    <div class="card p-4 p-md-5 shadow-sm">
        <h1 class="mb-4">Mi Historial de Transacciones</h1>
        
        <?php if (empty($transacciones)): ?>
            <div class="alert alert-info">Aún no has realizado ninguna transacción. <a href="<?php echo BASE_URL; ?>/dashboard/">Haz tu primer envío aquí.</a></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Beneficiario</th>
                            <th>Monto Enviado</th>
                            <th>Monto Recibido</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transacciones as $tx): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tx['TransaccionID']); ?></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($tx['FechaTransaccion'])); ?></td>
                                <td><?php echo htmlspecialchars($tx['BeneficiarioAlias']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($tx['MontoOrigen'], 2)) . ' ' . htmlspecialchars($tx['MonedaOrigen']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($tx['MontoDestino'], 2)) . ' ' . htmlspecialchars($tx['MonedaDestino']); ?></td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($tx['Estado']); ?>">
                                        <?php echo htmlspecialchars($tx['Estado']); ?>
                                    </span>
                                </td>
                                <td class="d-flex flex-wrap gap-2">
                                    <?php if ($tx['Estado'] == 'Pendiente de Pago'): ?>
                                        <button class="btn btn-sm btn-danger cancel-btn" data-tx-id="<?php echo $tx['TransaccionID']; ?>">
                                            Cancelar
                                        </button>
                                    <?php endif; ?>

                                    <?php if (empty($tx['ComprobanteURL'])): ?>
                                        <?php if ($tx['Estado'] == 'Pendiente de Pago'): ?>
                                            <button class="btn btn-sm btn-warning upload-btn" data-bs-toggle="modal" data-bs-target="#uploadReceiptModal" data-tx-id="<?php echo $tx['TransaccionID']; ?>">
                                                Subir
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL . '/' . htmlspecialchars($tx['ComprobanteURL']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Ver Pago</a>
                                        <button class="btn btn-sm btn-secondary upload-btn" data-bs-toggle="modal" data-bs-target="#uploadReceiptModal" data-tx-id="<?php echo $tx['TransaccionID']; ?>">
                                            Modificar
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo BASE_URL; ?>/generar-factura.php?id=<?php echo $tx['TransaccionID']; ?>" target="_blank" class="btn btn-sm btn-info">
                                        Orden
                                    </a>

                                    <?php if (!empty($tx['ComprobanteEnvioURL'])): ?>
                                        <a href="<?php echo BASE_URL . '/' . htmlspecialchars($tx['ComprobanteEnvioURL']); ?>" target="_blank" class="btn btn-sm btn-success fw-bold">
                                            Ver Comprobante de Envío
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="uploadReceiptModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    </div>

<?php
require_once __DIR__ . '/../../src/templates/footer.php';
?>