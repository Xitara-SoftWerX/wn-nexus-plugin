import Mark from 'mark.js';

import { on, qsa } from './utils.ts';

on(document, 'DOMContentLoaded', () => {
    const searchTerm = new URLSearchParams(window.location.search).get('highlight');

    if (!searchTerm) {
        return;
    }

    const elements = qsa<HTMLElement>('main');
    const marker = new Mark(elements);

    marker.mark(searchTerm, {
        done: () => {
            document.querySelector('mark')?.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        },
    });
});
