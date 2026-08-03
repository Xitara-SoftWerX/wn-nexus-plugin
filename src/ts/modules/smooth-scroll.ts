import config from '../config.ts';
import { on, qs, qsa } from './utils.ts';

/**
 * smooth scrolling on data-smooth-scroll
 */
export const smoothscroll = () => {
    qsa('[data-smooth-scroll]').forEach((elm: Element) => {
        on(elm, 'click', (ev) => {
            ev.preventDefault();

            let scrollOffset: number = config.scrollOffset;

            if (Number((elm as HTMLElement).dataset.smoothScroll) > 0) {
                scrollOffset = Number((elm as HTMLElement).dataset.smoothScroll);
            }

            const href = (ev.currentTarget as HTMLElement).getAttribute('href');
            if (href) {
                scroll({
                    top: Number((qs(href) as HTMLElement).offsetTop) - scrollOffset,
                    left: 0,
                    behavior: 'smooth',
                });
            }
        });
    });
};
