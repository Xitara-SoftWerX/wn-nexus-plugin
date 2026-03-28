<?php

return [
    'tab' => [
        'default' => 'Sonstiges',
        'menu' => 'Menüeinstellungen',
        'dashboard' => 'Dashboard',
    ],
    'menu_text' => 'Menütext',
    'menu_text_comment' => 'Text im Hauptmenü. Sollte nicht länger als 20 Zeichen sein',
    'is_compact_display' => 'Kompakte Anzeige',
    'is_compact_display_comment' => 'Menüpunkte und Listen werden kompakter, mit weniger Abständen und Zwischenräumen, ausgegeben.',
    'is_twig_filters' => 'Zusätzliche Twig-Filter aktivieren',
    'is_twig_filters_comment' => 'Aktiviert auf Wunsch zusätzlich Filteri n Twig wie z.B. Linkerzeugung für Telefon und E-Mail',
    'menu_icon' => 'Icon im Hauptmenü',
    'menu_icon_comment' => 'Vorzugsweise ein SVG-Bild, auch Farbig. Mindestgrösse: 30px x 30px',
    'menu_icon_text' => 'Alternativ: Icon aus der OctoberCMS Bibliothek',
    'menu_icon_text_comment' => 'Wird ignoriert, wenn ein Bild hochgeladen wurde. Eine Übersicht gibt es <a href="https://octobercms.com/docs/ui/example/icon" target="_blank">hier</a>',
    'dashboard_text' => 'Startseite im Backend ohne Dashboard',
    'dashboard_text_comment' => 'Text auf der Startseite, wenn der User keine Dashboard-Berechtigung hat',
    'timezone' => [
        'label' => 'Zeitzone',
        'comment' => 'Bei "Systemeinstellung verwenden" wird der Wert aus config/app.php -> timezone verwendet',
    ],
    'no_timezone' => '--- Systemeinstellung verwenden ---',
    'default_email' => 'Standard-Mailempfänger',
    'default_email_comment' => 'Die E-Mail Adresse, die als Empfänger für automatische Mails fungiert, z.B. für Statusmeldungen etc. Wenn nicht angeben, wird die Mailadresse aus config/mail.php ($from) benutzt',
    'default_email_name' => 'Angezeigter Name des Standard-Mailempfänger (optional)',
];
