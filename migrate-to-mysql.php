<?php
/**
 * BILOHASH Hosting CMS — JSON → MySQL migration (schema 2.0).
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/version.php';
require_once __DIR__ . '/includes/install-i18n.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/db-migrate.php';
require_once __DIR__ . '/includes/admin-auth.php';

$lang = hs_install_detect_lang();
$t = hs_install_strings($lang);

if (hs_db_pdo() instanceof PDO) {
    hs_db_ensure_schema();
}

function hs_migrate_page_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$canRun = hs_db_pdo() instanceof PDO;
$migratedAt = $canRun ? (string) hs_db_meta_get_scalar(HS_DB_META_JSON_MIGRATED, '') : '';
$error = '';
$result = null;

if ($canRun && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $adminUser = trim((string) ($_POST['admin_user'] ?? ''));
    $adminPass = (string) ($_POST['admin_pass'] ?? '');
    $confirm = !empty($_POST['confirm_migrate']);
    if (!$confirm) {
        $error = hs_install_t($t, 'migrate_error_confirm');
    } elseif (!hs_admin_verify_credentials($adminUser, $adminPass)) {
        $error = hs_install_t($t, 'migrate_error_auth');
    } else {
        $result = hs_mysql_migrate_from_json(true);
        if (!empty($result['ok'])) {
            $migratedAt = (string) hs_db_meta_get_scalar(HS_DB_META_JSON_MIGRATED, '');
        } else {
            $error = implode('; ', $result['errors'] ?? ['Migration failed']);
        }
    }
}

$jsonFiles = [
    'users.json' => hs_data_file('users'),
    'sites.json' => hs_data_file('sites'),
    'user-settings.json' => hs_data_file('user-settings'),
    'invoices.json' => hs_data_file('invoices'),
    'domain-orders.json' => hs_data_file('domain-orders'),
    'hosting-orders.json' => hs_data_file('hosting-orders'),
    'plans-catalog.json' => hs_data_file('plans-catalog'),
    'invoice-counter.json' => hs_data_file('invoice-counter'),
    'client-counter.json' => hs_data_file('client-counter'),
    'exchange-rates.json' => HS_DATA_DIR . '/exchange-rates.json',
];

global $base_path;
$prefix = rtrim((string) ($base_path ?? ''), '/');
$homeUrl = ($prefix !== '' ? $prefix : '') . '/';
$installUrl = $homeUrl . 'install.php?lang=' . rawurlencode($lang);
$panelUrl = $homeUrl . 'panel/';
$cssUrl = ($prefix !== '' ? $prefix : '') . '/assets/css/install.css?v=' . rawurlencode(hs_version());
?><!DOCTYPE html>
<html lang="<?= hs_migrate_page_h($t['html_lang'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title><?= hs_migrate_page_h($t['migrate_page_title'] ?? 'Migration') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= hs_migrate_page_h($cssUrl) ?>">
</head>
<body class="hi-body">
<div class="hi-wrap">
    <div class="hi-topbar">
        <a href="<?= hs_migrate_page_h($installUrl) ?>" style="color:var(--hi-accent);text-decoration:none;font-size:13px;font-weight:600"><?= hs_migrate_page_h($t['migrate_back_install'] ?? '') ?></a>
        <nav class="hi-langs">
            <?php foreach (hs_install_langs() as $code => $label): ?>
            <a href="<?= hs_migrate_page_h(hs_install_lang_url($code, 'migrate-to-mysql.php')) ?>" class="<?= $code === $lang ? 'is-active' : '' ?>"><?= hs_migrate_page_h($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>

    <header class="hi-hero">
        <div class="hi-logo"><i class="fa-solid fa-right-left"></i></div>
        <h1><?= hs_migrate_page_h($t['migrate_page_title'] ?? '') ?></h1>
        <p class="hi-tagline"><?= hs_migrate_page_h($t['migrate_page_lead'] ?? '') ?></p>
    </header>

    <section class="hi-card">
        <?php if (!$canRun): ?>
        <div class="hi-alert hi-alert-err"><?= hs_migrate_page_h($t['migrate_no_mysql'] ?? '') ?> — <a href="<?= hs_migrate_page_h($installUrl) ?>" style="color:inherit">install.php</a></div>
        <?php else: ?>
        <p class="hi-hint">Schema <strong><?= hs_migrate_page_h((string) hs_db_meta_get_scalar('schema_version', HS_MYSQL_SCHEMA_V2)) ?></strong>
            <?php if ($migratedAt !== ''): ?> · <?= hs_migrate_page_h($migratedAt) ?><?php endif; ?></p>
        <?php endif; ?>
    </section>

    <section class="hi-card">
        <h2><i class="fa-solid fa-file-code"></i> <?= hs_migrate_page_h($t['migrate_files_title'] ?? '') ?></h2>
        <ul class="hi-sec-list">
            <?php foreach ($jsonFiles as $name => $path): ?>
            <li><code><?= hs_migrate_page_h($name) ?></code> —
                <?php if (is_file($path)): ?>
                <span style="color:var(--hi-ok)"><?= hs_migrate_page_h($t['migrate_file_present'] ?? '') ?> (<?= (int) filesize($path) ?> B)</span>
                <?php else: ?>
                <span><?= hs_migrate_page_h($t['migrate_file_missing'] ?? '') ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="hi-card">
        <h2><i class="fa-solid fa-shield-halved"></i> <?= hs_migrate_page_h($t['security_title'] ?? '') ?></h2>
        <ul class="hi-sec-list">
            <li><code>data/db.config.php</code></li>
            <li><code>data/admin.config.php</code></li>
            <li><code>data/mysql-provision.config.php</code></li>
            <li><code>public_html/</code></li>
        </ul>
    </section>

    <?php if ($error !== ''): ?>
    <div class="hi-alert hi-alert-err"><?= hs_migrate_page_h($error) ?></div>
    <?php endif; ?>

    <?php if (is_array($result)): ?>
    <section class="hi-card">
        <div class="hi-alert hi-alert-ok"><?= hs_migrate_page_h($t['migrate_result_ok'] ?? '') ?></div>
        <?php if (($result['migrated'] ?? []) !== []): ?>
        <p><strong><?= hs_migrate_page_h($t['migrate_result_imported'] ?? '') ?>:</strong></p>
        <ul class="hi-sec-list"><?php foreach ($result['migrated'] as $line): ?><li><?= hs_migrate_page_h($line) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if (($result['skipped'] ?? []) !== []): ?>
        <p class="hi-hint"><strong><?= hs_migrate_page_h($t['migrate_result_skipped'] ?? '') ?>:</strong></p>
        <ul class="hi-sec-list"><?php foreach ($result['skipped'] as $line): ?><li><?= hs_migrate_page_h($line) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <div class="hi-links">
            <a class="hi-link hi-link-primary" href="<?= hs_migrate_page_h($panelUrl) ?>"><i class="fa-solid fa-gauge-high"></i> <?= hs_migrate_page_h($t['link_panel'] ?? 'Panel') ?></a>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($canRun): ?>
    <form method="post" class="hi-card">
        <p class="hi-lead"><?= hs_migrate_page_h($t['migrate_auth_hint'] ?? '') ?></p>
        <div class="hi-grid hi-grid-2">
            <div><label class="hi-label"><?= hs_migrate_page_h($t['migrate_lbl_user'] ?? '') ?></label>
                <input class="hi-input" type="text" name="admin_user" autocomplete="username" required></div>
            <div><label class="hi-label"><?= hs_migrate_page_h($t['migrate_lbl_pass'] ?? '') ?></label>
                <input class="hi-input" type="password" name="admin_pass" autocomplete="current-password" required></div>
        </div>
        <label class="hi-chk"><input type="checkbox" name="confirm_migrate" value="1" required> <?= hs_migrate_page_h($t['migrate_confirm'] ?? '') ?></label>
        <button type="submit" class="hi-btn"><i class="fa-solid fa-database"></i> <?= hs_migrate_page_h($t['migrate_submit'] ?? '') ?></button>
    </form>
    <?php endif; ?>

    <footer class="hi-foot">
        <p><?= hs_migrate_page_h($t['foot_note'] ?? '') ?></p>
    </footer>
</div>
</body>
</html>