document.addEventListener('DOMContentLoaded', () => {
    const uploadModalElement = document.getElementById('uploadReceiptModal');
    
    // Si el modal no existe en la página, no continuamos para evitar errores.
    if (!uploadModalElement) return;

    const uploadForm = document.getElementById('upload-receipt-form');
    const transactionIdField = document.getElementById('transactionIdField');
    const modalTxIdLabel = document.getElementById('modal-tx-id');

    // 1. Lógica para cuando se abre el modal
    uploadModalElement.addEventListener('show.bs.modal', function (event) {
        // Obtenemos el botón que activó el modal
        const button = event.relatedTarget;
        // Extraemos el ID de la transacción del atributo data-tx-id del botón
        const transactionId = button.getAttribute('data-tx-id');
        
        // Actualizamos el contenido del modal con el ID
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
});