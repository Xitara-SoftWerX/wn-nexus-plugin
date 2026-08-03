type BrowserStorage = Pick<Storage, 'getItem' | 'key' | 'length' | 'removeItem' | 'setItem'>;

class PrefixedStorage {
    readonly prefix: string;

    constructor(
        private readonly storage: BrowserStorage,
        prefix: string | null = null
    ) {
        this.prefix = prefix ? `${prefix}_` : '';
    }

    set(key: string, value: string): void {
        this.storage.setItem(`${this.prefix}${key}`, value);
    }

    get(key: string): string | null {
        return this.storage.getItem(`${this.prefix}${key}`);
    }

    check(key: string): boolean {
        return this.get(key) !== null;
    }

    remove(key: string): void {
        this.storage.removeItem(`${this.prefix}${key}`);
    }

    clear(): void {
        const keys = Array.from({ length: this.storage.length }, (_, index) =>
            this.storage.key(index)
        ).filter((key): key is string => key !== null && key.startsWith(this.prefix));

        keys.forEach((key) => this.storage.removeItem(key));
    }
}

export class LocalStorage extends PrefixedStorage {
    constructor(prefix: string | null = null) {
        super(localStorage, prefix);
    }
}

export class SessionStorage extends PrefixedStorage {
    constructor(prefix: string | null = null) {
        super(sessionStorage, prefix);
    }
}
