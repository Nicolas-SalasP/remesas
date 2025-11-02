document.addEventListener('DOMContentLoaded', () => {
    const contactForm = document.getElementById('contact-form');
    const submitButton = document.getElementById('contact-submit-btn');
    const feedbackDiv = document.getElementById('contact-feedback');

    if (!contactForm || !submitButton || !feedbackDiv) {
        console.warn('Elementos del formulario de contacto no encontrados.');
        return;
    }

    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...`;
        feedbackDiv.innerHTML = '';
        feedbackDiv.className = 'mt-3';

        const formData = new FormData(contactForm);
        const data = Object.fromEntries(formData.entries());

        if (!data.name || !data.email || !data.subject || !data.message) {
            feedbackDiv.className = 'alert alert-danger mt-3';
            feedbackDiv.textContent = 'Por favor, completa todos los campos.';
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
            return;
        }

        try {
            const response = await fetch('api/?accion=submitContactForm', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                feedbackDiv.className = 'alert alert-success mt-3';
                feedbackDiv.textContent = '¡Mensaje enviado con éxito! Te contactaremos pronto.';
                contactForm.reset();
            } else {
                throw new Error(result.error || 'No se pudo enviar el mensaje.');
            }
        } catch (error) {
            feedbackDiv.className = 'alert alert-danger mt-3';
            feedbackDiv.textContent = 'Error: ' + error.message;
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });
});