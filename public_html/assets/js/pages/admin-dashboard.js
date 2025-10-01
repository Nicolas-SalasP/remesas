document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('transactionsChart');
    const filterForm = document.getElementById('filter-form');
    const startDateInput = document.getElementById('fecha_inicio');
    const endDateInput = document.getElementById('fecha_fin');
    const chartTitle = document.querySelector('.card-header h5');

    if (!ctx || !filterForm) return;

    let chartInstance = null;

    const renderChart = async (startDate, endDate) => {
        chartTitle.textContent = 'Cargando...';
        try {
            const response = await fetch(`../api/?accion=getDashboardStats&fecha_inicio=${startDate}&fecha_fin=${endDate}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'No se pudieron cargar las estadísticas.');
            }

            const startFormatted = new Date(startDate + 'T00:00:00').toLocaleDateString('es-CL');
            const endFormatted = new Date(endDate + 'T00:00:00').toLocaleDateString('es-CL');
            chartTitle.textContent = `Estadísticas desde ${startFormatted} hasta ${endFormatted}`;

            if (chartInstance) {
                chartInstance.destroy();
            }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: result.stats.labels,
                    datasets: result.stats.datasets.map(dataset => ({
                        ...dataset,
                        fill: true,
                        tension: 0.3
                    }))
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Cantidad de Transacciones' },
                            beginAtZero: true
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: { display: true, text: 'Volumen (CLP)' },
                            grid: { drawOnChartArea: false },
                            beginAtZero: true
                        }
                    }
                }
            });
        } catch (error) {
            console.error("Error al renderizar el gráfico:", error);
            chartTitle.textContent = 'Error al cargar datos';
            ctx.parentElement.innerHTML = `<p class="text-center text-danger">${error.message}</p>`;
        }
    };

    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        if (!startDate || !endDate) {
            alert('Por favor, selecciona una fecha de inicio y una de fin.');
            return;
        }

        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (end < start) {
            alert('La fecha de fin no puede ser anterior a la fecha de inicio.');
            return;
        }

        if (diffDays > 30) {
            alert('El rango de fechas no puede ser mayor a 31 días.');
            return;
        }

        renderChart(startDate, endDate);
    });

    const today = new Date();
    const sevenDaysAgo = new Date();
    sevenDaysAgo.setDate(today.getDate() - 6);

    const initialEndDate = today.toISOString().split('T')[0];
    const initialStartDate = sevenDaysAgo.toISOString().split('T')[0];

    startDateInput.value = initialStartDate;
    endDateInput.value = initialEndDate;

    renderChart(initialStartDate, initialEndDate);
});