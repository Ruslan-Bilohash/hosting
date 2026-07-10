<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/mysql-provision.php';

hs_seed_demo_data();
hs_admin_require();

$cfg = hs_mysql_provision_config() ?? [];
$error = '';
$success = '';
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = 'CSRF';
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
            $error = 'Could not save mysql-provision.config.php';
        } else {
            $cfg = hs_mysql_provision_config() ?? [];
            $success = 'Saved';
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
        $success = "Provisioned/skipped: $done, failed: $failed";
    }
}

$page_title = 'MySQL provisioning';
ob_start();
?>
<div style="max-width:720px;margin:0 auto 2rem">
  <h1><i class="fa-solid fa-database"></i> MySQL — клієнтські бази</h1>
  <p class="hp-muted">Кожен активний клієнт отримує окрему MySQL-базу. Можна використовувати локальний сервер або віддалений MySQL.</p>
  <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>
  <?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
  <?php if ($testResult !== null): ?>
    <div class="hs-alert <?= !empty($testResult['ok']) ? 'hs-alert-success' : 'hs-alert-error' ?>">
      <?= !empty($testResult['ok']) ? 'CREATE DATABASE test OK' : hs_h('Test failed: ' . ($testResult['error'] ?? '')) ?>
    </div>
  <?php endif; ?>

  <form method="post" class="hs-auth-card" style="max-width:none;margin-top:1rem">
    <?= hs_csrf_field() ?>
    <h2>Сервер провізіонування</h2>
    <p class="hp-muted" style="font-size:.85rem">Користувач MySQL з правами CREATE DATABASE / CREATE USER / GRANT (не CMS db.config user).</p>
    <div class="hp-grid-2" style="display:grid;gap:12px;grid-template-columns:1fr 1fr">
      <div class="hs-field"><label>Host (provision)</label><input name="prov_host" value="<?= hs_h((string) ($cfg['host'] ?? 'localhost')) ?>" required></div>
      <div class="hs-field"><label>Port</label><input name="prov_port" type="number" value="<?= hs_h((string) ($cfg['port'] ?? '3306')) ?>"></div>
      <div class="hs-field"><label>User</label><input name="prov_user" value="<?= hs_h((string) ($cfg['user'] ?? '')) ?>" required autocomplete="off"></div>
      <div class="hs-field"><label>Password</label><input type="password" name="prov_pass" placeholder="<?= ($cfg['pass'] ?? '') !== '' ? '••••••••' : '' ?>" autocomplete="new-password"></div>
      <div class="hs-field"><label>DB prefix</label><input name="prov_db_prefix" value="<?= hs_h((string) ($cfg['db_prefix'] ?? 'hs_')) ?>"></div>
      <div class="hs-field"><label>Name prefix (Hostinger)</label><input name="prov_name_prefix" value="<?= hs_h((string) ($cfg['name_prefix'] ?? '')) ?>" placeholder="u762384583_"></div>
      <div class="hs-field"><label>Client host</label><input name="prov_client_host" value="<?= hs_h((string) ($cfg['client_host'] ?? '')) ?>" placeholder="localhost"></div>
      <div class="hs-field"><label>Grant host</label><input name="prov_grant_host" value="<?= hs_h((string) ($cfg['grant_host'] ?? 'localhost')) ?>" placeholder="localhost або %"></div>
      <div class="hs-field"><label>Mode</label><select name="prov_mode"><option value="dedicated"<?= ($cfg['mode'] ?? '') !== 'shared' ? ' selected' : '' ?>>dedicated (CREATE DATABASE)</option><option value="shared"<?= ($cfg['mode'] ?? '') === 'shared' ? ' selected' : '' ?>>shared (Hostinger CMS DB)</option></select></div>
      <div class="hs-field"><label>Shared database</label><input name="prov_shared_database" value="<?= hs_h((string) ($cfg['shared_database'] ?? '')) ?>" placeholder="u762384583_hosting_cms"></div>
      <div class="hs-field"><label>Shared user</label><input name="prov_shared_user" value="<?= hs_h((string) ($cfg['shared_user'] ?? '')) ?>"></div>
      <div class="hs-field"><label>Shared pass</label><input type="password" name="prov_shared_pass" placeholder="<?= ($cfg['shared_pass'] ?? '') !== '' ? '••••••••' : '' ?>"></div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:1rem">
      <button type="submit" name="save_provision" value="1" class="hs-btn hs-btn-primary">Зберегти</button>
      <button type="submit" name="test_provision" value="1" class="hs-btn hs-btn-ghost">Тест CREATE DATABASE</button>
      <button type="submit" name="provision_all" value="1" class="hs-btn hs-btn-ghost" onclick="return confirm('Створити БД для всіх активних клієнтів без live БД?')">Provision all clients</button>
    </div>
  </form>

  <p style="margin-top:1.5rem"><a href="<?= hs_h(hs_admin_url()) ?>">← Admin</a></p>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-public.php';