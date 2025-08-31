<?php
    // Asegurarnos de que la configuración base siempre esté disponible en este archivo.
    // require_once es seguro, no cargará el archivo dos veces si el header ya lo hizo.
    require_once __DIR__ . '/../config.php';
?>
</main> <footer class="main-footer bg-dark text-white pt-5 pb-4 mt-auto">
    <div class="container text-center text-md-start">
        <div class="row">
            <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4">
                <h6 class="text-uppercase fw-bold mb-4 logo-footer">TuLogo</h6>
                <p>
                    La forma más rápida, segura y confiable de enviar dinero a tus seres queridos, acortando distancias con cada transacción.
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
                <p><i class="bi bi-geo-alt-fill me-3"></i>Chile, Santiago</p>
                <p><i class="bi bi-envelope-fill me-3"></i>info@tuempresa.com</p>
                <p><i class="bi bi-telephone-fill me-3"></i>+56 9 1234 5678</p>
            </div>
        </div>
    </div>

    <div class="container text-center py-4">
        <a href="#!" class="btn btn-outline-light btn-floating m-1" role="button"><i class="bi bi-facebook"></i></a>
        <a href="#!" class="btn btn-outline-light btn-floating m-1" role="button"><i class="bi bi-twitter-x"></i></a>
        <a href="#!" class="btn btn-outline-light btn-floating m-1" role="button"><i class="bi bi-instagram"></i></a>
    </div>

    <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
        &copy; <?php echo date('Y'); ?> Tu Empresa de Remesas. Todos los derechos reservados.
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php if (isset($pageScript)): ?>
    <script type="module" src="<?php echo BASE_URL; ?>/assets/js/pages/<?php echo $pageScript; ?>"></script>
<?php endif; ?>

</body>
</html>