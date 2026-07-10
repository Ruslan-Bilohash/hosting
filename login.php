<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/client-auth.php';

hs_seed_demo_data();

if (hs_client_id() !== null) {
    hs_redirect(hs_panel_path(''));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
    } elseif (!hs_client_login(trim((string) ($_POST['login'] ?? '')), (string) ($_POST['password'] ?? ''))) {
        $error = $t['login_error'] ?? '';
    } else {
        hs_redirect(hs_panel_path(''));
    }
}

ob_start();
?>
<div class="hs-auth-wrap">
  <div class="hs-auth-card">
    <h1><?= hs_h($t['login_title'] ?? '') ?></h1>
    <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>
    <form method="post" action="">
      <?= hs_csrf_field() ?>
      <div class="hs-field">
        <label for="login"><?= hs_h($t['login_email'] ?? '') ?></label>
        <input type="text" id="login" name="login" required autocomplete="username" value="<?= hs_h($_POST['login'] ?? 'demo') ?>">
      </div>
      <div class="hs-field">
        <label for="password"><?= hs_h($t['login_password'] ?? '') ?></label>
        <input type="password" id="password" name="password" required autocomplete="current-password" value="<?= (defined('HS_DEMO_MODE') && HS_DEMO_MODE) ? 'demo' : '' ?>">
      </div>
      <button type="submit" class="hs-btn hs-btn-primary" style="width:100%"><?= hs_h($t['login_submit'] ?? '') ?></button>
    </form>
    <p style="margin-top:1rem;font-size:.85rem;text-align:center"><a href="<?= hs_h(hs_url('register.php')) ?>"><?= hs_h($t['nav_register'] ?? '') ?></a></p>
  </div>
</div>
<?php
$content = ob_get_clean();
$page_title = $t['login_title'] ?? '';
require __DIR__ . '/includes/layout-public.php';