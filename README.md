# Xitara Nexus Plugin [![Known Vulnerabilities](https://snyk.io/test/github/xitara/wn-nexus-plugin/badge.svg)](https://snyk.io//test/github/xitara/wn-nexus-plugin)

Nexus provides a shared Winter CMS backend side navigation, centralized menu selection and sorting, custom link groups, a configurable dashboard, and an optional compatible exception view with per-user editor links.

## Requirements

- PHP 8.2 or newer
- Winter CMS 1.2 or newer

## Configuration

The global plugin settings are available under **Settings → Xitara Nexus → Basic settings**. The category name may differ when a custom menu label has been configured.

### Menu settings

| Setting                                          | Effect                                                                                                                                             |
| ------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Menu text** (`menu_text`)                      | Changes the Nexus main-navigation label, the heading of Nexus' own side-menu group, and the category used for the plugin's backend settings.       |
| **Compact display** (`is_compact_display`)       | Loads the dedicated `assets/css/compact.css` build on backend pages to reduce menu and list spacing.                                               |
| **Main-menu icon upload** (`menu_icon_uploaded`) | Uses a privately stored PNG, BMP, JPG, GIF, or SVG as the Nexus main-menu icon.                                                                    |
| **Icon class** (`menu_icon_text`)                | Uses a Winter icon class such as `icon-leaf`. It is ignored when an uploaded icon exists. If neither option is set, the bundled Nexus SVG is used. |

### Dashboard setting

**Backend start page without dashboard permission** (`dashboard_text`) is rich text shown to users who can open the Nexus start page but do not have the `xitara.nexus.dashboard` permission.

### Exception-view setting

**Use extended exception view** (`extended_exception_view_enabled`) globally opts the installation into the Nexus exception view. The settings page also shows the detected Winter build, the SHA-256 hash of Winter's Core view, and the current compatibility state.

The personal editor and optional path mapping are not global plugin settings. Every backend user configures them under **Settings → My settings → Backend preferences → Exception editor**. On an installation with the standard backend URI, this page is `/backend/backend/preferences`.

See [Optional exception editor links](#optional-exception-editor-links) for the compatibility and editor details.

### Default email values

| Setting                                             | Stored value                                                                  |
| --------------------------------------------------- | ----------------------------------------------------------------------------- |
| **Default mail recipient** (`default_email`)        | Canonical recipient address for future automated Xitara system notifications. |
| **Displayed recipient name** (`default_email_name`) | Optional display name for that recipient.                                     |

Consumers use `Xitara\Nexus\Models\Settings::getNotificationRecipient()`, which returns `email` and `name`. Empty values fall back to Winter's `mail.from.address` and `mail.from.name` configuration.

### Locale time zones with Winter.Translate

When `Winter.Translate` is installed, Nexus extends its locale form with a **Time zone** dropdown. Selecting **Use system setting** stores no locale-specific value. `Plugin::getTimezone()` returns the selected locale time zone and otherwise falls back to `config/app.php`.

Version 2.4.0 stores these assignments in the Nexus-owned `xitara_nexus_locale_timezones` table, keyed by locale code. Winter.Translate may be installed before or after Nexus; its locale form is extended automatically on the next request after both plugins have completed their migrations.

### Permissions

Nexus registers the following backend permissions:

| Permission                       | Purpose                                                                                                                                         |
| -------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `xitara.nexus.mainmenu`          | Makes the Nexus main navigation available when no more specific Nexus permission applies.                                                       |
| `xitara.nexus.settings`          | Opens the global basic settings.                                                                                                                |
| `xitara.nexus.dashboard`         | Shows the report-widget dashboard instead of the configured fallback text.                                                                      |
| `xitara.nexus.menu`              | Opens the side-navigation selection and sorting page.                                                                                           |
| `xitara.nexus.custommenus`       | Shows the custom-menu management item in the Nexus navigation.                                                                                  |
| `xitara.nexus.custommenu.<slug>` | Canonical per-menu permission generated for each saved custom menu. Namespace-based legacy keys remain accepted for the 2.4 transition release. |

Permissions declared by aggregated native menu items are carried over to the corresponding entries. The Nexus parent item receives the combined child permissions so it remains visible whenever the user may access at least one contained item.

## Shared backend side navigation

Nexus treats native Winter `registerNavigation()` definitions as its canonical navigation sources. Under **Settings → Xitara Nexus → Side navigation**, authorized users can:

- refresh the catalog of currently installed navigation sources;
- enable or disable complete navigation groups; and
- arrange enabled groups using drag and drop.

Discovery is request-local during ordinary backend requests. It is persisted when the configuration page is opened or its refresh action is used. A previously unknown native source is disabled by default; legacy Nexus sources retain their historical opt-in behavior. Disabling a native source removes it only from the Nexus aggregation, so Winter can continue to show its original main-navigation item.

For each enabled source, Nexus:

- moves its native side-menu entries below the Nexus main item;
- uses the source's main item as a single side-menu entry when no `sideMenu` exists;
- preserves its labels, URLs, icons, counters, permissions, attributes, and local order;
- removes the original main-menu item after aggregation; and
- remaps the source plugin's current backend context to the generated Nexus entry.

### Sidebar behavior

The shared sidebar groups entries by source, is searchable, and is responsive. Groups can be collapsed and their state is retained in the `nexus_sidenav_groupStatus` cookie; the group containing the active page is always expanded. On narrow screens the panel can be opened and closed with dedicated buttons, by clicking the backdrop, or with <kbd>Esc</kbd>.

The rendered navigation supports active-page state, SVG or icon-font icons, descriptions, keyword-only search terms, counters with labels, indentation, separators, emphasized labels, and external targets. Links opened with `_blank` receive `rel="noopener noreferrer"`.

## Custom menu groups

**Nexus → Custom menus** manages link groups that can appear alongside native plugin menus. A custom menu provides:

- a name and generated slug;
- an optional namespace used to derive its navigation group and item identifiers;
- switches for activating the record and showing it in the side navigation; and
- a repeatable link list.

Each link has text, a URL, an optional Winter icon class or media-library SVG, an active switch, and a switch for opening in a new tab. Only active links from active menus marked for sidebar display are contributed. Sidebar groups created this way also appear in the centralized selection and sorting page.

Saving, renaming, changing the namespace of, hiding, or deleting a custom sidebar menu keeps its associated sorting record synchronized.

## Dashboard

Nexus removes Winter's original dashboard main-menu item and redirects the backend index to the Nexus dashboard. Users with `xitara.nexus.dashboard` receive Winter's original report-widget UI inside the Nexus side-menu context. The container uses Winter's normal `dashboard` preference context, Core stylesheet, AJAX lifecycle, widget inspector, sorting, add/remove actions, and default widget configuration, so existing report widgets and user layouts remain available.

Users without `xitara.nexus.dashboard` receive the configured rich-text fallback instead. The report-container AJAX handler independently enforces the dashboard permission.

To replace only the default widget layout, add `config/dashboard.yaml` to the active theme. Existing per-user dashboard layouts continue to take precedence through Winter's normal widget preferences.

## Backend user extensions

Nexus extends Winter's backend user management in two ways:

- Superusers can assign every backend role; other backend users only see roles that are not marked as system roles.
- A non-superuser editing **My account** receives a **Delete account** action. After confirmation, Nexus marks this specific self-deletion request, fires `backend.user.beforeDelete`, soft-deletes the account, logs the user out, and redirects to the backend entry page.

Soft deletion disables login immediately. A daily scheduled task permanently deletes only Nexus-marked backend accounts whose request is at least 14 days old. Other backend users soft-deleted through Winter remain untouched; restoring a marked account during the retention period cancels its permanent deletion.

## Optional exception editor links

Nexus can replace Winter's standalone detailed exception view with a compatible copy. In the extended view, the exception file and files in the stack trace can open directly at the reported line and, where available, column in a locally installed editor. Winter's `modules/system/views/exception.php` remains untouched.

### Compatibility and fallback

Enabling the global switch records that the installation should use the extended view whenever it is compatible with the installed Winter version. Nexus prepends its view only when the complete SHA-256 hash of Winter's installed exception view is on the reviewed compatibility list.

An unknown or unreadable Core view, a missing plugin view, a hash or cache failure, or any unexpected error leaves Winter's original view active. The configured switch remains enabled so a later Nexus update can reactivate the feature automatically. Authenticated backend users receive one compatibility warning per session while the override cannot be used.

### Configure the editor for a backend user

The following presets are available to every backend user regardless of the server or browser operating system:

- VS Code
- VS Code Insiders
- Cursor
- PhpStorm
- TextMate
- BBEdit
- Custom

Selecting **Custom** allows a valid protocol URL template containing the required `{file}` placeholder. The optional `{line}` and `{column}` placeholders default to `1` when no usable position is available. For example:

```text
xdebug://open?url=file://{file}&line={line}
subl://open?file={file}&line={line}&column={column}
```

Unsafe schemes, control characters, unknown placeholders, and malformed templates are rejected. Nexus URL-encodes file paths before inserting them into preset or custom templates. Without a valid personal editor configuration, the extended view renders normal unlinked file and position information.

### Map server paths to local paths

Path mapping is optional and is configured on the same **Exception editor** tab. It is useful when Winter and the local editor use different project roots. For example:

```text
Server path: /var/www/winter
Local path:  C:\Projects\winter
```

This maps `/var/www/winter/plugins/xitara/foo/Plugin.php` to `C:\Projects\winter\plugins\xitara\foo\Plugin.php` before the editor URL is generated. Nexus replaces only a complete path prefix and preserves the separator style of the local root.

## Other active runtime services

### Global PHP helpers

`helpers.php` is loaded for frontend, backend, and console requests and provides:

| Helper                                      | Result                                                            |
| ------------------------------------------- | ----------------------------------------------------------------- |
| `media_url($path = '')`                     | Absolute URL below Winter's configured media path.                |
| `plugins_url($path = '')`                   | Absolute URL below Winter's configured plugin path.               |
| `array_search_value($array, $search, $key)` | First matching row plus its original index as `__key`, or `null`. |
| `array_sort_value($array, $key)`            | A copy of the input sorted ascending by the specified row key.    |

### Browser URL variables

`GET /xitara/nexus/jsvars.js` returns JavaScript that assigns the absolute media and plugin base URLs to `winterUrl.media` and `winterUrl.plugins`.

### Scheduled account cleanup

Winter's scheduler runs the account-retention cleanup daily. The previous every-minute Docker diagnostic callback has been removed.

## Integrating another plugin

### Register native navigation

Define the main and side navigation through Winter's normal `registerNavigation()` method:

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

After activation, open the side-navigation settings, refresh the sources if necessary, enable the new group, and place it at the desired position.

### Keep the native backend context

Controllers continue to use their own plugin's native context:

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

Nexus maps the exact side-menu code as well as common historical aliases. Context values such as `nexus.[CONTROLLER_SLUG]` remain recognized for migration but are deprecated.

### Additional presentation attributes

Optional `attributes` on native side-menu items are preserved. The Nexus renderer uses these keys:

| Attribute              | Purpose                                                                      |
| ---------------------- | ---------------------------------------------------------------------------- |
| `description`          | Secondary line below the label.                                              |
| `keywords`             | Additional terms considered by sidebar search.                               |
| `target`               | Link target such as `_blank`.                                                |
| `level`                | Visual indentation; values start at `1`.                                     |
| `line`                 | Separator on `top`, `right`, `bottom`, or `left`.                            |
| `bold`                 | Emphasizes the item label.                                                   |
| `group` / `groupLabel` | Optional legacy grouping metadata. Native sources are grouped automatically. |

Winter's standard `counter`, `counterLabel`, `icon`, and `iconSvg` values are also carried over. Although `badge` metadata is copied to the aggregated menu item, the current Nexus sidebar partial does not render it.

### Register backend settings in the Nexus category

Another plugin may reuse the configured Nexus category while retaining a fallback when Nexus is not installed:

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
            'class' => '[VENDOR]\\[PLUGIN]\\Models\\Settings',
            'order' => 20,
        ],
    ];
}
```

### Translation keys

- `[VENDOR_SLUG].[PLUGIN_SLUG]::lang.plugin.name` commonly defines a source-group heading.
- `[VENDOR_SLUG].[PLUGIN_SLUG]::lang.submenu.[CONTROLLER]` commonly defines a side-menu item.
- Nexus passes all labels, group labels, descriptions, counter labels, and search keywords through Winter's translator before rendering.

### Utility methods

`Xitara\Nexus\Plugin` exposes two additional static methods:

- `Plugin::slug($title, $separator = '-', $language = null)` uses the session locale and then `app.locale` when no language is supplied.
- `Plugin::getTimezone($localeCode = null)` returns the Translate locale's Nexus time zone or the application default. It requires the Winter.Translate classes at runtime.

### Scoped attachment paths

`Models\ScopedFile` and `Traits\HasScopedAttachments` allow an attachment relation to append a validated subdirectory to Winter's normal storage and public paths. A relation must use `ScopedFile` and declare `path`:

```php
use Xitara\Nexus\Traits\HasScopedAttachments;

class Example extends Model
{
    use HasScopedAttachments;

    public $attachOne = [
        'document' => [\Xitara\Nexus\Models\ScopedFile::class, 'path' => 'documents/contracts'],
    ];
}
```

Only letters, digits, slashes, underscores, and hyphens are accepted. Empty paths use Winter's default location; any path containing `..` is rejected. Nexus itself does not currently use this trait.

The implementation uses PHP 8 language and library features within the supported PHP 8.2 baseline.

### Deprecated compatibility API

> [!WARNING]
> `Plugin::getSideMenu()`, `Plugin::getMenuOrder()`, external `injectSideMenu()` providers, `::hidden` label suffixes, plugin-specific Nexus sidebar partials, and reflection-based discovery remain for the 2.4 transition release only. New integrations must use native `registerNavigation()` definitions. Removal is planned for Nexus 3.0.

The global `media_url()`, `plugins_url()`, `array_search_value()`, and `array_sort_value()` helpers and `/xitara/nexus/jsvars.js` are also deprecated as of 2.4.0. No active Xitara consumer remains, but these APIs stay available throughout the 2.x line for unknown external consumers and are planned for removal in 3.0.

To migrate an older integration:

1. Move its entries from `injectSideMenu()` to the plugin's native `registerNavigation()['…']['sideMenu']` definition.
2. Restore the native owner, main-menu code, and side-menu code in its controllers.
3. Remove Nexus-specific boot hooks, custom sidebar partials, and `::hidden` suffixes.
4. Refresh, enable, and position the source under **Settings → Xitara Nexus → Side navigation**.

Deprecated context example:

```php
public function __construct()
{
    parent::__construct();
    BackendMenu::setContext('[VENDOR].[PLUGIN]', '[PLUGIN_SLUG]', 'nexus.[CONTROLLER_SLUG]');
}
```

## Companion plugins and frontend bundles

- `Xitara.TwigExtender` is the sole owner of the former Nexus Twig filters and their CSS-parser and QR-code dependencies.
- Font Awesome integration is provided by the separate `Xitara.FontAwesome` plugin.
- Nexus no longer contains a PWA component or service worker. A future PWA implementation belongs in a separate plugin.
- `assets/js/app.js` adds `js-enabled` to the frontend `<body>` when loaded; Nexus does not add this bundle to CMS pages automatically.
- `assets/js/backend.js` initializes responsive sidebar controls and reflects menu-toggle state after normal and AJAX rendering.

The retained TypeScript tree also contains reusable browser utilities. They are source-level helpers rather than a stable public API, and most are not imported by an active entry point.

## Installation and development

Place the repository in `plugins/xitara/nexus`. The supported runtime is PHP 8.2 or newer with Winter CMS 1.2 or newer. The development toolchain expects Node `26.5.1` from `.nvmrc` and Yarn 1.

From the plugin directory, install JavaScript dependencies and then initialize Composer dependencies and the local script configuration:

```bash
nvm use
yarn
yarn setup
```

`yarn setup` creates the ignored local `scripts/config.cjs` from its sample when necessary. Review that file before using packaging or upload commands.

## Yarn commands

Run these commands from the plugin directory with `yarn <command>`.

### Setup and local development

| Command     | Function                                                                                                                                                                                      |
| ----------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `setup`     | Creates `scripts/config.cjs` when missing and installs Composer dependencies.                                                                                                                 |
| `prepare`   | Initializes Husky. This lifecycle command normally runs automatically after installing JavaScript dependencies.                                                                               |
| `start`     | Starts the Webpack development server, opens it in the default browser, writes generated files to disk, includes development-only entry points, and enables source maps without minification. |
| `build`     | Creates a minified production build in `assets/`, copies files from `static/`, and generates the asset manifest and Webpack statistics.                                                       |
| `dbuild`    | Creates an unminified development build with source maps, `.debug.js` filenames, and development-only entry points.                                                                           |
| `watch`     | Runs the production build in watch mode.                                                                                                                                                      |
| `dwatch`    | Runs the development build in watch mode.                                                                                                                                                     |
| `build-all` | Runs a development build followed by a production build. The production pass enables removal of the configured functions and console methods through Terser.                                  |
| `analyze`   | Creates a production build and opens Webpack Bundle Analyzer with `assets/stats.json`.                                                                                                        |

### Code quality and tests

| Command            | Function                                                                                                                                                                                                                                  |
| ------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `lint`             | Runs `lint-js`, `lint-ts`, `lint-style`, `lint-php`, `lint-php-style`, and `typecheck` in sequence.                                                                                                                                       |
| `lint-js`          | Runs ESLint on `scripts/`, `test/`, `webpack/`, and JavaScript files in the project root.                                                                                                                                                 |
| `lint-ts`          | Runs ESLint on TypeScript files in `src/ts/`.                                                                                                                                                                                             |
| `lint-style`       | Runs Stylelint on CSS and SCSS files in `src/`.                                                                                                                                                                                           |
| `lint-php`         | Runs `php -l` on project PHP files, excluding generated assets, dependencies, documentation output, distribution files, and static copies. Optional file arguments limit the check.                                                       |
| `lint-php-style`   | Checks PHP code style with PHP CS Fixer, shows a diff, and does not modify files.                                                                                                                                                         |
| `lint-fix`         | Applies ESLint, Stylelint, PHP CS Fixer, and Prettier fixes. This command modifies files across the project.                                                                                                                              |
| `typecheck`        | Runs the TypeScript compiler without emitting files.                                                                                                                                                                                      |
| `format`           | Formats the project with Prettier and writes the changes.                                                                                                                                                                                 |
| `format-check`     | Checks Prettier formatting without modifying files.                                                                                                                                                                                       |
| `test-integration` | Runs transactional database checks for locale time zones, navigation permissions, dashboard authorization, and the complete 14-day backend-user deletion lifecycle. It requires the Nexus 2.4 migrations and rolls all test records back. |
| `test-unit`        | Runs the Node unit tests and PHP checks for exception-view compatibility, editor links, URL encoding, and path mapping.                                                                                                                   |
| `test-build`       | Verifies the configured artifact set, rejects unwanted stylesheet loader scripts, and validates the generated compact backend stylesheet.                                                                                                 |
| `test`             | Runs linting, formatting checks, unit tests, a production build, and build-artifact checks in sequence.                                                                                                                                   |

### Packaging, deployment, and documentation

The packaging and deployment commands use `scripts/config.cjs`. They can create local distribution files or modify a configured remote system.

| Command  | Function                                                                                                                                                                                                                                                      |
| -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `zip`    | Runs a production build, creates a timestamped distribution directory from the configured file list, installs optimized Composer dependencies without development packages inside it, and creates a ZIP archive. Requires the system `zip` command.           |
| `upload` | Uploads the newest matching distribution directory, archive, or both according to `UPLOAD_TYPE`. Supports FTP, FTPS, SCP, and SFTP and can optionally change ownership, back up and rename the remote directory, and execute a remote post-deployment script. |
| `deploy` | Runs `zip` and then `upload`, creating and deploying a new package in one operation.                                                                                                                                                                          |
| `docs`   | Generates PHP API documentation in `.docs/api` with phpDocumentor and opens the generated index in the configured or platform-default browser.                                                                                                                |

### Maintenance

| Command               | Function                                                                                                                                                                                                      |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `ncu`                 | Updates dependency version ranges in `package.json` with npm-check-updates. It does not install the updated dependencies.                                                                                     |
| `update-browserslist` | Updates the `caniuse-lite` browser compatibility data in `yarn.lock` and reports whether the configured target browsers changed.                                                                              |
| `cleanup`             | Recursively deletes generated assets, caches, documentation and distribution output, the generated `config/` copy, installed Composer and JavaScript dependencies, lockfiles, and other generated root files. |

> [!CAUTION]
> `cleanup` deletes `node_modules/`, `vendor/`, `yarn.lock`, `composer.lock`, `assets/`, `dist/`, and additional generated paths. Review `scripts/cleanup.js` before running it when local build or dependency state must be preserved.
