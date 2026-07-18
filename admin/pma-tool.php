<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/phpmyadmin.php';
require_once dirname(__DIR__) . '/includes/mysql-provision.php';
require_once dirname(__DIR__) . '/includes/database.php';

hs_admin_require();
$admin_active = 'pma';

$root = dirname(__DIR__);
$pmaInstalled = is_file($root . '/pma/index.php');
$error = '';
$creds = hs_pma_admin_credentials();
$open = isset($_GET['open']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['open_pma']));

if ($open) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = 'CSRF';
    } elseif (!$pmaInstalled) {
        $error = 'phpMyAdmin is not installed (missing pma/index.php).';
    } elseif ($creds === null) {
        $error = 'MySQL credentials not configured. Set up mysql-provision.config.php or db.config.php first.';
    } else {
        hs_pma_start_signon_session($creds, 'admin');
        header('Location: ' . hs_pma_index_url());
        exit;
    }
}

$dbCfg = hs_db_config();
$provCfg = hs_mysql_provision_config();
$credSource = '—';
$credUser = '—';
if (is_array($provCfg) && trim((string) ($provCfg['user'] ?? '')) !== '' && (string) ($provCfg['pass'] ?? '') !== '') {
    $credSource = 'mysql-provision.config.php';
    $credUser = (string) $provCfg['user'];
} elseif (is_array($dbCfg)) {
    $credSource = 'db.config.php';
    $credUser = (string) ($dbCfg['user'] ?? $dbCfg['username'] ?? '');
}

$page_title = 'phpMyAdmin';
ob_start();
?>
<div class="hs-admin-page" style="max-width:640px">
  <h1><i class="fa-solid fa-database"></i> phpMyAdmin</h1>
  <p class="hp-muted">Вхід для супер-адміністратора. Клієнти використовують Панель → Бази даних → phpMyAdmin (ізольований доступ лише до своєї БД).</p>

  <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

  <div class="hp-card" style="padding:1.25rem;margin:1rem 0">
    <p><strong>phpMyAdmin:</strong> <?= $pmaInstalled ? '✅ встановлено' : '❌ не встановлено' ?></p>
    <p><strong>MySQL user:</strong> <code><?= hs_h($credUser) ?></code></p>
    <p><strong>Джерело:</strong> <code><?= hs_h($credSource) ?></code></p>
    <?php if (function_exists('hs_mysql_provision_via_uapi') && hs_mysql_provision_via_uapi()): ?>
      <p class="hp-muted" style="font-size:.85rem;margin-top:.5rem">Режим cPanel UAPI — доступ до всіх баз акаунта <code>solaffhv_*</code>.</p>
    <?php endif; ?>
  </div>

  <?php if ($pmaInstalled && $creds !== null): ?>
  <form method="post" target="_blank" rel="noopener">
    <?= hs_csrf_field() ?>
    <button type="submit" name="open_pma" value="1" class="hs-btn hs-btn-primary">
      <i class="fa-solid fa-arrow-up-right-from-square"></i> Відкрити phpMyAdmin
    </button>
  </form>
  <p class="hp-muted" style="margin-top:.75rem;font-size:.85rem">Або <a href="<?= hs_h(hs_admin_url('pma-tool.php', ['open' => '1'])) ?>" target="_blank" rel="noopener">пряме посилання</a> (GET).</p>
  <?php elseif (!$pmaInstalled): ?>
  <div class="hs-alert hs-alert-error">
    Папка <code>pma/</code> відсутня. Завантажте phpMyAdmin 5.2.x у <code>/public_html/pma/</code> (хост блокує автоматичний installer у admin).
  </div>
  <?php else: ?>
  <div class="hs-alert hs-alert-error">Спочатку налаштуйте <a href="<?= hs_h(hs_admin_url('mysql.php')) ?>">MySQL provisioning</a> або встановіть CMS (<code>db.config.php</code>).</div>
  <?php endif; ?>

  <p style="margin-top:1.5rem"><a href="<?= hs_h(hs_admin_url('mysql.php')) ?>">← MySQL provisioning</a>
    · <a href="<?= hs_h(hs_admin_url('tools.php')) ?>">API &amp; tools</a></p>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';
