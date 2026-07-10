<?php
declare(strict_types=1);

$panel_active = 'resources';
$panel_load_charts = true;
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/resource-usage.php';

$page_title = $t['resources_title'] ?? 'Resource usage';
$panel_tip_key = 'resources';

$r = $hs_resources;
$history = hs_usage_history($userId, 30);
$series = hs_usage_chart_series($history, 30);
$donut = hs_usage_donut_current($r);
$historyCount = count($history);
$lastRecorded = $historyCount > 0 ? hs_usage_format_label((string) ($history[$historyCount - 1]['ts'] ?? '')) : ($t['resources_no_history'] ?? '—');

$diskSub = hs_h((string) $r['disk_used_gb']) . ' / ' . hs_h((string) $r['disk_max_gb']) . ' GB';
$inodeSub = hs_h($r['inodes_used_fmt'] ?? (string) $r['inodes_used']) . ' / ' . hs_h($r['inodes_max_fmt'] ?? (string) $r['inodes_max']);
$cpuSub = hs_h((string) round((float) ($r['cpu_percent'] ?? 0), 1)) . '%';
$bwSub = hs_h((string) ($r['bandwidth_gb'] ?? 0)) . ' GB';

$chartJson = json_encode([
    'series' => $series,
    'donut' => $donut,
    'i18n' => [
        'disk' => ($t['resources_chart_disk'] ?? 'Disk (MB)'),
        'memory' => ($t['dash_memory'] ?? 'Memory') . ' (MB)',
        'cpu' => ($t['resources_cpu'] ?? 'CPU') . ' %',
        'bandwidth' => ($t['resources_bandwidth'] ?? 'Bandwidth') . ' (GB)',
        'used' => $t['resources_used'] ?? 'Used',
        'free' => $t['resources_free'] ?? 'Free',
    ],
], JSON_UNESCAPED_UNICODE);

ob_start();
?>
<div class="hs-usage-dashboard">
  <div class="hs-usage-stats">
    <?= hs_usage_render_stat_card('fa-hard-drive', $t['resources_disk'] ?? 'Disk', '<span>' . hs_h((string) $r['disk_used_gb']) . ' <small>GB</small></span>', $diskSub, 'disk') ?>
    <?= hs_usage_render_stat_card('fa-folder-tree', $t['resources_inodes'] ?? 'Inodes', hs_h($r['inodes_used_fmt'] ?? (string) $r['inodes_used']), $inodeSub, 'inodes') ?>
    <?= hs_usage_render_stat_card('fa-microchip', $t['resources_cpu'] ?? 'CPU', hs_h((string) round((float) ($r['cpu_display'] ?? 0))) . '<small>%</small>', $cpuSub, 'cpu') ?>
    <?= hs_usage_render_stat_card('fa-gauge-high', $t['dash_memory'] ?? 'Memory', hs_h((string) ($r['memory_mb'] ?? 0)) . '<small> MB</small>', $t['resources_memory_live'] ?? 'Live estimate', 'memory') ?>
  </div>

  <?php if ($historyCount < 2): ?>
  <div class="hs-alert hs-alert-info"><?= hs_h($t['resources_chart_collecting'] ?? '') ?></div>
  <?php endif; ?>

  <p class="hp-muted hs-usage-meta"><i class="fa-solid fa-clock"></i> <?= hs_h($t['resources_last_sample'] ?? 'Last sample') ?>: <strong><?= hs_h($lastRecorded) ?></strong> · <?= (int) $historyCount ?> <?= hs_h($t['resources_samples'] ?? 'samples') ?></p>

  <div class="hs-usage-charts">
    <?= hs_usage_render_chart_box('chart-disk-memory', $t['resources_chart_disk_mem'] ?? 'Disk & memory', $t['resources_chart_disk_mem_hint'] ?? '') ?>
    <div class="hs-usage-charts-row">
      <?= hs_usage_render_chart_box('chart-cpu', $t['resources_chart_cpu'] ?? 'CPU load', null, '220px') ?>
      <?= hs_usage_render_chart_box('chart-bandwidth', $t['resources_chart_bandwidth'] ?? 'Bandwidth', null, '220px') ?>
    </div>
    <div class="hs-usage-charts-side">
      <?= hs_usage_render_chart_box('chart-disk-donut', $t['resources_chart_disk_now'] ?? 'Disk now', null, '240px') ?>
      <?= hs_render_card(
          $t['nav_plan_details'] ?? 'Plan',
          hs_render_kv_table([
              [$t['plan_current'] ?? 'Plan', hs_h($t['plan_' . ($user['plan'] ?? 'starter')] ?? '')],
              [$t['plan_storage_limit'] ?? 'Storage', hs_h((string) $r['storage_max_mb']) . ' MB'],
              [$t['plan_websites_limit'] ?? 'Websites', hs_h((string) $r['sites_used']) . ' / ' . hs_h((string) ($r['sites_max_display'] ?? $r['sites_max']))],
              [$t['resources_bandwidth'] ?? 'Bandwidth', hs_h((string) ($r['bandwidth_gb'] ?? 0)) . ' GB'],
          ]),
          '<a href="' . hs_h(hs_url(hs_panel_path('plan.php'))) . '" class="hs-btn hs-btn-ghost">' . hs_h($t['btn_change_plan'] ?? '') . '</a>'
      ) ?>
    </div>
  </div>

  <?= hs_render_card(
      $t['resources_current_bars'] ?? 'Current usage',
      hs_render_progress($t['dash_disk_usage'] ?? 'Disk', (float) $r['disk_used_gb'], (float) $r['disk_max_gb'], 'GB')
      . hs_render_progress($t['resources_inodes'] ?? 'Inodes', (float) ($r['inodes_used'] ?? 0), (float) ($r['inodes_max'] ?? 1), '')
      . hs_render_progress($t['plan_websites_limit'] ?? 'Sites', (float) $r['sites_used'], (float) ($r['sites_max_display'] ?? $r['sites_max']), '')
  ) ?>
</div>
<script>window.HS_USAGE_CHARTS = <?= $chartJson ?>;</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';