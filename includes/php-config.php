<?php
declare(strict_types=1);

/** @return list<array{minor:string,patch:string,recommended:bool}> */
function hs_php_available_versions(): array
{
    return [
        ['minor' => '8.5', 'patch' => '8.5.8', 'recommended' => true],
        ['minor' => '8.4', 'patch' => '8.4.23', 'recommended' => false],
        ['minor' => '8.3', 'patch' => '8.3.32', 'recommended' => false],
        ['minor' => '8.2', 'patch' => '8.2.32', 'recommended' => false],
    ];
}

function hs_php_normalize_version(string $version): string
{
    if (preg_match('/^(8\.[2345])/', trim($version), $m) === 1) {
        return $m[1];
    }
    return '8.2';
}

function hs_php_is_valid_version(string $version): bool
{
    $minor = hs_php_normalize_version($version);
    foreach (hs_php_available_versions() as $row) {
        if ($row['minor'] === $minor) {
            return true;
        }
    }
    return false;
}

/** @return array<string, array{label:string,ini:string,builtin?:bool,group:string}> */
function hs_php_extension_catalog(): array
{
    return [
        'bcmath' => ['label' => 'BCMath', 'ini' => 'bcmath', 'builtin' => true, 'group' => 'core'],
        'ctype' => ['label' => 'Ctype', 'ini' => 'ctype', 'builtin' => true, 'group' => 'core'],
        'curl' => ['label' => 'cURL', 'ini' => 'curl', 'builtin' => true, 'group' => 'core'],
        'dom' => ['label' => 'DOM', 'ini' => 'dom', 'builtin' => true, 'group' => 'core'],
        'exif' => ['label' => 'Exif', 'ini' => 'exif', 'group' => 'media'],
        'fileinfo' => ['label' => 'Fileinfo', 'ini' => 'fileinfo', 'builtin' => true, 'group' => 'core'],
        'filter' => ['label' => 'Filter', 'ini' => 'filter', 'builtin' => true, 'group' => 'core'],
        'ftp' => ['label' => 'FTP', 'ini' => 'ftp', 'group' => 'network'],
        'gd' => ['label' => 'GD', 'ini' => 'gd', 'group' => 'media'],
        'gettext' => ['label' => 'Gettext', 'ini' => 'gettext', 'group' => 'i18n'],
        'iconv' => ['label' => 'Iconv', 'ini' => 'iconv', 'builtin' => true, 'group' => 'i18n'],
        'imap' => ['label' => 'IMAP', 'ini' => 'imap', 'group' => 'mail'],
        'intl' => ['label' => 'Intl', 'ini' => 'intl', 'group' => 'i18n'],
        'mbstring' => ['label' => 'Mbstring', 'ini' => 'mbstring', 'builtin' => true, 'group' => 'i18n'],
        'mysqli' => ['label' => 'MySQLi', 'ini' => 'mysqli', 'group' => 'database'],
        'pdo_mysql' => ['label' => 'PDO MySQL', 'ini' => 'pdo_mysql', 'group' => 'database'],
        'openssl' => ['label' => 'OpenSSL', 'ini' => 'openssl', 'builtin' => true, 'group' => 'crypto'],
        'soap' => ['label' => 'SOAP', 'ini' => 'soap', 'group' => 'network'],
        'sockets' => ['label' => 'Sockets', 'ini' => 'sockets', 'group' => 'network'],
        'sodium' => ['label' => 'Sodium', 'ini' => 'sodium', 'builtin' => true, 'group' => 'crypto'],
        'tidy' => ['label' => 'Tidy', 'ini' => 'tidy', 'group' => 'xml'],
        'xml' => ['label' => 'XML', 'ini' => 'xml', 'builtin' => true, 'group' => 'xml'],
        'xmlreader' => ['label' => 'XMLReader', 'ini' => 'xmlreader', 'builtin' => true, 'group' => 'xml'],
        'xmlwriter' => ['label' => 'XMLWriter', 'ini' => 'xmlwriter', 'builtin' => true, 'group' => 'xml'],
        'zip' => ['label' => 'Zip', 'ini' => 'zip', 'group' => 'archive'],
        'imagick' => ['label' => 'Imagick', 'ini' => 'imagick', 'group' => 'media'],
    ];
}

/** @return list<string> */
function hs_php_extension_keys(): array
{
    return array_keys(hs_php_extension_catalog());
}

/** @return array<string, bool> */
function hs_php_default_extensions(): array
{
    $out = [];
    foreach (hs_php_extension_catalog() as $key => $meta) {
        $out[$key] = $key !== 'imagick';
    }
    return $out;
}

/** @return array<string, bool> */
function hs_php_extensions_enabled(array $settings): array
{
    $defaults = hs_php_default_extensions();
    $saved = is_array($settings['php_extensions'] ?? null) ? $settings['php_extensions'] : [];
    foreach ($defaults as $key => $defaultOn) {
        if (array_key_exists($key, $saved)) {
            $defaults[$key] = !empty($saved[$key]);
        }
    }
    return $defaults;
}

/** @return list<string> */
function hs_php_managed_directives(): array
{
    return [
        'memory_limit',
        'max_execution_time',
        'max_input_time',
        'post_max_size',
        'upload_max_filesize',
        'max_input_vars',
        'max_file_uploads',
        'display_errors',
        'log_errors',
        'allow_url_fopen',
        'allow_url_include',
        'short_open_tag',
        'default_charset',
        'error_reporting',
        'session.gc_maxlifetime',
        'date.timezone',
    ];
}

/** @return array<string, array{label_key:string,type:string,default:string,options?:list<string>}> */
function hs_php_parameter_fields(): array
{
    return [
        'memory_limit' => ['label_key' => 'php_memory', 'type' => 'size', 'default' => '256M'],
        'max_execution_time' => ['label_key' => 'php_max_exec', 'type' => 'int', 'default' => '120'],
        'max_input_time' => ['label_key' => 'php_max_input_time', 'type' => 'int', 'default' => '120'],
        'upload_max_filesize' => ['label_key' => 'php_upload', 'type' => 'size', 'default' => '64M'],
        'post_max_size' => ['label_key' => 'php_post', 'type' => 'size', 'default' => '64M'],
        'max_input_vars' => ['label_key' => 'php_vars', 'type' => 'int', 'default' => '3000'],
        'max_file_uploads' => ['label_key' => 'php_max_file_uploads', 'type' => 'int', 'default' => '20'],
        'php_timezone' => ['label_key' => 'php_timezone', 'type' => 'timezone', 'default' => 'Europe/Kyiv'],
        'default_charset' => ['label_key' => 'php_default_charset', 'type' => 'text', 'default' => 'UTF-8'],
        'error_reporting' => ['label_key' => 'php_error_reporting', 'type' => 'select', 'default' => 'E_ALL', 'options' => ['E_ALL', 'E_ALL & ~E_NOTICE', 'E_ALL & ~E_DEPRECATED', 'E_ERROR']],
        'display_errors' => ['label_key' => 'php_display_errors', 'type' => 'bool', 'default' => '0'],
        'allow_url_fopen' => ['label_key' => 'php_allow_url_fopen', 'type' => 'bool', 'default' => '1'],
        'allow_url_include' => ['label_key' => 'php_allow_url_include', 'type' => 'bool', 'default' => '0'],
        'short_open_tag' => ['label_key' => 'php_short_open_tag', 'type' => 'bool', 'default' => '0'],
        'session_gc_maxlifetime' => ['label_key' => 'php_session_gc', 'type' => 'int', 'default' => '1440'],
    ];
}

/** @return array<string, string> */
function hs_php_live_directives(): array
{
    $out = [];
    foreach (hs_php_managed_directives() as $key) {
        $val = ini_get($key);
        $out[$key] = $val === false ? '' : (string) $val;
    }
    return $out;
}

function hs_php_user_ini_path(string $username): string
{
    return hs_public_path($username) . '/php.ini';
}

function hs_php_legacy_user_ini_path(string $username): string
{
    return hs_public_path($username) . '/.user.ini';
}

/** Rename or remove legacy .user.ini after switch to php.ini. */
function hs_php_migrate_ini_file(string $username): void
{
    $legacy = hs_php_legacy_user_ini_path($username);
    $current = hs_php_user_ini_path($username);
    if (!is_file($legacy)) {
        return;
    }
    if (!is_file($current)) {
        @rename($legacy, $current);
        return;
    }
    @unlink($legacy);
}

function hs_php_probe_script_path(string $username): string
{
    return hs_public_path($username) . '/hs-php-probe.php';
}

function hs_php_version_ea_handler(string $version): string
{
    $v = preg_replace('/[^0-9]/', '', hs_php_normalize_version($version)) ?: '82';
    return 'ea-php' . $v;
}

function hs_php_htaccess_block(string $version): string
{
    $minor = hs_php_normalize_version($version);
    $handler = hs_php_version_ea_handler($minor);
    return "# Hosting CMS — PHP {$minor}\n"
        . "<IfModule mime_module>\n"
        . "  AddHandler application/x-httpd-{$handler} .php .php8 .phtml\n"
        . "</IfModule>\n";
}

/** @return array<string, string> */
function hs_php_parse_user_ini_file(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, ';') || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        if ($k !== '') {
            $out[$k] = $v;
        }
    }
    return $out;
}

function hs_php_probe_token(string $userId): string
{
    $settings = hs_user_settings_get($userId);
    $token = trim((string) ($settings['php_probe_token'] ?? ''));
    if ($token !== '') {
        return $token;
    }
    $token = bin2hex(random_bytes(16));
    hs_user_settings_save($userId, ['php_probe_token' => $token]);
    return $token;
}

function hs_php_onoff(string $value): string
{
    return $value === '1' || strtolower($value) === 'on' || $value === 'true' ? 'On' : 'Off';
}

function hs_php_build_user_ini(array $settings): string
{
    $version = hs_php_normalize_version((string) ($settings['php_version'] ?? '8.2'));
    $tz = trim((string) ($settings['php_timezone'] ?? 'Europe/Kyiv'));
    if ($tz === '' || !in_array($tz, timezone_identifiers_list(), true)) {
        $tz = 'UTC';
    }
    $lines = [
        '; Hosting CMS — PHP per-directory settings (php.ini)',
        '; https://www.php.net/manual/en/configuration.file.php',
        '; PHP version (panel): ' . $version . ' — also set via .htaccess AddHandler on this host',
        '; Changes on shared hosting may take up to 5 minutes to apply.',
        '',
        'memory_limit = ' . ($settings['memory_limit'] ?? '256M'),
        'max_execution_time = ' . (int) ($settings['max_execution_time'] ?? 120),
        'max_input_time = ' . (int) ($settings['max_input_time'] ?? 120),
        'post_max_size = ' . ($settings['post_max_size'] ?? '64M'),
        'upload_max_filesize = ' . ($settings['upload_max_filesize'] ?? '64M'),
        'max_input_vars = ' . (int) ($settings['max_input_vars'] ?? 3000),
        'max_file_uploads = ' . (int) ($settings['max_file_uploads'] ?? 20),
        'display_errors = ' . hs_php_onoff((string) ($settings['display_errors'] ?? '0')),
        'log_errors = On',
        'allow_url_fopen = ' . hs_php_onoff((string) ($settings['allow_url_fopen'] ?? '1')),
        'allow_url_include = ' . hs_php_onoff((string) ($settings['allow_url_include'] ?? '0')),
        'short_open_tag = ' . hs_php_onoff((string) ($settings['short_open_tag'] ?? '0')),
        'default_charset = ' . ($settings['default_charset'] ?? 'UTF-8'),
        'error_reporting = ' . ($settings['error_reporting'] ?? 'E_ALL'),
        'session.gc_maxlifetime = ' . (int) ($settings['session_gc_maxlifetime'] ?? 1440),
        'date.timezone = ' . $tz,
        '',
        '; PHP extensions',
    ];
    foreach (hs_php_extensions_enabled($settings) as $key => $on) {
        if (!$on) {
            continue;
        }
        $meta = hs_php_extension_catalog()[$key] ?? null;
        if ($meta === null || !empty($meta['builtin'])) {
            continue;
        }
        $lines[] = 'extension = ' . $meta['ini'];
    }
    return implode("\n", $lines) . "\n";
}

function hs_php_merge_htaccess(string $username, string $phpVersion): bool
{
    $base = hs_public_path($username);
    if (!is_dir($base)) {
        mkdir($base, 0755, true);
    }
    $file = $base . '/.htaccess';
    $existing = is_file($file) ? (file_get_contents($file) ?: '') : '';
    $existing = preg_replace(
        '/# Hosting CMS — PHP[^\n]*\n<IfModule mime_module>[\s\S]*?<\/IfModule>\n?/m',
        '',
        $existing
    ) ?? $existing;
    $block = hs_php_htaccess_block($phpVersion);
    $content = $block . (trim($existing) !== '' ? "\n" . ltrim($existing) : "Options -Indexes\n");
    return file_put_contents($file, $content, LOCK_EX) !== false;
}

function hs_php_write_probe(string $username, string $userId): bool
{
    $dir = hs_public_path($username);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return false;
    }
    $token = hs_php_probe_token($userId);
    $keys = hs_php_managed_directives();
    $extKeys = hs_php_extension_keys();
    $php = '<?php
declare(strict_types=1);
/** Auto-generated Hosting CMS — site PHP probe */
$expected = ' . var_export($token, true) . ';
if (!hash_equals($expected, (string) ($_GET[\'token\'] ?? \'\'))) {
    http_response_code(403);
    exit(\'Forbidden\');
}
header(\'Content-Type: application/json; charset=utf-8\');
$keys = ' . var_export($keys, true) . ';
$extKeys = ' . var_export($extKeys, true) . ';
$ini = [];
foreach ($keys as $k) {
    $v = ini_get($k);
    $ini[$k] = $v === false ? \'\' : (string) $v;
}
$extensions = [];
foreach ($extKeys as $k) {
    $extensions[$k] = extension_loaded($k);
}
echo json_encode([
    \'ok\' => true,
    \'php_version\' => PHP_VERSION,
    \'php_sapi\' => PHP_SAPI,
    \'ini\' => $ini,
    \'extensions\' => $extensions,
    \'user_ini\' => ini_get(\'user_ini.filename\') ?: \'\',
    \'scanned\' => ini_get(\'user_ini.cache_ttl\') ?: \'\',
], JSON_UNESCAPED_UNICODE);
';
    $path = hs_php_probe_script_path($username);
    if (file_put_contents($path, $php, LOCK_EX) === false) {
        return false;
    }
    @chmod($path, 0644);
    return true;
}

/** @return array<string, mixed>|null */
function hs_php_fetch_site_live(string $username, string $userId): ?array
{
    hs_php_write_probe($username, $userId);
    global $site_url;
    $url = rtrim((string) ($site_url ?? ''), '/')
        . '/' . HS_PUBLIC_HTML . '/'
        . rawurlencode(preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'user')
        . '/hs-php-probe.php?token=' . rawurlencode(hs_php_probe_token($userId));

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'ignore_errors' => true,
            'header' => "User-Agent: HostingCMS-PHP-Probe\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) && !empty($data['ok']) ? $data : null;
}

function hs_apply_php_ini(string $username, array $settings): bool
{
    $dir = hs_public_path($username);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return false;
    }
    hs_php_migrate_ini_file($username);
    $ini = hs_php_build_user_ini($settings);
    $ok = file_put_contents(hs_php_user_ini_path($username), $ini, LOCK_EX) !== false;
    @chmod(hs_php_user_ini_path($username), 0644);
    $ver = hs_php_normalize_version((string) ($settings['php_version'] ?? '8.2'));
    hs_php_merge_htaccess($username, $ver);
    return $ok;
}

/** @return array<string, mixed> */
function hs_php_patch_from_post(array $post, string $section): array
{
    $patch = [];
    if ($section === 'version') {
        $ver = (string) ($post['php_version'] ?? '8.2');
        $patch['php_version'] = hs_php_is_valid_version($ver) ? hs_php_normalize_version($ver) : '8.2';
        return $patch;
    }
    if ($section === 'extensions') {
        $ext = [];
        foreach (hs_php_extension_keys() as $key) {
            $ext[$key] = isset($post['ext_' . $key]);
        }
        $patch['php_extensions'] = $ext;
        return $patch;
    }
    $patch = [
        'memory_limit' => preg_match('/^\d+[MG]$/i', (string) ($post['memory_limit'] ?? '')) ? $post['memory_limit'] : '256M',
        'max_execution_time' => (string) max(30, min(600, (int) ($post['max_execution_time'] ?? 120))),
        'max_input_time' => (string) max(30, min(600, (int) ($post['max_input_time'] ?? 120))),
        'upload_max_filesize' => preg_match('/^\d+[MG]$/i', (string) ($post['upload_max_filesize'] ?? '')) ? $post['upload_max_filesize'] : '64M',
        'post_max_size' => preg_match('/^\d+[MG]$/i', (string) ($post['post_max_size'] ?? '')) ? $post['post_max_size'] : '64M',
        'max_input_vars' => (string) max(1000, min(10000, (int) ($post['max_input_vars'] ?? 3000))),
        'max_file_uploads' => (string) max(1, min(100, (int) ($post['max_file_uploads'] ?? 20))),
        'display_errors' => isset($post['display_errors']) ? '1' : '0',
        'allow_url_fopen' => isset($post['allow_url_fopen']) ? '1' : '0',
        'allow_url_include' => isset($post['allow_url_include']) ? '1' : '0',
        'short_open_tag' => isset($post['short_open_tag']) ? '1' : '0',
        'php_timezone' => trim((string) ($post['php_timezone'] ?? 'Europe/Kyiv')),
        'default_charset' => trim((string) ($post['default_charset'] ?? 'UTF-8')) ?: 'UTF-8',
        'session_gc_maxlifetime' => (string) max(60, min(86400, (int) ($post['session_gc_maxlifetime'] ?? 1440))),
    ];
    $er = (string) ($post['error_reporting'] ?? 'E_ALL');
    $allowedEr = ['E_ALL', 'E_ALL & ~E_NOTICE', 'E_ALL & ~E_DEPRECATED', 'E_ERROR'];
    $patch['error_reporting'] = in_array($er, $allowedEr, true) ? $er : 'E_ALL';
    return $patch;
}

/** Pull live server values into user settings (first-time sync). */
function hs_php_sync_from_server(string $userId, string $username): array
{
    $live = hs_php_live_directives();
    $patch = [
        'memory_limit' => $live['memory_limit'] !== '' ? $live['memory_limit'] : '256M',
        'max_execution_time' => (string) max(30, (int) ($live['max_execution_time'] ?: 120)),
        'max_input_time' => (string) max(30, (int) ($live['max_input_time'] ?: 120)),
        'upload_max_filesize' => $live['upload_max_filesize'] !== '' ? $live['upload_max_filesize'] : '64M',
        'post_max_size' => $live['post_max_size'] !== '' ? $live['post_max_size'] : '64M',
        'max_input_vars' => (string) max(1000, (int) ($live['max_input_vars'] ?: 3000)),
        'max_file_uploads' => (string) max(1, (int) ($live['max_file_uploads'] ?: 20)),
        'display_errors' => strtolower($live['display_errors'] ?? '') === '1' || strtolower($live['display_errors'] ?? '') === 'on' ? '1' : '0',
        'allow_url_fopen' => strtolower($live['allow_url_fopen'] ?? '') === '1' || strtolower($live['allow_url_fopen'] ?? '') === 'on' ? '1' : '0',
        'allow_url_include' => strtolower($live['allow_url_include'] ?? '') === '1' || strtolower($live['allow_url_include'] ?? '') === 'on' ? '1' : '0',
        'short_open_tag' => strtolower($live['short_open_tag'] ?? '') === '1' || strtolower($live['short_open_tag'] ?? '') === 'on' ? '1' : '0',
        'default_charset' => $live['default_charset'] !== '' ? $live['default_charset'] : 'UTF-8',
        'error_reporting' => $live['error_reporting'] !== '' ? $live['error_reporting'] : 'E_ALL',
        'session_gc_maxlifetime' => (string) max(60, (int) ($live['session.gc_maxlifetime'] ?: 1440)),
        'php_version' => hs_php_normalize_version(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION),
    ];
    if (!hs_php_is_valid_version($patch['php_version'])) {
        $patch['php_version'] = '8.2';
    }
    hs_user_settings_save($userId, $patch);
    $settings = hs_user_settings_get($userId);
    hs_apply_php_ini($username, $settings);
    hs_php_write_probe($username, $userId);
    return $patch;
}

function hs_ensure_php_config(string $userId, string $username): void
{
    hs_php_migrate_ini_file($username);
    $path = hs_php_user_ini_path($username);
    if (!is_file($path)) {
        $settings = hs_user_settings_get($userId);
        if (empty($settings['php_synced'])) {
            hs_php_sync_from_server($userId, $username);
            hs_user_settings_save($userId, ['php_synced' => true]);
        } else {
            hs_apply_php_ini($username, $settings);
        }
    }
    hs_php_write_probe($username, $userId);
}

function hs_php_tab_id(?string $requested = null): string
{
    $ids = ['version', 'extensions', 'options'];
    if ($requested !== null && $requested !== '' && in_array($requested, $ids, true)) {
        return $requested;
    }
    return 'version';
}

function hs_php_render_tabs(string $activeTab, array $t): string
{
    $tabs = [
        'version' => $t['php_tab_version'] ?? 'PHP version',
        'extensions' => $t['php_tab_extensions'] ?? 'PHP extensions',
        'options' => $t['php_tab_options'] ?? 'PHP options',
    ];
    $html = '<nav class="hp-tabs" aria-label="PHP tabs"><div class="hp-tabs-scroll">';
    foreach ($tabs as $id => $label) {
        $cls = $id === $activeTab ? ' active' : '';
        $url = hs_url(hs_panel_path('php.php'), $id === 'version' ? [] : ['tab' => $id]);
        $html .= '<a href="' . hs_h($url) . '" class="hp-tab' . $cls . '">' . hs_h($label) . '</a>';
    }
    return $html . '</div></nav>';
}

function hs_php_version_patch_label(string $minor): string
{
    foreach (hs_php_available_versions() as $row) {
        if ($row['minor'] === $minor) {
            return $row['patch'];
        }
    }
    return $minor;
}

/** @return array<string, array<string, mixed>> */
function hs_php_presets(): array
{
    return [
        'default' => [
            'memory_limit' => '256M',
            'max_execution_time' => '120',
            'max_input_time' => '120',
            'upload_max_filesize' => '64M',
            'post_max_size' => '64M',
            'max_input_vars' => '3000',
            'max_file_uploads' => '20',
            'display_errors' => '0',
            'allow_url_fopen' => '1',
            'allow_url_include' => '0',
            'short_open_tag' => '0',
            'error_reporting' => 'E_ALL & ~E_DEPRECATED',
        ],
        'wordpress' => [
            'memory_limit' => '256M',
            'max_execution_time' => '300',
            'max_input_time' => '300',
            'upload_max_filesize' => '128M',
            'post_max_size' => '128M',
            'max_input_vars' => '5000',
            'max_file_uploads' => '40',
            'display_errors' => '0',
            'allow_url_fopen' => '1',
            'allow_url_include' => '0',
            'short_open_tag' => '0',
            'error_reporting' => 'E_ALL & ~E_DEPRECATED',
        ],
        'dev' => [
            'memory_limit' => '512M',
            'max_execution_time' => '600',
            'max_input_time' => '600',
            'upload_max_filesize' => '256M',
            'post_max_size' => '256M',
            'max_input_vars' => '10000',
            'max_file_uploads' => '50',
            'display_errors' => '1',
            'allow_url_fopen' => '1',
            'allow_url_include' => '0',
            'short_open_tag' => '0',
            'error_reporting' => 'E_ALL',
        ],
    ];
}

/** @return array<string, string> */
function hs_php_preset_patch(string $preset): array
{
    return hs_php_presets()[$preset] ?? hs_php_presets()['default'];
}

/** @return array<string, list<string>> */
function hs_php_extension_presets(): array
{
    return [
        'wordpress' => ['mysqli', 'pdo_mysql', 'gd', 'zip', 'intl', 'mbstring', 'curl', 'exif', 'imagick'],
    ];
}

/** @return array<string, mixed> */
function hs_php_extension_preset_patch(string $preset): array
{
    $ext = hs_php_default_extensions();
    foreach (hs_php_extension_presets()[$preset] ?? [] as $key) {
        if (array_key_exists($key, $ext)) {
            $ext[$key] = true;
        }
    }
    return ['php_extensions' => $ext];
}

/** @param array<string, mixed> $s */
function hs_php_version_mismatch(array $s, ?array $siteLive): bool
{
    if (!is_array($siteLive)) {
        return false;
    }
    $live = (string) ($siteLive['php_version'] ?? '');
    if ($live === '') {
        return false;
    }
    $selected = hs_php_normalize_version((string) ($s['php_version'] ?? '8.2'));
    return hs_php_normalize_version($live) !== $selected;
}

/** @param array<string, mixed> $s */
function hs_php_has_pending_ini(array $s, ?array $siteLive, string $userIniPath): bool
{
    if (hs_php_version_mismatch($s, $siteLive)) {
        return true;
    }
    if (!is_array($siteLive)) {
        return false;
    }
    foreach (hs_php_managed_directives() as $key) {
        $savedKey = $key === 'date.timezone' ? 'php_timezone' : ($key === 'session.gc_maxlifetime' ? 'session_gc_maxlifetime' : $key);
        $savedVal = (string) ($s[$savedKey] ?? hs_php_parse_user_ini_file($userIniPath)[$key] ?? '');
        $siteVal = isset($siteLive['ini'][$key]) ? (string) $siteLive['ini'][$key] : '';
        if ($siteVal !== '' && $savedVal !== '' && $siteVal !== $savedVal) {
            return true;
        }
    }
    return false;
}

/** @return array<string, list<string>> */
function hs_php_option_groups(): array
{
    return [
        'limits' => ['memory_limit', 'max_execution_time', 'max_input_time', 'session_gc_maxlifetime'],
        'uploads' => ['upload_max_filesize', 'post_max_size', 'max_file_uploads', 'max_input_vars'],
        'errors' => ['error_reporting', 'display_errors'],
        'security' => ['allow_url_fopen', 'allow_url_include', 'short_open_tag'],
        'locale' => ['php_timezone', 'default_charset'],
    ];
}

/** @return array<string, string> */
function hs_php_extension_group_labels(array $t): array
{
    return [
        'core' => $t['php_ext_grp_core'] ?? 'Core',
        'database' => $t['php_ext_grp_db'] ?? 'Database',
        'media' => $t['php_ext_grp_media'] ?? 'Media',
        'i18n' => $t['php_ext_grp_i18n'] ?? 'i18n',
        'network' => $t['php_ext_grp_net'] ?? 'Network',
        'crypto' => $t['php_ext_grp_crypto'] ?? 'Crypto',
        'xml' => $t['php_ext_grp_xml'] ?? 'XML',
        'archive' => $t['php_ext_grp_archive'] ?? 'Archive',
        'mail' => $t['php_ext_grp_mail'] ?? 'Mail',
    ];
}

function hs_php_field_hint_key(string $fieldKey): string
{
    return 'php_hint_' . $fieldKey;
}

function hs_php_relative_public_path(string $username): string
{
    return HS_PUBLIC_HTML . '/' . $username;
}

/** @param array<string, mixed> $s */
function hs_php_render_hero(array $s, array $user, ?array $siteLive, array $t, bool $pending = false): string
{
    $username = (string) ($user['username'] ?? 'user');
    $currentMinor = hs_php_normalize_version((string) ($s['php_version'] ?? '8.2'));
    $liveVer = is_array($siteLive) ? (string) ($siteLive['php_version'] ?? '—') : '—';
    $handler = hs_php_version_ea_handler($currentMinor);
    $rel = hs_php_relative_public_path($username);
    $mismatch = hs_php_version_mismatch($s, $siteLive);
    $liveStat = '<div class="hs-php-hero-stat"><span class="label">' . hs_h($t['php_hero_live'] ?? 'Live PHP') . '</span>'
        . '<strong>PHP ' . hs_h($liveVer !== '—' ? $liveVer : PHP_VERSION) . '</strong>'
        . '<span class="sub">' . hs_h(is_array($siteLive) ? (string) ($siteLive['php_sapi'] ?? '') : PHP_SAPI);
    if ($mismatch) {
        $liveStat .= ' <span class="hs-php-mismatch" title="' . hs_h($t['php_version_mismatch'] ?? '') . '">'
            . '<i class="fa-solid fa-triangle-exclamation"></i> ' . hs_h($t['php_version_mismatch_short'] ?? 'Mismatch') . '</span>';
    }
    $liveStat .= '</span></div>';
    $statusCls = $pending ? ' is-pending' : ' is-synced';
    $statusLabel = $pending ? ($t['php_status_pending'] ?? 'Pending') : ($t['php_status_synced'] ?? 'Synced');
    return '<div class="hs-php-hero">'
        . $liveStat
        . '<div class="hs-php-hero-stat"><span class="label">' . hs_h($t['php_hero_selected'] ?? 'Selected') . '</span>'
        . '<strong>PHP ' . hs_h(hs_php_version_patch_label($currentMinor)) . '</strong>'
        . '<span class="sub"><code>' . hs_h($handler) . '</code></span></div>'
        . '<div class="hs-php-hero-stat"><span class="label">' . hs_h($t['php_hero_folder'] ?? 'Folder') . '</span>'
        . '<div class="hs-php-hero-path"><code id="php-path-folder">' . hs_h($rel) . '</code>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-target="php-path-folder" title="' . hs_h($t['php_copy'] ?? 'Copy') . '"><i class="fa-solid fa-copy"></i></button></div>'
        . '<span class="sub hs-php-status' . $statusCls . '"><i class="fa-solid fa-' . ($pending ? 'clock' : 'circle-check') . '"></i> '
        . hs_h($statusLabel) . ' · ' . hs_h($t['php_hero_apply'] ?? 'Changes apply in ~5 min') . '</span></div>'
        . '</div>';
}

function hs_php_render_quick_actions(array $t): string
{
    $cacheUrl = hs_url(hs_panel_tab_href('performance', 'cache'));
    $phpinfoUrl = hs_url(hs_panel_path('phpinfo.php'), ['show' => '1']);
    $sync = '<form method="post" class="hs-php-inline-form">' . hs_csrf_field()
        . '<button type="submit" name="php_sync" value="1" class="hs-btn hs-btn-ghost hp-dash-btn-sm">'
        . '<i class="fa-solid fa-rotate"></i> ' . hs_h($t['php_quick_sync'] ?? 'Sync') . '</button></form>';
    return '<div class="hs-php-quick-actions">'
        . '<span class="hs-php-quick-label"><i class="fa-solid fa-bolt"></i> ' . hs_h($t['php_quick_label'] ?? 'Quick actions') . '</span>'
        . '<div class="hs-php-quick-btns">' . $sync
        . '<a href="' . hs_h($phpinfoUrl) . '" target="_blank" rel="noopener" class="hs-btn hs-btn-ghost hp-dash-btn-sm">'
        . '<i class="fa-solid fa-circle-info"></i> ' . hs_h($t['php_quick_phpinfo'] ?? 'phpinfo()') . '</a>'
        . '<a href="#php-live-compare" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-php-scroll="php-live-compare">'
        . '<i class="fa-solid fa-table"></i> ' . hs_h($t['php_quick_compare'] ?? 'Compare') . '</a>'
        . '<a href="' . hs_h($cacheUrl) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm">'
        . '<i class="fa-solid fa-broom"></i> ' . hs_h($t['php_quick_cache'] ?? 'Clear cache') . '</a>'
        . '</div></div>';
}

function hs_php_render_faq(array $t): string
{
    $items = '';
    for ($i = 1; $i <= 4; $i++) {
        $qKey = 'php_faq_' . $i . '_q';
        $aKey = 'php_faq_' . $i . '_a';
        if (empty($t[$qKey]) || empty($t[$aKey])) {
            continue;
        }
        $items .= '<details class="hs-php-faq-item"><summary>' . hs_h($t[$qKey]) . '</summary>'
            . '<p>' . hs_h($t[$aKey]) . '</p></details>';
    }
    if ($items === '') {
        return '';
    }
    return '<section class="hp-card hs-php-faq-card">'
        . '<h2 class="hp-card-title"><i class="fa-solid fa-circle-question"></i> ' . hs_h($t['php_faq_title'] ?? 'FAQ') . '</h2>'
        . '<div class="hp-card-body">' . $items . '</div></section>';
}

function hs_php_render_guide(string $tab, array $t): string
{
    $steps = [];
    for ($i = 1; $i <= 4; $i++) {
        $key = 'php_guide_' . $tab . '_' . $i;
        if (!empty($t[$key])) {
            $steps[] = $t[$key];
        }
    }
    if ($steps === []) {
        return '';
    }
    $items = '';
    foreach ($steps as $n => $step) {
        $items .= '<li><span class="hs-php-guide-num">' . ($n + 1) . '</span>' . hs_h($step) . '</li>';
    }
    return '<aside class="hs-php-guide"><h3 class="hs-php-guide-title"><i class="fa-solid fa-book-open"></i> '
        . hs_h($t['php_guide_title'] ?? 'How it works') . '</h3><ol class="hs-php-guide-list">' . $items . '</ol></aside>';
}

function hs_php_render_paths_card(string $username, string $userIniPreview, bool $userIniExists, array $t): string
{
    $rel = hs_php_relative_public_path($username);
    $iniRel = $rel . '/php.ini';
    $htRel = $rel . '/.htaccess';
    return hs_render_card(
        $t['php_paths_title'] ?? 'Files on server',
        '<ul class="hs-php-paths">'
        . '<li><span>' . hs_h($t['php_user_ini'] ?? 'php.ini') . '</span><code id="php-path-ini">' . hs_h($iniRel) . '</code>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-target="php-path-ini" title="' . hs_h($t['php_copy'] ?? 'Copy') . '"><i class="fa-solid fa-copy"></i></button></li>'
        . '<li><span>.htaccess</span><code id="php-path-htaccess">' . hs_h($htRel) . '</code>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-target="php-path-htaccess"><i class="fa-solid fa-copy"></i></button></li>'
        . '</ul>'
        . '<details class="hs-php-ini-details"><summary>' . hs_h($t['php_preview_ini'] ?? 'Preview php.ini') . '</summary>'
        . '<pre class="hs-php-ini-preview">' . hs_h($userIniPreview) . '</pre></details>',
        ($userIniExists ? '' : '<span class="hp-muted">' . hs_h($t['php_user_ini_pending'] ?? '') . '</span>')
        . ' <a href="' . hs_h(hs_url(hs_panel_path('phpinfo.php'), ['show' => '1'])) . '" target="_blank" rel="noopener" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><i class="fa-solid fa-circle-info"></i> phpinfo()</a>'
    );
}

/** @param array<string, mixed> $s */
function hs_php_render_option_field(string $fieldKey, array $field, array $s, array $t): string
{
    $label = $t[$field['label_key']] ?? $fieldKey;
    $hintKey = hs_php_field_hint_key($fieldKey);
    $hint = $t[$hintKey] ?? '';
    $val = (string) ($s[$fieldKey] ?? $field['default']);
    $html = '<div class="hs-php-opt-field">';
    if ($field['type'] === 'bool') {
        $html .= '<label class="hs-php-bool-label"><input type="checkbox" name="' . hs_h($fieldKey) . '" value="1"' . ($val === '1' ? ' checked' : '') . '> '
            . '<span><strong>' . hs_h($label) . '</strong>';
        if ($hint !== '') {
            $html .= '<small>' . hs_h($hint) . '</small>';
        }
        $html .= '</span></label>';
    } else {
        $html .= '<label>' . hs_h($label) . '</label>';
        if ($field['type'] === 'select') {
            $opts = '';
            foreach ($field['options'] ?? [] as $opt) {
                $opts .= '<option value="' . hs_h($opt) . '"' . ($val === $opt ? ' selected' : '') . '>' . hs_h($opt) . '</option>';
            }
            $html .= '<select name="' . hs_h($fieldKey) . '">' . $opts . '</select>';
        } elseif ($field['type'] === 'timezone') {
            $html .= '<input type="text" name="php_timezone" value="' . hs_h($val) . '" list="php-tz-list">'
                . '<datalist id="php-tz-list"><option value="Europe/Kyiv"><option value="Europe/Oslo"><option value="UTC"><option value="Europe/London"></datalist>';
        } elseif ($field['type'] === 'int') {
            $min = $fieldKey === 'max_input_vars' ? 1000 : ($fieldKey === 'session_gc_maxlifetime' ? 60 : 1);
            $max = $fieldKey === 'max_input_vars' ? 10000 : ($fieldKey === 'session_gc_maxlifetime' ? 86400 : 600);
            $html .= '<input type="number" name="' . hs_h($fieldKey) . '" min="' . $min . '" max="' . $max . '" value="' . hs_h($val) . '">';
        } else {
            $pattern = $field['type'] === 'size' ? ' pattern="\\d+[MG]" required' : '';
            $html .= '<input type="text" name="' . hs_h($fieldKey) . '" value="' . hs_h($val) . '"' . $pattern . '>';
        }
        if ($hint !== '') {
            $html .= '<p class="hs-php-field-hint">' . hs_h($hint) . '</p>';
        }
    }
    return $html . '</div>';
}

/** @param array<string, mixed> $s */
function hs_php_render_options_form(array $s, array $t): string
{
    $fields = hs_php_parameter_fields();
    $groups = hs_php_option_groups();
    $groupLabels = [
        'limits' => $t['php_grp_limits'] ?? 'Limits',
        'uploads' => $t['php_grp_uploads'] ?? 'Uploads',
        'errors' => $t['php_grp_errors'] ?? 'Errors',
        'security' => $t['php_grp_security'] ?? 'Security',
        'locale' => $t['php_grp_locale'] ?? 'Locale',
    ];
    $body = '<p class="hp-muted">' . hs_h($t['php_options_hint'] ?? '') . '</p>'
        . '<div class="hs-php-presets">'
        . '<span class="hs-php-presets-label">' . hs_h($t['php_presets_label'] ?? 'Quick presets') . ':</span> ';
    foreach ([
        'default' => ['label' => $t['php_preset_default'] ?? 'Default', 'hint' => $t['php_preset_default_hint'] ?? ''],
        'wordpress' => ['label' => $t['php_preset_wp'] ?? 'WordPress', 'hint' => $t['php_preset_wp_hint'] ?? ''],
        'dev' => ['label' => $t['php_preset_dev'] ?? 'Development', 'hint' => $t['php_preset_dev_hint'] ?? ''],
    ] as $pid => $meta) {
        $body .= '<button type="submit" name="php_preset" value="' . hs_h($pid) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm"'
            . ($meta['hint'] !== '' ? ' title="' . hs_h($meta['hint']) . '"' : '') . '>' . hs_h($meta['label']) . '</button> ';
    }
    $body .= '</div>';
    $body .= '<p class="hp-muted hs-php-preset-hints">' . hs_h($t['php_presets_explainer'] ?? '') . '</p>';
    foreach ($groups as $gid => $keys) {
        $body .= '<section class="hs-php-opt-group"><h3 class="hs-php-opt-group-title">' . hs_h($groupLabels[$gid] ?? $gid) . '</h3><div class="hp-grid-2 hs-php-options-grid">';
        foreach ($keys as $key) {
            if (isset($fields[$key])) {
                $body .= hs_php_render_option_field($key, $fields[$key], $s, $t);
            }
        }
        $body .= '</div></section>';
    }
    return '<form method="post" class="hp-card hs-php-main-card">' . hs_csrf_field()
        . '<h2 class="hp-card-title">' . hs_h($t['php_tab_options'] ?? 'PHP options') . '</h2>'
        . '<div class="hp-card-body">' . $body . '</div>'
        . '<div class="hp-card-foot"><button type="submit" name="save_php_options" value="1" class="hs-btn hs-btn-primary">'
        . '<i class="fa-solid fa-floppy-disk"></i> ' . hs_h($t['btn_save'] ?? '') . '</button></div></form>';
}

/** @param array<string, mixed> $s */
function hs_php_render_extensions_form(array $s, ?array $siteLive, array $t): string
{
    $extEnabled = hs_php_extensions_enabled($s);
    $liveExt = is_array($siteLive['extensions'] ?? null) ? $siteLive['extensions'] : [];
    $groupLabels = hs_php_extension_group_labels($t);
    $byGroup = [];
    foreach (hs_php_extension_catalog() as $key => $meta) {
        $g = $meta['group'] ?? 'core';
        $byGroup[$g][$key] = $meta;
    }
    $grid = '<div class="hs-php-ext-toolbar">'
        . '<div class="hs-field hs-php-ext-search"><label>' . hs_h($t['php_ext_search'] ?? 'Search') . '</label>'
        . '<input type="search" id="php-ext-search" placeholder="' . hs_h($t['php_ext_search_ph'] ?? 'gd, mysqli…') . '" autocomplete="off"></div>'
        . '<div class="hs-php-ext-quick">'
        . '<button type="submit" name="php_ext_preset" value="wordpress" class="hs-btn hs-btn-ghost hp-dash-btn-sm" title="' . hs_h($t['php_ext_wp_pack_hint'] ?? '') . '">'
        . '<i class="fa-brands fa-wordpress"></i> ' . hs_h($t['php_ext_wp_pack'] ?? 'WordPress pack') . '</button>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" id="php-ext-enable-all" title="' . hs_h($t['php_ext_enable_all_hint'] ?? '') . '">'
        . '<i class="fa-solid fa-check-double"></i> ' . hs_h($t['php_ext_enable_all'] ?? 'Enable all') . '</button>'
        . '</div>'
        . '<p class="hp-muted hs-php-ext-note"><i class="fa-solid fa-circle-info"></i> ' . hs_h($t['php_ext_builtin_note'] ?? '') . '</p></div>';
    foreach ($byGroup as $gid => $items) {
        $grid .= '<h3 class="hs-php-ext-group-title">' . hs_h($groupLabels[$gid] ?? $gid) . '</h3><div class="hs-php-ext-grid">';
        foreach ($items as $key => $meta) {
            $on = !empty($extEnabled[$key]);
            $liveOn = !empty($liveExt[$key]);
            $builtin = !empty($meta['builtin']);
            $liveBadge = is_array($siteLive)
                ? '<span class="hs-php-ext-live ' . ($liveOn ? 'on' : 'off') . '">' . hs_h($liveOn ? ($t['php_ext_on'] ?? 'On') : ($t['php_ext_off'] ?? 'Off')) . '</span>'
                : '';
            $grid .= '<label class="hs-php-ext-row' . ($builtin ? ' is-builtin' : '') . '" data-ext-name="' . hs_h(strtolower($key . ' ' . $meta['label'])) . '">'
                . '<span class="hs-php-ext-name">' . hs_h($meta['label']) . ' <code>' . hs_h($key) . '</code>'
                . ($builtin ? ' <span class="hs-php-ext-builtin">' . hs_h($t['php_ext_builtin'] ?? 'Built-in') . '</span>' : '') . '</span>'
                . $liveBadge
                . '<span class="hs-switch"><input type="checkbox" name="ext_' . hs_h($key) . '" value="1"' . ($on ? ' checked' : '') . ($builtin ? ' disabled' : '') . '><span class="hs-switch-ui"></span></span></label>';
        }
        $grid .= '</div>';
    }
    return '<form method="post" class="hp-card hs-php-main-card" id="php-ext-form">' . hs_csrf_field()
        . '<h2 class="hp-card-title">' . hs_h($t['php_tab_extensions'] ?? '') . '</h2>'
        . '<div class="hp-card-body"><p class="hp-muted">' . hs_h($t['php_extensions_hint'] ?? '') . '</p>' . $grid . '</div>'
        . '<div class="hp-card-foot"><button type="submit" name="save_php_extensions" value="1" class="hs-btn hs-btn-primary">'
        . '<i class="fa-solid fa-floppy-disk"></i> ' . hs_h($t['btn_save'] ?? '') . '</button></div></form>';
}

/** @param array<string, mixed> $s */
function hs_php_render_version_form(array $s, array $t): string
{
    $currentMinor = hs_php_normalize_version((string) ($s['php_version'] ?? '8.2'));
    $cards = '';
    foreach (hs_php_available_versions() as $row) {
        $minor = $row['minor'];
        $checked = $currentMinor === $minor ? ' checked' : '';
        $rec = $row['recommended'] ? ' <span class="hs-php-ver-badge">' . hs_h($t['php_recommended'] ?? '') . '</span>' : '';
        $cards .= '<label class="hs-php-ver-card' . ($checked ? ' is-active' : '') . '">'
            . '<input type="radio" name="php_version" value="' . hs_h($minor) . '"' . $checked . '>'
            . '<span class="hs-php-ver-main">PHP ' . hs_h($row['patch']) . '</span>'
            . '<span class="hs-php-ver-sub">' . hs_h(hs_php_version_ea_handler($minor)) . $rec . '</span></label>';
    }
    return '<form method="post" class="hp-card hs-php-main-card">' . hs_csrf_field()
        . '<h2 class="hp-card-title">' . hs_h($t['php_tab_version'] ?? '') . '</h2>'
        . '<div class="hp-card-body"><p class="hp-muted">' . hs_h($t['php_version_hint'] ?? '') . '</p>'
        . '<div class="hs-php-ver-grid">' . $cards . '</div>'
        . '<div class="hs-php-note"><i class="fa-solid fa-clock"></i> ' . hs_h($t['tip_php'] ?? '') . '</div></div>'
        . '<div class="hp-card-foot"><button type="submit" name="save_php_version" value="1" class="hs-btn hs-btn-primary">'
        . '<i class="fa-solid fa-floppy-disk"></i> ' . hs_h($t['btn_save'] ?? '') . '</button>'
        . '<span class="hp-muted">.htaccess → <code>' . hs_h(hs_php_version_ea_handler($currentMinor)) . '</code></span></div></form>';
}

function hs_php_render_live_collapsible(array $panelLive, ?array $siteLive, array $s, string $userIniPath, array $t): string
{
    $rows = '';
    foreach (hs_php_managed_directives() as $key) {
        $panelVal = $panelLive[$key] ?? '—';
        $siteVal = is_array($siteLive) && isset($siteLive['ini'][$key]) ? (string) $siteLive['ini'][$key] : '—';
        $savedKey = $key === 'date.timezone' ? 'php_timezone' : ($key === 'session.gc_maxlifetime' ? 'session_gc_maxlifetime' : $key);
        $savedVal = (string) ($s[$savedKey] ?? hs_php_parse_user_ini_file($userIniPath)[$key] ?? '—');
        $match = $siteVal !== '—' && $savedVal !== '—' && $siteVal === $savedVal;
        $rows .= '<tr><td><code>' . hs_h($key) . '</code></td><td>' . hs_h($panelVal) . '</td><td>' . hs_h($siteVal) . '</td><td>' . hs_h($savedVal)
            . ($match ? ' <i class="fa-solid fa-check" style="color:var(--hs-success)"></i>' : '') . '</td></tr>';
    }
    $sync = '<form method="post" style="display:inline">' . hs_csrf_field()
        . '<button type="submit" name="php_sync" value="1" class="hs-btn hs-btn-ghost hp-dash-btn-sm">'
        . '<i class="fa-solid fa-rotate"></i> ' . hs_h($t['php_sync_btn'] ?? '') . '</button></form>';
    return '<details class="hp-card hs-php-live-details" id="php-live-compare"><summary class="hp-card-title hs-php-live-summary">'
        . '<span><i class="fa-solid fa-chart-simple"></i> ' . hs_h($t['php_live_compare'] ?? 'Compare values') . '</span>'
        . $sync . '</summary><div class="hp-card-body">'
        . '<p class="hp-muted">' . hs_h($t['php_live_site_hint'] ?? '') . '</p>'
        . '<div class="hs-table-wrap"><table class="hs-table"><thead><tr>'
        . '<th>' . hs_h($t['php_directive'] ?? '') . '</th><th>' . hs_h($t['php_panel'] ?? '') . '</th>'
        . '<th>' . hs_h($t['php_site'] ?? '') . '</th><th>' . hs_h($t['php_saved_col'] ?? '') . '</th></tr></thead><tbody>'
        . $rows . '</tbody></table></div></div></details>';
}