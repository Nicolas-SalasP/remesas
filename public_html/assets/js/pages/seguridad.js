document.addEventListener('DOMContentLoaded', () => {
    const statusContainer = document.getElementById('2fa-status-container');
    const setupSection = document.getElementById('setup-2fa-section');
    const disableSection = document.getElementById('disable-2fa-section');
    const qrContainer = document.getElementById('qr-code-container');
    const secretKeyDisplay = document.getElementById('secret-key-display');
    const verifyForm = document.getElementById('verify-2fa-form');
    const disableBtn = document.getElementById('disable-2fa-btn');
    
    const backupCodesModalEl = document.getElementById('backupCodesModal');
    const backupCodesModal = backupCodesModalEl ? new bootstrap.Modal(backupCodesModalEl) : null;
    const backupCodesList = document.getElementById('backup-codes-list');

    let is2FAEnabled = false;

    const update2FAStatus = () => {
        if (is2FAEnabled) {
            statusContainer.innerHTML = '<p class="lead text-success fw-bold"><i class="bi bi-shield-check"></i> Doble Factor (2FA) está ACTIVADO.</p>';
            setupSection.classList.add('d-none');
            disableSection.classList.remove('d-none');
        } else {
            statusContainer.innerHTML = '<p class="lead text-warning fw-bold"><i class="bi bi-shield-exclamation"></i> Doble Factor (2FA) está DESACTIVADO.</p>';
            setupSection.classList.remove('d-none');
            disableSection.classList.add('d-none');
            generate2FASecret();
        }
    };

    const getProfileStatus = async () => {
        try {
            const response = await fetch('../api/?accion=getUserProfile');
            const result = await response.json();
            if (result.success && result.profile) {
                is2FAEnabled = result.profile.twofa_enabled || false;
            } else {
                throw new Error(result.error || 'No se pudo obtener el perfil');
            }
        } catch (e) {
            console.error(e);
            statusContainer.innerHTML = `<p class="text-danger">Error al cargar estado 2FA: ${e.message}</p>`;
        }
        update2FAStatus();
    };

    const generate2FASecret = async () => {
        qrContainer.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>';
        secretKeyDisplay.textContent = 'Cargando...';
        try {
            const response = await fetch('../api/?accion=generate2FASecret', { method: 'POST' });
            const result = await response.json();
            if (!result.success) throw new Error(result.error);
            const qrCode = new QRCode(qrContainer, {
                text: result.qrCodeUrl,
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
            secretKeyDisplay.textContent = result.secret;
            
        } catch (e) {
            console.error(e);
            qrContainer.innerHTML = `<p class="text-danger">Error al generar QR: ${e.message}</p>`;
            secretKeyDisplay.textContent = 'Error';
        }
    };

    verifyForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const code = document.getElementById('2fa-code').value;
        const submitButton = verifyForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Verificando...';

        try {
            const response = await fetch('../api/?accion=enable2FA', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code })
            });
            const result = await response.json();
            
            if (result.success) {
                is2FAEnabled = true;
                update2FAStatus();
                
                if (backupCodesList && result.backup_codes && result.backup_codes.length > 0) {
                    backupCodesList.innerHTML = '';
                    result.backup_codes.forEach(code => {
                        const li = document.createElement('li');
                        li.textContent = code;
                        backupCodesList.appendChild(li);
                    });
                    if(backupCodesModal) backupCodesModal.show();
                } else {
                    window.showInfoModal('Éxito', '2FA activado correctamente.', true);
                }
                
                verifyForm.reset();
            } else {
                throw new Error(result.error || 'Código inválido');
            }
        } catch (e) {
            console.error(e);
            window.showInfoModal('Error', e.message, false);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Activar y Verificar';
        }
    });

    disableBtn.addEventListener('click', async () => {
        const confirmed = await window.showConfirmModal(
            'Confirmar Desactivación',
            '¿Estás seguro de que quieres desactivar 2FA? Tu cuenta será menos segura.'
        );
        if (!confirmed) return;

        disableBtn.disabled = true;
        disableBtn.textContent = 'Desactivando...';
        try {
            const response = await fetch('../api/?accion=disable2FA', { method: 'POST' });
            const result = await response.json();
            if (!result.success) throw new Error(result.error);
            
            is2FAEnabled = false;
            update2FAStatus();
            window.showInfoModal('2FA Desactivado', 'El doble factor ha sido desactivado.', true);

        } catch (e) {
            console.error(e);
            window.showInfoModal('Error', e.message, false);
        } finally {
            disableBtn.disabled = false;
            disableBtn.textContent = 'Desactivar 2FA';
        }
    });

    getProfileStatus();
});