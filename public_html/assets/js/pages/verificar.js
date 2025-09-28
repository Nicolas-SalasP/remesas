document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('verification-form');
    const alertDiv = document.getElementById('verification-alert');

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Enviando...';
        alertDiv.classList.add('d-none');

        try {
            const response = await fetch('../api/?accion=uploadVerificationDocs', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            alertDiv.classList.remove('d-none');
            if (result.success) {
                alertDiv.className = 'alert alert-success';
                alertDiv.textContent = '¡Documentos enviados! Serás notificado cuando tu cuenta sea verificada. Serás redirigido en 5 segundos.';
                form.reset();
                setTimeout(() => window.location.href = 'index.php', 5000);
            } else {
                alertDiv.className = 'alert alert-danger';
                alertDiv.textContent = 'Error: ' + (result.error || 'Ocurrió un problema.');
                submitButton.disabled = false;
                submitButton.textContent = 'Enviar para Verificación';
            }
        } catch (error) {
            alertDiv.className = 'alert alert-danger';
            alertDiv.textContent = 'Error de conexión. Inténtalo de nuevo.';
            submitButton.disabled = false;
            submitButton.textContent = 'Enviar para Verificación';
        }
    });
});