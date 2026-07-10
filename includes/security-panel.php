<?php
declare(strict_types=1);

require_once __DIR__ . '/performance.php';
require_once __DIR__ . '/user-settings.php';

const HS_SEC_MARKER_SSL = 'BILOHASH-SSL';
const HS_SEC_MARKER_FIREWALL = 'BILOHASH-FIREWALL';
const HS_SEC_MARKER_HOTLINK = 'BILOHASH-HOTLINK';
const HS_SEC_MARKER_INDEXING = 'BILOHASH-INDEXING';
const HS_SEC_MARKER_IPBLOCK = 'BILOHASH-IPBLOCK';

function hs_sec_htaccess(string $username): string
{
    return hs_perf_user_htaccess($username);
}

function hs_sec_has_marker(string $username, string $marker): bool
{
    $file = hs_sec_htaccess($username);
    if (!is_file($file)) {
        return false;
    }
    return str_contains((string) file_get_contents($file), '# BEGIN ' . $marker);
}

function hs_sec_ssl_block(): string
{
    return <<<'HT'
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{HTTPS} !=on
  RewriteCond %{HTTP:X-Forwarded-Proto} !https
  RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
HT;
}

function hs_sec_firewall_block(): string
{
    return <<<'HT'
<IfModule mod_headers.c>
  Header always set X-Frame-Options "SAMEORIGIN"
  Header always set X-Content-Type-Options "nosniff"
  Header always set Referrer-Policy "strict-origin-when-cross-origin"
  Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
</IfModule>
<FilesMatch "^(wp-config\.php|\.env|config\.php|php\.ini|\.user\.ini)$">
  Require all denied
</FilesMatch>
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule (^|/)\.git(/|$) - [F,L]
  RewriteRule (^|/)\.env$ - [F,L]
  RewriteCond %{QUERY_STRING} (\<|%3C).*script.*(\>|%3E) [NC,OR]
  RewriteCond %{QUERY_STRING} GLOBALS(=|\[) [OR]
  RewriteCond %{QUERY_STRING} _REQUEST(=|\[)
  RewriteRule .* - [F,L]
</IfModule>
HT;
}

function hs_sec_hotlink_block(string $domain): string
{
    $domain = preg_replace('/[^a-z0-9.-]/i', '', $domain) ?: 'bilohash.com';
    return <<<HT
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{HTTP_REFERER} !^$
  RewriteCond %{HTTP_REFERER} !^https?://([^.]+\.)?{$domain} [NC]
  RewriteRule \.(jpe?g|png|gif|webp|svg|ico)$ - [F,L]
</IfModule>
HT;
}

function hs_sec_indexing_block(bool $allow): string
{
    return $allow ? '' : "Options -Indexes\n";
}

/** @param list<string> $blockedIps */
function hs_sec_ipblock_block(array $blockedIps): string
{
    $lines = '';
    foreach ($blockedIps as $ip) {
        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $lines .= "Require not ip {$ip}\n";
        }
    }
    if ($lines === '') {
        return '';
    }
    return "<RequireAll>\nRequire all granted\n{$lines}</RequireAll>\n";
}

/** @return array{ok:bool,enabled:bool,error?:string} */
function hs_sec_set_ssl(array $user, bool $enable): array
{
    $username = (string) ($user['username'] ?? 'user');
    $block = $enable ? hs_sec_ssl_block() : '';
    if (!hs_perf_patch_htaccess(hs_sec_htaccess($username), HS_SEC_MARKER_SSL, $block)) {
        return ['ok' => false, 'enabled' => $enable, 'error' => 'htaccess'];
    }
    return ['ok' => true, 'enabled' => $enable];
}

/** @return array{ok:bool,enabled:bool,error?:string} */
function hs_sec_set_firewall(array $user, bool $enable): array
{
    $username = (string) ($user['username'] ?? 'user');
    $block = $enable ? hs_sec_firewall_block() : '';
    if (!hs_perf_patch_htaccess(hs_sec_htaccess($username), HS_SEC_MARKER_FIREWALL, $block)) {
        return ['ok' => false, 'enabled' => $enable, 'error' => 'htaccess'];
    }
    return ['ok' => true, 'enabled' => $enable];
}

/** @return array{ok:bool,enabled:bool,error?:string} */
function hs_sec_set_hotlink(array $user, bool $enable, string $domain): array
{
    $username = (string) ($user['username'] ?? 'user');
    $block = $enable ? hs_sec_hotlink_block($domain) : '';
    if (!hs_perf_patch_htaccess(hs_sec_htaccess($username), HS_SEC_MARKER_HOTLINK, $block)) {
        return ['ok' => false, 'enabled' => $enable, 'error' => 'htaccess'];
    }
    return ['ok' => true, 'enabled' => $enable];
}

/** @return array{ok:bool,enabled:bool,error?:string} */
function hs_sec_set_indexing(array $user, bool $allowListing): array
{
    $username = (string) ($user['username'] ?? 'user');
    $block = hs_sec_indexing_block($allowListing);
    if (!hs_perf_patch_htaccess(hs_sec_htaccess($username), HS_SEC_MARKER_INDEXING, $block)) {
        return ['ok' => false, 'enabled' => $allowListing, 'error' => 'htaccess'];
    }
    return ['ok' => true, 'enabled' => $allowListing];
}

/** @param list<string> $blocked */
function hs_sec_set_ip_block(array $user, array $blocked): array
{
    $username = (string) ($user['username'] ?? 'user');
    $block = hs_sec_ipblock_block($blocked);
    if (!hs_perf_patch_htaccess(hs_sec_htaccess($username), HS_SEC_MARKER_IPBLOCK, $block)) {
        return ['ok' => false, 'error' => 'htaccess'];
    }
    return ['ok' => true];
}

/** @return array<string, string> */
function hs_sec_malware_patterns(): array
{
    return [
        '/eval\s*\(\s*base64_decode\s*\(/i' => 'eval_base64',
        '/gzinflate\s*\(\s*base64_decode/i' => 'gzinflate_obfuscated',
        '/preg_replace\s*\([^)]*\/e["\']/i' => 'preg_replace_e',
        '/shell_exec\s*\(/i' => 'shell_exec',
        '/passthru\s*\(/i' => 'passthru',
        '/system\s*\(\s*\$_/i' => 'system_superglobal',
        '/assert\s*\(\s*\$_/i' => 'assert_injection',
        '/FilesMan/i' => 'webshell_filesman',
        '/c99shell|r57shell|WSO\s/i' => 'known_shell',
    ];
}

/** @return array{ok:bool,status:string,scanned:int,findings:list<array<string,string>>,error?:string} */
function hs_sec_run_malware_scan(array $user): array
{
    $username = (string) ($user['username'] ?? 'user');
    $base = hs_public_path($username);
    if (!is_dir($base)) {
        return ['ok' => true, 'status' => 'clean', 'scanned' => 0, 'findings' => []];
    }
    $patterns = hs_sec_malware_patterns();
    $findings = [];
    $scanned = 0;
    $maxFiles = 8000;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $scanned++;
        if ($scanned > $maxFiles) {
            break;
        }
        $path = $file->getPathname();
        $rel = ltrim(str_replace('\\', '/', substr($path, strlen($base))), '/');
        if (preg_match('#/(vendor|node_modules|cache|\.git)/#i', '/' . $rel)) {
            continue;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['php', 'phtml', 'php5', 'inc', 'js', 'htaccess'], true) && $file->getFilename() !== '.htaccess') {
            if ($ext !== '' && $ext !== 'html') {
                continue;
            }
        }
        $size = $file->getSize();
        if ($size > 524288) {
            continue;
        }
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            continue;
        }
        foreach ($patterns as $regex => $type) {
            if (preg_match($regex, $content, $m, PREG_OFFSET_CAPTURE)) {
                $line = 1;
                if (isset($m[0][1])) {
                    $line = substr_count(substr($content, 0, (int) $m[0][1]), "\n") + 1;
                }
                $findings[] = [
                    'path' => $rel,
                    'type' => $type,
                    'line' => (string) $line,
                ];
                break;
            }
        }
        if (count($findings) >= 50) {
            break;
        }
    }
    $status = $findings === [] ? 'clean' : 'issues';
    return ['ok' => true, 'status' => $status, 'scanned' => $scanned, 'findings' => $findings];
}

/** @return array{ssl:bool,firewall:bool,hotlink:bool,indexing_blocked:bool,ipblock:bool} */
function hs_sec_live_status(array $user, array $settings): array
{
    $username = (string) ($user['username'] ?? 'user');
    return [
        'ssl' => hs_sec_has_marker($username, HS_SEC_MARKER_SSL) || !empty($settings['ssl_enabled']),
        'firewall' => hs_sec_has_marker($username, HS_SEC_MARKER_FIREWALL) || !empty($settings['firewall_enabled']),
        'hotlink' => hs_sec_has_marker($username, HS_SEC_MARKER_HOTLINK) || !empty($settings['hotlink_protect']),
        'indexing_blocked' => hs_sec_has_marker($username, HS_SEC_MARKER_INDEXING) || empty($settings['search_indexing']),
        'ipblock' => hs_sec_has_marker($username, HS_SEC_MARKER_IPBLOCK) || !empty($settings['ip_blocklist']),
    ];
}

/** Apply saved settings to .htaccess when markers are missing (first visit / defaults). */
function hs_sec_sync_htaccess(array $user, array $settings): void
{
    $username = (string) ($user['username'] ?? 'user');
    if (!empty($settings['ssl_enabled']) && !hs_sec_has_marker($username, HS_SEC_MARKER_SSL)) {
        hs_sec_set_ssl($user, true);
    }
    if (!empty($settings['firewall_enabled']) && !hs_sec_has_marker($username, HS_SEC_MARKER_FIREWALL)) {
        hs_sec_set_firewall($user, true);
    }
    if (!empty($settings['hotlink_protect']) && !hs_sec_has_marker($username, HS_SEC_MARKER_HOTLINK)) {
        $domain = (string) ($settings['primary_domain'] ?? hs_default_primary_domain());
        hs_sec_set_hotlink($user, true, $domain);
    }
    if (empty($settings['search_indexing']) && !hs_sec_has_marker($username, HS_SEC_MARKER_INDEXING)) {
        hs_sec_set_indexing($user, false);
    }
    $blocked = is_array($settings['ip_blocklist'] ?? null) ? $settings['ip_blocklist'] : [];
    if ($blocked !== [] && !hs_sec_has_marker($username, HS_SEC_MARKER_IPBLOCK)) {
        hs_sec_set_ip_block($user, $blocked);
    }
}

function hs_sec_sync_wp_auto_update(string $userId, bool $enabled): void
{
    if (!function_exists('hs_wordpress_installs')) {
        require_once __DIR__ . '/wordpress-install.php';
    }
    $installs = hs_wordpress_installs($userId);
    foreach ($installs as $id => $meta) {
        $installs[$id]['auto_update'] = $enabled;
    }
    if ($installs !== []) {
        hs_user_settings_save($userId, ['wp_installs' => $installs]);
    }
}

function hs_sec_render_findings(array $findings, array $t): string
{
    if ($findings === []) {
        return '<p class="hp-muted hp-sec-ok"><i class="fa-solid fa-circle-check"></i> ' . hs_h($t['security_scan_ok'] ?? 'Clean') . '</p>';
    }
    $rows = array_map(static fn($f) => '<tr><td><code>' . hs_h((string) ($f['path'] ?? '')) . '</code></td>'
        . '<td>' . hs_h((string) ($f['type'] ?? '')) . '</td><td>' . hs_h((string) ($f['line'] ?? '')) . '</td></tr>', $findings);
    return '<div class="hs-table-wrap"><table class="hs-table"><thead><tr><th>'
        . hs_h($t['sec_finding_file'] ?? 'File') . '</th><th>'
        . hs_h($t['sec_finding_type'] ?? 'Threat') . '</th><th>'
        . hs_h($t['sec_finding_line'] ?? 'Line') . '</th></tr></thead><tbody>'
        . implode('', $rows) . '</tbody></table></div>';
}