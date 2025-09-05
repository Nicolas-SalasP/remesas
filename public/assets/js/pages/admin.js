document.addEventListener('DOMContentLoaded', () => {
    
    const statusSelects = document.querySelectorAll('.status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', async (e) => {
            const transactionId = e.target.dataset.txId;
            const newStatus = e.target.value;

            const originalSelectedIndex = e.target.selectedIndex;
            
            if (!confirm(`¿Estás seguro de que quieres cambiar el estado de la transacción #${transactionId} a "${newStatus}"?`)) {
                e.target.selectedIndex = originalSelectedIndex; 
                return;
            }

            try {
                const response = await fetch('../api/?accion=updateTransactionStatus', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transactionId, newStatus })
                });
                const result = await response.json();
                if (result.success) {
                    alert('¡Estado actualizado con éxito!');
                    e.target.closest('tr').style.backgroundColor = '#d4edda';
                } else {
                    alert('Error: ' + result.error);
                    e.target.selectedIndex = originalSelectedIndex; 
                }
            } catch (error) {
                alert('Error de conexión con el servidor.');
                e.target.selectedIndex = originalSelectedIndex; 
            }
        });
    });

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
                const response = await fetch('../api/?accion=adminUploadProof', {
                    method: 'POST',
                    body: formData 
                });
                const result = await response.json();
                if (result.success) {
                    alert('Comprobante de envío subido con éxito. La página se recargará.');
                    window.location.reload(); 
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error de conexión con el servidor.');
            }
        });
    }

    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const resetFiltersBtn = document.getElementById('resetFilters');
    const tableBody = document.getElementById('transactionsTableBody');
    const tableRows = tableBody ? tableBody.querySelectorAll('tr') : [];

    function filterTransactions() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;

        tableRows.forEach(row => {
            const userText = row.querySelector('.search-user')?.textContent.toLowerCase() || '';
            const beneficiaryText = row.querySelector('.search-beneficiary')?.textContent.toLowerCase() || '';
            const statusText = row.querySelector('.filter-status select')?.value || '';

            const matchesSearch = userText.includes(searchTerm) || beneficiaryText.includes(searchTerm);
            const matchesStatus = statusValue === '' || statusText === statusValue;
            
            if (matchesSearch && matchesStatus) {
                row.style.display = ''; 
            } else {
                row.style.display = 'none'; 
            }
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
});