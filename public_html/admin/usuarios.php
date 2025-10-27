<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_rol_name']) || $_SESSION['user_rol_name'] !== 'Admin') {
    die("Acceso denegado.");
}

$pageTitle = 'Gestionar Usuarios';
$pageScript = 'admin.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';

$adminID = $_SESSION['user_id'];

// Obtener todos los roles disponibles para el dropdown
$rolesDisponibles = $conexion->query("SELECT RolID, NombreRol FROM roles ORDER BY NombreRol")->fetch_all(MYSQLI_ASSOC);

// ***** INICIO DE MODIFICACIÓN: FILTRAR USUARIOS *****
$mostrarInactivos = isset($_GET['mostrar_inactivos']) && $_GET['mostrar_inactivos'] == '1';

$sqlWhere = "WHERE U.UserID != $adminID";
if (!$mostrarInactivos) {
    // Por defecto, mostrar solo usuarios activos (no bloqueados permanentemente)
    $sqlWhere .= " AND (U.LockoutUntil IS NULL OR U.LockoutUntil <= NOW())";
}
// ***** FIN DE MODIFICACIÓN *****

// Obtener todos los usuarios (excepto el admin actual) con su info de rol y verificación
$usuarios = $conexion->query("
    SELECT
        U.UserID, U.PrimerNombre, U.PrimerApellido, U.Email, U.LockoutUntil, U.RolID,
        EV.NombreEstado AS VerificacionEstadoNombre,
        R.NombreRol
    FROM usuarios U
    LEFT JOIN estados_verificacion EV ON U.VerificacionEstadoID = EV.EstadoID
    LEFT JOIN roles R ON U.RolID = R.RolID
    $sqlWhere
    ORDER BY U.FechaRegistro DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gestionar Usuarios</h1>
        <a href="<?php echo BASE_URL; ?>/admin/">Volver al panel principal</a>
    </div>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" value="1" id="mostrar_inactivos" 
               <?php echo $mostrarInactivos ? 'checked' : ''; ?> 
               onchange="window.location.href = this.checked ? '?mostrar_inactivos=1' : '?mostrar_inactivos=0'">
        <label class="form-check-label" for="mostrar_inactivos">
            Mostrar usuarios desactivados (bloqueados)
        </label>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Estado Verificación</th>
                    <th>Rol</th>
                    <th style="min-width: 180px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="6" class="text-center">No se encontraron usuarios<?php echo $mostrarInactivos ? ' inactivos' : ' activos'; ?>.</td></tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usuario):
                        $isBlocked = ($usuario['LockoutUntil'] && strtotime($usuario['LockoutUntil']) > time());
                        $isPrincipalAdmin = ($usuario['UserID'] == 1); 
                    ?>
                        <tr id="user-row-<?php echo $usuario['UserID']; ?>">
                            <td><?php echo $usuario['UserID']; ?></td>
                            <td><?php echo htmlspecialchars($usuario['PrimerNombre'] . ' ' . $usuario['PrimerApellido']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['Email']); ?></td>
                            <td>
                                <span class="badge <?php echo $usuario['VerificacionEstadoNombre'] == 'Verificado' ? 'bg-success' : ($usuario['VerificacionEstadoNombre'] == 'Pendiente' ? 'bg-warning text-dark' : ($usuario['VerificacionEstadoNombre'] == 'Rechazado' ? 'bg-danger' : 'bg-secondary')); ?>">
                                    <?php echo htmlspecialchars($usuario['VerificacionEstadoNombre'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            
                            <td>
                                <select class="form-select form-select-sm admin-role-select" 
                                        data-user-id="<?php echo $usuario['UserID']; ?>" 
                                        data-original-role-id="<?php echo $usuario['RolID']; ?>"
                                        aria-label="Seleccionar rol de usuario"
                                        <?php echo $isPrincipalAdmin ? 'disabled' : ''; ?>>
                                    
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($rolesDisponibles as $rol): ?>
                                        <option value="<?php echo $rol['RolID']; ?>" <?php echo ($usuario['RolID'] == $rol['RolID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($rol['NombreRol']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            
                            <td>
                                <button class="btn btn-sm block-user-btn <?php echo $isBlocked ? 'btn-success' : 'btn-warning'; ?>" 
                                        data-user-id="<?php echo $usuario['UserID']; ?>"
                                        data-current-status="<?php echo $isBlocked ? 'blocked' : 'active'; ?>"
                                        title="<?php echo $isBlocked ? 'Desbloquear' : 'Bloquear'; ?> usuario"
                                        <?php echo $isPrincipalAdmin ? 'disabled' : ''; ?>>
                                    <i class="bi <?php echo $isBlocked ? 'bi-unlock-fill' : 'bi-lock-fill'; ?>"></i>
                                </button>
                                
                                <?php ?>
                                <?php if (!$isBlocked): ?>
                                    <button class="btn btn-sm btn-danger admin-delete-user-btn ms-1"
                                            data-user-id="<?php echo $usuario['UserID']; ?>"
                                            data-user-name="<?php echo htmlspecialchars($usuario['PrimerNombre'] . ' ' . $usuario['PrimerApellido']); ?>"
                                            title="Desactivar usuario (eliminación lógica)"
                                            <?php echo $isPrincipalAdmin ? 'disabled' : ''; ?>>
                                        <i class="bi bi-trash-fill"></i>
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

<?php
require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>