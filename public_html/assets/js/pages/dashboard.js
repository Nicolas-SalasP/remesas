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

    const countryPhoneCodes = [
        { code: '+54', name: 'Argentina', flag: '🇦🇷', paisId: 7 },
        { code: '+591', name: 'Bolivia', flag: '🇧🇴' },
        { code: '+55', name: 'Brasil', flag: '🇧🇷' },
        { code: '+56', name: 'Chile', flag: '🇨🇱', paisId: 1 },
        { code: '+57', name: 'Colombia', flag: '🇨🇴', paisId: 2 },
        { code: '+506', name: 'Costa Rica', flag: '🇨🇷' },
        { code: '+53', name: 'Cuba', flag: '🇨🇺' },
        { code: '+593', name: 'Ecuador', flag: '🇪🇨' },
        { code: '+503', name: 'El Salvador', flag: '🇸🇻' },
        { code: '+502', name: 'Guatemala', flag: '🇬🇹' },
        { code: '+504', name: 'Honduras', flag: '🇭🇳' },
        { code: '+52', name: 'México', flag: '🇲🇽' },
        { code: '+505', name: 'Nicaragua', flag: '🇳🇮' },
        { code: '+507', name: 'Panamá', flag: '🇵🇦' },
        { code: '+595', name: 'Paraguay', flag: '🇵🇾' },
        { code: '+51', name: 'Perú', flag: '🇵🇪', paisId: 4 },
        { code: '+1', name: 'Puerto Rico', flag: '🇵🇷' },
        { code: '+1', name: 'Rep. Dominicana', flag: '🇩🇴' },
        { code: '+598', name: 'Uruguay', flag: '🇺🇾' },
        { code: '+58', name: 'Venezuela', flag: '🇻🇪', paisId: 3 },
        { code: '+1', name: 'EE.UU.', flag: '🇺🇸', paisId: 5 }
    ];
    countryPhoneCodes.sort((a, b) => a.name.localeCompare(b.name));

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
            throw error; // Relanzar el error para que el 'catch' en nextBtn lo maneje
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
             updateCalculation();
             return;
        }
        if (origenID === destinoID) {
             tasaDisplayInput.value = 'Origen y destino deben ser diferentes';
             updateCalculation();
             return;
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
                 currentRate = 0;
                 selectedTasaIdInput.value = '';
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
                    currentRate = 0;
                    selectedTasaIdInput.value = '';
                }
            }
            updateCalculation();
        } catch (e) {
            console.error("Error en fetchRate:", e);
            tasaDisplayInput.value = 'Error de red al obtener tasa.';
            currentRate = 0;
            selectedTasaIdInput.value = '';
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

        const selectedDestinoOption = paisDestinoSelect.options[paisDestinoSelect.selectedIndex];
        const monedaDestinoValue = selectedDestinoOption ? selectedDestinoOption.getAttribute('data-currency') : null;

        if (!monedaDestinoValue) {
            if (window.showInfoModal) {
                window.showInfoModal('Error de Datos', 'No se pudo determinar la moneda de destino. Recarga la página e intenta de nuevo.', false);
            } else {
                alert('Error: No se pudo determinar la moneda de destino.');
            }
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirmar y Generar Orden';
            return;
        }

        const transactionData = {
            userID: LOGGED_IN_USER_ID,
            cuentaID: selectedCuentaIdInput.value,
            tasaID: selectedTasaIdInput.value,
            montoOrigen: cleanNumber(montoOrigenInput.value),
            monedaOrigen: 'CLP',
            montoDestino: cleanNumber(montoDestinoInput.value),
            monedaDestino: monedaDestinoValue,
            formaDePago: formaDePagoSelect.value
        };

        if (isNaN(parseFloat(transactionData.montoOrigen)) || parseFloat(transactionData.montoOrigen) <= 0 ||
            isNaN(parseFloat(transactionData.montoDestino)) || parseFloat(transactionData.montoDestino) <= 0 ||
            !transactionData.cuentaID || !transactionData.tasaID || !transactionData.formaDePago) {
             if (window.showInfoModal) {
                 window.showInfoModal('Datos Incompletos', 'Asegúrate de haber completado todos los pasos anteriores correctamente (selección de beneficiario, montos, forma de pago y tasa válida).', false);
             } else {
                 alert('Error: Datos incompletos o inválidos para crear la transacción.');
             }
             submitBtn.disabled = false;
             submitBtn.textContent = 'Confirmar y Generar Orden';
             return;
        }

        try {
            const response = await fetch('../api/?accion=createTransaccion', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' , 'Accept': 'application/json' },
                body: JSON.stringify(transactionData)
            });

             let result;
             const contentType = response.headers.get("content-type");

             if (contentType && contentType.indexOf("application/json") !== -1) {
                 result = await response.json();
             } else {
                 result = { success: false, error: await response.text() };
                 console.error("Respuesta no JSON del servidor:", result.error);
             }

            if (!response.ok) {
                 const errorMsg = result?.error || `Error del servidor (${response.status}). Intenta de nuevo.`;
                 if (window.showInfoModal) {
                    window.showInfoModal('Error al Crear Orden', errorMsg, false);
                 } else {
                     alert('Error: ' + errorMsg);
                 }
                 submitBtn.disabled = false;
                 submitBtn.textContent = 'Confirmar y Generar Orden';
                 return;
            }

            if (result.success) {
                transaccionIdFinal.textContent = result.transaccionID;
                currentStep++;
                updateView();
            } else {
                 const errorMsg = result.error || 'No se pudo crear la transacción (respuesta inesperada).';
                 if (window.showInfoModal) {
                    window.showInfoModal('Error al Crear Orden', errorMsg, false);
                 } else {
                     alert('Error: ' + errorMsg);
                 }
                 submitBtn.disabled = false;
                 submitBtn.textContent = 'Confirmar y Generar Orden';
            }
        } catch (e) {
            console.error('Error en submitTransaction (catch):', e);
             if (window.showInfoModal) {
                 window.showInfoModal('Error de Red', 'No se pudo conectar con el servidor para crear la orden. Verifica tu conexión.', false);
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
                // ****** INICIO CORRECCIÓN TRY-CATCH ******
                try {
                    await loadBeneficiaries(paisDestinoSelect.value);
                    isValid = true; // Solo marcar como válido si loadBeneficiaries tuvo éxito
                } catch (error) {
                    // Si falla la carga de beneficiarios, mostrar error y no avanzar
                    alertMessage = "Error al cargar los beneficiarios para el país destino. Intenta de nuevo o contacta soporte.";
                    isValid = false;
                }
                // ****** FIN CORRECCIÓN TRY-CATCH ******
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
            if (monto > 0 && formaDePagoSelect.value && currentRate > 0 && selectedTasaIdInput.value) {
                createSummary();
                isValid = true;
            } else if (currentRate <= 0 || !selectedTasaIdInput.value) {
                 alertMessage = 'La tasa de cambio no está disponible o no se pudo cargar. No se puede continuar.';
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

    prevBtn?.addEventListener('click', () => {
        if (currentStep > 1 && currentStep < 5) {
            currentStep--;
            updateView();
        }
    });

    paisOrigenSelect?.addEventListener('change', () => {
        paisDestinoSelect.value = '';
        currencyLabelDestino.textContent = 'N/A';
        tasaDisplayInput.value = '';
        currentRate = 0;
        selectedTasaIdInput.value = '';
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

            phoneCodeSelect.innerHTML = '<option value="">Código...</option>';
            let selectedCodeFound = false;
            const paisDestinoData = countryPhoneCodes.find(c => c.paisId && c.paisId.toString() === paisDestinoID);

            countryPhoneCodes.forEach(country => {
                const isSelected = paisDestinoData && country.code === paisDestinoData.code && country.paisId === paisDestinoData.paisId;
                phoneCodeSelect.innerHTML += `<option value="${country.code}" ${isSelected ? 'selected' : ''}>${country.flag} ${country.code}</option>`;
                if (isSelected) selectedCodeFound = true;
            });
             if (!selectedCodeFound) {
                 phoneCodeSelect.value = "";
             }

            benefDocNumberInput.maxLength = 20;
            benefDocNumberInput.pattern = null;
            benefDocNumberInput.placeholder = 'Número de Documento';
            benefDocTypeSelect.value = '';
            phoneNumberInput.maxLength = 15;
            phoneNumberInput.placeholder = 'Número sin código de país';


            addBeneficiaryForm.reset();
            if (selectedCodeFound && paisDestinoData) {
                phoneCodeSelect.value = paisDestinoData.code;
            } else {
                 phoneCodeSelect.value = "";
            }

            addAccountModalInstance.show();
        });

        benefDocTypeSelect?.addEventListener('change', () => {
            benefDocNumberInput.maxLength = 20;
            benefDocNumberInput.placeholder = 'Número de Documento';
            benefDocNumberInput.pattern = null;
            benefDocNumberInput.value = '';
        });

        addBeneficiaryForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitModalButton = addBeneficiaryForm.closest('.modal-content').querySelector('button[type="submit"]');
            submitModalButton.disabled = true;
            submitModalButton.textContent = 'Guardando...';

            const formData = new FormData(addBeneficiaryForm);
            const data = Object.fromEntries(formData.entries());

            if (data.phoneCode && data.phoneNumber) {
                 data.numeroTelefono = data.phoneCode + data.phoneNumber.replace(/\s+/g, '');
            } else if (data.phoneNumber) {
                data.numeroTelefono = data.phoneNumber.replace(/\s+/g, '');
            } else {
                data.numeroTelefono = null;
            }
            delete data.phoneCode;
            delete data.phoneNumber;

            data.tipoBeneficiario = benefTipoSelect.value;
            data.tipoDocumento = benefDocTypeSelect.value;


            try {
                const response = await fetch('../api/?accion=addCuenta', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                     },
                    body: JSON.stringify(data)
                });

                let result;
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    result = await response.json();
                } else {
                    const errorText = await response.text();
                    result = { success: false, error: `Error ${response.status}: ${errorText || 'Respuesta inesperada del servidor.'}` };
                    console.error("Respuesta no JSON al agregar cuenta:", errorText);
                }

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
                    const errorMsg = result.error || 'No se pudo guardar la cuenta (error desconocido).';
                     if (window.showInfoModal) {
                        window.showInfoModal('Error al Guardar', errorMsg, false);
                    } else {
                        alert('Error: ' + errorMsg);
                    }
                }
            } catch (error) {
                console.error('Error al guardar la cuenta (catch):', error);
                 let errorMsg = 'No se pudo conectar con el servidor para guardar la cuenta.';
                 if (error instanceof SyntaxError && error.message.includes('JSON')) {
                     errorMsg = 'Error al procesar la respuesta del servidor. Intenta de nuevo o contacta soporte.';
                 }
                 if (window.showInfoModal) {
                    window.showInfoModal('Error de Red o Datos', errorMsg, false);
                 } else {
                    alert(errorMsg);
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