document.addEventListener('DOMContentLoaded', () => {
    const uploadModalElement = document.getElementById('uploadReceiptModal');
    
    // Si el modal no existe en la página, no continuamos para evitar errores.
    if (!uploadModalElement) return;

    const uploadForm = document.getElementById('upload-receipt-form');
    const transactionIdField = document.getElementById('transactionIdField');
    const modalTxIdLabel = document.getElementById('modal-tx-id');

    // 1. Lógica para cuando se abre el modal
    uploadModalElement.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const transactionId = button.getAttribute('data-tx-id');
        transactionIdField.value = transactionId;
        modalTxIdLabel.textContent = transactionId;
    });

    // 2. Lógica para cuando se envía el formulario
    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(uploadForm);
        
        try {
            const response = await fetch('../api/?accion=uploadReceipt', {
                method: 'POST',
                body: formData 
            });

            const result = await response.json();

            if (result.success) {
                alert('¡Comprobante subido con éxito! La página se recargará.');
                window.location.reload(); 
            } else {
                alert('Error al subir el archivo: ' + result.error);
            }
        } catch (error) {
            console.error('Error de red:', error);
            alert('No se pudo conectar con el servidor.');
        }
    });

const cancelButtons = document.querySelectorAll('.cancel-btn');

cancelButtons.forEach(button => {
    button.addEventListener('click', async (e) => {
        const transactionId = e.target.dataset.txId;

        if (!confirm(`¿Estás seguro de que quieres cancelar la transacción #${transactionId}? Esta acción no se puede deshacer.`)) {
            return;
        }

        try {
            const response = await fetch('../api/?accion=cancelTransaction', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ transactionId })
            });
            const result = await response.json();

            if (result.success) {
                alert('Transacción cancelada con éxito. La página se recargará.');
                window.location.reload();
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            alert('Error de conexión con el servidor.');
        }
    });
});
});