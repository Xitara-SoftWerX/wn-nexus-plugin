<?php

return [
    'tab' => [
        'default' => 'Sonstiges',
        'menu' => 'Menüeinstellungen',
        'dashboard' => 'Dashboard',
        'exception' => 'Exception-Ansicht',
    ],
    'menu_text' => 'Menütext',
    'menu_text_comment' => 'Text im Hauptmenü. Sollte nicht länger als 20 Zeichen sein',
    'is_compact_display' => 'Kompakte Anzeige',
    'is_compact_display_comment' =>
        'Menüpunkte und Listen werden kompakter, mit weniger Abständen und Zwischenräumen, ausgegeben.',
    'menu_icon' => 'Icon im Hauptmenü',
    'menu_icon_comment' => 'Vorzugsweise ein SVG-Bild, auch Farbig. Mindestgrösse: 30px x 30px',
    'menu_icon_text' => 'Alternativ: Icon aus der OctoberCMS Bibliothek',
    'menu_icon_text_comment' =>
        'Wird ignoriert, wenn ein Bild hochgeladen wurde. Eine Übersicht gibt es <a href="https://octobercms.com/docs/ui/example/icon" target="_blank">hier</a>',
    'dashboard_text' => 'Startseite im Backend ohne Dashboard',
    'dashboard_text_comment' =>
        'Text auf der Startseite, wenn der User keine Dashboard-Berechtigung hat',
    'timezone' => [
        'label' => 'Zeitzone',
        'comment' =>
            'Bei "Systemeinstellung verwenden" wird der Wert aus config/app.php -> timezone verwendet',
        'invalid' => 'Die ausgewählte Zeitzone ist ungültig.',
    ],
    'no_timezone' => '--- Systemeinstellung verwenden ---',
    'default_email' => 'Standard-Mailempfänger',
    'default_email_comment' =>
        'Verbindlicher Empfänger für zukünftige automatische Xitara-Systemmeldungen. Ohne Angabe wird mail.from.address aus der Winter-Konfiguration verwendet.',
    'default_email_name' => 'Angezeigter Name des Standard-Mailempfänger (optional)',
    'default_email_name_comment' =>
        'Ohne Angabe wird mail.from.name aus der Winter-Konfiguration verwendet.',
    'exception' => [
        'enabled' => 'Erweiterte Exception-Ansicht verwenden',
        'enabled_comment' =>
            'Verwendet die Nexus-Ansicht nur, wenn die installierte Winter-Core-View vollständig als kompatibel erkannt wurde. Den persönlichen Editor und das Path Mapping konfigurieren Sie unter Einstellungen → Meine Einstellungen → Backend-Einstellungen im Tab „Exception-Editor“.',
        'compatibility' => 'Kompatibilität',
        'compatibility_comment' =>
            'Geprüft wird die vollständige Datei modules/system/views/exception.php per SHA-256. Bei einem unbekannten Stand verwendet Winter automatisch seine originale View.',
        'compatible' => 'Die installierte Winter-Exception-View ist kompatibel.',
        'incompatible' => 'Die installierte Winter-Exception-View ist nicht kompatibel.',
        'hash' => 'Erkannter SHA-256',
        'build' => 'Winter-Build',
        'incompatible_warning' =>
            'Die erweiterte Exception-Ansicht wurde deaktiviert, weil sich die Winter-CMS-Core-Datei modules/system/views/exception.php geändert hat. Bis die Kompatibilität geprüft wurde, wird automatisch die originale Winter-Exception-Ansicht verwendet.',
        'detected_hash' => 'Erkannter SHA-256: :hash.',
        'winter_build' => 'Winter-Build: :build.',
        'editor_tab' => 'Exception-Editor',
        'editor_section' => 'Editor-Links in Exception-Seiten',
        'editor_section_comment' =>
            'Diese Einstellungen gelten nur für den aktuell angemeldeten Backend-Benutzer. Alle Presets werden unabhängig von Server, Browser und Betriebssystem angeboten.',
        'editor' => 'Editor-Protokoll',
        'editor_comment' => 'Ohne Auswahl werden Dateipfade und Zeilen ohne Editor-Link angezeigt.',
        'editor_none' => 'Keine Editor-Links',
        'editor_custom' => 'Benutzerdefiniert',
        'custom_name' => 'Anzeigename',
        'custom_name_comment' =>
            'Optionaler Name des benutzerdefinierten Editors oder Protocol-Handlers.',
        'custom_template' => 'URL-Template',
        'custom_template_comment' =>
            'Erlaubte Platzhalter: {file}, {line} und {column}. {file} ist erforderlich; Zeile und Spalte fallen bei fehlenden Angaben auf 1 zurück.',
        'path_mapping' => 'Optionales Path Mapping',
        'path_mapping_comment' =>
            'Beide Pfade leer lassen, um den Serverpfad unverändert zu verwenden. Ersetzt wird ausschließlich ein vollständiger Pfadpräfix.',
        'server_path' => 'Serverpfad',
        'server_path_comment' => 'Beispiel: /var/www/winter',
        'local_path' => 'Lokaler Editorpfad',
        'local_path_comment' => 'Beispiel: C:\\Projects\\winter',
    ],
];
