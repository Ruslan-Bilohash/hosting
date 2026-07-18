<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/client-auth.php';

hs_seed_demo_data();

// Only auto-enter panel when the session maps to a real user (stale ids cause redirect loops).
if (hs_client_id() !== null) {
    $hsLoginUser = hs_client_user();
    if ($hsLoginUser !== null) {
        hs_redirect(hs_panel_path(''));
    }
    hs_client_logout();
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

$body_class = 'hs-public-body hs-auth-page';
$page_extra_css = ['css/auth-public.css'];

ob_start();
?>
<div class="hs-auth-wrap hs-auth-wrap--login">
  <aside class="hs-auth-aside" aria-hidden="true">
    <span class="hs-auth-aside-badge">
      <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
      <?= hs_h($t['speed_badge'] ?? 'Performance first') ?>
    </span>
    <h2><?= hs_h($t['login_aside_title'] ?? $t['login_title'] ?? 'Welcome back') ?></h2>
    <p><?= hs_h($t['login_aside_lead'] ?? $t['login_lead'] ?? $t['hero_sub'] ?? '') ?></p>
    <ul class="hs-auth-aside-list">
      <li><i class="fa-solid fa-check" aria-hidden="true"></i><span><?= hs_h($t['login_aside_1'] ?? $t['feat_hosting_desc'] ?? 'SSD hosting in Europe') ?></span></li>
      <li><i class="fa-solid fa-check" aria-hidden="true"></i><span><?= hs_h($t['login_aside_2'] ?? $t['feat_install'] ?? 'One-click CMS installer') ?></span></li>
      <li><i class="fa-solid fa-check" aria-hidden="true"></i><span><?= hs_h($t['login_aside_3'] ?? $t['feat_speed_desc'] ?? 'Fast servers for Core Web Vitals') ?></span></li>
    </ul>
  </aside>

  <div class="hs-auth-card">
    <h1><?= hs_h($t['login_title'] ?? 'Welcome back') ?></h1>
    <p class="hs-auth-card-lead"><?= hs_h($t['login_lead'] ?? $t['login_subtitle'] ?? '') ?></p>
    <?php if ($error !== ''): ?>
      <div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div>
    <?php endif; ?>
    <form method="post" action="" autocomplete="on">
      <?= hs_csrf_field() ?>
      <div class="hs-field">
        <label for="login"><?= hs_h($t['login_email'] ?? 'Email or username') ?></label>
        <input type="text" id="login" name="login" required autocomplete="username"
          value="<?= hs_h((string) ($_POST['login'] ?? '')) ?>"
          placeholder="<?= hs_h($t['login_email_ph'] ?? 'you@company.com') ?>">
      </div>
      <div class="hs-field">
        <label for="password"><?= hs_h($t['login_password'] ?? 'Password') ?></label>
        <input type="password" id="password" name="password" required autocomplete="current-password" value="">
      </div>
      <button type="submit" class="hs-btn hs-btn-primary hs-auth-submit">
        <?= hs_h($t['login_submit'] ?? 'Log in') ?>
      </button>
    </form>
    <p class="hs-auth-footer">
      <?= hs_h($t['login_no_account'] ?? $t['hero_login_no_account'] ?? 'No account yet?') ?>
      <a href="<?= hs_h(hs_url('register.php')) ?>"><?= hs_h($t['nav_register'] ?? 'Get started') ?></a>
    </p>
  </div>
</div>
<?php
$content = ob_get_clean();
$page_title = $t['login_title'] ?? 'Login';
require __DIR__ . '/includes/layout-public.php';
