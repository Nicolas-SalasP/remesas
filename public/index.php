<?php
    // Definimos el título de esta página
    $pageTitle = 'Inicio';

    // Incluimos la cabecera
    require_once '../src/templates/header.php';
?>

<div class="container">

    <section class="hero">
        <h1>Envía dinero a tus seres queridos</h1>
        <p>La forma más rápida, segura y confiable de realizar tus envíos.</p>
        <a href="/remesas/public/dashboard/index.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 2rem;">Realizar una Transacción</a>
    </section>

    <section id="chart-container">
        <h3>Valor del Dólar BCV en tiempo real</h3>
        <canvas id="dolar-chart"></canvas>
    </section>

    <section class="features">
        <div class="feature-item">
            <h3>Seguridad Garantizada</h3>
            <p>Tus transacciones están protegidas con los más altos estándares de seguridad.</p>
        </div>
        <div class="feature-item">
            <h3>Las Mejores Tasas</h3>
            <p>Ofrecemos tasas de cambio competitivas para maximizar el valor de tu dinero.</p>
        </div>
        <div class="feature-item">
            <h3>Rapidez y Confianza</h3>
            <p>Las transferencias se completan de forma ágil para que el dinero llegue a tiempo.</p>
        </div>
    </section>

</div>

<?php
    // Incluimos el pie de página
    require_once '../src/templates/footer.php';
?>