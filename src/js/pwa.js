// import { qs, qsa, $on, $delegate } from './utils';
import { qs, $on } from './utils.js';

$on(window, 'load', () => {
    // new PWAConfApp();
    registerSW();
});

async function registerSW() {
    if ('serviceWorker' in navigator) {
        try {
            await navigator.serviceWorker.register('/plugins/xitara/nexus/assets/js/sw.js', {
                scope: '/nexus',
            });
        } catch (e) {
            alert('ServiceWorker registration failed. Sorry about that.');
        }
    } else {
        qs('.alert').removeAttribute('hidden');
    }
}

import '../scss/styles.scss';
