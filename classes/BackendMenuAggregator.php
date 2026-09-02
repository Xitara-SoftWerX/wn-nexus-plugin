<?php

namespace Xitara\Nexus\Classes;

use Backend\Classes\MainMenuItem;
use Backend\Classes\NavigationManager;
use Backend\Classes\SideMenuItem;
use Config;
use ReflectionMethod;
use Schema;
use Str;
use System\Classes\PluginManager;
use Throwable;
use Xitara\Nexus\Models\CustomMenu;
use Xitara\Nexus\Models\Menu;

/**
 * Aggregates selected native backend menus under the Nexus navigation context.
 */
class BackendMenuAggregator
{
    public const OWNER = 'Xitara.Nexus';
    public const MAIN_MENU_CODE = 'nexus';

    /** @var array<string, array> */
    protected $legacyItems = [];

    /** @var bool */
    protected $legacyItemsCollected = false;

    /** @var array */
    protected $nexusCustomItems = [];

    /** @var array<string, bool> */
    protected $usedTargetCodes = [];

    public function extend(NavigationManager $manager): void
    {
        $this->collectLegacyItems();
        $this->collectCustomItems();

        $sources = $this->captureSources($manager);
        BackendMenuRegistry::replaceSources($sources);

        $configurations = $this->loadConfigurations();
        $selectedSources = $this->selectedSources($sources, $configurations);
        $parentPermissions = $this->nexusPermissions($manager);
        $hasPublicItem = false;

        foreach ($selectedSources as $source) {
            $definitions = $this->definitionsForSource($source);
            $firstTargetCode = null;

            foreach ($definitions as $sourceCode => $definition) {
                $targetCode = $this->targetCode($source, (string) $sourceCode);
                $definition = $this->normalizeDefinition(
                    $source,
                    (string) $sourceCode,
                    $definition,
                );
                $manager->addSideMenuItem(
                    self::OWNER,
                    self::MAIN_MENU_CODE,
                    $targetCode,
                    $definition,
                );

                $firstTargetCode = $firstTargetCode ?? $targetCode;
                $this->registerContextAliases($source, (string) $sourceCode, $targetCode);

                if (empty($definition['permissions'])) {
                    $hasPublicItem = true;
                } else {
                    $parentPermissions = array_merge(
                        $parentPermissions,
                        $definition['permissions'],
                    );
                }
            }

            if ($firstTargetCode !== null) {
                BackendMenuRegistry::addContextMapping(
                    $source['owner'],
                    $source['main_menu_code'],
                    null,
                    $firstTargetCode,
                );
            }

            $manager->removeMainMenuItem($source['owner'], $source['main_menu_code']);
        }

        $customGroupCounts = [];
        foreach ($this->nexusCustomItems as $code => $definition) {
            $attributes = $definition['attributes'] ?? [];
            $groupReference = (string) ($attributes['group'] ?? 'xitara.nexus.custom');
            $configuration = $configurations['legacy:' . strtolower($groupReference)] ?? null;

            if ($configuration && !$configuration->is_enabled) {
                continue;
            }

            $customGroupCounts[$groupReference] = ($customGroupCounts[$groupReference] ?? 0) + 1;
            $localOrder = $customGroupCounts[$groupReference] - 1;
            $groupSortOrder = $configuration
                ? (int) $configuration->sort_order
                : (int) ($definition['order'] ?? 9999) - $localOrder;

            $attributes['nexusGroupCode'] = 'CUSTOM/' . strtoupper($groupReference);
            $attributes['nexusGroupLabel'] = $attributes['groupLabel'] ?? $groupReference;
            $definition['attributes'] = $attributes;
            $definition['order'] = $groupSortOrder * 1000 + $localOrder;

            $manager->addSideMenuItem(
                self::OWNER,
                self::MAIN_MENU_CODE,
                (string) $code,
                $definition,
            );

            if (empty($definition['permissions'])) {
                $hasPublicItem = true;
            } else {
                $parentPermissions = array_merge($parentPermissions, $definition['permissions']);
            }
        }

        $this->setParentPermissions($manager, $hasPublicItem ? [] : $parentPermissions);
        BackendMenuRegistry::remapCurrentContext();
    }

    /**
     * Compatibility hook used by plugins that still call Nexus::getSideMenu().
     */
    public function addLegacyPluginItems(
        NavigationManager $manager,
        string $owner,
        string $mainMenuCode,
    ): void {
        $this->collectLegacyItems();
        $items = $this->legacyItems[strtoupper($owner)] ?? [];

        if ($items !== []) {
            $manager->addSideMenuItems($owner, $mainMenuCode, $items);
        }
    }

    protected function captureSources(NavigationManager $manager): array
    {
        $sources = [];

        foreach ($manager->listMainMenuItems() as $item) {
            if ($item->owner === self::OWNER && $item->code === self::MAIN_MENU_CODE) {
                continue;
            }

            $label = (string) $item->label;
            $legacyHidden = substr($label, -8) === '::hidden';

            if ($legacyHidden) {
                $label = substr($label, 0, -8);
                $item->label = $label;
            }

            $legacyItems = $this->legacyItems[strtoupper($item->owner)] ?? [];
            $source = [
                'key' => BackendMenuRegistry::sourceKey($item->owner, $item->code),
                'record_code' => Menu::makeNavigationCode($item->owner, $item->code),
                'owner' => $item->owner,
                'main_menu_code' => $item->code,
                'label' => $label,
                'icon' => $item->icon,
                'iconSvg' => $item->iconSvg,
                'url' => $item->url,
                'permissions' => $item->permissions,
                'order' => $item->order,
                'items' => $this->sideMenuDefinitions($item),
                'legacy_items' => $legacyItems,
                'is_legacy' => $legacyHidden || $legacyItems !== [],
                'sort_order' => 999900,
            ];

            $sources[$source['key']] = $source;
        }

        return $sources;
    }

    protected function sideMenuDefinitions(MainMenuItem $mainItem): array
    {
        $definitions = [];

        foreach ($mainItem->sideMenu as $code => $item) {
            $definitions[$code] = $this->sideMenuDefinition($item);
        }

        return $definitions;
    }

    protected function sideMenuDefinition(SideMenuItem $item): array
    {
        return [
            'label' => $item->label,
            'url' => $item->url,
            'icon' => $item->icon,
            'iconSvg' => $item->iconSvg,
            'counter' => $item->counter,
            'counterLabel' => $item->counterLabel,
            'badge' => $item->badge,
            'permissions' => $item->permissions,
            'attributes' => $item->attributes,
            'order' => $item->order,
        ];
    }

    protected function loadConfigurations(): array
    {
        if (!BackendMenuRegistry::menuTableReady()) {
            return [];
        }

        $configurations = [];

        foreach (Menu::whereNotNull('owner')->get() as $menu) {
            $configurations[
                BackendMenuRegistry::sourceKey($menu->owner, $menu->main_menu_code)
            ] = $menu;
        }

        foreach (Menu::whereNull('owner')->get() as $menu) {
            $configurations['legacy:' . strtolower($menu->code)] = $menu;
        }

        return $configurations;
    }

    protected function selectedSources(array $sources, array $configurations): array
    {
        $selected = [];

        foreach ($sources as $source) {
            $configuration =
                $configurations[$source['key']] ??
                ($configurations['legacy:' . strtolower($source['owner'])] ?? null);

            $enabled = $configuration ? (bool) $configuration->is_enabled : $source['is_legacy'];

            if (!$enabled) {
                continue;
            }

            $source['sort_order'] = $configuration
                ? (int) $configuration->sort_order
                : ((int) $source['order']) * 100;
            $selected[] = $source;
        }

        usort($selected, static function (array $left, array $right): int {
            return $left['sort_order'] <=> $right['sort_order'];
        });

        return $selected;
    }

    protected function definitionsForSource(array $source): array
    {
        if ($source['items'] !== []) {
            return $source['items'];
        }

        if ($source['legacy_items'] !== []) {
            return $source['legacy_items'];
        }

        return [
            $source['main_menu_code'] => [
                'label' => $source['label'],
                'url' => $source['url'],
                'icon' => $source['icon'],
                'iconSvg' => $source['iconSvg'],
                'permissions' => $source['permissions'],
                'attributes' => [],
                'order' => 0,
            ],
        ];
    }

    protected function normalizeDefinition(
        array $source,
        string $sourceCode,
        array $definition,
    ): array {
        $attributes = $definition['attributes'] ?? [];
        $groupLabel = $attributes['groupLabel'] ?? ($attributes['group'] ?? $source['label']);
        $groupCode = $source['key'];
        $localOrder =
            isset($definition['order']) && (int) $definition['order'] >= 0
                ? (int) $definition['order']
                : 0;

        $attributes['nexusGroupCode'] = $groupCode;
        $attributes['nexusGroupLabel'] = $groupLabel;
        $attributes['nexusSourceCode'] = $sourceCode;

        $definition['attributes'] = $attributes;
        $definition['permissions'] = !empty($definition['permissions'])
            ? $definition['permissions']
            : $source['permissions'];
        $definition['order'] = (int) $source['sort_order'] * 1000 + $localOrder;

        return $definition;
    }

    protected function targetCode(array $source, string $sourceCode): string
    {
        $owner = strtolower(preg_replace('/[^a-z0-9.]+/i', '-', $source['owner']));
        $item = strtolower(preg_replace('/[^a-z0-9._-]+/i', '-', $sourceCode));
        $target = trim($owner . '.' . $item, '.');

        if (isset($this->usedTargetCodes[$target])) {
            $main = strtolower(preg_replace('/[^a-z0-9._-]+/i', '-', $source['main_menu_code']));
            $target = trim($owner . '.' . $main . '.' . $item, '.');
        }

        $this->usedTargetCodes[$target] = true;

        return $target;
    }

    protected function registerContextAliases(
        array $source,
        string $sourceCode,
        string $targetCode,
    ): void {
        $aliases = [$sourceCode];
        $parts = explode('.', $sourceCode);
        $leaf = end($parts);

        $aliases[] = $leaf;
        $aliases[] = 'nexus.' . $leaf;
        $aliases[] = $source['main_menu_code'] . '.' . $leaf;

        foreach (array_unique($aliases) as $alias) {
            BackendMenuRegistry::addContextMapping(
                $source['owner'],
                $source['main_menu_code'],
                $alias,
                $targetCode,
            );
        }
    }

    protected function collectLegacyItems(): void
    {
        if ($this->legacyItemsCollected) {
            return;
        }

        $this->legacyItemsCollected = true;

        foreach (PluginManager::instance()->getPlugins() as $name => $plugin) {
            if (strtoupper($name) === strtoupper(self::OWNER)) {
                continue;
            }

            $namespace = str_replace('.', '\\', $name) . '\\Plugin';

            if (!method_exists($namespace, 'injectSideMenu')) {
                continue;
            }

            try {
                $method = new ReflectionMethod($namespace, 'injectSideMenu');
                $items = $method->isStatic()
                    ? $plugin::injectSideMenu()
                    : $plugin->injectSideMenu();
            } catch (Throwable $exception) {
                \Log::warning('Unable to collect legacy Nexus menu items from ' . $name, [
                    'exception' => $exception,
                ]);
                continue;
            }

            if (!is_array($items)) {
                continue;
            }

            $this->legacyItems[strtoupper($name)] = $items;
        }
    }

    /**
     * Build Nexus custom links directly instead of routing them through the
     * deprecated plugin-reflection compatibility layer.
     */
    protected function collectCustomItems(): void
    {
        $this->nexusCustomItems = [];

        if (!Schema::hasTable('xitara_nexus_custommenus')) {
            return;
        }

        foreach (
            CustomMenu::where('is_submenu', true)->where('is_active', true)->get()
            as $customMenu
        ) {
            $groupCode = $customMenu->getNexusGroupCode();
            $permissionCodes = array_values(
                array_unique([
                    $customMenu->getPermissionCode(),
                    $customMenu->getLegacyPermissionCode(),
                ]),
            );
            $order = 0;

            foreach ((array) $customMenu->links as $link) {
                if (empty($link['is_active'])) {
                    continue;
                }

                $iconSvg = null;
                if (!empty($link['icon_image'])) {
                    $iconSvg = url(Config::get('cms.storage.media.path') . $link['icon_image']);
                }

                $code = $customMenu->getNavigationNamespace() . '.' . Str::slug($link['text']);
                $this->nexusCustomItems[$code] = [
                    'label' => $link['text'],
                    'url' => $link['link'],
                    'icon' => $link['icon'] ?? null,
                    'iconSvg' => $iconSvg,
                    'permissions' => $permissionCodes,
                    'attributes' => [
                        'group' => $groupCode,
                        'groupLabel' => $customMenu->name,
                        'target' => !empty($link['is_blank']) ? '_blank' : null,
                        'keywords' => $link['keywords'] ?? null,
                        'description' => $link['description'] ?? null,
                    ],
                    'order' => $order++,
                ];
            }
        }
    }

    protected function nexusPermissions(NavigationManager $manager): array
    {
        try {
            $nexus = $manager->getMainMenuItem(self::OWNER, self::MAIN_MENU_CODE);
        } catch (Throwable $exception) {
            return [];
        }

        $permissions = [];

        foreach ($nexus->sideMenu as $item) {
            $permissions = array_merge($permissions, $item->permissions);
        }

        return $permissions;
    }

    protected function setParentPermissions(NavigationManager $manager, array $permissions): void
    {
        try {
            $nexus = $manager->getMainMenuItem(self::OWNER, self::MAIN_MENU_CODE);
            $nexus->permissions = array_values(array_unique($permissions));
        } catch (Throwable $exception) {
            \Log::warning('Unable to update Nexus main menu permissions', [
                'exception' => $exception,
            ]);
        }
    }
}
