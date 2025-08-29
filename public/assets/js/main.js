// Este evento se asegura de que el código se ejecute solo cuando la página ha cargado completamente.
document.addEventListener('DOMContentLoaded', function() {

    // Solo ejecutar este código si estamos en la página de login
    const authContainer = document.querySelector('.auth-container');
    if (authContainer) {
        const tabLinks = authContainer.querySelectorAll('.tab-link');
        const authForms = authContainer.querySelectorAll('.auth-form');

        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                // Quitar 'active' de todos
                tabLinks.forEach(item => item.classList.remove('active'));
                authForms.forEach(form => form.classList.remove('active'));

                // Añadir 'active' al clickeado
                link.classList.add('active');
                const targetForm = document.getElementById(link.dataset.target);
                if (targetForm) {
                    targetForm.classList.add('active');
                }
            });
        });
    }


    // ... (El código de las pestañas que ya teníamos va aquí arriba) ...

// --- LÓGICA PARA ENVIAR FORMULARIOS DE LOGIN Y REGISTRO ---

// Solo ejecutar este código si estamos en la página de login
if (authContainer) {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    // Manejar envío del formulario de LOGIN
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // Evitar que la página se recargue

        const formData = {
            email: document.getElementById('login-email').value,
            password: document.getElementById('login-password').value
        };

        try {
            const response = await fetch('../api/index.php?accion=loginUser', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();

            if (result.success) {
                // Si el login es exitoso, redirigir al dashboard
                window.location.href = result.redirect;
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            alert('No se pudo conectar con el servidor.');
        }
    });

    // Manejar envío del formulario de REGISTRO
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = {
            primerNombre: document.getElementById('reg-firstname').value,
            segundoNombre: document.getElementById('reg-secondname').value,
            primerApellido: document.getElementById('reg-lastname1').value,
            segundoApellido: document.getElementById('reg-lastname2').value,
            email: document.getElementById('reg-email').value,
            tipoDocumento: document.getElementById('reg-doc-type').value,
            numeroDocumento: document.getElementById('reg-doc-number').value,
            password: document.getElementById('reg-password').value,
        };
        
        try {
            const response = await fetch('../api/index.php?accion=registerUser', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();

            if (result.success) {
                alert('¡Registro exitoso! Ahora puedes iniciar sesión.');
                // Opcional: cambiar automáticamente a la pestaña de login
                window.location.reload(); // Recargar para limpiar el form
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            alert('No se pudo conectar con el servidor.');
        }
    });
}
});