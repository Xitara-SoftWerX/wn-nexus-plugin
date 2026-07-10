<!-- Sidebar menu -->
<?php
    $sideMenuItems = BackendMenu::listSideMenuItems();

if ($sideMenuItems):
    $collapsedGroups = explode('|', isset($_COOKIE['sidenav_tree']) ? $_COOKIE['sidenav_tree'] : null);

    $categories = [];
    foreach ($sideMenuItems as $sideItemCode => $item) {
        if (isset($item->attributes['group'])) {
            $item->group = e(trans($item->attributes['group']));
        }

        if (strpos($sideItemCode, 'custommenulist.') !== false) {
            $sub = explode('.', $sideItemCode);
            $item->group = ucfirst($sub[1] ?? 'no_text');

            if (isset($item->attributes['groupLabel'])) {
                $item->group = $item->attributes['groupLabel'];
            }
        }

        if (!isset($item->group)) {
            $sub = explode('.', $item->code);
            $item->group = e(trans('xitara.' . $sub[0] . '::lang.submenu.label'));
        }

        /**
         * if permissions are given, show item only on have access
         */
        if (!empty($item->permissions)) {
            foreach ($item->permissions as $permission) {
                if ($this->user->hasAccess($permission)) {
                    $categories[$item->group][$sideItemCode] = $item;
                }
            }
        } else {
            /**
             * if no permissions are given, show item to everyone
             */
            $categories[$item->group][$sideItemCode] = $item;
        }
    }
// exit;
?>
    <ul class="top-level">
        <?php foreach ($categories as $category => $items):
            $collapsed = empty($_COOKIE['sidenav_tree']) ? true : in_array($category, $collapsedGroups);
            ?>
        <li data-group-code="<?= e($category); ?>"
            <?= $collapsed ? 'data-status="collapsed"' : null; ?>
        >
            <div class="group">
                <h3><?= e(trans($category)); ?></h3>
            </div>
            <ul>
            <?php foreach ($items as $key => $item): ?>
                <?php if (!isset($item->hidden) || $item->hidden == false): ?>
                    <li class="
                        <?= BackendMenu::isSideMenuItemActive($item) ? 'active' : null; ?>
                        level-<?= isset($item->attributes['level']) ? $item->attributes['level'] : 1; ?>
                        <?= isset($item->attributes['line']) ? ' border-' . $item->attributes['line'] : null; ?>
                        "
                        data-keywords="<?= e(trans($item->attributes['keywords'] ?? '')); ?>">
                        <?= (isset($item->attributes['bold']) && $item->attributes['bold'] === true) ? '<b>' : null ?>
                        <a href="<?= $item->url; ?>" target="<?= $item->attributes['target'] ?? '_self';?>">
                            <?php if ($item->iconSvg === null): ?>
                                <i class="sidebar-menu-item <?= $item->icon; ?>"></i>
                            <?php else: ?>
                                <img src="<?= $item->iconSvg; ?>">
                            <?php endif; ?>
                            <span class="header"><?= e(trans($item->label)); ?></span>
                            <span class="description">
                                <?= e(trans($item->attributes['description'] ?? '')); ?>
                            </span>
                        </a>
                        <?= (isset($item->attributes['bold']) && $item->attributes['bold'] === true) ? '</b>' : null ?>
                        <?php if ($item->counter > 0) : ?>
                            <span
                                class="counter"
                                title="<?= $item->counterLabel ;?>"
                                data-menu-id="<?= $key ;?>"
                            >
                                <?= $item->counter ;?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
            </ul>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

<script>
    $(document).ready(function () {
        /**
         * Add click-listener to the li elements with the data-group-code attribute
         * and write cookie accordingly
         */
        $('li[data-group-code]').each(function () {
            $(this).on('click', function (e) {
                console.log('click');

                if ($(e.target).is('a')) {
                    return;
                }

                let groupCode = $(this).data('group-code');
                let collapsedGroups = $('li[data-group-code][data-status="collapsed"]').map(function () {
                    return $(this).data('group-code');
                }).get();

                if ($(this).attr('data-status') === 'collapsed') {
                    // $(this).removeAttr('data-status');
                    collapsedGroups = collapsedGroups.filter(function (code) {
                        return code !== groupCode;
                    });
                } else {
                    // $(this).attr('data-status', 'collapsed');
                    collapsedGroups.push(groupCode);
                }

                document.cookie = 'sidenav_tree=' + collapsedGroups.join('|') + '; path=/';
            });
        });
    });
</script>
