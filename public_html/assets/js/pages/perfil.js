document.addEventListener('DOMContentLoaded', () => {
    
    // --- SECCIÓN 1: EDICIÓN DE PERFIL DE USUARIO ---
    
    const profileForm = document.getElementById('profile-form');
    const profileLoading = document.getElementById('profile-loading');
    const profileImgPreview = document.getElementById('profile-img-preview');
    const profileFotoInput = document.getElementById('profile-foto-input');
    const profileSaveBtn = document.getElementById('profile-save-btn');
    
    const nombreCompletoEl = document.getElementById('profile-nombre');
    const emailEl = document.getElementById('profile-email');
    const documentoEl = document.getElementById('profile-documento');
    const telefonoEl = document.getElementById('profile-telefono');
    const profilePhoneCodeEl = document.getElementById('profile-phone-code');
    const estadoBadge = document.getElementById('profile-estado');
    const verificationLinkContainer = document.getElementById('verification-link-container');
    const defaultPhoto = `${baseUrlJs}/assets/img/SoloLogoNegroSinFondo.png`;
    
    // --- SECCIÓN 2: GESTIÓN DE BENEFICIARIOS ---
    
    const beneficiariosLoading = document.getElementById('beneficiarios-loading');
    const beneficiaryListContainer = document.getElementById('beneficiary-list-container');
    const addAccountModalElement = document.getElementById('addAccountModal');
    const addAccountModal = new bootstrap.Modal(addAccountModalElement);
    const addBeneficiaryForm = document.getElementById('add-beneficiary-form');
    const addAccountModalLabel = document.getElementById('addAccountModalLabel');
    const benefCuentaIdInput = document.getElementById('benef-cuenta-id');
    
    const benefPaisIdInput = document.getElementById('benef-pais-id');
    const benefPhoneCodeSelect = document.getElementById('benef-phone-code');
    const benefPhoneNumberInput = document.getElementById('benef-phone-number');
    const benefTipoSelect = document.getElementById('benef-tipo');
    const benefDocTypeSelect = document.getElementById('benef-doc-type');

    // --- LÓGICA COMPARTIDA ---
    
    const countryPhoneCodes = [
        { code: '+54', name: 'Argentina', flag: '🇦🇷', paisId: 7 },
        { code: '+591', name: 'Bolivia', flag: '🇧🇴', paisId: 8 },
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
    
    let currentBeneficiaries = [];
    let isSubmittingBeneficiary = false;

    const loadPhoneCodes = (selectElement) => {
        if (!selectElement) return;
        
        countryPhoneCodes.sort((a, b) => a.name.localeCompare(b.name));
        selectElement.innerHTML = '<option value="">Código...</option>';
        countryPhoneCodes.forEach(country => {
            if (country.code) {
                selectElement.innerHTML += `<option value="${country.code}">${country.flag} ${country.code}</option>`;
            }
        });
    };
    
    const setPhoneCodeByPais = (paisId, selectElement) => {
        if (!selectElement) return;
        const paisDestinoData = countryPhoneCodes.find(c => c.paisId && c.paisId.toString() === paisId.toString());
        if (paisDestinoData && paisDestinoData.code) {
            selectElement.value = paisDestinoData.code;
        } else {
            selectElement.value = "";
        }
    };

    // --- LÓGICA DE PERFIL DE USUARIO ---

    const loadUserProfile = async () => {
        try {
            const response = await fetch('../api/?accion=getUserProfile');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const result = await response.json();
            if (result.success && result.profile) {
                const profile = result.profile;
                
                nombreCompletoEl.value = `${profile.PrimerNombre || ''} ${profile.SegundoNombre || ''} ${profile.PrimerApellido || ''} ${profile.SegundoApellido || ''}`.replace(/\s+/g, ' ').trim() || 'No disponible';
                emailEl.value = profile.Email || 'No disponible';
                documentoEl.value = `${profile.TipoDocumento || 'No especificado'} ${profile.NumeroDocumento || 'No disponible'}`;

                loadPhoneCodes(profilePhoneCodeEl);
                
                const fullPhone = profile.Telefono || '';
                const foundCode = countryPhoneCodes.find(c => c.code && fullPhone.startsWith(c.code));
                
                if (foundCode) {
                    profilePhoneCodeEl.value = foundCode.code;
                    telefonoEl.value = fullPhone.substring(foundCode.code.length);
                } else {
                    profilePhoneCodeEl.value = '';
                    telefonoEl.value = fullPhone;
                }

                const estadoVerificacion = profile.VerificacionEstado || 'Desconocido';
                estadoBadge.textContent = estadoVerificacion;
                estadoBadge.className = 'badge'; 

                if(estadoVerificacion === 'Verificado') estadoBadge.classList.add('bg-success');
                else if(estadoVerificacion === 'Pendiente') estadoBadge.classList.add('bg-warning', 'text-dark');
                else if(estadoVerificacion === 'Rechazado') estadoBadge.classList.add('bg-danger');
                else estadoBadge.classList.add('bg-secondary'); 

                if(verificationLinkContainer && (estadoVerificacion === 'No Verificado' || estadoVerificacion === 'Rechazado')) {
                    verificationLinkContainer.innerHTML = `<p class="mt-3">Tu cuenta necesita verificación para realizar transacciones.</p><a href="verificar.php" class="btn btn-primary">Verificar mi cuenta ahora</a>`;
                } else if (verificationLinkContainer && estadoVerificacion === 'Pendiente') {
                     verificationLinkContainer.innerHTML = `<p class="mt-3 text-info">Tus documentos están siendo revisados.</p>`;
                } else if (verificationLinkContainer && estadoVerificacion === 'Verificado') {
                     verificationLinkContainer.innerHTML = `<p class="mt-3 text-success">¡Tu cuenta está verificada!</p>`;
                }
                
                const photoUrl = profile.FotoPerfilURL ? `${baseUrlJs}/admin/view_secure_file.php?file=${encodeURIComponent(profile.FotoPerfilURL)}` : defaultPhoto;
                profileImgPreview.src = photoUrl;
                
                profileLoading.classList.add('d-none');
                profileForm.classList.remove('d-none');
            } else {
                 throw new Error(result.error || 'Respuesta no exitosa');
            }
        } catch (error) {
            console.error('Error al cargar el perfil:', error);
            profileLoading.innerHTML = '<p class="text-danger">Error al cargar el perfil.</p>';
        }
    };

    profileFotoInput?.addEventListener('change', () => {
        const file = profileFotoInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                profileImgPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    profileForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        profileSaveBtn.disabled = true;
        profileSaveBtn.textContent = 'Guardando...';

        try {
            const formData = new FormData(profileForm);
            
            const phoneCode = profilePhoneCodeEl.value;
            const phoneNumber = telefonoEl.value.replace(/\s+/g, '');
            
            if (phoneCode && phoneNumber) {
                formData.set('telefono', phoneCode + phoneNumber);
            } else {
                formData.set('telefono', phoneNumber);
            }
            formData.delete('profilePhoneCode');
            
            const response = await fetch('../api/?accion=updateUserProfile', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (!result.success) throw new Error(result.error);
            
            window.showInfoModal('Éxito', 'Tu perfil ha sido actualizado.', true);
            
            if (result.newPhotoUrl) {
                const newUrl = `${baseUrlJs}/admin/view_secure_file.php?file=${encodeURIComponent(result.newPhotoUrl)}&t=${new Date().getTime()}`;
                profileImgPreview.src = newUrl;
                document.getElementById('navbar-user-photo').src = newUrl;
            }

        } catch (error) {
            window.showInfoModal('Error', error.message, false);
        } finally {
            profileSaveBtn.disabled = false;
            profileSaveBtn.textContent = 'Guardar Cambios';
        }
    });

    
    // --- LÓGICA DE BENEFICIARIOS ---
    
    const loadBeneficiaries = async () => {
        try {
            beneficiariosLoading.classList.remove('d-none');
            beneficiaryListContainer.innerHTML = '';
            
            const response = await fetch(`../api/?accion=getCuentas`);
            if (!response.ok) throw new Error('Error al cargar beneficiarios');
            
            const cuentas = await response.json();
            currentBeneficiaries = cuentas;
            
            if (cuentas && cuentas.length > 0) {
                cuentas.forEach(cuenta => {
                    beneficiaryListContainer.innerHTML += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">${cuenta.Alias || 'Sin Alias'} (${cuenta.NombrePais})</h6>
                                <small class="text-muted">${cuenta.NombreBanco} - ...${cuenta.NumeroCuenta.slice(-4)}</small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-primary edit-benef-btn" data-id="${cuenta.CuentaID}" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger del-benef-btn" data-id="${cuenta.CuentaID}" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>`;
                });
            } else {
                beneficiaryListContainer.innerHTML = '<p class="text-muted text-center p-3">No tienes beneficiarios guardados.</p>';
            }
        } catch (error) {
            console.error('Error loadBeneficiaries:', error);
            beneficiaryListContainer.innerHTML = '<p class="text-danger">Error al cargar los beneficiarios.</p>';
        } finally {
            beneficiariosLoading.classList.add('d-none');
        }
    };

    const loadDropdownData = async (endpoint, selectElement, key = 'nombre') => {
         selectElement.disabled = true;
         selectElement.innerHTML = '<option value="">Cargando...</option>';
        try {
            const response = await fetch(`../api/?accion=${endpoint}`);
            if (!response.ok) throw new Error(`Error al cargar ${endpoint}`);
            const data = await response.json();
            selectElement.innerHTML = '<option value="">Selecciona...</option>';
            data.forEach(item => {
                const value = (typeof item === 'object') ? item[key] : item;
                let dataAttributes = '';
                if (typeof item === 'object') {
                    Object.keys(item).forEach(k => {
                        dataAttributes += ` data-${k}="${item[k]}"`;
                    });
                }
                selectElement.innerHTML += `<option value="${value}" ${dataAttributes}>${value}</option>`;
            });
             selectElement.disabled = false;
        } catch (error) {
            console.error(`Error en ${endpoint}:`, error);
            selectElement.innerHTML = '<option value="">Error al cargar</option>';
        }
    };
    
    benefPaisIdInput?.addEventListener('change', () => {
        setPhoneCodeByPais(benefPaisIdInput.value, benefPhoneCodeSelect);
    });

    beneficiaryListContainer.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-benef-btn');
        const deleteBtn = e.target.closest('.del-benef-btn');

        if (editBtn) {
            const cuentaId = editBtn.dataset.id;
            try {
                const response = await fetch(`../api/?accion=getBeneficiaryDetails&id=${cuentaId}`);
                const result = await response.json();
                if (!result.success) throw new Error(result.error);
                
                const details = result.details;
                addBeneficiaryForm.reset();
                
                addAccountModalLabel.textContent = 'Editar Beneficiario';
                benefCuentaIdInput.value = details.CuentaID;
                
                benefPaisIdInput.value = details.PaisID;
                benefPaisIdInput.disabled = true;
                
                setPhoneCodeByPais(details.PaisID, benefPhoneCodeSelect);
                
                document.getElementById('benef-alias').value = details.Alias;
                benefTipoSelect.value = details.TipoBeneficiarioNombre;
                document.getElementById('benef-bank').value = details.NombreBanco;
                document.getElementById('benef-firstname').value = details.TitularPrimerNombre;
                document.getElementById('benef-secondname').value = details.TitularSegundoNombre || '';
                document.getElementById('benef-lastname').value = details.TitularPrimerApellido;
                document.getElementById('benef-secondlastname').value = details.TitularSegundoApellido || '';
                benefDocTypeSelect.value = details.TitularTipoDocumentoNombre;
                document.getElementById('benef-doc-number').value = details.TitularNumeroDocumento;
                document.getElementById('benef-account-num').value = details.NumeroCuenta;
                
                const fullPhone = details.NumeroTelefono || '';
                const selectedCode = benefPhoneCodeSelect.value;
                
                if (selectedCode && fullPhone.startsWith(selectedCode)) {
                    document.getElementById('benef-phone-number').value = fullPhone.substring(selectedCode.length);
                } else {
                    document.getElementById('benef-phone-number').value = fullPhone;
                }
                
                addAccountModal.show();
                
            } catch (error) {
                window.showInfoModal('Error', `No se pudieron cargar los detalles: ${error.message}`, false);
            }
        }

        if (deleteBtn) {
            const cuentaId = deleteBtn.dataset.id;
            const cuenta = currentBeneficiaries.find(c => c.CuentaID == cuentaId);
            const alias = cuenta ? cuenta.Alias : `ID #${cuentaId}`;

            const confirmed = await window.showConfirmModal(
                'Confirmar Eliminación',
                `¿Estás seguro de que quieres eliminar al beneficiario "${alias}"? Esta acción no se puede deshacer.`
            );
            
            if (confirmed) {
                try {
                    const response = await fetch('../api/?accion=deleteBeneficiary', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: cuentaId })
                    });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.error);
                    
                    window.showInfoModal('Éxito', 'Beneficiario eliminado.', true);
                    loadBeneficiaries();
                    
                } catch (error) {
                    window.showInfoModal('Error', `No se pudo eliminar: ${error.message}`, false);
                }
            }
        }
    });

    document.getElementById('add-account-btn').addEventListener('click', () => {
        addBeneficiaryForm.reset();
        addAccountModalLabel.textContent = 'Registrar Nuevo Beneficiario';
        benefCuentaIdInput.value = '';
        benefPaisIdInput.disabled = false;
        benefPaisIdInput.value = '';
        benefPhoneCodeSelect.value = '';
    });

    addBeneficiaryForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (isSubmittingBeneficiary) return;
        isSubmittingBeneficiary = true;
        
        const submitModalButton = addAccountModalElement.querySelector('.modal-footer button[type="submit"]');
        submitModalButton.disabled = true;
        submitModalButton.textContent = 'Guardando...';

        const formData = new FormData(addBeneficiaryForm);
        const data = Object.fromEntries(formData.entries());
        
        const cuentaId = data.cuentaId;
        const isEditing = !!cuentaId;
        
        const action = isEditing ? 'updateBeneficiary' : 'addCuenta';
        
        if (isEditing) {
            data.paisID = benefPaisIdInput.value;
        }

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
            const response = await fetch(`../api/?accion=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Respuesta inesperada del servidor');

            if (result.success) {
                addAccountModal.hide();
                addBeneficiaryForm.reset();
                window.showInfoModal('Éxito', `Beneficiario ${isEditing ? 'actualizado' : 'guardado'} con éxito.`, true);
                loadBeneficiaries();
            } else {
                throw new Error(result.error || 'No se pudo guardar la cuenta');
            }
        } catch (error) {
            console.error('Error al guardar beneficiario:', error);
             window.showInfoModal('Error al Guardar', error.message, false);
        } finally {
            submitModalButton.disabled = false;
            submitModalButton.textContent = 'Guardar Beneficiario';
            benefPaisIdInput.disabled = false;
            isSubmittingBeneficiary = false;
        }
    });

    telefonoEl?.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\s+/g, '');
    });
    
    benefPhoneNumberInput?.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\s+/g, '');
    });

    Promise.all([
        loadDropdownData('getPaises&rol=Destino', benefPaisIdInput, 'NombrePais'),
        loadDropdownData('getBeneficiaryTypes', benefTipoSelect),
        loadDropdownData('getDocumentTypes', benefDocTypeSelect)
    ]).then(() => {
        loadPhoneCodes(benefPhoneCodeSelect);
        loadUserProfile();
        loadBeneficiaries();
    }).catch(error => {
        console.error("Error crítico al cargar datos iniciales de los dropdowns:", error);
        profileLoading.innerHTML = '<p class="text-danger">Error al cargar datos. Recarga la página.</p>';
        beneficiariosLoading.innerHTML = '<p class="text-danger">Error al cargar datos. Recarga la página.</p>';
    });
});