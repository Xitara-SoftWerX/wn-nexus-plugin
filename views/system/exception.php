<?php

use Xitara\Nexus\Classes\ExceptionEditorLinkBuilder;

$editorLinks = ExceptionEditorLinkBuilder::forCurrentUser();
$exceptionFile = $exception->getFile();
$exceptionLine = $exception->getLine();
$exceptionColumn = ExceptionEditorLinkBuilder::resolveColumn($exception);
$exceptionEditorUrl = $editorLinks
    ? $editorLinks->build($exceptionFile, $exceptionLine, $exceptionColumn)
    : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Exception</title>
    <link href="<?= Url::asset('/modules/system/assets/css/styles.css') ?>" rel="stylesheet">
    <link href="<?= Url::asset(
        Config::get('cms.pluginsPath') . '/xitara/nexus/assets/css/exception.css',
    ) ?>" rel="stylesheet">
    <script src="<?= Url::asset(
        '/modules/system/assets/vendor/syntaxhighlighter/scripts/shCore.js',
    ) ?>"></script>
    <script src="<?= Url::asset(
        '/modules/system/assets/vendor/syntaxhighlighter/scripts/shBrushPhp.js',
    ) ?>"></script>
    <script src="<?= Url::asset(
        '/modules/system/assets/vendor/syntaxhighlighter/scripts/shBrushXml.js',
    ) ?>"></script>
    <link href="<?= Url::asset(
        '/modules/system/assets/vendor/syntaxhighlighter/styles/shCore.css',
    ) ?>">
</head>

<body>
    <div class="container">

        <h1><i class="icon-power-off warning"></i> Error</h1>

        <p class="lead">We're sorry, but an unhandled error occurred. Please see the details below.</p>

        <div class="exception-name-block">
            <div><?= e($exception->getMessage()) ?></div>
            <p>
                <?php if ($exceptionEditorUrl): ?>
                    <a
                        href="<?= e($exceptionEditorUrl) ?>"
                        data-nexus-editor-link
                        title="<?= e('Open in ' . $editorLinks->getName()) ?>">
                        <?= e($exceptionFile) ?> <span>line</span> <?= e($exceptionLine) ?>
                    </a>
                <?php else: ?>
                    <?= e($exceptionFile) ?> <span>line</span> <?= e($exceptionLine) ?>
                <?php endif; ?>
            </p>
        </div>

        <ul class="indicators">
            <li>
                <h3>Type</h3>
                <p><?= e($exception->getErrorType()) ?></p>
            </li>
            <li>
                <h3>Exception</h3>
                <p><?= e($exception->getClassName()) ?></p>
            </li>
        </ul>

        <pre class="brush: php"><?= implode('', $exception->getHighlightLines()) ?></pre>

        <h3><i class="icon-code-fork warning"></i> Stack trace</h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="right">#</th>
                    <th>Called Code</th>
                    <th>Document</th>
                    <th class="right">Line</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exception->getCallStack() as $stackItem): ?>
                    <?php
                    $stackFile = ExceptionEditorLinkBuilder::resolveStackPath(
                        $stackItem->file,
                        base_path(),
                    );
                    $stackEditorUrl = $editorLinks
                        ? $editorLinks->build(
                            $stackFile,
                            $stackItem->line,
                            isset($stackItem->column) ? $stackItem->column : 1,
                        )
                        : null;
                    ?>
                    <tr>
                        <td class="right"><?= $stackItem->id ?></td>
                        <td>
                            <?= $stackItem->code ?>(<?php if ($stackItem->args): ?>
                                <abbr title="<?= $stackItem->args ?>">&hellip;</abbr>
                            <?php endif; ?>)
                        </td>
                        <td>
                            <?php if ($stackEditorUrl): ?>
                                <a
                                    href="<?= e($stackEditorUrl) ?>"
                                    data-nexus-editor-link
                                    title="<?= e('Open in ' . $editorLinks->getName()) ?>">
                                    <b><?= e($stackItem->file) ?></b>
                                </a>
                            <?php else: ?>
                                <?= e($stackItem->file) ?>
                            <?php endif; ?>
                        </td>
                        <td class="right"><?= $stackItem->line ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        SyntaxHighlighter.defaults['toolbar'] = false;
        SyntaxHighlighter.defaults['quick-code'] = false;
        SyntaxHighlighter.defaults['html-script'] = true;
        SyntaxHighlighter.defaults['first-line'] = <?= $exception->getHighlight()->startLine + 1 ?>;
        SyntaxHighlighter.defaults['highlight'] = <?= $exception->getLine() ?>;
        SyntaxHighlighter.all()
    </script>
</body>

</html>
