document.addEventListener('DOMContentLoaded', () => {
    const verificationModalElement = document.getElementById('verificationModal');
    if (verificationModalElement) {
        let verificationModalInstance = null;
        try {
            verificationModalInstance = bootstrap.Modal.getOrCreateInstance(verificationModalElement);
        } catch (e) {
            console.error("Error al inicializar el modal de verificación de Bootstrap:", e);
            return; 
        }

        const modalUserName = document.getElementById('modalUserName');
        const modalImgFrente = document.getElementById('modalImgFrente');
        const modalImgReverso = document.getElementById('modalImgReverso');
        const actionButtons = verificationModalElement.querySelectorAll('.action-btn');
        let currentUserId = null;

        verificationModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return; 

            currentUserId = button.dataset.userId;
            const userName = button.dataset.userName || 'Usuario Desconocido';
            const imgFrente = button.dataset.imgFrente || '';
            const imgReverso = button.dataset.imgReverso || '';

            modalUserName.textContent = userName;
            modalImgFrente.src = imgFrente ? `../admin/view_secure_file.php?file=${encodeURIComponent(imgFrente)}` : '';
            modalImgReverso.src = imgReverso ? `../admin/view_secure_file.php?file=${encodeURIComponent(imgReverso)}` : '';
            modalImgFrente.alt = imgFrente ? 'Frente del documento' : 'Imagen no disponible';
            modalImgReverso.alt = imgReverso ? 'Reverso del documento' : 'Imagen no disponible';
        });

        actionButtons.forEach(button => {
            button.addEventListener('click', async () => {
                const action = button.dataset.action;
                if (!currentUserId || !verificationModalInstance) return;
                const confirmed = await window.showConfirmModal(
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

                        verificationModalInstance.hide(); 

                        if (result.success) {
                            window.showInfoModal('Éxito', `El usuario ha sido marcado como '${action}'.`, true);
                            const rowToRemove = document.getElementById(`user-row-${currentUserId}`);
                            if (rowToRemove) rowToRemove.remove();
                        } else {
                            window.showInfoModal('Error', result.error || 'Ocurrió un problema.', false);
                        }
                    } catch (error) {
                         console.error("Error en la petición updateVerificationStatus:", error);
                        window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
                    }
                }
            });
        });
    }

    document.querySelectorAll('.toggle-status-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const triggerButton = e.currentTarget;
            const paisId = triggerButton.dataset.paisId;
            const currentStatus = triggerButton.dataset.currentStatus;
            const newStatus = currentStatus === '1' ? 0 : 1;
            const newStatusText = newStatus === 1 ? 'Activo' : 'Inactivo';
            const confirmed = await window.showConfirmModal('Confirmar Acción', `¿Seguro que quieres cambiar el estado de este país a "${newStatusText}"?`);
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
                        triggerButton.textContent = newStatusText; 
                        triggerButton.classList.toggle('btn-success', newStatus === 1);
                        triggerButton.classList.toggle('btn-secondary', newStatus === 0);
                        window.showInfoModal('Éxito', 'El estado del país ha sido actualizado.', true);
                    } else {
                        window.showInfoModal('Error', result.error || 'No se pudo actualizar el estado.', false);
                    }
                } catch (error) {
                    console.error("Error en la petición togglePaisStatus:", error);
                    window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
                }
            }
        });
    });

    document.querySelectorAll('.role-select').forEach(select => {
        const originalValue = select.value; 

        select.addEventListener('change', async (e) => {
            const paisId = e.target.dataset.paisId;
            const newRole = e.target.value;
            const confirmed = await window.showConfirmModal('Confirmar Cambio de Rol', `¿Estás seguro de que quieres cambiar el rol de este país a "${newRole}"?`);

            if (!confirmed) {
                e.target.value = originalValue; 
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
                    const selectedOption = e.target.querySelector(`option[value="${newRole}"]`);
                    if(selectedOption) {
                       Array.from(e.target.options).forEach(opt => opt.defaultSelected = false);
                       selectedOption.defaultSelected = true;
                    }
                    window.showInfoModal('Éxito', '¡Rol actualizado con éxito!', true);
                } else {
                    e.target.value = originalValue; 
                    window.showInfoModal('Error', result.error || 'No se pudo actualizar el rol.', false);
                }
            } catch (error) {
                console.error("Error en la petición updatePaisRol:", error);
                e.target.value = originalValue;
                window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
            }
        });
    });

    document.querySelectorAll('.block-user-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const triggerButton = e.currentTarget; 
            const userId = triggerButton.dataset.userId;
            const currentStatus = triggerButton.dataset.currentStatus;
            const newStatus = currentStatus === 'active' ? 'blocked' : 'active';
            const actionText = newStatus === 'blocked' ? 'BLOQUEAR' : 'DESBLOQUEAR';

            const confirmed = await window.showConfirmModal('Confirmar Acción', `¿Estás seguro de que quieres ${actionText} a este usuario?`);
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
                    window.showInfoModal('Éxito', successMessage, true, () => {
                        window.location.reload(); 
                    });
                } else {
                    window.showInfoModal('Error', result.error || 'No se pudo cambiar el estado del usuario.', false);
                }
            } catch (error) {
                 console.error("Error en la petición toggleUserBlock:", error);
                window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
            }
        });
    });


    document.querySelectorAll('.process-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const txId = e.currentTarget.dataset.txId;
            const confirmed = await window.showConfirmModal('Confirmar Pago', `¿Confirmas haber recibido el pago para la transacción #${txId} y deseas procesarla?`);
            if (confirmed) {
                try {
                    const response = await fetch('../api/?accion=processTransaction', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ transactionId: txId })
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.showInfoModal('Éxito', `Transacción #${txId} marcada como 'En Proceso'.`, true, () => window.location.reload());
                    } else {
                        window.showInfoModal('Error', result.error || 'No se pudo procesar.', false);
                    }
                } catch (error) {
                    window.showInfoModal('Error de Conexión', 'No se pudo conectar.', false);
                }
            }
        });
    });

    document.querySelectorAll('.reject-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const txId = e.currentTarget.dataset.txId;
            const confirmed = await window.showConfirmModal('Rechazar Pago', `¿Estás seguro de que quieres RECHAZAR el pago para la transacción #${txId}? Esto la cancelará.`);
            if (confirmed) {
                try {
                    const response = await fetch('../api/?accion=rejectTransaction', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ transactionId: txId })
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.showInfoModal('Éxito', `Transacción #${txId} ha sido Cancelada.`, true, () => window.location.reload());
                    } else {
                        window.showInfoModal('Error', result.error || 'No se pudo rechazar.', false);
                    }
                } catch (error) {
                    window.showInfoModal('Error de Conexión', 'No se pudo conectar.', false);
                }
            }
        });
    });

    const adminUploadModalElement = document.getElementById('adminUploadModal');
    if (adminUploadModalElement) {
        let adminUploadModalInstance = null;
         try {
            adminUploadModalInstance = bootstrap.Modal.getOrCreateInstance(adminUploadModalElement);
        } catch (e) {
            console.error("Error al inicializar el modal de subida admin:", e);
        }

        const adminTxIdLabel = document.getElementById('modal-admin-tx-id');
        const adminTransactionIdField = document.getElementById('adminTransactionIdField');
        const adminUploadForm = document.getElementById('admin-upload-form');

        adminUploadModalElement.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
             if (!button) return;
            const txId = button.dataset.txId;
            adminTxIdLabel.textContent = txId;
            adminTransactionIdField.value = txId;
        });

        if (adminUploadForm) {
            adminUploadForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (!adminUploadModalInstance) return;

                const formData = new FormData(adminUploadForm);
                const submitButton = adminUploadForm.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = 'Subiendo...';

                try {
                    const response = await fetch('../api/?accion=adminUploadProof', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    adminUploadModalInstance.hide(); 

                    if (result.success) {
                        window.showInfoModal('Éxito', 'Comprobante de envío subido y transacción completada.', true, () => window.location.reload());
                    } else {
                        window.showInfoModal('Error', result.error || 'No se pudo subir el comprobante.', false);
                    }
                } catch (error) {
                     adminUploadModalInstance.hide();
                    window.showInfoModal('Error de Conexión', 'No se pudo conectar.', false);
                } finally {
                    adminUploadForm.reset();
                    submitButton.disabled = false;
                    submitButton.textContent = 'Confirmar Envío';
                }
            });
        }
    }


}); 