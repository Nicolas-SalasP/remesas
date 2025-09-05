<?php
require_once __DIR__ . '/../../src/core/init.php';

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
    die("Acceso denegado.");
}

$pageTitle = 'Gestionar Tasas de Cambio';
$pageScript = 'tasas.js'; 
require_once __DIR__ . '/../../src/templates/header.php';

$tasas = $conexion->query("
    SELECT 
        T.TasaID,
        PO.NombrePais AS PaisOrigen,
        PD.NombrePais AS PaisDestino,
        T.ValorTasa
    FROM Tasas T
    JOIN Paises PO ON T.PaisOrigenID = PO.PaisID
    JOIN Paises PD ON T.PaisDestinoID = PD.PaisID
    ORDER BY T.TasaID
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mt-4">
    <h1 class="mb-4">Gestionar Tasas de Cambio</h1>
    <p><a href="<?php echo BASE_URL; ?>/admin/">Volver al panel de transacciones</a></p>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Ruta</th>
                    <th>Valor Tasa Actual</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasas as $tasa): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tasa['PaisOrigen'] . ' -> ' . $tasa['PaisDestino']); ?></td>
                        <td>
                            <input type="number" step="0.000001" class="form-control rate-input" 
                                   value="<?php echo htmlspecialchars($tasa['ValorTasa']); ?>" 
                                   data-tasa-id="<?php echo $tasa['TasaID']; ?>">
                        </td>
                        <td>
                            <button class="btn btn-success btn-sm save-rate-btn" data-tasa-id="<?php echo $tasa['TasaID']; ?>">
                                Guardar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="feedback-message" class="mt-3"></div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../src/templates/footer.php';
?>