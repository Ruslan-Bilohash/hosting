<?php
/**
 * Hosting CMS — import JSON runtime data into MySQL (schema 2.0).
 * Run once after upgrade. Backs up JSON to data/json-backup/.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/db-migrate.php';
require_once __DIR__ . '/includes/admin-auth.php';

if (hs_db_pdo() instanceof PDO) {
    hs_db_ensure_schema();
}

function hs_migrate_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
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
        $error = 'Підтвердіть імпорт (checkbox).';
    } elseif (!hs_admin_verify_credentials($adminUser, $adminPass)) {
        $error = 'Невірний логін або пароль платформного адміна (data/admin.config.php).';
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
?><!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Hosting CMS — Міграція JSON → MySQL</title>
    <style>
        :root { --bg:#f0f9ff; --card:#fff; --text:#0f172a; --muted:#64748b; --p:#0284c7; --ok:#16a34a; --err:#dc2626; --border:#bae6fd; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; }
        .wrap { max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; }
        h1 { font-size: 1.35rem; margin: 0 0 .5rem; }
        .muted { color: var(--muted); font-size: .92rem; }
        .err { color: var(--err); background: #fef2f2; padding: .75rem; border-radius: 8px; }
        .ok { color: var(--ok); }
        ul { margin: .5rem 0; padding-left: 1.25rem; }
        label { display: block; margin: .75rem 0 .25rem; font-weight: 500; }
        input[type=text], input[type=password] { width: 100%; padding: .5rem .75rem; border: 1px solid var(--border); border-radius: 8px; }
        button { margin-top: 1rem; background: var(--p); color: #fff; border: 0; padding: .65rem 1.25rem; border-radius: 8px; cursor: pointer; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        td, th { text-align: left; padding: .4rem .5rem; border-bottom: 1px solid var(--border); }
        code { font-size: .82rem; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Міграція JSON → MySQL</h1>
        <p class="muted">Схема <?= hs_migrate_h(HS_MYSQL_SCHEMA_V2) ?> — усі runtime-дані CMS переносяться в MySQL. JSON копії зберігаються в <code>data/json-backup/</code>.</p>
        <?php if (!$canRun): ?>
            <p class="err">Спочатку налаштуйте MySQL через <a href="<?= hs_migrate_h($homeUrl . 'install.php') ?>">install.php</a> (потрібен <code>data/db.config.php</code>).</p>
        <?php else: ?>
            <p class="muted">Schema: <strong><?= hs_migrate_h((string) hs_db_meta_get_scalar('schema_version', '—')) ?></strong>
                <?php if ($migratedAt !== ''): ?> · Імпорт: <strong><?= hs_migrate_h($migratedAt) ?></strong><?php endif; ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="font-size:1.1rem;margin:0 0 .75rem">Файли JSON на сервері</h2>
        <table>
            <tr><th>Файл</th><th>Статус</th></tr>
            <?php foreach ($jsonFiles as $name => $path): ?>
            <tr>
                <td><code><?= hs_migrate_h($name) ?></code></td>
                <td><?= is_file($path) ? '<span class="ok">є (' . (int) filesize($path) . ' B)</span>' : '<span class="muted">немає</span>' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h2 style="font-size:1.1rem;margin:0 0 .75rem">Що лишається у файлах (безпека)</h2>
        <ul class="muted">
            <li><code>data/db.config.php</code> — доступ CMS до MySQL</li>
            <li><code>data/admin.config.php</code> — супер-адмін панелі</li>
            <li><code>data/mysql-provision.config.php</code> — root для створення БД клієнтів</li>
            <li><code>data/client-db/*.php</code> — паролі БД для ecosystem-додатків</li>
            <li><code>data/ssh.config.local.php</code>, <code>config.local.php</code> — SSH / локальні секрети</li>
            <li><code>public_html/</code> — файли сайтів клієнтів (не в MySQL)</li>
        </ul>
    </div>

    <?php if ($error !== ''): ?>
    <div class="card err"><?= hs_migrate_h($error) ?></div>
    <?php endif; ?>

    <?php if (is_array($result)): ?>
    <div class="card">
        <h2 style="font-size:1.1rem;margin:0 0 .75rem">Результат</h2>
        <?php if (($result['migrated'] ?? []) !== []): ?>
            <p class="ok">Імпортовано:</p>
            <ul><?php foreach ($result['migrated'] as $line): ?><li><?= hs_migrate_h($line) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if (($result['skipped'] ?? []) !== []): ?>
            <p class="muted">Пропущено:</p>
            <ul><?php foreach ($result['skipped'] as $line): ?><li><?= hs_migrate_h($line) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if (($result['errors'] ?? []) !== []): ?>
            <p class="err">Помилки:</p>
            <ul><?php foreach ($result['errors'] as $line): ?><li><?= hs_migrate_h($line) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <p><a href="<?= hs_migrate_h($homeUrl . 'panel/') ?>">→ Клієнтська панель</a></p>
    </div>
    <?php endif; ?>

    <?php if ($canRun): ?>
    <div class="card">
        <form method="post">
            <p class="muted">Потрібні облікові дані з <code>data/admin.config.php</code>. Імпорт безпечний повторно: заповнені таблиці не перезаписуються.</p>
            <label>Логін адміна</label>
            <input type="text" name="admin_user" autocomplete="username" required>
            <label>Пароль адміна</label>
            <input type="password" name="admin_pass" autocomplete="current-password" required>
            <label><input type="checkbox" name="confirm_migrate" value="1"> Імпортувати JSON у MySQL і зробити backup</label>
            <button type="submit">Запустити міграцію</button>
        </form>
    </div>
    <?php endif; ?>

    <p class="muted"><a href="<?= hs_migrate_h($homeUrl) ?>">← На головну</a></p>
</div>
</body>
</html>