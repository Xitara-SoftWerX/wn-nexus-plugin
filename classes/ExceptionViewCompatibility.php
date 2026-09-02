<?php

namespace Xitara\Nexus\Classes;

use Cache;
use Throwable;

/**
 * Verifies that Nexus' exception view matches the installed Winter core view.
 */
class ExceptionViewCompatibility
{
    public const CORE_VIEW_RELATIVE_PATH = 'modules/system/views/exception.php';

    public const PLUGIN_VIEW_RELATIVE_PATH = 'xitara/nexus/views/system/exception.php';

    /**
     * Full SHA-256 hashes of Winter exception views reviewed with the Nexus copy.
     */
    private const SUPPORTED_EXCEPTION_VIEW_HASHES = [
        '5393cbecb7b58420fb44714054bbf1d79d5b2efd00c66d8d9f3003ea2c8c7b4e',
    ];

    /** @var string */
    private $coreViewPath;

    /** @var string */
    private $pluginViewPath;

    /** @var object|null */
    private $cache;

    /** @var array<int, string> */
    private $supportedHashes;

    /**
     * @param object|null            $cache           Cache repository override used by tests
     * @param array<int, string>|null $supportedHashes Supported hash override used by tests
     */
    public function __construct(
        ?string $coreViewPath = null,
        ?string $pluginViewPath = null,
        $cache = null,
        array $supportedHashes = null,
    ) {
        $this->coreViewPath = $coreViewPath ?? base_path(self::CORE_VIEW_RELATIVE_PATH);
        $this->pluginViewPath = $pluginViewPath ?? plugins_path(self::PLUGIN_VIEW_RELATIVE_PATH);
        $this->cache = $cache;
        $this->supportedHashes = $supportedHashes ?? self::SUPPORTED_EXCEPTION_VIEW_HASHES;
    }

    /**
     * Returns the current compatibility state without throwing.
     *
     * @return array{compatible: bool, hash: ?string, reason: string, core_view_path: string, plugin_view_path: string}
     */
    public function inspect(): array
    {
        $status = [
            'compatible' => false,
            'hash' => null,
            'reason' => 'unknown',
            'core_view_path' => $this->coreViewPath,
            'plugin_view_path' => $this->pluginViewPath,
        ];

        try {
            if (!is_file($this->pluginViewPath) || !is_readable($this->pluginViewPath)) {
                $status['reason'] = 'plugin_view_unavailable';

                return $status;
            }

            if (!is_file($this->coreViewPath)) {
                $status['reason'] = 'core_view_missing';

                return $status;
            }

            if (!is_readable($this->coreViewPath)) {
                $status['reason'] = 'core_view_unreadable';

                return $status;
            }

            clearstatcache(true, $this->coreViewPath);
            $modifiedAt = @filemtime($this->coreViewPath);
            $size = @filesize($this->coreViewPath);

            if ($modifiedAt === false || $size === false) {
                $status['reason'] = 'core_view_metadata_unavailable';

                return $status;
            }

            $cache = $this->cache ?? Cache::store();
            if (!$cache || !is_callable([$cache, 'rememberForever'])) {
                $status['reason'] = 'cache_unavailable';

                return $status;
            }

            $cacheKey = implode(':', [
                'xitara_nexus_exception_view_hash_v1',
                sha1($this->coreViewPath),
                (string) $modifiedAt,
                (string) $size,
            ]);

            $coreViewPath = $this->coreViewPath;
            $hash = $cache->rememberForever($cacheKey, function () use ($coreViewPath) {
                return @hash_file('sha256', $coreViewPath);
            });

            if (!is_string($hash) || !preg_match('/^[a-f0-9]{64}$/', $hash)) {
                $status['reason'] = 'hash_unavailable';

                return $status;
            }

            $status['hash'] = $hash;
            $status['compatible'] = in_array($hash, $this->supportedHashes, true);
            $status['reason'] = $status['compatible'] ? 'compatible' : 'unsupported_hash';
        } catch (Throwable $exception) {
            $status['compatible'] = false;
            $status['reason'] = 'check_failed';
        }

        return $status;
    }
}
