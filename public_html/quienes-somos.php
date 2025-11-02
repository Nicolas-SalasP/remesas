<?php
require_once __DIR__ . '/../remesas_private/src/core/init.php';
$pageTitle = 'Quiénes Somos';
require_once __DIR__ . '/../remesas_private/src/templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            
            <div class="card p-4 p-md-5 shadow-sm border-0 mb-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-6 text-center text-md-start">
                        <h1 class="display-5 fw-bold">Sobre JC Envíos</h1>
                        <p class="lead text-muted">Conectando familias, acortando distancias.</p>
                        <p>Entendemos la importancia de cada transacción y trabajamos para garantizar que tu esfuerzo llegue a quienes más quieres de la manera más rápida y confiable posible.</p>
                    </div>
                    <div class="col-md-6 text-center">
                        <img src="<?php echo BASE_URL; ?>/assets/img/logo.jpeg" alt="Logo JC Envios" class="img-fluid rounded shadow" style="max-height: 250px;">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4 text-center">
                            <div class="display-4 text-primary mb-3">
                                <i class="bi bi-bullseye"></i>
                            </div>
                            <h2 class="h3 fw-bold">Nuestra Misión</h2>
                            <p class="mb-0">Ofrecer un servicio de envío de dinero transparente, seguro y eficiente, brindando las mejores tasas del mercado y un servicio al cliente excepcional.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4 text-center">
                            <div class="display-4 text-primary mb-3">
                                <i class="bi bi-gem"></i>
                            </div>
                            <h2 class="h3 fw-bold">Nuestros Valores</h2>
                            <ul class="list-unstyled text-start mt-3" style="font-size: 1.1rem;">
                                <li class="mb-2">
                                    <i class="bi bi-shield-check-fill text-success me-2"></i>
                                    <strong>Confianza:</strong> Operamos con total transparencia y seguridad.
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-people-fill text-success me-2"></i>
                                    <strong>Compromiso:</strong> Estamos comprometidos con el bienestar de nuestros clientes.
                                </li>
                                <li class="mb-0">
                                    <i class="bi bi-lightning-charge-fill text-success me-2"></i>
                                    <strong>Eficiencia:</strong> Buscamos la forma más rápida de hacer llegar tu dinero.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../remesas_private/src/templates/footer.php';
?>