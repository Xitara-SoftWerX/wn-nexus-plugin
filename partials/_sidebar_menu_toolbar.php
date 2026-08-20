<div class="layout control-toolbar nexus-sidebar-search" role="search">
    <div class="layout-cell">
        <div class="toolbar-item size-input-text">
            <label class="sr-only" for="nexus-menu-search">
                <?= e(trans('xitara.nexus::lang.menu_configuration.search_label')) ?>
            </label>
            <i class="icon-search nexus-sidebar-search-icon" aria-hidden="true"></i>
            <input
                type="search"
                name="nexus_menu_search"
                class="form-control nexus-sidebar-search-input"
                id="nexus-menu-search"
                placeholder="<?= e(trans('system::lang.settings.search')) ?>"
                autocomplete="off"
                spellcheck="false">
        </div>
    </div>
</div>
