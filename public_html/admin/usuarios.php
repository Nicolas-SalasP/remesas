<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_rol_name']) || $_SESSION['user_rol_name'] !== 'Admin') {
    die("Acceso denegado.");
}

$pageTitle = 'Gestionar Usuarios';
$pageScript = 'admin.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';

$adminID = (int)$_SESSION['user_id'];
$usuarios = [];

$sql = "
    SELECT 
        u.UserID, u.PrimerNombre, u.PrimerApellido, u.Email, u.LockoutUntil, u.NumeroDocumento,
        ev.NombreEstado AS VerificacionEstado,
        r.NombreRol AS Rol,
        td.NombreDocumento AS TipoDocumento
    FROM usuarios u
    LEFT JOIN estados_verificacion ev ON u.VerificacionEstadoID = ev.EstadoID
    LEFT JOIN roles r ON u.RolID = r.RolID
    LEFT JOIN tipos_documento td ON u.TipoDocumentoID = td.TipoDocumentoID
    WHERE u.UserID != ? 
    ORDER BY u.FechaRegistro DESC
";
$stmt = $conexion->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $adminID);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuarios = ($resultado) ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    error_log("Error al preparar la consulta de usuarios: " . $conexion->error);
}
?>

<div class="container mt-4">
    <h1 class="mb-4">Gestionar Usuarios</h1>
    <p><a href="<?php echo BASE_URL; ?>/admin/">Volver al panel principal</a></p>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Documento</th>
                    <th>Estado Verificación</th>
                    <th>Acción (Bloqueo)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="7" class="text-center">No se encontraron usuarios (aparte de usted).</td></tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usuario):
                        $isBlocked = ($usuario['LockoutUntil'] && strtotime($usuario['LockoutUntil']) > time());
                    ?>
                        <tr>
                            <td><?php echo $usuario['UserID']; ?></td>
                            <td><?php echo htmlspecialchars($usuario['PrimerNombre'] . ' ' . $usuario['PrimerApellido']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['Email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['Rol'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(($usuario['TipoDocumento'] ?? 'N/A') . ': ' . ($usuario['NumeroDocumento'] ?? 'N/A')); ?></td>
                            <td><?php echo htmlspecialchars($usuario['VerificacionEstado'] ?? 'N/A'); ?></td>
                            <td>
                                <button class="btn btn-sm block-user-btn <?php echo $isBlocked ? 'btn-success' : 'btn-danger'; ?>" 
                                        data-user-id="<?php echo $usuario['UserID']; ?>"
                                        data-current-status="<?php echo $isBlocked ? 'blocked' : 'active'; ?>">
                                    <?php echo $isBlocked ? 'Desbloquear' : 'Bloquear'; ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>