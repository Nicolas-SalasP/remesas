document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('rate-editor-form');
    const paisOrigenSelect = document.getElementById('pais-origen');
    const paisDestinoSelect = document.getElementById('pais-destino');
    const rateValueInput = document.getElementById('rate-value');
    const montoMinInput = document.getElementById('rate-monto-min');
    const montoMaxInput = document.getElementById('rate-monto-max');
    const saveButton = document.getElementById('save-rate-btn');
    const currentTasaIdInput = document.getElementById('current-tasa-id');
    const feedbackMessage = document.getElementById('feedback-message');
    const ratesTableBody = document.querySelector('#existing-rates-table tbody');

    const resetEditor = (clearDropdowns = false) => {
        rateValueInput.disabled = true;
        montoMinInput.disabled = true;
        montoMaxInput.disabled = true;
        saveButton.disabled = true;

        rateValueInput.value = '';
        montoMinInput.value = '0.00';
        montoMaxInput.value = '9999999999.99';
        currentTasaIdInput.value = 'new';
        rateValueInput.classList.remove('is-invalid');

        if (clearDropdowns) {
            paisOrigenSelect.value = '';
            paisDestinoSelect.value = '';
        }
    };

    const enableEditor = () => {
        const origenId = paisOrigenSelect.value;
        const destinoId = paisDestinoSelect.value;

        if (!origenId || !destinoId) {
            resetEditor();
            return;
        }

        if (origenId === destinoId) {
            rateValueInput.value = 'N/A';
            rateValueInput.classList.add('is-invalid');
            montoMinInput.disabled = true;
            montoMaxInput.disabled = true;
            saveButton.disabled = true;
            return;
        }

        rateValueInput.disabled = false;
        montoMinInput.disabled = false;
        montoMaxInput.disabled = false;
        saveButton.disabled = false;
    };

    const handleSaveRate = async (e) => {
        e.preventDefault();

        const origenId = paisOrigenSelect.value;
        const destinoId = paisDestinoSelect.value;
        const nuevoValor = parseFloat(rateValueInput.value);
        const tasaId = currentTasaIdInput.value;
        const montoMin = parseFloat(montoMinInput.value);
        const montoMax = parseFloat(montoMaxInput.value);

        if (nuevoValor <= 0 || isNaN(nuevoValor) || isNaN(montoMin) || isNaN(montoMax)) {
            window.showInfoModal('Error', 'El valor de la tasa y los montos deben ser números positivos.', false);
            return;
        }

        if (montoMin >= montoMax) {
             window.showInfoModal('Error', 'El Monto Mínimo no puede ser mayor or igual al Monto Máximo.', false);
            return;
        }

        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

        try {
            const response = await fetch('../api/?accion=updateRate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tasaId: tasaId,
                    nuevoValor: nuevoValor,
                    origenId: origenId,
                    destinoId: destinoId,
                    montoMin: montoMin,
                    montoMax: montoMax
                })
            });

            const result = await response.json();
            feedbackMessage.classList.remove('alert-danger', 'alert-success');

            if (response.ok && result.success) {
                feedbackMessage.classList.add('alert', 'alert-success');
                feedbackMessage.textContent = '¡Tasa guardada con éxito!';

                const newTasaId = result.newTasaId.toString();
                
                updateTable(origenId, destinoId, newTasaId, nuevoValor, montoMin, montoMax);
                resetEditor(true);

            } else {
                feedbackMessage.classList.add('alert', 'alert-danger');
                feedbackMessage.textContent = 'Error: ' + (result.error || 'Ocurrió un problema.');
            }
        } catch (error) {
            console.error("Error al guardar tasa:", error);
            feedbackMessage.classList.add('alert', 'alert-danger');
            feedbackMessage.textContent = 'Error de conexión con el servidor.';
        } finally {
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="bi bi-save"></i> Guardar Tasa';
            setTimeout(() => {
                feedbackMessage.textContent = '';
                feedbackMessage.classList.remove('alert-danger', 'alert-success');
            }, 4000);
        }
    };

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
    };

    const updateTable = (origenId, destinoId, tasaId, valor, min, max) => {
        let row = document.getElementById(`tasa-row-${tasaId}`);

        if (row) {
            row.querySelector('.rate-value-cell').textContent = valor;
            row.querySelector('.rate-min-cell').textContent = formatCurrency(min);
            row.querySelector('.rate-max-cell').textContent = formatCurrency(max);
        } else {
            const noDataRow = ratesTableBody.querySelector('td[colspan="6"]');
            if (noDataRow) noDataRow.parentElement.remove();

            const origenText = paisOrigenSelect.options[paisOrigenSelect.selectedIndex].text;
            const destinoText = paisDestinoSelect.options[paisDestinoSelect.selectedIndex].text;

            const newRow = document.createElement('tr');
            newRow.id = `tasa-row-${tasaId}`;
            newRow.dataset.origenId = origenId;
            newRow.dataset.destinoId = destinoId;
            newRow.innerHTML = `
                <td>${origenText}</td>
                <td>${destinoText}</td>
                <td class="rate-min-cell">${formatCurrency(min)}</td>
                <td class="rate-max-cell">${formatCurrency(max)}</td>
                <td class="rate-value-cell">${valor}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary edit-rate-btn"
                            data-tasa-id="${tasaId}"
                            data-origen-id="${origenId}"
                            data-destino-id="${destinoId}"
                            data-valor="${valor}"
                            data-min="${min}"
                            data-max="${max}"
                            title="Editar esta tasa">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                </td>
            `;
            ratesTableBody.appendChild(newRow);
        }
    };

    paisOrigenSelect.addEventListener('change', enableEditor);
    paisDestinoSelect.addEventListener('change', enableEditor);
    form.addEventListener('submit', handleSaveRate);

    ratesTableBody.addEventListener('click', (e) => {
        const editButton = e.target.closest('.edit-rate-btn');
        if (!editButton) return;

        const tasaId = editButton.dataset.tasaId;
        const origenId = editButton.dataset.origenId;
        const destinoId = editButton.dataset.destinoId;
        const valor = editButton.dataset.valor;
        const min = editButton.dataset.min;
        const max = editButton.dataset.max;

        paisOrigenSelect.value = origenId;
        paisDestinoSelect.value = destinoId;
        
        rateValueInput.value = valor;
        montoMinInput.value = min;
        montoMaxInput.value = max;
        currentTasaIdInput.value = tasaId;

        enableEditor();

        form.scrollIntoView({ behavior: 'smooth' });
        
        const cardBody = form.closest('.card-body');
        cardBody.style.transition = 'background-color 0.5s ease-out';
        cardBody.style.backgroundColor = '#e6f7ff';
        setTimeout(() => {
            cardBody.style.backgroundColor = '';
        }, 1500);
    });

    resetEditor();
});