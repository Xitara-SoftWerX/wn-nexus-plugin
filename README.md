# Xitara Nexus Plugin [![Known Vulnerabilities](https://snyk.io/test/github/xitara/wn-nexus-plugin/badge.svg)](https://snyk.io//test/github/xitara/wn-nexus-plugin)

Provides a shared backend side navigation, custom menus, and centralized menu sorting.

## Getting started

- Clone the repository into `plugins/xitara/nexus`.
- Change to the `plugins/xitara/nexus` directory.
- Run `yarn` to install all dependencies.

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

## Commands

These commands are intended for plugin development and maintenance. Run them from the plugin directory with `yarn <command>`.

### Setup and local development

| Command     | Function                                                                                                                                                                                                                |
| ----------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `setup`     | Creates `scripts/config.cjs` from `scripts/config.sample.cjs` when it does not exist, then installs the Composer dependencies. Review the generated deployment configuration before using packaging or upload commands. |
| `prepare`   | Initializes Husky. This package lifecycle command normally runs automatically after installing JavaScript dependencies.                                                                                                 |
| `start`     | Starts the Webpack development server, opens it in the default browser, writes generated files to disk, includes development-only entry points, and enables source maps without minification.                           |
| `build`     | Creates a minified production build in `assets/`, copies files from `static/`, and generates the asset manifest and Webpack statistics.                                                                                 |
| `dbuild`    | Creates an unminified development build with source maps, `.debug.js` filenames, and development-only entry points.                                                                                                     |
| `watch`     | Runs the production build in watch mode.                                                                                                                                                                                |
| `dwatch`    | Runs the development build in watch mode.                                                                                                                                                                               |
| `build-all` | Runs a development build followed by a production build. The production pass enables removal of the configured functions and console methods through Terser.                                                            |
| `analyze`   | Creates a production build and opens Webpack Bundle Analyzer with `assets/stats.json`.                                                                                                                                  |

### Code quality and tests

| Command          | Function                                                                                                                                                                                           |
| ---------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `lint`           | Runs `lint-js`, `lint-ts`, `lint-style`, `lint-php`, `lint-php-style`, and `typecheck` in sequence.                                                                                                |
| `lint-js`        | Runs ESLint on `scripts/`, `test/`, `webpack/`, and JavaScript files in the project root.                                                                                                          |
| `lint-ts`        | Runs ESLint on TypeScript files in `src/ts/`.                                                                                                                                                      |
| `lint-style`     | Runs Stylelint on CSS and SCSS files in `src/`.                                                                                                                                                    |
| `lint-php`       | Runs `php -l` on project PHP files, excluding generated assets, dependencies, documentation output, distribution files, and static copies. Optional file arguments limit the check to those files. |
| `lint-php-style` | Checks PHP code style with PHP CS Fixer, shows a diff, and does not modify files.                                                                                                                  |
| `lint-fix`       | Applies ESLint, Stylelint, PHP CS Fixer, and Prettier fixes. This command modifies files across the project.                                                                                       |
| `typecheck`      | Runs the TypeScript compiler without emitting files.                                                                                                                                               |
| `format`         | Formats the project with Prettier and writes the changes.                                                                                                                                          |
| `format-check`   | Checks Prettier formatting without modifying files.                                                                                                                                                |
| `test-unit`      | Runs all `test/**/*.test.js` files with Node's built-in test runner.                                                                                                                               |
| `test-build`     | Verifies the fixed artifact set defined in `scripts/check-build.js`, checks for unwanted stylesheet loader scripts, and validates Bootstrap and prefixed Tailwind markers in the generated CSS.    |
| `test`           | Runs linting, formatting checks, unit tests, a production build, and the build artifact checks in sequence.                                                                                        |

> [!NOTE]
> `test-build` currently expects `assets/css/breakpoints.css`, `assets/css/styles.css`, and `assets/css/tailwind.css`, although the corresponding entry points are commented out in `webpack.meta.js`. Align the entry points and artifact checker before relying on this command or the aggregate `test` command.

### Packaging, deployment, and documentation

The packaging and deployment commands use `scripts/config.cjs`. They can create local distribution files or modify a configured remote system.

| Command  | Function                                                                                                                                                                                                                                                      |
| -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `zip`    | Runs a production build, creates a timestamped distribution directory from the configured file list, installs optimized Composer dependencies without development packages inside it, and creates a ZIP archive. Requires the system `zip` command.           |
| `upload` | Uploads the newest matching distribution directory, archive, or both according to `UPLOAD_TYPE`. Supports FTP, FTPS, SCP, and SFTP and can optionally change ownership, back up and rename the remote directory, and execute a remote post-deployment script. |
| `deploy` | Runs `zip` and then `upload`, creating and deploying a new package in one operation.                                                                                                                                                                          |
| `docs`   | Generates PHP API documentation in `.docs/api` with phpDocumentor and opens the generated index in the configured or platform-default browser.                                                                                                                |

### Maintenance

| Command   | Function                                                                                                                                                                                                      |
| --------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `ncu`     | Updates dependency version ranges in `package.json` with npm-check-updates. It does not install the updated dependencies.                                                                                     |
| `cleanup` | Recursively deletes generated assets, caches, documentation and distribution output, the generated `config/` copy, installed Composer and JavaScript dependencies, lockfiles, and other generated root files. |

> [!CAUTION]
> `cleanup` deletes `node_modules/`, `vendor/`, `yarn.lock`, `composer.lock`, `assets/`, `dist/`, and additional generated paths. Review `scripts/cleanup.js` before running it when local build or dependency state must be preserved.
