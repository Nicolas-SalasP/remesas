<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';
if (!isset($_SESSION['user_id'])) { 
    header('Location: ' . BASE_URL . '/login.php'); 
    exit(); 
}

if (isset($_SESSION['verification_status']) && in_array($_SESSION['verification_status'], ['Verificado', 'Pendiente'])) {
    header('Location: ' . BASE_URL . '/dashboard/');
    exit();
}

$pageTitle = 'Verificar Identidad';
$pageScript = 'verificar.js';
require_once __DIR__ . '/../../remesas_private/src/templates/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4 shadow-sm">
                <h1 class="text-center mb-3">Verificacion de Identidad</h1>
                <p class="text-center text-muted">Para poder realizar transacciones, es obligatorio que verifiques tu identidad. Por favor, sube una foto de tu RUT por ambos lados.</p>
                
                <div id="verification-alert" class="alert d-none" role="alert"></div>

                <form id="verification-form">
                    <div class="mb-3">
                        <label for="docFrente" class="form-label">RUT (Lado Frontal)</label>
                        <input class="form-control" type="file" id="docFrente" name="docFrente" accept="image/jpeg, image/png" required>
                    </div>
                    <div class="mb-3">
                        <label for="docReverso" class="form-label">RUT (Lado Reverso)</label>
                        <input class="form-control" type="file" id="docReverso" name="docReverso" accept="image/jpeg, image/png" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Enviar para Verificacion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../remesas_private/src/templates/footer.php';
?>