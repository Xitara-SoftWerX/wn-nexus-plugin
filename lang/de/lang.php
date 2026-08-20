<?php

return [
    'plugin' => [
        'name' => 'Xitara Nexus',
        'description' => 'Nexus-Plugin für alle Xitara-Plugins, inkl. Backend Seitenmenü',
        'author' => 'Xitara, Manuel Burghammer',
        'homepage' => 'https://xitara.com',
    ],
    'submenu' => [
        'label' => 'Xitara Nexus',
    ],
    'nexus' => [
        'mainmenu' => 'Hauptmenü',
        'dashboard' => 'Dashboard',
        'menu' => 'Menü-Sortierung',
    ],
    'settings' => [
        'label' => 'Grundeinstellungen',
        'description' => 'Einstellungen global für alle Plugins',
    ],
    'menu_configuration' => [
        'label' => 'Seitennavigation',
        'description' => 'Backend-Menübereiche auswählen und sortieren',
        'heading' => 'Seitennavigation konfigurieren',
        'help' => 'Aktivierte Menübereiche werden unter Nexus zusammengefasst. Ziehen Sie die Einträge, um die Reihenfolge der Gruppen festzulegen.',
        'refresh' => 'Menübereiche aktualisieren',
        'refreshed' => 'Die verfügbaren Menübereiche wurden aktualisiert.',
        'enabled' => '":name" wird im Nexus-Menü angezeigt.',
        'disabled' => '":name" wird nicht mehr im Nexus-Menü angezeigt.',
        'native' => 'Winter-Navigation',
        'custom' => 'Benutzerdefiniert',
        'move' => 'Menübereich :name verschieben',
        'toggle' => 'Menübereich :name ein- oder ausblenden',
        'navigation_label' => 'Gemeinsame Backend-Navigation',
        'search_label' => 'Navigation durchsuchen',
        'close_navigation' => 'Navigation schließen',
    ],
    'install' => [
        'heading' => 'Installation des Xitara Nexus nicht abgeschlossen',
        'text' => 'Vor Beginn müssen einige Grundeinstellungen abgeschlossen werden.',
        'button' => 'Einstellungen',
    ],
    'custommenu' => [
        'label' => 'Benutzerdefinierte Menüs',
        'name' => [
            'label' => 'Name',
            'comment' => 'Der Name bzw. die Überschrift des Menüs',
        ],
        'namespace' => [
            'label' => 'Namespace',
            'comment' => 'Der Namespace des Menüs. Wird für die Zuordnung der Menüpunkte und die Übersetzung benötigt. (optional)',
        ],
        'is_submenu' => 'Im Seitenmenü anzeigen',
        'is_active' => 'Aktiv',
        'links' => 'Links',
        'link' => 'Link',
        'text' => 'Text',
        'is_blank' => 'Link im neuen Fenster/Tab öffnen',
        'icon' => 'Icon',
        'icon_comment' => 'Entweder aus dem <a href="https://octobercms.com/docs/ui/icon" target="_blank">Iconpool</a> oder eine Datei aus dem Medienpool. Beides ist optional.',
    ],
    'deleteAccount' => [
        'label' => 'Account löschen',
        'comment' => 'Der Account wird deaktiviert. Anschliessend ist kein Login mehr möglich. Nach 14 Tagen wird der Account unwiderruflich gelöscht.',
        'confirm' => 'Wollen Sie ihren Account wirklich löschen?',
    ],
];
