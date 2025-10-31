document.addEventListener('DOMContentLoaded', () => {
    const rateContainer = document.getElementById('rate-container');
    const valorActualEl = document.getElementById('rate-valor-actual');
    const descriptionEl = document.getElementById('rate-description');
    const ultimaActualizacionEl = document.getElementById('rate-ultima-actualizacion');
    const ctx = document.getElementById('rate-history-chart');
    
    if (!rateContainer || !ctx || !valorActualEl || !descriptionEl) {
        console.warn('Elementos del gráfico de tasa no encontrados en esta página.');
        return;
    }

    let rateChartInstance = null;

    const renderizarGraficoTasa = async () => {
        try {
            const response = await fetch('api/?accion=getDolarBcv'); 

            if (!response.ok) {
                let errorMsg = `No se pudo obtener la data del gráfico. Código: ${response.status}`;
                try {
                    const errorData = await response.json();
                    errorMsg = errorData.error || errorMsg;
                } catch (e) { }
                throw new Error(errorMsg);
            }

            const data = await response.json();

            if (!data.success || !data.valorActual || !data.labels || !data.data) {
                throw new Error(data.error || 'La API del backend devolvió datos incompletos para el gráfico.');
            }

            const valorActual = parseFloat(data.valorActual) || 0;
            const valorFormateado = new Intl.NumberFormat('es-ES', {
                style: 'currency',
                currency: 'VES',
                minimumFractionDigits: 4,
                maximumFractionDigits: 6
            }).format(valorActual);

            valorActualEl.textContent = valorFormateado;
            descriptionEl.textContent = `1 CLP = ${valorFormateado} VES`;
            ultimaActualizacionEl.textContent = `Tasa de referencia actual.`;

            if (rateChartInstance) {
                rateChartInstance.destroy();
            }

            rateChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Tasa Promedio (VES)',
                        data: data.data,
                        fill: true,
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        tension: 0.3,
                        pointRadius: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });

        } catch (error) {
            console.error("Error al obtener el valor de la tasa:", error);
            if (rateContainer) {
                 rateContainer.innerHTML = `<h3 class="card-title text-center mb-3">Tasa de Referencia</h3><p class="text-center text-danger p-3">${error.message}</p>`;
            }
        }
    };

    renderizarGraficoTasa();
});