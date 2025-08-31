// --- LÓGICA PARA EL FORMULARIO DE TRANSACCIONES DEL DASHBOARD ---

// --- SELECTORES DE ELEMENTOS DEL DOM ---
const form = document.getElementById('remittance-form');
const formSteps = document.querySelectorAll('.form-step');
const nextBtn = document.getElementById('next-btn');
const prevBtn = document.getElementById('prev-btn');
const submitBtn = document.getElementById('submit-order-btn');

// Inputs y selects
const paisOrigenSelect = document.getElementById('pais-origen');
const paisDestinoSelect = document.getElementById('pais-destino');
const beneficiaryListDiv = document.getElementById('beneficiary-list');
const montoOrigenInput = document.getElementById('monto-origen');
const montoDestinoInput = document.getElementById('monto-destino');
const tasaDisplayInput = document.getElementById('tasa-display');

// Contenedores y displays
const summaryContainer = document.getElementById('summary-container');
const transaccionIdFinal = document.getElementById('transaccion-id-final');

// Inputs ocultos para guardar el estado
const userIdInput = document.getElementById('user-id');
const selectedTasaIdInput = document.getElementById('selected-tasa-id');
const selectedCuentaIdInput = document.getElementById('selected-cuenta-id');

// --- VARIABLES DE ESTADO ---
let currentStep = 1;
const LOGGED_IN_USER_ID = userIdInput.value;

// --- LÓGICA DE NAVEGACIÓN Y VISTAS ---
const updateView = () => {
    formSteps.forEach((step, index) => {
        step.classList.toggle('active', (index + 1) === currentStep);
    });
    prevBtn.classList.toggle('d-none', currentStep === 1 || currentStep === 5);
    nextBtn.classList.toggle('d-none', currentStep >= 4);
    if(submitBtn) submitBtn.classList.toggle('d-none', currentStep !== 4);
};

// --- FUNCIONES ASÍNCRONAS (API CALLS) ---
const loadPaises = async (rol, selectElement) => {
    try {
        const response = await fetch(`../api/?accion=getPaises&rol=${rol}`);
        const paises = await response.json();
        selectElement.innerHTML = '<option value="">Selecciona un país</option>';
        paises.forEach(pais => {
            selectElement.innerHTML += `<option value="${pais.PaisID}">${pais.NombrePais}</option>`;
        });
    } catch (error) {
        selectElement.innerHTML = '<option value="">Error al cargar</option>';
    }
};

const loadBeneficiaries = async (userID, paisID) => {
    beneficiaryListDiv.innerHTML = '<p class="text-muted">Cargando cuentas...</p>';
    try {
        const response = await fetch(`../api/?accion=getCuentas&userID=${userID}&paisID=${paisID}`);
        const cuentas = await response.json();
        beneficiaryListDiv.innerHTML = '';
        if (cuentas.length > 0) {
            cuentas.forEach(cuenta => {
                beneficiaryListDiv.innerHTML += `
                    <label class="list-group-item">
                        <input type="radio" class="form-check-input me-2" name="beneficiary-radio" value="${cuenta.CuentaID}">
                        ${cuenta.Alias}
                    </label>`;
            });
        } else {
            beneficiaryListDiv.innerHTML = '<p class="text-muted">No hay cuentas guardadas para este destino.</p>';
        }
    } catch (error) {
        beneficiaryListDiv.innerHTML = '<p class="text-danger">Error al cargar las cuentas.</p>';
    }
};

const calculateRate = async () => {
    const origenID = paisOrigenSelect.value;
    const destinoID = paisDestinoSelect.value;
    const monto = parseFloat(montoOrigenInput.value) || 0;
    if (!origenID || !destinoID || monto <= 0) {
        tasaDisplayInput.value = '';
        montoDestinoInput.value = '';
        return;
    }
    try {
        const response = await fetch(`../api/?accion=getTasa&origenID=${origenID}&destinoID=${destinoID}`);
        const tasaInfo = await response.json();
        if (tasaInfo && tasaInfo.ValorTasa) {
            const tasa = parseFloat(tasaInfo.ValorTasa);
            tasaDisplayInput.value = tasa;
            selectedTasaIdInput.value = tasaInfo.TasaID;
            montoDestinoInput.value = (monto * tasa).toFixed(2);
        } else {
             tasaDisplayInput.value = 'Ruta no disponible';
             montoDestinoInput.value = '';
        }
    } catch (e) { console.error('Error calculando la tasa:', e); }
};

const createSummary = async () => {
    // Aquí podrías hacer una llamada a la API para obtener los detalles completos del beneficiario si quisieras
    summaryContainer.innerHTML = `
        <div class="list-group">
            <div class="list-group-item d-flex justify-content-between"><span>País Origen:</span> <strong>${paisOrigenSelect.options[paisOrigenSelect.selectedIndex].text}</strong></div>
            <div class="list-group-item d-flex justify-content-between"><span>País Destino:</span> <strong>${paisDestinoSelect.options[paisDestinoSelect.selectedIndex].text}</strong></div>
            <div class="list-group-item d-flex justify-content-between"><span>Monto a Enviar:</span> <strong>${montoOrigenInput.value} CLP</strong></div>
            <div class="list-group-item d-flex justify-content-between"><span>Monto a Recibir (Aprox.):</span> <strong>${montoDestinoInput.value} VES</strong></div>
        </div>`;
};

const submitTransaction = async () => {
    const transactionData = {
        userID: LOGGED_IN_USER_ID,
        cuentaID: selectedCuentaIdInput.value,
        tasaID: selectedTasaIdInput.value,
        montoOrigen: montoOrigenInput.value,
        monedaOrigen: 'CLP', // Esto debería ser dinámico en el futuro
        montoDestino: montoDestinoInput.value,
        monedaDestino: 'VES' // Esto también
    };
    try {
        const response = await fetch('../api/?accion=createTransaccion', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(transactionData)
        });
        const result = await response.json();
        if (result.success) {
            transaccionIdFinal.textContent = result.transaccionID;
            currentStep++;
            updateView();
        } else {
            alert('Error al registrar la orden: ' + result.error);
        }
    } catch (e) { alert('No se pudo conectar con el servidor.'); }
};


// --- EVENT LISTENERS ---
nextBtn.addEventListener('click', async () => {
    let isValid = false;
    if (currentStep === 1) {
        if (paisOrigenSelect.value && paisDestinoSelect.value) {
            await loadBeneficiaries(LOGGED_IN_USER_ID, paisDestinoSelect.value);
            isValid = true;
        } else { alert('Debes seleccionar un país de origen y destino.'); }
    } else if (currentStep === 2) {
        const selectedAccount = document.querySelector('input[name="beneficiary-radio"]:checked');
        if (selectedAccount) {
            selectedCuentaIdInput.value = selectedAccount.value;
            isValid = true;
        } else { alert('Debes seleccionar una cuenta de beneficiario.'); }
    } else if (currentStep === 3) {
        if (montoOrigenInput.value && parseFloat(montoOrigenInput.value) > 0) {
            await createSummary();
            isValid = true;
        } else { alert('Debes ingresar un monto válido.'); }
    }
    
    if (isValid && currentStep < 4) {
        currentStep++;
        updateView();
    }
});

prevBtn.addEventListener('click', () => {
    if (currentStep > 1) {
        currentStep--;
        updateView();
    }
});

paisOrigenSelect.addEventListener('change', () => {
    paisDestinoSelect.innerHTML = '<option value="">Cargando...</option>';
    if (paisOrigenSelect.value) {
        loadPaises('Destino', paisDestinoSelect);
    }
});

montoOrigenInput.addEventListener('input', calculateRate);
if(submitBtn) submitBtn.addEventListener('click', submitTransaction);

// --- INICIALIZACIÓN ---
loadPaises('Origen', paisOrigenSelect);
updateView();