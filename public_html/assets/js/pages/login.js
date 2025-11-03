document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const loginFeedback = document.getElementById('login-feedback');
    const registerFeedback = document.getElementById('register-feedback');
    const docTypeSelect = document.getElementById('register-doc-type');
    const docNumInput = document.getElementById('register-doc-num');

    const registerPhoneCode = document.getElementById('register-phone-code');
    const registerTelefono = document.getElementById('register-telefono');
    const registerRoleSelect = document.getElementById('register-role');

    const countryPhoneCodes = [
        { code: '+54', name: 'Argentina' },
        { code: '+591', name: 'Bolivia' },
        { code: '+55', name: 'Brasil' },
        { code: '+56', name: 'Chile' },
        { code: '+57', name: 'Colombia' },
        { code: '+506', name: 'Costa Rica' },
        { code: '+53', name: 'Cuba' },
        { code: '+593', name: 'Ecuador' },
        { code: '+503', name: 'El Salvador' },
        { code: '+502', name: 'Guatemala' },
        { code: '+504', name: 'Honduras' },
        { code: '+52', name: 'M\u00e9xico' }, 
        { code: '+505', name: 'Nicaragua' },
        { code: '+507', name: 'Panam\u00e1' },
        { code: '+595', name: 'Paraguay' },
        { code: '+51', name: 'Per\u00fa' },
        { code: '+1', name: 'Puerto Rico' },
        { code: '+1', name: 'Rep. Dominicana' },
        { code: '+598', name: 'Uruguay' },
        { code: '+58', name: 'Venezuela' },
        { code: '+1', name: 'EE.UU.' }
    ];

    const loadPhoneCodes = (selectElement) => {
        if (!selectElement) return;

        countryPhoneCodes.sort((a, b) => a.name.localeCompare(b.name));
        selectElement.innerHTML = '<option value="">C\u00f3digo...</option>';
        countryPhoneCodes.forEach(country => {
            if (country.code) {
                selectElement.innerHTML += `<option value="${country.code}">${country.code} (${country.name})</option>`;
            }
        });
    };

    registerTelefono?.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
    });


    const loadDocumentTypes = async () => {
        try {
            const response = await fetch('api/?accion=getDocumentTypes');
            if (!response.ok) throw new Error('Error al cargar tipos de documento');
            const tipos = await response.json();

            docTypeSelect.innerHTML = '<option value="">Selecciona...</option>';
            tipos.forEach(tipo => {
                docTypeSelect.innerHTML += `<option value="${tipo.nombre}">${tipo.nombre}</option>`;
            });
        } catch (error) {
            console.error(error);
            docTypeSelect.innerHTML = '<option value="">Error al cargar</option>';
        }
    };

    // Cargar los roles de "Tipo de Cuenta"
    const loadAssignableRoles = async () => {
        if (!registerRoleSelect) return;
        try {
            const response = await fetch('api/?accion=getAssignableRoles');
            if (!response.ok) throw new Error('Error al cargar tipos de cuenta');
            const roles = await response.json();

            registerRoleSelect.innerHTML = '<option value="">Selecciona...</option>';
            roles.forEach(rol => {
                registerRoleSelect.innerHTML += `<option value="${rol.NombreRol}">${rol.NombreRol}</option>`;
            });
        } catch (error) {
            console.error(error);
            registerRoleSelect.innerHTML = '<option value="">Error al cargar</option>';
        }
    };

    // Validar RUT Chileno
    docTypeSelect?.addEventListener('change', () => {
        docNumInput.removeAttribute('pattern');
        docNumInput.classList.remove('is-invalid', 'is-valid');
        docNumInput.value = '';

        if (docTypeSelect.value === 'RUT') {
            docNumInput.dataset.validateRut = 'true';
            docNumInput.maxLength = 12;
            docNumInput.placeholder = '12.345.678-9';
        } else {
            docNumInput.dataset.validateRut = 'false';
            docNumInput.maxLength = 20;
            docNumInput.placeholder = 'Nro. Documento';
        }
    });

    docNumInput?.addEventListener('input', (e) => {
        if (docNumInput.dataset.validateRut === 'true') {
            if (typeof validateRut === 'function' && typeof formatRut === 'function') {
                const rut = e.target.value;
                if (validateRut(rut)) {
                    e.target.value = formatRut(rut);
                    docNumInput.classList.add('is-valid');
                    docNumInput.classList.remove('is-invalid');
                }
            }
        }
    });

    docNumInput?.addEventListener('blur', (e) => {
        if (docNumInput.dataset.validateRut === 'true') {
            if (typeof validateRut === 'function') {
                const rut = e.target.value;
                if (rut && !validateRut(rut)) {
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

        const submitButton = registerForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Registrando...';

        const password = registerForm.password.value;
        const passwordRepeat = registerForm.passwordRepeat.value;

        if (password !== passwordRepeat) {
            registerFeedback.textContent = 'Las contraseñas no coinciden.';
            submitButton.disabled = false;
            submitButton.textContent = 'Registrar Cuenta';
            return;
        }

        if (password.length < 6) {
            registerFeedback.textContent = 'La contraseña debe tener al menos 6 caracteres.';
            submitButton.disabled = false;
            submitButton.textContent = 'Registrar Cuenta';
            return;
        }

        if (docNumInput.dataset.validateRut === 'true' && (typeof validateRut !== 'function' || !validateRut(docNumInput.value))) {
            registerFeedback.textContent = 'El RUT ingresado no es válido.';
            submitButton.disabled = false;
            submitButton.textContent = 'Registrar Cuenta';
            return;
        }

        const formData = new FormData(registerForm);

        const phoneInput = formData.get('phoneNumber');
        formData.set('phoneNumber', phoneInput.replace(/\D/g, ''));

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
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Registrar Cuenta';
        }
    });

    // Cargas iniciales
    if (docTypeSelect) {
        loadDocumentTypes();
    }

    if (registerPhoneCode) {
        loadPhoneCodes(registerPhoneCode);
    }
    if (registerRoleSelect) {
        loadAssignableRoles();
    }
});