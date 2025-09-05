<?php
require_once __DIR__ . '/../../src/core/init.php';

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
    header('Location: ' . BASE_URL); 
    exit();
}

$pageTitle = 'Registro de Actividad (Logs)';
require_once __DIR__ . '/../../src/templates/header.php';

$logs = $conexion->query("
    SELECT L.*, U.Email 
    FROM Logs L
    LEFT JOIN Usuarios U ON L.UserID = U.UserID
    ORDER BY L.Timestamp DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mt-4">
    <h1 class="mb-4">Registro de Actividad del Sistema</h1>
    <p><a href="<?php echo BASE_URL; ?>/admin/">Volver al panel de transacciones</a></p>
    
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="4" class="text-center">No hay registros de actividad.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date("d/m/Y H:i:s", strtotime($log['Timestamp'])); ?></td>
                            <td><?php echo htmlspecialchars($log['Email'] ?? 'Sistema'); ?></td>
                            <td><?php echo htmlspecialchars($log['Accion']); ?></td>
                            <td><?php echo htmlspecialchars($log['Detalles']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../src/templates/footer.php';
?> 