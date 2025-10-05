document.addEventListener('DOMContentLoaded', () => {

    // --- Controlador para el MODAL DE NOTIFICACIONES (Éxito/Error) ---
    const infoModalElement = document.getElementById('infoModal');
    if (infoModalElement) {
        const infoModal = new bootstrap.Modal(infoModalElement);
        const modalTitle = document.getElementById('infoModalTitle');
        const modalBody = document.getElementById('infoModalBody');
        const modalHeader = document.getElementById('infoModalHeader');
        const modalCloseBtn = document.getElementById('infoModalCloseBtn');

        window.showInfoModal = (title, message, isSuccess = true, onHideCallback = null) => {
            modalTitle.textContent = title;
            modalBody.textContent = message;
            modalHeader.classList.remove('bg-success', 'bg-danger');
            modalCloseBtn.classList.remove('btn-success', 'btn-danger');
            
            if (isSuccess) {
                modalHeader.classList.add('bg-success');
                modalCloseBtn.classList.add('btn-success');
            } else {
                modalHeader.classList.add('bg-danger');
                modalCloseBtn.classList.add('btn-danger');
            }
            
            if (onHideCallback) {
                const handler = () => {
                    onHideCallback();
                    infoModalElement.removeEventListener('hidden.bs.modal', handler);
                };
                infoModalElement.addEventListener('hidden.bs.modal', handler);
            }
            infoModal.show();
        };
    }

    // --- Controlador ÚNICO para el MODAL DE CONFIRMACIÓN ---
    const confirmModalElement = document.getElementById('confirmModal');
    if (confirmModalElement) {
        const confirmModal = new bootstrap.Modal(confirmModalElement);
        const modalTitle = document.getElementById('confirmModalTitle');
        const modalBody = document.getElementById('confirmModalBody');
        const confirmBtn = document.getElementById('confirmModalConfirmBtn');
        const cancelBtn = document.getElementById('confirmModalCancelBtn');

        window.showConfirmModal = (title, message) => {
            return new Promise(resolve => {
                modalTitle.textContent = title;
                modalBody.innerHTML = message;
                confirmModal.show();

                confirmBtn.onclick = () => {
                    confirmModal.hide();
                    resolve(true);
                };
                
                const cancelOrClose = () => {
                    confirmModal.hide();
                    resolve(false);
                };
                cancelBtn.onclick = cancelOrClose;
                confirmModalElement.querySelector('.btn-close').onclick = cancelOrClose;
            });
        };
    }

    // ===================================================================
    // ---- LÓGICA DE LAS PÁGINAS DEL ADMIN ----
    // ===================================================================

    // --- LÓGICA PARA EL MODAL DE VERIFICACIÓN ---
    const verificationModalElement = document.getElementById('verificationModal');
    if (verificationModalElement) {
        const verificationModal = new bootstrap.Modal(verificationModalElement);
        const modalUserName = document.getElementById('modalUserName');
        const modalImgFrente = document.getElementById('modalImgFrente');
        const modalImgReverso = document.getElementById('modalImgReverso');
        const actionButtons = verificationModalElement.querySelectorAll('.action-btn');
        let currentUserId = null;

        verificationModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            currentUserId = button.dataset.userId;
            
            modalUserName.textContent = button.dataset.userName;
            modalImgFrente.src = `../view_secure_file.php?file=${button.dataset.imgFrente}`;
            modalImgReverso.src = `../view_secure_file.php?file=${button.dataset.imgReverso}`;
        });

        actionButtons.forEach(button => {
            button.addEventListener('click', async () => {
                const action = button.dataset.action;
                if (!currentUserId) return;

                const confirmed = await showConfirmModal(
                    `Confirmar Acción`, 
                    `¿Estás seguro de que quieres '${action}' la verificación para el usuario #${currentUserId}?`
                );

                if (confirmed) {
                    try {
                        const response = await fetch('../api/?accion=updateVerificationStatus', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ userId: currentUserId, newStatus: action })
                        });
                        const result = await response.json();

                        verificationModal.hide();

                        if (result.success) {
                            showInfoModal('Éxito', `El usuario ha sido marcado como '${action}'.`, true);
                            const rowToRemove = document.getElementById(`user-row-${currentUserId}`);
                            if(rowToRemove) rowToRemove.remove();
                        } else {
                            showInfoModal('Error', result.error, false);
                        }
                    } catch (error) {
                        showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
                    }
                }
            });
        });
    }

    // --- LÓGICA PARA GESTIONAR PAÍSES (ACTIVAR/DESACTIVAR) ---
    document.querySelectorAll('.toggle-status-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const triggerButton = e.currentTarget;
            const paisId = triggerButton.dataset.paisId;
            const currentStatus = triggerButton.dataset.currentStatus;
            const newStatus = currentStatus === '1' ? 0 : 1;
            const newStatusText = newStatus === 1 ? 'Activo' : 'Inactivo';

            const confirmed = await showConfirmModal('Confirmar Acción', `¿Seguro que quieres cambiar el estado de este país a "${newStatusText}"?`);
            if (confirmed) {
                try {
                    const response = await fetch('../api/?accion=togglePaisStatus', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ paisId: paisId, newStatus: newStatus })
                    });
                    const result = await response.json();
                    if (result.success) {
                        triggerButton.dataset.currentStatus = newStatus;
                        triggerButton.textContent = newStatus === 1 ? 'Activo' : 'Inactivo';
                        triggerButton.classList.toggle('btn-success', newStatus === 1);
                        triggerButton.classList.toggle('btn-secondary', newStatus === 0);
                        showInfoModal('Éxito', 'El estado del país ha sido actualizado.', true);
                    } else {
                        showInfoModal('Error', result.error, false);
                    }
                } catch (error) {
                    showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
                }
            }
        });
    });

    // --- LÓGICA PARA GESTIONAR PAÍSES (CAMBIAR ROL) ---
    document.querySelectorAll('.role-select').forEach(select => {
        select.addEventListener('change', async (e) => {
            const paisId = e.target.dataset.paisId;
            const newRole = e.target.value;
            
            const confirmed = await showConfirmModal('Confirmar Cambio de Rol', `¿Estás seguro de que quieres cambiar el rol de este país a "${newRole}"?`);
            if (!confirmed) {
                const originalOption = Array.from(e.target.options).find(opt => opt.defaultSelected);
                if (originalOption) e.target.value = originalOption.value;
                return;
            }

            try {
                const response = await fetch('../api/?accion=updatePaisRol', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ paisId, newRole })
                });
                const result = await response.json();
                if (result.success) {
                    showInfoModal('Éxito', '¡Rol actualizado con éxito!', true);
                } else {
                    showInfoModal('Error', result.error, false);
                }
            } catch (error) {
                showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
            }
        });
    });

    // --- LÓGICA PARA BLOQUEAR/DESBLOQUEAR USUARIOS ---
    document.querySelectorAll('.block-user-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const userId = e.target.dataset.userId;
            const currentStatus = e.target.dataset.currentStatus;
            const newStatus = currentStatus === 'active' ? 'blocked' : 'active';
            const actionText = newStatus === 'blocked' ? 'BLOQUEAR' : 'DESBLOQUEAR';

            const confirmed = await showConfirmModal('Confirmar Acción', `¿Estás seguro de que quieres ${actionText} a este usuario?`);
            if (!confirmed) return;

            try {
                const response = await fetch('../api/?accion=toggleUserBlock', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ userId, newStatus })
                });
                const result = await response.json();
                if (result.success) {
                    const successMessage = newStatus === 'blocked' ? 'Usuario bloqueado correctamente.' : 'Usuario desbloqueado correctamente.';
                    showInfoModal('Éxito', successMessage, true, () => {
                        window.location.reload();
                    });
                } else {
                    showInfoModal('Error', result.error, false);
                }
            } catch (error) {
                showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
            }
        });
    });

});