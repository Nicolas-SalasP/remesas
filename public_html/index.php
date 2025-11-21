<?php
require_once __DIR__ . '/../remesas_private/src/core/init.php';

if (isset($_SESSION['user_rol_name']) && $_SESSION['user_rol_name'] === 'Admin') {
    header('Location: ' . BASE_URL . '/admin/');
    exit();
}
if (isset($_SESSION['user_rol_name']) && $_SESSION['user_rol_name'] === 'Operador') {
    header('Location: ' . BASE_URL . '/operador/pendientes.php');
    exit();
}


$pageTitle = 'Inicio';
$pageScript = 'home.js';

require_once __DIR__ . '/../remesas_private/src/templates/header.php';
?>

<div class="container">

    <section class="text-center py-5">
        <h1 class="display-4 fw-bold">Envía dinero a tus seres queridos</h1>
        <p class="lead text-muted col-lg-8 mx-auto">La forma más rápida, segura y confiable de realizar tus envíos, con las mejores tasas del mercado.</p>
        <a href="<?php echo BASE_URL; ?>/dashboard/" class="btn btn-primary btn-lg mt-3">Realizar una Transacción</a>
    </section>

    <section class="card shadow-sm mb-5">
        <div class="card-body p-4" id="rate-container"> 
            
            <div class="row mb-3 align-items-center">
                <div class="col-md-7">
                    <h3 class="card-title mb-0">Tasa de Referencia</h3>
                </div>
                <div class="col-md-5">
                    <label for="country-select-dropdown" class="form-label small text-muted mb-0">Ver tasa para:</label>
                    <select id="country-select-dropdown" class="form-select form-select-sm">
                        <option value="">Cargando países...</option>
                    </select>
                </div>
            </div>
            
            <div class="text-center p-3">
                <h1 id="rate-valor-actual" class="display-4 fw-bold text-primary">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </h1>
                <p id="rate-description" class="lead text-muted mb-0">Cargando tasa...</p>
                <small id="rate-ultima-actualizacion" class="text-muted"></small>
            </div>
            
            <div class="mt-3" style="max-height: 250px;">
                <canvas id="rate-history-chart"></canvas>
            </div>
            
        </div>
    </section>


    <section class="row text-center">
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="h4">Seguridad Garantizada</h3>
                    <p>Tus transacciones están protegidas con los más altos estándares de seguridad.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="h4">Las Mejores Tasas</h3>
                    <p>Ofrecemos tasas de cambio competitivas para maximizar el valor de tu dinero.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="h4">Rapidez y Confianza</h3>
                    <p>Las transferencias se completan de forma ágil para que el dinero llegue a tiempo.</p>
                </div>
            </div>
        </div>
    </section>

</div>

<?php
require_once __DIR__ . '/../remesas_private/src/templates/footer.php';
?>