<?php
    // Proteger la página
    require_once '../../src/core/session.php';
    require_once '../../src/database/db_connection.php';
    
    $pageTitle = 'Mi Historial';
    require_once '../../src/templates/header.php';

    // Obtener las transacciones del usuario logueado
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

<div class="container page-content">
    <h1>Mi Historial de Transacciones</h1>
    
    <?php if (empty($transacciones)): ?>
        <p>Aún no has realizado ninguna transacción. <a href="/remesas/public/dashboard/">Haz tu primer envío aquí.</a></p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Destino</th>
                        <th>Beneficiario</th>
                        <th>Monto Enviado</th>
                        <th>Monto a Recibir</th>
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
                            <td><?php echo htmlspecialchars($tx['MontoOrigen']) . ' ' . htmlspecialchars($tx['MonedaOrigen']); ?></td>
                            <td><?php echo htmlspecialchars($tx['MontoDestino']) . ' ' . htmlspecialchars($tx['MonedaDestino']); ?></td>
                            <td><span class="status <?php echo strtolower(str_replace(' ', '-', $tx['Estado'])); ?>"><?php echo htmlspecialchars($tx['Estado']); ?></span></td>
                            <td>
                                <?php if ($tx['Estado'] == 'Pendiente de Pago'): ?>
                                    <a href="#" class="action-link">Subir Comprobante</a>
                                <?php endif; ?>
                                <a href="#" class="action-link">Ver PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
    require_once '../../src/templates/footer.php';
?>