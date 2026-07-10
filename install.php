<?php
/**
 * BILOHASH Hosting CMS — full MySQL installation wizard (multilingual).
 * Schema 2.0 · 30-day demo license · JSON migration guide.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/version.php';
require_once __DIR__ . '/includes/install-i18n.php';
require_once __DIR__ . '/includes/mysql-install.php';

const HS_INSTALL_UI_VERSION = '2.0.0';

function hs_install_page_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$lang = hs_install_detect_lang();
$t = hs_install_strings($lang);

$appRoot = __DIR__;
$dataDir = HS_DATA_DIR;
$installed = hs_install_mysql_ready($dataDir);
$requirements = hs_install_requirements($dataDir, $appRoot);
$reqOk = !in_array(false, array_column($requirements, 'ok'), true);
$error = '';
$success = null;
$licenseAccepted = !empty($_POST['accept_license']);

$jsonCandidates = [
    'users.json', 'sites.json', 'user-settings.json', 'invoices.json',
    'domain-orders.json', 'hosting-orders.json',
];
$hasJsonData = false;
foreach ($jsonCandidates as $jf) {
    if (is_file($dataDir . '/' . $jf)) {
        $hasJsonData = true;
        break;
    }
}

if (!$installed && $reqOk && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!$licenseAccepted) {
        $error = hs_install_t($t, 'btn_accept_required');
    } else {
        $success = hs_install_run($_POST, $appRoot, $dataDir);
        if (!empty($success['ok'])) {
            $installed = true;
            if (function_exists('hs_db_ensure_schema')) {
                require_once __DIR__ . '/includes/database.php';
                require_once __DIR__ . '/includes/db-migrate.php';
                if (hs_is_mysql_installed() && $hasJsonData) {
                    hs_mysql_migrate_from_json(true);
                }
            }
        } else {
            $error = (string) ($success['error'] ?? 'Installation failed.');
            $success = null;
        }
    }
}

global $base_path;
$prefix = rtrim((string) ($base_path ?? ''), '/');
$homeUrl = ($prefix !== '' ? $prefix : '') . '/';
$adminUrl = $homeUrl . 'admin/';
$panelUrl = $homeUrl . 'panel/';
$migrateUrl = $homeUrl . 'migrate-to-mysql.php';
$provisionUrl = $adminUrl . 'mysql.php';
$cssUrl = ($prefix !== '' ? $prefix : '') . '/assets/css/install.css?v=' . rawurlencode(hs_version());
$schemaVer = defined('HS_MYSQL_SCHEMA_VERSION') ? HS_MYSQL_SCHEMA_VERSION : '2.0.0';
?><!DOCTYPE html>
<html lang="<?= hs_install_page_h($t['html_lang'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <meta name="description" content="<?= hs_install_page_h($t['meta_desc'] ?? '') ?>">
    <meta name="theme-color" content="#070b14">
    <title><?= hs_install_page_h($t['title'] ?? 'Install') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= hs_install_page_h($cssUrl) ?>">
</head>
<body class="hi-body">
<div class="hi-wrap">
    <div class="hi-topbar">
        <span class="hi-version"><?= hs_install_page_h(hs_install_t($t, 'version_label', ['cms' => hs_version_label(), 'schema' => $schemaVer])) ?></span>
        <nav class="hi-langs" aria-label="Language">
            <?php foreach (hs_install_langs() as $code => $label): ?>
            <a href="<?= hs_install_page_h(hs_install_lang_url($code)) ?>" class="<?= $code === $lang ? 'is-active' : '' ?>"><?= hs_install_page_h($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>

    <header class="hi-hero">
        <div class="hi-logo"><i class="fa-solid fa-server" aria-hidden="true"></i></div>
        <h1><?= hs_install_page_h($t['brand'] ?? 'Hosting CMS') ?></h1>
        <p class="hi-tagline"><?= hs_install_page_h($t['tagline'] ?? '') ?></p>
    </header>

    <nav class="hi-nav">
        <a href="#overview"><i class="fa-solid fa-circle-info"></i> <?= hs_install_page_h($t['nav_overview'] ?? '') ?></a>
        <a href="#license"><i class="fa-solid fa-scale-balanced"></i> <?= hs_install_page_h($t['nav_license'] ?? '') ?></a>
        <a href="#requirements"><i class="fa-solid fa-list-check"></i> <?= hs_install_page_h($t['nav_requirements'] ?? '') ?></a>
        <?php if (!$installed): ?>
        <a href="#install"><i class="fa-solid fa-database"></i> <?= hs_install_page_h($t['nav_install'] ?? '') ?></a>
        <?php endif; ?>
        <?php if ($installed): ?>
        <a href="#migrate"><i class="fa-solid fa-right-left"></i> <?= hs_install_page_h($t['nav_migrate'] ?? '') ?></a>
        <?php endif; ?>
    </nav>

    <?php if ($installed && is_array($success) && !empty($success['ok'])): ?>
    <section class="hi-card">
        <div class="hi-alert hi-alert-ok"><i class="fa-solid fa-circle-check"></i> <?= hs_install_page_h($t['success_title'] ?? '') ?></div>
        <p class="hi-lead"><?= hs_install_page_h($t['success_lead'] ?? '') ?></p>
        <p><?= hs_install_page_h(hs_install_t($t, 'success_admin', ['user' => (string) ($success['admin_user'] ?? 'admin')])) ?></p>
        <p class="hi-hint"><?= hs_install_page_h($t['success_demo'] ?? '') ?></p>
        <p class="hi-hint"><i class="fa-solid fa-arrow-right"></i> <?= hs_install_page_h($t['success_provision'] ?? '') ?></p>
        <div class="hi-links">
            <a class="hi-link hi-link-primary" href="<?= hs_install_page_h($homeUrl) ?>"><i class="fa-solid fa-house"></i> <?= hs_install_page_h($t['link_home'] ?? '') ?></a>
            <a class="hi-link hi-link-ghost" href="<?= hs_install_page_h($panelUrl) ?>"><i class="fa-solid fa-gauge-high"></i> <?= hs_install_page_h($t['link_panel'] ?? '') ?></a>
            <a class="hi-link hi-link-ghost" href="<?= hs_install_page_h($adminUrl) ?>"><i class="fa-solid fa-lock"></i> <?= hs_install_page_h($t['link_admin'] ?? '') ?></a>
            <a class="hi-link hi-link-ghost" href="<?= hs_install_page_h($provisionUrl) ?>"><i class="fa-solid fa-database"></i> <?= hs_install_page_h($t['link_provision'] ?? '') ?></a>
        </div>
    </section>
    <?php elseif ($installed): ?>
    <section class="hi-card">
        <div class="hi-alert hi-alert-ok"><i class="fa-solid fa-database"></i> <?= hs_install_page_h($t['already_title'] ?? '') ?></div>
        <p class="hi-lead"><?= hs_install_page_h($t['already_lead'] ?? '') ?></p>
        <div class="hi-links">
            <a class="hi-link hi-link-primary" href="<?= hs_install_page_h($homeUrl) ?>"><?= hs_install_page_h($t['link_home'] ?? '') ?></a>
            <a class="hi-link hi-link-ghost" href="<?= hs_install_page_h($panelUrl) ?>"><?= hs_install_page_h($t['link_panel'] ?? '') ?></a>
        </div>
    </section>
    <?php else: ?>

    <?php if ($error !== ''): ?>
    <div class="hi-alert hi-alert-err"><?= hs_install_page_h($error) ?></div>
    <?php endif; ?>

    <section class="hi-card" id="overview">
        <h2><i class="fa-solid fa-rocket"></i> <?= hs_install_page_h($t['overview_title'] ?? '') ?></h2>
        <p class="hi-lead"><?= hs_install_page_h($t['overview_lead'] ?? '') ?></p>
        <div class="hi-features">
            <div class="hi-feat"><strong><?= hs_install_page_h($t['feat_panel'] ?? '') ?></strong><span><?= hs_install_page_h($t['feat_panel_desc'] ?? '') ?></span></div>
            <div class="hi-feat"><strong><?= hs_install_page_h($t['feat_eco'] ?? '') ?></strong><span><?= hs_install_page_h($t['feat_eco_desc'] ?? '') ?></span></div>
            <div class="hi-feat"><strong><?= hs_install_page_h($t['feat_mysql'] ?? '') ?></strong><span><?= hs_install_page_h($t['feat_mysql_desc'] ?? '') ?></span></div>
            <div class="hi-feat"><strong><?= hs_install_page_h($t['feat_i18n'] ?? '') ?></strong><span><?= hs_install_page_h($t['feat_i18n_desc'] ?? '') ?></span></div>
        </div>
    </section>

    <section class="hi-card hi-license" id="license">
        <h2><i class="fa-solid fa-scale-balanced"></i> <?= hs_install_page_h($t['license_title'] ?? '') ?></h2>
        <div class="hi-license-badge"><i class="fa-solid fa-clock"></i> <?= hs_install_page_h($t['license_badge'] ?? '') ?></div>
        <p><?= hs_install_page_h($t['license_p1'] ?? '') ?></p>
        <p><?= hs_install_page_h($t['license_p2'] ?? '') ?></p>
        <a class="cta" href="<?= hs_install_page_h($t['license_url'] ?? 'https://bilohash.com') ?>" target="_blank" rel="noopener">
            <i class="fa-solid fa-envelope"></i> <?= hs_install_page_h($t['license_contact'] ?? '') ?> — bilohash.com
        </a>
    </section>

    <section class="hi-card" id="requirements">
        <h2><i class="fa-solid fa-server"></i> <?= hs_install_page_h($t['req_title'] ?? '') ?></h2>
        <p class="hi-hint"><?= hs_install_page_h($t['req_hint'] ?? '') ?></p>
        <?php foreach ($requirements as $r): ?>
        <div class="hi-req">
            <span><?= hs_install_page_h($r['label']) ?></span>
            <span class="<?= $r['ok'] ? 'ok' : 'bad' ?>"><?= $r['ok'] ? '✓' : '✗' ?> <?= hs_install_page_h($r['hint']) ?></span>
        </div>
        <?php endforeach; ?>
    </section>

    <section class="hi-card" id="install">
        <h2><i class="fa-solid fa-terminal"></i> <?= hs_install_page_h($t['step_mysql_title'] ?? '') ?></h2>
        <p class="hi-hint"><?= hs_install_page_h($t['step_mysql_hint'] ?? '') ?></p>
        <pre class="hi-sql"><code><?= hs_install_page_h($t['sql_create_db'] ?? '') ?></code><code><?= hs_install_page_h($t['sql_create_user'] ?? '') ?></code><code><?= hs_install_page_h($t['sql_grant'] ?? '') ?></code></pre>
    </section>

    <form method="post" class="hi-card">
        <h2><i class="fa-solid fa-plug"></i> <?= hs_install_page_h($t['step_connect_title'] ?? '') ?></h2>
        <div class="hi-grid hi-grid-2">
            <div><label class="hi-label"><?= hs_install_page_h($t['lbl_db_host'] ?? '') ?></label>
                <input class="hi-input" name="db_host" value="<?= hs_install_page_h($_POST['db_host'] ?? 'localhost') ?>" required></div>
            <div><label class="hi-label"><?= hs_install_page_h($t['lbl_db_name'] ?? '') ?></label>
                <input class="hi-input" name="db_name" value="<?= hs_install_page_h($_POST['db_name'] ?? 'hosting_cms') ?>" required></div>
            <div><label class="hi-label"><?= hs_install_page_h($t['lbl_db_user'] ?? '') ?></label>
                <input class="hi-input" name="db_user" value="<?= hs_install_page_h($_POST['db_user'] ?? '') ?>" required autocomplete="off"></div>
            <div><label class="hi-label"><?= hs_install_page_h($t['lbl_db_pass'] ?? '') ?></label>
                <input class="hi-input" type="password" name="db_pass" autocomplete="new-password"></div>
            <div><label class="hi-label"><?= hs_install_page_h($t['lbl_db_prefix'] ?? '') ?></label>
                <input class="hi-input" name="db_prefix" value="<?= hs_install_page_h($_POST['db_prefix'] ?? 'hs_') ?>"></div>
        </div>

        <h2 style="margin-top:22px"><i class="fa-solid fa-user-shield"></i> <?= hs_install_page_h($t['step_admin_title'] ?? '') ?></h2>
        <div class="hi-grid hi-grid-2">
            <div><label class="hi-label"><?= hs_install_page_h($t['lbl_admin_user'] ?? '') ?></label>
                <input class="hi-input" name="admin_user" value="<?= hs_install_page_h($_POST['admin_user'] ?? 'admin') ?>" required></div>
            <div><label class="hi-label"><?= hs_install_page_h($t['lbl_admin_pass'] ?? '') ?></label>
                <input class="hi-input" type="password" name="admin_pass" required autocomplete="new-password"></div>
        </div>
        <label class="hi-chk"><input type="checkbox" name="seed_demo" value="1" checked> <?= hs_install_page_h($t['chk_seed_demo'] ?? '') ?></label>
        <label class="hi-chk"><input type="checkbox" name="accept_license" value="1" <?= $licenseAccepted ? 'checked' : '' ?> required> <?= hs_install_page_h($t['license_accept'] ?? '') ?></label>

        <section class="hi-card" style="margin-top:18px;padding:16px;background:rgba(0,0,0,.2)">
            <h2 style="font-size:.95rem"><i class="fa-solid fa-shield-halved"></i> <?= hs_install_page_h($t['security_title'] ?? '') ?></h2>
            <p class="hi-hint"><?= hs_install_page_h($t['security_lead'] ?? '') ?></p>
            <ul class="hi-sec-list">
                <li><code>data/db.config.php</code> — <?= hs_install_page_h($t['sec_db_config'] ?? '') ?></li>
                <li><code>data/admin.config.php</code> — <?= hs_install_page_h($t['sec_admin_config'] ?? '') ?></li>
                <li><code>data/mysql-provision.config.php</code> — <?= hs_install_page_h($t['sec_provision'] ?? '') ?></li>
                <li><code>data/client-db/*.php</code> — <?= hs_install_page_h($t['sec_client_db'] ?? '') ?></li>
                <li><code>public_html/</code> — <?= hs_install_page_h($t['sec_public'] ?? '') ?></li>
                <li><code>config.local.php</code> — <?= hs_install_page_h($t['sec_ssh'] ?? '') ?></li>
            </ul>
        </section>

        <button type="submit" class="hi-btn" <?= ($reqOk && $licenseAccepted) ? '' : 'disabled' ?>>
            <i class="fa-solid fa-rocket"></i> <?= hs_install_page_h($reqOk ? ($t['btn_install'] ?? '') : ($t['btn_disabled'] ?? '')) ?>
        </button>
        <?php if (!$reqOk): ?><p class="hi-hint"><?= hs_install_page_h($t['btn_disabled'] ?? '') ?></p><?php endif; ?>
    </form>
    <?php endif; ?>

    <?php if ($installed): ?>
    <section class="hi-card" id="migrate">
        <h2><i class="fa-solid fa-right-left"></i> <?= hs_install_page_h($t['migrate_title'] ?? '') ?></h2>
        <p class="hi-lead"><?= hs_install_page_h($t['migrate_lead'] ?? '') ?></p>
        <?php if ($hasJsonData): ?>
        <p class="hi-hint"><i class="fa-solid fa-file-code"></i> <?= hs_install_page_h($t['migrate_json_detected'] ?? '') ?></p>
        <?php endif; ?>
        <p class="hi-hint"><code><?= hs_install_page_h($t['migrate_cli'] ?? '') ?></code></p>
        <div class="hi-links">
            <a class="hi-link hi-link-primary" href="<?= hs_install_page_h($migrateUrl . '?lang=' . rawurlencode($lang)) ?>"><i class="fa-solid fa-database"></i> <?= hs_install_page_h($t['migrate_btn'] ?? '') ?></a>
        </div>
    </section>
    <?php endif; ?>

    <footer class="hi-foot">
        <p><?= hs_install_page_h($t['foot_note'] ?? '') ?></p>
        <p><a href="https://github.com/Ruslan-Bilohash/hosting" target="_blank" rel="noopener"><?= hs_install_page_h($t['foot_github'] ?? '') ?></a> · <a href="https://bilohash.com" target="_blank" rel="noopener">bilohash.com</a></p>
    </footer>
</div>
<script>
(function () {
  var accept = document.querySelector('input[name="accept_license"]');
  var btn = document.querySelector('.hi-btn');
  if (!accept || !btn) return;
  function sync() { btn.disabled = !accept.checked || btn.hasAttribute('data-req-fail'); }
  accept.addEventListener('change', sync);
  if (!<?= $reqOk ? 'true' : 'false' ?>) btn.setAttribute('data-req-fail', '1');
  sync();
})();
</script>
</body>
</html>