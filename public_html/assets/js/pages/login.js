const authContainer = document.querySelector('.auth-container');
if (authContainer) {
    const tabLinks = authContainer.querySelectorAll('.tab-link');
    const authForms = authContainer.querySelectorAll('.auth-form');
    const loginForm = document.getElementById('form-login');
    const registerForm = document.getElementById('form-registro');
    const docTypeSelect = document.getElementById('reg-doc-type');
    const docNumberInput = document.getElementById('reg-doc-number');

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

    const handleRutInput = () => {
        let rutBody = docNumberInput.value.replace(/[^0-9kK]/g, '').toUpperCase();
        let rutLimpio = docNumberInput.value.replace(/[^0-9]/g, '');

        if (rutLimpio.length > 9) {
            rutLimpio = rutLimpio.slice(0, 9);
        }
        let valorFormateado = rutBody;
        if (rutLimpio.length >= 1) {
           const bodyFormat = rutLimpio.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
           const dvInput = rutBody.length > rutLimpio.length ? rutBody.slice(-1) : '';
           valorFormateado = dvInput ? `${bodyFormat}-${dvInput}` : bodyFormat;
        }
        docNumberInput.value = valorFormateado.slice(0, 12);
    };

    const validateRutOnSubmit = () => {
        if (docTypeSelect.value !== 'RUT') {
            return true;
        }
        const rutCompleto = docNumberInput.value.replace(/[^0-9kK]/g, '').toUpperCase();
        if (rutCompleto.length < 2) {
             if (window.showInfoModal) window.showInfoModal('Error de Validación', 'El RUT ingresado es demasiado corto.', false);
             else alert('El RUT ingresado es demasiado corto.');
            return false;
        }
        const body = rutCompleto.slice(0, -1);
        const dvIngresado = rutCompleto.slice(-1);
        const dvCalculado = typeof calculateDv === 'function' ? calculateDv(body) : '?'; 

        if (dvCalculado === '?' || dvIngresado !== dvCalculado) {
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
        if (docTypeSelect.value === 'RUT') {
            docNumberInput.setAttribute('maxlength', '12');
            docNumberInput.addEventListener('input', handleRutInput);
        } else {
            docNumberInput.removeAttribute('maxlength');
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
        const formData = new FormData(registerForm);
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
        }
    });

    loadDocumentTypesForRegistration();
}