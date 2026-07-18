<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/cpanel-provision.php';
require_once dirname(__DIR__) . '/includes/whm-api.php';
require_once dirname(__DIR__) . '/includes/plans.php';

hs_admin_require();
$admin_active = 'cpanel-pool';
$page_title = $t['admin_cpanel_pool_title'] ?? 'cPanel pool (Nebula)';

$msg = '';
$msgType = 'success';
$cfg = hs_whm_config(true);
$whmOn = hs_whm_enabled();
$credsReady = function_exists('hs_whm_credentials_ready')
    ? hs_whm_credentials_ready($cfg)
    : (trim((string) ($cfg['host'] ?? '')) !== ''
        && trim((string) ($cfg['api_user'] ?? '')) !== ''
        && trim((string) ($cfg['api_token'] ?? '')) !== '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $msg = $t['register_error_csrf'] ?? 'Security check failed (CSRF). Reload the page and try again.';
        $msgType = 'error';
    } else {
        $action = '';
        if (isset($_POST['save_pool_settings'])) {
            $action = 'save';
        } elseif (isset($_POST['test_whm'])) {
            $action = 'test';
        } elseif (isset($_POST['ensure_packages'])) {
            $action = 'ensure';
        } elseif (isset($_POST['provision_user'])) {
            $action = 'provision';
        }

        // --- Save settings ---
        if ($action === 'save') {
            $packages = [];
            $diskMap = [];
            if (is_array($_POST['pkg'] ?? null)) {
                foreach ($_POST['pkg'] as $pid => $name) {
                    $packages[(string) $pid] = trim((string) $name);
                }
            }
            if (is_array($_POST['disk'] ?? null)) {
                foreach ($_POST['disk'] as $pid => $gb) {
                    $diskMap[(string) $pid] = max(1, (int) $gb);
                }
            }
            $save = hs_whm_config_save([
                'enabled' => !empty($_POST['enabled']),
                'host' => trim((string) ($_POST['host'] ?? '')),
                'port' => (int) ($_POST['port'] ?? 2087),
                'use_ssl' => !empty($_POST['use_ssl']),
                'api_user' => trim((string) ($_POST['api_user'] ?? '')),
                'api_token' => (string) ($_POST['api_token'] ?? ''),
                'bridge_url' => trim((string) ($_POST['bridge_url'] ?? '')),
                'bridge_secret' => (string) ($_POST['bridge_secret'] ?? ''),
                'max_accounts' => max(1, (int) ($_POST['max_accounts'] ?? 25)),
                'max_disk_gb' => max(1, (int) ($_POST['max_disk_gb'] ?? 30)),
                'reserved_disk_gb' => max(0, (int) ($_POST['reserved_disk_gb'] ?? 0)),
                'warn_accounts_pct' => max(1, min(100, (int) ($_POST['warn_accounts_pct'] ?? 80))),
                'warn_disk_pct' => max(1, min(100, (int) ($_POST['warn_disk_pct'] ?? 80))),
                'auto_provision' => !empty($_POST['auto_provision']),
                'packages' => $packages,
                'disk_gb' => $diskMap,
                'package_limits' => [
                    'max_parked' => max(0, (int) ($_POST['max_parked'] ?? 0)),
                    'max_addon' => max(0, (int) ($_POST['max_addon'] ?? 2)),
                    'max_sql' => max(0, (int) ($_POST['max_sql'] ?? 5)),
                    'max_pop' => max(0, (int) ($_POST['max_pop'] ?? 10)),
                    'max_ftp' => max(0, (int) ($_POST['max_ftp'] ?? 5)),
                    'max_sub' => max(0, (int) ($_POST['max_sub'] ?? 10)),
                    'hasshell' => !empty($_POST['hasshell']),
                ],
                'default_contact_email' => trim((string) ($_POST['default_contact_email'] ?? '')),
                'nameserver_1' => trim((string) ($_POST['nameserver_1'] ?? '')),
                'nameserver_2' => trim((string) ($_POST['nameserver_2'] ?? '')),
                'client_domain_suffix' => trim((string) ($_POST['client_domain_suffix'] ?? '')),
                'cpanel_port' => (int) ($_POST['cpanel_port'] ?? 2083),
                'username_prefix' => trim((string) ($_POST['username_prefix'] ?? '')),
            ]);
            if (!empty($save['ok'])) {
                $msg = $t['admin_cpanel_settings_saved'] ?? 'Pool settings saved.';
                $msgType = 'success';
            } else {
                $msg = ($t['admin_cpanel_settings_fail'] ?? 'Could not save') . ': ' . ($save['error'] ?? '');
                $msgType = 'error';
            }
        }

        // --- Test connection (always uses force; works even if Enable is off) ---
        if ($action === 'test') {
            // Optional: apply host/user/token from form without full save
            if (isset($_POST['host']) || isset($_POST['api_user']) || isset($_POST['api_token'])) {
                $patch = [];
                if (isset($_POST['host'])) {
                    $patch['host'] = trim((string) $_POST['host']);
                }
                if (isset($_POST['port'])) {
                    $patch['port'] = (int) $_POST['port'];
                }
                if (isset($_POST['api_user'])) {
                    $patch['api_user'] = trim((string) $_POST['api_user']);
                }
                if (isset($_POST['api_token']) && trim((string) $_POST['api_token']) !== '') {
                    $patch['api_token'] = (string) $_POST['api_token'];
                }
                if (array_key_exists('use_ssl', $_POST) || isset($_POST['use_ssl'])) {
                    $patch['use_ssl'] = !empty($_POST['use_ssl']);
                }
                if ($patch !== []) {
                    hs_whm_config_save($patch);
                }
            }
            $cfg = hs_whm_config(true);
            $ready = function_exists('hs_whm_credentials_ready')
                ? hs_whm_credentials_ready($cfg)
                : (trim((string) ($cfg['host'] ?? '')) !== '' && trim((string) ($cfg['api_token'] ?? '')) !== '');
            if (!$ready) {
                $msg = $t['admin_cpanel_test_need_creds'] ?? 'Set WHM host, API user and API token first, then Save.';
                $msgType = 'error';
            } else {
                $test = hs_whm_test_connection(true);
                if (!empty($test['ok'])) {
                    // Successful test → turn on Enable so provision works
                    if (empty($cfg['enabled'])) {
                        hs_whm_config_save(['enabled' => true]);
                    }
                    $msg = ($t['admin_cpanel_test_ok'] ?? 'WHM connection OK')
                        . (isset($test['accounts']) ? ' · ' . (int) $test['accounts'] . ' ' . ($t['admin_cpanel_remote_accts'] ?? 'remote accounts') : '')
                        . (!empty($test['version']) ? ' · WHM ' . (string) $test['version'] : '');
                    $msgType = 'success';
                } else {
                    $err = (string) ($test['error'] ?? 'unknown');
                    $hint = '';
                    if ($err === 'missing_credentials') {
                        $hint = ' — fill host, API user, token and Save.';
                    } elseif (str_contains($err, 'auth_failed') || str_contains($err, 'Access denied') || str_contains(strtolower($err), 'permission')) {
                        // Stellar outbound IP often differs from site A-record (e.g. .121 vs .123)
                        $outIp = '';
                        if (function_exists('curl_init')) {
                            $chIp = curl_init('https://api.ipify.org');
                            if ($chIp !== false) {
                                curl_setopt_array($chIp, [
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_TIMEOUT => 5,
                                    CURLOPT_CONNECTTIMEOUT => 3,
                                ]);
                                $rawIp = curl_exec($chIp);
                                curl_close($chIp);
                                if (is_string($rawIp) && preg_match('/^\d{1,3}(?:\.\d{1,3}){3}$/', trim($rawIp))) {
                                    $outIp = trim($rawIp);
                                }
                            }
                        }
                        $hint = ' — WHM Access denied. Fix: (1) API Token Whitelisted IPs must include this server outbound IP'
                            . ($outIp !== '' ? ' **' . $outIp . '**' : ' (check via ipify from Stellar)')
                            . ' or leave whitelist empty; (2) re-paste token if regenerated; (3) API user = reseller (bilomiwy).';
                    } elseif (str_contains($err, 'curl') || str_contains($err, 'timed out') || str_contains($err, 'resolve')) {
                        $hint = ' — server cannot reach Nebula WHM (firewall / host / SSL / timeout).';
                    } elseif ($err === 'php_curl_missing') {
                        $hint = ' — enable PHP curl extension on Stellar.';
                    }
                    $msg = ($t['admin_cpanel_test_fail'] ?? 'WHM connection failed') . ': ' . $err . $hint;
                    $msgType = 'error';
                }
            }
        }

        // --- Ensure packages ---
        if ($action === 'ensure') {
            $cfg = hs_whm_config(true);
            $ready = function_exists('hs_whm_credentials_ready')
                ? hs_whm_credentials_ready($cfg)
                : (trim((string) ($cfg['host'] ?? '')) !== '' && trim((string) ($cfg['api_token'] ?? '')) !== '');
            if ($ready && !hs_whm_enabled()) {
                hs_whm_config_save(['enabled' => true]);
            }
            if (!hs_whm_enabled()) {
                $msg = $t['admin_cpanel_need_whm'] ?? 'Enable WHM and pass Test connection first.';
                $msgType = 'error';
            } else {
                $parts = [];
                $anyFail = false;
                foreach (hs_cpanel_pool_plan_ids() as $pid) {
                    $pkg = hs_cpanel_plan_package($pid);
                    $gb = hs_cpanel_plan_disk_gb($pid);
                    $plim = hs_cpanel_package_limits_for_plan($pid);
                    $r = hs_whm_ensure_package(
                        $pkg,
                        $gb,
                        (int) $plim['max_parked'],
                        (int) $plim['max_addon'],
                        (int) $plim['max_sql'],
                        (int) $plim['max_pop'],
                        (int) $plim['max_ftp'],
                        (int) $plim['max_sub'],
                        (bool) $plim['hasshell']
                    );
                    $ok = !empty($r['ok']);
                    if (!$ok) {
                        $anyFail = true;
                    }
                    $parts[] = $pkg . ' (' . $gb . 'GB): ' . ($ok ? 'OK' : (string) ($r['error'] ?? 'fail'));
                }
                $msg = implode(' · ', $parts);
                $msgType = $anyFail ? 'error' : 'success';
            }
        }

        // --- Manual provision ---
        if ($action === 'provision') {
            if (!hs_whm_enabled()) {
                $msg = $t['admin_cpanel_need_whm'] ?? 'Enable WHM and pass Test connection first.';
                $msgType = 'error';
            } else {
                $uid = (string) ($_POST['user_id'] ?? '');
                $u = hs_user_by_id($uid);
                if (!is_array($u)) {
                    $msg = $t['admin_cpanel_user_missing'] ?? 'User not found.';
                    $msgType = 'error';
                } elseif (($u['subscription_status'] ?? '') !== 'active') {
                    $msg = ($t['admin_cpanel_provision_fail'] ?? 'Failed') . ': inactive subscription';
                    $msgType = 'error';
                } else {
                    $r = hs_cpanel_provision_for_user($u);
                    $msg = !empty($r['ok'])
                        ? (($t['admin_cpanel_provision_ok'] ?? 'cPanel provisioned')
                            . (!empty($r['skipped']) ? ' (already exists)' : '')
                            . (!empty($r['entry']['user']) ? ': ' . (string) $r['entry']['user'] : ''))
                        : (($t['admin_cpanel_provision_fail'] ?? 'Failed') . ': ' . (string) ($r['error'] ?? ''));
                    $msgType = !empty($r['ok']) ? 'success' : 'error';
                }
            }
        }
    }

    $cfg = hs_whm_config(true);
    $whmOn = hs_whm_enabled();
    $credsReady = function_exists('hs_whm_credentials_ready')
        ? hs_whm_credentials_ready($cfg)
        : (trim((string) ($cfg['host'] ?? '')) !== '' && trim((string) ($cfg['api_token'] ?? '')) !== '');
}

$cfg = hs_whm_config(true);
$whmOn = hs_whm_enabled();
$credsReady = function_exists('hs_whm_credentials_ready')
    ? hs_whm_credentials_ready($cfg)
    : (trim((string) ($cfg['host'] ?? '')) !== '' && trim((string) ($cfg['api_token'] ?? '')) !== '');
$limits = hs_cpanel_pool_limits();
$usage = hs_cpanel_pool_usage();
$usableDisk = max(0, $limits['max_disk_gb'] - (int) $limits['reserved_disk_gb']);
$pctAcc = $limits['max_accounts'] > 0 ? round(100 * $usage['accounts'] / $limits['max_accounts']) : 0;
$pctDisk = $usableDisk > 0 ? round(100 * $usage['disk_gb'] / $usableDisk) : 0;
$warnAcc = $pctAcc >= (int) $limits['warn_accounts_pct'];
$warnDisk = $pctDisk >= (int) $limits['warn_disk_pct'];

$pkgLim = is_array($cfg['package_limits'] ?? null) ? $cfg['package_limits'] : [];
$planIds = hs_cpanel_pool_plan_ids();
$tokenSet = trim((string) ($cfg['api_token'] ?? '')) !== '';
$bridgeUrl = trim((string) ($cfg['bridge_url'] ?? ''));
$bridgeSecretSet = trim((string) ($cfg['bridge_secret'] ?? '')) !== '';

$rows = '';
foreach ($usage['rows'] as $row) {
    $rows .= '<tr>'
        . '<td>' . hs_h((string) $row['username']) . '</td>'
        . '<td><code>' . hs_h((string) $row['cpanel_user']) . '</code></td>'
        . '<td>' . hs_h((string) $row['domain']) . '</td>'
        . '<td>' . hs_h((string) $row['plan']) . '</td>'
        . '<td>' . (int) $row['disk_gb'] . ' GB</td>'
        . '<td class="hp-muted">' . hs_h((string) $row['created_at']) . '</td>'
        . '</tr>';
}
if ($rows === '') {
    $rows = '<tr><td colspan="6" class="hp-muted">' . hs_h($t['admin_cpanel_empty'] ?? 'No cPanel accounts provisioned yet.') . '</td></tr>';
}

$pending = '';
foreach (hs_users() as $u) {
    if (!is_array($u) || ($u['subscription_status'] ?? '') !== 'active') {
        continue;
    }
    if (!hs_plan_is_hosting((string) ($u['plan'] ?? ''))) {
        continue;
    }
    $id = (string) ($u['id'] ?? '');
    $acc = hs_cpanel_account_for_user($id);
    if ($acc !== null && !empty($acc['provisioned'])) {
        continue;
    }
    $pending .= '<tr>'
        . '<td>' . hs_h((string) ($u['username'] ?? '')) . '</td>'
        . '<td>' . hs_h((string) ($u['plan'] ?? '')) . '</td>'
        . '<td>' . hs_cpanel_plan_disk_gb((string) ($u['plan'] ?? 'starter')) . ' GB</td>'
        . '<td><form method="post" action="" style="display:inline">' . hs_csrf_field()
        . '<input type="hidden" name="user_id" value="' . hs_h($id) . '">'
        . '<button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm" name="provision_user" value="1"'
        . ($whmOn ? '' : ' disabled title="Enable WHM first"') . '>'
        . hs_h($t['admin_cpanel_create'] ?? 'Create cPanel') . '</button></form></td></tr>';
}
if ($pending === '') {
    $pending = '<tr><td colspan="4" class="hp-muted">—</td></tr>';
}

$quotaLine = [];
foreach ($planIds as $pid) {
    $quotaLine[] = hs_h($pid) . ' ' . hs_cpanel_plan_disk_gb($pid) . ' GB';
}

ob_start();
?>
<?php if ($msg !== ''): ?>
  <div class="hs-alert hs-alert-<?= $msgType === 'error' ? 'error' : 'success' ?>" style="margin-bottom:1rem" role="status">
    <?= hs_h($msg) ?>
  </div>
<?php endif; ?>

<div class="hs-alert" style="margin-bottom:1rem">
  <strong><i class="fa-solid fa-diagram-project"></i> Architecture</strong>
  <ul style="margin:.5rem 0 0 1.1rem;line-height:1.5">
    <li><strong>Stellar</strong> (<code>solaskinner.com</code>) — panel, checkout, admin.</li>
    <li><strong>Nebula</strong> (<code>host15.registrar-servers.com</code> / <code>bilomiwy</code>) — client cPanels after payment.</li>
  </ul>
</div>

<div class="hs-alert <?= $whmOn ? 'hs-alert-success' : ($credsReady ? '' : 'hs-alert-warn') ?>" style="margin-bottom:1rem">
  <strong>WHM (Nebula):</strong>
  <?php if ($whmOn): ?>
    <span style="color:var(--hs-ok,#059669)">enabled</span>
    · <code><?= hs_h((string) ($cfg['host'] ?? '')) ?></code>:<?= (int) ($cfg['port'] ?? 2087) ?>
    · user <code><?= hs_h((string) ($cfg['api_user'] ?? '')) ?></code>
    · token <?= $tokenSet ? '✅ set' : '❌ missing' ?>
    · bridge <?= $bridgeUrl !== '' ? '✅ HTTPS proxy' : 'direct :2087' ?>
    · auto-provision <?= !empty($cfg['auto_provision']) ? 'ON' : 'OFF' ?>
  <?php elseif ($credsReady): ?>
    credentials ready, but <strong>Enable</strong> is off — Save with Enable, or click <strong>Test</strong> (auto-enables on success).
  <?php else: ?>
    disabled — need host + API user + token, then <strong>Save</strong> + <strong>Test</strong>.
  <?php endif; ?>
  <?php if ($bridgeUrl === ''): ?>
    <br><span class="hp-muted">If Test times out on :2087 (common on Namecheap Stellar), install the <strong>HTTPS bridge</strong> on Nebula below.</span>
  <?php endif; ?>
  <?php if ($warnAcc || $warnDisk): ?>
    <br><strong style="color:var(--hs-warn,#b45309)">Pool near capacity
      (<?= $warnAcc ? 'accounts ' . (int) $pctAcc . '%' : '' ?><?= $warnAcc && $warnDisk ? ', ' : '' ?><?= $warnDisk ? 'disk ' . (int) $pctDisk . '%' : '' ?>)</strong>
  <?php endif; ?>
</div>

<?php // Quick actions OUTSIDE main form — never blocked by HTML5 validation ?>
<section class="hp-card" style="margin-bottom:1rem;padding:1rem 1.25rem">
  <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:center">
    <form method="post" action="" style="display:inline;margin:0">
      <?= hs_csrf_field() ?>
      <button type="submit" name="test_whm" value="1" class="hs-btn hs-btn-primary">
        <i class="fa-solid fa-plug"></i> Test WHM connection
      </button>
    </form>
    <form method="post" action="" style="display:inline;margin:0">
      <?= hs_csrf_field() ?>
      <button type="submit" name="ensure_packages" value="1" class="hs-btn hs-btn-ghost">
        <i class="fa-solid fa-box"></i> Create/sync WHM packages
      </button>
    </form>
    <span class="hp-muted" style="font-size:.85rem">
      Uses saved credentials (token on server). Fill form + Save first if you changed host/token.
    </span>
  </div>
</section>

<div class="hs-admin-client-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin:1rem 0">
  <div class="hp-card" style="padding:1rem">
    <div class="label">cPanel accounts (local map)</div>
    <strong style="font-size:1.5rem"><?= (int) $usage['accounts'] ?> / <?= (int) $limits['max_accounts'] ?></strong>
    <div class="hp-muted"><?= (int) $pctAcc ?>% · left: <?= max(0, (int) $limits['max_accounts'] - (int) $usage['accounts']) ?></div>
  </div>
  <div class="hp-card" style="padding:1rem">
    <div class="label">Disk allocated</div>
    <strong style="font-size:1.5rem"><?= (int) $usage['disk_gb'] ?> / <?= (int) $usableDisk ?> GB</strong>
    <div class="hp-muted"><?= (int) $pctDisk ?>% · pool <?= (int) $limits['max_disk_gb'] ?> GB</div>
  </div>
  <div class="hp-card" style="padding:1rem">
    <div class="label">Auto-provision</div>
    <strong style="font-size:1.15rem"><?= !empty($limits['auto_provision']) ? 'On (after pay)' : 'Off (manual)' ?></strong>
  </div>
</div>

<p class="hp-muted" style="margin:0 0 1.25rem">
  Plan quotas: <strong><?= implode(' · ', $quotaLine) ?></strong>
</p>

<section class="hp-card" style="margin-bottom:1.5rem">
  <h2 class="hp-card-title" style="margin:0;padding:1rem 1.25rem;border-bottom:1px solid var(--hs-border,rgba(0,0,0,.08))">
    <i class="fa-solid fa-sliders"></i> Pool &amp; WHM settings
  </h2>
  <div class="hp-card-body" style="padding:1.25rem">
    <form method="post" action="" class="hs-admin-cpanel-settings" id="hs-whm-settings-form" novalidate>
      <?= hs_csrf_field() ?>

      <h3 style="margin:0 0 .75rem;font-size:1rem">WHM connection</h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.25rem">
        <label class="hs-field" style="display:flex;align-items:center;gap:.5rem;grid-column:1/-1">
          <input type="checkbox" name="enabled" value="1" <?= !empty($cfg['enabled']) ? ' checked' : '' ?>>
          <span>Enable WHM / Nebula cPanel provisioning</span>
        </label>
        <div class="hs-field">
          <label>WHM host</label>
          <input type="text" name="host" value="<?= hs_h((string) ($cfg['host'] ?? 'host15.registrar-servers.com')) ?>" placeholder="host15.registrar-servers.com">
        </div>
        <div class="hs-field">
          <label>WHM port</label>
          <input type="number" name="port" min="1" max="65535" value="<?= (int) ($cfg['port'] ?? 2087) ?>">
        </div>
        <div class="hs-field">
          <label>cPanel port</label>
          <input type="number" name="cpanel_port" min="1" max="65535" value="<?= (int) ($cfg['cpanel_port'] ?? 2083) ?>">
        </div>
        <label class="hs-field" style="display:flex;align-items:center;gap:.5rem">
          <input type="checkbox" name="use_ssl" value="1" <?= (!array_key_exists('use_ssl', $cfg) || !empty($cfg['use_ssl'])) ? ' checked' : '' ?>>
          <span>Use HTTPS (SSL)</span>
        </label>
        <div class="hs-field">
          <label>API user (reseller)</label>
          <input type="text" name="api_user" value="<?= hs_h((string) ($cfg['api_user'] ?? 'bilomiwy')) ?>" autocomplete="off" placeholder="bilomiwy">
        </div>
        <div class="hs-field">
          <label>API token</label>
          <input type="password" name="api_token" value="" placeholder="<?= $tokenSet ? '•••••••• leave blank to keep current token' : 'Paste WHM API token' ?>" autocomplete="new-password">
          <span class="hp-muted" style="font-size:.8rem"><?= $tokenSet
              ? 'Token is saved on server. Leave blank to keep it.'
              : 'Required — paste from WHM → Manage API Tokens' ?></span>
        </div>
        <div class="hs-field" style="grid-column:1/-1">
          <label>HTTPS bridge URL (if :2087 blocked from Stellar)</label>
          <input type="url" name="bridge_url" value="<?= hs_h($bridgeUrl) ?>" placeholder="https://YOUR-DOMAIN-ON-NEBULA/sola-whm-bridge.php">
          <span class="hp-muted" style="font-size:.8rem">
            Upload <code>tools/nebula-whm-bridge.php</code> to bilomiwy public_html as <code>sola-whm-bridge.php</code>
            (via WHM File Manager from your PC). Stellar calls this over <strong>:443</strong>; bridge calls WHM on localhost:2087.
          </span>
        </div>
        <div class="hs-field">
          <label>Bridge secret</label>
          <input type="password" name="bridge_secret" value="" placeholder="<?= $bridgeSecretSet ? '•••••••• leave blank to keep' : 'Same as BRIDGE_SECRET in bridge file' ?>" autocomplete="new-password">
        </div>
      </div>

      <h3 style="margin:0 0 .75rem;font-size:1rem">Nebula pool limits</h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.25rem">
        <div class="hs-field">
          <label>Max accounts</label>
          <input type="number" name="max_accounts" min="1" max="500" value="<?= (int) ($cfg['max_accounts'] ?? 25) ?>">
        </div>
        <div class="hs-field">
          <label>Max disk (GB)</label>
          <input type="number" name="max_disk_gb" min="1" max="10000" value="<?= (int) ($cfg['max_disk_gb'] ?? 30) ?>">
        </div>
        <div class="hs-field">
          <label>Reserved disk (GB)</label>
          <input type="number" name="reserved_disk_gb" min="0" max="5000" value="<?= (int) ($cfg['reserved_disk_gb'] ?? 0) ?>">
        </div>
        <div class="hs-field">
          <label>Warn accounts %</label>
          <input type="number" name="warn_accounts_pct" min="1" max="100" value="<?= (int) ($cfg['warn_accounts_pct'] ?? 80) ?>">
        </div>
        <div class="hs-field">
          <label>Warn disk %</label>
          <input type="number" name="warn_disk_pct" min="1" max="100" value="<?= (int) ($cfg['warn_disk_pct'] ?? 80) ?>">
        </div>
        <label class="hs-field" style="display:flex;align-items:center;gap:.5rem;align-self:end;padding-bottom:.35rem">
          <input type="checkbox" name="auto_provision" value="1" <?= (!array_key_exists('auto_provision', $cfg) || !empty($cfg['auto_provision'])) ? ' checked' : '' ?>>
          <span>Auto-create cPanel after hosting payment</span>
        </label>
      </div>

      <h3 style="margin:0 0 .75rem;font-size:1rem">DNS &amp; client defaults</h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.25rem">
        <div class="hs-field">
          <label>Default contact email</label>
          <input type="email" name="default_contact_email" value="<?= hs_h((string) ($cfg['default_contact_email'] ?? 'support@solaskinner.com')) ?>">
        </div>
        <div class="hs-field">
          <label>Nameserver 1</label>
          <input type="text" name="nameserver_1" value="<?= hs_h((string) ($cfg['nameserver_1'] ?? 'dns1.namecheaphosting.com')) ?>">
        </div>
        <div class="hs-field">
          <label>Nameserver 2</label>
          <input type="text" name="nameserver_2" value="<?= hs_h((string) ($cfg['nameserver_2'] ?? 'dns2.namecheaphosting.com')) ?>">
        </div>
        <div class="hs-field">
          <label>Temp client domain suffix</label>
          <input type="text" name="client_domain_suffix" value="<?= hs_h((string) ($cfg['client_domain_suffix'] ?? 'clients.solaskinner.com')) ?>">
        </div>
        <div class="hs-field">
          <label>cPanel username prefix</label>
          <input type="text" name="username_prefix" value="<?= hs_h((string) ($cfg['username_prefix'] ?? 'sola')) ?>" maxlength="8">
        </div>
      </div>

      <h3 style="margin:0 0 .75rem;font-size:1rem">Default package resources</h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:1rem;margin-bottom:1.25rem">
        <div class="hs-field"><label>maxpark</label><input type="number" name="max_parked" min="0" value="<?= (int) ($pkgLim['max_parked'] ?? 0) ?>"></div>
        <div class="hs-field"><label>maxaddon</label><input type="number" name="max_addon" min="0" value="<?= (int) ($pkgLim['max_addon'] ?? 5) ?>"></div>
        <div class="hs-field"><label>maxsql</label><input type="number" name="max_sql" min="0" value="<?= (int) ($pkgLim['max_sql'] ?? 10) ?>"></div>
        <div class="hs-field"><label>maxpop</label><input type="number" name="max_pop" min="0" value="<?= (int) ($pkgLim['max_pop'] ?? 15) ?>"></div>
        <div class="hs-field"><label>maxftp</label><input type="number" name="max_ftp" min="0" value="<?= (int) ($pkgLim['max_ftp'] ?? 10) ?>"></div>
        <div class="hs-field"><label>maxsub</label><input type="number" name="max_sub" min="0" value="<?= (int) ($pkgLim['max_sub'] ?? 20) ?>"></div>
        <label class="hs-field" style="display:flex;align-items:center;gap:.5rem;align-self:end;padding-bottom:.35rem">
          <input type="checkbox" name="hasshell" value="1" <?= !empty($pkgLim['hasshell']) ? ' checked' : '' ?>>
          <span>SSH shell</span>
        </label>
      </div>

      <h3 style="margin:0 0 .75rem;font-size:1rem">Packages &amp; disk per plan</h3>
      <div class="hs-table-wrap" style="margin-bottom:1.25rem">
        <table class="hs-table" style="width:100%">
          <thead>
            <tr><th>Plan</th><th>WHM package</th><th>Disk GB</th></tr>
          </thead>
          <tbody>
            <?php foreach ($planIds as $pid):
                $pkgName = hs_cpanel_plan_package($pid);
                $diskVal = hs_cpanel_plan_disk_gb($pid);
                ?>
            <tr>
              <td><code><?= hs_h($pid) ?></code></td>
              <td><input type="text" name="pkg[<?= hs_h($pid) ?>]" value="<?= hs_h($pkgName) ?>" style="width:100%;min-width:12rem"></td>
              <td><input type="number" name="disk[<?= hs_h($pid) ?>]" min="1" max="500" value="<?= (int) $diskVal ?>" style="width:6rem"></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;padding-top:.5rem;border-top:1px solid var(--hs-border,#e2e8f0)">
        <button type="submit" name="save_pool_settings" value="1" class="hs-btn hs-btn-primary">
          <i class="fa-solid fa-floppy-disk"></i> Save settings
        </button>
        <button type="submit" name="test_whm" value="1" class="hs-btn hs-btn-ghost">
          <i class="fa-solid fa-plug"></i> Save fields + Test
        </button>
        <button type="submit" name="ensure_packages" value="1" class="hs-btn hs-btn-ghost">
          <i class="fa-solid fa-box"></i> Create/sync packages
        </button>
      </div>
      <p class="hp-muted" style="margin:.75rem 0 0;font-size:.85rem">
        Order: <strong>Save</strong> (Enable + token) → top <strong>Test</strong> → <strong>Create/sync packages</strong> → Create cPanel for pending clients.
      </p>
    </form>
  </div>
</section>

<h3>Provisioned accounts (local map)</h3>
<table class="hs-table" style="width:100%">
  <thead>
    <tr>
      <th>Client</th><th>cPanel</th><th>Domain</th><th>Plan</th><th>Disk</th><th>Created</th>
    </tr>
  </thead>
  <tbody><?= $rows ?></tbody>
</table>

<h3 style="margin-top:2rem">Active clients without cPanel</h3>
<table class="hs-table" style="width:100%">
  <thead><tr><th>Client</th><th>Plan</th><th>Quota</th><th></th></tr></thead>
  <tbody><?= $pending ?></tbody>
</table>

<style>
.hs-admin-cpanel-settings .hs-field label{display:block;font-size:.85rem;font-weight:600;margin-bottom:.25rem}
.hs-admin-cpanel-settings .hs-field input[type=text],
.hs-admin-cpanel-settings .hs-field input[type=email],
.hs-admin-cpanel-settings .hs-field input[type=number],
.hs-admin-cpanel-settings .hs-field input[type=password]{
  width:100%;max-width:100%;padding:.45rem .6rem;border:1px solid var(--hs-border,#cbd5e1);border-radius:.4rem;background:var(--hs-input-bg,#fff)
}
.hs-admin-cpanel-settings h3{color:var(--hs-text-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;font-size:.75rem!important}
</style>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';
