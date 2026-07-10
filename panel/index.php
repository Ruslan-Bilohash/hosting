<?php
declare(strict_types=1);

$panel_active = 'dashboard';
$panel_hide_tip = true;
$panel_load_charts = true;
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/installer.php';
require_once dirname(__DIR__) . '/includes/resource-usage.php';
require_once dirname(__DIR__) . '/includes/launch-checklist.php';
require_once dirname(__DIR__) . '/includes/panel-admin-dashboard.php';
$page_title = $t['nav_dashboard'] ?? 'Dashboard';

$domain = hs_plan_display_domain($user, $hs_user_settings);
$planId = (string) ($user['plan'] ?? 'starter');
$created = hs_format_date((string) ($user['created_at'] ?? '2026-02-18'));
$createdDate = preg_match('/^\d{4}-\d{2}-\d{2}/', (string) ($user['created_at'] ?? ''))
    ? substr((string) $user['created_at'], 0, 10)
    : '2026-02-18';
$r = $hs_resources;
$userId = (string) ($user['id'] ?? '');
$usage24 = hs_usage_chart_series_hours(hs_usage_history_hours($userId, 24), 24);
$dashChartJson = json_encode([
    'series' => $usage24,
    'i18n' => [
        'disk' => ($t['resources_chart_disk'] ?? 'Disk (MB)'),
        'memory' => ($t['dash_memory'] ?? 'Memory') . ' (MB)',
        'cpu' => ($t['resources_cpu'] ?? 'CPU') . ' %',
    ],
], JSON_UNESCAPED_UNICODE);
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hs_csrf_verify($_POST['csrf'] ?? null)) {
    if (isset($_POST['clear_cache'])) {
        require_once dirname(__DIR__) . '/includes/performance.php';
        hs_perf_clear_cache($user);
        hs_user_settings_save((string) $user['id'], ['cache_cleared_at' => date('c')]);
        $flash = $t['dash_cache_cleared'] ?? 'Cache cleared';
    } elseif (isset($_POST['launch_dismiss'])) {
        $cur = is_array($hs_user_settings['launch_checklist'] ?? null) ? $hs_user_settings['launch_checklist'] : [];
        hs_user_settings_save((string) $user['id'], [
            'launch_checklist' => array_merge(hs_launch_checklist_defaults(), $cur, [
                'dismissed' => true,
                'dismissed_at' => gmdate('c'),
            ]),
        ]);
        $hs_user_settings = hs_user_settings_get((string) $user['id']);
    }
}

ob_start();
?>
<?php if ($flash !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($flash) ?></div><?php endif; ?>

<?php if ($hs_is_platform_admin && !hs_impersonation_active()): ?>
<?= hs_panel_admin_dashboard_block($t, $lang) ?>
<?php endif; ?>

<div class="hp-dash-wrap">
<?= hs_render_launch_checklist($user, $hs_user_settings, $hs_sites, $t) ?>
<section class="hp-dash-hero">
  <div class="hp-dash-hero-top">
    <a href="<?= hs_h(hs_url(hs_panel_path('plan.php'))) ?>" class="hs-btn hs-btn-ghost hp-dash-change-plan"><?= hs_h($t['btn_change_plan'] ?? '') ?></a>
  </div>
  <div class="hp-dash-hero-main">
    <div class="hp-dash-hero-icon"><i class="fa-solid fa-globe"></i></div>
    <div>
      <h2 class="hp-dash-domain"><?= hs_h($domain) ?></h2>
      <p class="hp-dash-created"><?= hs_h($t['dash_created'] ?? '') ?> <?= hs_h($createdDate) ?></p>
    </div>
  </div>
  <div class="hp-dash-hero-links">
    <a href="<?= hs_h(hs_url(hs_panel_path('domains.php'))) ?>" class="hp-dash-link-btn"><i class="fa-solid fa-globe"></i> <?= hs_h($t['dash_manage_domain'] ?? '') ?></a>
    <a href="<?= hs_h(hs_url(hs_panel_path('email.php'))) ?>" class="hp-dash-link-btn"><i class="fa-solid fa-envelope"></i> <?= hs_h($t['dash_manage_email'] ?? '') ?></a>
  </div>
</section>

<?= hs_panel_site_details_card($domain, $user, $t) ?>

<h2 class="hp-dash-section-title"><?= hs_h($t['dash_essentials'] ?? '') ?></h2>
<div class="hp-dash-essentials">
  <?= hs_render_essential_tile(
      'fa-database',
      $t['dash_db_title'] ?? 'Database',
      $t['dash_db_desc'] ?? '',
      '<a href="' . hs_h(hs_url(hs_panel_path('databases.php'))) . '" class="hs-btn hs-btn-primary hp-dash-btn-sm">' . hs_h($t['dash_btn_manage'] ?? '') . '</a>'
  ) ?>
  <?= hs_render_essential_tile(
      'fa-clock-rotate-left',
      $t['dash_backups'] ?? 'Backups',
      $t['dash_backups_daily'] ?? 'Daily',
      '<a href="' . hs_h(hs_url(hs_panel_path('backups.php'))) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm">' . hs_h($t['dash_btn_manage'] ?? '') . '</a>'
  ) ?>
  <?= hs_render_essential_tile(
      'fa-folder-open',
      $t['dash_files'] ?? 'File manager',
      $t['dash_files_desc'] ?? '',
      '<a href="' . hs_h(hs_url(hs_panel_path('files.php'))) . '" class="hs-btn hs-btn-primary hp-dash-btn-sm">' . hs_h($t['dash_btn_open'] ?? '') . '</a>'
  ) ?>
  <?= hs_render_essential_tile(
      'fa-bolt',
      $t['dash_cache'] ?? 'Cache',
      $t['dash_cache_desc'] ?? '',
      '<form method="post" class="hp-dash-cache-form">' . hs_csrf_field()
      . '<button type="submit" name="clear_cache" value="1" class="hs-btn hs-btn-ghost hp-dash-btn-sm">' . hs_h($t['dash_cache_clear'] ?? '') . '</button>'
      . (count($hs_sites) > 0
          ? '<a href="' . hs_h(hs_public_url_for_site($user, $hs_sites[0])) . '?nocache=' . time() . '" target="_blank" rel="noopener" class="hs-btn hs-btn-ghost hp-dash-btn-sm">' . hs_h($t['dash_cache_preview'] ?? '') . '</a>'
          : '<span class="hs-btn hs-btn-ghost hp-dash-btn-sm" style="opacity:.5;cursor:default">' . hs_h($t['dash_cache_preview'] ?? '') . '</span>')
      . '</form>'
  ) ?>
</div>

<section class="hp-dash-plan">
  <span class="hp-dash-plan-label"><?= hs_h($t['dash_hosting_plan'] ?? '') ?></span>
  <strong><?= hs_h(hs_plan_hosting_label($planId, $t)) ?></strong>
</section>

<div class="hp-dash-bottom">
  <section class="hp-card hp-dash-perf-card">
    <div class="hp-card-title hp-dash-card-head">
      <span><?= hs_h($t['nav_performance'] ?? '') ?></span>
      <a href="<?= hs_h(hs_url(hs_panel_path('performance.php'))) ?>" class="hp-dash-text-link"><?= hs_h($t['dash_perf_check'] ?? '') ?></a>
    </div>
    <div class="hp-card-body hp-dash-perf-body">
      <?= hs_render_perf_score(
          $t['dash_perf_desktop'] ?? 'Desktop',
          (int) ($r['perf_desktop'] ?? 96),
          $t['dash_perf_last_scan'] ?? '',
          (string) ($r['perf_desktop_scan'] ?? '')
      ) ?>
      <?= hs_render_perf_score(
          $t['dash_perf_mobile'] ?? 'Mobile',
          (int) ($r['perf_mobile'] ?? 74),
          $t['dash_perf_last_scan'] ?? '',
          (string) ($r['perf_mobile_scan'] ?? '')
      ) ?>
    </div>
  </section>

  <section class="hp-card hp-dash-res-card">
    <div class="hp-card-title hp-dash-card-head">
      <span><?= hs_h($t['dash_resource_plan'] ?? '') ?></span>
      <a href="<?= hs_h(hs_url(hs_panel_path('resources.php'))) ?>" class="hp-dash-text-link"><?= hs_h($t['dash_btn_details'] ?? '') ?></a>
    </div>
    <div class="hp-card-body">
      <p class="hp-muted hp-dash-res-period"><i class="fa-solid fa-chart-line"></i> <?= hs_h($t['dash_memory_period'] ?? 'Last 24 hours') ?></p>
      <div class="hp-dash-res-grid">
        <?= hs_render_resource_stat(
            $t['dash_disk_usage'] ?? '',
            hs_h((string) ($r['disk_used_gb'] ?? '1')) . ' GB <span class="hp-dash-res-sep">/</span> ' . hs_h((string) ($r['disk_max_gb'] ?? '200')) . ' GB'
        ) ?>
        <?= hs_render_resource_stat(
            $t['resources_inodes'] ?? 'Inodes',
            hs_h((string) ($r['inodes_used_fmt'] ?? '0')) . ' <span class="hp-dash-res-sep">/</span> ' . hs_h((string) ($r['inodes_max_fmt'] ?? '600K'))
        ) ?>
        <?= hs_render_resource_stat(
            $t['plan_websites_limit'] ?? 'Sites',
            hs_h((string) ($r['sites_used'] ?? 0)) . ' <span class="hp-dash-res-sep">/</span> ' . hs_h((string) ($r['sites_max_display'] ?? 100))
        ) ?>
        <?= hs_render_resource_stat(
            $t['resources_cpu'] ?? 'CPU',
            hs_h((string) ($r['cpu_display'] ?? 1)) . ' %'
        ) ?>
        <?= hs_render_resource_stat(
            $t['dash_memory'] ?? 'Memory',
            hs_h((string) ($r['memory_mb'] ?? 78)) . ' MB'
        ) ?>
      </div>
      <div class="hp-dash-chart-wrap"><canvas id="dash-usage-24h"></canvas></div>
    </div>
  </section>
</div>
<script>window.HS_DASH_USAGE_CHART = <?= $dashChartJson ?>;</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';