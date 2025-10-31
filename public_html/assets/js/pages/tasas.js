document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('rate-editor-form');
    const paisOrigenSelect = document.getElementById('pais-origen');
    const paisDestinoSelect = document.getElementById('pais-destino');
    const rateValueInput = document.getElementById('rate-value');
    const saveButton = document.getElementById('save-rate-btn');
    const currentTasaIdInput = document.getElementById('current-tasa-id');
    const feedbackMessage = document.getElementById('feedback-message');
    const ratesTableBody = document.querySelector('#existing-rates-table tbody');

    const updateRateEditor = () => {
        const origenId = paisOrigenSelect.value;
        const destinoId = paisDestinoSelect.value;

        rateValueInput.disabled = true;
        saveButton.disabled = true;
        rateValueInput.value = '';
        currentTasaIdInput.value = 'new';
        rateValueInput.classList.remove('is-invalid');

        if (!origenId || !destinoId) {
            return;
        }

        if (origenId === destinoId) {
            rateValueInput.value = 'N/A';
            rateValueInput.classList.add('is-invalid');
            return;
        }

        if (ratesMap[origenId] && ratesMap[origenId][destinoId]) {
            const tasa = ratesMap[origenId][destinoId];
            rateValueInput.value = tasa.valor;
            currentTasaIdInput.value = tasa.tasaId;
        } else {
            rateValueInput.value = '0.000000';
            currentTasaIdInput.value = 'new';
        }

        rateValueInput.disabled = false;
        saveButton.disabled = false;
    };

    const handleSaveRate = async (e) => {
        e.preventDefault();

        const origenId = paisOrigenSelect.value;
        const destinoId = paisDestinoSelect.value;
        const nuevoValor = parseFloat(rateValueInput.value);
        const tasaId = currentTasaIdInput.value;

        if (nuevoValor <= 0 || isNaN(nuevoValor)) {
            window.showInfoModal('Error', 'El valor de la tasa debe ser un número positivo.', false);
            return;
        }

        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        try {
            const response = await fetch('../api/?accion=updateRate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tasaId: tasaId,
                    nuevoValor: nuevoValor,
                    origenId: origenId,
                    destinoId: destinoId
                })
            });

            const result = await response.json();
            feedbackMessage.classList.remove('alert-danger', 'alert-success');

            if (response.ok && result.success) {
                feedbackMessage.classList.add('alert', 'alert-success');
                feedbackMessage.textContent = '¡Tasa guardada con éxito!';

                const newTasaId = result.newTasaId.toString();
                
                if (!ratesMap[origenId]) ratesMap[origenId] = {};
                ratesMap[origenId][destinoId] = { tasaId: newTasaId, valor: nuevoValor.toFixed(6) };
                
                currentTasaIdInput.value = newTasaId;

                updateTable(origenId, destinoId, newTasaId, nuevoValor.toFixed(6));

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
            saveButton.innerHTML = '<i class="bi bi-save"></i>';
            setTimeout(() => {
                feedbackMessage.textContent = '';
                feedbackMessage.classList.remove('alert-danger', 'alert-success');
            }, 4000);
        }
    };

    const updateTable = (origenId, destinoId, newTasaId, nuevoValor) => {
        let rowFound = false;
        
        let row = document.getElementById(`tasa-row-${newTasaId}`);
        if (!row) {
            row = ratesTableBody.querySelector(`tr[data-origen-id="${origenId}"][data-destino-id="${destinoId}"]`);
        }

        if (row) {
            row.querySelector('.rate-value-cell').textContent = nuevoValor;
            rowFound = true;
        } else {
            const noDataRow = ratesTableBody.querySelector('td[colspan="4"]');
            if (noDataRow) noDataRow.parentElement.remove();

            const origenText = paisOrigenSelect.options[paisOrigenSelect.selectedIndex].text;
            const destinoText = paisDestinoSelect.options[paisDestinoSelect.selectedIndex].text;

            const newRow = document.createElement('tr');
            newRow.id = `tasa-row-${newTasaId}`;
            newRow.dataset.origenId = origenId;
            newRow.dataset.destinoId = destinoId;
            newRow.innerHTML = `
                <td>${origenText}</td>
                <td>${destinoText}</td>
                <td class="rate-value-cell">${nuevoValor}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary edit-rate-btn"
                            data-origen-id="${origenId}"
                            data-destino-id="${destinoId}"
                            title="Editar esta tasa">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                </td>
            `;
            ratesTableBody.appendChild(newRow);
        }
    };

    paisOrigenSelect.addEventListener('change', updateRateEditor);
    paisDestinoSelect.addEventListener('change', updateRateEditor);
    form.addEventListener('submit', handleSaveRate);

    ratesTableBody.addEventListener('click', (e) => {
        const editButton = e.target.closest('.edit-rate-btn');
        if (!editButton) return;

        const origenId = editButton.dataset.origenId;
        const destinoId = editButton.dataset.destinoId;

        paisOrigenSelect.value = origenId;
        paisDestinoSelect.value = destinoId;

        updateRateEditor();

        form.scrollIntoView({ behavior: 'smooth' });
        
        const cardBody = form.closest('.card-body');
        cardBody.style.transition = 'background-color 0.5s ease-out';
        cardBody.style.backgroundColor = '#e6f7ff';
        setTimeout(() => {
            cardBody.style.backgroundColor = '';
        }, 1500);
    });
});