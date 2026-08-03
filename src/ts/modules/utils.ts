import config from '../config.ts';

type ListenerTarget = Document | Element | Window;
type ListenerOptions = AddEventListenerOptions | boolean;

interface Listener {
    type: string;
    callback: EventListenerOrEventListenerObject;
    options: ListenerOptions;
}

const listenersMap = new WeakMap<ListenerTarget, Listener[]>();

export const qs = <T extends Element = HTMLElement>(
    selector: string,
    scope: Document | Element = document
): T | null => scope.querySelector<T>(selector);

export const qsa = <T extends Element = HTMLElement>(
    selector: string,
    scope: Document | Element = document
): T[] => Array.from(scope.querySelectorAll<T>(selector));

export const on = (
    target: ListenerTarget,
    type: string,
    callback: EventListenerOrEventListenerObject,
    options: ListenerOptions = {}
): void => {
    const listeners = listenersMap.get(target) ?? [];
    listeners.push({ type, callback, options });
    listenersMap.set(target, listeners);
    target.addEventListener(type, callback, options);
};

export const off = (target: ListenerTarget, type: string): void => {
    const listeners = listenersMap.get(target) ?? [];
    const retainedListeners: Listener[] = [];

    for (const listener of listeners) {
        if (listener.type === type) {
            target.removeEventListener(type, listener.callback, listener.options);
        } else {
            retainedListeners.push(listener);
        }
    }

    listenersMap.set(target, retainedListeners);
};

export const trigger = (target: Element, type: string): void => {
    target.dispatchEvent(new Event(type, { bubbles: true, cancelable: true }));
};

export const event = <T extends object>(name: string, data: T): CustomEvent<T> =>
    new CustomEvent<T>(name, { detail: data });

export const fire = (element: Element, type: string): void => {
    trigger(element, type);
};

export const scroll = (
    position: number | string,
    left = 0,
    behavior: ScrollBehavior = 'smooth'
): void => {
    let top: number;

    if (typeof position === 'string') {
        const element = qs(position);

        if (!element) {
            return;
        }

        top = element.getBoundingClientRect().top + window.scrollY - config.scrollOffset;
    } else {
        top = position;
    }

    window.scrollTo({ top: Math.max(0, top), left, behavior });
};

export const fetchData = async <T = unknown>(
    url: string,
    method: 'GET' | 'POST' | 'PUT' | 'OPTIONS' = 'POST',
    payload: Record<string, unknown> = {},
    headers: Record<string, string> = {},
    mode: RequestMode = 'cors'
): Promise<T> => {
    const response = await fetch(url, {
        headers: method === 'GET' ? headers : { 'Content-Type': 'application/json', ...headers },
        method,
        mode,
        body: method === 'GET' ? undefined : JSON.stringify(payload),
    });

    if (!response.ok) {
        throw new Error(`Request failed with ${response.status} ${response.statusText}`);
    }

    return (await response.json()) as T;
};

export const getQueryParam = (paramName: string, url = window.location.search): string | null => {
    const query = url.includes('?') ? url.slice(url.indexOf('?') + 1) : url;
    return new URLSearchParams(query).get(paramName);
};

const getCookie = (name: string): string | null => {
    const key = `${encodeURIComponent(name)}=`;
    const cookie = document.cookie
        .split(';')
        .map((item) => item.trim())
        .find((item) => item.startsWith(key));

    return cookie ? decodeURIComponent(cookie.slice(key.length)) : null;
};

export const setCookie = (name: string, value: string, expire = 30): void => {
    const date = new Date();
    date.setDate(date.getDate() + expire);
    document.cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}; expires=${date.toUTCString()}; path=/; SameSite=Lax`;
};

export const checkCookie = (name: string, value?: string): boolean | string | null => {
    const cookieValue = getCookie(name);

    if (cookieValue === null || value === undefined) {
        return cookieValue;
    }

    return cookieValue === value;
};

export const deleteCookie = (name: string, value?: string): boolean => {
    const cookieValue = getCookie(name);

    if (cookieValue === null || (value !== undefined && cookieValue !== value)) {
        return false;
    }

    document.cookie = `${encodeURIComponent(name)}=; Max-Age=0; path=/; SameSite=Lax`;
    return true;
};

export const randomNumber = (min: number, max: number): number =>
    Math.floor(Math.random() * (max - min + 1) + min);

export const randomString = (length = 20): string => {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    return Array.from({ length }, () => characters[randomNumber(0, characters.length - 1)]).join(
        ''
    );
};

export const obs = (
    parentSelector: string,
    targetSelector: string,
    callback: (...parameters: unknown[]) => void,
    parameters: unknown[] = [],
    throttleTime = 5000
): MutationObserver | null => {
    const parent = qs(parentSelector);

    if (!parent) {
        return null;
    }

    let lastExecution = 0;
    const observer = new MutationObserver((mutations) => {
        const now = Date.now();
        const hasChildChanges = mutations.some((mutation) => mutation.type === 'childList');

        if (hasChildChanges && qs(targetSelector) && now - lastExecution >= throttleTime) {
            lastExecution = now;
            callback(...parameters);
        }
    });

    observer.observe(parent, { childList: true, subtree: true });
    return observer;
};

export const slugify = (value: string): string =>
    value
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/--+/g, '-')
        .replace(/^-+|-+$/g, '');

export const asyncTimeout = (ms: number): Promise<void> =>
    new Promise((resolve) => window.setTimeout(resolve, ms));

export const checkMail = (email: string): boolean =>
    /^[\w.-]+@(?:[\w-]+\.)+[\w-]{2,14}$/.test(email);

export const round = (floatingNumber: number, digits = 2, separator = ','): string =>
    floatingNumber.toFixed(digits).replace('.', separator);

export const viewport = (): { width: number; height: number } => ({
    width: window.innerWidth,
    height: window.innerHeight,
});

export const toFloat = (value: string, digits = 2): number | null => {
    if (!value.trim()) {
        return null;
    }

    let cleanedValue = value.replace(/[^0-9.,-]/g, '');
    const lastComma = cleanedValue.lastIndexOf(',');
    const lastDot = cleanedValue.lastIndexOf('.');
    const decimalSeparator = lastComma > lastDot ? ',' : '.';

    if (lastComma >= 0 && lastDot >= 0) {
        const thousandsSeparator = decimalSeparator === ',' ? '.' : ',';
        cleanedValue = cleanedValue.replaceAll(thousandsSeparator, '');
    }

    if (decimalSeparator === ',') {
        cleanedValue = cleanedValue.replace(',', '.');
    }

    const parsedValue = Number.parseFloat(cleanedValue);
    return Number.isNaN(parsedValue) ? null : Number(parsedValue.toFixed(digits));
};
