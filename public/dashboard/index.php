<?php
    // 1. Proteger la página
    require_once '../../src/core/session.php';
    
    // 2. Definir título y cargar cabecera
    $pageTitle = 'Realizar Transacción';
    require_once '../../src/templates/header.php';
?>

<div class="form-container">
    <h2>Nueva Transacción</h2>
    <form id="remittance-form">
        <input type="hidden" id="user-id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
        
        <input type="hidden" id="selected-tasa-id" value="">
        <input type="hidden" id="selected-cuenta-id" value="">
        
        <div class="form-step active" id="step-1"> ... </div>
        <div class="form-step" id="step-2"> ... </div>
        <div class="form-step" id="step-3"> ... </div>
        <div class="form-step" id="step-4"> ... </div>
        <div class="form-step" id="step-5"> ... </div>
        
        <div class="navigation-buttons">
            <button type="button" id="prev-btn" class="btn-prev hidden">Anterior</button>
            <button type="button" id="next-btn" class="btn-next">Siguiente</button>
        </div>
    </form>
</div>

<?php
    // 3. Cargar el pie de página
    require_once '../../src/templates/footer.php';
?>