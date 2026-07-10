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

/** @return list<array{regex:string,type:string,severity:string}> */
function hs_sec_malware_patterns(): array
{
    return [
        ['regex' => '/eval\s*\(\s*base64_decode\s*\(/i', 'type' => 'eval_base64', 'severity' => 'critical'],
        ['regex' => '/eval\s*\(\s*gzinflate\s*\(/i', 'type' => 'eval_gzinflate', 'severity' => 'critical'],
        ['regex' => '/gzinflate\s*\(\s*base64_decode/i', 'type' => 'gzinflate_obfuscated', 'severity' => 'critical'],
        ['regex' => '/preg_replace\s*\([^)]*\/e["\']/i', 'type' => 'preg_replace_e', 'severity' => 'critical'],
        ['regex' => '/\b(system|exec|shell_exec|passthru|proc_open)\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i', 'type' => 'rce_superglobal', 'severity' => 'critical'],
        ['regex' => '/\b(include|require)(_once)?\s*\(?\s*\$_(GET|POST|REQUEST)/i', 'type' => 'dynamic_include', 'severity' => 'critical'],
        ['regex' => '/FilesMan|b374k|IndoXploit/i', 'type' => 'webshell_known', 'severity' => 'critical'],
        ['regex' => '/c99shell|r57shell|WSO\s|Fx29sh/i', 'type' => 'known_shell', 'severity' => 'critical'],
        ['regex' => '/shell_exec\s*\(/i', 'type' => 'shell_exec', 'severity' => 'high'],
        ['regex' => '/passthru\s*\(/i', 'type' => 'passthru', 'severity' => 'high'],
        ['regex' => '/proc_open\s*\(/i', 'type' => 'proc_open', 'severity' => 'high'],
        ['regex' => '/\bexec\s*\(/i', 'type' => 'exec_call', 'severity' => 'high'],
        ['regex' => '/system\s*\(\s*\$_/i', 'type' => 'system_superglobal', 'severity' => 'high'],
        ['regex' => '/assert\s*\(\s*\$_/i', 'type' => 'assert_injection', 'severity' => 'high'],
        ['regex' => '/create_function\s*\(/i', 'type' => 'create_function', 'severity' => 'high'],
        ['regex' => '/base64_decode\s*\(\s*["\'][A-Za-z0-9+\/]{80,}/i', 'type' => 'long_base64', 'severity' => 'medium'],
        ['regex' => '/php:\/\/input/i', 'type' => 'php_input_stream', 'severity' => 'medium'],
        ['regex' => '/chmod\s*\([^)]*0777/i', 'type' => 'chmod_world', 'severity' => 'medium'],
    ];
}

/** @return array<string, string> */
function hs_sec_malware_threat_labels(): array
{
    return [
        'eval_base64' => 'sec_threat_eval_base64',
        'eval_gzinflate' => 'sec_threat_eval_gzinflate',
        'gzinflate_obfuscated' => 'sec_threat_gzinflate_obfuscated',
        'preg_replace_e' => 'sec_threat_preg_replace_e',
        'rce_superglobal' => 'sec_threat_rce_superglobal',
        'dynamic_include' => 'sec_threat_dynamic_include',
        'webshell_known' => 'sec_threat_webshell_known',
        'known_shell' => 'sec_threat_known_shell',
        'shell_exec' => 'sec_threat_shell_exec',
        'passthru' => 'sec_threat_passthru',
        'proc_open' => 'sec_threat_proc_open',
        'exec_call' => 'sec_threat_exec_call',
        'system_superglobal' => 'sec_threat_system_superglobal',
        'assert_injection' => 'sec_threat_assert_injection',
        'create_function' => 'sec_threat_create_function',
        'long_base64' => 'sec_threat_long_base64',
        'php_input_stream' => 'sec_threat_php_input_stream',
        'chmod_world' => 'sec_threat_chmod_world',
    ];
}

function hs_sec_malware_severity_rank(string $severity): int
{
    return match ($severity) {
        'critical' => 0,
        'high' => 1,
        'medium' => 2,
        default => 3,
    };
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
        foreach ($patterns as $pattern) {
            $regex = (string) ($pattern['regex'] ?? '');
            if ($regex === '' || !preg_match($regex, $content, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $line = 1;
            if (isset($m[0][1])) {
                $line = substr_count(substr($content, 0, (int) $m[0][1]), "\n") + 1;
            }
            $findings[] = [
                'path' => $rel,
                'type' => (string) ($pattern['type'] ?? 'unknown'),
                'severity' => (string) ($pattern['severity'] ?? 'medium'),
                'line' => (string) $line,
            ];
            break;
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

    $labels = hs_sec_malware_threat_labels();
    $counts = ['critical' => 0, 'high' => 0, 'medium' => 0];
    foreach ($findings as $f) {
        $sev = (string) ($f['severity'] ?? 'medium');
        if (isset($counts[$sev])) {
            $counts[$sev]++;
        }
    }

    usort($findings, static function (array $a, array $b): int {
        $ra = hs_sec_malware_severity_rank((string) ($a['severity'] ?? 'medium'));
        $rb = hs_sec_malware_severity_rank((string) ($b['severity'] ?? 'medium'));
        return $ra <=> $rb;
    });

    $badge = static function (string $severity) use ($t): string {
        $key = 'sec_sev_' . $severity;
        $label = $t[$key] ?? ucfirst($severity);
        return '<span class="hs-sev-badge hs-sev-' . hs_h($severity) . '">' . hs_h($label) . '</span>';
    };

    $summary = '<div class="hs-malware-summary">'
        . '<span><strong>' . hs_h($t['sec_findings_total'] ?? 'Findings') . ':</strong> ' . count($findings) . '</span>';
    foreach ($counts as $sev => $n) {
        if ($n > 0) {
            $summary .= '<span>' . $badge($sev) . ' ' . $n . '</span>';
        }
    }
    $summary .= '</div>';

    $rows = [];
    foreach ($findings as $f) {
        $type = (string) ($f['type'] ?? '');
        $labelKey = $labels[$type] ?? '';
        $threatLabel = $labelKey !== '' && !empty($t[$labelKey]) ? (string) $t[$labelKey] : $type;
        $rows[] = '<tr><td><code>' . hs_h((string) ($f['path'] ?? '')) . '</code></td>'
            . '<td>' . $badge((string) ($f['severity'] ?? 'medium')) . '</td>'
            . '<td>' . hs_h($threatLabel) . '</td><td>' . hs_h((string) ($f['line'] ?? '')) . '</td></tr>';
    }

    return $summary
        . '<div class="hs-table-wrap"><table class="hs-table hs-malware-table"><thead><tr><th>'
        . hs_h($t['sec_finding_file'] ?? 'File') . '</th><th>'
        . hs_h($t['sec_finding_severity'] ?? 'Severity') . '</th><th>'
        . hs_h($t['sec_finding_type'] ?? 'Threat') . '</th><th>'
        . hs_h($t['sec_finding_line'] ?? 'Line') . '</th></tr></thead><tbody>'
        . implode('', $rows) . '</tbody></table></div>';
}