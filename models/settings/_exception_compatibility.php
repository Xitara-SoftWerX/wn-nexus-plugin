<?php

use System\Models\Parameter;
use Xitara\Nexus\Classes\ExceptionViewCompatibility;

$status = (new ExceptionViewCompatibility())->inspect();
$winterBuild = null;

try {
    $winterBuild = Parameter::get('system::core.build');
} catch (Throwable $exception) {
    // Compatibility status remains useful without optional version metadata.
}

$isCompatible = $status['compatible'];
$calloutClass = $isCompatible ? 'callout-success' : 'callout-warning';
$iconClass = $isCompatible ? 'icon-check' : 'icon-warning';
$statusLabel = $isCompatible
    ? 'xitara.nexus::settings.exception.compatible'
    : 'xitara.nexus::settings.exception.incompatible';
?>

<div class="callout fade in <?= $calloutClass ?> no-subheader">
    <div class="header">
        <i class="<?= $iconClass ?>"></i>
        <h3><?= e(trans($statusLabel)) ?></h3>
    </div>
    <div class="content">
        <p><?= e(trans('xitara.nexus::settings.exception.compatibility_comment')) ?></p>
        <?php if ($status['hash']): ?>
            <p>
                <strong><?= e(trans('xitara.nexus::settings.exception.hash')) ?>:</strong>
                <code><?= e($status['hash']) ?></code>
            </p>
        <?php endif; ?>
        <?php if ($winterBuild !== null && $winterBuild !== ''): ?>
            <p>
                <strong><?= e(trans('xitara.nexus::settings.exception.build')) ?>:</strong>
                <?= e($winterBuild) ?>
            </p>
        <?php endif; ?>
    </div>
</div>
