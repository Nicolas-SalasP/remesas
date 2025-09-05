<?php
require_once __DIR__ . '/../../src/core/init.php';
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') { die("Acceso denegado."); }

// Añadimos la función para los colores de los estados aquí también
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Pagado': return 'bg-success';
        case 'En Proceso': return 'bg-primary';
        case 'En Verificación': return 'bg-info text-dark';
        case 'Cancelado': return 'bg-danger';
        default: return 'bg-warning text-dark';
    }
}

$pageTitle = 'Panel de Administración';
$pageScript = 'admin.js'; 
require_once __DIR__ . '/../../src/templates/header.php';

$transacciones = $conexion->query("
    SELECT T.*, U.PrimerNombre, U.PrimerApellido, CONCAT(CB.TitularPrimerNombre, ' ', CB.TitularPrimerApellido) AS BeneficiarioNombreCompleto 
    FROM Transacciones T
    JOIN Usuarios U ON T.UserID = U.UserID
    JOIN CuentasBeneficiarias CB ON T.CuentaBeneficiariaID = CB.CuentaID
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
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Beneficiario</th>
                    <th>Estado</th>
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
                            <span class="badge <?php echo getStatusBadgeClass($tx['Estado']); ?>">
                                <?php echo htmlspecialchars($tx['Estado']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($tx['ComprobanteURL'])): ?>
                                <a href="<?php echo BASE_URL . '/' . $tx['ComprobanteURL']; ?>" target="_blank" class="btn btn-sm btn-info">Ver</a>
                            <?php else: ?><span class="text-muted">N/A</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($tx['ComprobanteEnvioURL'])): ?>
                                <a href="<?php echo BASE_URL . '/' . $tx['ComprobanteEnvioURL']; ?>" target="_blank" class="btn btn-sm btn-success">Ver</a>
                            <?php else: ?><span class="text-muted">N/A</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../src/templates/footer.php';
?>