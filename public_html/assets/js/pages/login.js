document.addEventListener('DOMContentLoaded', () => {
    const authContainer = document.querySelector('.auth-container');
    if (authContainer) {
        const tabLinks = authContainer.querySelectorAll('.tab-link');
        const authForms = authContainer.querySelectorAll('.auth-form');
        const loginForm = document.getElementById('form-login');
        const registerForm = document.getElementById('form-registro');
        const docTypeSelect = document.getElementById('reg-doc-type');
        const docNumberInput = document.getElementById('reg-doc-number');
        const phoneCodeSelect = document.getElementById('reg-phone-code'); // Nuevo

        const countryPhoneCodes = [
            { code: '+54', name: 'Argentina', flag: '🇦🇷' },
            { code: '+591', name: 'Bolivia', flag: '🇧🇴' },
            { code: '+55', name: 'Brasil', flag: '🇧🇷' },
            { code: '+56', name: 'Chile', flag: '🇨🇱' },
            { code: '+57', name: 'Colombia', flag: '🇨🇴' },
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
            { code: '+51', name: 'Perú', flag: '🇵🇪' },
            { code: '+1', name: 'Puerto Rico', flag: '🇵🇷' },
            { code: '+1', name: 'Rep. Dominicana', flag: '🇩🇴' },
            { code: '+598', name: 'Uruguay', flag: '🇺🇾' },
            { code: '+58', name: 'Venezuela', flag: '🇻🇪' },
            { code: '+1', name: 'EE.UU.', flag: '🇺🇸' }
        ];
        countryPhoneCodes.sort((a, b) => a.name.localeCompare(b.name));

        const loadPhoneCodes = () => {
            if (!phoneCodeSelect) return;
            phoneCodeSelect.innerHTML = '<option value="">Código...</option>';
            countryPhoneCodes.forEach(country => {
                phoneCodeSelect.innerHTML += `<option value="${country.code}">${country.flag} ${country.code}</option>`;
            });
            phoneCodeSelect.value = '+56';
        };

        const loadDocumentTypesForRegistration = async () => {
            if (!docTypeSelect) return;
            docTypeSelect.disabled = true;
            docTypeSelect.innerHTML = '<option value="">Cargando...</option>';
            try {
                const response = await fetch('../api/?accion=getDocumentTypes');
                if (!response.ok) throw new Error('Error al cargar tipos de documento');
                const tipos = await response.json();
                docTypeSelect.innerHTML = '<option value="">Selecciona...</option>';
                tipos.forEach(tipo => {
                    docTypeSelect.innerHTML += `<option value="${tipo.nombre}">${tipo.nombre}</option>`;
                });
                docTypeSelect.disabled = false;
            } catch (error) {
                console.error('Error loadDocumentTypesForRegistration:', error);
                docTypeSelect.innerHTML = '<option value="">Error al cargar</option>';
                docTypeSelect.disabled = false;
            }
        };

        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                tabLinks.forEach(item => item.classList.remove('active'));
                authForms.forEach(form => form.classList.remove('active'));
                link.classList.add('active');
                const targetForm = document.getElementById(link.dataset.target);
                if(targetForm) targetForm.classList.add('active');
            });
        });

        const handleRutInput = (e) => {
            e.target.value = e.target.value.replace(/[^0-9kK]/g, '').toUpperCase();
        };

        const handleRutBlur = (e) => {
            if (typeof formatRut === 'function') {
                const clean = cleanRut(e.target.value);
                if (clean.length > 1) {
                    e.target.value = formatRut(clean);
                }
            }
        };

        const validateRutOnSubmit = () => {
            if (docTypeSelect.value !== 'RUT') {
                return true;
            }
            const rutLimpio = cleanRut(docNumberInput.value);
            
            if (typeof validateRut !== 'function' || !validateRut(rutLimpio)) {
                 if (window.showInfoModal) window.showInfoModal('Error de Validación', 'El RUT ingresado no es válido. Por favor, revísalo.', false);
                 else alert('El RUT ingresado no es válido. Por favor, revísalo.');
                return false;
            }
            return true;
        };

        docTypeSelect?.addEventListener('change', () => {
            if (!docNumberInput) return;
            docNumberInput.value = '';
            docNumberInput.removeEventListener('input', handleRutInput);
            docNumberInput.removeEventListener('blur', handleRutBlur);
            docNumberInput.placeholder = 'Sin puntos ni guiones';

            switch (docTypeSelect.value) {
                case 'RUT': 
                    docNumberInput.setAttribute('maxlength', '12');
                    docNumberInput.addEventListener('input', handleRutInput);
                    docNumberInput.addEventListener('blur', handleRutBlur);
                    break;
                case 'Cédula de Identidad': 
                    docNumberInput.setAttribute('maxlength', '10');
                    break;
                case 'RIF': 
                    docNumberInput.setAttribute('maxlength', '10');
                    break;
                case 'Pasaporte':
                    docNumberInput.setAttribute('maxlength', '20');
                    break;
                default:
                    docNumberInput.setAttribute('maxlength', '30');
            }
        });

        loginForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitButton = loginForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Ingresando...';
            const formData = {
                email: document.getElementById('login-email').value,
                password: document.getElementById('login-password').value
            };
            try {
                const response = await fetch('api/?accion=loginUser', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();
                if (response.ok && result.success) {
                     if (result.twofa_required) {
                         window.location.href = result.redirect; 
                     } else {
                        window.location.href = result.redirect; 
                     }
                } else {
                    const errorMessage = result.error || 'Correo electrónico o contraseña no válidos. Inténtalo nuevamente.';
                    if (window.showInfoModal) showInfoModal('Error de Inicio de Sesión', errorMessage, false);
                    else alert(errorMessage);
                    submitButton.disabled = false;
                    submitButton.textContent = 'Ingresar';
                }
            } catch (error) {
                console.error('Error de red o parseo en login:', error);
                if (window.showInfoModal) showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor. Verifica tu conexión e inténtalo de nuevo.', false);
                else alert('No se pudo conectar con el servidor.');
                submitButton.disabled = false;
                submitButton.textContent = 'Ingresar';
            }
        });

        registerForm?.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (docTypeSelect.value === 'RUT' && !validateRutOnSubmit()) {
                return; 
            }
            
            const submitButton = registerForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Registrando...';
            
            let originalDocValue = docNumberInput.value;
            if (docTypeSelect.value === 'RUT' && typeof cleanRut === 'function') {
                docNumberInput.value = cleanRut(originalDocValue);
            }

            const formData = new FormData(registerForm);
            
            const phoneCode = formData.get('phoneCode');
            const phoneNumber = formData.get('phoneNumber');
            if (phoneCode && phoneNumber) {
                formData.append('telefono', phoneCode + phoneNumber);
            }
            formData.delete('phoneCode');
            formData.delete('phoneNumber');

            try {
                const response = await fetch('api/?accion=registerUser', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    if (window.showInfoModal) {
                        window.showInfoModal(
                            '¡Registro Exitoso!',
                            'Tu cuenta ha sido creada. Serás redirigido en breve.',
                            true,
                            () => { window.location.href = result.redirect; }
                        );
                    } else {
                        alert('Registro exitoso. Redirigiendo...');
                        window.location.href = result.redirect;
                    }
                } else {
                     const errorMsg = result.error || 'No se pudo crear la cuenta.';
                     if (window.showInfoModal) window.showInfoModal('Error de Registro', errorMsg, false);
                     else alert('Error: ' + errorMsg);
                    submitButton.disabled = false;
                    submitButton.textContent = 'Crear Cuenta';
                }
            } catch (error) {
                console.error('Error de conexión en registro:', error);
                 if (window.showInfoModal) window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
                 else alert('No se pudo conectar con el servidor.');
                submitButton.disabled = false;
                submitButton.textContent = 'Crear Cuenta';
            } finally {
                 if (docTypeSelect.value === 'RUT') {
                    docNumberInput.value = originalDocValue;
                }
            }
        });

        loadDocumentTypesForRegistration();
        loadPhoneCodes(); 
    }
});