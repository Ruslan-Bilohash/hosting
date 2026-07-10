<?php
declare(strict_types=1);

$panel_active = 'plan';
$GLOBALS['panel_plan_change_mode'] = true;
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/plan-page-ui.php';

$page_title = $t['nav_plan_details'] ?? 'Plan details';
$panel_tip_key = 'plan';

$tab = ($_GET['tab'] ?? 'overview') === 'technical' ? 'technical' : 'overview';
$ctx = [
    't' => $t,
    'lang' => $lang,
    'user' => $user,
    'hs_user_settings' => $hs_user_settings,
    'resources' => $hs_resources,
];

ob_start();
?>
<nav class="hs-plan-tabs">
  <a href="<?= hs_h(hs_url(hs_panel_path('plan.php'))) ?>" class="hs-plan-tab<?= $tab === 'overview' ? ' is-active' : '' ?>">
    <i class="fa-solid fa-layer-group"></i> <?= hs_h($t['plan_tab_overview'] ?? 'Overview') ?>
  </a>
  <a href="<?= hs_h(hs_url(hs_panel_path('plan.php'), ['tab' => 'technical'])) ?>" class="hs-plan-tab<?= $tab === 'technical' ? ' is-active' : '' ?>">
    <i class="fa-solid fa-server"></i> <?= hs_h($t['plan_tab_technical'] ?? 'Technical details') ?>
  </a>
</nav>
<?php
echo $tab === 'technical' ? hs_plan_page_technical($ctx) : hs_plan_page_overview($ctx);
echo hs_plan_change_modal_shell($t, $lang);
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';