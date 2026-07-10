<?php
/**
 * Hosting CMS — MySQL installation wizard.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/mysql-install.php';

const HS_INSTALL_UI_VERSION = '1.0.0';

$appRoot = __DIR__;
$dataDir = HS_DATA_DIR;
$installed = hs_install_mysql_ready($dataDir);
$requirements = hs_install_requirements($dataDir, $appRoot);
$reqOk = !in_array(false, array_column($requirements, 'ok'), true);
$error = '';
$success = null;

if (!$installed && $reqOk && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $success = hs_install_run($_POST, $appRoot, $dataDir);
    if (!empty($success['ok'])) {
        $installed = true;
    } else {
        $error = $success['error'] ?? 'Installation failed.';
        $success = null;
    }
}

function hs_install_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

global $base_path;
$prefix = rtrim((string) ($base_path ?? ''), '/');
$homeUrl = ($prefix !== '' ? $prefix : '') . '/';
$adminUrl = $homeUrl . 'admin/';
$panelUrl = $homeUrl . 'panel/';
?><!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#059669">
    <title>Hosting CMS — Установка MySQL</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
    <style>
        :root { --bg:#f0fdf4; --card:#fff; --text:#0f172a; --muted:#64748b; --p:#059669; --p2:#10b981; --ok:#16a34a; --err:#dc2626; --border:#d1fae5; }
        *{box-sizing:border-box} body{margin:0;min-height:100vh;font-family:'DM Sans',system-ui,sans-serif;color:var(--text);
        background:radial-gradient(900px 500px at 100% -10%,rgba(16,185,129,.15),transparent),var(--bg);padding:24px 16px 48px}
        .wrap{max-width:720px;margin:0 auto} .hero{text-align:center;margin-bottom:24px}
        .logo{width:72px;height:72px;margin:0 auto 12px;border-radius:20px;background:linear-gradient(135deg,var(--p),var(--p2));
        display:flex;align-items:center;justify-content:center;font-size:30px;color:#fff}
        h1{margin:0 0 8px;font-size:1.7rem} .sub{margin:0;color:var(--muted);font-size:15px;line-height:1.5}
        .card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px;margin-bottom:16px;box-shadow:0 8px 30px rgba(5,150,105,.08)}
        .card h2{margin:0 0 14px;font-size:1.05rem;display:flex;align-items:center;gap:8px}
        .req{display:flex;justify-content:space-between;padding:8px 10px;border-radius:8px;background:#f8fafc;margin-bottom:6px;font-size:14px}
        .ok{color:var(--ok)} .bad{color:var(--err)}
        .grid{display:grid;gap:12px} @media(min-width:560px){.g2{grid-template-columns:1fr 1fr}}
        label{display:block;font-size:11px;font-weight:600;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.04em}
        input{width:100%;padding:12px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:var(--text);font-size:15px}
        input[type=checkbox]{width:auto;margin-right:8px}
        .btn{width:100%;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--p),var(--p2));
        color:#fff;font-weight:700;font-size:16px;cursor:pointer;margin-top:8px}
        .btn:disabled{opacity:.5;cursor:not-allowed}
        .alert{padding:12px;border-radius:10px;margin-bottom:12px;font-size:14px}
        .alert-e{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
        .alert-ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
        .links{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
        .link{flex:1;min-width:130px;text-align:center;padding:12px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px}
        .lp{background:var(--p);color:#fff} .lo{border:1px solid var(--border);color:var(--text)}
        .foot{text-align:center;margin-top:24px;color:var(--muted);font-size:12px;line-height:1.6}
        .hint{font-size:12px;color:var(--muted);margin-top:6px}
        ul.steps{margin:0;padding-left:20px;color:var(--muted);font-size:14px;line-height:1.7}
        .chk{display:flex;align-items:center;font-size:14px;color:var(--text);margin-top:8px}
    </style>
</head>
<body>
<div class="wrap">
    <div class="hero">
        <div class="logo"><i class="fa-solid fa-server"></i></div>
        <h1>Hosting CMS</h1>
        <p class="sub">Установка MySQL · v<?= hs_install_h(HS_INSTALL_UI_VERSION) ?></p>
    </div>

    <?php if ($installed && $success): ?>
    <div class="card">
        <div class="alert alert-ok"><i class="fa-solid fa-check-circle"></i> Установку завершено!</div>
        <p>Таблиці створено, <code>data/db.config.php</code> записано.</p>
        <p><strong>Адмін панелі:</strong> <?= hs_install_h((string) ($success['admin_user'] ?? 'administrator')) ?> / ваш пароль</p>
        <p class="hint">Демо-клієнт (якщо увімкнено): <strong>demo</strong> / <strong>demo</strong></p>
        <p class="hint"><strong>Крок 4:</strong> налаштуйте <a href="<?= hs_install_h($adminUrl . 'mysql.php') ?>">MySQL provisioning</a> — локальний або віддалений сервер, один MySQL на клієнта.</p>
        <div class="links">
            <a class="link lp" href="<?= hs_install_h($homeUrl) ?>"><i class="fa-solid fa-house"></i> Головна</a>
            <a class="link lo" href="<?= hs_install_h($panelUrl) ?>"><i class="fa-solid fa-gauge-high"></i> hPanel</a>
            <a class="link lo" href="<?= hs_install_h($adminUrl) ?>"><i class="fa-solid fa-lock"></i> Admin</a>
        </div>
    </div>
    <?php elseif ($installed): ?>
    <div class="card">
        <div class="alert alert-ok"><i class="fa-solid fa-database"></i> MySQL уже налаштовано.</div>
        <p>Файл <code>data/installed.lock</code> існує. Для перевстановлення видаліть <code>db.config.php</code> та <code>installed.lock</code>.</p>
        <div class="links">
            <a class="link lp" href="<?= hs_install_h($homeUrl) ?>">На сайт</a>
            <a class="link lo" href="<?= hs_install_h($panelUrl) ?>">Панель</a>
        </div>
    </div>
    <?php else: ?>
    <?php if ($error !== ''): ?>
    <div class="alert alert-e"><?= hs_install_h($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fa-solid fa-list-check"></i> Вимоги сервера</h2>
        <?php foreach ($requirements as $r): ?>
        <div class="req">
            <span><?= hs_install_h($r['label']) ?></span>
            <span class="<?= $r['ok'] ? 'ok' : 'bad' ?>"><?= $r['ok'] ? '✓' : '✗' ?> <?= hs_install_h($r['hint']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2><i class="fa-solid fa-database"></i> Крок 1 — MySQL</h2>
        <p class="hint">Створіть порожню базу в phpMyAdmin або через SSH:</p>
        <ul class="steps">
            <li><code>CREATE DATABASE hosting_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code></li>
            <li><code>CREATE USER 'hosting_user'@'localhost' IDENTIFIED BY '...';</code></li>
            <li><code>GRANT ALL ON hosting_cms.* TO 'hosting_user'@'localhost';</code></li>
        </ul>
    </div>

    <form method="post" class="card">
        <h2><i class="fa-solid fa-plug"></i> Крок 2 — Підключення</h2>
        <div class="grid g2">
            <div><label>Хост MySQL</label><input name="db_host" value="<?= hs_install_h($_POST['db_host'] ?? 'localhost') ?>" required></div>
            <div><label>Ім'я бази</label><input name="db_name" value="<?= hs_install_h($_POST['db_name'] ?? 'hosting_cms') ?>" required></div>
            <div><label>Користувач MySQL</label><input name="db_user" value="<?= hs_install_h($_POST['db_user'] ?? '') ?>" required autocomplete="off"></div>
            <div><label>Пароль MySQL</label><input type="password" name="db_pass" autocomplete="new-password"></div>
            <div><label>Префікс таблиць</label><input name="db_prefix" value="<?= hs_install_h($_POST['db_prefix'] ?? 'hs_') ?>"></div>
        </div>

        <h2 style="margin-top:20px"><i class="fa-solid fa-user-shield"></i> Крок 3 — Адмін платформи</h2>
        <div class="grid g2">
            <div><label>Логін admin</label><input name="admin_user" value="<?= hs_install_h($_POST['admin_user'] ?? 'admin') ?>" required></div>
            <div><label>Пароль admin (мін. 6)</label><input type="password" name="admin_pass" required autocomplete="new-password"></div>
        </div>
        <label class="chk"><input type="checkbox" name="seed_demo" value="1" checked> Імпортувати демо (demo/demo + admin/admin)</label>

        <button type="submit" class="btn" <?= $reqOk ? '' : 'disabled' ?>>
            <i class="fa-solid fa-rocket"></i> Встановити MySQL
        </button>
        <?php if (!$reqOk): ?><p class="hint">Виправте вимоги сервера перед установкою.</p><?php endif; ?>
    </form>
    <?php endif; ?>

    <p class="foot">Hosting CMS · MySQL schema <?= hs_install_h(HS_MYSQL_SCHEMA_VERSION) ?><br>
    Після установки видаліть або захистіть <code>install.php</code> на production.</p>
</div>
</body>
</html>