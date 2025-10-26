<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_rol_name']) || $_SESSION['user_rol_name'] !== 'Admin') {
    die("Acceso denegado.");
}

$pageTitle = 'Gestionar Usuarios';
$pageScript = 'admin.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';

$adminID = $_SESSION['user_id'];
$usuarios = $conexion->query("SELECT UserID, PrimerNombre, PrimerApellido, Email, VerificacionEstado, LockoutUntil FROM usuarios WHERE UserID != $adminID ORDER BY FechaRegistro DESC")->fetch_all(MYSQLI_ASSOC);
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
                    <th>Estado Verificación</th>
                    <th>Acción (Bloqueo)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario):
                    $isBlocked = ($usuario['LockoutUntil'] && strtotime($usuario['LockoutUntil']) > time() + (365*24*60*60));
                ?>
                    <tr>
                        <td><?php echo $usuario['UserID']; ?></td>
                        <td><?php echo htmlspecialchars($usuario['PrimerNombre'] . ' ' . $usuario['PrimerApellido']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['Email']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['VerificacionEstado']); ?></td>
                        <td>
                            <button class="btn btn-sm block-user-btn <?php echo $isBlocked ? 'btn-success' : 'btn-danger'; ?>" 
                                    data-user-id="<?php echo $usuario['UserID']; ?>"
                                    data-current-status="<?php echo $isBlocked ? 'blocked' : 'active'; ?>">
                                <?php echo $isBlocked ? 'Desbloquear' : 'Bloquear'; ?>
                            </button>
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