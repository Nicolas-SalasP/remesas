<?php
    $pageTitle = 'Contacto';
    require_once '../src/templates/header.php';
?>

<div class="container page-content">
    <div class="text-center">
        <h1>Ponte en Contacto</h1>
        <p class="subtitle">¿Tienes alguna duda? Estamos aquí para ayudarte.</p>
    </div>

    <div class="form-container-small">
        <form id="contact-form">
            <div class="form-group">
                <label for="contact-name">Nombre Completo</label>
                <input type="text" id="contact-name" name="name" required>
            </div>
            <div class="form-group">
                <label for="contact-email">Correo Electrónico</label>
                <input type="email" id="contact-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="contact-subject">Asunto</label>
                <input type="text" id="contact-subject" name="subject" required>
            </div>
            <div class="form-group">
                <label for="contact-message">Mensaje</label>
                <textarea id="contact-message" name="message" rows="6" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-full-width">Enviar Mensaje</button>
        </form>
    </div>
    <p class="text-center" style="margin-top: 2rem;">
        También puedes contactarnos a través de nuestro correo: <strong>soporte@tuempresa.com</strong>
    </p>
</div>

<?php
    require_once '../src/templates/footer.php';
?>