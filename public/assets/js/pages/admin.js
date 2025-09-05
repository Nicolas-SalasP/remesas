document.addEventListener('DOMContentLoaded', () => {
    
    // ===================================================================
    // LÓGICA PARA LA PÁGINA PRINCIPAL DE TRANSACCIONES (admin/index.php)
    // ===================================================================
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const resetFiltersBtn = document.getElementById('resetFilters');
    const tableBody = document.getElementById('transactionsTableBody');
    const tableRows = tableBody ? tableBody.querySelectorAll('tr') : [];

    function filterTransactions() {
        if (!searchInput) return; // Si no estamos en la página principal, no hacer nada
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;

        tableRows.forEach(row => {
            const userText = row.querySelector('.search-user')?.textContent.toLowerCase() || '';
            const beneficiaryText = row.querySelector('.search-beneficiary')?.textContent.toLowerCase() || '';
            // Buscamos el texto dentro del badge de estado
            const statusText = row.querySelector('td span.badge')?.textContent.trim() || '';

            const matchesSearch = userText.includes(searchTerm) || beneficiaryText.includes(searchTerm);
            const matchesStatus = statusValue === '' || statusText === statusValue;
            
            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', filterTransactions);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterTransactions);
    }
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', () => {
            searchInput.value = '';
            statusFilter.value = '';
            filterTransactions();
        });
    }

    // ===================================================================
    // LÓGICA PARA LA PÁGINA DE TRANSACCIONES PENDIENTES (admin/pendientes.php)
    // ===================================================================

    // --- Modal de subida de comprobante del admin ---
    const adminUploadModalElement = document.getElementById('adminUploadModal');
    if (adminUploadModalElement) {
        const adminUploadForm = document.getElementById('admin-upload-form');
        const adminTransactionIdField = document.getElementById('adminTransactionIdField');
        const modalAdminTxIdLabel = document.getElementById('modal-admin-tx-id');

        adminUploadModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const transactionId = button.getAttribute('data-tx-id');
            adminTransactionIdField.value = transactionId;
            modalAdminTxIdLabel.textContent = transactionId;
        });

        adminUploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(adminUploadForm);
            try {
                const response = await fetch('../api/?accion=adminUploadProof', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert('Comprobante de envío subido. La página se recargará.');
                    window.location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error de conexión con el servidor.');
            }
        });
    }
    
    // --- Botones de acción (Confirmar / Rechazar) ---
    async function handleTransactionAction(action, transactionId) {
        let confirmationMessage = '';
        if (action === 'processTransaction') {
            confirmationMessage = `¿Confirmas que has recibido el pago para la transacción #${transactionId} y quieres ponerla 'En Proceso'?`;
        } else if (action === 'rejectTransaction') {
            confirmationMessage = `¿Estás seguro de que quieres RECHAZAR y cancelar la transacción #${transactionId}?`;
        }
        
        if (!confirmationMessage || !confirm(confirmationMessage)) return;

        try {
            const response = await fetch(`../api/?accion=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ transactionId })
            });
            const result = await response.json();
            if (result.success) {
                alert('Acción completada. La página se recargará.');
                window.location.reload();
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            alert('Error de conexión con el servidor.');
        }
    }

    document.querySelectorAll('.process-btn').forEach(button => {
        button.addEventListener('click', (e) => handleTransactionAction('processTransaction', e.target.dataset.txId));
    });

    document.querySelectorAll('.reject-btn').forEach(button => {
        button.addEventListener('click', (e) => handleTransactionAction('rejectTransaction', e.target.dataset.txId));
    });

    // ===================================================================
    // LÓGICA PARA LA PÁGINA DE GESTIONAR PAÍSES (admin/paises.php)
    // ===================================================================

    // --- Formulario de añadir país ---
    const addPaisForm = document.getElementById('add-pais-form');
    if (addPaisForm) {
        addPaisForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(addPaisForm);
            const data = Object.fromEntries(formData.entries());
            try {
                const response = await fetch('../api/?accion=addPais', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    alert('¡País añadido con éxito! La página se recargará.');
                    window.location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error de conexión con el servidor.');
            }
        });
    }
    
    // --- Cambiar rol de país ---
    const roleSelects = document.querySelectorAll('.role-select');
    roleSelects.forEach(select => {
        select.addEventListener('change', async (e) => {
            const paisId = e.target.dataset.paisId;
            const newRole = e.target.value;
            if (!confirm(`¿Estás seguro de que quieres cambiar el rol de este país a "${newRole}"?`)) {
                // Lógica simple para revertir si se cancela
                const originalOption = Array.from(e.target.options).find(opt => opt.defaultSelected);
                if (originalOption) e.target.value = originalOption.value;
                return;
            }
            try {
                const response = await fetch('../api/?accion=updatePaisRol', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ paisId, newRole })
                });
                const result = await response.json();
                if (result.success) {
                    alert('¡Rol actualizado con éxito!');
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error de conexión con el servidor.');
            }
        });
    });

    // --- Cambiar estado de país con modal de confirmación ---
    const confirmModalElement = document.getElementById('confirmModal');
    if (confirmModalElement) {
        const confirmModal = new bootstrap.Modal(confirmModalElement);
        const confirmModalBody = document.getElementById('confirmModalBody');
        const confirmActionBtn = document.getElementById('confirmActionBtn');
        const toggleStatusButtons = document.querySelectorAll('.toggle-status-btn');
        let actionToConfirm = null;
        let triggerButton = null;

        toggleStatusButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                triggerButton = e.currentTarget;
                const paisId = triggerButton.dataset.paisId;
                const currentStatus = triggerButton.dataset.currentStatus;
                const newStatus = currentStatus === '1' ? 0 : 1;
                const newStatusText = newStatus === 1 ? 'Activo' : 'Inactivo';

                confirmModalBody.textContent = `¿Seguro que quieres cambiar el estado de este país a "${newStatusText}"?`;
                actionToConfirm = () => performStatusToggle(paisId, newStatus, triggerButton);
                confirmModal.show();
            });
        });

        confirmActionBtn.addEventListener('click', () => {
            if (actionToConfirm) {
                actionToConfirm();
            }
            confirmModal.hide();
        });
        
        confirmModalElement.addEventListener('hidden.bs.modal', () => {
            if (triggerButton) {
                triggerButton.focus();
            }
        });

        async function performStatusToggle(paisId, newStatus, buttonElement) {
            try {
                const response = await fetch('../api/?accion=togglePaisStatus', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ paisId: paisId, newStatus: newStatus })
                });
                const result = await response.json();
                if (result.success) {
                    buttonElement.dataset.currentStatus = newStatus;
                    buttonElement.textContent = newStatus === 1 ? 'Activo' : 'Inactivo';
                    buttonElement.classList.toggle('btn-success', newStatus === 1);
                    buttonElement.classList.toggle('btn-secondary', newStatus === 0);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error de conexión con el servidor.');
            }
        }
    }
});