const authContainer = document.querySelector('.auth-container');
if (authContainer) {
    const tabLinks = authContainer.querySelectorAll('.tab-link');
    const authForms = authContainer.querySelectorAll('.auth-form');
    const loginForm = document.getElementById('form-login');
    const registerForm = document.getElementById('form-registro');
    const docTypeSelect = document.getElementById('reg-doc-type');
    const docNumberInput = document.getElementById('reg-doc-number');

    // --- LÓGICA PARA CAMBIAR ENTRE PESTAÑAS "INGRESAR" Y "REGISTRARSE" ---
    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            tabLinks.forEach(item => item.classList.remove('active'));
            authForms.forEach(form => form.classList.remove('active'));
            link.classList.add('active');
            document.getElementById(link.dataset.target).classList.add('active');
        });
    });
    
    // --- LÓGICA PARA EL FORMATEO INTELIGENTE DEL RUT ---
    const handleRutInput = () => {
        let rutBody = docNumberInput.value.replace(/[^0-9]/g, '');

        if (rutBody.length > 9) {
            rutBody = rutBody.slice(0, 9);
        }

        if (rutBody.length >= 7) {
            const dv = calculateDv(rutBody);
            docNumberInput.value = formatRut(rutBody + dv);
        } else {
            docNumberInput.value = rutBody;
        }
    };
    
    // Activar/desactivar la lógica del RUT según el tipo de documento seleccionado
    docTypeSelect.addEventListener('change', () => {
        docNumberInput.value = '';
        if (docTypeSelect.value === 'RUT') {
            docNumberInput.setAttribute('maxlength', '12');
            docNumberInput.addEventListener('input', handleRutInput);
        } else {
            docNumberInput.removeAttribute('maxlength');
            docNumberInput.removeEventListener('input', handleRutInput);
        }
    });

    // --- MANEJO DEL ENVÍO DEL FORMULARIO DE LOGIN ---
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
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
            if (result.success) {
                window.location.href = result.redirect;
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            alert('No se pudo conectar con el servidor.');
        }
    });

    // --- MANEJO DEL ENVÍO DEL FORMULARIO DE REGISTRO ---
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // VALIDACIÓN DE RUT ANTES DE ENVIAR
        if (docTypeSelect.value === 'RUT') {
            const rutCompleto = docNumberInput.value.replace(/[^0-9kK]/g, '').toUpperCase();
            if (rutCompleto.length < 2) {
                alert('El RUT ingresado es demasiado corto.');
                return; // Detiene el envío
            }
            const body = rutCompleto.slice(0, -1);
            const dvIngresado = rutCompleto.slice(-1);
            const dvCalculado = calculateDv(body);

            if (dvIngresado !== dvCalculado) {
                alert('El RUT ingresado no es válido. Por favor, revísalo.');
                return;
            }
        }
        
        const formData = new FormData(registerForm);
        
        try {
            const response = await fetch('api/?accion=registerUser', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                alert('¡Registro exitoso! Ahora puedes iniciar sesión.');
                window.location.reload();
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            alert('No se pudo conectar con el servidor.');
        }
    });
}