document.addEventListener('DOMContentLoaded', () => {
    // Lógica existente de 'verificationModal'
    const verificationModalElement = document.getElementById('verificationModal');
    if (verificationModalElement) {
        let verificationModalInstance = null;
        try {
            verificationModalInstance = bootstrap.Modal.getOrCreateInstance(verificationModalElement);
        } catch (e) {
            console.error("Error al inicializar el modal de verificación:", e);
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
                            window.showInfoModal('Éxito', `El usuario ha sido marcado como '${action}'. La página se recargará.`, true, () => window.location.reload());
                        } else {
                            window.showInfoModal('Error', result.error || 'Ocurrió un problema.', false);
                        }
                    } catch (error) {
                         console.error("Error en updateVerificationStatus:", error);
                        window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
                    }
                }
            });
        });
    }

    // Lógica existente de '.toggle-status-btn' (países)
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
                    console.error("Error en togglePaisStatus:", error);
                    window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
                }
            }
        });
    });

    // Lógica existente de '.role-select' (países)
    document.querySelectorAll('.role-select').forEach(select => {
        let originalValue = select.value;
        select.addEventListener('focus', () => { originalValue = select.value; }); 

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
                    originalValue = newRole; 
                    window.showInfoModal('Éxito', '¡Rol actualizado con éxito!', true);
                } else {
                    e.target.value = originalValue;
                    window.showInfoModal('Error', result.error || 'No se pudo actualizar el rol.', false);
                }
            } catch (error) {
                console.error("Error en updatePaisRol:", error);
                e.target.value = originalValue;
                window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
            }
        });
    });

    // Lógica existente de '.block-user-btn'
    document.querySelectorAll('.block-user-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const triggerButton = e.currentTarget;
            const userId = triggerButton.dataset.userId;
            const currentStatus = triggerButton.dataset.currentStatus;
            const newStatus = currentStatus === 'active' ? 'blocked' : 'active';
            const actionText = newStatus === 'blocked' ? 'DESBLOQUEAR' : 'BLOQUEAR';
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
                 console.error("Error en toggleUserBlock:", error);
                window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
            }
        });
    });

    // Lógica existente de '.process-btn'
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
    
    // Lógica existente de '.reject-btn'
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

    // Lógica existente de 'adminUploadModal'
    const adminUploadModalElement = document.getElementById('adminUploadModal');
    if (adminUploadModalElement) {
        let adminUploadModalInstance = null;
         try {
            adminUploadModalInstance = bootstrap.Modal.getOrCreateInstance(adminUploadModalElement);
        } catch (e) {
            console.error("Error al inicializar modal adminUpload:", e);
        }
        const adminTxIdLabel = document.getElementById('modal-admin-tx-id');
        const adminTransactionIdField = document.getElementById('adminTransactionIdField');
        const adminUploadForm = document.getElementById('admin-upload-form');

        adminUploadModalElement.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
             if (!button) return;
            const txId = button.dataset.txId;
            if(adminTxIdLabel) adminTxIdLabel.textContent = txId;
            if(adminTransactionIdField) adminTransactionIdField.value = txId;
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

    // --- Lógica Modal Visualización de Comprobantes ---
    const viewModalElement = document.getElementById('viewComprobanteModal');
    if (viewModalElement) {
        const modalContent = document.getElementById('comprobante-content');
        const modalPlaceholder = document.getElementById('comprobante-placeholder');
        const downloadButton = document.getElementById('download-comprobante');
        const filenameSpan = document.getElementById('comprobante-filename');
        const navigationDiv = document.getElementById('comprobante-navigation');
        const prevButton = document.getElementById('prev-comprobante');
        const nextButton = document.getElementById('next-comprobante');
        const indicatorSpan = document.getElementById('comprobante-indicator');
        const modalLabel = document.getElementById('viewComprobanteModalLabel');

        let comprobantes = [];
        let currentIndex = 0;
        let currentTxId = null;

        const showComprobante = (index) => {
            modalContent.innerHTML = '';
            modalPlaceholder.textContent = 'Cargando comprobante...';
            modalPlaceholder.classList.remove('d-none', 'text-danger');
            downloadButton.classList.add('disabled');
            downloadButton.href = '#';
            filenameSpan.textContent = '';


            if (!comprobantes[index]) {
                 console.error("Índice de comprobante inválido:", index);
                 modalPlaceholder.textContent = 'Error: No se encontró información del comprobante.';
                 modalPlaceholder.classList.add('text-danger');
                 return;
            };

            currentIndex = index;
            const current = comprobantes[index];
            const originalUrl = current.url;
            const type = current.type;

            if (typeof baseUrlJs === 'undefined') {
                console.error('baseUrlJs no está definida. Asegúrate de que se define en footer.php.');
                modalPlaceholder.textContent = 'Error de configuración: No se pudo determinar la URL base.';
                modalPlaceholder.classList.add('text-danger');
                return;
            }
            
            const secureUrl = `${baseUrlJs}/dashboard/ver-comprobante.php?id=${currentTxId}&type=${type}`;
            
            const fileName = decodeURIComponent(originalUrl.split('/').pop().split('?')[0]);
            const fileExtension = fileName.split('.').pop().toLowerCase();
            const typeText = type === 'user' ? 'Comprobante de Pago' : 'Comprobante de Envío';

            modalLabel.textContent = `${typeText} (Transacción #${currentTxId})`;
            downloadButton.href = secureUrl;
            downloadButton.download = fileName;
            filenameSpan.textContent = fileName;

            try {
                if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(fileExtension)) {
                    const img = document.createElement('img');
                    img.src = secureUrl;
                    img.alt = typeText;
                    img.classList.add('img-fluid', 'rounded');
                    img.style.maxHeight = '75vh';
                    img.style.display = 'none';
                    img.onload = () => {
                        modalPlaceholder.classList.add('d-none');
                        img.style.display = 'block';
                        downloadButton.classList.remove('disabled');
                    };
                    img.onerror = () => {
                         console.error("Error al cargar imagen desde:", secureUrl);
                         modalPlaceholder.textContent = `Error al cargar la imagen. Verifica que el archivo exista y los permisos sean correctos.`;
                         modalPlaceholder.classList.add('text-danger');
                         modalPlaceholder.classList.remove('d-none');
                         downloadButton.classList.remove('disabled');
                    }
                    modalContent.appendChild(img);
                } else if (fileExtension === 'pdf') {
                    const iframe = document.createElement('iframe');
                    iframe.src = secureUrl;
                    iframe.style.width = '100%';
                    iframe.style.height = '75vh';
                    iframe.style.border = 'none';
                    iframe.title = typeText;
                    iframe.onload = () => {
                        modalPlaceholder.classList.add('d-none');
                        downloadButton.classList.remove('disabled');
                    }
                    iframe.innerHTML = '<p class="p-3 text-warning">Tu navegador no soporta la previsualización de PDF. Usa el botón de descarga.</p>';
                    modalContent.appendChild(iframe);

                    setTimeout(() => {
                        if (modalPlaceholder.classList.contains('d-none')) {
                             downloadButton.classList.remove('disabled');
                        } else {
                            modalPlaceholder.textContent = 'La previsualización del PDF está tardando o no es compatible. Intenta descargarlo.';
                            modalPlaceholder.classList.remove('d-none');
                            downloadButton.classList.remove('disabled');
                        }
                    }, 5000);

                } else {
                    modalPlaceholder.textContent = `No se puede previsualizar este tipo de archivo (${fileExtension}).`;
                    modalPlaceholder.classList.remove('d-none');
                    downloadButton.classList.remove('disabled');
                }
            } catch (e) {
                 console.error("Error al crear elemento de visualización:", e);
                 modalPlaceholder.textContent = 'Ocurrió un error al intentar mostrar el archivo.';
                 modalPlaceholder.classList.add('text-danger');
                 modalPlaceholder.classList.remove('d-none');
                 downloadButton.classList.add('disabled');
                 downloadButton.href = '#';
            }


            if (comprobantes.length > 1) {
                indicatorSpan.textContent = `${index + 1} / ${comprobantes.length}`;
                prevButton.disabled = (index === 0);
                nextButton.disabled = (index === comprobantes.length - 1);
            } else {
                 prevButton.disabled = true;
                 nextButton.disabled = true;
            }
        };

        viewModalElement.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            if (!button || !button.classList.contains('view-comprobante-btn-admin')) { 
                return;
            }

            const userUrl = button.dataset.comprobanteUrl || '';
            const adminUrl = button.dataset.envioUrl || '';
            const startType = button.dataset.startType || 'user';
            currentTxId = button.dataset.txId || 'N/A';

            comprobantes = [];
            if (userUrl) {
                comprobantes.push({ type: 'user', url: userUrl, name: 'Comprobante de Pago' });
            }
            if (adminUrl) {
                comprobantes.push({ type: 'admin', url: adminUrl, name: 'Comprobante de Envío' });
            }

            if (comprobantes.length > 1) {
                navigationDiv.classList.remove('d-none');
            } else {
                navigationDiv.classList.add('d-none');
            }

            currentIndex = 0;
            if (startType === 'admin' && adminUrl) {
                const adminIndex = comprobantes.findIndex(c => c.type === 'admin');
                if (adminIndex !== -1) {
                    currentIndex = adminIndex;
                }
            }

            modalContent.innerHTML = '';
            modalPlaceholder.classList.remove('d-none', 'text-danger');
            modalPlaceholder.textContent = 'Cargando comprobante...';
            downloadButton.href = '#';
            downloadButton.classList.add('disabled');
            filenameSpan.textContent = '';
            modalLabel.textContent = `Visor de Comprobantes (Transacción #${currentTxId})`;


            if (comprobantes.length > 0) {
                 setTimeout(() => showComprobante(currentIndex), 100);
            } else {
                modalPlaceholder.textContent = 'No hay comprobantes disponibles para esta transacción.';
                modalPlaceholder.classList.remove('d-none');
                downloadButton.classList.add('disabled');
                navigationDiv.classList.add('d-none');
            }
        });

        prevButton.addEventListener('click', () => {
            if (currentIndex > 0) {
                showComprobante(currentIndex - 1);
            }
        });

        nextButton.addEventListener('click', () => {
            if (currentIndex < comprobantes.length - 1) {
                showComprobante(currentIndex + 1);
            }
        });

         viewModalElement.addEventListener('hidden.bs.modal', () => {
             modalContent.innerHTML = '';
              const mediaElement = modalContent.querySelector('iframe, embed, img');
              if (mediaElement) mediaElement.src = 'about:blank';

             modalPlaceholder.classList.remove('d-none', 'text-danger');
             modalPlaceholder.textContent = 'Cargando comprobante...';
             downloadButton.href = '#';
             downloadButton.classList.remove('disabled');
             filenameSpan.textContent = '';
             navigationDiv.classList.add('d-none');
             modalLabel.textContent = 'Visor de Comprobantes';
             comprobantes = [];
             currentIndex = 0;
             currentTxId = null;
         });

    } else {
         console.warn("No se encontró el elemento para el modal de visualización (#viewComprobanteModal).");
    }

    document.querySelectorAll('.admin-role-select').forEach(select => {
        let originalRoleId = select.value;
        const userId = select.dataset.userId;

        select.addEventListener('focus', () => {
            originalRoleId = select.value;
        });

        select.addEventListener('change', async (e) => {
            const newRoleId = e.target.value;
            const newRoleName = e.target.options[e.target.selectedIndex].text;
            
            const confirmed = await window.showConfirmModal(
                'Confirmar Cambio de Rol',
                `¿Estás seguro de que quieres cambiar el rol del usuario #${userId} a "${newRoleName}"?`
            );
            
            if (!confirmed) {
                e.target.value = originalRoleId;
                return;
            }

            try {
                const response = await fetch('../api/?accion=updateUserRole', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ userId: userId, newRoleId: newRoleId })
                });
                const result = await response.json();

                if (result.success) {
                    originalRoleId = newRoleId;
                    window.showInfoModal('Éxito', `Rol del usuario #${userId} actualizado a "${newRoleName}".`, true);
                } else {
                    e.target.value = originalRoleId;
                    window.showInfoModal('Error', result.error || 'No se pudo actualizar el rol.', false);
                }
            } catch (error) {
                console.error("Error en updateUserRole:", error);
                e.target.value = originalRoleId;
                window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
            }
        });
    });

    document.querySelectorAll('.admin-delete-user-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const triggerButton = e.currentTarget;
            const userId = triggerButton.dataset.userId;
            const userName = triggerButton.dataset.userName || `usuario #${userId}`;
            const confirmed1 = await window.showConfirmModal(
                'Confirmar Eliminación (Paso 1 de 2)',
                `¿Estás seguro de que quieres eliminar permanentemente a <strong>${userName}</strong> (ID: ${userId})?<br><br>Esta acción es irreversible y borrará todos sus datos.`
            );
            
            if (!confirmed1) return;

            await new Promise(resolve => setTimeout(resolve, 500));

            const confirmed2 = await window.showConfirmModal(
                '¡CONFIRMACIÓN FINAL! (Paso 2 de 2)',
                `<strong class="text-danger">¡ACCIÓN IRREVERSIBLE!</strong><br>Estás a punto de eliminar a <strong>${userName}</strong>. No habrá forma de recuperar los datos.<br><br>¿Estás absolutamente seguro?`
            );

            if (!confirmed2) return;

            triggerButton.disabled = true;
            triggerButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            try {
                const response = await fetch('../api/?accion=deleteUser', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ userId: userId })
                });
                const result = await response.json();

                if (result.success) {
                    window.showInfoModal('Éxito', `El usuario ${userName} (ID: ${userId}) ha sido eliminado.`, true);
                    const rowToRemove = document.getElementById(`user-row-${userId}`);
                    if (rowToRemove) {
                        rowToRemove.remove();
                    }
                } else {
                    window.showInfoModal('Error', result.error || 'No se pudo eliminar el usuario.', false);
                    triggerButton.disabled = false;
                    triggerButton.innerHTML = '<i class="bi bi-trash-fill"></i>';
                }
            } catch (error) {
                console.error("Error en deleteUser:", error);
                window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
                triggerButton.disabled = false;
                triggerButton.innerHTML = '<i class="bi bi-trash-fill"></i>';
            }
        });
    });

    const userDetailsModalElement = document.getElementById('userDetailsModal');
    if (userDetailsModalElement) {
        const modalTitle = document.getElementById('userDetailsModalLabel');
        const nombreCompletoEl = document.getElementById('modalUserNombreCompleto');
        const emailEl = document.getElementById('modalUserEmail');
        const telefonoEl = document.getElementById('modalUserTelefono');
        const fechaRegistroEl = document.getElementById('modalUserFechaRegistro');
        const verificacionEl = document.getElementById('modalUserVerificacion');
        const twoFaEl = document.getElementById('modalUser2FA');
        const docFrenteContainer = document.getElementById('modalUserDocFrenteContainer');
        const docReversoContainer = document.getElementById('modalUserDocReversoContainer');

        const getBadgeClass = (status) => {
            switch (status) {
                case 'Verificado': return 'bg-success';
                case 'Pendiente': return 'bg-warning text-dark';
                case 'Rechazado': return 'bg-danger';
                default: return 'bg-secondary';
            }
        };

        const createDocImage = (url) => {
            if (!url) {
                return '<span class="text-muted small d-block pt-3">No subido</span>';
            }
            const secureUrl = `../admin/view_secure_file.php?file=${encodeURIComponent(url)}`;
            return `<a href="${secureUrl}" target="_blank"><img src="${secureUrl}" class="img-fluid rounded" alt="Documento de verificación" style="max-height: 150px;"></a>`;
        };

        userDetailsModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button || !button.classList.contains('view-user-details-btn')) return;

            const userId = button.dataset.userId;
            const nombreCompleto = button.dataset.nombreCompleto || 'N/A';
            const email = button.dataset.email || 'N/A';
            const telefono = button.dataset.telefono || 'N/A';
            const fechaRegistro = button.dataset.fechaRegistro || 'N/A';
            const twoFaStatus = button.dataset.twoFaStatus;
            const verificacionStatus = button.dataset.verificacionStatus || 'N/A';
            const docFrenteUrl = button.dataset.docFrente || '';
            const docReversoUrl = button.dataset.docReverso || '';

            modalTitle.textContent = `Ficha del Usuario: #${userId}`;
            nombreCompletoEl.textContent = nombreCompleto;
            emailEl.textContent = email;
            telefonoEl.textContent = telefono;
            fechaRegistroEl.textContent = fechaRegistro;

            verificacionEl.textContent = verificacionStatus;
            verificacionEl.className = 'badge ' + getBadgeClass(verificacionStatus);

            if (twoFaStatus === '1') {
                twoFaEl.textContent = 'Activado';
                twoFaEl.className = 'badge bg-success';
            } else {
                twoFaEl.textContent = 'Inactivo';
                twoFaEl.className = 'badge bg-secondary';
            }

            docFrenteContainer.innerHTML = createDocImage(docFrenteUrl);
            docReversoContainer.innerHTML = createDocImage(docReversoUrl);
        });

        userDetailsModalElement.addEventListener('hidden.bs.modal', function () {
            docFrenteContainer.innerHTML = '';
            docReversoContainer.innerHTML = '';
        });
    }
});