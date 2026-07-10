<?php
declare(strict_types=1);

require_once __DIR__ . '/impersonation.php';
require_once __DIR__ . '/panel-domains.php';

/** @var array $t */
/** @var array $user */
/** @var string $lang */
/** @var array $hs_user_settings */
$panel_active = $panel_active ?? 'dashboard';
$page_title = $page_title ?? ($t['nav_dashboard'] ?? '');
$panel_tip_key = $panel_tip_key ?? $panel_active;
$panel_hide_tip = $panel_hide_tip ?? !empty($GLOBALS['panel_hide_tip']);
$panel_fm_mode = !empty($GLOBALS['panel_fm_mode']);
$panel_support_mode = !empty($GLOBALS['panel_support_mode']);
$panel_landing_mode = !empty($GLOBALS['panel_landing_mode']);
$panel_landing_focus = !empty($GLOBALS['panel_landing_focus']);
$panel_speed_mode = !empty($GLOBALS['panel_speed_mode']);
$panel_perf_mode = !empty($GLOBALS['panel_perf_mode']);
$panel_plan_change_mode = !empty($GLOBALS['panel_plan_change_mode']);
$panel_domains_mode = !empty($GLOBALS['panel_domains_mode']);
$panel_domains_pending_mode = !empty($GLOBALS['panel_domains_pending_mode']);
$panel_databases_mode = !empty($GLOBALS['panel_databases_mode']);
$user = is_array($user ?? null) ? $user : [];
$hs_user_settings = is_array($hs_user_settings ?? null) ? $hs_user_settings : hs_user_settings_defaults();
$hs_is_platform_admin = $hs_is_platform_admin ?? hs_is_platform_admin($user);
$nav_groups = hs_panel_nav_groups_for_admin($t, $hs_is_platform_admin && !hs_impersonation_active());
$search_items = hs_panel_search_items($t);
$hs_active_domain = $hs_active_domain ?? hs_active_domain($hs_user_settings);
$hs_domain_choices = $hs_domain_choices ?? hs_user_domain_choices($hs_user_settings);
$nav_open_slug = hs_panel_nav_open_slug($panel_active);
$lang_meta = hs_langs()[$lang] ?? hs_langs()['en'];
$client_label = hs_client_display_name($user);
?>
<!DOCTYPE html>
<html lang="<?= hs_h($lang_meta['html'] ?? 'en') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="theme-color" content="#059669">
<title><?= hs_h($page_title) ?> — <?= hs_h($t['brand'] ?? '') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400..700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
<link rel="stylesheet" href="<?= hs_h(hs_asset('css/app.css')) ?>">
<?php if ($panel_support_mode): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" crossorigin="anonymous">
<?php endif; ?>
</head>
<body class="hs-panel hp-panel<?= $panel_fm_mode ? ' hs-fm-page' : '' ?><?= $panel_support_mode ? ' hs-support-page' : '' ?><?= $panel_landing_focus ? ' hs-landing-focus-page' : '' ?>" data-hp-acc-open="<?= hs_h($nav_open_slug) ?>">
<?php if (hs_impersonation_active()): ?>
<div class="hp-impersonate-bar">
  <span><i class="fa-solid fa-user-secret"></i> <?= hs_h(str_replace(['{admin}', '{client}'], [hs_impersonator_label(), $client_label], $t['impersonate_banner'] ?? '')) ?></span>
  <a href="<?= hs_h(hs_url(hs_panel_path('stop-impersonate.php'))) ?>" class="hp-impersonate-exit"><?= hs_h($t['impersonate_exit'] ?? '') ?></a>
  <?php if (hs_impersonation_from_admin()): ?><span class="hp-muted" style="margin-left:.75rem;font-size:.8rem"><?= hs_h($t['impersonate_admin_note'] ?? '') ?></span><?php endif; ?>
</div>
<?php endif; ?>
<div class="hs-overlay" data-hs-overlay></div>
<?php if (!$panel_landing_focus): ?>
<aside class="hs-sidebar hp-sidebar" data-hs-sidebar>
  <a href="<?= hs_h(hs_url(hs_panel_path(''))) ?>" class="hp-sidebar-brand">
    <span class="hp-sidebar-brand-mark"><i class="fa-solid fa-server"></i></span>
    <?= hs_h($t['brand'] ?? 'Hosting CMS') ?>
  </a>

  <div class="hp-domain-drop" data-hp-domain>
    <button type="button" class="hp-domain-btn" data-hp-domain-btn aria-haspopup="listbox">
      <span class="hp-domain-btn-label"><?= hs_h($t['site_name_label'] ?? '') ?></span>
      <span class="hp-domain-btn-value"><i class="fa-solid fa-globe"></i> <?= hs_h($hs_active_domain) ?></span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <div class="hp-domain-menu" data-hp-domain-menu hidden>
      <?php foreach ($hs_domain_choices as $dom): ?>
        <a href="<?= hs_h(hs_domain_switch_url($dom)) ?>" class="<?= $dom === $hs_active_domain ? 'active' : '' ?>" role="option">
          <i class="fa-solid fa-globe"></i> <?= hs_h($dom) ?>
        </a>
      <?php endforeach; ?>
      <a href="<?= hs_h(hs_url(hs_panel_path('domains.php'))) ?>" class="hp-domain-add"><i class="fa-solid fa-plus"></i> <?= hs_h($t['btn_add_domain'] ?? '') ?></a>
    </div>
  </div>

  <nav class="hp-nav hp-nav-accordion" data-hp-accordion>
    <?php foreach ($nav_groups as $gi => $group): ?>
      <?php if (($group['type'] ?? '') === 'item'): ?>
        <ul class="hs-nav-list hp-nav-standalone">
          <?php foreach ($group['items'] ?? [] as $item): ?>
            <li>
              <a href="<?= hs_h(hs_url($item['url'])) ?>" class="<?= ($panel_active === $item['key']) ? 'active' : '' ?>">
                <i class="<?= hs_h(!empty($item['icon_brand']) ? 'fa-brands ' . $item['icon'] : 'fa-solid ' . $item['icon']) ?>"></i>
                <?= hs_h($item['label']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php elseif (($group['type'] ?? '') === 'group'): ?>
        <?php
          $slug = (string) ($group['slug'] ?? 'g' . $gi);
          $isOpen = $nav_open_slug === $slug;
          $gIcon = (string) ($group['icon'] ?? 'fa-folder');
          $gBrand = !empty($group['icon_brand']);
        ?>
        <div class="hp-acc-group<?= $isOpen ? ' is-open' : '' ?>" data-hp-acc-group="<?= hs_h($slug) ?>">
          <button type="button" class="hp-acc-trigger" aria-expanded="<?= $isOpen ? 'true' : 'false' ?>">
            <i class="<?= hs_h($gBrand ? 'fa-brands ' . $gIcon : 'fa-solid ' . $gIcon) ?>"></i>
            <span><?= hs_h($group['label'] ?? '') ?></span>
            <i class="fa-solid fa-chevron-down hp-acc-chevron"></i>
          </button>
          <ul class="hs-nav-list hp-acc-panel">
            <?php foreach ($group['items'] ?? [] as $item): ?>
              <li>
                <a href="<?= hs_h(hs_url($item['url'])) ?>" class="<?= ($panel_active === $item['key']) ? 'active' : '' ?>">
                  <i class="<?= hs_h(!empty($item['icon_brand']) ? 'fa-brands ' . $item['icon'] : 'fa-solid ' . $item['icon']) ?>"></i>
                  <?= hs_h($item['label']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    <ul class="hs-nav-list hp-nav-foot">
      <li>
        <a href="<?= hs_h(hs_url(hs_panel_path('support.php'))) ?>" class="<?= ($panel_active === 'site-support') ? 'active' : '' ?>">
          <i class="fa-solid fa-headset"></i><?= hs_h($t['nav_support'] ?? $t['tab_site_support'] ?? 'Support') ?>
        </a>
      </li>
      <li>
        <a href="<?= hs_h(hs_url('logout.php')) ?>" class="hp-nav-logout">
          <i class="fa-solid fa-right-from-bracket"></i><?= hs_h($t['panel_logout'] ?? '') ?>
        </a>
      </li>
    </ul>
  </nav>
</aside>
<?php endif; ?>
<div class="hs-main hp-main<?= hs_impersonation_active() ? ' hp-has-impersonate' : '' ?><?= $panel_landing_focus ? ' hs-landing-focus-main' : '' ?>">
  <?php if (!$panel_landing_focus): ?>
  <header class="hp-topbar">
    <div class="hp-topbar-left">
      <button type="button" class="hs-burger" data-hs-burger aria-label="<?= hs_h($t['menu_open'] ?? '') ?>"><i class="fa-solid fa-bars"></i></button>
      <h1 class="hp-page-heading"><?= hs_h($page_title) ?></h1>
    </div>
    <div class="hp-topbar-right">
      <div class="hp-search" data-hp-search>
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" placeholder="<?= hs_h($t['search_placeholder'] ?? '') ?>" autocomplete="off" data-hp-search-input>
        <div class="hp-search-drop" data-hp-search-drop hidden>
          <?php foreach ($search_items as $si): ?>
            <a href="<?= hs_h(hs_url($si['url'])) ?>" data-hp-search-item="<?= hs_h(strtolower($si['label'])) ?>"><?= hs_h($si['label']) ?></a>
          <?php endforeach; ?>
          <span class="hp-search-empty" data-hp-search-empty hidden><?= hs_h($t['search_no_results'] ?? '') ?></span>
        </div>
      </div>
      <?= hs_render_lang_dropdown($lang) ?>
    </div>
  </header>
  <?php endif; ?>
  <main class="hs-content hp-content<?= $panel_landing_focus ? ' hp-content-elb' : '' ?>">
    <?php if (empty($panel_hide_tip)): ?>
      <?= hs_render_tip($panel_tip_key, $t) ?>
    <?php endif; ?>
    <?= $content ?? '' ?>
  </main>
</div>
<script>window.HP_SEARCH_ITEMS = <?= json_encode($search_items, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php if ($panel_support_mode): ?>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js" crossorigin="anonymous" defer></script>
<script src="<?= hs_h(hs_asset('js/support.js')) ?>" defer></script>
<?php endif; ?>
<?php if ($panel_fm_mode): ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.css" crossorigin="anonymous">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/xml/xml.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/javascript/javascript.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/css/css.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/htmlmixed/htmlmixed.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/php/php.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/sql/sql.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/markdown/markdown.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/yaml/yaml.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/shell/shell.min.js" crossorigin="anonymous"></script>
<script src="<?= hs_h(hs_asset('js/file-manager.js')) ?>" defer></script>
<?php endif; ?>
<?php if (!empty($panel_php_mode)): ?>
<script src="<?= hs_h(hs_asset('js/php-panel.js')) ?>" defer></script>
<?php endif; ?>
<?php if (!empty($panel_load_charts)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script src="<?= hs_h(hs_asset('js/resource-charts.js')) ?>"></script>
<?php endif; ?>
<?php if ($panel_landing_mode): ?>
<link rel="stylesheet" href="<?= hs_h(hs_asset('css/landing-elementor.css')) ?>">
<script src="<?= hs_h(hs_asset('js/landing-builder.js')) ?>" defer></script>
<?php endif; ?>
<?php if ($panel_perf_mode): ?>
<script src="<?= hs_h(hs_asset('js/performance-panel.js')) ?>" defer></script>
<?php endif; ?>
<?php if ($panel_speed_mode): ?>
<script src="<?= hs_h(hs_asset('js/speed-test.js')) ?>" defer></script>
<?php endif; ?>
<?php if ($panel_plan_change_mode): ?>
<script src="<?= hs_h(hs_asset('js/plan-change.js')) ?>" defer></script>
<?php endif; ?>
<?php if ($panel_domains_mode): ?>
<script src="<?= hs_h(hs_asset('js/panel-domains.js')) ?>" defer></script>
<?php endif; ?>
<?php if ($panel_domains_pending_mode): ?>
<script src="<?= hs_h(hs_asset('js/panel-domains-pending.js')) ?>" defer></script>
<?php endif; ?>
<?php if ($panel_databases_mode): ?>
<script src="<?= hs_h(hs_asset('js/panel-databases.js')) ?>" defer></script>
<?php endif; ?>
<script src="<?= hs_h(hs_asset('js/app.js')) ?>" defer></script>
</body>
</html>