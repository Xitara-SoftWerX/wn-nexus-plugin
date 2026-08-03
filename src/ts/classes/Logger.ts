import config from '../config.ts';

type LogLevel = 'debug' | 'error' | 'info' | 'log' | 'warn';

export class Log {
    private static readonly imageUrl = config.imageUrl;
    private static readonly imageSize = '14px 14px';

    static log(message: unknown, data: unknown = null): void {
        this.group('log', message, data);
    }

    static debug(message: unknown, data: unknown = null): void {
        this.group('debug', message, data);
    }

    static info(message: unknown, data: unknown = null): void {
        this.group('info', message, data);
    }

    static warn(message: unknown, data: unknown = null): void {
        this.group('warn', message, data);
    }

    static error(message: unknown, data: unknown = null): void {
        this.group('error', message, data);
    }

    static group(level: LogLevel, message: unknown, data: unknown = null): void {
        const css = [
            `background: url(${Log.imageUrl}) no-repeat`,
            `background-size: ${Log.imageSize}`,
            'padding-left: 20px',
        ].join(';');
        const heading = `~ %cLogger [${level.toUpperCase()}]`;

        if (typeof message !== 'string') {
            console.groupCollapsed(heading, css);
            console[level](message);
            console.groupEnd();
            return;
        }

        if (data !== null) {
            console.groupCollapsed(`${heading} ${message}`, css);
            console[level](data);
            console.groupEnd();
            return;
        }

        console[level](`${heading} ${message}`, css);
    }
}
