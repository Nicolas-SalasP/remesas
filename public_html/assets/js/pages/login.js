document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const loginFeedback = document.getElementById('login-feedback');
    const registerFeedback = document.getElementById('register-feedback');
    const docTypeSelect = document.getElementById('register-doc-type');
    const docNumInput = document.getElementById('register-doc-num');
    
    // --- INICIO DE LA CORRECCIÓN ---
    const registerPhoneCode = document.getElementById('register-phone-code');
    const registerTelefono = document.getElementById('register-telefono');
    const registerRoleSelect = document.getElementById('register-role'); // Nuevo

    // 1. Array de códigos de teléfono (estático para robustez)
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

    // 2. Función para cargar los códigos en el select
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
    
    // 3. Listener para eliminar espacios/letras en tiempo real
    registerTelefono?.addEventListener('input', (e) => {
        // Elimina cualquier cosa que no sea un número
        e.target.value = e.target.value.replace(/\D/g, '');
    });
    // --- FIN DE LA CORRECCIÓN ---


    // Cargar tipos de documento
    const loadDocumentTypes = async () => {
        try {
            const response = await fetch('api/?accion=getDocumentTypes');
            if (!response.ok) throw new Error('Error al cargar tipos de documento');
            const tipos = await response.json();
            
            docTypeSelect.innerHTML = '<option value="">Selecciona...</option>';
            tipos.forEach(tipo => {
                // CORRECCIÓN: Usar tipo.nombre
                docTypeSelect.innerHTML += `<option value="${tipo.nombre}">${tipo.nombre}</option>`;
            });
        } catch (error) {
            console.error(error);
            docTypeSelect.innerHTML = '<option value="">Error al cargar</option>';
        }
    };

    // --- INICIO DE LA CORRECCIÓN ---
    // Cargar los roles de "Tipo de Cuenta"
    const loadAssignableRoles = async () => {
        if (!registerRoleSelect) return;
        try {
            const response = await fetch('api/?accion=getAssignableRoles');
            if (!response.ok) throw new Error('Error al cargar tipos de cuenta');
            const roles = await response.json();
            
            registerRoleSelect.innerHTML = '<option value="">Selecciona...</option>';
            roles.forEach(rol => {
                // CORRECCIÓN: Usar rol.NombreRol
                registerRoleSelect.innerHTML += `<option value="${rol.NombreRol}">${rol.NombreRol}</option>`;
            });
        } catch (error) {
            console.error(error);
            registerRoleSelect.innerHTML = '<option value="">Error al cargar</option>';
        }
    };
    // --- FIN DE LA CORRECCIÓN ---
    
    // Validar RUT Chileno
    docTypeSelect?.addEventListener('change', () => {
        if (docTypeSelect.value === 'RUT (Chile)') {
            docNumInput.dataset.validateRut = 'true';
            docNumInput.maxLength = 12;
            docNumInput.placeholder = '12.345.678-9';
        } else {
            docNumInput.dataset.validateRut = 'false';
            docNumInput.maxLength = 20;
            docNumInput.placeholder = 'Nro. Documento';
            docNumInput.classList.remove('is-invalid', 'is-valid');
        }
    });

    docNumInput?.addEventListener('input', (e) => {
        if (docNumInput.dataset.validateRut === 'true') {
            // CORRECCIÓN: Usar 'Rut' (mayúscula) que es como se define en rut-validator.js
            if (typeof Rut !== 'undefined') {
                const rut = e.target.value;
                if (Rut.validate(rut)) {
                    e.target.value = Rut.format(rut);
                    docNumInput.classList.add('is-valid');
                    docNumInput.classList.remove('is-invalid');
                } else {
                    docNumInput.classList.add('is-invalid');
                    docNumInput.classList.remove('is-valid');
                }
            }
        }
    });


    // Formulario de Login
    loginForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        loginFeedback.textContent = '';
        const formData = new FormData(loginForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('api/?accion=loginUser', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                window.location.href = result.redirect;
            } else {
                loginFeedback.textContent = result.error || 'Error desconocido';
            }
        } catch (error) {
            loginFeedback.textContent = 'Error de conexión. Inténtalo de nuevo.';
        }
    });

    // Formulario de Registro
    registerForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        registerFeedback.textContent = '';

        const password = registerForm.password.value;
        const passwordRepeat = registerForm.passwordRepeat.value;

        if (password !== passwordRepeat) {
            registerFeedback.textContent = 'Las contraseñas no coinciden.';
            return;
        }

        if (password.length < 6) {
             registerFeedback.textContent = 'La contraseña debe tener al menos 6 caracteres.';
             return;
        }

        const formData = new FormData(registerForm);
        
        try {
            const response = await fetch('api/?accion=registerUser', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (response.ok && result.success) {
                window.location.href = result.redirect;
            } else {
                registerFeedback.textContent = result.error || 'Error al registrar la cuenta.';
            }
        } catch (error) {
            registerFeedback.textContent = 'Error de conexión. Inténtalo de nuevo.';
        }
    });

    // Cargas iniciales
    if(docTypeSelect) {
        loadDocumentTypes();
    }
    
    // --- INICIO DE LA CORRECCIÓN ---
    // Cargar códigos de teléfono al iniciar
    if(registerPhoneCode) {
        loadPhoneCodes(registerPhoneCode);
    }
    // Cargar roles de cuenta al iniciar
    if(registerRoleSelect) {
        loadAssignableRoles();
    }
    // --- FIN DE LA CORRECCIÓN ---
});