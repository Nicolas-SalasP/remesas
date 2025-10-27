<?php
require_once __DIR__ . '/../remesas_private/src/core/init.php';


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
        <div class="card-body p-4" id="bcv-container"> <?php ?>
            <h3 class="card-title text-center mb-3">Valor del Dólar BCV</h3>
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-muted mt-2">Cargando tasa oficial...</p>
            </div>
            <?php ?>
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