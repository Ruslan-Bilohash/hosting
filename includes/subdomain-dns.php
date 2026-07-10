<?php
declare(strict_types=1);

require_once __DIR__ . '/plan-specs.php';

/** @return list<array{path:string,label:string}> */
function hs_public_html_folder_options(int $maxDepth = 2): array
{
    $root = hs_public_path();
    $out = [['path' => '', 'label' => 'public_html/']];
    if (!is_dir($root)) {
        return $out;
    }
    $walk = static function (string $dir, string $rel, int $depth) use (&$walk, &$out, $maxDepth, $root): void {
        if ($depth > $maxDepth) {
            return;
        }
        $items = @scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $name) {
            if ($name === '.' || $name === '..' || $name[0] === '.') {
                continue;
            }
            $full = $dir . DIRECTORY_SEPARATOR . $name;
            if (!is_dir($full)) {
                continue;
            }
            $path = $rel === '' ? $name : $rel . '/' . $name;
            $out[] = ['path' => $path, 'label' => 'public_html/' . $path . '/'];
            $walk($full, $path, $depth + 1);
        }
    };
    $walk($root, '', 1);
    return $out;
}

function hs_normalize_public_html_folder(string $folder): string
{
    $folder = str_replace('\\', '/', trim($folder));
    $folder = trim($folder, '/');
    if (str_contains($folder, '..')) {
        return '';
    }
    return preg_replace('#/+#', '/', $folder) ?? '';
}

/** @return array{ok:bool,error?:string} */
function hs_dns_ensure_subdomain_record(string $userId, string $subName, string $primaryDomain, ?array $user = null): array
{
    $subName = strtolower(trim(preg_replace('/[^a-z0-9-]/', '', $subName) ?? ''));
    if ($subName === '') {
        return ['ok' => false, 'error' => 'invalid'];
    }
    $srv = hs_server_constants($user);
    $host = $subName;
    $cur = hs_user_settings_get($userId);
    $recs = is_array($cur['dns_records'] ?? null) ? $cur['dns_records'] : [];
    foreach ($recs as $r) {
        if (is_array($r) && ($r['host'] ?? '') === $host && strtoupper((string) ($r['type'] ?? '')) === 'A') {
            return ['ok' => true];
        }
    }
    $recs[] = [
        'id' => 'dns_sub_' . bin2hex(random_bytes(3)),
        'type' => 'A',
        'host' => $host,
        'value' => (string) $srv['ip'],
        'ttl' => 14400,
        'system' => true,
        'subdomain' => $subName,
        'created_at' => gmdate('c'),
    ];
    if (!hs_user_settings_save($userId, ['dns_records' => $recs])) {
        return ['ok' => false, 'error' => 'save'];
    }
    return ['ok' => true];
}

/** @param array<string,mixed> $settings */
function hs_apply_subdomain_routes(string $username, array $settings): void
{
    $base = hs_public_path($username);
    if (!is_dir($base)) {
        mkdir($base, 0755, true);
    }
    $primary = strtolower(trim((string) ($settings['primary_domain'] ?? hs_default_primary_domain())));
    $subs = is_array($settings['domains'] ?? null) ? $settings['domains'] : [];
    $rules = '';
    foreach ($subs as $sub) {
        if (!is_array($sub)) {
            continue;
        }
        $name = (string) ($sub['name'] ?? '');
        $folder = hs_normalize_public_html_folder((string) ($sub['folder'] ?? ''));
        if ($name === '' || $primary === '') {
            continue;
        }
        $host = preg_quote($name . '.' . $primary, '/');
        $target = $folder === '' ? '' : ltrim($folder, '/') . '/';
        if ($target !== '' && is_dir(hs_public_path($target))) {
            $rules .= "RewriteCond %{HTTP_HOST} ^{$host}$ [NC]\n";
            $rules .= 'RewriteRule ^(.*)$ /' . HS_PUBLIC_HTML . '/' . preg_quote($target, '/') . '$1 [L,QSA]' . "\n";
        }
    }
    $existing = is_file($base . '/.htaccess') ? (string) file_get_contents($base . '/.htaccess') : '';
    $existing = preg_replace('/# BEGIN HS-SUBDOMAINS.*?# END HS-SUBDOMAINS\n/s', '', $existing) ?? $existing;
    if ($rules !== '') {
        $block = "# BEGIN HS-SUBDOMAINS\n<IfModule mod_rewrite.c>\nRewriteEngine On\n" . $rules . "</IfModule>\n# END HS-SUBDOMAINS\n";
        $existing = $block . $existing;
    }
    if (trim($existing) !== '') {
        file_put_contents($base . '/.htaccess', $existing);
    }
}

/** @return list<array<string,mixed>> */
function hs_dns_all_records(array $settings, ?array $user = null): array
{
    $srv = hs_server_constants($user);
    $domain = strtolower(trim((string) ($settings['primary_domain'] ?? hs_default_primary_domain())));
    $system = [
        ['type' => 'A', 'host' => '@', 'value' => $srv['ip'], 'ttl' => 14400, 'system' => true],
        ['type' => 'CNAME', 'host' => 'www', 'value' => $domain, 'ttl' => 14400, 'system' => true],
        ['type' => 'NS', 'host' => '@', 'value' => $srv['ns1'], 'ttl' => 86400, 'system' => true],
        ['type' => 'NS', 'host' => '@', 'value' => $srv['ns2'], 'ttl' => 86400, 'system' => true],
    ];
    foreach ($settings['domains'] ?? [] as $sub) {
        if (!is_array($sub)) {
            continue;
        }
        $name = (string) ($sub['name'] ?? '');
        if ($name === '') {
            continue;
        }
        $system[] = [
            'type' => 'A',
            'host' => $name,
            'value' => $srv['ip'],
            'ttl' => 14400,
            'system' => true,
            'subdomain' => $name,
        ];
    }
    $custom = [];
    foreach (is_array($settings['dns_records'] ?? null) ? $settings['dns_records'] : [] as $r) {
        if (!is_array($r) || !empty($r['system'])) {
            continue;
        }
        $custom[] = $r;
    }
    return ['system' => $system, 'custom' => $custom];
}