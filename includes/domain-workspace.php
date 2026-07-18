<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/user-settings.php';
require_once __DIR__ . '/subdomain-dns.php';
require_once __DIR__ . '/panel-domains.php';
require_once __DIR__ . '/installer.php';

/** Sentinel: serve domain from account root public_html/{username}/ (not a subfolder). */
const HS_DOMAIN_ROOT_FOLDER = '_root';

/** Safe folder name for a domain (test.com → test.com). */
function hs_domain_folder_name(string $domain): string
{
    $domain = strtolower(trim($domain));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#/.*$#', '', $domain);
    $domain = preg_replace('/[^a-z0-9._-]/', '', $domain) ?? '';
    return substr($domain, 0, 64);
}

/** True when path has a real website (not empty / not panel placeholder). */
function hs_domain_folder_has_site(string $absPath): bool
{
    if (!is_dir($absPath)) {
        return false;
    }
    $php = $absPath . '/index.php';
    if (is_file($php)) {
        $sz = (int) @filesize($php);

        return $sz > 80;
    }
    $html = $absPath . '/index.html';
    if (is_file($html) && !hs_domain_index_is_placeholder($html)) {
        return true;
    }

    return false;
}

/**
 * Default folder for a domain: always public_html/{user}/{domain}/
 * (Each purchased domain gets its own folder — put index.php / index.html there.)
 * Explicit mapping in domain_roots may override to another folder or account root (_root).
 */
function hs_domain_detect_best_site_folder(array $user, string $domain, ?array $settings = null): string
{
    $domain = strtolower(trim($domain));
    if ($settings === null) {
        $settings = hs_user_settings_get((string) ($user['id'] ?? ''));
    }
    $roots = hs_domain_roots_map($settings);
    // Respect saved assignment (including _root if client chose account root)
    if (isset($roots[$domain]) && $roots[$domain] !== '') {
        return $roots[$domain];
    }
    $domFolder = hs_domain_folder_name($domain);

    return $domFolder !== '' ? $domFolder : HS_DOMAIN_ROOT_FOLDER;
}

/**
 * Ensure domain docroot exists and will serve index.php / index.html.
 * Does not overwrite real apps.
 */
function hs_domain_seed_docroot_files(array $user, string $domain, string $absPath): void
{
    if (!is_dir($absPath)) {
        @mkdir($absPath, 0755, true);
    }
    $ht = $absPath . '/.htaccess';
    $htBody = "Options -Indexes\nDirectoryIndex index.php index.html index.htm\n";
    if (!is_file($ht)) {
        @file_put_contents($ht, $htBody);
    } elseif (!str_contains((string) @file_get_contents($ht), 'DirectoryIndex')) {
        @file_put_contents($ht, $htBody . "\n" . (string) @file_get_contents($ht));
    }
    $hasPhp = is_file($absPath . '/index.php');
    $hasHtml = is_file($absPath . '/index.html') || is_file($absPath . '/index.htm');
    if ($hasPhp || ($hasHtml && !hs_domain_index_is_placeholder($absPath . '/index.html'))) {
        return;
    }
    // Starter page so the domain responds until client uploads their site
    $domainH = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . $domainH . '</title>'
        . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
        . 'font-family:system-ui,sans-serif;background:#f8fafc;color:#0f172a}'
        . 'main{max-width:32rem;padding:2rem;text-align:center}'
        . 'code{background:#e2e8f0;padding:.15rem .4rem;border-radius:6px;font-size:.9rem}</style></head><body>'
        . '<main class="hs-domain-starter">'
        . '<h1 style="font-size:1.35rem;margin:0 0 .75rem">' . $domainH . '</h1>'
        . '<p style="color:#64748b;line-height:1.55">Upload <code>index.php</code> or <code>index.html</code> '
        . 'into this domain folder via File Manager. The site will open here automatically.</p>'
        . '</main></body></html>';
    @file_put_contents($absPath . '/index.html', $html);
    if (!is_file($absPath . '/robots.txt')) {
        @file_put_contents($absPath . '/robots.txt', "User-agent: *\nAllow: /\n");
    }
}

/** Client-relative docroot path: {username} or {username}/{domainFolder}. */
function hs_domain_docroot_rel(array $user, string $domain, ?array $settings = null): string
{
    $username = hs_user_public_rel_prefix($user);
    $domain = strtolower(trim($domain));
    if ($domain === '') {
        return $username;
    }
    if ($settings === null) {
        $settings = hs_user_settings_get((string) ($user['id'] ?? ''));
    }
    $roots = hs_domain_roots_map($settings);
    $folder = $roots[$domain] ?? '';
    if ($folder === '' || $folder === HS_DOMAIN_ROOT_FOLDER || $folder === '@' || $folder === '.') {
        if ($folder === HS_DOMAIN_ROOT_FOLDER || $folder === '@' || $folder === '.') {
            return $username;
        }
        // No mapping yet — detect (does not save here)
        $folder = hs_domain_detect_best_site_folder($user, $domain, $settings);
        if ($folder === HS_DOMAIN_ROOT_FOLDER || $folder === '@' || $folder === '.') {
            return $username;
        }
    }
    $folder = hs_normalize_public_html_folder($folder);
    if ($folder === '' || $folder === HS_DOMAIN_ROOT_FOLDER) {
        return $username;
    }
    // folder may be "username/sub" from legacy — strip prefix
    if ($folder === $username) {
        return $username;
    }
    if (str_starts_with($folder, $username . '/')) {
        return $folder;
    }

    return trim($username . '/' . $folder, '/');
}

function hs_domain_docroot_path(array $user, string $domain, ?array $settings = null): string
{
    return hs_public_path(hs_domain_docroot_rel($user, $domain, $settings));
}

/** @return array<string, string> domain => folder key relative to username (_root = account root) */
function hs_domain_roots_map(array $settings): array
{
    $raw = $settings['domain_roots'] ?? [];
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $domain => $folder) {
        if (!is_string($domain) || !is_string($folder)) {
            continue;
        }
        $domain = strtolower(trim($domain));
        $folder = trim(str_replace('\\', '/', $folder), '/');
        if ($domain === '') {
            continue;
        }
        if ($folder === '' || $folder === '@' || $folder === '.') {
            $folder = HS_DOMAIN_ROOT_FOLDER;
        }
        if ($folder !== HS_DOMAIN_ROOT_FOLDER) {
            $folder = hs_normalize_public_html_folder($folder);
            if ($folder === '') {
                continue;
            }
        }
        $out[$domain] = $folder;
    }

    return $out;
}

function hs_domain_roots_save(string $userId, string $domain, string $folder): bool
{
    $domain = strtolower(trim($domain));
    if ($domain === '') {
        return false;
    }
    $folder = trim(str_replace('\\', '/', $folder), '/');
    if ($folder === '' || $folder === '@' || $folder === '.') {
        $folder = HS_DOMAIN_ROOT_FOLDER;
    }
    if ($folder !== HS_DOMAIN_ROOT_FOLDER) {
        $folder = hs_normalize_public_html_folder($folder);
        if ($folder === '') {
            return false;
        }
    }
    $settings = hs_user_settings_get($userId);
    $roots = hs_domain_roots_map($settings);
    $roots[$domain] = $folder;

    return hs_user_settings_save($userId, ['domain_roots' => $roots]);
}

/**
 * Auto-bind domain → its own website folder + rebuild HTTP routes.
 * Default: public_html/{user}/{domain}/ — each domain is independent.
 * Client may reassign folder in Domains panel (including account root).
 *
 * @return array{ok:bool,folder?:string,rel?:string,error?:string}
 */
function hs_domain_auto_bind_site(array $user, string $domain, bool $forceDetect = false): array
{
    $userId = (string) ($user['id'] ?? '');
    $domain = strtolower(trim($domain));
    if ($userId === '' || $domain === '' || !str_contains($domain, '.')) {
        return ['ok' => false, 'error' => 'domain'];
    }
    $settings = hs_user_settings_get($userId);
    $roots = hs_domain_roots_map($settings);
    $folder = $roots[$domain] ?? '';
    // Missing mapping → always own folder public_html/{user}/{domain}/
    if ($folder === '') {
        $folder = hs_domain_folder_name($domain);
    }
    // Legacy: everything on account root → split to domain folders (multi-domain model)
    if ($forceDetect && $folder === HS_DOMAIN_ROOT_FOLDER) {
        $folder = hs_domain_folder_name($domain);
    }
    if ($folder === '') {
        $folder = hs_domain_folder_name($domain) ?: HS_DOMAIN_ROOT_FOLDER;
    }
    if (!hs_domain_roots_save($userId, $domain, $folder)) {
        return ['ok' => false, 'error' => 'save'];
    }
    $settings = hs_user_settings_get($userId);
    $rel = hs_domain_docroot_rel($user, $domain, $settings);
    $path = hs_public_path($rel);
    hs_domain_seed_docroot_files($user, $domain, $path);
    // Ensure domain is in choices (primary/active/extra)
    $choices = hs_user_domain_choices($settings);
    if (!in_array($domain, $choices, true)) {
        $primary = strtolower(trim((string) ($settings['primary_domain'] ?? '')));
        if ($primary === '' || hs_domain_is_host_brand($primary)) {
            hs_user_settings_save($userId, [
                'primary_domain' => $domain,
                'active_domain' => $domain,
            ]);
        } else {
            $extra = is_array($settings['extra_domains'] ?? null) ? $settings['extra_domains'] : [];
            if (!in_array($domain, $extra, true)) {
                $extra[] = $domain;
                hs_user_settings_save($userId, ['extra_domains' => $extra, 'active_domain' => $domain]);
            }
        }
        $settings = hs_user_settings_get($userId);
    }
    $username = hs_user_public_rel_prefix($user);
    hs_apply_client_domain_routes($username, $settings);
    hs_rebuild_global_domain_routes();
    hs_apply_account_public_routes();

    return ['ok' => true, 'folder' => $folder, 'rel' => $rel];
}

/**
 * Ensure every domain on the account has a folder + route.
 * Default folder = domain name; client can reassign via panel.
 *
 * @return list<array{domain:string,folder:string,rel:string}>
 */
function hs_domain_auto_bind_all_for_user(array $user, bool $migrateRootToDomainFolder = true): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return [];
    }
    require_once __DIR__ . '/panel-domains.php';
    $settings = hs_user_settings_get($userId);
    $domains = hs_user_domain_choices($settings);
    $out = [];
    foreach ($domains as $domain) {
        $domain = strtolower(trim((string) $domain));
        if ($domain === '' || !str_contains($domain, '.')) {
            continue;
        }
        $res = hs_domain_auto_bind_site($user, $domain, $migrateRootToDomainFolder);
        if (!empty($res['ok'])) {
            $out[] = [
                'domain' => $domain,
                'folder' => (string) ($res['folder'] ?? ''),
                'rel' => (string) ($res['rel'] ?? ''),
            ];
        }
    }
    hs_rebuild_global_domain_routes();

    return $out;
}

/**
 * Assign domain → folder (relative to account, or _root for account root).
 *
 * @return array{ok:bool,error?:string,rel?:string}
 */
function hs_domain_assign_folder(array $user, string $domain, string $folder): array
{
    $userId = (string) ($user['id'] ?? '');
    $domain = strtolower(trim($domain));
    $username = hs_user_public_rel_prefix($user);
    if ($userId === '' || $domain === '' || !str_contains($domain, '.')) {
        return ['ok' => false, 'error' => 'domain'];
    }
    $folder = trim(str_replace('\\', '/', $folder), '/');
    if ($folder === '' || $folder === $username || $folder === '@' || $folder === '.') {
        $folder = HS_DOMAIN_ROOT_FOLDER;
    } elseif (str_starts_with($folder, $username . '/')) {
        $folder = substr($folder, strlen($username) + 1);
    }
    // Security: only under this account
    if ($folder !== HS_DOMAIN_ROOT_FOLDER) {
        $folder = hs_normalize_public_html_folder($folder);
        $relCheck = $username . '/' . $folder;
        if (!hs_user_owns_public_rel($user, $relCheck) && $folder !== hs_domain_folder_name($domain)) {
            // allow creating new domain-named or subfolder under user
            if (!str_starts_with($relCheck, $username . '/') && $folder !== $username) {
                return ['ok' => false, 'error' => 'forbidden'];
            }
        }
    }
    if (!hs_domain_roots_save($userId, $domain, $folder)) {
        return ['ok' => false, 'error' => 'save'];
    }
    $res = hs_domain_auto_bind_site($user, $domain, false);

    return !empty($res['ok'])
        ? ['ok' => true, 'rel' => (string) ($res['rel'] ?? '')]
        : ['ok' => false, 'error' => (string) ($res['error'] ?? 'bind')];
}

/** @return array{0:string,1:string} url, label */
function hs_domain_workspace_link_target(array $user, string $domain, ?array $settings = null): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($settings === null && $userId !== '') {
        $settings = hs_user_settings_get($userId);
    }
    $settings ??= [];
    $status = (string) ($user['subscription_status'] ?? 'active');
    if ($status === 'pending') {
        return [hs_url(hs_panel_path('activate.php')), 'Оплатити та активувати хостинг'];
    }
    $landing = is_array($settings['landing_builder'] ?? null) ? $settings['landing_builder'] : [];
    $publishedUrl = trim((string) ($landing['published_url'] ?? ''));
    if ($publishedUrl !== '' && !empty($landing['published_at'])) {
        return [$publishedUrl, 'Відкрити сайт'];
    }
    $panel = hs_url(hs_panel_path(''));
    if ($domain !== '') {
        return [$panel, 'Панель керування — ' . $domain];
    }

    return [$panel, 'Панель керування'];
}

function hs_domain_link_index_html(string $url, string $label, string $domain = ''): string
{
    $robots = function_exists('hs_prelaunch_mode') && hs_prelaunch_mode()
        ? '<meta name="robots" content="noindex,nofollow,noarchive">'
        : '';
    $title = $domain !== '' ? $domain : 'Hosting';

    return '<!DOCTYPE html><html lang="uk"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . $robots
        . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>'
        . '<style>'
        . 'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;font-family:system-ui,sans-serif;color:#0f172a}'
        . 'main{text-align:center;padding:2rem}'
        . 'a{display:inline-block;padding:.85rem 1.35rem;border-radius:12px;background:#4f46e5;color:#fff;text-decoration:none;font-weight:700;font-size:1rem;box-shadow:0 4px 14px rgba(79,70,229,.35)}'
        . 'a:hover{background:#4338ca}'
        . 'p{margin:1rem 0 0;font-size:.85rem;color:#64748b}'
        . '</style></head><body class="hs-domain-link-page"><main>'
        . '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>'
        . ($domain !== '' ? '<p>' . htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') . '</p>' : '')
        . '</main></body></html>';
}

function hs_domain_index_is_placeholder(string $indexFile): bool
{
    if (!is_file($indexFile)) {
        return true;
    }
    $content = (string) file_get_contents($indexFile);
    if ($content === '') {
        return true;
    }
    if (str_contains($content, 'hs-domain-link-page')) {
        return true;
    }

    return str_contains($content, 'Your site is live') || str_contains($content, 'Install a CMS');
}

/** Create domain docroot + default index; register in domain_roots (auto-detect site folder). */
function hs_domain_ensure_workspace(array $user, string $domain, bool $rebuildRoutes = true): bool
{
    $userId = (string) ($user['id'] ?? '');
    $domain = strtolower(trim($domain));
    if ($userId === '' || $domain === '') {
        return false;
    }

    $bind = hs_domain_auto_bind_site($user, $domain, false);
    if (empty($bind['ok'])) {
        return false;
    }

    $settings = hs_user_settings_get($userId);
    $rel = (string) ($bind['rel'] ?? hs_domain_docroot_rel($user, $domain, $settings));
    if (!hs_user_owns_public_rel($user, $rel)) {
        return false;
    }

    $path = hs_public_path($rel);
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    $folder = (string) ($bind['folder'] ?? '');
    // Only write placeholder when this is a dedicated empty domain folder (not account root with app)
    if ($folder !== HS_DOMAIN_ROOT_FOLDER && !hs_domain_folder_has_site($path)) {
        $landing = is_array($settings['landing_builder'] ?? null) ? $settings['landing_builder'] : [];
        $landingPublished = !empty($landing['published_at']) && trim((string) ($landing['published_url'] ?? '')) !== '';
        $index = $path . '/index.html';
        if (!$landingPublished && (hs_domain_index_is_placeholder($index) || !is_file($path . '/index.php'))) {
            if (!is_file($path . '/index.php')) {
                [$linkUrl, $linkLabel] = hs_domain_workspace_link_target($user, $domain, $settings);
                file_put_contents($index, hs_domain_link_index_html($linkUrl, $linkLabel, $domain));
            }
        }
    }
    $ht = $path . '/.htaccess';
    if (!is_file($ht)) {
        $headers = hs_prelaunch_mode()
            ? "<IfModule mod_headers.c>\nHeader set X-Robots-Tag \"noindex, nofollow, noarchive\"\n</IfModule>\n"
            : '';
        file_put_contents($ht, $headers . "Options -Indexes\nDirectoryIndex index.php index.html\n");
    }
    if (!is_file($path . '/robots.txt')) {
        file_put_contents($path . '/robots.txt', hs_prelaunch_mode() ? "User-agent: *\nDisallow: /\n" : "User-agent: *\nAllow: /\n");
    }

    $username = hs_user_public_rel_prefix($user);
    hs_apply_client_domain_routes($username, $settings);
    if ($rebuildRoutes) {
        hs_rebuild_global_domain_routes();
    }

    return true;
}

/** Ensure folders for primary, extra, and subdomain hostnames. */
function hs_domain_ensure_all_workspaces(array $user, ?array $settings = null): void
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return;
    }
    if ($settings === null) {
        $settings = hs_user_settings_get($userId);
    }
    foreach (hs_user_domain_choices($settings) as $domain) {
        hs_domain_ensure_workspace($user, $domain, false);
    }
    hs_rebuild_global_domain_routes();
    hs_apply_account_public_routes();
}

/** CMS paths that must not be rewritten to client docroots. */
function hs_domain_route_skip_pattern(): string
{
    return '(admin|panel|pma|assets|scripts|login|register|checkout|logout|install\\.php|migrate-to-mysql\\.php|config\\.php|data|lang|includes|seo)';
}

const HS_ACCOUNT_PATH_MARKER = 'HS-ACCOUNT-PATHS';

/** On cPanel hosts, serve /username/ from public_html/username/ (clean URLs). */
function hs_apply_account_public_routes(): void
{
    if (hs_public_url_uses_nested_folder()) {
        return;
    }
    require_once __DIR__ . '/performance.php';
    $skip = hs_domain_route_skip_pattern();
    $block = "<IfModule mod_rewrite.c>\nRewriteEngine On\n"
        . 'RewriteCond %{REQUEST_URI} !^/' . HS_PUBLIC_HTML . "/\n"
        . "RewriteCond %{REQUEST_URI} !^/({$skip})(/|\$)\n"
        . 'RewriteRule ^([a-z0-9_-]+)/(.*)$ ' . HS_PUBLIC_HTML . "/$1/$2 [L]\n"
        . '</IfModule>';
    hs_perf_patch_htaccess(hs_cms_root_htaccess(), HS_ACCOUNT_PATH_MARKER, $block);
}

/** Per-account .htaccess hints (subfolder access). */
function hs_apply_client_domain_routes(string $username, array $settings): void
{
    require_once __DIR__ . '/subdomain-dns.php';
    hs_apply_subdomain_routes($username, $settings);
}

/**
 * Map HTTP_HOST → public_html/{username}/… for all clients.
 * Written to CMS document-root .htaccess (not nested client tree) so addon domains work.
 */
function hs_rebuild_global_domain_routes(): void
{
    require_once __DIR__ . '/performance.php';

    // Clean legacy copy wrongly written under nested public_html/.htaccess
    $legacy = hs_public_path('.htaccess');
    if (is_file($legacy)) {
        $legacyBody = (string) file_get_contents($legacy);
        $cleaned = preg_replace('/# BEGIN HS-DOMAIN-ROUTES.*?# END HS-DOMAIN-ROUTES\n?/s', '', $legacyBody) ?? $legacyBody;
        if ($cleaned !== $legacyBody) {
            @file_put_contents($legacy, $cleaned);
        }
    }

    $skip = hs_domain_route_skip_pattern();
    $rules = '';
    foreach (hs_users() as $user) {
        if (!is_array($user)) {
            continue;
        }
        $userId = (string) ($user['id'] ?? '');
        if ($userId === '') {
            continue;
        }
        $settings = hs_user_settings_get($userId);
        $username = hs_user_public_rel_prefix($user);
        $roots = hs_domain_roots_map($settings);
        $choices = hs_user_domain_choices($settings);

        foreach ($choices as $host) {
            $host = strtolower(trim($host));
            if ($host === '' || !str_contains($host, '.')) {
                continue;
            }
            $folder = $roots[$host] ?? '';
            if ($folder === '' || $folder === HS_DOMAIN_ROOT_FOLDER || $folder === '@' || $folder === '.') {
                $folder = hs_domain_detect_best_site_folder($user, $host, $settings);
            }
            if ($folder === HS_DOMAIN_ROOT_FOLDER || $folder === '@' || $folder === '.' || $folder === $username) {
                $target = $username . '/';
            } else {
                $folder = hs_normalize_public_html_folder((string) $folder);
                if ($folder === '') {
                    $folder = hs_domain_folder_name($host);
                }
                if (str_starts_with($folder, $username . '/')) {
                    $target = $folder . '/';
                } else {
                    $target = trim($username . '/' . $folder, '/') . '/';
                }
            }
            if (!is_dir(hs_public_path(rtrim($target, '/')))) {
                continue;
            }
            $hostRe = preg_quote($host, '/');
            $rules .= "RewriteCond %{HTTP_HOST} ^(www\\.)?{$hostRe}$ [NC]\n";
            $rules .= "RewriteCond %{REQUEST_URI} !^/({$skip})(/|\$)\n";
            // From document root: public_html/{user}/… (nested client tree)
            $rules .= 'RewriteRule ^(.*)$ ' . HS_PUBLIC_HTML . '/' . $target . '$1 [L,QSA]' . "\n";
        }
    }

    $block = '';
    if ($rules !== '') {
        $block = "<IfModule mod_rewrite.c>\nRewriteEngine On\n" . $rules . "</IfModule>";
    }
    hs_perf_patch_htaccess(hs_cms_root_htaccess(), 'HS-DOMAIN-ROUTES', $block);
}

/** Display path for file manager — each client sees only their own public_html/. */
function hs_fm_scope_label(array $user, ?array $settings = null): string
{
    return 'public_html/';
}

/** Whether file manager shows whole account or active domain only. */
function hs_fm_scope_is_domain(array $user, ?array $settings = null): bool
{
    if (isset($_GET['scope']) && $_GET['scope'] === 'account') {
        return false;
    }
    if ($settings === null) {
        $settings = hs_user_settings_get((string) ($user['id'] ?? ''));
    }
    $domain = hs_active_domain($settings);
    if ($domain === '') {
        return false;
    }
    $rel = hs_domain_docroot_rel($user, $domain, $settings);

    return is_dir(hs_public_path($rel));
}