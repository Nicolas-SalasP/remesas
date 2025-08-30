document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('dolar-chart');
    if (!ctx) return;

    const renderizarGraficoDolar = async () => {
        try {
            // ===================================================================
            // CORRECCIÓN: Se quita ../ de la URL para apuntar correctamente
            const response = await fetch('api/?accion=getDolarBcv');
            // ===================================================================
            
            if (!response.ok) {
                throw new Error('No se pudo obtener la data del dólar. Código de estado: ' + response.status);
            }
            
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'La API devolvió un error.');
            }
            
            const valorActual = data.valor;

            // Generamos un historial falso para el gráfico
            const valores = [
                valorActual * 0.995,
                valorActual * 1.001,
                valorActual * 0.998,
                valorActual * 1.003,
                valorActual
            ];
            const labels = ['Hace 4 días', 'Hace 3 días', 'Ayer', 'Hoy'];

            // Crear el gráfico.
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: `Valor del Dólar BCV (Hoy: ${valorActual.toFixed(2)} Bs.)`,
                        data: valores,
                        borderColor: '#0056b3',
                        backgroundColor: 'rgba(0, 86, 179, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: { /* ... (opciones sin cambios) ... */ }
            });

        } catch (error) {
            console.error("Error al construir el gráfico:", error);
            ctx.parentElement.innerHTML = `<p class="text-center text-danger">${error.message}</p>`;
        }
    };

    renderizarGraficoDolar();
});