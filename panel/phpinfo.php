<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/panel-bootstrap.php';

if (empty($_GET['show'])) {
    $panel_active = 'adv-phpinfo';
    $page_title = $t['tab_adv_phpinfo'] ?? 'PHP Info';
    ob_start();
    ?>
    <p class="hp-muted"><?= hs_h($t['phpinfo_hint'] ?? 'View full PHP configuration for this server.') ?></p>
    <div class="hp-actions">
      <a href="<?= hs_h(hs_url(hs_panel_path('phpinfo.php'), ['show' => '1'])) ?>" target="_blank" rel="noopener" class="hs-btn hs-btn-primary"><?= hs_h($t['phpinfo_open'] ?? 'Open phpinfo()') ?></a>
      <a href="<?= hs_h(hs_url(hs_panel_path('php.php'))) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['nav_php'] ?? 'PHP config') ?></a>
    </div>
    <?php
    $content = ob_get_clean();
    require dirname(__DIR__) . '/includes/layout-panel.php';
    exit;
}

phpinfo();