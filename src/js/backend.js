// import { qs, qsa, $on, $delegate } from './utils';
import { qs, $on } from './utils.js';
import '../scss/backend.scss';

$on(document, 'DOMContentLoaded', () => {
    /**
     * mobile nav trigger
     */
    if (qs('#mobile-show')) {
        $on(qs('#mobile-show'), 'click', () => {
            qs('#mobile-hide').classList.add('active');
            qs('.sidenav-tree > .layout').classList.add('active');
        });
    }
    if (qs('#mobile-hide')) {
        $on(qs('#mobile-hide'), 'click', () => {
            qs('#mobile-hide').classList.remove('active');
            qs('.sidenav-tree > .layout').classList.remove('active');
        });
    }
});
