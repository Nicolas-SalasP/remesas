document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector('#cuentas-table tbody');
    const modalElement = document.getElementById('cuentaModal');
    const modalInstance = new bootstrap.Modal(modalElement);
    const form = document.getElementById('cuenta-form');
    const btnNueva = document.getElementById('btn-nueva-cuenta');

    let cuentasData = [];

    const loadCuentas = async () => {
        try {
            const response = await fetch('../api/?accion=getCuentasAdmin');
            const result = await response.json();
            if (result.success) {
                cuentasData = result.cuentas;
                renderTable();
            }
        } catch (error) {
            console.error(error);
        }
    };

    const renderTable = () => {
        tableBody.innerHTML = '';
        cuentasData.forEach(c => {
            const row = `
                <tr>
                    <td><strong>${c.NombrePais}</strong></td>
                    <td><span class="badge bg-info text-dark">${c.FormaPagoNombre}</span></td>
                    <td>
                        <strong>${c.Banco}</strong><br>
                        <small>${c.Titular}</small>
                    </td>
                    <td>
                        ${c.TipoCuenta}<br>
                        ${c.NumeroCuenta}
                    </td>
                    <td><div style="width: 30px; height: 30px; background-color: ${c.ColorHex}; border-radius: 4px; border: 1px solid #ccc;"></div></td>
                    <td>
                        <span class="badge ${c.Activo == 1 ? 'bg-success' : 'bg-secondary'}">
                            ${c.Activo == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary btn-edit" data-id="${c.CuentaAdminID}"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger btn-delete" data-id="${c.CuentaAdminID}"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            `;
            tableBody.innerHTML += row;
        });
    };

    btnNueva.addEventListener('click', () => {
        form.reset();
        document.getElementById('cuenta-id').value = '';
        document.getElementById('cuentaModalLabel').textContent = 'Nueva Cuenta Bancaria';
        document.getElementById('color-hex').value = '#000000';
        // Pre-seleccionar Chile por defecto si existe
        const paisSelect = document.getElementById('pais-id');
        if (paisSelect.options.length > 0) paisSelect.selectedIndex = 0;
    });

    tableBody.addEventListener('click', async (e) => {
        const btnEdit = e.target.closest('.btn-edit');
        const btnDelete = e.target.closest('.btn-delete');

        if (btnEdit) {
            const id = btnEdit.dataset.id;
            const cuenta = cuentasData.find(c => c.CuentaAdminID == id);
            if (cuenta) {
                document.getElementById('cuenta-id').value = cuenta.CuentaAdminID;
                document.getElementById('pais-id').value = cuenta.PaisID; // Cargar país
                document.getElementById('forma-pago-id').value = cuenta.FormaPagoID;
                document.getElementById('banco').value = cuenta.Banco;
                document.getElementById('titular').value = cuenta.Titular;
                document.getElementById('tipo-cuenta').value = cuenta.TipoCuenta;
                document.getElementById('numero-cuenta').value = cuenta.NumeroCuenta;
                document.getElementById('rut').value = cuenta.RUT;
                document.getElementById('email').value = cuenta.Email;
                document.getElementById('instrucciones').value = cuenta.Instrucciones;
                document.getElementById('color-hex').value = cuenta.ColorHex;
                document.getElementById('activo').value = cuenta.Activo;

                document.getElementById('cuentaModalLabel').textContent = 'Editar Cuenta';
                modalInstance.show();
            }
        }

        if (btnDelete) {
            if (confirm('¿Estás seguro de eliminar esta cuenta?')) {
                const id = btnDelete.dataset.id;
                await fetch('../api/?accion=deleteCuentaAdmin', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                loadCuentas();
            }
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        await fetch('../api/?accion=saveCuentaAdmin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        modalInstance.hide();
        loadCuentas();
    });

    loadCuentas();
});