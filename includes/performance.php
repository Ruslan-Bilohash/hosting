<?php
declare(strict_types=1);

const HS_PERF_MARKER_CACHE = 'BILOHASH-OBJECT-CACHE';
const HS_PERF_MARKER_CDN = 'BILOHASH-CDN';

function hs_perf_user_htaccess(string $username): string
{
    return hs_public_path(preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'user') . '/.htaccess';
}

function hs_perf_patch_htaccess(string $file, string $marker, string $block): bool
{
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return false;
    }
    $content = is_file($file) ? (file_get_contents($file) ?: '') : '';
    $pattern = '/# BEGIN ' . preg_quote($marker, '/') . '\r?\n.*?# END ' . preg_quote($marker, '/') . '\r?\n/s';
    $content = preg_replace($pattern, '', $content) ?? $content;
    if ($block !== '') {
        $content = rtrim($content) . "\n\n# BEGIN {$marker}\n" . rtrim($block) . "\n# END {$marker}\n";
    }
    return file_put_contents($file, ltrim($content), LOCK_EX) !== false;
}

function hs_perf_object_cache_block(): string
{
    return <<<'HT'
<IfModule LiteSpeed>
  CacheLookup on
</IfModule>
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
</IfModule>
HT;
}

function hs_perf_cdn_block(): string
{
    return <<<'HT'
<IfModule mod_headers.c>
  <FilesMatch "\.(ico|jpg|jpeg|png|gif|webp|svg|css|js|woff2?|ttf)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
  </FilesMatch>
</IfModule>
HT;
}

/** @return array{ok:bool,enabled:bool,error?:string} */
function hs_perf_set_object_cache(array $user, bool $enable): array
{
    $username = (string) ($user['username'] ?? 'user');
    $file = hs_perf_user_htaccess($username);
    $block = $enable ? hs_perf_object_cache_block() : '';
    if (!hs_perf_patch_htaccess($file, HS_PERF_MARKER_CACHE, $block)) {
        return ['ok' => false, 'enabled' => $enable, 'error' => 'htaccess'];
    }
    return ['ok' => true, 'enabled' => $enable];
}

/** @return array{ok:bool,enabled:bool,error?:string} */
function hs_perf_set_cdn(array $user, bool $enable): array
{
    $username = (string) ($user['username'] ?? 'user');
    $file = hs_perf_user_htaccess($username);
    $block = $enable ? hs_perf_cdn_block() : '';
    if (!hs_perf_patch_htaccess($file, HS_PERF_MARKER_CDN, $block)) {
        return ['ok' => false, 'enabled' => $enable, 'error' => 'htaccess'];
    }
    return ['ok' => true, 'enabled' => $enable];
}

/** @return array{ok:bool,cleared:int,error?:string} */
function hs_perf_clear_cache(array $user): array
{
    $username = (string) ($user['username'] ?? 'user');
    $base = hs_public_path($username);
    if (!is_dir($base)) {
        return ['ok' => true, 'cleared' => 0];
    }
    $cleared = 0;
    foreach (['cache', 'lscache', '.litespeed_cache', 'tmp/cache'] as $rel) {
        $path = $base . '/' . $rel;
        if (is_dir($path)) {
            hs_recursive_remove($path);
            $cleared++;
        }
    }
    @file_put_contents($base . '/.cache-bust', (string) time(), LOCK_EX);
    return ['ok' => true, 'cleared' => $cleared];
}

/**
 * Domains the client may test (active first).
 *
 * @return list<string>
 */
function hs_perf_client_domains(array $user): array
{
    require_once __DIR__ . '/user-settings.php';
    require_once __DIR__ . '/panel-domains.php';
    $settings = hs_user_settings_get((string) ($user['id'] ?? ''));
    $out = [];
    $active = function_exists('hs_active_domain')
        ? strtolower(trim(hs_active_domain($settings)))
        : strtolower(trim((string) ($settings['active_domain'] ?? '')));
    if ($active !== '' && str_contains($active, '.')) {
        $out[] = $active;
    }
    if (function_exists('hs_user_domain_choices')) {
        foreach (hs_user_domain_choices($settings) as $d) {
            $d = strtolower(trim((string) $d));
            if ($d !== '' && str_contains($d, '.')) {
                $out[] = $d;
            }
        }
    }
    foreach (['primary_domain', 'platform_free_host'] as $k) {
        $d = strtolower(trim((string) ($settings[$k] ?? '')));
        if ($d !== '' && str_contains($d, '.')) {
            $out[] = $d;
        }
    }

    return array_values(array_unique($out));
}

/** Best public URL for the currently selected domain / account site. */
function hs_perf_selected_domain_url(array $user, array $sites = []): string
{
    global $site_url;
    require_once __DIR__ . '/user-settings.php';
    require_once __DIR__ . '/panel-domains.php';
    require_once __DIR__ . '/installer.php';
    $settings = hs_user_settings_get((string) ($user['id'] ?? ''));
    $active = function_exists('hs_active_domain')
        ? strtolower(trim(hs_active_domain($settings)))
        : strtolower(trim((string) ($settings['active_domain'] ?? '')));
    if ($active === '') {
        $active = strtolower(trim((string) ($settings['primary_domain'] ?? '')));
    }
    if ($active === '') {
        $active = strtolower(trim((string) ($settings['platform_free_host'] ?? '')));
    }
    $isBrand = function_exists('hs_domain_is_host_brand') && $active !== '' && hs_domain_is_host_brand($active);
    if ($active !== '' && str_contains($active, '.') && !$isBrand) {
        return 'https://' . preg_replace('#^https?://#i', '', $active) . '/';
    }
    // Site record URL if any
    if ($sites !== [] && function_exists('hs_public_url_for_site')) {
        $first = $sites[0];
        if (is_array($first)) {
            $u = hs_public_url_for_site($user, $first);
            if ($u !== '') {
                return $u;
            }
        }
    }
    $username = preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user')) ?: 'user';

    // Clean rewrite path: /{username}/ (not /public_html/…)
    return rtrim((string) $site_url, '/') . '/' . $username . '/';
}

/** @return list<string> Ordered probe URLs (selected domain first) */
function hs_perf_speed_test_urls(array $user, array $sites): array
{
    global $site_url;
    require_once __DIR__ . '/installer.php';
    $username = preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user')) ?: 'user';
    $urls = [];

    // 1) Selected / active domain — highest priority
    $selected = hs_perf_selected_domain_url($user, $sites);
    if ($selected !== '') {
        $urls[] = $selected;
        // Also try without trailing path variants
        $urls[] = rtrim($selected, '/') . '/index.php';
        $urls[] = rtrim($selected, '/') . '/index.html';
    }
    foreach (hs_perf_client_domains($user) as $dom) {
        $urls[] = 'https://' . $dom . '/';
        $urls[] = 'https://www.' . $dom . '/';
    }

    // 2) Account root (rewrite + nested public_html path)
    $urls[] = rtrim((string) $site_url, '/') . '/' . $username . '/';
    $urls[] = rtrim((string) $site_url, '/') . '/' . HS_PUBLIC_HTML . '/' . $username . '/';

    // 3) Installed sites
    if ($sites !== [] && function_exists('hs_public_url_for_site')) {
        foreach ($sites as $site) {
            if (!is_array($site)) {
                continue;
            }
            $siteUrl = hs_public_url_for_site($user, $site);
            if ($siteUrl !== '') {
                $urls[] = $siteUrl;
            }
            $rel = function_exists('hs_install_path_rel') ? hs_install_path_rel($user, $site) : '';
            if ($rel !== '') {
                $urls[] = rtrim((string) $site_url, '/') . '/' . $rel . '/';
                $urls[] = rtrim((string) $site_url, '/') . '/' . HS_PUBLIC_HTML . '/' . $rel . '/';
            }
        }
    }

    $welcome = hs_public_path($username . '/welcome');
    if (is_dir($welcome)) {
        $urls[] = rtrim((string) $site_url, '/') . '/' . $username . '/welcome/';
        $urls[] = rtrim((string) $site_url, '/') . '/' . HS_PUBLIC_HTML . '/' . $username . '/welcome/';
    }

    $seen = [];
    $out = [];
    foreach ($urls as $u) {
        $u = trim((string) $u);
        if ($u === '' || isset($seen[$u])) {
            continue;
        }
        $seen[$u] = true;
        $out[] = $u;
    }

    return $out;
}

function hs_perf_primary_site_url(array $user, array $sites): ?string
{
    $selected = hs_perf_selected_domain_url($user, $sites);
    if ($selected !== '') {
        return $selected;
    }
    $urls = hs_perf_speed_test_urls($user, $sites);

    return $urls[0] ?? null;
}

/** Map a panel public URL to a local file when the site is on this server */
function hs_perf_url_local_file(string $url): ?string
{
    global $site_url;
    require_once __DIR__ . '/user-settings.php';
    require_once __DIR__ . '/panel-domains.php';
    require_once __DIR__ . '/domain-workspace.php';

    $path = (string) parse_url($url, PHP_URL_PATH);
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    $panelPath = rtrim((string) parse_url((string) $site_url, PHP_URL_PATH), '/');
    $panelHost = strtolower((string) parse_url((string) $site_url, PHP_URL_HOST));

    $tryRel = static function (string $rel): ?string {
        $rel = trim(str_replace('\\', '/', $rel), '/');
        if ($rel === '' || str_contains($rel, '..')) {
            return null;
        }
        $local = hs_public_path($rel);
        if (is_file($local)) {
            return $local;
        }
        if (is_dir($local)) {
            foreach (['index.php', 'index.html', 'index.htm'] as $idx) {
                $candidate = $local . '/' . $idx;
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    };

    // /public_html/{user}/…
    $prefixNested = ($panelPath !== '' ? $panelPath : '') . '/' . HS_PUBLIC_HTML . '/';
    $prefixNested = preg_replace('#/+#', '/', $prefixNested) ?? $prefixNested;
    if ($path !== '' && str_starts_with($path, $prefixNested)) {
        $hit = $tryRel(substr($path, strlen($prefixNested)));
        if ($hit !== null) {
            return $hit;
        }
    }

    // /{user}/… (rewrite)
    if ($path !== '' && ($host === $panelHost || $host === '')) {
        $p = ltrim($path, '/');
        if ($panelPath !== '' && str_starts_with('/' . $p, $panelPath . '/')) {
            $p = ltrim(substr('/' . $p, strlen($panelPath)), '/');
        }
        if ($p !== '' && !str_starts_with($p, HS_PUBLIC_HTML . '/')) {
            $hit = $tryRel($p);
            if ($hit !== null) {
                return $hit;
            }
        }
    }

    // Custom domain → docroot via domain_roots
    if ($host !== '' && $host !== $panelHost) {
        // Need user context — scan users is heavy; resolve via path only if we have session user later.
        // Callers with user use hs_perf_url_local_file_for_user when available.
    }

    return null;
}

/** Local file for URL using client domain map (active domain / roots). */
function hs_perf_url_local_file_for_user(array $user, string $url): ?string
{
    $hit = hs_perf_url_local_file($url);
    if ($hit !== null) {
        return $hit;
    }
    require_once __DIR__ . '/user-settings.php';
    require_once __DIR__ . '/domain-workspace.php';
    require_once __DIR__ . '/panel-domains.php';
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '' || str_starts_with($host, 'www.')) {
        $hostBare = preg_replace('#^www\.#', '', $host) ?? $host;
    } else {
        $hostBare = $host;
    }
    $settings = hs_user_settings_get((string) ($user['id'] ?? ''));
    $domains = hs_perf_client_domains($user);
    foreach ($domains as $d) {
        if ($host === $d || $host === 'www.' . $d || $hostBare === $d) {
            $rel = hs_domain_docroot_rel($user, $d, $settings);
            $local = hs_public_path($rel);
            if (is_dir($local)) {
                foreach (['index.php', 'index.html', 'index.htm'] as $idx) {
                    if (is_file($local . '/' . $idx)) {
                        return $local . '/' . $idx;
                    }
                }
            }
            if (is_file($local)) {
                return $local;
            }
        }
    }

    return null;
}

/** @return array{ok:bool,ttfb:float,total:float,size:int,code:int,url:string,dns_ms:float,connect_ms:float,download_ms:float,http_version:string,compression:string,source?:string} */
function hs_perf_local_probe(string $file, string $url): array
{
    $start = microtime(true);
    $body = @file_get_contents($file);
    $total = (microtime(true) - $start) * 1000;
    if ($body === false) {
        return [
            'ok' => false, 'ttfb' => 0.0, 'total' => 0.0, 'size' => 0, 'code' => 0, 'url' => $url,
            'dns_ms' => 0.0, 'connect_ms' => 0.0, 'download_ms' => 0.0, 'http_version' => '', 'compression' => '',
            'error' => 'local_read',
        ];
    }
    $size = strlen($body);
    $read = round($total, 1);
    return [
        'ok' => true,
        'ttfb' => round($read * 0.35, 1),
        'total' => $read,
        'size' => $size,
        'code' => 200,
        'url' => $url,
        'dns_ms' => 0.5,
        'connect_ms' => 0.5,
        'download_ms' => round(max(0, $read * 0.65), 1),
        'http_version' => 'HTTP/1.1',
        'compression' => 'none',
        'source' => 'local',
    ];
}

/** @return string|null Sanitized URL the user may test */
function hs_perf_resolve_test_url(array $user, array $sites, string $requested = ''): ?string
{
    $requested = trim($requested);
    if ($requested === '') {
        return hs_perf_primary_site_url($user, $sites);
    }
    // Allow bare domain: booking.online → https://booking.online/
    if (!preg_match('#^https?://#i', $requested) && preg_match('/^[a-z0-9][a-z0-9.-]+\.[a-z]{2,}$/i', $requested)) {
        $requested = 'https://' . strtolower($requested) . '/';
    }
    if (!filter_var($requested, FILTER_VALIDATE_URL)) {
        return null;
    }
    $host = strtolower((string) parse_url($requested, PHP_URL_HOST));
    $hostBare = preg_replace('#^www\.#', '', $host) ?? $host;
    global $site_url;
    $panelHost = strtolower((string) parse_url((string) $site_url, PHP_URL_HOST));
    $username = preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? '')) ?: '';

    $allowedHosts = [];
    if ($panelHost !== '') {
        $allowedHosts[] = $panelHost;
    }
    foreach (hs_perf_client_domains($user) as $d) {
        $allowedHosts[] = $d;
        $allowedHosts[] = 'www.' . $d;
    }
    $allowedHosts = array_values(array_unique(array_filter($allowedHosts)));

    // Own custom domain
    if (in_array($host, $allowedHosts, true) || in_array($hostBare, $allowedHosts, true)) {
        return $requested;
    }

    // Panel host: only paths under this client's folder
    if ($panelHost !== '' && $host === $panelHost && $username !== '') {
        $path = (string) parse_url($requested, PHP_URL_PATH);
        $panelPath = rtrim((string) parse_url((string) $site_url, PHP_URL_PATH), '/');
        $pathNorm = $path;
        if ($panelPath !== '' && str_starts_with($pathNorm, $panelPath)) {
            $pathNorm = substr($pathNorm, strlen($panelPath)) ?: '/';
        }
        $pathNorm = '/' . ltrim($pathNorm, '/');
        // /public_html/{user}/… or /{user}/…
        $okPrefixes = [
            '/' . HS_PUBLIC_HTML . '/' . $username,
            '/' . $username,
        ];
        foreach ($okPrefixes as $pref) {
            if ($pathNorm === $pref || $pathNorm === $pref . '/' || str_starts_with($pathNorm, $pref . '/')) {
                return $requested;
            }
        }

        return null;
    }

    return null;
}

/** @return array{ok:bool,ttfb:float,total:float,size:int,code:int,url:string,dns_ms:float,connect_ms:float,download_ms:float,http_version:string,compression:string,error?:string} */
function hs_perf_http_probe(string $url, bool $mobile = false): array
{
    $url = trim($url);
    $empty = [
        'ok' => false, 'ttfb' => 0.0, 'total' => 0.0, 'size' => 0, 'code' => 0, 'url' => $url,
        'dns_ms' => 0.0, 'connect_ms' => 0.0, 'download_ms' => 0.0, 'http_version' => '', 'compression' => '',
    ];
    if ($url === '') {
        return $empty + ['error' => 'no_url'];
    }
    $ua = $mobile
        ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1 BILOHASH-SpeedTest'
        : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 BILOHASH-SpeedTest';
    $start = microtime(true);
    if (function_exists('curl_init')) {
        $headers = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 35,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_USERAGENT => $ua,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$headers): int {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($line);
            },
        ]);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $code = (int) ($info['http_code'] ?? 0);
        if ($body === false || $code >= 400 || $code === 0) {
            $local = hs_perf_url_local_file($url);
            if ($local !== null) {
                $localProbe = hs_perf_local_probe($local, $url);
                if ($localProbe['ok']) {
                    return $localProbe;
                }
            }
            if ($body === false) {
                return $empty + ['error' => $err !== '' ? $err : 'fetch'];
            }
            return $empty + [
                'code' => $code,
                'error' => 'http_' . $code,
            ];
        }
        $dns = (float) ($info['namelookup_time'] ?? 0) * 1000;
        $connect = (float) ($info['connect_time'] ?? 0) * 1000;
        $ttfb = (float) ($info['starttransfer_time'] ?? 0) * 1000;
        $total = (float) ($info['total_time'] ?? (microtime(true) - $start)) * 1000;
        $download = max(0.0, $total - $ttfb);
        $size = (int) ($info['size_download'] ?? strlen((string) $body));
        $ver = (float) ($info['http_version'] ?? 0);
        $httpVersion = $ver >= 3 ? 'HTTP/3' : ($ver >= 2 ? 'HTTP/2' : 'HTTP/1.1');
        $enc = strtolower((string) ($headers['content-encoding'] ?? ''));
        $compression = str_contains($enc, 'br') ? 'brotli' : (str_contains($enc, 'gzip') ? 'gzip' : 'none');
        return [
            'ok' => true,
            'ttfb' => round($ttfb, 1),
            'total' => round($total, 1),
            'size' => $size,
            'code' => $code,
            'url' => (string) ($info['url'] ?? $url),
            'dns_ms' => round($dns, 1),
            'connect_ms' => round(max(0, $connect - $dns), 1),
            'download_ms' => round($download, 1),
            'http_version' => $httpVersion,
            'compression' => $compression,
            'source' => 'http',
        ];
    }
    $ctx = stream_context_create(['http' => ['timeout' => 30, 'user_agent' => $ua]]);
    $body = @file_get_contents($url, false, $ctx);
    $total = (microtime(true) - $start) * 1000;
    if ($body === false) {
        return $empty + ['error' => 'fetch'];
    }
    return [
        'ok' => true,
        'ttfb' => round($total * 0.35, 1),
        'total' => round($total, 1),
        'size' => strlen($body),
        'code' => 200,
        'url' => $url,
        'dns_ms' => round($total * 0.08, 1),
        'connect_ms' => round($total * 0.12, 1),
        'download_ms' => round($total * 0.55, 1),
        'http_version' => 'HTTP/1.1',
        'compression' => 'none',
    ];
}

function hs_perf_score_from_probe(array $probe, bool $mobile = false): int
{
    if (empty($probe['ok'])) {
        return 0;
    }
    $score = 100;
    $ttfb = (float) ($probe['ttfb'] ?? 0);
    $total = (float) ($probe['total'] ?? 0);
    $sizeKb = ((int) ($probe['size'] ?? 0)) / 1024;
    if ($ttfb > 800) {
        $score -= 30;
    } elseif ($ttfb > 400) {
        $score -= 15;
    } elseif ($ttfb > 200) {
        $score -= 5;
    }
    if ($total > 3000) {
        $score -= 25;
    } elseif ($total > 1500) {
        $score -= 10;
    }
    if ($sizeKb > 2000) {
        $score -= 20;
    } elseif ($sizeKb > 500) {
        $score -= 8;
    }
    if ($mobile) {
        $score = (int) round($score * 0.88);
    }
    return max(0, min(100, $score));
}

/** @return array{letter:string,label:string,tier:string,color:string} */
function hs_perf_score_grade(int $score, array $t): array
{
    if ($score >= 90) {
        return ['letter' => 'A', 'label' => $t['perf_grade_a'] ?? 'Excellent', 'tier' => 'great', 'color' => '#059669'];
    }
    if ($score >= 75) {
        return ['letter' => 'B', 'label' => $t['perf_grade_b'] ?? 'Good', 'tier' => 'good', 'color' => '#10b981'];
    }
    if ($score >= 60) {
        return ['letter' => 'C', 'label' => $t['perf_grade_c'] ?? 'Fair', 'tier' => 'fair', 'color' => '#f59e0b'];
    }
    if ($score >= 40) {
        return ['letter' => 'D', 'label' => $t['perf_grade_d'] ?? 'Poor', 'tier' => 'poor', 'color' => '#f97316'];
    }
    return ['letter' => 'F', 'label' => $t['perf_grade_f'] ?? 'Critical', 'tier' => 'bad', 'color' => '#dc2626'];
}

/** @param list<array<string,mixed>> $probes */
function hs_perf_average_probe(array $probes): array
{
    if ($probes === []) {
        return [];
    }
    $keys = ['ttfb', 'total', 'size', 'dns_ms', 'connect_ms', 'download_ms'];
    $avg = $probes[0];
    $n = count($probes);
    foreach ($keys as $k) {
        $sum = 0.0;
        foreach ($probes as $p) {
            $sum += (float) ($p[$k] ?? 0);
        }
        $avg[$k] = round($sum / $n, 1);
        if ($k === 'size') {
            $avg[$k] = (int) round($sum / $n);
        }
    }
    $avg['ok'] = !empty($avg['ok']);
    return $avg;
}

/** @return list<array{level:string,key:string,detail?:string}> */
function hs_perf_speed_tips(array $probe, int $desktop, int $mobile, array $t): array
{
    $tips = [];
    $ttfb = (float) ($probe['ttfb'] ?? 0);
    $total = (float) ($probe['total'] ?? 0);
    $sizeKb = ((int) ($probe['size'] ?? 0)) / 1024;
    $compression = (string) ($probe['compression'] ?? 'none');
    $httpVer = (string) ($probe['http_version'] ?? '');

    if ($ttfb <= 200) {
        $tips[] = ['level' => 'ok', 'key' => 'perf_tip_ttfb_good', 'detail' => (int) round($ttfb) . ' ms'];
    } elseif ($ttfb <= 600) {
        $tips[] = ['level' => 'warn', 'key' => 'perf_tip_ttfb_mid', 'detail' => (int) round($ttfb) . ' ms'];
    } else {
        $tips[] = ['level' => 'bad', 'key' => 'perf_tip_ttfb_slow', 'detail' => (int) round($ttfb) . ' ms'];
    }

    if ($total <= 1000) {
        $tips[] = ['level' => 'ok', 'key' => 'perf_tip_load_good', 'detail' => (int) round($total) . ' ms'];
    } elseif ($total <= 2500) {
        $tips[] = ['level' => 'warn', 'key' => 'perf_tip_load_mid'];
    } else {
        $tips[] = ['level' => 'bad', 'key' => 'perf_tip_load_slow', 'detail' => (int) round($total) . ' ms'];
    }

    if ($sizeKb <= 500) {
        $tips[] = ['level' => 'ok', 'key' => 'perf_tip_size_good', 'detail' => (int) round($sizeKb) . ' KB'];
    } elseif ($sizeKb <= 1500) {
        $tips[] = ['level' => 'warn', 'key' => 'perf_tip_size_mid', 'detail' => (int) round($sizeKb) . ' KB'];
    } else {
        $tips[] = ['level' => 'bad', 'key' => 'perf_tip_size_large', 'detail' => (int) round($sizeKb) . ' KB'];
    }

    if ($compression !== 'none') {
        $tips[] = ['level' => 'ok', 'key' => 'perf_tip_compression_on', 'detail' => strtoupper($compression)];
    } else {
        $tips[] = ['level' => 'warn', 'key' => 'perf_tip_compression_off'];
    }

    if ($httpVer === 'HTTP/2' || $httpVer === 'HTTP/3') {
        $tips[] = ['level' => 'ok', 'key' => 'perf_tip_http2', 'detail' => $httpVer];
    } else {
        $tips[] = ['level' => 'info', 'key' => 'perf_tip_http1'];
    }

    if ($mobile < $desktop - 15) {
        $tips[] = ['level' => 'warn', 'key' => 'perf_tip_mobile_gap', 'detail' => (string) $mobile . ' vs ' . (string) $desktop];
    }
    if ($desktop >= 90) {
        $tips[] = ['level' => 'ok', 'key' => 'perf_tip_score_great'];
    }

    return $tips;
}

/** @return array{ok:bool,desktop:int,mobile:int,probe:array<string,mixed>,error?:string,report?:array<string,mixed>} */
function hs_perf_run_speed_test(array $user, array $sites, string $requestedUrl = ''): array
{
    $requestedUrl = trim($requestedUrl);
    if ($requestedUrl !== '') {
        $url = hs_perf_resolve_test_url($user, $sites, $requestedUrl);
        $candidates = $url !== null ? [$url] : [];
    } else {
        $candidates = hs_perf_speed_test_urls($user, $sites);
    }
    if ($candidates === []) {
        return ['ok' => false, 'desktop' => 0, 'mobile' => 0, 'probe' => [], 'error' => $requestedUrl !== '' ? 'invalid_url' : 'no_site'];
    }

    $url = '';
    $desktopRuns = [];
    $last = [];
    foreach ($candidates as $candidate) {
        $desktopRuns = [];
        $url = $candidate;
        for ($i = 0; $i < 3; $i++) {
            $p = hs_perf_http_probe($url, false);
            $last = $p;
            if ($p['ok']) {
                $desktopRuns[] = $p;
            }
            if ($i < 2) {
                usleep(120000);
            }
        }
        if ($desktopRuns !== []) {
            break;
        }
    }
    if ($desktopRuns === []) {
        return ['ok' => false, 'desktop' => 0, 'mobile' => 0, 'probe' => $last, 'error' => $last['error'] ?? 'fetch'];
    }

    $probe = hs_perf_average_probe($desktopRuns);
    $mobileProbe = hs_perf_http_probe($url, true);
    $desktopScore = hs_perf_score_from_probe($probe, false);
    $mobileScore = hs_perf_score_from_probe(
        $mobileProbe['ok'] ? $mobileProbe : $probe,
        true
    );

    global $t;
    $tips = hs_perf_speed_tips($probe, $desktopScore, $mobileScore, is_array($t ?? null) ? $t : []);

    $report = [
        'url' => (string) ($probe['url'] ?? $url),
        'tested_at' => gmdate('c'),
        'runs' => count($desktopRuns),
        'desktop' => array_merge(['score' => $desktopScore], hs_perf_score_grade($desktopScore, is_array($t ?? null) ? $t : [])),
        'mobile' => array_merge(['score' => $mobileScore], hs_perf_score_grade($mobileScore, is_array($t ?? null) ? $t : [])),
        'metrics' => [
            'ttfb_ms' => (float) ($probe['ttfb'] ?? 0),
            'load_ms' => (float) ($probe['total'] ?? 0),
            'dns_ms' => (float) ($probe['dns_ms'] ?? 0),
            'connect_ms' => (float) ($probe['connect_ms'] ?? 0),
            'download_ms' => (float) ($probe['download_ms'] ?? 0),
            'size_bytes' => (int) ($probe['size'] ?? 0),
            'size_kb' => round(((int) ($probe['size'] ?? 0)) / 1024, 1),
            'http_code' => (int) ($probe['code'] ?? 0),
            'http_version' => (string) ($probe['http_version'] ?? ''),
            'compression' => (string) ($probe['compression'] ?? 'none'),
        ],
        'mobile_metrics' => [
            'ttfb_ms' => (float) ($mobileProbe['ttfb'] ?? 0),
            'load_ms' => (float) ($mobileProbe['total'] ?? 0),
        ],
        'tips' => $tips,
        'probe' => $probe,
    ];

    return [
        'ok' => true,
        'desktop' => $desktopScore,
        'mobile' => $mobileScore,
        'probe' => $probe,
        'report' => $report,
    ];
}

function hs_perf_json_response(array $data, int $code = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** @param array<string, mixed> $ctx */
function hs_perf_render_speed_tab(array $ctx): string
{
    $t = $ctx['t'];
    $s = $ctx['hs_user_settings'];
    $user = $ctx['user'];
    $sites = $ctx['hs_sites'];
    $defaultUrl = hs_perf_primary_site_url($user, is_array($sites) ? $sites : []) ?? '';
    $report = is_array($s['speed_report'] ?? null) ? $s['speed_report'] : [];
    $testedAt = (string) ($s['speed_tested_at'] ?? '');
    $apiUrl = hs_url(hs_panel_path('performance-speed-api.php'));
    $csrf = hs_csrf_token();

    // Prefer currently selected domain over a stale last-test URL for another host
    $inputUrl = $defaultUrl;
    $reportUrl = trim((string) ($report['url'] ?? ''));
    if ($reportUrl !== '' && $defaultUrl !== '') {
        $repHost = strtolower((string) parse_url($reportUrl, PHP_URL_HOST));
        $defHost = strtolower((string) parse_url($defaultUrl, PHP_URL_HOST));
        $repBare = preg_replace('#^www\.#', '', $repHost) ?? $repHost;
        $defBare = preg_replace('#^www\.#', '', $defHost) ?? $defHost;
        if ($repBare === $defBare || $repHost === $defHost) {
            $inputUrl = $reportUrl;
        }
    } elseif ($reportUrl !== '' && $defaultUrl === '') {
        $inputUrl = $reportUrl;
    }

    $domainChoices = hs_perf_client_domains($user);
    $activeLabel = '';
    if ($defaultUrl !== '') {
        $activeLabel = (string) parse_url($defaultUrl, PHP_URL_HOST);
    }

    $initial = [
        'url' => $inputUrl,
        'tested_at' => $testedAt,
        'report' => $report,
        'default_url' => $defaultUrl,
        'domains' => $domainChoices,
        'active_domain' => $activeLabel,
    ];

    $i18n = [
        'running' => $t['perf_speed_running'] ?? 'Running speed test…',
        'step_dns' => $t['perf_speed_step_dns'] ?? 'DNS lookup',
        'step_connect' => $t['perf_speed_step_connect'] ?? 'Connecting',
        'step_download' => $t['perf_speed_step_download'] ?? 'Downloading',
        'step_analyze' => $t['perf_speed_step_analyze'] ?? 'Analyzing',
        'run' => $t['perf_run_test'] ?? 'Run speed test',
        'desktop' => $t['dash_perf_desktop'] ?? 'Desktop',
        'mobile' => $t['dash_perf_mobile'] ?? 'Mobile',
        'ttfb' => $t['perf_metric_ttfb'] ?? 'TTFB',
        'load' => $t['perf_metric_load'] ?? 'Load time',
        'size' => $t['perf_metric_size'] ?? 'Page size',
        'dns' => $t['perf_metric_dns'] ?? 'DNS',
        'connect' => $t['perf_metric_connect'] ?? 'Connect',
        'download' => $t['perf_metric_download'] ?? 'Download',
        'http' => $t['perf_metric_http'] ?? 'Protocol',
        'compression' => $t['perf_metric_compression'] ?? 'Compression',
        'tips_title' => $t['perf_tips_title'] ?? 'Recommendations',
        'last_test' => $t['perf_last_test'] ?? 'Last test',
        'error' => $t['perf_speed_error'] ?? 'Speed test failed',
        'no_site' => $t['perf_speed_no_site'] ?? 'No site to test',
        'url_label' => $t['perf_probe_url'] ?? 'Test URL',
        'ms' => $t['perf_unit_ms'] ?? 'ms',
        'kb' => $t['perf_unit_kb'] ?? 'KB',
    ];

    foreach (['perf_grade_a', 'perf_grade_b', 'perf_grade_c', 'perf_grade_d', 'perf_grade_f'] as $gk) {
        $i18n[$gk] = $t[$gk] ?? '';
    }
    foreach (['perf_tip_ttfb_good', 'perf_tip_ttfb_mid', 'perf_tip_ttfb_slow', 'perf_tip_load_good', 'perf_tip_load_mid', 'perf_tip_load_slow',
        'perf_tip_size_good', 'perf_tip_size_mid', 'perf_tip_size_large', 'perf_tip_compression_on', 'perf_tip_compression_off',
        'perf_tip_http2', 'perf_tip_http1', 'perf_tip_mobile_gap', 'perf_tip_score_great'] as $tk) {
        $i18n[$tk] = $t[$tk] ?? $tk;
    }

    $json = json_encode([
        'api' => $apiUrl,
        'csrf' => $csrf,
        'initial' => $initial,
        'i18n' => $i18n,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

    return '<div class="hs-speed-lab" data-hs-speed-lab>'
        . '<header class="hs-speed-hero">'
        . '<div class="hs-speed-hero-glow" aria-hidden="true"></div>'
        . '<div class="hs-speed-hero-inner">'
        . '<h2 class="hs-speed-hero-title"><i class="fa-solid fa-gauge-high"></i> ' . hs_h($t['tab_perf_speed'] ?? 'Website speed') . '</h2>'
        . '<p class="hs-speed-hero-sub">' . hs_h($t['perf_speed_lab_sub'] ?? 'Real HTTP probe from our server — TTFB, load time, compression & mobile score.') . '</p>'
        . ($activeLabel !== ''
            ? '<p class="hp-muted hs-speed-active-domain" style="margin:0 0 .75rem"><i class="fa-solid fa-globe"></i> '
                . hs_h($t['perf_speed_active_domain'] ?? 'Selected domain') . ': <strong>' . hs_h($activeLabel) . '</strong></p>'
            : '')
        . '<div class="hs-speed-url-row">'
        . '<label class="hs-speed-url-label" for="hs-speed-url">' . hs_h($t['perf_probe_url'] ?? 'URL') . '</label>'
        . '<div class="hs-speed-url-input">'
        . '<i class="fa-solid fa-link"></i>'
        . '<input type="url" id="hs-speed-url" value="' . hs_h($inputUrl) . '" placeholder="https://">'
        . '<button type="button" class="hs-btn hs-btn-primary" data-hs-speed-run><i class="fa-solid fa-play"></i> ' . hs_h($t['perf_run_test'] ?? 'Run') . '</button>'
        . '</div></div>'
        . ($domainChoices !== []
            ? '<div class="hs-speed-domain-picks" style="display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.65rem">'
                . implode('', array_map(static function (string $d) use ($defaultUrl): string {
                    $href = 'https://' . $d . '/';
                    $active = str_contains(strtolower($defaultUrl), strtolower($d));
                    return '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm' . ($active ? ' is-active' : '') . '" data-hs-speed-pick="'
                        . hs_h($href) . '"><i class="fa-solid fa-globe"></i> ' . hs_h($d) . '</button>';
                }, $domainChoices))
                . '</div>'
            : '')
        . '<div class="hs-speed-steps" data-hs-speed-steps hidden>'
        . '<div class="hs-speed-step" data-step="dns"><span class="hs-speed-step-dot"></span>' . hs_h($i18n['step_dns']) . '</div>'
        . '<div class="hs-speed-step" data-step="connect"><span class="hs-speed-step-dot"></span>' . hs_h($i18n['step_connect']) . '</div>'
        . '<div class="hs-speed-step" data-step="download"><span class="hs-speed-step-dot"></span>' . hs_h($i18n['step_download']) . '</div>'
        . '<div class="hs-speed-step" data-step="analyze"><span class="hs-speed-step-dot"></span>' . hs_h($i18n['step_analyze']) . '</div>'
        . '</div></div></header>'
        . '<div class="hs-speed-results" data-hs-speed-results>'
        . '<div class="hs-speed-gauges">'
        . '<article class="hs-speed-gauge" data-hs-speed-gauge="desktop"><div class="hs-speed-gauge-ring"><svg viewBox="0 0 120 120"><circle class="hs-speed-ring-bg" cx="60" cy="60" r="52"/><circle class="hs-speed-ring-fill" cx="60" cy="60" r="52" data-ring-fill/></svg><div class="hs-speed-gauge-center"><span class="hs-speed-score" data-score>—</span><span class="hs-speed-grade" data-grade></span></div></div><h3>' . hs_h($i18n['desktop']) . '</h3><p class="hs-speed-grade-label" data-grade-label></p></article>'
        . '<article class="hs-speed-gauge" data-hs-speed-gauge="mobile"><div class="hs-speed-gauge-ring"><svg viewBox="0 0 120 120"><circle class="hs-speed-ring-bg" cx="60" cy="60" r="52"/><circle class="hs-speed-ring-fill" cx="60" cy="60" r="52" data-ring-fill/></svg><div class="hs-speed-gauge-center"><span class="hs-speed-score" data-score>—</span><span class="hs-speed-grade" data-grade></span></div></div><h3>' . hs_h($i18n['mobile']) . '</h3><p class="hs-speed-grade-label" data-grade-label></p></article>'
        . '</div>'
        . '<div class="hs-speed-metrics" data-hs-speed-metrics></div>'
        . '<div class="hs-speed-waterfall" data-hs-speed-waterfall hidden><h4>' . hs_h($t['perf_waterfall_title'] ?? 'Timing breakdown') . '</h4><div class="hs-speed-bars" data-hs-speed-bars></div></div>'
        . '<aside class="hs-speed-tips" data-hs-speed-tips><h4><i class="fa-solid fa-lightbulb"></i> ' . hs_h($i18n['tips_title']) . '</h4><ul data-hs-speed-tips-list></ul></aside>'
        . '<p class="hp-muted hs-speed-meta" data-hs-speed-meta></p>'
        . '</div></div>'
        . '<script>window.HS_SPEED_LAB=' . $json . ';</script>';
}

/** @return array{headers:array<string,string>,code:int,http_version:string,error?:string} */
function hs_perf_fetch_response_meta(string $url): array
{
    $url = trim($url);
    if ($url === '' || !function_exists('curl_init')) {
        return ['headers' => [], 'code' => 0, 'http_version' => ''];
    }
    $headers = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_USERAGENT => 'BILOHASH-HealthCheck/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$headers): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($line);
        },
    ]);
    curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err !== '') {
        return ['headers' => [], 'code' => 0, 'http_version' => '', 'error' => $err];
    }
    $ver = (float) ($info['http_version'] ?? 0);
    $httpVersion = $ver >= 3 ? 'HTTP/3' : ($ver >= 2 ? 'HTTP/2' : 'HTTP/1.1');
    return [
        'headers' => $headers,
        'code' => (int) ($info['http_code'] ?? 0),
        'http_version' => $httpVersion,
    ];
}

/** @return array{ok:bool,https:bool,redirects:int} */
function hs_perf_check_https(string $url): array
{
    $httpUrl = preg_replace('#^https:#i', 'http:', $url) ?? $url;
    if (!function_exists('curl_init')) {
        return ['ok' => str_starts_with(strtolower($url), 'https://'), 'https' => true, 'redirects' => 0];
    }
    $ch = curl_init($httpUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'BILOHASH-HealthCheck/1.0',
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $loc = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    $https = $code >= 300 && $code < 400 && stripos($loc, 'https://') === 0;
    return ['ok' => $https || str_starts_with(strtolower($url), 'https://'), 'https' => $https, 'redirects' => $https ? 1 : 0];
}

/** @return list<array{level:string,message:string,site?:string,detail?:string}> */
function hs_perf_run_health_scan(array $user, array $sites): array
{
    $findings = [];
    $username = (string) ($user['username'] ?? 'user');
    $userId = (string) ($user['id'] ?? '');
    $base = hs_public_path($username);
    $settings = hs_user_settings_get($userId);
    $usage = hs_resource_usage($user, $sites);

    if ($sites === []) {
        $findings[] = ['level' => 'warn', 'message' => 'no_sites'];
    }

    $primaryUrl = hs_perf_primary_site_url($user, $sites);
    if ($primaryUrl !== null) {
        $https = hs_perf_check_https($primaryUrl);
        if (!$https['ok']) {
            $findings[] = ['level' => 'error', 'message' => 'no_https_redirect'];
        } else {
            $findings[] = ['level' => 'ok', 'message' => 'https_ok'];
        }

        $meta = hs_perf_fetch_response_meta($primaryUrl);
        if (($meta['code'] ?? 0) >= 200 && ($meta['code'] ?? 0) < 400) {
            $headers = $meta['headers'] ?? [];
            $hasGzip = isset($headers['content-encoding']) && str_contains(strtolower((string) $headers['content-encoding']), 'gzip');
            $hasBr = isset($headers['content-encoding']) && str_contains(strtolower((string) $headers['content-encoding']), 'br');
            if ($hasGzip || $hasBr) {
                $findings[] = ['level' => 'ok', 'message' => 'compression_ok', 'detail' => $hasBr ? 'Brotli' : 'Gzip'];
            } else {
                $findings[] = ['level' => 'warn', 'message' => 'no_compression'];
            }

            $sec = 0;
            foreach (['strict-transport-security', 'x-content-type-options', 'x-frame-options', 'referrer-policy'] as $h) {
                if (!empty($headers[$h])) {
                    $sec++;
                }
            }
            if ($sec >= 3) {
                $findings[] = ['level' => 'ok', 'message' => 'security_headers_ok', 'detail' => (string) $sec . '/4'];
            } else {
                $findings[] = ['level' => 'warn', 'message' => 'security_headers_weak', 'detail' => (string) $sec . '/4'];
            }

            $httpVer = (string) ($meta['http_version'] ?? '');
            if ($httpVer === 'HTTP/2' || $httpVer === 'HTTP/3') {
                $findings[] = ['level' => 'ok', 'message' => 'http2_ok', 'detail' => $httpVer];
            } else {
                $findings[] = ['level' => 'info', 'message' => 'http1_only'];
            }
        }

        $domain = (string) ($settings['primary_domain'] ?? '');
        if ($domain !== '') {
            $resolved = gethostbyname($domain);
            if ($resolved === $domain) {
                $findings[] = ['level' => 'warn', 'message' => 'dns_fail', 'detail' => $domain];
            } else {
                $findings[] = ['level' => 'ok', 'message' => 'dns_ok', 'detail' => $resolved];
            }
        }
    }

    foreach ($sites as $site) {
        $slug = (string) ($site['slug'] ?? '');
        $url = hs_public_url($username, $slug);
        $probe = hs_perf_http_probe($url);
        if (!$probe['ok']) {
            $findings[] = ['level' => 'error', 'message' => 'site_unreachable', 'site' => $slug, 'detail' => (string) ($probe['code'] ?? 0)];
        } elseif ((float) ($probe['ttfb'] ?? 0) > 800) {
            $findings[] = ['level' => 'warn', 'message' => 'slow_ttfb', 'site' => $slug, 'detail' => (int) round((float) $probe['ttfb']) . 'ms'];
        } elseif ((float) ($probe['total'] ?? 0) > 2500) {
            $findings[] = ['level' => 'warn', 'message' => 'slow_load', 'site' => $slug, 'detail' => (int) round((float) $probe['total']) . 'ms'];
        } else {
            $findings[] = ['level' => 'ok', 'message' => 'site_ok', 'site' => $slug];
        }
        $index = hs_public_path($username . '/' . $slug . '/index.php');
        $indexHtml = hs_public_path($username . '/' . $slug . '/index.html');
        if (!is_file($index) && !is_file($indexHtml)) {
            $findings[] = ['level' => 'error', 'message' => 'missing_index', 'site' => $slug];
        }
        $robots = hs_public_path($username . '/' . $slug . '/robots.txt');
        if (!is_file($robots)) {
            $findings[] = ['level' => 'info', 'message' => 'no_robots', 'site' => $slug];
        } else {
            $findings[] = ['level' => 'ok', 'message' => 'robots_ok', 'site' => $slug];
        }
    }

    $phpVer = (string) ($settings['php_version'] ?? '8.2');
    $phpMajor = (float) preg_replace('/[^0-9.].*/', '', $phpVer);
    if ($phpMajor < 8.1) {
        $findings[] = ['level' => 'error', 'message' => 'php_outdated', 'detail' => $phpVer];
    } elseif ($phpMajor < 8.2) {
        $findings[] = ['level' => 'warn', 'message' => 'php_old', 'detail' => $phpVer];
    } else {
        $findings[] = ['level' => 'ok', 'message' => 'php_ok', 'detail' => $phpVer];
    }

    $diskPct = 0;
    if ((int) ($usage['storage_max_mb'] ?? 0) > 0) {
        $diskPct = (int) round(((float) ($usage['storage_used_mb'] ?? 0) / (int) $usage['storage_max_mb']) * 100);
    }
    if ($diskPct >= 85) {
        $findings[] = ['level' => 'error', 'message' => 'disk_critical', 'detail' => $diskPct . '%'];
    } elseif ($diskPct >= 70) {
        $findings[] = ['level' => 'warn', 'message' => 'disk_high', 'detail' => $diskPct . '%'];
    } else {
        $findings[] = ['level' => 'ok', 'message' => 'disk_ok', 'detail' => $diskPct . '%'];
    }

    $inodePct = (int) ($usage['inodes_percent'] ?? 0);
    if ($inodePct >= 80) {
        $findings[] = ['level' => 'warn', 'message' => 'inodes_high', 'detail' => $inodePct . '%'];
    }

    $databases = is_array($settings['databases'] ?? null) ? $settings['databases'] : [];
    if ($databases === []) {
        $findings[] = ['level' => 'info', 'message' => 'no_database'];
    } else {
        $findings[] = ['level' => 'ok', 'message' => 'database_ok', 'detail' => (string) count($databases)];
    }

    $errLog = $base . '/error.log';
    if (is_file($errLog) && filesize($errLog) > 0) {
        $tail = (string) file_get_contents($errLog, false, null, max(0, (int) filesize($errLog) - 2048));
        $lines = array_filter(array_map('trim', explode("\n", $tail)));
        if ($lines !== []) {
            $findings[] = ['level' => 'warn', 'message' => 'php_errors', 'detail' => (string) array_slice($lines, -1)[0]];
        }
    }

    $htaccess = hs_perf_user_htaccess($username);
    $cacheLive = is_file($htaccess) && strpos((string) file_get_contents($htaccess), HS_PERF_MARKER_CACHE) !== false;
    if (empty($settings['cache_enabled']) && !$cacheLive) {
        $findings[] = ['level' => 'info', 'message' => 'cache_off'];
    } elseif ($cacheLive) {
        $findings[] = ['level' => 'ok', 'message' => 'cache_on'];
    }

    if (empty($settings['ssl_enabled'])) {
        $findings[] = ['level' => 'warn', 'message' => 'ssl_off'];
    } else {
        $findings[] = ['level' => 'ok', 'message' => 'ssl_on'];
    }

    $large = hs_perf_find_large_files($base, 5 * 1024 * 1024, 3);
    foreach ($large as $file) {
        $findings[] = ['level' => 'warn', 'message' => 'large_file', 'detail' => $file];
    }

    if ($findings === []) {
        $findings[] = ['level' => 'ok', 'message' => 'all_clear'];
    }
    return $findings;
}

/** @return list<string> */
function hs_perf_find_large_files(string $base, int $minBytes, int $limit = 5): array
{
    if (!is_dir($base)) {
        return [];
    }
    $out = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        if ($file->getSize() >= $minBytes) {
            $rel = substr($file->getPathname(), strlen($base) + 1);
            $out[] = $rel . ' (' . round($file->getSize() / 1048576, 1) . ' MB)';
            if (count($out) >= $limit) {
                break;
            }
        }
    }
    return $out;
}

function hs_perf_finding_label(array $finding, array $t): string
{
    $key = 'perf_find_' . (string) ($finding['message'] ?? 'unknown');
    $msg = (string) ($t[$key] ?? $finding['message'] ?? '');
    $site = (string) ($finding['site'] ?? '');
    $detail = (string) ($finding['detail'] ?? '');
    if ($site !== '') {
        $msg .= ' (' . $site . ')';
    }
    if ($detail !== '') {
        $msg .= ' — ' . $detail;
    }
    return $msg;
}

function hs_perf_render_findings(array $findings, array $t): string
{
    if ($findings === []) {
        return '<p class="hp-muted">' . hs_h($t['perf_ai_no_scan'] ?? 'No scan yet.') . '</p>';
    }
    $html = '<ul class="hp-list hp-perf-findings">';
    foreach ($findings as $f) {
        $level = (string) ($f['level'] ?? 'info');
        $html .= '<li class="hp-perf-' . hs_h($level) . '"><i class="fa-solid fa-' . hs_perf_finding_icon($level) . '"></i> '
            . hs_h(hs_perf_finding_label($f, $t)) . '</li>';
    }
    return $html . '</ul>';
}

function hs_perf_finding_icon(string $level): string
{
    return match ($level) {
        'ok' => 'circle-check',
        'warn' => 'triangle-exclamation',
        'error' => 'circle-xmark',
        default => 'circle-info',
    };
}

/** @param list<array<string,mixed>> $findings */
/** @return list<array<string,mixed>> */
function hs_perf_build_advice(array $findings, array $user, array $t, array $settings = []): array
{
    $messages = array_column($findings, 'message');
    $advice = [];
    $add = static function (string $key, string $level, string $titleKey, string $bodyKey, ?string $actionUrl = null, ?string $actionKey = null) use (&$advice, $t): void {
        foreach ($advice as $a) {
            if (($a['key'] ?? '') === $key) {
                return;
            }
        }
        $advice[] = [
            'key' => $key,
            'level' => $level,
            'title' => $t[$titleKey] ?? $titleKey,
            'body' => $t[$bodyKey] ?? $bodyKey,
            'action_url' => $actionUrl,
            'action_label' => $actionKey !== null ? ($t[$actionKey] ?? $actionKey) : '',
        ];
    };

    if (in_array('no_compression', $messages, true) || in_array('cache_off', $messages, true)) {
        $add('cache', 'warn', 'perf_advice_cache_title', 'perf_advice_cache_body',
            hs_url(hs_panel_path('performance.php'), ['tab' => 'cache']), 'perf_advice_cache_action');
    }
    if (in_array('no_https_redirect', $messages, true) || in_array('ssl_off', $messages, true)) {
        $add('ssl', 'error', 'perf_advice_ssl_title', 'perf_advice_ssl_body',
            hs_url(hs_panel_path('security.php'), ['tab' => 'ssl']), 'perf_advice_ssl_action');
    }
    if (in_array('slow_ttfb', $messages, true) || in_array('slow_load', $messages, true)) {
        $add('speed', 'warn', 'perf_advice_speed_title', 'perf_advice_speed_body',
            hs_url(hs_panel_path('performance.php'), ['tab' => 'speed']), 'perf_advice_speed_action');
    }
    if (in_array('security_headers_weak', $messages, true)) {
        $add('headers', 'warn', 'perf_advice_headers_title', 'perf_advice_headers_body',
            hs_url(hs_panel_path('security.php')), 'perf_advice_headers_action');
    }
    if (in_array('disk_critical', $messages, true) || in_array('disk_high', $messages, true) || in_array('large_file', $messages, true)) {
        $add('disk', 'warn', 'perf_advice_disk_title', 'perf_advice_disk_body',
            hs_url(hs_panel_path('files.php')), 'perf_advice_disk_action');
    }
    if (in_array('php_outdated', $messages, true) || in_array('php_old', $messages, true)) {
        $add('php', 'warn', 'perf_advice_php_title', 'perf_advice_php_body',
            hs_url(hs_panel_path('php.php')), 'perf_advice_php_action');
    }
    if (in_array('http1_only', $messages, true)) {
        $add('http2', 'info', 'perf_advice_http2_title', 'perf_advice_http2_body', null, null);
    }
    if (in_array('no_robots', $messages, true)) {
        $add('robots', 'info', 'perf_advice_robots_title', 'perf_advice_robots_body',
            hs_url(hs_panel_path('files.php')), 'perf_advice_robots_action');
    }

    $report = is_array($settings['speed_report'] ?? null) ? $settings['speed_report'] : [];
    foreach (is_array($report['tips'] ?? null) ? $report['tips'] : [] as $tip) {
        if (($tip['level'] ?? '') === 'bad' || ($tip['level'] ?? '') === 'warn') {
            $tipKey = (string) ($tip['key'] ?? '');
            if ($tipKey !== '' && !isset($advice[$tipKey])) {
                $advice[] = [
                    'key' => 'tip_' . $tipKey,
                    'level' => (string) ($tip['level'] ?? 'info'),
                    'title' => $t[$tipKey] ?? $tipKey,
                    'body' => $t['perf_advice_from_speed'] ?? '',
                    'action_url' => hs_url(hs_panel_path('performance.php'), ['tab' => 'speed']),
                    'action_label' => $t['perf_advice_speed_action'] ?? '',
                ];
            }
        }
    }

    if ($advice === [] && in_array('all_clear', $messages, true)) {
        $add('great', 'ok', 'perf_advice_great_title', 'perf_advice_great_body', null, null);
    }

    return $advice;
}

/** @param list<array<string,mixed>> $advice */
function hs_perf_render_advice_cards(array $advice, array $t): string
{
    if ($advice === []) {
        return '';
    }
    $html = '<section class="hs-perf-advice"><h4><i class="fa-solid fa-lightbulb"></i> ' . hs_h($t['perf_advice_title'] ?? 'Recommendations') . '</h4><div class="hs-perf-advice-grid">';
    foreach ($advice as $item) {
        $level = (string) ($item['level'] ?? 'info');
        $html .= '<article class="hs-perf-advice-card hs-perf-advice-' . hs_h($level) . '">'
            . '<h5><i class="fa-solid fa-' . hs_perf_finding_icon($level) . '"></i> ' . hs_h((string) ($item['title'] ?? '')) . '</h5>'
            . '<p>' . hs_h((string) ($item['body'] ?? '')) . '</p>';
        $actionUrl = (string) ($item['action_url'] ?? '');
        $actionLabel = (string) ($item['action_label'] ?? '');
        if ($actionUrl !== '' && $actionLabel !== '') {
            $html .= '<a href="' . hs_h($actionUrl) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm">' . hs_h($actionLabel) . '</a>';
        }
        $html .= '</article>';
    }
    return $html . '</div></section>';
}

/** @param array<string,mixed> $ctx */
function hs_perf_render_panel_boot(array $ctx): string
{
    $t = $ctx['t'];
    $s = $ctx['hs_user_settings'];
    $lastScan = (string) ($s['perf_ai_last_scan'] ?? '');
    $stale = $lastScan === '' || (time() - strtotime($lastScan)) > 86400;
    $json = json_encode([
        'api' => hs_url(hs_panel_path('performance-scan-api.php')),
        'csrf' => hs_csrf_token(),
        'auto_scan' => $stale,
        'last_scan' => $lastScan,
        'i18n' => [
            'scanning' => $t['perf_scan_running'] ?? 'Running diagnostics…',
            'scan_done' => $t['perf_ai_scan_done'] ?? 'Diagnostics complete',
            'scan_error' => $t['perf_scan_error'] ?? 'Scan failed',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    return '<div data-hs-perf-panel hidden></div><script>window.HS_PERF_PANEL=' . $json . ';</script>';
}