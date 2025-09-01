</main> <footer class="main-footer bg-dark text-white pt-4 pb-2 mt-auto">
    <div class="container text-center text-md-start">
        <div class="row">
            <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4">
                <h6 class="text-uppercase fw-bold mb-4 logo-footer">TuLogo</h6>
                <p>
                    La forma más rápida, segura y confiable de enviar dinero a tus seres queridos.
                </p>
            </div>

            <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mb-4">
                <h6 class="text-uppercase fw-bold mb-4">Enlaces Rápidos</h6>
                <p><a href="<?php echo BASE_URL; ?>/quienes-somos.php" class="text-reset">Quiénes Somos</a></p>
                <p><a href="<?php echo BASE_URL; ?>/contacto.php" class="text-reset">Contacto</a></p>
                <p><a href="#!" class="text-reset">Preguntas Frecuentes</a></p>
            </div>

            <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4">
                <h6 class="text-uppercase fw-bold mb-4">Contacto</h6>
                <p><i class="bi bi-geo-alt-fill me-2"></i>Chile, Santiago</p>
                <p><i class="bi bi-envelope-fill me-2"></i>info@tuempresa.com</p>
                <p><i class="bi bi-telephone-fill me-2"></i>+56 9 1234 5678</p>
            </div>
        </div>
    </div>

    <div class="text-center p-3 border-top border-secondary">
        <div class="container d-flex justify-content-between align-items-center">
            <span class="text-muted">&copy; <?php echo date('Y'); ?> Tu Empresa de Remesas.</span>
            <div>
                <a href="#!" class="btn btn-outline-light btn-floating m-1" role="button"><i class="bi bi-facebook"></i></a>
                <a href="#!" class="btn btn-outline-light btn-floating m-1" role="button"><i class="bi bi-twitter-x"></i></a>
                <a href="#!" class="btn btn-outline-light btn-floating m-1" role="button"><i class="bi bi-instagram"></i></a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php if (isset($pageScripts) && is_array($pageScripts)): // Si es un array de scripts ?>
    <?php foreach ($pageScripts as $script): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo htmlspecialchars($script); ?>"></script>
    <?php endforeach; ?>
<?php elseif (isset($pageScript)): // Si es un solo script (para mantener compatibilidad) ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/pages/<?php echo htmlspecialchars($pageScript); ?>"></script>
<?php endif; ?>

</body>
</html>