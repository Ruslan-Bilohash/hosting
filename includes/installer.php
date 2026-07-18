<?php
declare(strict_types=1);

require_once __DIR__ . '/ecosystem-catalog.php';

function hs_user_public_subdir(array $user): string
{
    return preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user')) ?: 'user';
}

/** Default folder under public_html/ for new installs */
function hs_install_default_base(array $user): string
{
    return hs_user_public_subdir($user);
}

function hs_install_base_locked(array $user): bool
{
    return defined('HS_DEMO_MODE') && HS_DEMO_MODE && hs_install_default_base($user) === 'demo';
}

/** @return string Relative path inside public_html/ (no leading slash) */
function hs_install_normalize_base(array $user, string $base): string
{
    $base = trim(str_replace('\\', '/', $base), '/');
    if ($base === '' || str_contains($base, '..')) {
        return hs_install_default_base($user);
    }
    $base = preg_replace('#/+#', '/', $base) ?? $base;
    $allowed = hs_install_default_base($user);

    if (hs_install_base_locked($user)) {
        if ($base === $allowed || str_starts_with($base, $allowed . '/')) {
            return $base;
        }
        return $allowed;
    }

    if ($base === $allowed || str_starts_with($base, $allowed . '/')) {
        return $base;
    }

    return $allowed;
}

/** Whether site files are served from install_base root (not a subfolder). */
function hs_install_site_at_root(array $user, array $site): bool
{
    // Trust explicit install flags only — never infer from ambient index.* on the base
    // (that wrongly marked every folder site as "root" after one root install).
    if (!empty($site['at_root']) || strtolower((string) ($site['install_mode'] ?? '')) === 'root') {
        return true;
    }
    $app = strtolower((string) ($site['app'] ?? ''));
    $slug = preg_replace('/[^a-z0-9_-]/i', '', (string) ($site['slug'] ?? '')) ?: '';
    if ($app === 'landing') {
        return true;
    }
    // Legacy: slug www with no install_mode meant account root.
    if ($slug === 'www') {
        return true;
    }

    return false;
}

/** Auto-seeded empty Welcome site (safe to free for installer on 1-site plans). */
function hs_site_is_auto_welcome(array $site): bool
{
    $slug = strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string) ($site['slug'] ?? '')) ?: '');
    $app = strtolower((string) ($site['app'] ?? ''));

    return $slug === 'welcome' && ($app === 'empty' || $app === '' || $app === 'php');
}

/** @return string e.g. demo/my-shop — prefers paths that exist on disk */
function hs_install_path_rel(array $user, array $site): string
{
    $slug = preg_replace('/[^a-z0-9_-]/i', '', (string) ($site['slug'] ?? '')) ?: 'site';
    $base = hs_install_normalize_base($user, (string) ($site['install_base'] ?? hs_install_default_base($user)));

    if (hs_install_site_at_root($user, $site)) {
        return $base;
    }

    $candidates = [trim($base . '/' . $slug, '/')];
    $defaultBase = hs_install_default_base($user);
    if ($defaultBase !== $base) {
        $candidates[] = trim($defaultBase . '/' . $slug, '/');
    }
    // Prefer the canonical folder path even if disk folder is missing (delete/path labels).
    foreach ($candidates as $rel) {
        if ($rel === '' || str_contains($rel, '..')) {
            continue;
        }
        $path = hs_public_path($rel);
        if (is_dir($path) || is_file($path . '/index.html') || is_file($path . '/index.php')) {
            return $rel;
        }
    }

    return $candidates[0];
}

function hs_install_path_label(array $user, string $base, string $slug): string
{
    return 'public_html/' . hs_install_path_rel($user, [
        'install_base' => $base,
        'slug' => $slug,
        'app' => '',
    ]);
}

/** Absolute public URL for a site record */
function hs_public_url_for_site(array $user, array $site): string
{
    global $site_url;
    // Empty string on site.domain must not block account active_domain (?? only skips null).
    $domain = trim((string) ($site['domain'] ?? ''));
    if ($domain === '' && function_exists('hs_user_settings_get')) {
        $settings = hs_user_settings_get((string) ($user['id'] ?? ''));
        $domain = trim((string) (
            $settings['active_domain']
            ?? $settings['primary_domain']
            ?? $settings['platform_free_host']
            ?? ''
        ));
    }
    if ($domain !== '' && !str_contains($domain, '/') && !str_contains($domain, ' ')) {
        return 'https://' . preg_replace('#^https?://#i', '', $domain) . '/';
    }
    $rel = trim(hs_install_path_rel($user, $site), '/');
    // Public path uses rewrite: /{username}/… → public_html/{username}/ (no /public_html/ in browser URL)
    return rtrim((string) $site_url, '/') . '/' . $rel . '/';
}

/** @return array{default_base:string,locked:bool,prefix_label:string} */
function hs_welcome_index_html(): string
{
    return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Welcome</title><style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8fafc;color:#0f172a}main{text-align:center;padding:2rem}h1{font-size:1.75rem}</style></head><body><main><h1>Your site is live</h1><p>BILOHASH Hosting — install a CMS from the panel.</p></main></body></html>';
}

/** Ensure account folder exists (tenant isolation). Welcome site is seeded only once. */
function hs_ensure_user_workspace(array $user): void
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return;
    }
    $base = hs_install_default_base($user);
    $root = hs_public_path($base);
    if (!is_dir($root)) {
        mkdir($root, 0755, true);
    }
    $ht = $root . '/.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "Options -Indexes\n");
    }

    $userSites = hs_sites_for_user($userId);
    if ($userSites === []) {
        // Do NOT re-create Welcome after the client deleted it — that blocked installer on 1-site plans.
        require_once __DIR__ . '/user-settings.php';
        $settings = hs_user_settings_get($userId);
        $alreadySeeded = !empty($settings['workspace_welcome_seeded']);
        if (!$alreadySeeded) {
            $welcomeRel = trim($base . '/welcome', '/');
            $welcomePath = hs_public_path($welcomeRel);
            if (!is_dir($welcomePath)) {
                mkdir($welcomePath, 0755, true);
            }
            $index = $welcomePath . '/index.html';
            if (!is_file($index)) {
                file_put_contents($index, hs_welcome_index_html());
            }

            hs_site_add_for_user($userId, [
                'id' => hs_new_id('s'),
                'slug' => 'welcome',
                'install_base' => $base,
                'title' => 'Welcome Site',
                'domain' => '',
                'app' => 'empty',
                'status' => 'active',
                'created_at' => gmdate('c'),
                'install_mode' => 'folder',
                'at_root' => false,
            ]);
            hs_user_settings_save($userId, ['workspace_welcome_seeded' => true]);
            $userSites = hs_sites_for_user($userId);
        }
    } else {
        // Mark seeded so future empty lists never re-inject Welcome.
        require_once __DIR__ . '/user-settings.php';
        $settings = hs_user_settings_get($userId);
        if (empty($settings['workspace_welcome_seeded'])) {
            hs_user_settings_save($userId, ['workspace_welcome_seeded' => true]);
        }
    }

    foreach ($userSites as $site) {
        $rel = hs_install_path_rel($user, $site);
        if (!hs_user_owns_public_rel($user, $rel)) {
            continue;
        }
        $sitePath = hs_public_path($rel);
        if (!is_dir($sitePath) && !hs_install_site_at_root($user, $site)) {
            mkdir($sitePath, 0755, true);
        }
        if (($site['app'] ?? '') === 'empty' && !is_file($sitePath . '/index.php')) {
            $index = $sitePath . '/index.html';
            if (!is_file($index) && is_dir($sitePath)) {
                file_put_contents($index, hs_welcome_index_html());
            }
        }
    }
}

/**
 * Free auto-seeded Welcome site so installer can run on 1-site plans.
 *
 * @return array{ok:bool,freed?:bool,error?:string}
 */
function hs_install_try_free_placeholder(array $user): array
{
    return hs_install_try_free_slot($user, ['allow_replace' => false]);
}

/**
 * Make room for a new install (silent — never flash “site deleted” to the UI).
 *
 * - Always free auto Welcome when present
 * - On 1-site plans with allow_replace: remove the only site so reinstall works
 * - Root mode: remove existing root site for the same install base
 *
 * @param array{allow_replace?:bool,mode?:string,install_base?:string} $opts
 * @return array{ok:bool,freed?:bool,kind?:string,error?:string}
 */
function hs_install_try_free_slot(array $user, array $opts = []): array
{
    if (hs_user_can_add_site($user)) {
        return ['ok' => true, 'freed' => false];
    }
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return ['ok' => false, 'error' => 'user'];
    }

    // 1) Auto Welcome first
    foreach (hs_sites_for_user($userId) as $site) {
        if (!hs_site_is_auto_welcome($site)) {
            continue;
        }
        $res = hs_delete_user_site($user, (string) ($site['id'] ?? ''));
        if (!empty($res['ok']) && hs_user_can_add_site($user)) {
            return ['ok' => true, 'freed' => true, 'kind' => 'welcome'];
        }
        break;
    }

    $mode = strtolower(trim((string) ($opts['mode'] ?? '')));
    $base = hs_install_normalize_base(
        $user,
        (string) ($opts['install_base'] ?? hs_install_default_base($user))
    );

    // 2) Root reinstall: free existing root site on same base
    if ($mode === 'root') {
        foreach (hs_sites_for_user($userId) as $site) {
            $sBase = hs_install_normalize_base($user, (string) ($site['install_base'] ?? hs_install_default_base($user)));
            if ($sBase !== $base || !hs_install_site_at_root($user, $site)) {
                continue;
            }
            $res = hs_delete_user_site($user, (string) ($site['id'] ?? ''));
            if (!empty($res['ok']) && hs_user_can_add_site($user)) {
                return ['ok' => true, 'freed' => true, 'kind' => 'root'];
            }
        }
    }

    // 3) Mini/1-site plan: replace the only site when installing a new app
    $allowReplace = !empty($opts['allow_replace']);
    $limit = hs_user_site_limit($user);
    $sites = hs_sites_for_user($userId);
    if ($allowReplace && $limit <= 1 && count($sites) === 1) {
        $res = hs_delete_user_site($user, (string) ($sites[0]['id'] ?? ''));
        if (!empty($res['ok']) && hs_user_can_add_site($user)) {
            return ['ok' => true, 'freed' => true, 'kind' => 'replace'];
        }

        return ['ok' => false, 'error' => (string) ($res['error'] ?? 'delete')];
    }

    return ['ok' => false, 'error' => 'limit'];
}

function hs_install_ui_paths(array $user): array
{
    $defaultBase = hs_install_default_base($user);
    $locked = hs_install_base_locked($user);
    return [
        'default_base' => $defaultBase,
        'locked' => $locked,
        'prefix_label' => 'public_html/' . $defaultBase . '/',
    ];
}

/** Shared install-path UI for installer & WordPress forms */
function hs_install_path_fields_html(array $user, array $t, string $slugName = 'slug', string $slugId = 'slug'): string
{
    $paths = hs_install_ui_paths($user);
    $html = '<div class="hs-field"><label>' . hs_h($t['installer_path'] ?? 'Install path') . '</label>';
    if ($paths['locked']) {
        $html .= '<input type="hidden" name="install_base" value="' . hs_h($paths['default_base']) . '">'
            . '<div class="hs-path-input"><span class="hs-path-prefix">' . hs_h($paths['prefix_label']) . '</span>'
            . '<input type="text" id="' . hs_h($slugId) . '" name="' . hs_h($slugName) . '" required pattern="[a-z0-9][a-z0-9-]*" placeholder="my-shop"></div>'
            . '<p class="hp-muted hs-path-hint">' . hs_h($t['installer_demo_path'] ?? '') . '</p>';
    } else {
        $html .= '<div class="hs-path-input hs-path-input-full"><span class="hs-path-prefix">public_html/</span>'
            . '<input type="text" name="install_base" value="' . hs_h($paths['default_base']) . '" pattern="[a-z0-9][a-z0-9/_-]*">'
            . '<span class="hs-path-sep">/</span>'
            . '<input type="text" id="' . hs_h($slugId) . '" name="' . hs_h($slugName) . '" required pattern="[a-z0-9][a-z0-9-]*" placeholder="my-shop"></div>'
            . '<p class="hp-muted hs-path-hint">' . hs_h($t['installer_path_hint'] ?? '') . '</p>';
    }
    return $html . '</div>';
}

function hs_installable_apps(): array
{
    $catalog = bh_ecosystem_catalog();
    $apps = [
        'empty' => ['slug' => 'empty', 'short' => 'Empty HTML', 'icon' => 'file-code', 'local' => ''],
        'php' => ['slug' => 'php', 'short' => 'PHP Starter', 'icon' => 'php', 'icon_brand' => true, 'local' => ''],
    ];
    foreach ($catalog as $slug => $meta) {
        // Hosting CMS is not installable as a client app (platform product / SEO landing only).
        if ((string) $slug === 'hosting') {
            continue;
        }
        $apps[$slug] = array_merge($meta, ['slug' => $slug, 'local' => hs_ecosystem_local_path($slug)]);
    }
    return $apps;
}

/** Ecosystem “planet” apps (Shop, Booking, …) — not empty/php starters. */
function hs_is_ecosystem_demo_app(string $slug): bool
{
    $slug = strtolower(trim($slug));
    if ($slug === '' || in_array($slug, ['empty', 'php', 'hosting'], true)) {
        return false;
    }
    $catalog = bh_ecosystem_catalog();

    return isset($catalog[$slug]);
}

/** @return list<string> */
function hs_ecosystem_demo_app_slugs(): array
{
    $out = [];
    foreach (array_keys(bh_ecosystem_catalog()) as $slug) {
        $s = (string) $slug;
        if (hs_is_ecosystem_demo_app($s)) {
            $out[] = $s;
        }
    }

    return $out;
}

/** Whether a package/template exists for this app on the server. */
function hs_app_package_available(string $slug): bool
{
    $slug = strtolower(trim($slug));
    if ($slug === '' || $slug === 'hosting') {
        return false;
    }
    if (in_array($slug, ['empty', 'php'], true)) {
        return true;
    }
    $root = dirname(__DIR__);
    if (is_dir($root . '/templates/' . $slug)) {
        return true;
    }
    if (is_file($root . '/packages/' . $slug . '.zip')) {
        return true;
    }
    if (hs_ecosystem_local_path($slug) !== '') {
        return true;
    }

    return false;
}

function hs_ecosystem_local_path(string $slug): string
{
    $publicRoot = dirname(__DIR__, 2);
    $map = [
        'shop' => $publicRoot . '/shop',
        'booking' => $publicRoot . '/booking',
        'auction' => $publicRoot . '/auction',
        'freelance' => $publicRoot . '/freelance',
        'pizza' => $publicRoot . '/pizza',
        'today' => $publicRoot . '/today',
        'gamehub' => $publicRoot . '/gamehub',
        'tavle' => $publicRoot . '/tavle',
        'faktura' => $publicRoot . '/faktura',
        'lending' => $publicRoot . '/lending',
        'hosting' => $publicRoot . '/hosting',
        'news' => $publicRoot . '/news',
        'wordpress' => $publicRoot . '/wordpress',
        '3d' => $publicRoot . '/3d',
        'ai' => $publicRoot . '/ai',
    ];
    $path = $map[$slug] ?? '';
    return (is_dir($path) && is_file($path . '/index.php')) ? $path : '';
}

function hs_app_template_vars(string $app, string $slug): array
{
    $catalog = bh_ecosystem_catalog();
    $meta = $catalog[$app] ?? [];
    return [
        '{{SITE_NAME}}' => $slug,
        '{{APP_SLUG}}' => $app,
        '{{APP_TITLE}}' => (string) ($meta['short'] ?? ucfirst($app)),
        '{{APP_COLOR}}' => (string) ($meta['color'] ?? '#059669'),
        '{{DEMO_URL}}' => hs_app_demo_url($app),
        '{{PANEL_URL}}' => hs_absolute_url(hs_panel_path('')),
        '{{YEAR}}' => date('Y'),
        '{{APP}}' => $app,
    ];
}

/**
 * @param array<string, mixed> $options clean_demo?:bool
 * @return array{ok:bool,error?:string,path?:string,install_base?:string,path_label?:string,app?:string,clean_demo?:bool,setup_url?:string}
 */
function hs_install_site(
    array $user,
    string $slug,
    string $title,
    string $app,
    string $installBase = '',
    string $mode = 'folder',
    array $options = []
): array {
    $mode = strtolower(trim($mode)) === 'root' ? 'root' : 'folder';
    $cleanDemo = !array_key_exists('clean_demo', $options) || !empty($options['clean_demo']);
    $slug = hs_slugify($slug);
    $installBase = hs_install_normalize_base($user, $installBase);
    // Free Welcome / sole slot / existing root so installer can proceed (silent).
    if (!hs_user_can_add_site($user) || $mode === 'root') {
        $free = hs_install_try_free_slot($user, [
            'allow_replace' => true,
            'mode' => $mode,
            'install_base' => $installBase,
        ]);
        if (!hs_user_can_add_site($user)) {
            if (empty($free['ok'])) {
                return ['ok' => false, 'error' => 'limit'];
            }
        }
        $fresh = hs_user_by_id((string) ($user['id'] ?? ''));
        if (is_array($fresh)) {
            $user = $fresh;
        }
    }

    $apps = hs_installable_apps();
    if (!isset($apps[$app])) {
        $app = 'empty';
    }
    if (!hs_app_package_available($app)) {
        return ['ok' => false, 'error' => 'package_missing'];
    }

    if ($mode === 'root') {
        if ($slug === '') {
            $slug = hs_slugify($app) !== '' ? hs_slugify($app) : 'site';
        }
        // Root reinstall: free any existing root site for this base (already attempted above).
        foreach (hs_sites_for_user((string) $user['id']) as $s) {
            $sBase = hs_install_normalize_base($user, (string) ($s['install_base'] ?? hs_install_default_base($user)));
            if ($sBase === $installBase && hs_install_site_at_root($user, $s)) {
                hs_delete_user_site($user, (string) ($s['id'] ?? ''));
            }
        }
        $relPath = $installBase;
        $sitePath = hs_public_path($relPath);
        if (!is_dir($sitePath) && !mkdir($sitePath, 0755, true)) {
            return ['ok' => false, 'error' => 'mkdir'];
        }
        // Clear previous root app entry points (domain folders under base are kept).
        if (is_file($sitePath . '/index.php') || is_file($sitePath . '/index.html') || is_file($sitePath . '/.bilohash-hosting.json')) {
            foreach (['index.html', 'index.php', 'install.php', '.bilohash-hosting.json'] as $f) {
                $fp = $sitePath . '/' . $f;
                if (is_file($fp)) {
                    @unlink($fp);
                }
            }
        }
    } else {
        if ($slug === '') {
            return ['ok' => false, 'error' => 'limit'];
        }
        foreach (hs_sites_for_user((string) $user['id']) as $s) {
            if (($s['slug'] ?? '') === $slug && (string) ($s['install_base'] ?? hs_install_default_base($user)) === $installBase) {
                return ['ok' => false, 'error' => 'slug_taken'];
            }
        }
        $relPath = trim($installBase . '/' . $slug, '/');
        $sitePath = hs_public_path($relPath);
        if (is_dir($sitePath)) {
            return ['ok' => false, 'error' => 'path_exists'];
        }
        if (!mkdir($sitePath, 0755, true)) {
            return ['ok' => false, 'error' => 'mkdir'];
        }
    }

    $written = hs_deploy_app_to_path($app, $sitePath, $user, $slug);
    if (!$written) {
        if ($mode !== 'root') {
            hs_recursive_remove($sitePath);
        }

        return ['ok' => false, 'error' => 'deploy'];
    }

    $siteMeta = [
        'id' => hs_new_id('s'),
        'slug' => $slug,
        'install_base' => $installBase,
        'title' => $title !== '' ? $title : $slug,
        'domain' => '',
        'app' => $app,
        'status' => 'active',
        'created_at' => gmdate('c'),
        'install_mode' => $mode,
        'at_root' => $mode === 'root',
    ];
    if (!hs_site_add_for_user((string) $user['id'], $siteMeta)) {
        return ['ok' => false, 'error' => 'save'];
    }
    if (function_exists('hs_panel_log')) {
        hs_panel_log((string) $user['id'], 'install_app', $app . ':' . $slug . ':' . $mode);
    }

    // Keep each domain on its own folder. Only map the active domain to this install path
    // when install is into a dedicated subfolder (not account root — root stays multi-domain safe).
    if (function_exists('hs_domain_assign_folder') && $mode === 'folder') {
        require_once __DIR__ . '/domain-workspace.php';
        require_once __DIR__ . '/user-settings.php';
        $settings = hs_user_settings_get((string) ($user['id'] ?? ''));
        $active = strtolower(trim((string) ($settings['active_domain'] ?? $settings['primary_domain'] ?? '')));
        if ($active !== '' && str_contains($active, '.')) {
            $sub = trim(str_replace('\\', '/', $relPath), '/');
            $uname = hs_user_public_rel_prefix($user);
            if (str_starts_with($sub, $uname . '/')) {
                $sub = substr($sub, strlen($uname) + 1);
            }
            if ($sub !== '' && $sub !== $uname) {
                hs_domain_assign_folder($user, $active, $sub);
            }
        }
    } elseif (function_exists('hs_domain_auto_bind_all_for_user')) {
        require_once __DIR__ . '/domain-workspace.php';
        // Ensure domain folders exist; do not force all domains onto account root
        hs_domain_auto_bind_all_for_user($user, true);
    }

    $setupUrl = '';
    if (is_file($sitePath . '/install.php')) {
        $setupUrl = 'install.php';
    } elseif (is_file($sitePath . '/admin/install.php')) {
        $setupUrl = 'admin/install.php';
    }

    return [
        'ok' => true,
        'path' => $sitePath,
        'install_base' => $installBase,
        'path_label' => 'public_html/' . $relPath . ($mode === 'root' ? '/' : ''),
        'app' => $app,
        'clean_demo' => $cleanDemo,
        'setup_url' => $setupUrl,
    ];
}

/** True when account root only has placeholder welcome content (safe to overwrite). */
function hs_install_root_is_placeholder(string $sitePath): bool
{
    $html = is_file($sitePath . '/index.html') ? (string) @file_get_contents($sitePath . '/index.html') : '';
    $php = is_file($sitePath . '/index.php') ? (string) @file_get_contents($sitePath . '/index.php') : '';
    if ($html !== '' && (str_contains($html, 'Your site is live') || str_contains($html, 'BILOHASH Hosting'))) {
        return true;
    }
    // Tiny stub left by bad deletes / probes
    if ($php !== '' && strlen($php) < 80 && !str_contains($php, 'wp-') && !str_contains($php, 'require')) {
        return true;
    }
    if ($html === '' && $php === '') {
        return true;
    }

    return false;
}

/**
 * @return array{ok:bool,error?:string,slug?:string}
 */
function hs_delete_user_site(array $user, string $siteId): array
{
    if ($siteId === '') {
        return ['ok' => false, 'error' => 'not_found'];
    }
    if (function_exists('hs_is_demo_panel_user') && hs_is_demo_panel_user($user)) {
        return ['ok' => false, 'error' => 'demo'];
    }
    $userId = (string) ($user['id'] ?? '');
    $site = hs_site_by_id_for_user($siteId, $userId);
    if ($site === null) {
        return ['ok' => false, 'error' => 'not_found'];
    }

    $base = hs_install_normalize_base($user, (string) ($site['install_base'] ?? hs_install_default_base($user)));
    $rel = hs_install_path_rel($user, $site);
    if (!hs_user_owns_public_rel($user, $rel)) {
        return ['ok' => false, 'error' => 'path_forbidden'];
    }

    if (hs_install_site_at_root($user, $site)) {
        // Remove root entry files only — never wipe other sites' subfolders under the same base.
        $basePath = hs_public_path($base);
        foreach (['index.html', 'index.php', 'install.php', '.bilohash-hosting.json', 'style.css', 'styles.css', 'app.css'] as $f) {
            $fp = $basePath . '/' . $f;
            if (is_file($fp)) {
                @unlink($fp);
            }
        }
        // Bridge / template extras that live next to index
        foreach (['assets', 'css', 'js', 'img', 'images', 'static'] as $dir) {
            $dp = $basePath . '/' . $dir;
            // Only remove if clearly not shared with other folder sites (heuristic: tiny or bridge meta)
            if (is_dir($dp) && is_file($basePath . '/.bilohash-hosting.json')) {
                hs_recursive_remove($dp);
            }
        }
    } else {
        // Folder site: never allow deleting the account root itself.
        if ($rel === $base || $rel === '' || $rel === hs_install_default_base($user)) {
            // Fall back: try slug folder under base even if path_rel was wrong historically.
            $slug = preg_replace('/[^a-z0-9_-]/i', '', (string) ($site['slug'] ?? '')) ?: '';
            if ($slug !== '' && $slug !== 'www') {
                $rel = trim($base . '/' . $slug, '/');
            } else {
                return ['ok' => false, 'error' => 'protected'];
            }
        }
        if (!hs_user_owns_public_rel($user, $rel) || $rel === $base) {
            return ['ok' => false, 'error' => 'protected'];
        }
        $sitePath = hs_public_path($rel);
        if (is_dir($sitePath)) {
            hs_recursive_remove($sitePath);
        }
    }

    if (!hs_site_delete_for_user($siteId, $userId)) {
        return ['ok' => false, 'error' => 'save'];
    }
    // Remember that welcome was removed so ensure_workspace will not re-add it.
    if (hs_site_is_auto_welcome($site)) {
        require_once __DIR__ . '/user-settings.php';
        hs_user_settings_save($userId, ['workspace_welcome_seeded' => true]);
    }
    if (function_exists('hs_panel_log')) {
        require_once __DIR__ . '/panel-features.php';
        hs_panel_log($userId, 'site_delete', (string) ($site['slug'] ?? $siteId));
    }

    return ['ok' => true, 'slug' => (string) ($site['slug'] ?? '')];
}

function hs_deploy_app_to_path(string $app, string $dest, array $user, string $slug): bool
{
    $root = dirname(__DIR__);
    $vars = hs_app_template_vars($app, $slug);
    $local = hs_ecosystem_local_path($app);

    if ($app === 'empty' || $app === 'php') {
        return hs_copy_template($root . '/templates/' . $app, $dest, $vars);
    }

    $custom = $root . '/templates/' . $app;
    if (is_dir($custom)) {
        return hs_copy_template($custom, $dest, $vars);
    }

    if ($local !== '') {
        return hs_copy_ecosystem_bridge($local, $dest, $app, $slug);
    }

    return hs_copy_template($root . '/templates/app-starter', $dest, $vars);
}

function hs_copy_template(string $src, string $dest, array $vars): bool
{
    if (!is_dir($src)) {
        return false;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $rel = substr($item->getPathname(), strlen($src) + 1);
        $target = $dest . '/' . $rel;
        if ($item->isDir()) {
            if (!is_dir($target) && !mkdir($target, 0755, true)) {
                return false;
            }
        } else {
            $content = file_get_contents($item->getPathname());
            if ($content === false) {
                return false;
            }
            foreach ($vars as $k => $v) {
                $content = str_replace($k, (string) $v, $content);
            }
            if (file_put_contents($target, $content) === false) {
                return false;
            }
        }
    }
    return true;
}

function hs_copy_ecosystem_bridge(string $ecosystemPath, string $dest, string $app, string $slug): bool
{
    $bridge = dirname(__DIR__) . '/templates/ecosystem-bridge';
    if (!hs_copy_template($bridge, $dest, [
        '{{APP_SLUG}}' => $app,
        '{{SITE_NAME}}' => $slug,
        '{{ECOSYSTEM_PATH}}' => $ecosystemPath,
    ])) {
        return false;
    }
    $meta = [
        'app' => $app,
        'installed_at' => gmdate('c'),
        'mode' => 'bridge',
        'ecosystem_path' => $ecosystemPath,
    ];
    return file_put_contents($dest . '/.bilohash-hosting.json', json_encode($meta, JSON_PRETTY_PRINT)) !== false;
}

function hs_recursive_remove(string $path): void
{
    if (!is_dir($path)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }
    @rmdir($path);
}