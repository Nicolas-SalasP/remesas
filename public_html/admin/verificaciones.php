<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
    die("Acceso denegado.");
}

$pageTitle = 'Verificaciones Pendientes';
$pageScript = 'admin.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';

$usuariosPendientes = $conexion->query("
    SELECT UserID, PrimerNombre, PrimerApellido, Email, NumeroDocumento, DocumentoImagenURL_Frente, DocumentoImagenURL_Reverso
    FROM usuarios
    WHERE VerificacionEstado = 'Pendiente'
    ORDER BY FechaRegistro ASC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mt-4">
    <h1 class="mb-4">Verificaciones Pendientes</h1>
    <p><a href="<?php echo BASE_URL; ?>/admin/">Volver al panel principal</a></p>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID Usuario</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Documento</th>
                    <th>Imágenes</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuariosPendientes)): ?>
                    <tr><td colspan="6" class="text-center">No hay usuarios pendientes de verificación.</td></tr>
                <?php else: ?>
                    <?php foreach ($usuariosPendientes as $usuario): ?>
                        <tr>
                            <td><?php echo $usuario['UserID']; ?></td>
                            <td><?php echo htmlspecialchars($usuario['PrimerNombre'] . ' ' . $usuario['PrimerApellido']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['Email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['NumeroDocumento']); ?></td>
                            <td>
                                <a href="<?php echo BASE_URL . '/' . $usuario['DocumentoImagenURL_Frente']; ?>" target="_blank" class="btn btn-sm btn-info">Ver Frente</a>
                                <a href="<?php echo BASE_URL . '/' . $usuario['DocumentoImagenURL_Reverso']; ?>" target="_blank" class="btn btn-sm btn-info">Ver Reverso</a>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-success verification-action-btn" data-user-id="<?php echo $usuario['UserID']; ?>" data-action="Verificado">Aprobar</button>
                                <button class="btn btn-sm btn-danger verification-action-btn" data-user-id="<?php echo $usuario['UserID']; ?>" data-action="Rechazado">Rechazar</button>
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