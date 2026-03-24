(() => {
    const storageKey = 'ct2SidebarState';
    const root = document.documentElement;
    const sidebar = document.querySelector('[data-ct2-sidebar]');
    const toggle = document.querySelector('[data-ct2-sidebar-toggle]');

    if (!sidebar || !toggle) {
        return;
    }

    const applyState = (state) => {
        const isCollapsed = state === 'collapsed';

        root.setAttribute('data-ct2-sidebar-state', isCollapsed ? 'collapsed' : 'expanded');
        sidebar.setAttribute('data-ct2-sidebar-state', isCollapsed ? 'collapsed' : 'expanded');
        toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        toggle.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
        toggle.setAttribute('title', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
    };

    const persistState = (state) => {
        try {
            window.localStorage.setItem(storageKey, state);
        } catch (error) {
        }
    };

    const readState = () => {
        try {
            return window.localStorage.getItem(storageKey) === 'collapsed' ? 'collapsed' : 'expanded';
        } catch (error) {
            return 'expanded';
        }
    };

    applyState(readState());

    toggle.addEventListener('click', () => {
        const nextState = root.getAttribute('data-ct2-sidebar-state') === 'collapsed' ? 'expanded' : 'collapsed';

        applyState(nextState);
        persistState(nextState);
    });
})();
