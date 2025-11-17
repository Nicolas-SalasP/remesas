<?php
require_once __DIR__ . '/../remesas_private/src/core/init.php';
$pageTitle = 'Contacto';
$pageScript = 'contacto.js';
require_once __DIR__ . '/../remesas_private/src/templates/header.php';
?>

<div class="container mt-4">

    <div class="text-center mb-4">
        <h1 class="display-6 fw-bold">Ponte en Contacto Hoy</h1>
        <p class="lead text-muted">¿Tienes alguna duda? Estamos aquí para ayudarte.</p>
    </div>

    <div class="row g-5"> <?php ?>

        <div class="col-lg-7">
            <div class="card p-4 p-md-5 shadow-sm border-0 h-100">
                <h4 class="mb-4 fw-bold text-primary"><i class="bi bi-envelope-fill me-2"></i>Escríbenos un Mensaje</h4>

                <form id="contact-form" novalidate>
                    <div class="mb-3 input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                        <input type="text" class="form-control" id="contact-name" name="name"
                            placeholder="Nombre Completo" required>
                    </div>

                    <div class="mb-3 input-group">
                        <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                        <input type="email" class="form-control" id="contact-email" name="email"
                            placeholder="Correo Electrónico" required>
                    </div>

                    <div class="mb-3 input-group">
                        <span class="input-group-text"><i class="bi bi-chat-left-dots-fill"></i></span>
                        <input type="text" class="form-control" id="contact-subject" name="subject" placeholder="Asunto"
                            required>
                    </div>

                    <div class="mb-3">
                        <textarea class="form-control" id="contact-message" name="message" rows="5"
                            placeholder="Escribe tu mensaje aquí..." required></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" id="contact-submit-btn" class="btn btn-primary btn-lg py-2">
                            <i class="bi bi-send-fill me-2"></i>Enviar Mensaje
                        </button>
                    </div>
                </form>

                <div id="contact-feedback" class="mt-3"></div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card p-4 shadow-sm border-0 h-100">
                <h4 class="mb-4 fw-bold text-primary"><i class="bi bi-geo-alt-fill me-2"></i>Nuestra Ubicación</h4>

                <p>
                    <strong class="d-block">Dirección:</strong>
                    <i class="bi bi-geo-alt-fill me-2"></i>Agustinas 681, Santiago, Chile
                </p>

                <hr class="my-3">

                <div class="ratio ratio-16x9 rounded shadow-sm">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3329.283121110051!2d-70.65211138479998!3d-33.4414923807759!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9662c5a525f7a26b%3A0x747062f6b6a6c2b3!2sAgustinas%20681%2C%20Santiago%2C%20Regi%C3%B3n%20Metropolitana!5e0!3m2!1ses-419!2scl!4v1678888888888!5m2!1ses-419!2scl"
                        style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                        title="Mapa de ubicación de JC Envios">
                    </iframe>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
require_once __DIR__ . '/../remesas_private/src/templates/footer.php';
?>