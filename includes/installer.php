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

/** Whether site files are served from install_base root (landing, index.html). */
function hs_install_site_at_root(array $user, array $site): bool
{
    $app = (string) ($site['app'] ?? '');
    $slug = preg_replace('/[^a-z0-9_-]/i', '', (string) ($site['slug'] ?? '')) ?: '';
    if ($app === 'landing') {
        return true;
    }
    if ($slug === 'www' || $slug === '') {
        return true;
    }
    $base = hs_install_normalize_base($user, (string) ($site['install_base'] ?? hs_install_default_base($user)));

    return is_file(hs_public_path($base . '/index.html'));
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
    $candidates[] = trim($defaultBase . '/welcome', '/');

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
    if (function_exists('hs_user_settings_get')) {
        $settings = hs_user_settings_get((string) ($user['id'] ?? ''));
        $domain = trim((string) ($site['domain'] ?? $settings['active_domain'] ?? $settings['primary_domain'] ?? ''));
        if ($domain !== '' && !str_contains($domain, '/')) {
            return 'https://' . preg_replace('#^https?://#i', '', $domain) . '/';
        }
    }
    $rel = hs_install_path_rel($user, $site);

    return rtrim((string) $site_url, '/') . '/' . HS_PUBLIC_HTML . '/' . $rel . '/';
}

/** @return array{default_base:string,locked:bool,prefix_label:string} */
function hs_welcome_index_html(): string
{
    return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Welcome</title><style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8fafc;color:#0f172a}main{text-align:center;padding:2rem}h1{font-size:1.75rem}</style></head><body><main><h1>Your site is live</h1><p>BILOHASH Hosting — install a CMS from the panel.</p></main></body></html>';
}

/** Ensure account folder + at least one welcome site record (tenant isolation). */
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
        ]);
        $userSites = hs_sites_for_user($userId);
    }

    foreach ($userSites as $site) {
        $rel = hs_install_path_rel($user, $site);
        if (!hs_user_owns_public_rel($user, $rel)) {
            continue;
        }
        $sitePath = hs_public_path($rel);
        if (!is_dir($sitePath)) {
            mkdir($sitePath, 0755, true);
        }
        if (($site['app'] ?? '') === 'empty' && !is_file($sitePath . '/index.php')) {
            $index = $sitePath . '/index.html';
            if (!is_file($index)) {
                file_put_contents($index, hs_welcome_index_html());
            }
        }
    }
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
        $apps[$slug] = array_merge($meta, ['slug' => $slug, 'local' => hs_ecosystem_local_path($slug)]);
    }
    return $apps;
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

function hs_install_site(array $user, string $slug, string $title, string $app, string $installBase = ''): array
{
    $slug = hs_slugify($slug);
    $installBase = hs_install_normalize_base($user, $installBase);
    if ($slug === '' || !hs_user_can_add_site($user)) {
        return ['ok' => false, 'error' => 'limit'];
    }
    foreach (hs_sites_for_user((string) $user['id']) as $s) {
        if (($s['slug'] ?? '') === $slug && (string) ($s['install_base'] ?? hs_install_default_base($user)) === $installBase) {
            return ['ok' => false, 'error' => 'slug_taken'];
        }
    }

    $apps = hs_installable_apps();
    if (!isset($apps[$app])) {
        $app = 'empty';
    }

    $relPath = trim($installBase . '/' . $slug, '/');
    $sitePath = hs_public_path($relPath);
    if (is_dir($sitePath)) {
        return ['ok' => false, 'error' => 'path_exists'];
    }
    if (!mkdir($sitePath, 0755, true)) {
        return ['ok' => false, 'error' => 'mkdir'];
    }

    $written = hs_deploy_app_to_path($app, $sitePath, $user, $slug);
    if (!$written) {
        hs_recursive_remove($sitePath);
        return ['ok' => false, 'error' => 'deploy'];
    }

    if (!hs_site_add_for_user((string) $user['id'], [
        'id' => hs_new_id('s'),
        'slug' => $slug,
        'install_base' => $installBase,
        'title' => $title !== '' ? $title : $slug,
        'domain' => '',
        'app' => $app,
        'status' => 'active',
        'created_at' => gmdate('c'),
    ])) {
        return ['ok' => false, 'error' => 'save'];
    }
    if (function_exists('hs_panel_log')) {
        hs_panel_log((string) $user['id'], 'install_app', $app . ':' . $slug);
    }
    return ['ok' => true, 'path' => $sitePath, 'install_base' => $installBase, 'path_label' => 'public_html/' . $relPath];
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

    $base = hs_install_default_base($user);
    $rel = hs_install_path_rel($user, $site);
    if (!hs_user_owns_public_rel($user, $rel)) {
        return ['ok' => false, 'error' => 'path_forbidden'];
    }

    if (hs_install_site_at_root($user, $site)) {
        $indexFile = hs_public_path($base) . '/index.html';
        if (is_file($indexFile)) {
            @unlink($indexFile);
        }
    } else {
        if ($rel === $base || $rel === '') {
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