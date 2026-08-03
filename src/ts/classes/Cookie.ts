export class Cookie {
    set(key: string, value: string, expire = 30): void {
        const date = new Date();
        date.setDate(date.getDate() + expire);

        document.cookie = [
            `${encodeURIComponent(key)}=${encodeURIComponent(value)}`,
            `expires=${date.toUTCString()}`,
            'path=/',
            'SameSite=Lax',
        ].join('; ');
    }

    get(key: string): string | null {
        const encodedKey = `${encodeURIComponent(key)}=`;
        const cookie = document.cookie
            .split(';')
            .map((item) => item.trim())
            .find((item) => item.startsWith(encodedKey));

        return cookie ? decodeURIComponent(cookie.slice(encodedKey.length)) : null;
    }

    check(key: string, expectedValue: string | null = null): boolean | string | null {
        const value = this.get(key);

        if (value === null || expectedValue === null) {
            return value;
        }

        return value === expectedValue;
    }

    remove(key: string, value?: string): boolean {
        const currentValue = this.get(key);

        if (currentValue === null || (value !== undefined && currentValue !== value)) {
            return false;
        }

        document.cookie = `${encodeURIComponent(key)}=; Max-Age=0; path=/; SameSite=Lax`;
        return true;
    }
}
