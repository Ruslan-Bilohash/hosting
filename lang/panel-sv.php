<?php
/**
 * Swedish panel strings — English base with SV overrides.
 */
declare(strict_types=1);

$base = require __DIR__ . '/panel-en.php';
$sv = [
    'panel_dashboard' => 'Instrumentpanel',
    'panel_websites' => 'Webbplatser',
    'panel_files' => 'Filhanterare',
    'panel_account' => 'Konto',
    'panel_logout' => 'Logga ut',
    'panel_installer' => 'Appinstallerare',
    'btn_save' => 'Spara',
    'btn_cancel' => 'Avbryt',
    'btn_add' => 'Lägg till',
    'btn_delete' => 'Ta bort',
    'btn_edit' => 'Redigera',
    'nav_menu' => 'Meny',
    'domains_subdomains' => 'Underdomäner',
];
return array_replace($base, $sv);
