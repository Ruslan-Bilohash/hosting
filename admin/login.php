<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

hs_seed_demo_data();

if (hs_admin_logged()) {
    header('Location: ' . hs_admin_url(), true, 302);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = 'CSRF';
    } elseif (!hs_admin_login(trim((string) ($_POST['user'] ?? '')), (string) ($_POST['password'] ?? ''))) {
        $error = $t['login_error'] ?? 'Error';
    } else {
        header('Location: ' . hs_admin_url(), true, 302);
        exit;
    }
}

$page_title = $t['admin_login'] ?? '';
ob_start();
?>
<div class="hs-auth-wrap">
  <div class="hs-auth-card">
    <h1><?= hs_h($t['admin_login'] ?? '') ?></h1>
    <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>
    <form method="post">
      <?= hs_csrf_field() ?>
      <div class="hs-field"><label><?= hs_h($t['admin_login_user'] ?? 'User') ?></label><input name="user" required value="admin" autocomplete="username"></div>
      <div class="hs-field"><label><?= hs_h($t['admin_login_password'] ?? 'Password') ?></label><input type="password" name="password" required autocomplete="current-password"></div>
      <button type="submit" class="hs-btn hs-btn-primary" style="width:100%"><?= hs_h($t['admin_login_submit'] ?? 'Login') ?></button>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-public.php';