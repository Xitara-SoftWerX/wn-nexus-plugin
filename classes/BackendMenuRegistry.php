<?php

namespace Xitara\Nexus\Classes;

use BackendMenu;
use Carbon\Carbon;
use Schema;
use Xitara\Nexus\Models\CustomMenu;
use Xitara\Nexus\Models\Menu;

/**
 * Request-local catalog of native backend navigation sources.
 */
class BackendMenuRegistry
{
    /** @var array<string, array> */
    protected static $sources = [];

    /** @var array<string, string> */
    protected static $contextMap = [];

    /** @var string[] */
    protected static $customMenuCodes = [];

    /** @var bool|null */
    protected static $menuTableReady;

    public static function replaceSources(array $sources): void
    {
        static::$sources = $sources;
        static::$contextMap = [];
    }

    public static function sources(): array
    {
        return static::$sources;
    }

    public static function source(string $owner, string $mainMenuCode): ?array
    {
        return static::$sources[static::sourceKey($owner, $mainMenuCode)] ?? null;
    }

    public static function sourceKey(string $owner, string $mainMenuCode): string
    {
        return strtoupper($owner) . '/' . $mainMenuCode;
    }

    public static function customMenuCodes(): array
    {
        return static::$customMenuCodes;
    }

    public static function addContextMapping(
        string $owner,
        string $mainMenuCode,
        ?string $sourceSideMenuCode,
        string $targetSideMenuCode,
    ): void {
        static::$contextMap[
            static::contextKey($owner, $mainMenuCode, $sourceSideMenuCode)
        ] = $targetSideMenuCode;
    }

    public static function remapCurrentContext(): void
    {
        $context = BackendMenu::getContext();

        if (!$context->owner || !$context->mainMenuCode) {
            return;
        }

        $exactKey = static::contextKey(
            $context->owner,
            $context->mainMenuCode,
            $context->sideMenuCode,
        );
        $defaultKey = static::contextKey($context->owner, $context->mainMenuCode, null);
        $target = static::$contextMap[$exactKey] ?? (static::$contextMap[$defaultKey] ?? null);

        if ($target === null) {
            return;
        }

        BackendMenu::setContext('Xitara.Nexus', 'nexus', $target);
    }

    /**
     * Persist the currently discovered catalog when the configuration screen is opened.
     */
    public static function syncSources(): void
    {
        if (!static::menuTableReady()) {
            return;
        }

        $nextSortOrder = ((int) Menu::max('sort_order')) + 100;
        $seenAt = Carbon::now();

        foreach (static::$sources as $source) {
            $model = Menu::where('owner', $source['owner'])
                ->where('main_menu_code', $source['main_menu_code'])
                ->first();

            if ($model === null) {
                $model = Menu::whereNull('owner')
                    ->where('code', strtolower($source['owner']))
                    ->first();
            }

            if ($model === null) {
                $model = new Menu();
                $model->code = Menu::makeNavigationCode(
                    $source['owner'],
                    $source['main_menu_code'],
                );
                $model->sort_order = $nextSortOrder;
                $model->is_enabled = $source['is_legacy'];
                $nextSortOrder += 100;
            }

            $model->owner = $source['owner'];
            $model->main_menu_code = $source['main_menu_code'];
            $model->source_type = 'navigation';
            $model->name = $source['label'];
            $model->last_seen_at = $seenAt;
            $model->save();
        }

        static::syncCustomMenus();
    }

    public static function menuTableReady(): bool
    {
        if (static::$menuTableReady !== null) {
            return static::$menuTableReady;
        }

        return static::$menuTableReady =
            Schema::hasTable('xitara_nexus_menus') &&
            Schema::hasColumns('xitara_nexus_menus', [
                'owner',
                'main_menu_code',
                'source_type',
                'is_enabled',
                'last_seen_at',
            ]);
    }

    protected static function syncCustomMenus(): void
    {
        static::$customMenuCodes = [];

        if (!Schema::hasTable('xitara_nexus_custommenus')) {
            return;
        }

        $nextSortOrder = ((int) Menu::max('sort_order')) + 100;

        foreach (CustomMenu::where('is_submenu', true)->get() as $customMenu) {
            $groupCode = $customMenu->getNexusGroupCode();
            static::$customMenuCodes[] = $groupCode;
            $menu = Menu::firstOrNew(['code' => $groupCode]);

            if (!$menu->exists) {
                $menu->sort_order = $nextSortOrder;
                $menu->is_enabled = true;
                $nextSortOrder += 100;
            }

            $menu->owner = null;
            $menu->main_menu_code = null;
            $menu->source_type = 'custom';
            $menu->name = $customMenu->name;
            $menu->save();
        }
    }

    protected static function contextKey(
        string $owner,
        ?string $mainMenuCode,
        ?string $sideMenuCode,
    ): string {
        return strtoupper($owner) . '|' . ($mainMenuCode ?? '') . '|' . ($sideMenuCode ?? '');
    }
}
