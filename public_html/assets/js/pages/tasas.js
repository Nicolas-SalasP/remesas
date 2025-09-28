document.addEventListener('DOMContentLoaded', () => {
    const saveButtons = document.querySelectorAll('.save-rate-btn');
    const feedbackMessage = document.getElementById('feedback-message');

    saveButtons.forEach(button => {
        button.addEventListener('click', async (e) => {
            const tasaId = e.target.dataset.tasaId;
            const inputElement = document.querySelector(`.rate-input[data-tasa-id="${tasaId}"]`);
            const nuevoValor = inputElement.value;

            if (!nuevoValor || parseFloat(nuevoValor) <= 0) {
                alert('Por favor, ingresa un valor de tasa válido y mayor a cero.');
                return;
            }

            try {
                const response = await fetch('../api/?accion=updateRate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ tasaId: tasaId, nuevoValor: nuevoValor })
                });
                const result = await response.json();

                feedbackMessage.classList.remove('alert-danger', 'alert-success');
                if (result.success) {
                    feedbackMessage.classList.add('alert', 'alert-success');
                    feedbackMessage.textContent = '¡Tasa actualizada con éxito!';
                } else {
                    feedbackMessage.classList.add('alert', 'alert-danger');
                    feedbackMessage.textContent = 'Error: ' + result.error;
                }
            } catch (error) {
                feedbackMessage.classList.add('alert', 'alert-danger');
                feedbackMessage.textContent = 'Error de conexión con el servidor.';
            }
        });
    });
});