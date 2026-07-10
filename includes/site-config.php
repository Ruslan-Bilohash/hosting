<?php
declare(strict_types=1);

require_once __DIR__ . '/php-config.php';

/** Write .htaccess redirects, hotlink, indexing from user settings */
function hs_apply_site_config(string $username, array $settings): void
{
    $base = hs_public_path($username);
    if (!is_dir($base)) {
        mkdir($base, 0755, true);
    }

    $phpVer = (string) ($settings['php_version'] ?? '8.2');
    $lines = [hs_php_htaccess_block($phpVer), "# Hosting CMS — auto-generated\n", "Options -Indexes\n"];

    if (!empty($settings['hotlink_protect'])) {
        $lines[] = "<IfModule mod_rewrite.c>\nRewriteEngine On\n";
        $lines[] = "RewriteCond %{HTTP_REFERER} !^$\n";
        $lines[] = "RewriteCond %{HTTP_REFERER} !^https?://([^.]+\\.)?" . preg_quote((string) ($settings['primary_domain'] ?? 'localhost'), '/') . " [NC]\n";
        $lines[] = "RewriteRule \\.(jpe?g|png|gif|webp)$ - [F,NC]\n</IfModule>\n";
    }

    $redirects = is_array($settings['redirects'] ?? null) ? $settings['redirects'] : [];
    if ($redirects !== []) {
        $lines[] = "<IfModule mod_rewrite.c>\nRewriteEngine On\n";
        foreach ($redirects as $r) {
            $from = trim((string) ($r['from'] ?? ''));
            $to = trim((string) ($r['to'] ?? ''));
            if ($from === '' || $to === '') {
                continue;
            }
            $from = ltrim($from, '/');
            $lines[] = 'RewriteRule ^' . preg_quote($from, '/') . '$ ' . $to . " [R=301,L]\n";
        }
        $lines[] = "</IfModule>\n";
    }

    if (!empty($settings['htpasswd_user']) && !empty($settings['htpasswd_pass'])) {
        $salt = '$1$' . substr(bin2hex(random_bytes(4)), 0, 8) . '$';
        $hash = crypt((string) $settings['htpasswd_pass'], $salt);
        if (is_string($hash) && $hash !== '') {
            $lines[] = "AuthType Basic\nAuthName \"Protected\"\n";
            $lines[] = "AuthUserFile " . $base . "/.htpasswd\n";
            $lines[] = "Require valid-user\n";
            file_put_contents($base . '/.htpasswd', (string) $settings['htpasswd_user'] . ':' . $hash . "\n");
        }
    }

    file_put_contents($base . '/.htaccess', implode('', $lines));

    $robots = !empty($settings['search_indexing'])
        ? "User-agent: *\nAllow: /\n"
        : "User-agent: *\nDisallow: /\n";
    file_put_contents($base . '/robots.txt', $robots);
}