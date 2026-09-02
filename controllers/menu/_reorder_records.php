<?php foreach ($records as $record): ?>
    <?php
    $isNavigation = $record->source_type === 'navigation';
    $contextLabel = $isNavigation
        ? $record->owner . ' / ' . $record->main_menu_code
        : $record->code;
    ?>
    <li
        class="nexus-menu-source <?= $record->is_enabled ? 'is-enabled' : 'is-disabled' ?>"
        data-record-id="<?= e($record->getKey()) ?>"
        <?php if ($reorderSortMode === 'simple'): ?>
            data-record-sort-order="<?= e($record->{$record->getSortOrderColumn()}) ?>"
        <?php endif; ?>
    >
        <div class="record nexus-menu-source-record">
            <a
                href="javascript:;"
                class="move nexus-menu-source-move"
                aria-label="<?= e(
                    trans('xitara.nexus::lang.menu_configuration.move', [
                        'name' => $record->display_name,
                    ]),
                ) ?>">
            </a>

            <div class="nexus-menu-source-identity">
                <strong class="nexus-menu-source-name"><?= e($record->display_name) ?></strong>
                <span class="nexus-menu-source-context"><?= e($contextLabel) ?></span>
            </div>

            <span class="nexus-menu-source-type">
                <?= e(
                    trans(
                        $isNavigation
                            ? 'xitara.nexus::lang.menu_configuration.native'
                            : 'xitara.nexus::lang.menu_configuration.custom',
                    ),
                ) ?>
            </span>

            <label class="custom-switch nexus-menu-source-switch">
                <input
                    type="checkbox"
                    value="1"
                    <?= $record->is_enabled ? 'checked="checked"' : '' ?>
                    data-request="onToggleSource"
                    data-request-data="code: '<?= e($record->getKey()) ?>'"
                    data-nexus-menu-toggle
                    data-stripe-load-indicator
                    aria-label="<?= e(
                        trans('xitara.nexus::lang.menu_configuration.toggle', [
                            'name' => $record->display_name,
                        ]),
                    ) ?>">
                <span>
                    <span><?= e(trans('system::lang.plugins.check_yes')) ?></span>
                    <span><?= e(trans('system::lang.plugins.check_no')) ?></span>
                </span>
                <a class="slide-button" aria-hidden="true"></a>
            </label>

            <input name="record_ids[]" type="hidden" value="<?= e($record->getKey()) ?>" />
        </div>

        <?php if ($reorderShowTree): ?>
            <ol>
                <?php if ($record->children): ?>
                    <?= $this->reorderMakePartial('records', ['records' => $record->children]) ?>
                <?php endif; ?>
            </ol>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
