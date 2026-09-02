<?php

namespace Xitara\Nexus\Classes;

use Backend\Models\Preference;
use Throwable;

/**
 * Builds editor protocol links for the authenticated backend user.
 */
class ExceptionEditorLinkBuilder
{
    public const EDITOR_CUSTOM = 'custom';

    public const PREFERENCE_EDITOR = 'nexus_exception_editor';

    public const PREFERENCE_CUSTOM_NAME = 'nexus_exception_custom_name';

    public const PREFERENCE_CUSTOM_TEMPLATE = 'nexus_exception_custom_template';

    public const PREFERENCE_SERVER_PATH = 'nexus_exception_server_path';

    public const PREFERENCE_LOCAL_PATH = 'nexus_exception_local_path';

    private const PRESETS = [
        'vscode' => [
            'label' => 'VS Code',
            'template' => 'vscode://file/{file}:{line}:{column}',
        ],
        'vscode_insiders' => [
            'label' => 'VS Code Insiders',
            'template' => 'vscode-insiders://file/{file}:{line}:{column}',
        ],
        'cursor' => [
            'label' => 'Cursor',
            'template' => 'cursor://file/{file}:{line}:{column}',
        ],
        'phpstorm' => [
            'label' => 'PhpStorm',
            'template' => 'phpstorm://open?file={file}&line={line}&column={column}',
        ],
        'textmate' => [
            'label' => 'TextMate',
            'template' => 'txmt://open/?url=file://{file}&line={line}&column={column}',
        ],
        'bbedit' => [
            'label' => 'BBEdit',
            'template' => 'x-bbedit://open?url=file://{file}&line={line}&column={column}',
        ],
    ];

    /** @var string */
    private $name;

    /** @var string */
    private $template;

    /** @var string */
    private $serverPath;

    /** @var string */
    private $localPath;

    private function __construct(
        string $name,
        string $template,
        string $serverPath,
        string $localPath,
    ) {
        $this->name = $name;
        $this->template = $template;
        $this->serverPath = $serverPath;
        $this->localPath = $localPath;
    }

    /**
     * Returns every supported preset without client or server OS filtering.
     *
     * @return array<string, string>
     */
    public static function getPresetOptions(): array
    {
        $options = [];
        foreach (self::PRESETS as $key => $preset) {
            $options[$key] = $preset['label'];
        }

        return $options;
    }

    /**
     * Creates a fail-safe builder for the authenticated backend user.
     */
    public static function forCurrentUser(): ?self
    {
        try {
            if (!\BackendAuth::check()) {
                return null;
            }

            return self::fromConfiguration([
                'editor' => Preference::get(self::PREFERENCE_EDITOR),
                'custom_name' => Preference::get(self::PREFERENCE_CUSTOM_NAME),
                'custom_template' => Preference::get(self::PREFERENCE_CUSTOM_TEMPLATE),
                'server_path' => Preference::get(self::PREFERENCE_SERVER_PATH),
                'local_path' => Preference::get(self::PREFERENCE_LOCAL_PATH),
            ]);
        } catch (Throwable $exception) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromConfiguration(array $configuration): ?self
    {
        $editor = trim((string) ($configuration['editor'] ?? ''));
        $serverPath = trim((string) ($configuration['server_path'] ?? ''));
        $localPath = trim((string) ($configuration['local_path'] ?? ''));

        if (isset(self::PRESETS[$editor])) {
            return new self(
                self::PRESETS[$editor]['label'],
                self::PRESETS[$editor]['template'],
                $serverPath,
                $localPath,
            );
        }

        if ($editor !== self::EDITOR_CUSTOM) {
            return null;
        }

        $template = trim((string) ($configuration['custom_template'] ?? ''));
        if (!self::isValidTemplate($template)) {
            return null;
        }

        $name = trim((string) ($configuration['custom_name'] ?? ''));

        return new self($name !== '' ? $name : 'Custom', $template, $serverPath, $localPath);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Builds a URL or returns null if the file or template cannot be used safely.
     */
    public function build(?string $file = null, $line = null, $column = null): ?string
    {
        try {
            if ($file === null || trim($file) === '' || !self::isValidTemplate($this->template)) {
                return null;
            }

            $mappedFile = self::mapPath($file, $this->serverPath, $this->localPath);

            return strtr($this->template, [
                '{file}' => self::encodeFilePath($mappedFile),
                '{line}' => (string) self::normalizePosition($line),
                '{column}' => (string) self::normalizePosition($column),
            ]);
        } catch (Throwable $exception) {
            return null;
        }
    }

    /**
     * Expands the leading tilde used by Winter's ExceptionBase::getCallStack().
     */
    public static function resolveStackPath(?string $file = null, ?string $basePath = null): ?string
    {
        if ($file === null || $file === '') {
            return null;
        }

        if ($file[0] !== '~') {
            return $file;
        }

        if (strlen($file) > 1 && $file[1] !== '/' && $file[1] !== '\\') {
            return $file;
        }

        $root = rtrim($basePath ?? base_path(), '/\\');
        $suffix = ltrim(substr($file, 1), '/\\');

        return $suffix === '' ? $root : $root . DIRECTORY_SEPARATOR . $suffix;
    }

    /**
     * Replaces only a complete server root prefix and preserves the local root's slash style.
     */
    public static function mapPath(string $file, string $serverPath, string $localPath): string
    {
        if ($serverPath === '' || $localPath === '') {
            return $file;
        }

        $normalizedFile = str_replace('\\', '/', $file);
        $normalizedServer = rtrim(str_replace('\\', '/', $serverPath), '/');
        if ($normalizedServer === '') {
            $normalizedServer = '/';
        }

        $caseInsensitive =
            preg_match('/^[a-z]:/i', $normalizedServer) === 1 ||
            strpos($normalizedServer, '//') === 0;
        $prefixMatches = $caseInsensitive
            ? strncasecmp($normalizedFile, $normalizedServer, strlen($normalizedServer)) === 0
            : strncmp($normalizedFile, $normalizedServer, strlen($normalizedServer)) === 0;

        if (!$prefixMatches) {
            return $file;
        }

        $boundary = substr($normalizedFile, strlen($normalizedServer), 1);
        if (
            $normalizedFile !== $normalizedServer &&
            $normalizedServer !== '/' &&
            $boundary !== '/'
        ) {
            return $file;
        }

        $suffix = ltrim(substr($normalizedFile, strlen($normalizedServer)), '/');
        $localSeparator =
            strpos($localPath, '\\') !== false && strpos($localPath, '/') === false ? '\\' : '/';
        $localRoot = rtrim($localPath, '/\\');

        if ($suffix === '') {
            return $localRoot;
        }

        return $localRoot . $localSeparator . str_replace('/', $localSeparator, $suffix);
    }

    public static function isValidTemplate(string $template): bool
    {
        if (
            $template === '' ||
            strpos($template, '{file}') === false ||
            preg_match('/[\x00-\x1f\x7f]/', $template) === 1 ||
            preg_match('/^([a-z][a-z0-9+.-]*):\/\//i', $template, $schemeMatch) !== 1
        ) {
            return false;
        }

        if (in_array(strtolower($schemeMatch[1]), ['data', 'javascript', 'vbscript'], true)) {
            return false;
        }

        preg_match_all('/\{([^{}]+)\}/', $template, $placeholderMatches);
        foreach ($placeholderMatches[1] as $placeholder) {
            if (!in_array($placeholder, ['file', 'line', 'column'], true)) {
                return false;
            }
        }

        $withoutPlaceholders = str_replace(['{file}', '{line}', '{column}'], '', $template);
        if (
            strpos($withoutPlaceholders, '{') !== false ||
            strpos($withoutPlaceholders, '}') !== false
        ) {
            return false;
        }

        return true;
    }

    /**
     * Reads optional exception column information without risking the error view.
     *
     * @param object $exception
     */
    public static function resolveColumn($exception): int
    {
        try {
            return method_exists($exception, 'getColumn')
                ? self::normalizePosition($exception->getColumn())
                : 1;
        } catch (Throwable $error) {
            return 1;
        }
    }

    private static function encodeFilePath(string $file): string
    {
        $encoded = rawurlencode(str_replace('\\', '/', $file));

        return str_replace(['%2F', '%3A'], ['/', ':'], $encoded);
    }

    private static function normalizePosition($position): int
    {
        $position = filter_var($position, FILTER_VALIDATE_INT);

        return $position !== false && $position > 0 ? $position : 1;
    }
}
