document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch('../api/?accion=getUserProfile');
        if (!response.ok) {
             throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();

        if (result.success && result.profile) {
            const profile = result.profile;
            const nombreCompleto = `${profile.PrimerNombre || ''} ${profile.SegundoNombre || ''} ${profile.PrimerApellido || ''} ${profile.SegundoApellido || ''}`.replace(/\s+/g, ' ').trim();
            document.getElementById('profile-nombre').textContent = nombreCompleto || 'No disponible';
            document.getElementById('profile-email').textContent = profile.Email || 'No disponible';

            const tipoDoc = profile.TipoDocumento || 'No especificado';
            const numDoc = profile.NumeroDocumento || 'No disponible';
            document.getElementById('profile-documento').textContent = `${tipoDoc} ${numDoc}`;

            const estadoBadge = document.getElementById('profile-estado');
            const estadoVerificacion = profile.VerificacionEstado || 'Desconocido';
            estadoBadge.textContent = estadoVerificacion;
            estadoBadge.className = 'badge'; 

            if(estadoVerificacion === 'Verificado') estadoBadge.classList.add('bg-success');
            else if(estadoVerificacion === 'Pendiente') estadoBadge.classList.add('bg-warning', 'text-dark');
            else if(estadoVerificacion === 'Rechazado') estadoBadge.classList.add('bg-danger');
            else estadoBadge.classList.add('bg-secondary'); 

            const verificationLinkContainer = document.getElementById('verification-link-container');
            if(verificationLinkContainer && (estadoVerificacion === 'No Verificado' || estadoVerificacion === 'Rechazado')) {
                verificationLinkContainer.innerHTML =
                `<p class="mt-3">Tu cuenta necesita verificación para realizar transacciones.</p><a href="verificar.php" class="btn btn-primary">Verificar mi cuenta ahora</a>`;
            } else if (verificationLinkContainer && estadoVerificacion === 'Pendiente') {
                 verificationLinkContainer.innerHTML = `<p class="mt-3 text-info">Tus documentos están siendo revisados.</p>`;
            } else if (verificationLinkContainer && estadoVerificacion === 'Verificado') {
                 verificationLinkContainer.innerHTML = `<p class="mt-3 text-success">¡Tu cuenta está verificada!</p>`;
            }

        } else {
             console.error("Error al obtener perfil:", result.error || 'Respuesta no exitosa');
             document.getElementById('profile-nombre').textContent = 'Error al cargar';
             document.getElementById('profile-email').textContent = 'Error al cargar';
             document.getElementById('profile-documento').textContent = 'Error al cargar';
             document.getElementById('profile-estado').textContent = 'Error';
             document.getElementById('profile-estado').className = 'badge bg-danger';
        }
    } catch (error) {
        console.error('Error al cargar el perfil:', error);
         document.getElementById('profile-nombre').textContent = 'Error de conexión';
         document.getElementById('profile-email').textContent = 'Error de conexión';
         document.getElementById('profile-documento').textContent = 'Error de conexión';
         document.getElementById('profile-estado').textContent = 'Error';
         document.getElementById('profile-estado').className = 'badge bg-danger';
    }
});