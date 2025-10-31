<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

if (!isset($_SESSION['user_rol_name']) || $_SESSION['user_rol_name'] !== 'Admin') {
    die("Acceso denegado.");
}

$pageTitle = 'Gestionar Tasas de Cambio';
$pageScript = 'tasas.js'; 
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';

$paisesActivos = $conexion->query("
    SELECT PaisID, NombrePais, CodigoMoneda, Rol FROM paises 
    WHERE Activo = TRUE 
    ORDER BY NombrePais ASC
")->fetch_all(MYSQLI_ASSOC);

$tasasExistentes = $conexion->query("
    SELECT 
        T.TasaID, T.ValorTasa,
        PO.PaisID as OrigenID, PO.NombrePais AS PaisOrigen,
        PD.PaisID as DestinoID, PD.NombrePais AS PaisDestino
    FROM tasas T
    JOIN paises PO ON T.PaisOrigenID = PO.PaisID
    JOIN paises PD ON T.PaisDestinoID = PD.PaisID
    WHERE PO.Activo = TRUE AND PD.Activo = TRUE
    ORDER BY PO.NombrePais, PD.NombrePais
")->fetch_all(MYSQLI_ASSOC);

$paisesOrigen = [];
$paisesDestino = [];
foreach ($paisesActivos as $pais) {
    if ($pais['Rol'] === 'Origen' || $pais['Rol'] === 'Ambos') {
        $paisesOrigen[] = $pais;
    }
    if ($pais['Rol'] === 'Destino' || $pais['Rol'] === 'Ambos') {
        $paisesDestino[] = $pais;
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gestionar Tasas de Cambio</h1>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Editor de Tasas</h5>
        </div>
        <div class="card-body">
            <form id="rate-editor-form">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="pais-origen" class="form-label">De (Origen):</label>
                        <select id="pais-origen" class="form-select">
                            <option value="">Seleccionar país...</option>
                            <?php foreach ($paisesOrigen as $pais): ?>
                                <option value="<?php echo $pais['PaisID']; ?>"><?php echo htmlspecialchars($pais['NombrePais']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="pais-destino" class="form-label">A (Destino):</label>
                        <select id="pais-destino" class="form-select">
                            <option value="">Seleccionar país...</option>
                            <?php foreach ($paisesDestino as $pais): ?>
                                <option value="<?php echo $pais['PaisID']; ?>"><?php echo htmlspecialchars($pais['NombrePais']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="rate-value" class="form-label">Valor de la Tasa:</label>
                        <input type="number" step="0.000001" min="0" class="form-control" id="rate-value" placeholder="0.000000" disabled>
                    </div>

                    <div class="col-md-1 d-grid">
                        <button type="submit" id="save-rate-btn" class="btn btn-primary" title="Guardar Tasa" disabled>
                            <i class="bi bi-save"></i>
                        </button>
                    </div>
                </div>
            </form>
            <div id="feedback-message" class="mt-3"></div>
            <input type="hidden" id="current-tasa-id" value="new">
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Tasas Configuradas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="existing-rates-table">
                    <thead class="table-light">
                        <tr>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Valor Tasa</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasasExistentes)): ?>
                            <tr><td colspan="4" class="text-center">No hay tasas configuradas. Usa el editor para crear la primera.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tasasExistentes as $tasa): ?>
                                <tr id="tasa-row-<?php echo $tasa['TasaID']; ?>" data-origen-id="<?php echo $tasa['OrigenID']; ?>" data-destino-id="<?php echo $tasa['DestinoID']; ?>">
                                    <td><?php echo htmlspecialchars($tasa['PaisOrigen']); ?></td>
                                    <td><?php echo htmlspecialchars($tasa['PaisDestino']); ?></td>
                                    <td class="rate-value-cell"><?php echo htmlspecialchars($tasa['ValorTasa']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-rate-btn"
                                                data-origen-id="<?php echo $tasa['OrigenID']; ?>"
                                                data-destino-id="<?php echo $tasa['DestinoID']; ?>"
                                                title="Editar esta tasa">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
echo "<script id='rates-data' type='application/json'>";
echo json_encode(array_map(fn($t) => [
    'TasaID' => $t['TasaID'],
    'ValorTasa' => $t['ValorTasa']
], array_column($tasasExistentes, null, 'OrigenID_DestinoID_Key') ?: $ratesMap ?? []));
echo "</script>";

echo "<script>";
echo "const ratesMap = {};";
foreach ($tasasExistentes as $tasa) {
    echo "if(!ratesMap[{$tasa['OrigenID']}]) ratesMap[{$tasa['OrigenID']}] = {};";
    echo "ratesMap[{$tasa['OrigenID']}][{$tasa['DestinoID']}] = { tasaId: '{$tasa['TasaID']}', valor: '{$tasa['ValorTasa']}' };";
}
echo "</script>";

require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>