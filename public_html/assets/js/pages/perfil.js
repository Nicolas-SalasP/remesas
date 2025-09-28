document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch('../api/?accion=getUserProfile');
        const result = await response.json();

        if (result.success) {
            const profile = result.profile;
            document.getElementById('profile-nombre').textContent = `${profile.PrimerNombre} ${profile.PrimerApellido}`;
            document.getElementById('profile-email').textContent = profile.Email;
            document.getElementById('profile-documento').textContent = `${profile.TipoDocumento} ${profile.NumeroDocumento}`;
            
            const estadoBadge = document.getElementById('profile-estado');
            estadoBadge.textContent = profile.VerificacionEstado;
            
            // Asignar color según el estado
            if(profile.VerificacionEstado === 'Verificado') estadoBadge.classList.add('bg-success');
            else if(profile.VerificacionEstado === 'Pendiente') estadoBadge.classList.add('bg-warning', 'text-dark');
            else if(profile.VerificacionEstado === 'Rechazado') estadoBadge.classList.add('bg-danger');
            else estadoBadge.classList.add('bg-secondary');

            if(profile.VerificacionEstado === 'No Verificado' || profile.VerificacionEstado === 'Rechazado') {
                document.getElementById('verification-link-container').innerHTML = 
                `<a href="verificar.php" class="btn btn-primary">Verificar mi cuenta ahora</a>`;
            }
        }
    } catch (error) {
        console.error('Error al cargar el perfil:', error);
    }
});