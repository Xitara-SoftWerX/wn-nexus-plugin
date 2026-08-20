import { qs, qsa, $on } from './utils.js';
import '../scss/backend.scss';

const initializeSidebar = () => {
    const sidebar = qs('.nexus-sidebar');

    if (!sidebar || sidebar.dataset.nexusInitialized === 'true') {
        return;
    }

    sidebar.dataset.nexusInitialized = 'true';

    const panel = qs('.nexus-sidebar-panel', sidebar);
    const openButton = qs('[data-nexus-sidebar-open]', sidebar);
    const closeButton = qs('[data-nexus-sidebar-close]', sidebar);

    const setOpen = (isOpen) => {
        sidebar.classList.toggle('is-open', isOpen);
        panel?.classList.toggle('is-open', isOpen);
        openButton?.setAttribute('aria-expanded', String(isOpen));
        document.body.classList.toggle('nexus-sidebar-open', isOpen);

        if (isOpen) {
            closeButton?.focus();
        } else {
            openButton?.focus();
        }
    };

    openButton?.addEventListener('click', () => setOpen(true));
    closeButton?.addEventListener('click', () => setOpen(false));

    sidebar.addEventListener('click', (event) => {
        if (event.target === sidebar && sidebar.classList.contains('is-open')) {
            setOpen(false);
        }
    });

    $on(document, 'keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
            setOpen(false);
        }
    });

    const syncGroupState = (group) => {
        const toggle = qs('.nexus-menu-group-toggle', group);
        const isExpanded = group.getAttribute('data-status') !== 'collapsed';
        toggle?.setAttribute('aria-expanded', String(isExpanded));
    };

    qsa('.nexus-menu-group', sidebar).forEach((group) => {
        syncGroupState(group);

        qs('.nexus-menu-group-toggle', group)?.addEventListener('click', () => {
            window.setTimeout(() => syncGroupState(group), 220);
        });
    });
};

const initializeMenuConfiguration = () => {
    qsa('[data-nexus-menu-toggle]:not([data-nexus-initialized])').forEach((input) => {
        input.dataset.nexusInitialized = 'true';

        const syncState = () => {
            const source = input.closest('.nexus-menu-source');
            source?.classList.toggle('is-enabled', input.checked);
            source?.classList.toggle('is-disabled', !input.checked);
        };

        input.addEventListener('change', syncState);

        window.jQuery?.(input).on('ajaxFail.nexusMenu', () => {
            input.checked = !input.checked;
            syncState();
        });
    });
};

const initialize = () => {
    initializeSidebar();
    initializeMenuConfiguration();
};

$on(document, 'DOMContentLoaded', initialize);
$on(document, 'ajaxUpdateComplete', initialize);
$on(window, 'ajaxUpdateComplete', initialize);
