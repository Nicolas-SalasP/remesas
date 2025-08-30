<?php
    // 1. Proteger la página: si no hay sesión, redirige al login.
    require_once '../../src/core/session.php';
    
    // 2. Incluir la configuración y la conexión a la BD
    require_once '../../src/database/connection.php';
    
    // 3. Definir título y cargar la cabecera del HTML
    $pageTitle = 'Mi Historial';
    require_once '../../src/templates/header.php';

    // 4. LÓGICA PARA BUSCAR LAS TRANSACCIONES DEL USUARIO
    $userID = $_SESSION['user_id'];
    $transacciones = [];
    
    $sql = "SELECT 
                T.TransaccionID, T.FechaTransaccion, T.MontoOrigen, T.MonedaOrigen, 
                T.MontoDestino, T.MonedaDestino, T.Estado,
                C.Alias AS BeneficiarioAlias,
                P.NombrePais AS PaisDestino
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
    $conexion->close();
?>

<div class="container mt-4">
    <div class="card p-4 p-md-5 shadow-sm">
        <h1 class="mb-4">Mi Historial de Transacciones</h1>
        
        <?php if (empty($transacciones)): ?>
            <div class="alert alert-info">Aún no has realizado ninguna transacción. <a href="/remesas/public/dashboard/">Haz tu primer envío aquí.</a></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Destino</th>
                            <th>Beneficiario</th>
                            <th>Monto Enviado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transacciones as $tx): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tx['TransaccionID']); ?></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($tx['FechaTransaccion'])); ?></td>
                                <td><?php echo htmlspecialchars($tx['PaisDestino']); ?></td>
                                <td><?php echo htmlspecialchars($tx['BeneficiarioAlias']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($tx['MontoOrigen'], 2)) . ' ' . htmlspecialchars($tx['MonedaOrigen']); ?></td>
                                <td><span class="badge <?php echo 'status-' . strtolower(str_replace(' ', '-', $tx['Estado'])); ?>"><?php echo htmlspecialchars($tx['Estado']); ?></span></td>
                                <td>
                                    <?php if ($tx['Estado'] == 'Pendiente de Pago'): ?>
                                        <a href="#" class="btn btn-sm btn-warning">Subir Comprobante</a>
                                    <?php endif; ?>
                                    <a href="#" class="btn btn-sm btn-info">Ver PDF</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
    // 6. Cargar el pie de página
    require_once '../../src/templates/footer.php';
?>