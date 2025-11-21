document.addEventListener('DOMContentLoaded', () => {
    const uploadModalElement = document.getElementById('uploadReceiptModal');
    const uploadForm = document.getElementById('upload-receipt-form');
    const transactionIdField = document.getElementById('transactionIdField');
    const modalTxIdLabel = document.getElementById('modal-tx-id');

    if (uploadModalElement && uploadForm && transactionIdField && modalTxIdLabel) {
        let uploadModalInstance = null;

        uploadModalElement.addEventListener('show.bs.modal', function (event) {
            uploadModalInstance = bootstrap.Modal.getInstance(uploadModalElement) || new bootstrap.Modal(uploadModalElement);
            const button = event.relatedTarget;
            const transactionId = button.getAttribute('data-tx-id');
            transactionIdField.value = transactionId;
            modalTxIdLabel.textContent = transactionId;
            uploadForm.reset();
        });

        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(uploadForm);
            const submitButton = uploadModalElement.querySelector('button[type="submit"][form="upload-receipt-form"]');
            const transactionId = transactionIdField.value;

            if (!submitButton) {
                console.error("No se encontró el botón de submit para el formulario de subida.");
                if (window.showInfoModal) {
                    window.showInfoModal('Error Interno', 'No se pudo encontrar el botón de envío. Refresca la página.', false);
                } else {
                    alert('Error: Botón de envío no encontrado.');
                }
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Subiendo...';

            try {
                const response = await fetch('../api/?accion=uploadReceipt', {
                    method: 'POST',
                    body: formData
                });

                let result;
                try {
                     result = await response.json();
                } catch(jsonError) {
                     console.error("Error al parsear JSON en subida:", jsonError);
                     result = { success: false, error: 'Respuesta inválida del servidor al subir.' };
                }

                if(uploadModalInstance) uploadModalInstance.hide();

                if (response.ok && result.success) {
                    if (window.showInfoModal) {
                        window.showInfoModal(
                            '¡Éxito!',
                            `Comprobante para la transacción #${transactionId} subido con éxito. La página se recargará.`,
                            true,
                            () => { window.location.reload(); }
                        );
                    } else {
                        alert(`¡Comprobante para la transacción #${transactionId} subido con éxito! La página se recargará.`);
                        window.location.reload();
                    }
                } else {
                    const errorMsg = result.error || `Error ${response.status}: No se pudo subir el archivo.`;
                    if (window.showInfoModal) {
                        window.showInfoModal('Error al Subir', errorMsg, false);
                    } else {
                        alert('Error al subir el archivo: ' + errorMsg);
                    }
                    submitButton.disabled = false;
                    submitButton.textContent = 'Subir Archivo';
                }
            } catch (error) {
                console.error('Error de red al subir comprobante:', error);
                if(uploadModalInstance) uploadModalInstance.hide();
                if (window.showInfoModal) {
                     window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor para subir el archivo.', false);
                } else {
                    alert('No se pudo conectar con el servidor.');
                }
                submitButton.disabled = false;
                submitButton.textContent = 'Subir Archivo';
            }
        });
    } else {
        console.warn("No se encontraron elementos para el modal de subida.");
    }

    const cancelButtons = document.querySelectorAll('.cancel-btn');
    cancelButtons.forEach(button => {
        button.addEventListener('click', async (e) => {
            const transactionId = e.target.closest('button').dataset.txId;
            if (!transactionId) return;

            const confirmed = await window.showConfirmModal(
                'Confirmar Cancelación',
                `¿Estás seguro de que quieres cancelar la transacción #${transactionId}? Esta acción no se puede deshacer.`
            );

            if (confirmed) {
                e.target.closest('button').disabled = true;
                e.target.closest('button').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cancelando...';

                try {
                    const response = await fetch('../api/?accion=cancelTransaction', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ transactionId })
                    });

                    let result;
                    try {
                         result = await response.json();
                    } catch(jsonError) {
                         console.error("Error al parsear JSON en cancelación:", jsonError);
                         result = { success: false, error: 'Respuesta inválida del servidor al cancelar.' };
                    }


                    if (response.ok && result.success) {
                         if (window.showInfoModal) {
                             window.showInfoModal('Éxito', 'Transacción cancelada con éxito. La página se recargará.', true, () => window.location.reload());
                         } else {
                            alert('Transacción cancelada con éxito. La página se recargará.');
                            window.location.reload();
                         }
                    } else {
                        const errorMsg = result.error || 'No se pudo cancelar la transacción.';
                        if (window.showInfoModal) {
                             window.showInfoModal('Error', errorMsg, false);
                        } else {
                            alert('Error: ' + errorMsg);
                        }
                        e.target.closest('button').disabled = false;
                        e.target.closest('button').innerHTML = '<i class="bi bi-x-circle"></i> Cancelar';
                    }
                } catch (error) {
                     console.error('Error de red al cancelar:', error);
                     if(window.showInfoModal) {
                        window.showInfoModal('Error de Conexión', 'No se pudo conectar con el servidor.', false);
                     } else {
                        alert('Error de conexión con el servidor.');
                     }
                     e.target.closest('button').disabled = false;
                     e.target.closest('button').innerHTML = '<i class="bi bi-x-circle"></i> Cancelar';
                }
            }
        });
    });

    const viewModalElement = document.getElementById('viewComprobanteModal');
    if (viewModalElement) {
        const viewModalInstance = new bootstrap.Modal(viewModalElement);
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

            // ***** INICIO CORRECCIÓN USO baseUrlJs *****
            // Verificar si baseUrlJs está definida globalmente
            if (typeof baseUrlJs === 'undefined') {
                console.error('baseUrlJs no está definida. Asegúrate de que se define en footer.php.');
                modalPlaceholder.textContent = 'Error de configuración: No se pudo determinar la URL base.';
                modalPlaceholder.classList.add('text-danger');
                return;
            }
            const secureUrl = `${baseUrlJs}/dashboard/ver-comprobante.php?id=${currentTxId}&type=${type}`;
            // ***** FIN CORRECCIÓN USO baseUrlJs *****

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
            if (!button) return;

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
         console.warn("No se encontró el elemento para el modal de visualización.");
    }

});