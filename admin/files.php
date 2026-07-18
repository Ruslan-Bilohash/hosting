<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-file-manager.php';

hs_admin_require();
$admin_active = 'files';
$admin_fm_mode = true;

// Default to CMS tree (readable on shared hosting). "server" needs full FS access.
$scope = hs_afm_norm_scope((string) ($_GET['scope'] ?? 'cms'));
$rel = hs_fm_norm_rel((string) ($_GET['path'] ?? ''));

$page_title = $t['admin_files_title'] ?? 'Server files';
ob_start();
echo hs_render_admin_files_panel($t, $scope, $rel);
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';