<?php
require_once __DIR__ . '/../remesas_private/src/core/init.php';
$pageTitle = 'Quiénes Somos';
require_once __DIR__ . '/../remesas_private/src/templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card p-4 p-md-5 shadow-sm">
                <div class="text-center mb-4">
                    <h1>Sobre Nuestra Empresa</h1>
                    <p class="lead text-muted">Conectando familias, acortando distancias.</p>
                </div>

                <div class="content-section mb-4">
                    <h2 class="h3">Nuestra Misión</h2>
                    <p>Nuestra misión es ofrecer un servicio de envío de dinero transparente, seguro y eficiente, brindando las mejores tasas del mercado y un servicio al cliente excepcional. Entendemos la importancia de cada transacción y trabajamos para garantizar que tu esfuerzo llegue a quienes más quieres de la manera más rápida y confiable posible.</p>
                </div>
                
                <div class="content-section">
                    <h2 class="h3">Nuestros Valores</h2>
                    <ul class="list-unstyled">
                        <li class="mb-2"><strong>Confianza:</strong> Operamos con total transparencia y seguridad.</li>
                        <li class="mb-2"><strong>Compromiso:</strong> Estamos comprometidos con el bienestar de nuestros clientes.</li>
                        <li class="mb-2"><strong>Eficiencia:</strong> Buscamos siempre la forma más rápida y sencilla de hacer llegar tu dinero.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../remesas_private/src/templates/footer.php';
?>