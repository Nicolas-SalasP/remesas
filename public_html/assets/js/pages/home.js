document.addEventListener('DOMContentLoaded', () => {
    // Apuntar al CONTENEDOR, no al canvas
    const bcvContainer = document.getElementById('bcv-container');
    
    if (!bcvContainer) {
        console.warn('Elemento #bcv-container no encontrado en esta página.');
        return;
    }

    // Definir la función para renderizar el valor
    const renderizarValorDolar = async () => {
        try {
            // URL de la API (corregida, sin ../)
            const response = await fetch('api/?accion=getDolarBcv');

            if (!response.ok) {
                let errorMsg = `No se pudo obtener la data del dólar. Código: ${response.status}`;
                try {
                    const errorData = await response.json();
                    errorMsg = errorData.error || errorMsg;
                } catch (e) {
                }
                throw new Error(errorMsg);
            }

            const data = await response.json();

            if (!data.success || !data.valorActual) {
                throw new Error(data.error || 'La API del backend devolvió un error o no hay datos.');
            }

            const valorActual = parseFloat(data.valorActual) || 0;
            const fechaActualizacion = data.lastUpdate ? new Date(data.lastUpdate).toLocaleString('es-CL') : 'reciente';
            
            const valorFormateado = new Intl.NumberFormat('es-ES', {
                style: 'currency',
                currency: 'VES', 
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(valorActual);

            bcvContainer.innerHTML = `
                <h3 class="card-title text-center mb-3">Valor del Dólar (BCV)</h3>
                <div class="text-center p-3">
                    <h1 class="display-4 fw-bold text-primary">${valorFormateado}</h1>
                    <p class="text-muted mb-0">Tasa oficial del Banco Central de Venezuela</p>
                    <small class="text-muted">Última actualización: ${fechaActualizacion}</small>
                </div>
            `;

        } catch (error) {
            console.error("Error al obtener el valor del dólar:", error);
            if (bcvContainer) {
                 bcvContainer.innerHTML = `<h3 class="card-title text-center mb-3">Valor del Dólar (BCV)</h3><p class="text-center text-danger p-3">${error.message}</p>`;
            }
        }
    };

    renderizarValorDolar();
});