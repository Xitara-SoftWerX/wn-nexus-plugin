<?php
    $sideMenuItems = BackendMenu::listSideMenuItems();
    $collapsedGroups = explode('|', $_COOKIE['nexus_sidenav_groupStatus'] ?? '');
    $categories = [];

    foreach ($sideMenuItems as $sideItemCode => $item) {
        $attributes = $item->attributes ?? [];
        $groupCode = (string) ($attributes['nexusGroupCode'] ?? $attributes['group'] ?? 'nexus');
        $groupLabel = (string) (
            $attributes['nexusGroupLabel']
            ?? $attributes['groupLabel']
            ?? $attributes['group']
            ?? 'xitara.nexus::lang.submenu.label'
        );

        if (!isset($categories[$groupCode])) {
            $categories[$groupCode] = [
                'label' => $groupLabel,
                'items' => [],
            ];
        }

        $categories[$groupCode]['items'][$sideItemCode] = $item;
    }
?>

<?php if ($categories): ?>
    <nav
        class="nexus-sidebar-navigation"
        aria-label="<?= e(trans('xitara.nexus::lang.menu_configuration.navigation_label')) ?>">
        <ul class="top-level nexus-menu-groups" role="list">
            <?php foreach ($categories as $groupCode => $category): ?>
                <?php
                    $groupId = 'nexus-menu-group-'.substr(sha1($groupCode), 0, 12);
                    $containsActiveItem = false;

                    foreach ($category['items'] as $item) {
                        if (BackendMenu::isSideMenuItemActive($item)) {
                            $containsActiveItem = true;
                            break;
                        }
                    }

                    $collapsed = !$containsActiveItem && in_array($groupCode, $collapsedGroups, true);
                ?>
                <li
                    class="nexus-menu-group"
                    data-group-code="<?= e($groupCode) ?>"
                    data-status="<?= $collapsed ? 'collapsed' : 'expanded' ?>">
                    <div class="group nexus-menu-group-heading">
                        <h3>
                            <button
                                type="button"
                                class="nexus-menu-group-toggle"
                                aria-expanded="<?= $collapsed ? 'false' : 'true' ?>"
                                aria-controls="<?= e($groupId) ?>">
                                <span><?= e(trans($category['label'])) ?></span>
                                <i class="icon-angle-down" aria-hidden="true"></i>
                            </button>
                        </h3>
                    </div>

                    <ul id="<?= e($groupId) ?>" class="nexus-menu-group-items" role="list">
                        <?php foreach ($category['items'] as $key => $item): ?>
                            <?php
                                $attributes = $item->attributes ?? [];
                                $isActive = BackendMenu::isSideMenuItemActive($item);
                                $target = $attributes['target'] ?? '_self';
                                $description = $attributes['description'] ?? null;
                                $level = max(1, (int) ($attributes['level'] ?? 1));
                                $line = $attributes['line'] ?? null;
                            ?>
                            <?php if (empty($item->hidden)): ?>
                                <li
                                    class="nexus-menu-item level-<?= e($level) ?><?= $isActive ? ' active' : '' ?><?= $line ? ' border-'.e($line) : '' ?><?= !empty($attributes['bold']) ? ' is-emphasized' : '' ?>"
                                    data-keywords="<?= e(trans($attributes['keywords'] ?? '')) ?>">
                                    <a
                                        class="nexus-menu-item-link"
                                        href="<?= e($item->url) ?>"
                                        target="<?= e($target) ?>"
                                        <?= $target === '_blank' ? 'rel="noopener noreferrer"' : '' ?>
                                        <?= $isActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nexus-menu-item-icon" aria-hidden="true">
                                            <?php if ($item->iconSvg): ?>
                                                <img src="<?= e(Url::asset($item->iconSvg)) ?>" alt="" loading="lazy">
                                            <?php else: ?>
                                                <i class="<?= e($item->icon) ?>"></i>
                                            <?php endif ?>
                                        </span>

                                        <span class="nexus-menu-item-content">
                                            <span class="header nexus-menu-item-label"><?= e(trans($item->label)) ?></span>
                                            <?php if ($description): ?>
                                                <span class="description nexus-menu-item-description">
                                                    <?= e(trans($description)) ?>
                                                </span>
                                            <?php endif ?>
                                        </span>
                                    </a>

                                    <?php if ($item->counter !== null): ?>
                                        <span
                                            class="counter nexus-menu-item-counter"
                                            data-menu-id="<?= e($key) ?>"
                                            <?php if ($item->counterLabel): ?>
                                                title="<?= e(trans($item->counterLabel)) ?>"
                                            <?php endif ?>>
                                            <?= e($item->counter) ?>
                                        </span>
                                    <?php endif ?>
                                </li>
                            <?php endif ?>
                        <?php endforeach ?>
                    </ul>
                </li>
            <?php endforeach ?>
        </ul>
    </nav>
<?php endif ?>
