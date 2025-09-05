<?php
require_once __DIR__ . '/../../src/core/init.php';

// --- ¡SEGURIDAD! ---
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
    die("Acceso denegado. No tienes permisos para ver esta página.");
}

$pageTitle = 'Panel de Administración';
$pageScript = 'admin.js'; 
require_once __DIR__ . '/../../src/templates/header.php';

// Lógica para buscar TODAS las transacciones
$transacciones = $conexion->query("
    SELECT T.*, U.PrimerNombre, U.PrimerApellido, CONCAT(CB.TitularPrimerNombre, ' ', CB.TitularPrimerApellido) AS BeneficiarioNombreCompleto 
    FROM Transacciones T
    JOIN Usuarios U ON T.UserID = U.UserID
    JOIN CuentasBeneficiarias CB ON T.CuentaBeneficiariaID = CB.CuentaID
    ORDER BY T.FechaTransaccion DESC
")->fetch_all(MYSQLI_ASSOC);

$estadosPosibles = ['Pendiente de Pago', 'En Verificación', 'En Proceso', 'Pagado', 'Cancelado'];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Panel de Administración</h1>
        <div>
            <a href="<?php echo BASE_URL; ?>/admin/pendientes.php" class="btn btn-primary">Ver Pendientes</a>
            <a href="<?php echo BASE_URL; ?>/admin/logs.php" class="btn btn-secondary">Ver Logs</a>
        </div>
    </div>
    
    <div class="card card-body mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="searchInput" class="form-label">Buscar por Usuario o Beneficiario</label>
                <input type="search" class="form-control" id="searchInput" placeholder="Escribe un nombre...">
            </div>
            <div class="col-md-4">
                <label for="statusFilter" class="form-label">Filtrar por Estado</label>
                <select id="statusFilter" class="form-select">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estadosPosibles as $estado): ?>
                        <option value="<?php echo $estado; ?>"><?php echo $estado; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-secondary w-100" id="resetFilters">Limpiar</button>
            </div>
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
                        <td data-fecha="<?php echo date("Y-m-d", strtotime($tx['FechaTransaccion'])); ?>">
                            <?php echo date("d/m/y H:i", strtotime($tx['FechaTransaccion'])); ?>
                        </td>
                        <td class="search-user"><?php echo htmlspecialchars($tx['PrimerNombre'] . ' ' . $tx['PrimerApellido']); ?></td>
                        <td class="search-beneficiary"><?php echo htmlspecialchars($tx['BeneficiarioNombreCompleto']); ?></td>
                        <td class="filter-status">
                            <select class="form-select form-select-sm status-select" data-tx-id="<?php echo $tx['TransaccionID']; ?>">
                                <?php foreach ($estadosPosibles as $estado): ?>
                                    <option value="<?php echo $estado; ?>" <?php echo ($tx['Estado'] == $estado) ? 'selected' : ''; ?>>
                                        <?php echo $estado; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <?php if (!empty($tx['ComprobanteURL'])): ?>
                                <a href="<?php echo BASE_URL . '/' . $tx['ComprobanteURL']; ?>" target="_blank" class="btn btn-sm btn-info">Ver</a>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($tx['ComprobanteEnvioURL'])): ?>
                                <a href="<?php echo BASE_URL . '/' . $tx['ComprobanteEnvioURL']; ?>" target="_blank" class="btn btn-sm btn-success">Ver</a>
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
require_once __DIR__ . '/../../src/templates/footer.php';
?>