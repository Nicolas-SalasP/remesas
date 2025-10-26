<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_rol_name']) || $_SESSION['user_rol_name'] !== 'Admin') {
    die("Acceso denegado.");
}

$pageTitle = 'Transacciones Pendientes';
$pageScript = 'admin.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';

$transacciones = $conexion->query("
    SELECT
        T.*, -- Seleccionar todas las columnas de transacciones
        U.PrimerNombre, U.PrimerApellido,
        CONCAT(CB.TitularPrimerNombre, ' ', CB.TitularPrimerApellido) AS BeneficiarioNombreCompleto,
        ET.NombreEstado AS EstadoNombre -- Obtener el nombre del estado
    FROM transacciones T
    JOIN usuarios U ON T.UserID = U.UserID
    JOIN cuentas_beneficiarias CB ON T.CuentaBeneficiariaID = CB.CuentaID -- Corregido nombre tabla
    JOIN estados_transaccion ET ON T.EstadoID = ET.EstadoID -- JOIN con estados_transaccion
    WHERE ET.NombreEstado IN ('En Verificación', 'En Proceso') -- Filtrar por NombreEstado
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
                                <?php ?>
                                <?php if (!empty($tx['ComprobanteURL'])): ?>
                                    <a href="<?php echo BASE_URL . '/' . htmlspecialchars($tx['ComprobanteURL']); ?>" target="_blank" class="btn btn-sm btn-info">Ver Comprobante</a>
                                <?php else: ?>
                                    <span class="text-muted">No disponible</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php ?>
                                <?php if ($tx['EstadoNombre'] == 'En Verificación'): ?>
                                    <button class="btn btn-sm btn-success process-btn" data-tx-id="<?php echo $tx['TransaccionID']; ?>">Confirmar y Procesar</button>
                                    <button class="btn btn-sm btn-danger reject-btn" data-tx-id="<?php echo $tx['TransaccionID']; ?>">Rechazar Pago</button>
                                <?php elseif ($tx['EstadoNombre'] == 'En Proceso'): ?>
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

<?php // Modal (sin cambios, omitido para brevedad) ?>

<?php
require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>