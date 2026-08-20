# Xitara Nexus Plugin [![Known Vulnerabilities](https://snyk.io/test/github/xitara/wn-nexus-plugin/badge.svg)](https://snyk.io//test/github/xitara/wn-nexus-plugin)

Implements backend sidemenu, custom menus, menu sorting

## Getting started

- clone the repo to folder `plugins/xitara/nexus`
- cd to `plugins/xitara/nexus`
- run `yarn` to fetch all the dependencies

## Commands

- `start` - start the dev server
- `cleanup` - remove compiled data, node_modules, vendor, etc. don't delete any sources
- `watch` - start webpack --watch
- `dwatch` - start webpack --watch --mode development
- `build` - build the complete app including copying static content
- `dbuild` - build the complete app including copying static content with --mode development
- `zip` - zips a package with only needed files without overhead
- `deploy` - deploys a package with only needed files without overhead in a folder without zipping
- `ftp` - uploads a minimizes package to a configured server (needs lftp)
- `analyze` - analyze your production bundle
- `lint-code` - run an ESLint check
- `lint-style` - run a Stylelint check
- `check-eslint-config` - check if ESLint config contains any rules that are unnecessary or conflict with Prettier
- `check-stylelint-config` - check if Stylelint config contains any rules that are unnecessary or conflict with Prettier

## Plugins in die gemeinsame Seitennavigation aufnehmen

Nexus verwendet die von Winter bereitgestellte `registerNavigation()`-Definition als Quelle. Ein Plugin benötigt deshalb weder einen Nexus-spezifischen Boot-Hook noch ein eigenes Sidebar-Partial. Verfügbare Hauptmenüs können im Backend unter **Einstellungen → Seitennavigation** aktiviert und per Drag-and-drop sortiert werden.

### Navigation im Plugin registrieren

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

Nach dem Aktivieren übernimmt Nexus die Side-Menu-Einträge, blendet das zugehörige Hauptmenü aus und ordnet den bisherigen Backend-Kontext der gemeinsamen Navigation zu. Hat ein Hauptmenü kein `sideMenu`, wird es als einzelner Eintrag übernommen.

### Backend-Kontext im Controller

Der Controller verwendet weiterhin unverändert den nativen Kontext seines Plugins:

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

Nexus erkennt außerdem ältere Varianten wie `nexus.[CONTROLLER_SLUG]`, damit bestehende Controller schrittweise migriert werden können.

### Zusätzliche Darstellungsattribute

Optionale `attributes` eines nativen Side-Menu-Eintrags bleiben erhalten. Die Nexus-Seitennavigation unterstützt insbesondere:

- `description`: zweite Textzeile
- `keywords`: zusätzliche Suchbegriffe
- `target`: zum Beispiel `_blank`
- `level`: optische Einrückung (`1` bis `3`)
- `line`: Trennlinie (`top`, `right`, `bottom` oder `left`)
- `bold`: hervorgehobene Beschriftung

### Übergang von `injectSideMenu()`

Die bisherige Methode `public static function injectSideMenu()` und `Xitara\Nexus\Plugin::getSideMenu()` werden vorerst aus Kompatibilitätsgründen unterstützt. Für neue Plugins sollte ausschließlich `registerNavigation()` verwendet werden. Die Zusätze `::hidden`, eigene Nexus-Sidebar-Partials und Reflection-Hooks in `backend.page.beforeDisplay` sind nicht mehr erforderlich.

Ein bestehendes Plugin kann schrittweise migriert werden:

1. Die Einträge aus `injectSideMenu()` nach `registerNavigation()['…']['sideMenu']` verschieben.
2. Im Controller wieder den nativen Plugin-Kontext setzen.
3. Nexus-spezifische Boot-Hooks und `::hidden` entfernen.
4. Den Menübereich in **Einstellungen → Seitennavigation** aktivieren und einsortieren.

## Benutzerdefinierte Menügruppen

Die im Nexus-Backend gepflegten benutzerdefinierten Menüs bleiben verfügbar. Sie erscheinen gemeinsam mit den nativen Plugin-Menüs in der zentralen Auswahl und Sortierung.

## Übersetzungen

- `[VENDOR_SLUG].[PLUGIN_SLUG]::lang.plugin.name` bezeichnet die Gruppenüberschrift.
- `[VENDOR_SLUG].[PLUGIN_SLUG]::lang.submenu.[CONTROLLER]` bezeichnet einen Menüeintrag.

## Legacy-Beispiel: Kontext

Dieser Kontext wird weiterhin erkannt, sollte bei einer Modernisierung aber durch den nativen Kontext aus dem Beispiel oben ersetzt werden:

```php
public function __construct()
{
    parent::__construct();
    BackendMenu::setContext('[VENDOR].[PLUGIN]', '[PLUGIN_SLUG]', 'nexus.[CONTROLLER_SLUG]');
}
```

## Register backend settings
### You must implement your own settings model in your plugin

Register settings to Nexus category
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
