<aside
    class="layout-cell sidenav-tree nexus-sidebar bg-p"
    aria-label="<?= e(trans('xitara.nexus::lang.menu_configuration.navigation_label')) ?>"
    data-control="sidenav-tree"
    data-tree-name="nexus_sidenav_"
    data-search-input="#nexus-menu-search">

    <button
        type="button"
        class="nexus-sidebar-mobile-open"
        data-nexus-sidebar-open
        aria-controls="nexus-sidebar-panel"
        aria-expanded="false">
        <i class="icon-bars" aria-hidden="true"></i>
        <span><?= e(trans('xitara.nexus::core.menu')) ?></span>
    </button>

    <div class="layout nexus-sidebar-panel" id="nexus-sidebar-panel">
        <div class="layout-row min-size nexus-sidebar-toolbar">
            <?= $this->makePartial('$/xitara/nexus/partials/_sidebar_menu_toolbar.htm') ?>

            <button
                type="button"
                class="nexus-sidebar-mobile-close"
                data-nexus-sidebar-close
                aria-label="<?= e(trans('xitara.nexus::lang.menu_configuration.close_navigation')) ?>">
                <i class="icon-times" aria-hidden="true"></i>
            </button>
        </div>

        <div class="layout-row">
            <div class="layout-cell">
                <div class="layout-relative">
                    <div class="layout-absolute">
                        <div
                            class="control-scrollbar drag-scrollbar vertical"
                            data-control="scrollbar"
                            data-disposable="">
                            <?= $this->makePartial('$/xitara/nexus/partials/_sidebar_menu.htm') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
