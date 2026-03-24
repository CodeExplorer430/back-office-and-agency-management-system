(() => {
    const container = document.querySelector('[data-ct2-toast-container]');
    const Toast = window.bootstrap?.Toast;

    if (!container || !Toast) {
        return;
    }

    const defaults = {
        success: { title: 'Success', delay: 4000, autohide: true },
        error: { title: 'Error', delay: 6000, autohide: true },
        warning: { title: 'Notice', delay: 5000, autohide: true },
        info: { title: 'Update', delay: 4000, autohide: true },
    };

    const normalizeType = (type) => (Object.hasOwn(defaults, type) ? type : 'info');

    const attachToast = (toast) => {
        toast.addEventListener(
            'hidden.bs.toast',
            () => {
                toast.remove();
            },
            { once: true }
        );

        Toast.getOrCreateInstance(toast).show();
    };

    const createToast = ({ type = 'info', message = '', title, delay, autohide } = {}) => {
        const resolvedType = normalizeType(type);
        const resolvedDefaults = defaults[resolvedType];
        const toast = document.createElement('div');
        const header = document.createElement('div');
        const indicator = document.createElement('span');
        const titleNode = document.createElement('strong');
        const closeButton = document.createElement('button');
        const body = document.createElement('div');

        toast.className = `toast border-0 shadow-sm ct2-toast ct2-toast-${resolvedType} animate__animated animate__fadeInUp animate__fast`;
        toast.setAttribute('role', resolvedType === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-live', resolvedType === 'error' ? 'assertive' : 'polite');
        toast.setAttribute('aria-atomic', 'true');
        toast.setAttribute('data-ct2-toast', '');
        toast.setAttribute('data-bs-autohide', (autohide ?? resolvedDefaults.autohide) ? 'true' : 'false');
        toast.setAttribute('data-bs-delay', String(delay ?? resolvedDefaults.delay));

        header.className = 'toast-header ct2-toast-header';
        indicator.className = 'ct2-toast-indicator';
        indicator.setAttribute('aria-hidden', 'true');

        titleNode.className = 'me-auto';
        titleNode.textContent = title ?? resolvedDefaults.title;

        closeButton.type = 'button';
        closeButton.className = 'btn-close';
        closeButton.setAttribute('data-bs-dismiss', 'toast');
        closeButton.setAttribute('aria-label', 'Close');

        body.className = 'toast-body';
        body.textContent = message;

        header.append(indicator, titleNode, closeButton);
        toast.append(header, body);

        return toast;
    };

    document.querySelectorAll('[data-ct2-toast]').forEach(attachToast);

    window.ct2Toast = {
        show(options) {
            const toast = createToast(options);

            container.appendChild(toast);
            attachToast(toast);

            return toast;
        },
    };
})();
