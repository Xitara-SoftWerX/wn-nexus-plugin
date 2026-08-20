# Xitara Nexus Plugin [![Known Vulnerabilities](https://snyk.io/test/github/xitara/wn-nexus-plugin/badge.svg)](https://snyk.io//test/github/xitara/wn-nexus-plugin)

Provides a shared backend side navigation, custom menus, and centralized menu sorting.

## Getting started

- Clone the repository into `plugins/xitara/nexus`.
- Change to the `plugins/xitara/nexus` directory.
- Run `yarn` to install all dependencies.

## Commands

- `start` - Start the development server.
- `cleanup` - Remove generated files, `node_modules`, `vendor`, and other build data without deleting source files.
- `watch` - Start Webpack in watch mode.
- `dwatch` - Start Webpack in development watch mode.
- `build` - Build the complete application and copy static content.
- `dbuild` - Build the complete application in development mode and copy static content.
- `zip` - Create a minimal distribution archive containing only required files.
- `deploy` - Create an unpacked minimal distribution in a target directory.
- `ftp` - Upload a minimized package to a configured server; requires `lftp`.
- `analyze` - Analyze the production bundle.
- `lint-code` - Run ESLint.
- `lint-style` - Run Stylelint.
- `check-eslint-config` - Check the ESLint configuration for unnecessary rules or conflicts with Prettier.
- `check-stylelint-config` - Check the Stylelint configuration for unnecessary rules or conflicts with Prettier.

## Add plugins to the shared side navigation

Nexus uses Winter's native `registerNavigation()` definition as its source. A plugin therefore needs neither a Nexus-specific boot hook nor a custom sidebar partial. Available main navigation items can be enabled and sorted by drag-and-drop under **Settings → Side navigation** in the backend.

### Register navigation in a plugin

```php
public function registerNavigation()
{
    return [
        '[PLUGIN_SLUG]' => [
            'label' => '[VENDOR_SLUG].[PLUGIN_SLUG]::lang.plugin.name',
            'url' => Backend::url('[VENDOR_SLUG]/[PLUGIN_SLUG]/[CONTROLLER_SLUG]'),
            'icon' => 'icon-leaf',
            'permissions' => ['[VENDOR_SLUG].[PLUGIN_SLUG].*'],
            'order' => 500,
            'sideMenu' => [
                '[CONTROLLER_SLUG]' => [
                    'label' => '[VENDOR_SLUG].[PLUGIN_SLUG]::lang.submenu.[CONTROLLER_SLUG]',
                    'url' => Backend::url('[VENDOR_SLUG]/[PLUGIN_SLUG]/[CONTROLLER_SLUG]'),
                    'icon' => 'icon-archive',
                    'permissions' => ['[VENDOR_SLUG].[PLUGIN_SLUG].*'],
                    'order' => 100,
                ],
            ],
        ],
    ];
}
```

After activation, Nexus adds the side menu items to the shared navigation, hides the corresponding main navigation item, and maps the plugin's existing backend context to the shared navigation. If a main navigation item has no `sideMenu`, Nexus adds it as a single side navigation item.

### Set the backend context in a controller

The controller continues to use its plugin's native context without any Nexus-specific changes:

```php
public function __construct()
{
    parent::__construct();
    BackendMenu::setContext(
        '[VENDOR].[PLUGIN]',
        '[PLUGIN_SLUG]',
        '[CONTROLLER_SLUG]'
    );
}
```

> [!WARNING]
> Context values such as `nexus.[CONTROLLER_SLUG]` are deprecated. They are recognized only to support existing controllers during migration.

### Additional presentation attributes

Optional `attributes` on native side menu items are preserved. The Nexus side navigation specifically supports:

- `description`: A secondary line of text.
- `keywords`: Additional search terms.
- `target`: A link target such as `_blank`.
- `level`: Visual indentation from `1` to `3`.
- `line`: A separator on `top`, `right`, `bottom`, or `left`.
- `bold`: An emphasized label.

### Deprecated compatibility API

> [!WARNING]
> `public static function injectSideMenu()`, `Xitara\Nexus\Plugin::getSideMenu()`, `::hidden` label suffixes, custom Nexus sidebar partials, and Reflection hooks in `backend.page.beforeDisplay` are deprecated. They remain available only for backward compatibility. New integrations must use `registerNavigation()`.

Migrate an existing plugin as follows:

1. Move the entries from `injectSideMenu()` to `registerNavigation()['…']['sideMenu']`.
2. Restore the plugin's native context in its controllers.
3. Remove Nexus-specific boot hooks and `::hidden` suffixes.
4. Enable and position the navigation group under **Settings → Side navigation**.

## Custom menu groups

Custom menus managed in the Nexus backend remain available. They appear alongside native plugin menus in the centralized selection and sorting interface.

## Translations

- `[VENDOR_SLUG].[PLUGIN_SLUG]::lang.plugin.name` defines the group heading.
- `[VENDOR_SLUG].[PLUGIN_SLUG]::lang.submenu.[CONTROLLER]` defines a navigation item.

## Deprecated context example

> [!CAUTION]
> The following context format is deprecated. It remains recognized for backward compatibility and must be replaced with the native context shown above.

```php
public function __construct()
{
    parent::__construct();
    BackendMenu::setContext('[VENDOR].[PLUGIN]', '[PLUGIN_SLUG]', 'nexus.[CONTROLLER_SLUG]');
}
```

## Register backend settings

### Implement a settings model in your plugin

Register the settings page in the Nexus category:

```php
public function registerSettings()
{
    $category = '[VENDOR_SLUG].[PLUGIN_SLUG]::lang.settings.label';

    if (PluginManager::instance()->exists('Xitara.Nexus') === true) {
        if (($category = \Xitara\Nexus\Models\Settings::get('menu_text')) == '') {
            $category = 'xitara.nexus::core.settings.name';
        }
    }

    return [
        'settings' => [
            'category' => $category,
            'label' => '[VENDOR_SLUG].[PLUGIN_SLUG]::lang.submenu.label',
            'description' => '[VENDOR_SLUG].[PLUGIN_SLUG]::lang.submenu.description',
            'icon' => 'icon-comments-o',
            'class' => '[VENDOR]\[PLUGIN]\Models\Settings',
            'order' => 20,
        ],
    ];
}
```
