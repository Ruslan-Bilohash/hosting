<?php
declare(strict_types=1);

/**
 * Admin site debugger: structure, HTTP, APIs, error log store, saved reports.
 */

function hs_debug_dir(): string
{
    $dir = HS_DATA_DIR . '/debug';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "Require all denied\nDeny from all\n");
    }

    return $dir;
}

function hs_debug_errors_file(): string
{
    return hs_debug_dir() . '/errors.json';
}

function hs_debug_reports_dir(): string
{
    $dir = hs_debug_dir() . '/reports';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }

    return $dir;
}

/** @return list<array<string,mixed>> */
function hs_debug_errors_load(): array
{
    $data = hs_read_json(hs_debug_errors_file());
    $items = is_array($data['items'] ?? null) ? $data['items'] : [];

    return array_values(array_filter($items, 'is_array'));
}

/** @param list<array<string,mixed>> $items */
function hs_debug_errors_save(array $items): bool
{
    // Keep last 500
    if (count($items) > 500) {
        $items = array_slice($items, -500);
    }

    return hs_write_json(hs_debug_errors_file(), [
        'updated_at' => gmdate('c'),
        'items' => array_values($items),
    ]);
}

/** @param array<string,mixed> $entry */
function hs_debug_error_append(array $entry): void
{
    $items = hs_debug_errors_load();
    $items[] = array_merge([
        'id' => 'err_' . bin2hex(random_bytes(6)),
        'ts' => gmdate('c'),
        'source' => 'manual',
        'level' => 'error',
        'message' => '',
        'file' => '',
        'line' => 0,
    ], $entry);
    hs_debug_errors_save($items);
}

function hs_debug_errors_clear(): bool
{
    return hs_write_json(hs_debug_errors_file(), ['updated_at' => gmdate('c'), 'items' => []]);
}

/**
 * Import recent PHP error_log lines into stored errors.
 *
 * @return array{imported:int,sources:list<string>}
 */
function hs_debug_import_error_logs(int $maxLines = 80): array
{
    $root = dirname(__DIR__);
    $paths = [
        $root . '/error_log',
        $root . '/panel/error_log',
        $root . '/admin/error_log',
    ];
    $imported = 0;
    $sources = [];
    $items = hs_debug_errors_load();
    $seen = [];
    foreach ($items as $it) {
        $sig = md5((string) ($it['message'] ?? '') . '|' . (string) ($it['file'] ?? '') . '|' . (string) ($it['line'] ?? ''));
        $seen[$sig] = true;
    }

    foreach ($paths as $path) {
        if (!is_file($path) || !is_readable($path)) {
            continue;
        }
        $sources[] = $path;
        $size = filesize($path) ?: 0;
        $raw = (string) @file_get_contents($path, false, null, max(0, $size - 120000));
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $lines = array_slice($lines, -$maxLines);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (!preg_match('/Fatal|Parse error|Warning|Notice|Deprecated|Uncaught/i', $line)) {
                continue;
            }
            $file = '';
            $ln = 0;
            if (preg_match('/in (.+?) on line (\d+)/', $line, $m)) {
                $file = $m[1];
                $ln = (int) $m[2];
            }
            $level = 'error';
            if (stripos($line, 'Warning') !== false) {
                $level = 'warning';
            } elseif (stripos($line, 'Notice') !== false || stripos($line, 'Deprecated') !== false) {
                $level = 'notice';
            } elseif (stripos($line, 'Fatal') !== false || stripos($line, 'Parse') !== false) {
                $level = 'fatal';
            }
            $sig = md5($line);
            if (isset($seen[$sig])) {
                continue;
            }
            $seen[$sig] = true;
            $items[] = [
                'id' => 'err_' . bin2hex(random_bytes(5)),
                'ts' => gmdate('c'),
                'source' => basename(dirname($path)) . '/' . basename($path),
                'level' => $level,
                'message' => mb_substr($line, 0, 2000),
                'file' => $file,
                'line' => $ln,
            ];
            $imported++;
        }
    }
    hs_debug_errors_save($items);

    return ['imported' => $imported, 'sources' => $sources];
}

/**
 * Critical paths that must exist for Solaskinner hosting CMS.
 *
 * @return list<string>
 */
function hs_debug_required_paths(): array
{
    return [
        'init.php',
        'config.php',
        'index.php',
        'login.php',
        'register.php',
        'cookies.php',
        'privacy.php',
        'terms.php',
        'sitemap.php',
        'includes/helpers.php',
        'includes/storage.php',
        'includes/security.php',
        'includes/database.php',
        'includes/client-auth.php',
        'includes/admin-auth.php',
        'includes/panel-bootstrap.php',
        'includes/layout-public.php',
        'includes/layout-panel.php',
        'includes/layout-admin.php',
        'includes/admin-nav.php',
        'includes/plans.php',
        'includes/invoices.php',
        'includes/payments.php',
        'includes/payment-settings.php',
        'includes/pdf-invoice.php',
        'includes/invoice-ui.php',
        'includes/seo-apps-catalog.php',
        'includes/seo-app-page.php',
        'includes/legal.php',
        'includes/legal-content.php',
        'panel/index.php',
        'panel/invoices.php',
        'panel/activate.php',
        'admin/index.php',
        'admin/tools.php',
        'admin/debugger.php',
        'admin/debugger-api.php',
        'includes/admin-debugger.php',
        'admin/clients.php',
        'admin/login.php',
        'seo/index.php',
        'assets/css/app.css',
        'assets/js/app.js',
        'assets/js/cookie-consent.js',
        'lang/en.php',
        'lang/uk.php',
        'lang/no.php',
    ];
}

/**
 * Public URLs relative to site root for HTTP smoke tests.
 *
 * @return list<string>
 */
function hs_debug_public_urls(): array
{
    return [
        '/',
        '/login.php',
        '/register.php',
        '/cookies.php',
        '/privacy.php',
        '/terms.php',
        '/seo/',
        '/seo/hosting-for-shop.php',
        '/seo/hosting-for-booking.php',
        '/seo/hosting-for-wordpress.php',
        '/domain',
        '/sitemap.php',
        '/panel/',
        '/admin/login.php',
    ];
}

/**
 * @return list<array{path:string,ok:bool,detail:string,size?:int}>
 */
function hs_debug_scan_structure(): array
{
    $root = dirname(__DIR__);
    $out = [];
    foreach (hs_debug_required_paths() as $rel) {
        $abs = $root . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
        if (!file_exists($abs)) {
            $out[] = ['path' => $rel, 'ok' => false, 'detail' => 'missing'];
            continue;
        }
        $size = is_file($abs) ? (int) filesize($abs) : 0;
        if (is_file($abs) && $size === 0) {
            $out[] = ['path' => $rel, 'ok' => false, 'detail' => 'empty file', 'size' => 0];
            continue;
        }
        $out[] = ['path' => $rel, 'ok' => true, 'detail' => is_dir($abs) ? 'dir' : 'file', 'size' => $size];
    }

    return $out;
}

/**
 * Lightweight PHP token parse check (no exec/php -l needed).
 *
 * @return list<array{path:string,ok:bool,detail:string}>
 */
function hs_debug_scan_php_syntax(int $limit = 120): array
{
    $root = dirname(__DIR__);
    $dirs = ['includes', 'panel', 'admin', 'seo', 'lang'];
    $files = [];
    foreach (['init.php', 'config.php', 'index.php', 'login.php', 'register.php', 'cookies.php', 'privacy.php', 'terms.php'] as $top) {
        $p = $root . '/' . $top;
        if (is_file($p)) {
            $files[] = $top;
        }
    }
    foreach ($dirs as $dir) {
        $base = $root . '/' . $dir;
        if (!is_dir($base)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            // skip huge backups / vendor if any
            if (str_contains($rel, '/vendor/') || str_contains($rel, 'backups/')) {
                continue;
            }
            $files[] = $rel;
            if (count($files) >= $limit) {
                break 2;
            }
        }
    }

    $out = [];
    foreach ($files as $rel) {
        $abs = $root . '/' . $rel;
        $src = @file_get_contents($abs);
        if ($src === false) {
            $out[] = ['path' => $rel, 'ok' => false, 'detail' => 'unreadable'];
            continue;
        }
        // token_get_all does not catch all syntax errors but catches many parse issues in PHP 8+
        // Prefer php_check_syntax alternative: eval in isolated process not available — use token + brace balance
        $tokens = @token_get_all($src);
        if (!is_array($tokens) || $tokens === []) {
            $out[] = ['path' => $rel, 'ok' => false, 'detail' => 'token_get_all failed'];
            continue;
        }
        $bad = false;
        foreach ($tokens as $tok) {
            if (is_array($tok) && defined('T_BAD_CHARACTER') && $tok[0] === T_BAD_CHARACTER) {
                $bad = true;
                break;
            }
        }
        if ($bad) {
            $out[] = ['path' => $rel, 'ok' => false, 'detail' => 'T_BAD_CHARACTER'];
            continue;
        }
        // Unclosed PHP open tag without close on pure-PHP files is fine; ensure non-empty
        if (strlen(trim($src)) < 5) {
            $out[] = ['path' => $rel, 'ok' => false, 'detail' => 'too small'];
            continue;
        }
        $out[] = ['path' => $rel, 'ok' => true, 'detail' => 'ok tokens=' . count($tokens)];
    }

    return $out;
}

/**
 * @return list<array{url:string,ok:bool,status:int,ms:int,detail:string,len:int}>
 */
function hs_debug_scan_http(): array
{
    $base = rtrim(defined('HS_CANONICAL_URL') ? HS_CANONICAL_URL : 'https://solaskinner.com', '/');
    $out = [];
    foreach (hs_debug_public_urls() as $path) {
        $url = $base . $path;
        $started = microtime(true);
        $status = 0;
        $body = '';
        $err = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_CONNECTTIMEOUT => 6,
                CURLOPT_USERAGENT => 'SolaSkinner-AdminDebugger/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HEADER => false,
            ]);
            $body = (string) curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($body === '' && curl_errno($ch)) {
                $err = curl_error($ch);
            }
            curl_close($ch);
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 12,
                    'header' => "User-Agent: SolaSkinner-AdminDebugger/1.0\r\n",
                    'ignore_errors' => true,
                ],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $body = (string) @file_get_contents($url, false, $ctx);
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
                $status = (int) $m[1];
            }
        }
        $ms = (int) round((microtime(true) - $started) * 1000);
        $badMarkers = ['Fatal error', 'Parse error', 'Uncaught Error', 'Uncaught TypeError', 'undefined function'];
        $hasFatal = false;
        foreach ($badMarkers as $m) {
            if (str_contains($body, $m)) {
                $hasFatal = true;
                break;
            }
        }
        $ok = $status >= 200 && $status < 400 && !$hasFatal && $body !== '';
        $detail = $err !== '' ? $err : ($hasFatal ? 'php_error_in_body' : 'ok');
        if ($status === 0 && $detail === 'ok') {
            $detail = 'no_response';
            $ok = false;
        }
        $out[] = [
            'url' => $url,
            'ok' => $ok,
            'status' => $status,
            'ms' => $ms,
            'detail' => $detail,
            'len' => strlen($body),
        ];
    }

    return $out;
}

/**
 * @return list<array{id:string,label:string,ok:bool,detail:string}>
 */
function hs_debug_scan_apis(): array
{
    $out = [];
    $root = dirname(__DIR__);

    $checks = [
        [
            'id' => 'mysql',
            'label' => 'MySQL / DB',
            'fn' => static function (): array {
                $dbFile = dirname(__DIR__) . '/includes/database.php';
                if (is_file($dbFile)) {
                    require_once $dbFile;
                }
                $installed = function_exists('hs_is_mysql_installed') && hs_is_mysql_installed();
                if (!$installed) {
                    return [true, 'JSON storage mode (MySQL not required)'];
                }
                try {
                    $pdo = function_exists('hs_db_pdo') ? hs_db_pdo() : null;
                    if ($pdo instanceof PDO) {
                        $pdo->query('SELECT 1');
                        return [true, 'connected'];
                    }
                    if (function_exists('hs_db_require_pdo')) {
                        $pdo = hs_db_require_pdo();
                        $pdo->query('SELECT 1');
                        return [true, 'connected (require)'];
                    }
                    return [false, 'MySQL installed but PDO unavailable'];
                } catch (Throwable $e) {
                    return [false, $e->getMessage()];
                }
            },
        ],
        [
            'id' => 'stripe',
            'label' => 'Stripe payments',
            'fn' => static function (): array {
                if (is_file(dirname(__DIR__) . '/includes/payment-settings.php')) {
                    require_once dirname(__DIR__) . '/includes/payment-settings.php';
                }
                if (!function_exists('hs_payment_stripe_enabled')) {
                    return [false, 'payment-settings missing'];
                }
                $en = hs_payment_stripe_enabled();
                $sk = function_exists('hs_payment_stripe_secret_key') ? hs_payment_stripe_secret_key() : '';
                if (!$en) {
                    return [true, 'disabled (OK if unused)'];
                }
                return [$sk !== '', $sk !== '' ? 'enabled + secret set' : 'enabled but secret empty'];
            },
        ],
        [
            'id' => 'paypal',
            'label' => 'PayPal payments',
            'fn' => static function (): array {
                if (is_file(dirname(__DIR__) . '/includes/payment-settings.php')) {
                    require_once dirname(__DIR__) . '/includes/payment-settings.php';
                }
                if (!function_exists('hs_payment_paypal_enabled')) {
                    return [false, 'payment-settings missing'];
                }
                $en = hs_payment_paypal_enabled();
                if (!$en) {
                    return [true, 'disabled (OK if unused)'];
                }
                $id = hs_payment_paypal_client_id();
                return [$id !== '', $id !== '' ? 'enabled + client id set' : 'enabled but credentials empty'];
            },
        ],
        [
            'id' => 'namecheap',
            'label' => 'Namecheap domains API',
            'fn' => static function (): array {
                $api = dirname(__DIR__) . '/includes/providers/namecheap-api.php';
                if (!is_file($api)) {
                    return [false, 'namecheap-api.php missing'];
                }
                require_once $api;
                $has = function_exists('hs_namecheap_configured') && hs_namecheap_configured();

                return [true, $has ? 'configured' : 'file present (not configured)'];
            },
        ],
        [
            'id' => 'users',
            'label' => 'Users storage',
            'fn' => static function (): array {
                if (!function_exists('hs_users')) {
                    require_once dirname(__DIR__) . '/includes/storage.php';
                }
                $users = function_exists('hs_users') ? hs_users() : [];
                $n = is_array($users) ? count($users) : 0;
                return [true, $n . ' user(s)'];
            },
        ],
        [
            'id' => 'plans',
            'label' => 'Plans catalog',
            'fn' => static function (): array {
                if (!function_exists('hs_plans')) {
                    require_once dirname(__DIR__) . '/includes/plans.php';
                }
                $plans = function_exists('hs_plans') ? hs_plans() : [];
                $n = is_array($plans) ? count($plans) : 0;
                return [$n > 0, $n . ' plan(s)'];
            },
        ],
        [
            'id' => 'seo_catalog',
            'label' => 'SEO CMS catalog',
            'fn' => static function (): array {
                if (!function_exists('hs_seo_apps_catalog')) {
                    $f = dirname(__DIR__) . '/includes/seo-apps-catalog.php';
                    if (is_file($f)) {
                        require_once $f;
                    }
                }
                $n = function_exists('hs_seo_apps_order') ? count(hs_seo_apps_order()) : 0;
                return [$n >= 10, $n . ' app landing(s)'];
            },
        ],
        [
            'id' => 'session',
            'label' => 'PHP session',
            'fn' => static function (): array {
                $path = session_save_path();
                $writable = $path === '' || is_writable($path) || @is_writable(sys_get_temp_dir());
                return [$writable, 'save_path=' . ($path !== '' ? $path : '(default)') . ' php=' . PHP_VERSION];
            },
        ],
        [
            'id' => 'data_dir',
            'label' => 'Data directory',
            'fn' => static function (): array {
                $ok = defined('HS_DATA_DIR') && is_dir(HS_DATA_DIR) && is_writable(HS_DATA_DIR);
                return [$ok, defined('HS_DATA_DIR') ? HS_DATA_DIR : 'HS_DATA_DIR undefined'];
            },
        ],
        [
            'id' => 'public_html',
            'label' => 'public_html path',
            'fn' => static function (): array {
                $p = function_exists('hs_public_path') ? hs_public_path() : '';
                $ok = $p !== '' && is_dir($p);
                return [$ok, $p !== '' ? $p : 'missing'];
            },
        ],
        [
            'id' => 'opcache',
            'label' => 'OPcache',
            'fn' => static function (): array {
                $en = function_exists('opcache_get_status');
                if (!$en) {
                    return [true, 'not available'];
                }
                $st = @opcache_get_status(false);
                if (!is_array($st)) {
                    return [true, 'status unavailable'];
                }
                return [true, !empty($st['opcache_enabled']) ? 'enabled' : 'disabled'];
            },
        ],
        [
            'id' => 'pdf_invoice',
            'label' => 'Invoice PDF generator',
            'fn' => static function (): array {
                $f = dirname(__DIR__) . '/includes/pdf-invoice.php';
                if (!is_file($f)) {
                    return [false, 'pdf-invoice.php missing'];
                }
                require_once $f;
                $ok = function_exists('hs_invoice_pdf_bytes');
                return [$ok, $ok ? 'hs_invoice_pdf_bytes ready' : 'function missing'];
            },
        ],
        [
            'id' => 'cookie_consent',
            'label' => 'Cookie consent assets',
            'fn' => static function (): array {
                $js = dirname(__DIR__) . '/assets/js/cookie-consent.js';
                $legal = dirname(__DIR__) . '/includes/legal.php';
                $ok = is_file($js) && is_file($legal);
                return [$ok, $ok ? 'js + legal present' : 'missing assets'];
            },
        ],
        [
            'id' => 'admin_debugger',
            'label' => 'Debugger self',
            'fn' => static function (): array {
                return [true, 'loaded'];
            },
        ],
    ];

    foreach ($checks as $c) {
        try {
            /** @var array{0:bool,1:string} $res */
            $res = ($c['fn'])();
            $out[] = [
                'id' => $c['id'],
                'label' => $c['label'],
                'ok' => (bool) $res[0],
                'detail' => (string) $res[1],
            ];
        } catch (Throwable $e) {
            $out[] = [
                'id' => $c['id'],
                'label' => $c['label'],
                'ok' => false,
                'detail' => $e->getMessage(),
            ];
        }
    }

    // Admin endpoint presence
    $adminEndpoints = [
        'admin/server-health-api.php',
        'admin/files-api.php',
        'admin/payments.php',
        'admin/namecheap.php',
        'admin/mysql.php',
        'panel/invoice-pdf.php',
        'panel/api.php',
    ];
    foreach ($adminEndpoints as $rel) {
        $abs = $root . '/' . $rel;
        $out[] = [
            'id' => 'file:' . $rel,
            'label' => 'Endpoint ' . $rel,
            'ok' => is_file($abs),
            'detail' => is_file($abs) ? 'present' : 'missing',
        ];
    }

    return $out;
}

/**
 * Run full diagnostic suite.
 *
 * @return array<string,mixed>
 */
function hs_debug_run_full(bool $importLogs = true): array
{
    $t0 = microtime(true);
    $import = $importLogs ? hs_debug_import_error_logs() : ['imported' => 0, 'sources' => []];
    $structure = hs_debug_scan_structure();
    $syntax = hs_debug_scan_php_syntax(150);
    $http = hs_debug_scan_http();
    $apis = hs_debug_scan_apis();
    $errors = hs_debug_errors_load();

    $countFail = static function (array $rows) : int {
        $n = 0;
        foreach ($rows as $r) {
            if (empty($r['ok'])) {
                $n++;
            }
        }

        return $n;
    };

    $summary = [
        'structure_total' => count($structure),
        'structure_fail' => $countFail($structure),
        'syntax_total' => count($syntax),
        'syntax_fail' => $countFail($syntax),
        'http_total' => count($http),
        'http_fail' => $countFail($http),
        'api_total' => count($apis),
        'api_fail' => $countFail($apis),
        'errors_stored' => count($errors),
        'errors_imported_now' => (int) ($import['imported'] ?? 0),
    ];
    $summary['ok'] = $summary['structure_fail'] === 0
        && $summary['syntax_fail'] === 0
        && $summary['http_fail'] === 0
        && $summary['api_fail'] === 0;

    return [
        'id' => 'rep_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(3)),
        'created_at' => gmdate('c'),
        'version' => function_exists('hs_version') ? hs_version() : '',
        'host' => defined('HS_PRIMARY_DOMAIN') ? HS_PRIMARY_DOMAIN : '',
        'php' => PHP_VERSION,
        'duration_ms' => (int) round((microtime(true) - $t0) * 1000),
        'summary' => $summary,
        'import' => $import,
        'structure' => $structure,
        'syntax' => $syntax,
        'http' => $http,
        'apis' => $apis,
        'errors' => array_slice($errors, -100),
    ];
}

/**
 * @param array<string,mixed> $report
 * @return array{ok:bool,id?:string,path?:string,error?:string}
 */
function hs_debug_report_save(array $report): array
{
    $id = (string) ($report['id'] ?? ('rep_' . gmdate('Ymd_His')));
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?: ('rep_' . time());
    $report['id'] = $id;
    $report['saved_at'] = gmdate('c');
    $path = hs_debug_reports_dir() . '/' . $id . '.json';
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return ['ok' => false, 'error' => 'json_encode failed'];
    }
    if (@file_put_contents($path, $json, LOCK_EX) === false) {
        return ['ok' => false, 'error' => 'write failed'];
    }
    // index
    $indexPath = hs_debug_dir() . '/reports-index.json';
    $index = hs_read_json($indexPath);
    $list = is_array($index['items'] ?? null) ? $index['items'] : [];
    array_unshift($list, [
        'id' => $id,
        'created_at' => $report['created_at'] ?? gmdate('c'),
        'ok' => !empty($report['summary']['ok']),
        'duration_ms' => $report['duration_ms'] ?? 0,
        'summary' => $report['summary'] ?? [],
    ]);
    $list = array_slice($list, 0, 50);
    hs_write_json($indexPath, ['items' => $list, 'updated_at' => gmdate('c')]);

    return ['ok' => true, 'id' => $id, 'path' => $path];
}

/** @return list<array<string,mixed>> */
function hs_debug_reports_list(): array
{
    $index = hs_read_json(hs_debug_dir() . '/reports-index.json');
    $items = is_array($index['items'] ?? null) ? $index['items'] : [];

    return array_values(array_filter($items, 'is_array'));
}

function hs_debug_report_load(string $id): ?array
{
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?? '';
    if ($id === '') {
        return null;
    }
    $path = hs_debug_reports_dir() . '/' . $id . '.json';
    if (!is_file($path)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($path), true);

    return is_array($data) ? $data : null;
}

function hs_debug_report_delete(string $id): bool
{
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?? '';
    if ($id === '') {
        return false;
    }
    $path = hs_debug_reports_dir() . '/' . $id . '.json';
    if (is_file($path)) {
        @unlink($path);
    }
    $indexPath = hs_debug_dir() . '/reports-index.json';
    $index = hs_read_json($indexPath);
    $list = is_array($index['items'] ?? null) ? $index['items'] : [];
    $list = array_values(array_filter($list, static fn($it): bool => is_array($it) && ($it['id'] ?? '') !== $id));
    hs_write_json($indexPath, ['items' => $list, 'updated_at' => gmdate('c')]);

    return true;
}

/** @param array<string,mixed> $report */
function hs_debug_report_to_text(array $report): string
{
    $s = $report['summary'] ?? [];
    $lines = [];
    $lines[] = 'SolaSkinner Admin Debug Report';
    $lines[] = 'ID: ' . ($report['id'] ?? '');
    $lines[] = 'Created: ' . ($report['created_at'] ?? '');
    $lines[] = 'Host: ' . ($report['host'] ?? '') . '  PHP: ' . ($report['php'] ?? '') . '  Ver: ' . ($report['version'] ?? '');
    $lines[] = 'Duration: ' . ($report['duration_ms'] ?? 0) . ' ms';
    $lines[] = 'Overall OK: ' . (!empty($s['ok']) ? 'YES' : 'NO');
    $lines[] = '';
    $lines[] = 'SUMMARY';
    $lines[] = sprintf(
        'structure fail %d/%d | syntax fail %d/%d | http fail %d/%d | api fail %d/%d | errors %d',
        (int) ($s['structure_fail'] ?? 0),
        (int) ($s['structure_total'] ?? 0),
        (int) ($s['syntax_fail'] ?? 0),
        (int) ($s['syntax_total'] ?? 0),
        (int) ($s['http_fail'] ?? 0),
        (int) ($s['http_total'] ?? 0),
        (int) ($s['api_fail'] ?? 0),
        (int) ($s['api_total'] ?? 0),
        (int) ($s['errors_stored'] ?? 0)
    );
    $lines[] = '';
    $lines[] = 'STRUCTURE FAILS';
    foreach ((array) ($report['structure'] ?? []) as $r) {
        if (!empty($r['ok'])) {
            continue;
        }
        $lines[] = '- ' . ($r['path'] ?? '') . ' :: ' . ($r['detail'] ?? '');
    }
    $lines[] = '';
    $lines[] = 'SYNTAX FAILS';
    foreach ((array) ($report['syntax'] ?? []) as $r) {
        if (!empty($r['ok'])) {
            continue;
        }
        $lines[] = '- ' . ($r['path'] ?? '') . ' :: ' . ($r['detail'] ?? '');
    }
    $lines[] = '';
    $lines[] = 'HTTP';
    foreach ((array) ($report['http'] ?? []) as $r) {
        $mark = !empty($r['ok']) ? 'OK' : 'FAIL';
        $lines[] = sprintf(
            '[%s] %s status=%s ms=%s len=%s %s',
            $mark,
            $r['url'] ?? '',
            $r['status'] ?? '',
            $r['ms'] ?? '',
            $r['len'] ?? '',
            $r['detail'] ?? ''
        );
    }
    $lines[] = '';
    $lines[] = 'APIs';
    foreach ((array) ($report['apis'] ?? []) as $r) {
        $mark = !empty($r['ok']) ? 'OK' : 'FAIL';
        $lines[] = sprintf('[%s] %s — %s', $mark, $r['label'] ?? $r['id'] ?? '', $r['detail'] ?? '');
    }
    $lines[] = '';
    $lines[] = 'RECENT ERRORS';
    foreach ((array) ($report['errors'] ?? []) as $e) {
        $lines[] = sprintf(
            '[%s] %s %s',
            $e['level'] ?? 'error',
            $e['ts'] ?? '',
            $e['message'] ?? ''
        );
    }
    $lines[] = '';
    $lines[] = 'END';

    return implode("\n", $lines) . "\n";
}
