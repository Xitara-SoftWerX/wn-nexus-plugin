import { on, qsa } from './utils.ts';

/**
 * add timezone-offset to links with data-tz
 */
on(document, 'DOMContentLoaded', () => {
    qsa('[data-tz]').forEach((elm: Element) => {
        const d = new Date();
        (elm as HTMLAnchorElement).href += '/' + d.getTimezoneOffset();
    });
});
