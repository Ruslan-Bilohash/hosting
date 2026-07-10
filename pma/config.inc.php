<?php
declare(strict_types=1);

/**
 * phpMyAdmin — signon-only via Hosting CMS panel (per-client MySQL user).
 */
$root = dirname(__DIR__);
require_once $root . '/config.php';
require_once $root . '/includes/phpmyadmin.php';

$cfg['blowfish_secret'] = hs_pma_blowfish_secret();
$cfg['DefaultLang'] = 'en';
$cfg['ServerDefault'] = 1;
$cfg['UploadDir'] = '';
$cfg['SaveDir'] = '';
$cfg['TempDir'] = hs_pma_temp_dir();
$cfg['ThemeDefault'] = 'pmahomme';
$cfg['AllowArbitraryServer'] = false;
$cfg['ShowPhpInfo'] = false;
$cfg['CheckConfigurationPermissions'] = false;

$i = 0;
$i++;
$cfg['Servers'][$i]['auth_type'] = 'signon';
$cfg['Servers'][$i]['SignonSession'] = HS_PMA_SIGNON_SESSION;
$cfg['Servers'][$i]['SignonURL'] = hs_pma_signon_url();
$cfg['Servers'][$i]['LogoutURL'] = hs_pma_logout_url();
$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;
$cfg['Servers'][$i]['DisableIS'] = true;
$cfg['Servers'][$i]['hide_db'] = '^(information_schema|performance_schema|mysql|sys)$';