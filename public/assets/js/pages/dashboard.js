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
            selectElement.innerHTML += `<option value="${pais.PaisID}" data-currency="${pais.CodigoMoneda}">${pais.NombrePais}</option>`;
        });
    } catch (error) {
        selectElement.innerHTML = '<option value="">Error al cargar</option>';
    }
};

const loadBeneficiaries = async (userID, paisID) => {
    beneficiaryListDiv.innerHTML = '<p>Cargando...</p>';
    try {
        const response = await fetch(`../api/?accion=getCuentas&paisID=${paisID}`);
        const cuentas = await response.json();
        beneficiaryListDiv.innerHTML = ''; // Limpiar
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
    if (!origenID || !destinoID || monto <= 0) return;
    try {
        const response = await fetch(`../api/?accion=getTasa&origenID=${origenID}&destinoID=${destinoID}`);
        const tasaInfo = await response.json();
        if (tasaInfo && tasaInfo.ValorTasa) {
            tasaDisplayInput.value = tasaInfo.ValorTasa;
            selectedTasaIdInput.value = tasaInfo.TasaID;
            montoDestinoInput.value = (monto * parseFloat(tasaInfo.ValorTasa)).toFixed(2);
        } else {
             tasaDisplayInput.value = 'Ruta no disponible';
             montoDestinoInput.value = '';
        }
    } catch (e) { /* Manejar error */ }
};

const createSummary = async () => {
    const origenOption = paisOrigenSelect.options[paisOrigenSelect.selectedIndex];
    const destinoOption = paisDestinoSelect.options[paisDestinoSelect.selectedIndex];
    const monedaOrigen = origenOption.getAttribute('data-currency');
    const monedaDestino = destinoOption.getAttribute('data-currency');

    summaryContainer.innerHTML = `
        <div class="list-group">
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <span>País Origen:</span>
                <strong>${origenOption.text}</strong>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <span>País Destino:</span>
                <strong>${destinoOption.text}</strong>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <span>Monto a Enviar:</span>
                <strong>${montoOrigenInput.value} ${monedaOrigen}</strong>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <span>Monto a Recibir (Aprox.):</span>
                <strong>${montoDestinoInput.value} ${monedaDestino}</strong>
            </div>
        </div>`;
};


const submitTransaction = async () => {
    const formaDePagoSelect = document.getElementById('forma-pago');

    const transactionData = {
        userID: LOGGED_IN_USER_ID,
        cuentaID: selectedCuentaIdInput.value,
        tasaID: selectedTasaIdInput.value,
        montoOrigen: montoOrigenInput.value,
        monedaOrigen: 'CLP',
        montoDestino: montoDestinoInput.value,
        formaDePago: formaDePagoSelect.value 
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
            alert('Error: ' + result.error);
        }
    } catch (e) { alert('No se pudo conectar con el servidor.'); }
};

const loadFormasDePago = async () => {
    const formaDePagoSelect = document.getElementById('forma-pago');
    try {
        const response = await fetch(`../api/?accion=getFormasDePago`);
        const opciones = await response.json();
        
        formaDePagoSelect.innerHTML = '<option value="">Selecciona una opción...</option>';
        opciones.forEach(opcion => {
            formaDePagoSelect.innerHTML += `<option value="${opcion}">${opcion}</option>`;
        });
    } catch (error) {
        formaDePagoSelect.innerHTML = '<option value="">Error al cargar</option>';
    }
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
        if (document.querySelector('input[name="beneficiary-radio"]:checked')) {
            selectedCuentaIdInput.value = document.querySelector('input[name="beneficiary-radio"]:checked').value;
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

paisOrigenSelect.addEventListener('change', () => loadPaises('Destino', paisDestinoSelect));
montoOrigenInput.addEventListener('input', calculateRate);
if(submitBtn) submitBtn.addEventListener('click', submitTransaction);

const addAccountBtn = document.getElementById('add-account-btn');
const addAccountModalElement = document.getElementById('addAccountModal');
const addAccountModal = new bootstrap.Modal(addAccountModalElement);
const addBeneficiaryForm = document.getElementById('add-beneficiary-form');
const benefPaisIdInput = document.getElementById('benef-pais-id');

addAccountBtn.addEventListener('click', () => {
    const paisDestinoID = paisDestinoSelect.value;
    if (!paisDestinoID) {
        alert('Por favor, selecciona un país de destino antes de añadir una cuenta.');
        return;
    }
    benefPaisIdInput.value = paisDestinoID;
    addAccountModal.show();
});

addBeneficiaryForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(addBeneficiaryForm);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('../api/?accion=addCuenta', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            alert('¡Cuenta de beneficiario guardada con éxito!');
            addAccountModal.hide();
            addBeneficiaryForm.reset();
            loadBeneficiaries(LOGGED_IN_USER_ID, paisDestinoSelect.value);
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        console.error('Error al guardar la cuenta:', error);
        alert('No se pudo conectar con el servidor para guardar la cuenta.');
    }
});

const numberFormatter = new Intl.NumberFormat('es-ES', {
    style: 'decimal',
    maximumFractionDigits: 2,
    minimumFractionDigits: 2
});

montoOrigenInput.addEventListener('blur', () => {
    console.log('--- Evento BLUR Activado ---');
    console.log('1. Valor inicial en el campo:', `"${montoOrigenInput.value}"`);

    let valorLimpio = montoOrigenInput.value.replace(/\./g, ''); 
    valorLimpio = valorLimpio.replace(',', '.'); 

    const valorNumerico = parseFloat(valorLimpio);

    if (isNaN(valorNumerico) || valorNumerico <= 0) {
        montoOrigenInput.value = '';
        montoDestinoInput.value = '';
        return;
    }
    const valorFormateado = numberFormatter.format(valorNumerico);
    montoOrigenInput.value = valorFormateado;
});

montoOrigenInput.addEventListener('focus', () => {
    if (!montoOrigenInput.value) return;
    let valorLimpio = montoOrigenInput.value.replace(/\./g, '');
    valorLimpio = valorLimpio.replace(',', '.');
    montoOrigenInput.value = valorLimpio;
});


loadPaises('Origen', paisOrigenSelect);
loadFormasDePago();
updateView();