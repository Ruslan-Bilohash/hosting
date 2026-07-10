<?php
declare(strict_types=1);

require_once __DIR__ . '/installer.php';
require_once __DIR__ . '/user-settings.php';

/** @return array<string, array<string, mixed>> */
function hs_wordpress_installs(string $userId): array
{
    $settings = hs_user_settings_get($userId);
    $installs = $settings['wp_installs'] ?? [];
    return is_array($installs) ? $installs : [];
}

/** @return list<array<string, mixed>> */
function hs_wordpress_sites_for_user(array $user): array
{
    $userId = (string) ($user['id'] ?? '');
    $installs = hs_wordpress_installs($userId);
    $out = [];
    foreach (hs_sites_for_user($userId) as $site) {
        if (($site['app'] ?? '') !== 'wordpress') {
            continue;
        }
        $id = (string) ($site['id'] ?? '');
        $meta = is_array($installs[$id] ?? null) ? $installs[$id] : [];
        $out[] = array_merge($site, $meta);
    }
    return $out;
}

function hs_wordpress_fetch_url(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_USERAGENT => 'BILOHASH-Hosting-CMS/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code < 200 || $code >= 300) {
            return null;
        }
        return $body;
    }
    $ctx = stream_context_create(['http' => ['timeout' => 120, 'user_agent' => 'BILOHASH-Hosting-CMS/1.0']]);
    $body = @file_get_contents($url, false, $ctx);
    return $body !== false ? $body : null;
}

/** @return array<string, string> slug => bootstrap file */
function hs_wordpress_bundled_plugins(): array
{
    return [
        'bilohash-ai-chat-consultant' => 'bilohash-ai-chat-consultant.php',
        'bilohash-smart-popups' => 'bilohash-smart-popups.php',
        'bilohash-booking' => 'bilohash-booking.php',
        'callback-request-by-bilohash' => 'callback-request-by-bilohash.php',
        'Redirect-Call-Widgets' => 'redirect-call-widgets.php',
    ];
}

function hs_wordpress_plugins_library(): ?string
{
    $candidates = [
        dirname(__DIR__, 2) . '/wordpress/wp-content/plugins',
        dirname(__DIR__) . '/wordpress-plugins',
    ];
    foreach ($candidates as $path) {
        if (is_dir($path)) {
            $real = realpath($path);
            return $real !== false ? $real : $path;
        }
    }
    return null;
}

/** @return array{ok:bool,error?:string,copied?:list<string>} */
function hs_wordpress_copy_bundled_plugins(string $sitePath): array
{
    $lib = hs_wordpress_plugins_library();
    if ($lib === null) {
        return ['ok' => false, 'error' => 'plugins_library'];
    }
    $dest = $sitePath . '/wp-content/plugins';
    if (!is_dir($dest) && !mkdir($dest, 0755, true)) {
        return ['ok' => false, 'error' => 'plugins_mkdir'];
    }
    $copied = [];
    foreach (hs_wordpress_bundled_plugins() as $slug => $bootstrap) {
        $src = $lib . '/' . $slug;
        $target = $dest . '/' . $slug;
        if (!is_dir($src) || !is_file($src . '/' . $bootstrap)) {
            return ['ok' => false, 'error' => 'plugin_missing:' . $slug];
        }
        if (is_dir($target)) {
            hs_recursive_remove($target);
        }
        if (!function_exists('hs_recursive_copy')) {
            require_once __DIR__ . '/panel-features.php';
        }
        if (!hs_recursive_copy($src, $target)) {
            return ['ok' => false, 'error' => 'plugin_copy:' . $slug];
        }
        $copied[] = $slug;
    }
    return ['ok' => true, 'copied' => $copied];
}

/** @return array{ok:bool,activated?:list<string>,error?:string} */
function hs_wordpress_activate_bundled_plugins(string $sitePath): array
{
    if (!defined('ABSPATH')) {
        return ['ok' => false, 'error' => 'abspath'];
    }
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $activated = [];
    foreach (hs_wordpress_bundled_plugins() as $slug => $bootstrap) {
        $pluginFile = $slug . '/' . $bootstrap;
        $full = WP_PLUGIN_DIR . '/' . $pluginFile;
        if (!is_file($full)) {
            continue;
        }
        $result = activate_plugin($pluginFile, '', false, true);
        if (!is_wp_error($result)) {
            $activated[] = $slug;
        }
    }
    return ['ok' => true, 'activated' => $activated];
}

function hs_wordpress_fetch_salts(): string
{
    $raw = hs_wordpress_fetch_url('https://api.wordpress.org/secret-key/1.1/salt/');
    if ($raw !== null && strpos($raw, 'AUTH_KEY') !== false) {
        return $raw;
    }
    $keys = ['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'];
    $lines = '';
    foreach ($keys as $key) {
        $lines .= "define('{$key}', '" . bin2hex(random_bytes(32)) . "');\n";
    }
    return $lines;
}

/** @return array{ok:bool,error?:string} */
function hs_wordpress_extract_zip(string $zipPath, string $dest): array
{
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'error' => 'zip_missing'];
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['ok' => false, 'error' => 'zip_open'];
    }
    $tmp = $dest . '/_wp_extract_' . bin2hex(random_bytes(4));
    if (!mkdir($tmp, 0755, true)) {
        $zip->close();
        return ['ok' => false, 'error' => 'mkdir'];
    }
    if (!$zip->extractTo($tmp)) {
        $zip->close();
        hs_recursive_remove($tmp);
        return ['ok' => false, 'error' => 'zip_extract'];
    }
    $zip->close();
    $inner = $tmp . '/wordpress';
    if (!is_dir($inner)) {
        hs_recursive_remove($tmp);
        return ['ok' => false, 'error' => 'zip_layout'];
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($inner, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $rel = substr($item->getPathname(), strlen($inner) + 1);
        $target = $dest . '/' . $rel;
        if ($item->isDir()) {
            if (!is_dir($target) && !mkdir($target, 0755, true)) {
                hs_recursive_remove($tmp);
                return ['ok' => false, 'error' => 'copy'];
            }
        } elseif (!copy($item->getPathname(), $target)) {
            hs_recursive_remove($tmp);
            return ['ok' => false, 'error' => 'copy'];
        }
    }
    hs_recursive_remove($tmp);
    return ['ok' => true];
}

/** @return array{ok:bool,error?:string} */
function hs_wordpress_deploy_core(string $sitePath): array
{
    $zipBody = hs_wordpress_fetch_url('https://wordpress.org/latest.zip');
    if ($zipBody === null) {
        return ['ok' => false, 'error' => 'download'];
    }
    $zipFile = $sitePath . '/_wordpress-latest.zip';
    if (file_put_contents($zipFile, $zipBody) === false) {
        return ['ok' => false, 'error' => 'download'];
    }
    $res = hs_wordpress_extract_zip($zipFile, $sitePath);
    @unlink($zipFile);
    return $res;
}

function hs_wordpress_write_config(string $sitePath, array $db, string $tablePrefix = 'wp_'): bool
{
    $prefix = preg_replace('/[^a-z0-9_]/i', '', $tablePrefix) ?: 'wp_';
    if (!str_ends_with($prefix, '_')) {
        $prefix .= '_';
    }
    $salts = hs_wordpress_fetch_salts();
    $php = "<?php\n"
        . "define('DB_NAME', " . var_export((string) ($db['name'] ?? ''), true) . ");\n"
        . "define('DB_USER', " . var_export((string) ($db['user'] ?? ''), true) . ");\n"
        . "define('DB_PASSWORD', " . var_export((string) ($db['password'] ?? ''), true) . ");\n"
        . "define('DB_HOST', " . var_export((string) ($db['host'] ?? 'localhost'), true) . ");\n"
        . "define('DB_CHARSET', 'utf8mb4');\n"
        . "define('DB_COLLATE', '');\n"
        . $salts
        . "\$table_prefix = " . var_export($prefix, true) . ";\n"
        . "define('WP_DEBUG', false);\n"
        . "if (!defined('ABSPATH')) {\n    define('ABSPATH', __DIR__ . '/');\n}\n"
        . "require_once ABSPATH . 'wp-settings.php';\n";
    return file_put_contents($sitePath . '/wp-config.php', $php, LOCK_EX) !== false;
}

/** @return array{ok:bool,error?:string} */
function hs_wordpress_run_setup(string $sitePath, string $siteUrl, string $title, string $adminUser, string $adminEmail, string $adminPass): array
{
    if (!is_file($sitePath . '/wp-config.php') || !is_file($sitePath . '/wp-load.php')) {
        return ['ok' => false, 'error' => 'files'];
    }
    $prevCwd = getcwd() ?: $sitePath;
    chdir($sitePath);
    $parsed = parse_url($siteUrl);
    $_SERVER['HTTP_HOST'] = (string) ($parsed['host'] ?? 'localhost');
    $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
    $_SERVER['REQUEST_URI'] = (string) (($parsed['path'] ?? '/') ?: '/');
    $_SERVER['SERVER_PORT'] = (($parsed['scheme'] ?? 'https') === 'https') ? '443' : '80';
    $_SERVER['HTTPS'] = (($parsed['scheme'] ?? '') === 'https') ? 'on' : '';

    try {
        if (!defined('WP_INSTALLING')) {
            define('WP_INSTALLING', true);
        }
        require_once $sitePath . '/wp-load.php';
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        require_once ABSPATH . 'wp-includes/pluggable.php';
        if (function_exists('wp_installing') && wp_installing()) {
            wp_install($title, $adminUser, $adminPass, $adminEmail, true, '', 'en_US');
            $cleanUrl = rtrim($siteUrl, '/');
            update_option('siteurl', $cleanUrl);
            update_option('home', $cleanUrl);
            update_option('blogname', $title);
            hs_wordpress_activate_bundled_plugins($sitePath);
        }
    } catch (Throwable $e) {
        chdir($prevCwd);
        return ['ok' => false, 'error' => $e->getMessage()];
    }
    chdir($prevCwd);
    return ['ok' => true];
}

/** @return array{ok:bool,entry?:array<string,mixed>,site_id?:string,error?:string} */
function hs_wordpress_install(array $user, string $slug, string $title, string $adminUser, string $adminEmail, string $adminPass, string $installBase = ''): array
{
    $slug = hs_slugify($slug);
    $installBase = hs_install_normalize_base($user, $installBase);
    $title = trim($title) !== '' ? trim($title) : $slug;
    $adminUser = preg_replace('/[^a-z0-9_-]/i', '', trim($adminUser)) ?: 'admin';
    $adminEmail = trim($adminEmail);
    $adminPass = (string) $adminPass;

    if ($slug === '' || !hs_user_can_add_site($user)) {
        return ['ok' => false, 'error' => 'limit'];
    }
    if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'email'];
    }
    if (strlen($adminPass) < 8) {
        return ['ok' => false, 'error' => 'password'];
    }
    foreach (hs_sites_for_user((string) $user['id']) as $s) {
        if (($s['slug'] ?? '') === $slug && (string) ($s['install_base'] ?? hs_install_default_base($user)) === $installBase) {
            return ['ok' => false, 'error' => 'slug_taken'];
        }
    }

    $username = (string) ($user['username'] ?? 'user');
    $userId = (string) ($user['id'] ?? '');
    $relPath = trim($installBase . '/' . $slug, '/');
    $sitePath = hs_public_path($relPath);
    if (is_dir($sitePath)) {
        return ['ok' => false, 'error' => 'path_exists'];
    }
    if (!mkdir($sitePath, 0755, true)) {
        return ['ok' => false, 'error' => 'mkdir'];
    }

    $deploy = hs_wordpress_deploy_core($sitePath);
    if (!$deploy['ok']) {
        hs_recursive_remove($sitePath);
        return ['ok' => false, 'error' => $deploy['error'] ?? 'download'];
    }

    $plugins = hs_wordpress_copy_bundled_plugins($sitePath);
    if (!$plugins['ok']) {
        hs_recursive_remove($sitePath);
        return ['ok' => false, 'error' => $plugins['error'] ?? 'plugins'];
    }

    $dbRes = hs_create_database($userId, $username, $user);
    if (!$dbRes['ok'] || empty($dbRes['entry'])) {
        hs_recursive_remove($sitePath);
        return ['ok' => false, 'error' => $dbRes['error'] ?? 'db'];
    }
    $db = $dbRes['entry'];

    $tablePrefix = (string) ($db['table_prefix'] ?? 'wp_');
    if (!hs_wordpress_write_config($sitePath, $db, $tablePrefix)) {
        hs_recursive_remove($sitePath);
        return ['ok' => false, 'error' => 'config'];
    }

    $siteUrl = hs_public_url($username, $slug, $installBase);
    $setup = hs_wordpress_run_setup($sitePath, $siteUrl, $title, $adminUser, $adminEmail, $adminPass);
    if (!$setup['ok']) {
        hs_recursive_remove($sitePath);
        return ['ok' => false, 'error' => $setup['error'] ?? 'setup'];
    }

    $siteId = hs_new_id('s');
    if (!hs_site_add_for_user($userId, [
        'id' => $siteId,
        'slug' => $slug,
        'install_base' => $installBase,
        'title' => $title,
        'domain' => '',
        'app' => 'wordpress',
        'status' => 'active',
        'created_at' => gmdate('c'),
    ])) {
        hs_recursive_remove($sitePath);
        return ['ok' => false, 'error' => 'save'];
    }

    $installs = hs_wordpress_installs($userId);
    $installs[$siteId] = [
        'site_id' => $siteId,
        'slug' => $slug,
        'title' => $title,
        'db_id' => (string) ($db['id'] ?? ''),
        'db_name' => (string) ($db['name'] ?? ''),
        'db_user' => (string) ($db['user'] ?? ''),
        'db_host' => (string) ($db['host'] ?? 'localhost'),
        'admin_user' => $adminUser,
        'admin_email' => $adminEmail,
        'auto_update' => true,
        'installed_at' => gmdate('c'),
        'site_url' => $siteUrl,
        'plugins' => $plugins['copied'] ?? array_keys(hs_wordpress_bundled_plugins()),
    ];
    hs_user_settings_save($userId, ['wp_installs' => $installs]);

    if (function_exists('hs_panel_log')) {
        require_once __DIR__ . '/panel-features.php';
        hs_panel_log($userId, 'wp_install', $slug);
    }

    return ['ok' => true, 'site_id' => $siteId, 'entry' => $installs[$siteId]];
}

/** @param array{title?:string,admin_email?:string,auto_update?:bool} $changes */
function hs_wordpress_update_security(string $userId, array $user, string $siteId, array $changes): array
{
    $installs = hs_wordpress_installs($userId);
    if ($siteId === '' || !isset($installs[$siteId])) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $meta = $installs[$siteId];
    $siteRecord = hs_site_by_id_for_user($siteId, $userId);
    if ($siteRecord === null) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $username = (string) ($user['username'] ?? 'user');
    $slug = (string) ($meta['slug'] ?? '');
    $installBase = (string) ($siteRecord['install_base'] ?? hs_install_default_base($user));
    $relPath = trim($installBase . '/' . $slug, '/');
    if (!hs_user_owns_public_rel($user, $relPath)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $sitePath = hs_public_path($relPath);

    if (isset($changes['title'])) {
        $title = trim((string) $changes['title']);
        if ($title !== '') {
            $meta['title'] = $title;
            hs_site_update_for_user($siteId, $userId, static function (array $site) use ($title): array {
                $site['title'] = $title;
                return $site;
            });
            hs_wordpress_update_live_option($sitePath, $meta, 'blogname', $title);
        }
    }
    if (isset($changes['admin_email'])) {
        $email = trim((string) $changes['admin_email']);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $meta['admin_email'] = $email;
            hs_wordpress_update_live_user_email($sitePath, $meta, $email);
        }
    }
    if (array_key_exists('auto_update', $changes)) {
        $meta['auto_update'] = (bool) $changes['auto_update'];
    }

    $installs[$siteId] = $meta;
    hs_user_settings_save($userId, ['wp_installs' => $installs]);
    return ['ok' => true];
}

function hs_wordpress_pdo_for_install(array $meta): ?PDO
{
    $dbName = (string) ($meta['db_name'] ?? '');
    $dbUser = (string) ($meta['db_user'] ?? '');
    $dbHost = (string) ($meta['db_host'] ?? 'localhost');
    $settings = hs_user_settings_get((string) ($meta['user_id'] ?? ''));
    $pass = '';
    foreach (is_array($settings['databases'] ?? null) ? $settings['databases'] : [] as $db) {
        if (($db['name'] ?? '') === $dbName) {
            $pass = (string) ($db['password'] ?? '');
            break;
        }
    }
    if ($dbName === '' || $dbUser === '' || $pass === '') {
        return null;
    }
    try {
        return new PDO(
            'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
            $dbUser,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (Throwable) {
        return null;
    }
}

function hs_wordpress_table_prefix(string $sitePath): string
{
    $cfg = $sitePath . '/wp-config.php';
    if (!is_readable($cfg)) {
        return 'wp_';
    }
    $content = file_get_contents($cfg) ?: '';
    if (preg_match('/\$table_prefix\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $m)) {
        return (string) $m[1];
    }
    return 'wp_';
}

function hs_wordpress_quote_ident(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function hs_wordpress_update_live_option(string $sitePath, array $meta, string $option, string $value): void
{
    $pdo = hs_wordpress_pdo_for_site($sitePath, $meta);
    if (!$pdo instanceof PDO) {
        return;
    }
    $prefix = hs_wordpress_table_prefix($sitePath);
    $stmt = $pdo->prepare('UPDATE ' . hs_wordpress_quote_ident($prefix . 'options') . ' SET option_value = ? WHERE option_name = ?');
    $stmt->execute([$value, $option]);
}

function hs_wordpress_pdo_for_site(string $sitePath, array $meta): ?PDO
{
    $dbName = (string) ($meta['db_name'] ?? '');
    $dbUser = (string) ($meta['db_user'] ?? '');
    $dbHost = (string) ($meta['db_host'] ?? 'localhost');
    $pass = hs_wordpress_db_password($meta);
    if ($dbName === '' || $dbUser === '' || $pass === '') {
        return null;
    }
    try {
        return new PDO(
            'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
            $dbUser,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (Throwable) {
        return null;
    }
}

function hs_wordpress_db_password(array $meta): string
{
    foreach (hs_users() as $u) {
        $settings = hs_user_settings_get((string) ($u['id'] ?? ''));
        foreach (is_array($settings['databases'] ?? null) ? $settings['databases'] : [] as $db) {
            if (($db['name'] ?? '') === ($meta['db_name'] ?? '')) {
                return (string) ($db['password'] ?? '');
            }
        }
    }
    return '';
}

function hs_wordpress_update_live_user_email(string $sitePath, array $meta, string $email): void
{
    $pdo = hs_wordpress_pdo_for_site($sitePath, $meta);
    if (!$pdo instanceof PDO) {
        return;
    }
    $prefix = hs_wordpress_table_prefix($sitePath);
    $admin = (string) ($meta['admin_user'] ?? 'admin');
    $stmt = $pdo->prepare('UPDATE ' . hs_wordpress_quote_ident($prefix . 'users') . ' SET user_email = ? WHERE user_login = ?');
    $stmt->execute([$email, $admin]);
}