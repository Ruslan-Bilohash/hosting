<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/mysql-provision.php';
require_once dirname(__DIR__) . '/includes/admin-nav.php';

hs_seed_demo_data();
hs_admin_require();

$cfg = hs_mysql_provision_config() ?? [];
$error = '';
$success = '';
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? 'CSRF';
    } elseif (isset($_POST['test_provision'])) {
        $testResult = hs_mysql_provision_test();
    } elseif (isset($_POST['save_provision'])) {
        $newCfg = [
            'host' => $_POST['prov_host'] ?? 'localhost',
            'port' => $_POST['prov_port'] ?? 3306,
            'user' => $_POST['prov_user'] ?? '',
            'pass' => $_POST['prov_pass'] ?? '',
            'db_prefix' => $_POST['prov_db_prefix'] ?? 'hs_',
            'name_prefix' => $_POST['prov_name_prefix'] ?? '',
            'client_host' => $_POST['prov_client_host'] ?? '',
            'grant_host' => $_POST['prov_grant_host'] ?? 'localhost',
            'mode' => $_POST['prov_mode'] ?? 'dedicated',
            'shared_database' => $_POST['prov_shared_database'] ?? '',
            'shared_user' => $_POST['prov_shared_user'] ?? '',
            'shared_pass' => $_POST['prov_shared_pass'] ?? '',
        ];
        if (trim((string) $newCfg['pass']) === '' && ($cfg['pass'] ?? '') !== '') {
            $newCfg['pass'] = (string) $cfg['pass'];
        }
        if (trim((string) $newCfg['shared_pass']) === '' && ($cfg['shared_pass'] ?? '') !== '') {
            $newCfg['shared_pass'] = (string) $cfg['shared_pass'];
        }
        if (!hs_mysql_provision_save_config($newCfg)) {
            $error = $t['admin_mysql_save_fail'] ?? 'Could not save mysql-provision.config.php';
        } else {
            $cfg = hs_mysql_provision_config() ?? [];
            $success = $t['admin_mysql_saved'] ?? 'Saved';
            $testResult = hs_mysql_provision_test();
            if (!empty($testResult['ok'])) {
                require_once dirname(__DIR__) . '/includes/user-settings.php';
                foreach (hs_all_user_settings() as $uid => $set) {
                    if (!empty($set['db_provision_failed'])) {
                        hs_user_settings_save((string) $uid, ['db_provision_failed' => false]);
                    }
                }
            }
        }
    } elseif (isset($_POST['provision_all'])) {
        require_once dirname(__DIR__) . '/includes/user-settings.php';
        $done = 0;
        $failed = 0;
        foreach (hs_users() as $u) {
            if (($u['subscription_status'] ?? '') !== 'active') {
                continue;
            }
            $res = hs_ensure_user_database((string) ($u['id'] ?? ''), (string) ($u['username'] ?? 'user'), $u);
            if (!empty($res['ok'])) {
                $done++;
            } else {
                $failed++;
            }
        }
        $success = str_replace(
            ['{done}', '{failed}'],
            [(string) $done, (string) $failed],
            $t['admin_mysql_provision_result'] ?? 'Provisioned/skipped: {done}, failed: {failed}'
        );
    }
}

$page_title = $t['admin_mysql_title'] ?? 'MySQL provisioning';
$admin_nav_active = 'mysql';
ob_start();
?>
<div class="hs-admin-page">
  <div style="max-width:720px;margin:0 auto 2rem">
  <h1><i class="fa-solid fa-database"></i> <?= hs_h($t['admin_mysql_heading'] ?? 'MySQL — client databases') ?></h1>
  <p class="hp-muted"><?= hs_h($t['admin_mysql_intro'] ?? '') ?></p>
  <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>
  <?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
  <?php if ($testResult !== null): ?>
    <div class="hs-alert <?= !empty($testResult['ok']) ? 'hs-alert-success' : 'hs-alert-error' ?>">
      <?php if (!empty($testResult['ok'])): ?>
        <?= hs_h($t['admin_mysql_test_ok'] ?? 'CREATE DATABASE test OK') ?>
      <?php else: ?>
        <?= hs_h(str_replace('{error}', (string) ($testResult['error'] ?? ''), $t['admin_mysql_test_fail'] ?? 'Test failed: {error}')) ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="hs-auth-card" style="max-width:none;margin-top:1rem">
    <?= hs_csrf_field() ?>
    <h2><?= hs_h($t['admin_mysql_server_title'] ?? 'Provisioning server') ?></h2>
    <p class="hp-muted" style="font-size:.85rem"><?= hs_h($t['admin_mysql_server_hint'] ?? '') ?></p>
    <div class="hp-grid-2" style="display:grid;gap:12px;grid-template-columns:1fr 1fr">
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_host'] ?? 'Host') ?></label><input name="prov_host" value="<?= hs_h((string) ($cfg['host'] ?? 'localhost')) ?>" required></div>
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_port'] ?? 'Port') ?></label><input name="prov_port" type="number" value="<?= hs_h((string) ($cfg['port'] ?? '3306')) ?>"></div>
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_user'] ?? 'User') ?></label><input name="prov_user" value="<?= hs_h((string) ($cfg['user'] ?? '')) ?>" required autocomplete="off"></div>
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_password'] ?? 'Password') ?></label><input type="password" name="prov_pass" placeholder="<?= ($cfg['pass'] ?? '') !== '' ? '••••••••' : '' ?>" autocomplete="new-password"></div>
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_db_prefix'] ?? 'DB prefix') ?></label><input name="prov_db_prefix" value="<?= hs_h((string) ($cfg['db_prefix'] ?? 'hs_')) ?>"></div>
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_name_prefix'] ?? 'Name prefix') ?></label><input name="prov_name_prefix" value="<?= hs_h((string) ($cfg['name_prefix'] ?? '')) ?>" placeholder="account_prefix_"></div>
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_client_host'] ?? 'Client host') ?></label><input name="prov_client_host" value="<?= hs_h((string) ($cfg['client_host'] ?? '')) ?>" placeholder="localhost"></div>
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_grant_host'] ?? 'Grant host') ?></label><input name="prov_grant_host" value="<?= hs_h((string) ($cfg['grant_host'] ?? 'localhost')) ?>" placeholder="localhost / %"></div>
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_mode'] ?? 'Mode') ?></label><select name="prov_mode"><option value="dedicated"<?= ($cfg['mode'] ?? '') !== 'shared' ? ' selected' : '' ?>><?= hs_h($t['admin_mysql_mode_dedicated'] ?? 'dedicated') ?></option><option value="shared"<?= ($cfg['mode'] ?? '') === 'shared' ? ' selected' : '' ?>><?= hs_h($t['admin_mysql_mode_shared'] ?? 'shared') ?></option></select></div>
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_shared_database'] ?? 'Shared database') ?></label><input name="prov_shared_database" value="<?= hs_h((string) ($cfg['shared_database'] ?? '')) ?>" placeholder="hosting_cms"></div>
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_shared_user'] ?? 'Shared user') ?></label><input name="prov_shared_user" value="<?= hs_h((string) ($cfg['shared_user'] ?? '')) ?>"></div>
      <div class="hs-field"><label><?= hs_h($t['admin_mysql_shared_pass'] ?? 'Shared password') ?></label><input type="password" name="prov_shared_pass" placeholder="<?= ($cfg['shared_pass'] ?? '') !== '' ? '••••••••' : '' ?>"></div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:1rem">
      <button type="submit" name="save_provision" value="1" class="hs-btn hs-btn-primary"><?= hs_h($t['admin_mysql_save'] ?? 'Save') ?></button>
      <button type="submit" name="test_provision" value="1" class="hs-btn hs-btn-ghost"><?= hs_h($t['admin_mysql_test'] ?? 'Test CREATE DATABASE') ?></button>
      <button type="submit" name="provision_all" value="1" class="hs-btn hs-btn-ghost" onclick="return confirm(<?= json_encode($t['admin_mysql_provision_confirm'] ?? 'Proceed?', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)"><?= hs_h($t['admin_mysql_provision_all'] ?? 'Provision all clients') ?></button>
    </div>
  </form>

  <p style="margin-top:1.5rem"><a href="<?= hs_h(hs_admin_url()) ?>"><?= hs_h($t['admin_mysql_back'] ?? '← Admin') ?></a></p>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';
