<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/phpmyadmin.php';

hs_admin_require();
$admin_active = 'mysql';

$root = dirname(__DIR__);
$pmaIndex = $root . '/pma/index.php';
$done = is_file($pmaIndex);
$error = '';
$log = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_pma'])) {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = 'CSRF';
    } elseif ($done) {
        $log[] = 'phpMyAdmin already installed';
    } elseif (!function_exists('shell_exec')) {
        $error = 'shell_exec disabled on server';
    } else {
        $tmp = $root . '/data/pma-tmp';
        if (!is_dir($tmp)) {
            mkdir($tmp, 0750, true);
        }
        $archive = $tmp . '/pma.tar.gz';
        $url = 'https://files.phpmyadmin.net/phpMyAdmin/5.2.2/phpMyAdmin-5.2.2-all-languages.tar.gz';
        $dl = @shell_exec('curl -fsSL ' . escapeshellarg($url) . ' -o ' . escapeshellarg($archive) . ' 2>&1');
        if (!is_file($archive) || filesize($archive) < 1000000) {
            $error = 'Download failed: ' . trim((string) $dl);
        } else {
            $staging = $tmp . '/staging';
            @mkdir($staging, 0755, true);
            @shell_exec('tar -xzf ' . escapeshellarg($archive) . ' -C ' . escapeshellarg($staging) . ' 2>&1');
            $src = $staging . '/phpMyAdmin-5.2.2-all-languages';
            if (!is_dir($src)) {
                $error = 'Extract failed';
            } else {
                if (!is_dir($root . '/pma')) {
                    mkdir($root . '/pma', 0755, true);
                }
                $copy = 'cp -a ' . escapeshellarg($src . '/.') . ' ' . escapeshellarg($root . '/pma/') . ' 2>&1';
                $cpOut = @shell_exec($copy);
                @unlink($archive);
                @shell_exec('rm -rf ' . escapeshellarg($staging));
                $done = is_file($pmaIndex);
                if (!$done) {
                    $error = 'Copy failed: ' . trim((string) $cpOut);
                } else {
                    $log[] = 'phpMyAdmin 5.2.2 installed';
                }
            }
        }
        $cfgFile = HS_DATA_DIR . '/pma.config.php';
        if (!is_file($cfgFile)) {
            $secret = bin2hex(random_bytes(24));
            file_put_contents($cfgFile, "<?php\nreturn ['blowfish_secret' => " . var_export($secret, true) . "];\n");
            @chmod($cfgFile, 0640);
            $log[] = 'Created pma.config.php';
        }
    }
}

$page_title = 'Install phpMyAdmin';
ob_start();
?>
<div class="hs-admin-page" style="max-width:640px">
  <h1><i class="fa-solid fa-database"></i> phpMyAdmin</h1>
  <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>
  <?php foreach ($log as $line): ?><div class="hs-alert hs-alert-success"><?= hs_h($line) ?></div><?php endforeach; ?>
  <p class="hp-muted">Sign-on mode: кожен клієнт входить під своїм MySQL-користувачем — бачить лише свою базу.</p>
  <p><strong>Status:</strong> <?= $done ? '✅ Installed' : '❌ Not installed (missing pma/index.php)' ?></p>
  <?php if (!$done): ?>
  <form method="post"><?= hs_csrf_field() ?>
    <button type="submit" name="install_pma" value="1" class="hs-btn hs-btn-primary">Download &amp; install phpMyAdmin 5.2.2</button>
  </form>
  <?php else: ?>
  <p><a href="<?= hs_h(hs_pma_index_url()) ?>" class="hs-btn hs-btn-ghost" target="_blank" rel="noopener">Open pma/</a></p>
  <?php endif; ?>
  <p style="margin-top:1.5rem"><a href="<?= hs_h(hs_admin_url('mysql.php')) ?>">← MySQL provisioning</a></p>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';