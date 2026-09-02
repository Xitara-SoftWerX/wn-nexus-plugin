<?php

use Xitara\Nexus\Classes\ExceptionEditorLinkBuilder;
use Xitara\Nexus\Classes\ExceptionViewCompatibility;

require_once __DIR__ . '/../classes/ExceptionEditorLinkBuilder.php';
require_once __DIR__ . '/../classes/ExceptionViewCompatibility.php';

final class ArrayCacheRepository
{
    /** @var array<string, mixed> */
    private $values = [];

    /** @var int */
    public $writes = 0;

    public function rememberForever(string $key, callable $callback)
    {
        if (!array_key_exists($key, $this->values)) {
            $this->values[$key] = $callback();
            ++$this->writes;
        }

        return $this->values[$key];
    }
}

final class FailingCacheRepository
{
    public function rememberForever(string $key, callable $callback)
    {
        throw new RuntimeException('Cache unavailable');
    }
}

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(
            STDERR,
            $message .
                PHP_EOL .
                'Expected: ' .
                var_export($expected, true) .
                PHP_EOL .
                'Actual:   ' .
                var_export($actual, true) .
                PHP_EOL,
        );
        exit(1);
    }
}

function assertTrueValue($actual, string $message): void
{
    assertSameValue(true, $actual, $message);
}

$encodedUnixFile = '/var/www/My%20Project/%C3%A4%26%3F.php';
$presetExpectations = [
    'vscode' => 'vscode://file/' . $encodedUnixFile . ':42:7',
    'vscode_insiders' => 'vscode-insiders://file/' . $encodedUnixFile . ':42:7',
    'cursor' => 'cursor://file/' . $encodedUnixFile . ':42:7',
    'phpstorm' => 'phpstorm://open?file=' . $encodedUnixFile . '&line=42&column=7',
    'textmate' => 'txmt://open/?url=file://' . $encodedUnixFile . '&line=42&column=7',
    'bbedit' => 'x-bbedit://open?url=file://' . $encodedUnixFile . '&line=42&column=7',
];

foreach ($presetExpectations as $editor => $expectedUrl) {
    $builder = ExceptionEditorLinkBuilder::fromConfiguration(['editor' => $editor]);
    assertTrueValue($builder instanceof ExceptionEditorLinkBuilder, $editor . ' builder missing');
    assertSameValue(
        $expectedUrl,
        $builder->build('/var/www/My Project/ä&?.php', 42, 7),
        $editor . ' URL mismatch',
    );
}

$userABuilder = ExceptionEditorLinkBuilder::fromConfiguration(['editor' => 'vscode']);
$userBBuilder = ExceptionEditorLinkBuilder::fromConfiguration(['editor' => 'phpstorm']);
assertSameValue(
    'vscode',
    parse_url($userABuilder->build('/srv/project/File.php', 4), PHP_URL_SCHEME),
    'User A editor configuration mismatch',
);
assertSameValue(
    'phpstorm',
    parse_url($userBBuilder->build('/srv/project/File.php', 4), PHP_URL_SCHEME),
    'User B editor configuration mismatch',
);

$windowsBuilder = ExceptionEditorLinkBuilder::fromConfiguration(['editor' => 'vscode']);
assertSameValue(
    'vscode://file/C:/Projects/A%20B/Foo.php:12:1',
    $windowsBuilder->build('C:\\Projects\\A B\\Foo.php', 12),
    'Windows paths must be encoded without losing drive or path separators',
);

$xdebugBuilder = ExceptionEditorLinkBuilder::fromConfiguration([
    'editor' => 'custom',
    'custom_template' => 'xdebug://open?url=file://{file}&line={line}',
]);
assertSameValue(
    'xdebug://open?url=file:///srv/project/File.php&line=9',
    $xdebugBuilder->build('/srv/project/File.php', 9, 4),
    'Custom xdebug handler mismatch',
);

$sublimeBuilder = ExceptionEditorLinkBuilder::fromConfiguration([
    'editor' => 'custom',
    'custom_name' => 'Sublime Text',
    'custom_template' => 'subl://open?file={file}&line={line}&column={column}',
]);
assertSameValue('Sublime Text', $sublimeBuilder->getName(), 'Custom editor name mismatch');
assertSameValue(
    'subl://open?file=/srv/project/File.php&line=1&column=1',
    $sublimeBuilder->build('/srv/project/File.php'),
    'Custom handler position defaults mismatch',
);

$mappedWindowsBuilder = ExceptionEditorLinkBuilder::fromConfiguration([
    'editor' => 'phpstorm',
    'server_path' => '/var/www/winter',
    'local_path' => 'C:\\Projects\\winter',
]);
assertSameValue(
    'phpstorm://open?file=C:/Projects/winter/plugins/xitara/foo/Plugin.php&line=8&column=1',
    $mappedWindowsBuilder->build('/var/www/winter/plugins/xitara/foo/Plugin.php', 8),
    'Unix to Windows mapping mismatch',
);
assertSameValue(
    '/var/www/winter-copy/file.php',
    ExceptionEditorLinkBuilder::mapPath(
        '/var/www/winter-copy/file.php',
        '/var/www/winter',
        'C:\\Projects\\winter',
    ),
    'Path mapping must not replace partial directory names',
);
assertSameValue(
    '/Users/test/Projects/project/src/File.php',
    ExceptionEditorLinkBuilder::mapPath(
        '/srv/project/src/File.php',
        '/srv/project',
        '/Users/test/Projects/project',
    ),
    'Unix to Unix mapping mismatch',
);
assertSameValue(
    '/winter/plugins/xitara/foo/Plugin.php',
    ExceptionEditorLinkBuilder::resolveStackPath('~/plugins/xitara/foo/Plugin.php', '/winter'),
    'Winter stack path expansion mismatch',
);

assertSameValue(
    null,
    ExceptionEditorLinkBuilder::fromConfiguration([]),
    'An unconfigured user must not receive editor links',
);
assertSameValue(
    null,
    ExceptionEditorLinkBuilder::fromConfiguration([
        'editor' => 'custom',
        'custom_template' => 'javascript://open/{file}',
    ]),
    'Unsafe custom schemes must be rejected',
);
assertSameValue(
    null,
    ExceptionEditorLinkBuilder::fromConfiguration([
        'editor' => 'custom',
        'custom_template' => 'subl://open?line={line}',
    ]),
    'Custom templates without a file placeholder must be rejected',
);

$columnException = new class {
    public function getColumn(): int
    {
        throw new RuntimeException('No column available');
    }
};
assertSameValue(
    1,
    ExceptionEditorLinkBuilder::resolveColumn($columnException),
    'Column lookup must fail safely',
);

$coreFile = tempnam(sys_get_temp_dir(), 'nexus-core-view-');
$pluginFile = tempnam(sys_get_temp_dir(), 'nexus-plugin-view-');
if ($coreFile === false || $pluginFile === false) {
    fwrite(STDERR, 'Could not create compatibility test files.' . PHP_EOL);
    exit(1);
}

register_shutdown_function(function () use ($coreFile, $pluginFile): void {
    @unlink($coreFile);
    @unlink($pluginFile);
});

file_put_contents($coreFile, 'supported core view');
file_put_contents($pluginFile, 'plugin view');
$supportedHash = hash_file('sha256', $coreFile);
$cache = new ArrayCacheRepository();
$compatibility = new ExceptionViewCompatibility($coreFile, $pluginFile, $cache, [$supportedHash]);

assertTrueValue($compatibility->inspect()['compatible'], 'Known core hash must be compatible');
assertTrueValue($compatibility->inspect()['compatible'], 'Cached core hash must stay compatible');
assertSameValue(1, $cache->writes, 'Unchanged file metadata must reuse the cached SHA-256');

file_put_contents($coreFile, 'changed and unsupported core view');
touch($coreFile, time() + 10);
$changedStatus = $compatibility->inspect();
assertSameValue(false, $changedStatus['compatible'], 'Changed core file must disable the override');
assertSameValue('unsupported_hash', $changedStatus['reason'], 'Changed hash reason mismatch');
assertSameValue(2, $cache->writes, 'Changed file metadata must calculate a new SHA-256');

file_put_contents($coreFile, 'supported core view');
touch($coreFile, time() + 20);
assertTrueValue(
    $compatibility->inspect()['compatible'],
    'Restored supported core view must reactivate automatically',
);

$missingStatus = (new ExceptionViewCompatibility($coreFile . '.missing', $pluginFile, $cache, [
    $supportedHash,
]))->inspect();
assertSameValue('core_view_missing', $missingStatus['reason'], 'Missing core fallback mismatch');

$cacheFailureStatus = (new ExceptionViewCompatibility(
    $coreFile,
    $pluginFile,
    new FailingCacheRepository(),
    [$supportedHash],
))->inspect();
assertSameValue(false, $cacheFailureStatus['compatible'], 'Cache errors must disable the override');

$projectRoot = dirname(__DIR__, 4);
$currentCoreView = $projectRoot . '/modules/system/views/exception.php';
$currentPluginView = __DIR__ . '/../views/system/exception.php';
$currentStatus = (new ExceptionViewCompatibility(
    $currentCoreView,
    $currentPluginView,
    new ArrayCacheRepository(),
))->inspect();
assertTrueValue(
    $currentStatus['compatible'],
    'The currently reviewed Winter exception view hash must remain supported',
);

$pluginViewContent = file_get_contents($currentPluginView);
foreach (
    [
        'class="exception-name-block"',
        '/modules/system/assets/css/styles.css',
        '/xitara/nexus/assets/css/exception.css',
        '</head>',
    ]
    as $requiredMarker
) {
    assertTrueValue(
        strpos($pluginViewContent, $requiredMarker) !== false,
        'Plugin exception view is missing marker: ' . $requiredMarker,
    );
}

echo 'Exception compatibility and editor-link checks passed.' . PHP_EOL;
