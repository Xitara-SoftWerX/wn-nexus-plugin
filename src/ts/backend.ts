import { on, qs, qsa } from './modules/utils.ts';
import '../scss/backend.scss';

interface JQueryEventTarget {
    on(eventName: string, handler: () => void): void;
}

type JQueryFactory = (target: Element) => JQueryEventTarget;

const initializeSidebar = (): void => {
    const sidebar = qs<HTMLElement>('.nexus-sidebar');

    if (!sidebar || sidebar.dataset.nexusInitialized === 'true') {
        return;
    }

    sidebar.dataset.nexusInitialized = 'true';

    const panel = qs<HTMLElement>('.nexus-sidebar-panel', sidebar);
    const openButton = qs<HTMLButtonElement>('[data-nexus-sidebar-open]', sidebar);
    const closeButton = qs<HTMLButtonElement>('[data-nexus-sidebar-close]', sidebar);

    const setOpen = (isOpen: boolean): void => {
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

    on(document, 'keydown', (event) => {
        if ((event as KeyboardEvent).key === 'Escape' && sidebar.classList.contains('is-open')) {
            setOpen(false);
        }
    });

    const syncGroupState = (group: HTMLElement): void => {
        const toggle = qs<HTMLButtonElement>('.nexus-menu-group-toggle', group);
        const isExpanded = group.dataset.status !== 'collapsed';
        toggle?.setAttribute('aria-expanded', String(isExpanded));
    };

    qsa<HTMLElement>('.nexus-menu-group', sidebar).forEach((group) => {
        syncGroupState(group);

        qs<HTMLButtonElement>('.nexus-menu-group-toggle', group)?.addEventListener('click', () => {
            window.setTimeout(() => syncGroupState(group), 220);
        });
    });
};

const initializeMenuConfiguration = (): void => {
    qsa<HTMLInputElement>('[data-nexus-menu-toggle]:not([data-nexus-initialized])').forEach(
        (input) => {
            input.dataset.nexusInitialized = 'true';

            const syncState = (): void => {
                const source = input.closest<HTMLElement>('.nexus-menu-source');
                source?.classList.toggle('is-enabled', input.checked);
                source?.classList.toggle('is-disabled', !input.checked);
            };

            input.addEventListener('change', syncState);

            const jquery = (window as Window & { jQuery?: JQueryFactory }).jQuery;
            jquery?.(input).on('ajaxFail.nexusMenu', () => {
                input.checked = !input.checked;
                syncState();
            });
        }
    );
};

const initialize = (): void => {
    initializeSidebar();
    initializeMenuConfiguration();
};

on(document, 'DOMContentLoaded', initialize);
on(document, 'ajaxUpdateComplete', initialize);
on(window, 'ajaxUpdateComplete', initialize);
