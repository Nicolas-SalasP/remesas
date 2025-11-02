<?php
    require_once __DIR__ . '/../remesas_private/src/core/init.php';
    $pageTitle = 'Contacto';
    $pageScript = 'contacto.js';
    require_once __DIR__ . '/../remesas_private/src/templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card p-4 p-md-5 shadow-sm">
                <div class="text-center mb-4">
                    <h1>Ponte en Contacto Hoy</h1>
                    <p class="lead text-muted">¿Tienes alguna duda? Estamos aquí para ayudarte.</p>
                </div>

                <form id="contact-form" novalidate>
                    <div class="mb-3">
                        <label for="contact-name" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="contact-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="contact-email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="contact-email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="contact-subject" class="form-label">Asunto</label>
                        <input type="text" class="form-control" id="contact-subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="contact-message" class="form-label">Mensaje</label>
                        <textarea class="form-control" id="contact-message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" id="contact-submit-btn" class="btn btn-primary w-100 py-2">
                        Enviar Mensaje
                    </button>
                </form>
                
                <div id="contact-feedback" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<?php
    require_once __DIR__ . '/../remesas_private/src/templates/footer.php';
?>