document.addEventListener('DOMContentLoaded', () => {
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
    const addAccountBtn = document.getElementById('add-account-btn');
    const addAccountModalElement = document.getElementById('addAccountModal');
    const addBeneficiaryForm = document.getElementById('add-beneficiary-form');
    const benefPaisIdInput = document.getElementById('benef-pais-id');
    const phoneCodeSelect = document.getElementById('benef-phone-code');
    const phoneNumberInput = document.getElementById('benef-phone-number');
    const benefTipoSelect = document.getElementById('benef-tipo');
    const benefDocTypeSelect = document.getElementById('benef-doc-type');
    const benefDocNumberInput = document.getElementById('benef-doc-number');

    let currentStep = 1;
    let activeInput = 'origen';
    let currentRate = 0;
    let isCalculating = false;
    const LOGGED_IN_USER_ID = userIdInput ? userIdInput.value : null;

    const numberFormatter = new Intl.NumberFormat('es-ES', { style: 'decimal', maximumFractionDigits: 2, minimumFractionDigits: 2 });
    const cleanNumber = (value) => {
        if (typeof value !== 'string' || !value) return '';
        return value.replace(/\./g, '').replace(',', '.');
    };

    const updateView = () => {
        formSteps.forEach((step, index) => {
            step.classList.toggle('active', (index + 1) === currentStep);
        });
        prevBtn.classList.toggle('d-none', currentStep === 1 || currentStep === 5);
        nextBtn.classList.toggle('d-none', currentStep >= 4);
        if (submitBtn) submitBtn.classList.toggle('d-none', currentStep !== 4);
    };

    const loadPaises = async (rol, selectElement) => {
         if (!selectElement) return;
         selectElement.disabled = true;
         selectElement.innerHTML = '<option value="">Cargando...</option>';
        try {
            const response = await fetch(`../api/?accion=getPaises&rol=${rol}`);
            if (!response.ok) throw new Error('Error al cargar países');
            const paises = await response.json();
            selectElement.innerHTML = '<option value="">Selecciona un país</option>';
            paises.forEach(pais => {
                selectElement.innerHTML += `<option value="${pais.PaisID}" data-currency="${pais.CodigoMoneda}">${pais.NombrePais}</option>`;
            });
             selectElement.disabled = false;
        } catch (error) {
            console.error('Error loadPaises:', error);
            selectElement.innerHTML = '<option value="">Error al cargar</option>';
             selectElement.disabled = false;
        }
    };

    const loadBeneficiaries = async (paisID) => {
        if (!beneficiaryListDiv || !paisID) return;
        beneficiaryListDiv.innerHTML = '<p>Cargando beneficiarios...</p>';
        try {
            const response = await fetch(`../api/?accion=getCuentas&paisID=${paisID}`);
            if (!response.ok) throw new Error('Error al cargar beneficiarios');
            const cuentas = await response.json();
            beneficiaryListDiv.innerHTML = '';
            if (cuentas && cuentas.length > 0) {
                cuentas.forEach(cuenta => {
                    beneficiaryListDiv.innerHTML += `
                        <label class="list-group-item d-flex align-items-center">
                            <input type="radio" class="form-check-input me-2" name="beneficiary-radio" value="${cuenta.CuentaID}">
                            ${cuenta.Alias || 'Beneficiario sin alias'}
                        </label>`;
                });
            } else {
                beneficiaryListDiv.innerHTML = '<p class="text-muted mb-0">No tienes beneficiarios guardados para este destino.</p>';
            }
        } catch (error) {
            console.error('Error loadBeneficiaries:', error);
            beneficiaryListDiv.innerHTML = '<p class="text-danger">Error al cargar los beneficiarios.</p>';
        }
    };

    const loadFormasDePago = async () => {
         if (!formaDePagoSelect) return;
         formaDePagoSelect.disabled = true;
         formaDePagoSelect.innerHTML = '<option value="">Cargando...</option>';
        try {
            const response = await fetch(`../api/?accion=getFormasDePago`);
             if (!response.ok) throw new Error('Error al cargar formas de pago');
            const opciones = await response.json();
            formaDePagoSelect.innerHTML = '<option value="">Selecciona una opción...</option>';
            opciones.forEach(opcion => {
                formaDePagoSelect.innerHTML += `<option value="${opcion}">${opcion}</option>`;
            });
             formaDePagoSelect.disabled = false;
        } catch (error) {
            console.error('Error loadFormasDePago:', error);
            formaDePagoSelect.innerHTML = '<option value="">Error al cargar</option>';
             formaDePagoSelect.disabled = false;
        }
    };

    const loadTiposBeneficiario = async () => {
        if (!benefTipoSelect) return;
         benefTipoSelect.disabled = true;
         benefTipoSelect.innerHTML = '<option value="">Cargando...</option>';
        try {
            const response = await fetch(`../api/?accion=getBeneficiaryTypes`);
             if (!response.ok) throw new Error('Error al cargar tipos de beneficiario');
            const tipos = await response.json();
            benefTipoSelect.innerHTML = '<option value="">Selecciona...</option>';
            tipos.forEach(tipo => {
                benefTipoSelect.innerHTML += `<option value="${tipo}">${tipo}</option>`;
            });
             benefTipoSelect.disabled = false;
        } catch (error) {
            console.error('Error loadTiposBeneficiario:', error);
            benefTipoSelect.innerHTML = '<option value="">Error al cargar</option>';
             benefTipoSelect.disabled = false;
        }
    };

     const loadTiposDocumento = async () => {
         if (!benefDocTypeSelect) return;
         benefDocTypeSelect.disabled = true;
         benefDocTypeSelect.innerHTML = '<option value="">Cargando...</option>';
         try {
             const response = await fetch(`../api/?accion=getDocumentTypes`);
             if (!response.ok) throw new Error('Error al cargar tipos de documento');
             const tipos = await response.json();
             benefDocTypeSelect.innerHTML = '<option value="">Selecciona...</option>';
             tipos.forEach(tipo => {
                 benefDocTypeSelect.innerHTML += `<option value="${tipo.nombre}">${tipo.nombre}</option>`;
             });
              benefDocTypeSelect.disabled = false;
         } catch (error) {
             console.error('Error loadTiposDocumento:', error);
             benefDocTypeSelect.innerHTML = '<option value="">Error al cargar</option>';
              benefDocTypeSelect.disabled = false;
         }
     };

    const fetchRate = async () => {
        const origenID = paisOrigenSelect.value;
        const destinoID = paisDestinoSelect.value;
        tasaDisplayInput.value = 'Calculando...';
        selectedTasaIdInput.value = '';
        currentRate = 0;

        if (!origenID || !destinoID) {
            tasaDisplayInput.value = 'Selecciona origen y destino';
             updateCalculation(); return;
        }
        if (origenID === destinoID) {
             tasaDisplayInput.value = 'Origen y destino deben ser diferentes';
             updateCalculation(); return;
        }

        try {
            const response = await fetch(`../api/?accion=getTasa&origenID=${origenID}&destinoID=${destinoID}`);
            if (!response.ok) {
                if (response.status === 404) {
                     tasaDisplayInput.value = 'Tasa no disponible para esta ruta.';
                } else {
                     tasaDisplayInput.value = 'Error al obtener tasa (Servidor).';
                     console.error("Error del servidor al obtener tasa:", response.statusText);
                }
            } else {
                const tasaInfo = await response.json();
                if (tasaInfo && typeof tasaInfo.ValorTasa !== 'undefined' && tasaInfo.TasaID) {
                    currentRate = parseFloat(tasaInfo.ValorTasa);
                    const selectedDestinoOption = paisDestinoSelect.options[paisDestinoSelect.selectedIndex];
                    const monedaDestino = selectedDestinoOption ? selectedDestinoOption.getAttribute('data-currency') : 'N/A';
                    tasaDisplayInput.value = `1 CLP ≈ ${currentRate.toFixed(4)} ${monedaDestino}`;
                    selectedTasaIdInput.value = tasaInfo.TasaID;
                } else {
                    tasaDisplayInput.value = 'Tasa no disponible (Datos inválidos).';
                }
            }
            updateCalculation();
        } catch (e) {
            console.error("Error en fetchRate:", e);
            tasaDisplayInput.value = 'Error de red al obtener tasa.';
            updateCalculation();
        }
    };

    const updateCalculation = () => {
        if (isCalculating) return;
        if (currentRate <= 0) {
             if (activeInput === 'origen') montoDestinoInput.value = ''; else montoOrigenInput.value = '';
             return;
        }
        isCalculating = true;
        let sourceInput = activeInput === 'origen' ? montoOrigenInput : montoDestinoInput;
        let targetInput = activeInput === 'origen' ? montoDestinoInput : montoOrigenInput;
        const sourceValue = parseFloat(cleanNumber(sourceInput.value)) || 0;
        let targetValue = 0;
        if (sourceValue > 0) {
            if (activeInput === 'origen') {
                targetValue = sourceValue * currentRate;
            } else {
                targetValue = sourceValue / currentRate;
            }
             targetInput.value = numberFormatter.format(targetValue);
        } else {
            targetInput.value = '';
        }
        setTimeout(() => { isCalculating = false; }, 50);
    };

    const createSummary = () => {
        const origenText = paisOrigenSelect.options[paisOrigenSelect.selectedIndex]?.text || 'N/A';
        const destinoOption = paisDestinoSelect.options[paisDestinoSelect.selectedIndex];
        const destinoText = destinoOption?.text || 'N/A';
        const monedaDestinoText = destinoOption?.getAttribute('data-currency') || '';
        const formaPagoText = formaDePagoSelect.value || 'No seleccionada';
        const beneficiarioSeleccionado = document.querySelector('input[name="beneficiary-radio"]:checked');
        const beneficiarioAlias = beneficiarioSeleccionado ? beneficiarioSeleccionado.closest('label').textContent.trim() : 'No seleccionado';

        summaryContainer.innerHTML = `
            <h4 class="mb-3">Confirma tu Envío:</h4>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>País Origen:</span> <strong>${origenText}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>País Destino:</span> <strong>${destinoText}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Beneficiario:</span> <strong>${beneficiarioAlias}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Forma de Pago (Tuya):</span> <strong>${formaPagoText}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Monto a Enviar:</span> <strong class="text-danger">${montoOrigenInput.value || '0,00'} CLP</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Monto a Recibir (Aprox.):</span> <strong class="text-success">${montoDestinoInput.value || '0,00'} ${monedaDestinoText}</strong></li>
                 <li class="list-group-item d-flex justify-content-between"><span>Tasa Aplicada:</span> <strong>${tasaDisplayInput.value || 'N/A'}</strong></li>
            </ul>`;
    };

    const submitTransaction = async () => {
         if (!submitBtn) return;
         submitBtn.disabled = true;
         submitBtn.textContent = 'Procesando...';
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
            if (response.ok && result.success) {
                transaccionIdFinal.textContent = result.transaccionID;
                currentStep++;
                updateView();
            } else {
                 const errorMsg = result.error || 'No se pudo crear la transacción.';
                 if (window.showInfoModal) {
                    window.showInfoModal('Error al Crear Orden', errorMsg, false);
                 } else {
                     alert('Error: ' + errorMsg);
                 }
                 submitBtn.disabled = false;
                 submitBtn.textContent = 'Confirmar y Generar Orden';
            }
        } catch (e) {
            console.error('Error en submitTransaction:', e);
             if (window.showInfoModal) {
                 window.showInfoModal('Error de Red', 'No se pudo conectar con el servidor para crear la orden.', false);
             } else {
                alert('No se pudo conectar con el servidor.');
             }
             submitBtn.disabled = false;
             submitBtn.textContent = 'Confirmar y Generar Orden';
        }
    };

    nextBtn?.addEventListener('click', async () => {
        let isValid = false;
        let alertMessage = '';
        if (currentStep === 1) {
            if (paisOrigenSelect.value && paisDestinoSelect.value && paisOrigenSelect.value !== paisDestinoSelect.value) {
                await loadBeneficiaries(paisDestinoSelect.value);
                isValid = true;
            } else if (paisOrigenSelect.value === paisDestinoSelect.value) {
                alertMessage = 'El país de origen y destino no pueden ser iguales.';
            } else {
                alertMessage = 'Debes seleccionar un país de origen y destino.';
            }
        } else if (currentStep === 2) {
            const beneficiarioSeleccionado = document.querySelector('input[name="beneficiary-radio"]:checked');
            if (beneficiarioSeleccionado) {
                selectedCuentaIdInput.value = beneficiarioSeleccionado.value;
                isValid = true;
            } else {
                alertMessage = 'Debes seleccionar una cuenta de beneficiario o registrar una nueva.';
            }
        } else if (currentStep === 3) {
            const monto = parseFloat(cleanNumber(montoOrigenInput.value)) || 0;
            if (monto > 0 && formaDePagoSelect.value && currentRate > 0) {
                createSummary();
                isValid = true;
            } else if (currentRate <= 0) {
                 alertMessage = 'La tasa de cambio no está disponible para esta ruta. No se puede continuar.';
            } else if (monto <= 0) {
                alertMessage = 'Debes ingresar un monto a enviar válido.';
            } else {
                 alertMessage = 'Debes seleccionar una forma de pago.';
            }
        }
        if (isValid && currentStep < 4) {
            currentStep++;
            updateView();
        } else if (alertMessage) {
            if (window.showInfoModal) window.showInfoModal('Información Requerida', alertMessage, false);
            else alert(alertMessage);
        }
    });

    prevBtn?.addEventListener('click', () => { if (currentStep > 1 && currentStep < 5) { currentStep--; updateView(); } });

    paisOrigenSelect?.addEventListener('change', () => {
        paisDestinoSelect.value = '';
        currencyLabelDestino.textContent = 'N/A';
        tasaDisplayInput.value = '';
        currentRate = 0;
        beneficiaryListDiv.innerHTML = '';
         loadPaises('Destino', paisDestinoSelect);
         updateCalculation();
    });

    paisDestinoSelect?.addEventListener('change', () => {
        const selectedOption = paisDestinoSelect.options[paisDestinoSelect.selectedIndex];
        currencyLabelDestino.textContent = selectedOption ? selectedOption.getAttribute('data-currency') || 'N/A' : 'N/A';
        fetchRate();
         loadBeneficiaries(paisDestinoSelect.value);
    });

    swapCurrencyBtn?.addEventListener('click', () => {
        activeInput = (activeInput === 'origen') ? 'destino' : 'origen';
        const tempValue = montoOrigenInput.value;
        montoOrigenInput.value = montoDestinoInput.value;
        montoDestinoInput.value = tempValue;
        montoOrigenInput.blur();
        montoDestinoInput.blur();
        montoOrigenInput.readOnly = activeInput !== 'origen';
        montoDestinoInput.readOnly = activeInput !== 'destino';
        montoOrigenInput.classList.toggle('bg-light', activeInput !== 'origen');
        montoDestinoInput.classList.toggle('bg-light', activeInput !== 'destino');
        updateCalculation();
    });

    montoOrigenInput?.addEventListener('input', () => { if (activeInput === 'origen') updateCalculation(); });
    montoDestinoInput?.addEventListener('input', () => { if (activeInput === 'destino') updateCalculation(); });
    montoOrigenInput?.addEventListener('focus', () => { activeInput = 'origen'; montoOrigenInput.readOnly=false; montoDestinoInput.readOnly=true; montoOrigenInput.classList.remove('bg-light'); montoDestinoInput.classList.add('bg-light'); });
    montoDestinoInput?.addEventListener('focus', () => { activeInput = 'destino'; montoDestinoInput.readOnly=false; montoOrigenInput.readOnly=true; montoDestinoInput.classList.remove('bg-light'); montoOrigenInput.classList.add('bg-light'); });

    [montoOrigenInput, montoDestinoInput].forEach(input => {
        if (!input) return;
        input.addEventListener('blur', (e) => {
            const valorNumerico = parseFloat(cleanNumber(e.target.value));
            if (!isNaN(valorNumerico) && valorNumerico > 0) {
                e.target.value = numberFormatter.format(valorNumerico);
            } else {
                 e.target.value = '';
                 if (e.target.id === `monto-${activeInput}`) {
                     updateCalculation();
                 }
            }
        });
    });

    submitBtn?.addEventListener('click', submitTransaction);

    const validationRules = { /* ... */ };
    let addAccountModalInstance = null;
    if (addAccountModalElement) {
         addAccountModalInstance = new bootstrap.Modal(addAccountModalElement);
        addAccountBtn?.addEventListener('click', () => {
            const paisDestinoID = paisDestinoSelect.value;
            if (!paisDestinoID) {
                if(window.showInfoModal) window.showInfoModal('Acción Requerida', 'Por favor, selecciona un país de destino antes de añadir una cuenta.', false);
                else alert('Por favor, selecciona un país de destino antes de añadir una cuenta.');
                return;
            }
            benefPaisIdInput.value = paisDestinoID;
            const rules = validationRules[paisDestinoID];
            if (rules && rules.code) {
                phoneCodeSelect.innerHTML = `<option value="${rules.code}">${rules.code}</option>`;
                phoneNumberInput.maxLength = rules.phoneLength;
                phoneNumberInput.placeholder = `${rules.phoneLength} dígitos`;
            } else {
                 phoneCodeSelect.innerHTML = `<option value="">Código?</option>`;
                phoneNumberInput.maxLength = 15;
                phoneNumberInput.placeholder = 'Número completo';
            }
            benefDocNumberInput.maxLength = 20;
            benefDocNumberInput.pattern = null;
            benefDocNumberInput.placeholder = '';
            benefDocTypeSelect.value = '';
            addBeneficiaryForm.reset();
            addAccountModalInstance.show();
        });

        benefDocTypeSelect?.addEventListener('change', () => {
            const paisDestinoID = benefPaisIdInput.value;
            const docType = benefDocTypeSelect.value;
            const rules = validationRules[paisDestinoID]?.doc[docType];
            if (rules) {
                benefDocNumberInput.maxLength = rules.length;
                benefDocNumberInput.placeholder = `${rules.length} dígitos`;
                benefDocNumberInput.pattern = rules.numeric ? "[0-9]*" : null;
            } else {
                benefDocNumberInput.maxLength = 20;
                benefDocNumberInput.placeholder = '';
                benefDocNumberInput.pattern = null;
            }
             benefDocNumberInput.value = '';
        });

        addBeneficiaryForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitModalButton = addBeneficiaryForm.closest('.modal-content').querySelector('button[type="submit"]');
            submitModalButton.disabled = true;
            submitModalButton.textContent = 'Guardando...';
            const formData = new FormData(addBeneficiaryForm);
            const data = Object.fromEntries(formData.entries());
            const phoneCode = data.phoneCode || '';
            data.numeroTelefono = phoneCode + data.phoneNumber;
            delete data.phoneCode;
            delete data.phoneNumber;
            data.tipoBeneficiario = benefTipoSelect.value;
            data.tipoDocumento = benefDocTypeSelect.value;
            try {
                const response = await fetch('../api/?accion=addCuenta', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    addAccountModalInstance.hide();
                    addBeneficiaryForm.reset();
                    if (window.showInfoModal) {
                       window.showInfoModal('Éxito', '¡Cuenta de beneficiario guardada con éxito!', true);
                    } else {
                       alert('¡Cuenta de beneficiario guardada con éxito!');
                    }
                    loadBeneficiaries(paisDestinoSelect.value);
                } else {
                    const errorMsg = result.error || 'No se pudo guardar la cuenta.';
                     if (window.showInfoModal) {
                        window.showInfoModal('Error al Guardar', errorMsg, false);
                    } else {
                        alert('Error: ' + errorMsg);
                    }
                }
            } catch (error) {
                console.error('Error al guardar la cuenta:', error);
                 if (window.showInfoModal) {
                    window.showInfoModal('Error de Red', 'No se pudo conectar con el servidor para guardar la cuenta.', false);
                 } else {
                    alert('No se pudo conectar con el servidor.');
                 }
            } finally {
                submitModalButton.disabled = false;
                submitModalButton.textContent = 'Guardar Cuenta';
            }
        });
    }

    if (LOGGED_IN_USER_ID) {
        loadPaises('Origen', paisOrigenSelect);
        loadFormasDePago();
        loadTiposBeneficiario();
         loadTiposDocumento();
        updateView();
    } else {
         console.error("No hay sesión de usuario activa.");
    }
});