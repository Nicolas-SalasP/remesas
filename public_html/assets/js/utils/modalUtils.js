document.addEventListener('DOMContentLoaded', () => {
    const infoModalElement = document.getElementById('infoModal');
    let infoModalInstance = null; 

    if (infoModalElement) {
        infoModalInstance = new bootstrap.Modal(infoModalElement); 
        const modalTitle = document.getElementById('infoModalTitle');
        const modalBody = document.getElementById('infoModalBody');
        const modalHeader = document.getElementById('infoModalHeader');
        const modalCloseBtn = document.getElementById('infoModalCloseBtn');

        window.showInfoModal = (title, message, isSuccess = true, onHideCallback = null) => {
            if (!infoModalInstance) return; 

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

            const existingHandler = infoModalElement.hideListener;
            if (existingHandler) {
                infoModalElement.removeEventListener('hidden.bs.modal', existingHandler);
            }

            if (onHideCallback && typeof onHideCallback === 'function') {
                const newHandler = () => {
                    onHideCallback();
                    infoModalElement.removeEventListener('hidden.bs.modal', newHandler);
                    infoModalElement.hideListener = null;
                };
                infoModalElement.addEventListener('hidden.bs.modal', newHandler);
                infoModalElement.hideListener = newHandler; 
            } else {
                 infoModalElement.hideListener = null;
            }

            infoModalInstance.show();
        };
    } else {
        window.showInfoModal = (title, message) => {
             console.warn('InfoModal element not found. Message:', title, message);
        };
    }

    const confirmModalElement = document.getElementById('confirmModal');
    let confirmModalInstance = null; 

    if (confirmModalElement) {
        confirmModalInstance = new bootstrap.Modal(confirmModalElement); 
        const modalTitle = document.getElementById('confirmModalTitle');
        const modalBody = document.getElementById('confirmModalBody');
        const confirmBtn = document.getElementById('confirmModalConfirmBtn');
        const cancelBtn = document.getElementById('confirmModalCancelBtn');
        const closeBtn = confirmModalElement.querySelector('.btn-close');

        window.showConfirmModal = (title, message) => {
             if (!confirmModalInstance) return Promise.resolve(false); 

            return new Promise(resolve => {
                modalTitle.textContent = title;
                if (typeof message === 'string' && message.indexOf('<') === -1) {
                    modalBody.textContent = message;
                } else {
                    modalBody.innerHTML = message;
                }

                confirmBtn.onclick = null;
                cancelBtn.onclick = null;
                closeBtn.onclick = null;

                confirmBtn.onclick = () => {
                    confirmModalInstance.hide();
                    resolve(true);
                };

                const cancelOrClose = () => {
                    confirmModalInstance.hide();
                    resolve(false);
                };
                cancelBtn.onclick = cancelOrClose;
                closeBtn.onclick = cancelOrClose;

                confirmModalInstance.show();
            });
        };
    } else {
         window.showConfirmModal = (title, message) => {
             console.warn('ConfirmModal element not found. Message:', title, message)
             return Promise.resolve(false); 
         };
    }
});