document.addEventListener('DOMContentLoaded', () => {
    const verifyForm = document.getElementById('form-2fa-verify');
    const backupForm = document.getElementById('form-2fa-backup');
    
    if (!verifyForm || !backupForm) return;

    const handleSubmit = async (e, formElement) => {
        e.preventDefault();
        const formData = new FormData(formElement);
        const code = formData.get('code');
        const submitButton = formElement.querySelector('button[type="submit"]');
        
        if (!code) return;

        submitButton.disabled = true;
        const originalText = submitButton.textContent;
        submitButton.textContent = 'Verificando...';

        try {
            const response = await fetch('api/?accion=verify2FACode', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                window.location.href = result.redirect || '../dashboard/';
            } else {
                throw new Error(result.error || 'Código inválido.');
            }
        } catch (error) {
            console.error('Error al verificar 2FA:', error);
            if (window.showInfoModal) {
                window.showInfoModal('Error de Verificación', error.message, false);
            } else {
                alert(error.message);
            }
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    };

    verifyForm.addEventListener('submit', (e) => handleSubmit(e, verifyForm));
    backupForm.addEventListener('submit', (e) => handleSubmit(e, backupForm));
});