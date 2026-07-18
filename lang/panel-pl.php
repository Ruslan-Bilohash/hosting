<?php
/**
 * Polish panel strings — English base with PL overrides.
 */
declare(strict_types=1);

$base = require __DIR__ . '/panel-en.php';
$pl = [
    'panel_dashboard' => 'Panel',
    'panel_websites' => 'Strony',
    'panel_files' => 'Menedżer plików',
    'panel_account' => 'Konto',
    'panel_logout' => 'Wyloguj',
    'panel_installer' => 'Instalator aplikacji',
    'btn_save' => 'Zapisz',
    'btn_cancel' => 'Anuluj',
    'btn_add' => 'Dodaj',
    'btn_delete' => 'Usuń',
    'btn_edit' => 'Edytuj',
    'nav_menu' => 'Menu',
    'domains_subdomains' => 'Subdomeny',
];
return array_replace($base, $pl);
