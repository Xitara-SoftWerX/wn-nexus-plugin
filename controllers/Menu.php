<?php

namespace Xitara\Nexus\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;
use Lang;
use Xitara\Nexus\Classes\BackendMenuRegistry;
use Xitara\Nexus\Models\Menu as MenuModel;

/**
 * Menu Back-end Controller
 */
class Menu extends Controller
{
    public $requiredPermissions = ['xitara.nexus.menu'];

    public $implement = [
        'Backend.Behaviors.ReorderController',
    ];

    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Xitara.Nexus', 'nexus', 'nexus.menu');

        // This fills the request-local catalog before it is persisted. Normal
        // backend requests never write discovered navigation sources.
        BackendMenu::listMainMenuItems();
        BackendMenuRegistry::syncSources();

        $this->pageTitle = 'xitara.nexus::core.submenu.menu_order';
    }

    public function onToggleSource(): void
    {
        $menu = MenuModel::findOrFail((string) post('code'));
        $menu->is_enabled = !$menu->is_enabled;
        $menu->save();

        Flash::success(Lang::get(
            $menu->is_enabled
                ? 'xitara.nexus::lang.menu_configuration.enabled'
                : 'xitara.nexus::lang.menu_configuration.disabled',
            ['name' => $menu->display_name]
        ));
    }

    public function onRefreshSources()
    {
        BackendMenuRegistry::syncSources();
        Flash::success(Lang::get('xitara.nexus::lang.menu_configuration.refreshed'));

        return \Redirect::refresh();
    }

    public function reorderExtendQuery($query): void
    {
        $navigationSources = BackendMenuRegistry::sources();
        $customMenuCodes = BackendMenuRegistry::customMenuCodes();

        $query->where(function ($query) use ($navigationSources, $customMenuCodes) {
            foreach ($navigationSources as $source) {
                $query->orWhere(function ($query) use ($source) {
                    $query->where('owner', $source['owner'])
                        ->where('main_menu_code', $source['main_menu_code']);
                });
            }

            if ($customMenuCodes !== []) {
                $query->orWhereIn('code', $customMenuCodes);
            }
        });
    }
}
