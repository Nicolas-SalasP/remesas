document.addEventListener('DOMContentLoaded', () => {
    // --- SELECTORES DE ELEMENTOS DEL DOM (Formulario Principal) ---
    const formSteps = document.querySelectorAll('.form-step');
    const nextBtn = document.getElementById('next-btn');
    const prevBtn = document.getElementById('prev-btn');
    const submitBtn = document.getElementById('submit-order-btn');
    const paisOrigenSelect = document.getElementById('pais-origen');
    const paisDestinoSelect = document.getElementById('pais-destino');
    const beneficiaryListDiv = document.getElementById('beneficiary-list');
    const montoOrigenInput = document.getElementById('monto-origen');
    const montoDestinoInput = document.getElementById('monto-destino');
    const tasaDisplayInput = document.getElementById('tasa-display');
    const currencyLabelDestino = document.getElementById('currency-label-destino');
    const swapCurrencyBtn = document.getElementById('swap-currency-btn');
    const formaDePagoSelect = document.getElementById('forma-pago');
    const summaryContainer = document.getElementById('summary-container');
    const transaccionIdFinal = document.getElementById('transaccion-id-final');
    const userIdInput = document.getElementById('user-id');
    const selectedTasaIdInput = document.getElementById('selected-tasa-id');
    const selectedCuentaIdInput = document.getElementById('selected-cuenta-id');

    // --- VARIABLES DE ESTADO ---
    let currentStep = 1;
    let activeInput = 'origen';
    let currentRate = 0;
    let isCalculating = false;
    const LOGGED_IN_USER_ID = userIdInput.value;

    // --- FORMATEADOR Y LIMPIADOR DE NÚMEROS ---
    const numberFormatter = new Intl.NumberFormat('es-ES', { style: 'decimal', maximumFractionDigits: 2, minimumFractionDigits: 2 });
    const cleanNumber = (value) => {
        if (typeof value !== 'string' || !value) return '';
        return value.replace(/\./g, '').replace(',', '.');
    };

    // --- LÓGICA DE NAVEGACIÓN Y VISTAS ---
    const updateView = () => {
        formSteps.forEach((step, index) => {
            step.classList.toggle('active', (index + 1) === currentStep);
        });
        prevBtn.classList.toggle('d-none', currentStep === 1 || currentStep === 5);
        nextBtn.classList.toggle('d-none', currentStep >= 4);
        if (submitBtn) submitBtn.classList.toggle('d-none', currentStep !== 4);
    };

    // --- FUNCIONES ASÍNCRONAS ---
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

    const loadBeneficiaries = async (paisID) => {
        beneficiaryListDiv.innerHTML = '<p>Cargando...</p>';
        try {
            const response = await fetch(`../api/?accion=getCuentas&paisID=${paisID}`);
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

    const loadFormasDePago = async () => {
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
    
    const loadTiposBeneficiario = async () => {
    const tipoBeneficiarioSelect = document.getElementById('benef-tipo');
    if (!tipoBeneficiarioSelect) return;
    try {
        const response = await fetch(`../api/?accion=getBeneficiaryTypes`);
        const tipos = await response.json();
        
        tipoBeneficiarioSelect.innerHTML = '<option value="">Selecciona...</option>';
        tipos.forEach(tipo => {
            tipoBeneficiarioSelect.innerHTML += `<option value="${tipo}">${tipo}</option>`;
        });
    } catch (error) {
        tipoBeneficiarioSelect.innerHTML = '<option value="">Error al cargar</option>';
    }
};

    const fetchRate = async () => {
        const origenID = paisOrigenSelect.value;
        const destinoID = paisDestinoSelect.value;
        if (!origenID || !destinoID) {
            currentRate = 0;
            tasaDisplayInput.value = 'Selecciona origen y destino';
            return;
        }
        try {
            const response = await fetch(`../api/?accion=getTasa&origenID=${origenID}&destinoID=${destinoID}`);
            const tasaInfo = await response.json();
            if (tasaInfo && tasaInfo.ValorTasa) {
                currentRate = parseFloat(tasaInfo.ValorTasa);
                const monedaDestino = paisDestinoSelect.options[paisDestinoSelect.selectedIndex].getAttribute('data-currency');
                tasaDisplayInput.value = `1 CLP ≈ ${currentRate.toFixed(4)} ${monedaDestino}`;
                selectedTasaIdInput.value = tasaInfo.TasaID;
            } else {
                currentRate = 0;
                tasaDisplayInput.value = 'Ruta no disponible';
            }
            updateCalculation();
        } catch (e) {
            currentRate = 0;
            tasaDisplayInput.value = 'Error al obtener tasa';
        }
    };

    const updateCalculation = () => {
        if (isCalculating || currentRate <= 0) return;
        isCalculating = true;
        
        let sourceInput = activeInput === 'origen' ? montoOrigenInput : montoDestinoInput;
        let targetInput = activeInput === 'origen' ? montoDestinoInput : montoOrigenInput;

        const sourceValue = parseFloat(cleanNumber(sourceInput.value)) || 0;

        if (activeInput === 'origen') {
            const targetValue = sourceValue * currentRate;
            targetInput.value = targetValue > 0 ? targetValue.toFixed(2).replace('.', ',') : '';
        } else {
            const targetValue = sourceValue / currentRate;
            targetInput.value = targetValue > 0 ? targetValue.toFixed(2).replace('.', ',') : '';
        }
        
        setTimeout(() => { isCalculating = false; }, 50);
    };

    const createSummary = () => {
        const origenText = paisOrigenSelect.options[paisOrigenSelect.selectedIndex].text;
        const destinoOption = paisDestinoSelect.options[paisDestinoSelect.selectedIndex];
        const destinoText = destinoOption.text;
        const monedaDestinoText = destinoOption.getAttribute('data-currency') || '';

        summaryContainer.innerHTML = `
            <div class="list-group">
                <div class="list-group-item d-flex justify-content-between"><span>País Origen:</span> <strong>${origenText}</strong></div>
                <div class="list-group-item d-flex justify-content-between"><span>País Destino:</span> <strong>${destinoText}</strong></div>
                <div class="list-group-item d-flex justify-content-between"><span>Forma de Pago:</span> <strong>${formaDePagoSelect.value}</strong></div>
                <div class="list-group-item d-flex justify-content-between"><span>Monto a Enviar:</span> <strong>${montoOrigenInput.value} CLP</strong></div>
                <div class="list-group-item d-flex justify-content-between"><span>Monto a Recibir (Aprox.):</span> <strong>${montoDestinoInput.value} ${monedaDestinoText}</strong></div>
            </div>`;
    };

    const submitTransaction = async () => {
        const transactionData = {
            userID: LOGGED_IN_USER_ID,
            cuentaID: selectedCuentaIdInput.value,
            tasaID: selectedTasaIdInput.value,
            montoOrigen: cleanNumber(montoOrigenInput.value),
            monedaOrigen: 'CLP',
            montoDestino: cleanNumber(montoDestinoInput.value),
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

    // --- EVENT LISTENERS ---
    nextBtn.addEventListener('click', async () => {
        let isValid = false;
        if (currentStep === 1) {
            if (paisOrigenSelect.value && paisDestinoSelect.value) {
                await loadBeneficiaries(paisDestinoSelect.value);
                isValid = true;
            } else { alert('Debes seleccionar un país de origen y destino.'); }
        } else if (currentStep === 2) {
            const beneficiarioSeleccionado = document.querySelector('input[name="beneficiary-radio"]:checked');
            if (beneficiarioSeleccionado) {
                selectedCuentaIdInput.value = beneficiarioSeleccionado.value;
                isValid = true;
            } else { alert('Debes seleccionar una cuenta de beneficiario.'); }
        } else if (currentStep === 3) {
            const monto = parseFloat(cleanNumber(montoOrigenInput.value));
            if (monto > 0 && formaDePagoSelect.value) {
                createSummary();
                isValid = true;
            } else { alert('Debes ingresar un monto válido y seleccionar una forma de pago.'); }
        }
        if (isValid && currentStep < 4) {
            currentStep++;
            updateView();
        }
    });

    prevBtn.addEventListener('click', () => { if (currentStep > 1) { currentStep--; updateView(); } });
    paisOrigenSelect.addEventListener('change', () => loadPaises('Destino', paisDestinoSelect));
    paisDestinoSelect.addEventListener('change', () => {
        const selectedOption = paisDestinoSelect.options[paisDestinoSelect.selectedIndex];
        currencyLabelDestino.textContent = selectedOption.getAttribute('data-currency') || 'N/A';
        fetchRate();
    });
    swapCurrencyBtn.addEventListener('click', () => {
        activeInput = (activeInput === 'origen') ? 'destino' : 'origen';
        montoOrigenInput.value = '';
        montoDestinoInput.value = '';
        montoOrigenInput.classList.toggle('bg-light', activeInput !== 'origen');
        montoDestinoInput.classList.toggle('bg-light', activeInput !== 'destino');
    });
    montoOrigenInput.addEventListener('keyup', () => { activeInput = 'origen'; updateCalculation(); });
    montoDestinoInput.addEventListener('keyup', () => { activeInput = 'destino'; updateCalculation(); });
    [montoOrigenInput, montoDestinoInput].forEach(input => {
        input.addEventListener('focus', (e) => {
            if (e.target.value) e.target.value = cleanNumber(e.target.value);
        });
        input.addEventListener('blur', (e) => {
            const valorNumerico = parseFloat(cleanNumber(e.target.value));
            if (!isNaN(valorNumerico) && valorNumerico > 0) {
                e.target.value = numberFormatter.format(valorNumerico);
            } else { e.target.value = ''; }
        });
    });
    if (submitBtn) submitBtn.addEventListener('click', submitTransaction);

    // --- LÓGICA PARA EL MODAL DE AÑADIR NUEVA CUENTA ---
    const validationRules = {
        '2': { country: 'Colombia', code: '+57', phoneLength: 10, doc: { 'Cédula': { length: 10, numeric: true } } },
        '3': { country: 'Venezuela', code: '+58', phoneLength: 10, doc: { 'Cédula': { length: 8, numeric: true } } },
        '4': { country: 'Perú', code: '+51', phoneLength: 9, doc: { 'DNI': { length: 8, numeric: true } } },
        '5': { country: 'Usa / Zelle', code: '+1', phoneLength: 10, doc: {} }
    };
    const addAccountBtn = document.getElementById('add-account-btn');
    const addAccountModalElement = document.getElementById('addAccountModal');
    if (addAccountModalElement) {
        const addAccountModal = new bootstrap.Modal(addAccountModalElement);
        const addBeneficiaryForm = document.getElementById('add-beneficiary-form');
        const benefPaisIdInput = document.getElementById('benef-pais-id');
        const phoneCodeSelect = document.getElementById('benef-phone-code');
        const phoneNumberInput = document.getElementById('benef-phone-number');
        const docTypeSelect = document.getElementById('benef-doc-type');
        const docNumberInput = document.getElementById('benef-doc-number');

        addAccountBtn.addEventListener('click', () => {
            const paisDestinoID = paisDestinoSelect.value;
            if (!paisDestinoID) {
                alert('Por favor, selecciona un país de destino antes de añadir una cuenta.');
                return;
            }
            benefPaisIdInput.value = paisDestinoID;
            const rules = validationRules[paisDestinoID];
            if (rules) {
                phoneCodeSelect.innerHTML = `<option value="${rules.code}">${rules.code}</option>`;
                phoneNumberInput.maxLength = rules.phoneLength;
                phoneNumberInput.placeholder = `${rules.phoneLength} dígitos`;
            } else {
                phoneCodeSelect.innerHTML = `<option value="">N/A</option>`;
                phoneNumberInput.maxLength = 15;
                phoneNumberInput.placeholder = '';
            }
            docNumberInput.maxLength = 20;
            docNumberInput.pattern = null;
            addAccountModal.show();
        });

        docTypeSelect.addEventListener('change', () => {
            const paisDestinoID = benefPaisIdInput.value;
            const docType = docTypeSelect.value;
            const rules = validationRules[paisDestinoID]?.doc[docType];
            if (rules) {
                docNumberInput.maxLength = rules.length;
                docNumberInput.placeholder = `${rules.length} dígitos`;
                docNumberInput.pattern = rules.numeric ? "[0-9]*" : null;
            } else {
                docNumberInput.maxLength = 20;
                docNumberInput.placeholder = '';
                docNumberInput.pattern = null;
            }
        });

        addBeneficiaryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(addBeneficiaryForm);
            const data = Object.fromEntries(formData.entries());
            data.numeroTelefono = data.phoneCode + data.phoneNumber;
            delete data.phoneCode;
            delete data.phoneNumber;
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
                    loadBeneficiaries(paisDestinoSelect.value);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error al guardar la cuenta:', error);
                alert('No se pudo conectar con el servidor.');
            }
        });
    }

    // --- INICIALIZACIÓN ---
    loadPaises('Origen', paisOrigenSelect);
    loadPaises('Destino', paisDestinoSelect);
    loadFormasDePago();
    loadTiposBeneficiario();
    updateView();
});