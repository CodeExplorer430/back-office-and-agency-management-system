(() => {
    const Modal = window.bootstrap?.Modal;
    const modalRoot = document.querySelector('[data-ct2-modal-root]') ?? document.body;
    const modals = Array.from(document.querySelectorAll('.ct2-modal'));
    const returnFocusTargets = new WeakMap();

    if (modals.length === 0) {
        return;
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest('[data-bs-toggle="modal"][data-bs-target]')
            : null;

        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        const targetSelector = trigger.getAttribute('data-bs-target');
        if (!targetSelector) {
            return;
        }

        const targetModal = document.querySelector(targetSelector);
        if (targetModal instanceof HTMLElement && targetModal.classList.contains('ct2-modal')) {
            returnFocusTargets.set(targetModal, trigger);
        }
    });

    const focusModal = (modalElement) => {
        const focusTarget = modalElement.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])');

        if (focusTarget instanceof HTMLElement) {
            focusTarget.focus({ preventScroll: true });
        }
    };

    const normalizeModalFooter = (modalElement, index) => {
        const modalContent = modalElement.querySelector('.modal-content');
        const form = modalElement.querySelector('.modal-body form');

        if (!(modalContent instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
            return;
        }

        const submitButtons = Array.from(form.querySelectorAll('button[type="submit"]'));
        const submitButton = submitButtons[submitButtons.length - 1];

        if (!(submitButton instanceof HTMLButtonElement)) {
            return;
        }

        if (!form.id) {
            form.id = modalElement.id ? `${modalElement.id}-form` : `ct2-modal-form-${index + 1}`;
        }

        let footer = modalContent.querySelector('.modal-footer');

        if (!(footer instanceof HTMLElement)) {
            footer = document.createElement('div');
            footer.className = 'modal-footer';
            modalContent.appendChild(footer);
        }

        let dismissButton = footer.querySelector('[data-bs-dismiss="modal"]');

        if (!(dismissButton instanceof HTMLButtonElement)) {
            dismissButton = document.createElement('button');
            dismissButton.type = 'button';
            dismissButton.className = 'ct2-btn ct2-btn-secondary';
            dismissButton.setAttribute('data-bs-dismiss', 'modal');
            dismissButton.textContent = 'Cancel';
            footer.appendChild(dismissButton);
        }

        submitButton.setAttribute('form', form.id);
        footer.appendChild(submitButton);
    };

    modals.forEach((modalElement, index) => {
        if (modalElement.parentElement !== modalRoot) {
            modalRoot.appendChild(modalElement);
        }

        normalizeModalFooter(modalElement, index);

        modalElement.addEventListener('show.bs.modal', (event) => {
            if (event.relatedTarget instanceof HTMLElement) {
                returnFocusTargets.set(modalElement, event.relatedTarget);
            }
        });

        modalElement.addEventListener('shown.bs.modal', () => {
            window.setTimeout(() => {
                focusModal(modalElement);
            }, 40);
        });

        modalElement.addEventListener('hidden.bs.modal', () => {
            const returnTarget = returnFocusTargets.get(modalElement);
            if (returnTarget instanceof HTMLElement) {
                window.setTimeout(() => {
                    returnTarget.focus({ preventScroll: true });
                }, 120);
            }
            returnFocusTargets.delete(modalElement);
        });
    });

    if (typeof Modal !== 'function') {
        return;
    }

    const autoOpenModal = modals.find((modalElement) => modalElement.dataset.ct2ModalAutoOpen === 'true');

    if (autoOpenModal) {
        Modal.getOrCreateInstance(autoOpenModal).show();
    }
})();
