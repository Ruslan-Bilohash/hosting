<?php
declare(strict_types=1);

function hs_panel_feature_defaults(): array
{
    return [
        'cache_enabled' => true,
        'cdn_enabled' => false,
        'perf_ai_enabled' => false,
        'malware_last_scan' => '',
        'malware_status' => 'clean',
        'malware_findings' => [],
        'malware_scanned' => 0,
        'firewall_enabled' => true,
        'ip_blocklist' => [],
        'wp_auto_update' => true,
        'redirects' => [],
        'cron_jobs' => [],
        'dns_records' => [],
        'activity_log' => [],
        'backups' => [],
        'backup_schedule' => 'day',
        'backup_auto' => false,
        'backup_cron_token' => '',
        'dev_mode' => false,
        'logo_file' => '',
        'db_remote' => false,
        'db_remote_ips' => '',
        'git_url' => '',
        'git_branch' => 'main',
        'git_deploy_subdir' => '',
        'git_token' => '',
        'git_last_output' => '',
        'htpasswd_user' => '',
        'htpasswd_pass' => '',
        'ip_allowlist' => [],
        'hotlink_protect' => false,
        'search_indexing' => true,
        'wp_staging_sites' => [],
        'wp_preset' => 'default',
        'migrate_queue' => [],
        'error_log_lines' => [],
        'ftp_password_token' => '',
    ];
}

function hs_panel_log(string $userId, string $action, string $detail = ''): void
{
    require_once __DIR__ . '/activity-log.php';
    hs_activity_log_append($userId, [
        'type' => $action === 'panel_visit' ? 'visit' : 'change',
        'action' => $action,
        'detail' => $detail,
    ]);
}

/** Human-readable label for activity log action codes. */
function hs_panel_log_action_label(string $action, array $t = []): string
{
    $key = 'log_action_' . $action;
    if (!empty($t[$key])) {
        return (string) $t[$key];
    }
    static $fallback = [
        'cache_toggle' => 'Cache toggle',
        'cache_clear' => 'Cache cleared',
        'cdn_toggle' => 'CDN toggle',
        'perf_ai' => 'Performance AI',
        'perf_ai_scan' => 'Performance AI scan',
        'speed_test' => 'Speed test',
        'ssl_toggle' => 'SSL toggle',
        'firewall_toggle' => 'Firewall toggle',
        'hotlink' => 'Hotlink protection',
        'indexing' => 'Folder indexing',
        'ip_block' => 'IP blocked',
        'malware_scan' => 'Malware scan',
        'wp_update_toggle' => 'WP auto-update',
        'redirect_add' => 'Redirect added',
        'migrate_queue' => 'Migration queued',
        'site_copy' => 'Site copied',
        'site_delete' => 'Website deleted',
        'file_upload' => 'File uploaded',
        'backup_create' => 'Backup created',
        'backup_settings' => 'Backup settings',
        'backup_cron' => 'Scheduled backup',
        'db_remote' => 'Remote MySQL',
        'db_remote_save' => 'Remote MySQL settings',
        'db_delete' => 'Database deleted',
        'db_create' => 'Database created',
        'cron_add' => 'Cron job added',
        'dns_add' => 'DNS record added',
        'dns_delete' => 'DNS record deleted',
        'git_save' => 'Git settings saved',
        'git_deploy' => 'Git deploy',
        'htpasswd' => 'Directory password',
        'ip_add' => 'IP allowlist',
        'ai_settings' => 'AI API settings',
        'wp_install' => 'WordPress installed',
        'wp_security' => 'WordPress security',
        'install_app' => 'App installed',
        'master_password_changed' => 'Password changed',
        'master_password_generated' => 'Password generated',
        'landing_publish' => 'Landing published',
        'landing_gallery_upload' => 'Gallery upload',
        'plan_renew' => 'Plan renewed',
        'mailbox_add' => 'Mailbox added',
        'pma_signon' => 'phpMyAdmin opened',
        'folder_chmod' => 'Folder permissions',
        'domain_primary' => 'Primary domain',
        'subdomain_add' => 'Subdomain added',
        'extra_domain_add' => 'Domain added',
        'ssh_toggle' => 'SSH access',
        'php_settings' => 'PHP settings',
        'panel_visit' => 'Panel page',
        'login' => 'Login',
        'logout' => 'Logout',
    ];
    if (str_starts_with($action, 'fm_')) {
        $fm = substr($action, 3);
        return ($t['log_action_fm'] ?? 'File manager') . ': ' . $fm;
    }
    return $fallback[$action] ?? $action;
}

function hs_panel_handle_post(string $section, string $userId, array $user, array $t): array
{
    $error = '';
    $success = '';
    $refresh = false;

    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        return ['error' => $t['register_error_csrf'] ?? 'CSRF', 'success' => '', 'refresh' => false];
    }

    $patch = [];

    if ($section === 'performance') {
        require_once __DIR__ . '/performance.php';
        $sites = hs_sites_for_user($userId);
        if (isset($_POST['toggle_cache'])) {
            $cur = hs_user_settings_get($userId);
            $new = empty($cur['cache_enabled']);
            $res = hs_perf_set_object_cache($user, $new);
            if ($res['ok']) {
                $patch['cache_enabled'] = $new;
                $success = $new ? ($t['perf_cache_on'] ?? 'On') : ($t['perf_cache_off'] ?? 'Off');
                hs_panel_log($userId, 'cache_toggle', $success);
            } else {
                $error = $t['perf_cache_error'] ?? 'Could not update cache rules.';
            }
        } elseif (isset($_POST['clear_object_cache'])) {
            $res = hs_perf_clear_cache($user);
            if ($res['ok']) {
                $patch['cache_cleared_at'] = gmdate('c');
                $success = $t['perf_cache_cleared'] ?? 'Cache cleared';
                hs_panel_log($userId, 'cache_clear', (string) ($res['cleared'] ?? 0));
            } else {
                $error = $t['perf_cache_error'] ?? 'Could not clear cache.';
            }
        } elseif (isset($_POST['toggle_cdn'])) {
            $cur = hs_user_settings_get($userId);
            $new = empty($cur['cdn_enabled']);
            $res = hs_perf_set_cdn($user, $new);
            if ($res['ok']) {
                $patch['cdn_enabled'] = $new;
                $success = $new ? ($t['perf_cdn_on'] ?? 'CDN enabled') : ($t['perf_cdn_off'] ?? 'CDN disabled');
                hs_panel_log($userId, 'cdn_toggle');
            } else {
                $error = $t['perf_cdn_error'] ?? 'Could not update CDN rules.';
            }
        } elseif (isset($_POST['toggle_perf_ai'])) {
            $cur = hs_user_settings_get($userId);
            $patch['perf_ai_enabled'] = empty($cur['perf_ai_enabled']);
            $success = $t['btn_save'] ?? 'Saved';
            hs_panel_log($userId, 'perf_ai');
        } elseif (isset($_POST['run_perf_scan'])) {
            $findings = hs_perf_run_health_scan($user, $sites);
            $patch['perf_ai_findings'] = $findings;
            $patch['perf_ai_last_scan'] = gmdate('c');
            $patch['perf_ai_enabled'] = true;
            $success = $t['perf_ai_scan_done'] ?? 'Diagnostics complete';
            hs_panel_log($userId, 'perf_ai_scan', (string) count($findings));
        } elseif (isset($_POST['run_speed_test'])) {
            $res = hs_perf_run_speed_test($user, $sites, trim((string) ($_POST['speed_url'] ?? '')));
            if ($res['ok']) {
                $patch['speed_desktop'] = $res['desktop'];
                $patch['speed_mobile'] = $res['mobile'];
                $patch['speed_tested_at'] = gmdate('c');
                $patch['speed_probe'] = $res['probe'];
                $patch['speed_report'] = $res['report'] ?? [];
                $success = $t['perf_speed_done'] ?? 'Speed test complete';
                hs_panel_log($userId, 'speed_test', $res['desktop'] . '/' . $res['mobile']);
            } else {
                $error = match ($res['error'] ?? '') {
                    'no_site' => $t['perf_speed_no_site'] ?? 'Install a site first.',
                    'invalid_url' => $t['perf_speed_invalid_url'] ?? 'URL not allowed.',
                    default => $t['perf_speed_error'] ?? 'Speed test failed.',
                };
            }
        }
    } elseif ($section === 'security') {
        require_once __DIR__ . '/security-panel.php';
        $cur = hs_user_settings_get($userId);
        if (isset($_POST['toggle_ssl'])) {
            $new = empty($cur['ssl_enabled']);
            $res = hs_sec_set_ssl($user, $new);
            if ($res['ok']) {
                $patch['ssl_enabled'] = $new;
                $success = $new ? ($t['ssl_enabled_msg'] ?? 'SSL enabled') : ($t['ssl_disabled_msg'] ?? 'SSL disabled');
                hs_panel_log($userId, 'ssl_toggle');
            } else {
                $error = $t['sec_htaccess_error'] ?? 'Could not update .htaccess';
            }
        } elseif (isset($_POST['toggle_firewall'])) {
            $new = empty($cur['firewall_enabled']);
            $res = hs_sec_set_firewall($user, $new);
            if ($res['ok']) {
                $patch['firewall_enabled'] = $new;
                $success = $new ? ($t['sec_firewall_on_msg'] ?? 'Firewall enabled') : ($t['sec_firewall_off_msg'] ?? 'Firewall disabled');
                hs_panel_log($userId, 'firewall_toggle');
            } else {
                $error = $t['sec_htaccess_error'] ?? 'Could not update .htaccess';
            }
        } elseif (isset($_POST['toggle_hotlink_sec'])) {
            $new = empty($cur['hotlink_protect']);
            $domain = (string) ($cur['primary_domain'] ?? hs_default_primary_domain());
            $res = hs_sec_set_hotlink($user, $new, $domain);
            if ($res['ok']) {
                $patch['hotlink_protect'] = $new;
                $success = $t['btn_save'] ?? 'Saved';
                hs_panel_log($userId, 'hotlink');
            } else {
                $error = $t['sec_htaccess_error'] ?? 'Could not update .htaccess';
            }
        } elseif (isset($_POST['toggle_indexing_sec'])) {
            $newAllow = empty($cur['search_indexing']);
            $res = hs_sec_set_indexing($user, $newAllow);
            if ($res['ok']) {
                $patch['search_indexing'] = $newAllow;
                $success = $t['btn_save'] ?? 'Saved';
                hs_panel_log($userId, 'indexing');
            } else {
                $error = $t['sec_htaccess_error'] ?? 'Could not update .htaccess';
            }
        } elseif (isset($_POST['add_blocked_ip'])) {
            $ip = trim((string) ($_POST['blocked_ip'] ?? ''));
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $list = is_array($cur['ip_blocklist'] ?? null) ? $cur['ip_blocklist'] : [];
                if (!in_array($ip, $list, true)) {
                    $list[] = $ip;
                }
                if (hs_sec_set_ip_block($user, $list)['ok']) {
                    $patch['ip_blocklist'] = $list;
                    $success = $t['sec_ip_added'] ?? 'IP blocked';
                    hs_panel_log($userId, 'ip_block', $ip);
                } else {
                    $error = $t['sec_htaccess_error'] ?? 'Could not update .htaccess';
                }
            } else {
                $error = $t['sec_ip_invalid'] ?? 'Invalid IP';
            }
        } elseif (isset($_POST['run_malware_scan'])) {
            $scan = hs_sec_run_malware_scan($user);
            if ($scan['ok']) {
                $patch['malware_last_scan'] = gmdate('c');
                $patch['malware_status'] = $scan['status'];
                $patch['malware_findings'] = $scan['findings'];
                $patch['malware_scanned'] = $scan['scanned'];
                $success = $scan['status'] === 'clean'
                    ? ($t['security_scan_ok'] ?? 'Clean')
                    : ($t['sec_malware_found'] ?? 'Issues found');
                hs_panel_log($userId, 'malware_scan', (string) count($scan['findings']));
            } else {
                $error = $t['sec_scan_error'] ?? 'Scan failed';
            }
        } elseif (isset($_POST['toggle_wp_update'])) {
            $new = empty($cur['wp_auto_update']);
            $patch['wp_auto_update'] = $new;
            hs_sec_sync_wp_auto_update($userId, $new);
            $success = $t['btn_save'] ?? 'Saved';
            hs_panel_log($userId, 'wp_update_toggle');
        }
    } elseif ($section === 'domains' && isset($_POST['add_redirect'])) {
        $from = trim((string) ($_POST['redirect_from'] ?? ''));
        $to = trim((string) ($_POST['redirect_to'] ?? ''));
        if ($from !== '' && $to !== '') {
            $cur = hs_user_settings_get($userId);
            $list = is_array($cur['redirects'] ?? null) ? $cur['redirects'] : [];
            $list[] = ['from' => $from, 'to' => $to, 'created_at' => gmdate('c')];
            $patch['redirects'] = $list;
            $success = $t['btn_add_domain'] ?? 'Added';
            hs_panel_log($userId, 'redirect_add', $from . ' → ' . $to);
        } else {
            $error = 'Invalid redirect';
        }
    } elseif ($section === 'websites') {
        if (isset($_POST['queue_migrate'])) {
            $url = trim((string) ($_POST['migrate_url'] ?? ''));
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $cur = hs_user_settings_get($userId);
                $q = is_array($cur['migrate_queue'] ?? null) ? $cur['migrate_queue'] : [];
                $q[] = ['url' => $url, 'status' => 'queued', 'at' => gmdate('c')];
                $patch['migrate_queue'] = $q;
                $success = $t['site_migrate_queued'] ?? 'Queued';
                hs_panel_log($userId, 'migrate_queue', $url);
            } else {
                $error = 'Invalid URL';
            }
        } elseif (isset($_POST['delete_site'])) {
            require_once __DIR__ . '/installer.php';
            $siteId = trim((string) ($_POST['site_id'] ?? ''));
            $res = hs_delete_user_site($user, $siteId);
            if (!empty($res['ok'])) {
                $success = $t['site_deleted'] ?? 'Website deleted';
                $refresh = true;
            } else {
                $error = match ($res['error'] ?? '') {
                    'demo' => $t['site_delete_demo'] ?? 'Demo account cannot delete websites.',
                    'not_found' => $t['site_delete_fail'] ?? 'Could not delete website.',
                    'protected', 'path_forbidden' => $t['site_delete_protected'] ?? 'This website cannot be deleted.',
                    'save' => $t['site_delete_fail'] ?? 'Could not delete website.',
                    default => $t['site_delete_fail'] ?? 'Could not delete website.',
                };
            }
        } elseif (isset($_POST['copy_site'])) {
            $srcSlug = trim((string) ($_POST['copy_from'] ?? ''));
            $newSlug = hs_slugify((string) ($_POST['copy_to'] ?? ''));
            $res = hs_copy_user_site($user, $srcSlug, $newSlug);
            if ($res['ok']) {
                $destPath = (string) ($res['dest_path'] ?? $newSlug);
                $success = ($t['site_copy_done'] ?? 'Copied') . ' → public_html/' . $destPath;
                $refresh = true;
                hs_panel_log($userId, 'site_copy', $srcSlug . ' → ' . $newSlug);
            } else {
                $error = match ($res['error'] ?? '') {
                    'limit' => $t['site_copy_error_limit'] ?? 'Site limit reached for your plan.',
                    'source_not_found' => $t['site_copy_error_source_not_found'] ?? 'Source site not found.',
                    'source_missing' => $t['site_copy_error_source_missing'] ?? 'Source folder not found on disk.',
                    'slug_taken' => $t['site_copy_error_slug_taken'] ?? 'A site with this folder name already exists.',
                    'path_exists' => $t['site_copy_error_path_exists'] ?? 'Destination folder already exists.',
                    'copy_failed' => $t['site_copy_error_copy_failed'] ?? 'Could not copy files.',
                    'save' => $t['site_copy_error_save'] ?? 'Files copied but site record could not be saved.',
                    default => $t['site_copy_error_copy_failed'] ?? 'Copy failed',
                };
            }
        }
    } elseif ($section === 'files') {
        if (isset($_POST['upload_file']) && !empty($_FILES['file']['name'])) {
            $username = (string) ($user['username'] ?? 'user');
            $rel = trim((string) ($_POST['upload_path'] ?? ''));
            $base = hs_public_path($username);
            $destDir = hs_safe_path($base, $rel) ?? $base;
            $name = basename((string) $_FILES['file']['name']);
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name) ?? '';
            if ($name !== '' && is_uploaded_file($_FILES['file']['tmp_name'])) {
                $target = rtrim($destDir, '/\\') . '/' . $name;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                    $success = $t['file_uploaded'] ?? 'File uploaded';
                    hs_panel_log($userId, 'file_upload', $name);
                } else {
                    $error = $t['file_upload_fail'] ?? 'Upload failed';
                }
            } else {
                $error = 'Invalid file';
            }
        } elseif (isset($_POST['create_backup'])) {
            require_once __DIR__ . '/backups.php';
            $res = hs_create_user_backup($user, 'manual');
            if ($res['ok']) {
                $success = $t['backup_created'] ?? 'Backup created';
                hs_panel_log($userId, 'backup_create', $res['name'] ?? '');
            } else {
                $error = match ($res['error'] ?? '') {
                    'zip_missing' => $t['backup_zip_missing'] ?? 'ZIP extension not available',
                    'too_large' => $t['backup_too_large'] ?? 'Backup too large (max 500 MB)',
                    'no_files' => $t['backup_no_files'] ?? 'No files to backup',
                    default => $t['backup_failed'] ?? 'Backup failed',
                };
            }
        } elseif (isset($_POST['save_backup_settings'])) {
            require_once __DIR__ . '/backups.php';
            $freq = (string) ($_POST['backup_schedule'] ?? 'day');
            if (!isset(hs_backup_frequencies()[$freq])) {
                $freq = 'day';
            }
            $patch['backup_schedule'] = $freq;
            $patch['backup_auto'] = !empty($_POST['backup_auto']);
            $success = $t['backup_settings_saved'] ?? 'Backup settings saved';
            hs_panel_log($userId, 'backup_settings', $freq);
        }
    } elseif ($section === 'databases' && (isset($_POST['toggle_db_remote']) || isset($_POST['save_db_remote']))) {
        require_once __DIR__ . '/mysql-provision.php';
        $cur = hs_user_settings_get($userId);
        if (isset($_POST['save_db_remote'])) {
            $ips = trim((string) ($_POST['db_remote_ips'] ?? ''));
            $patch['db_remote_ips'] = $ips;
            $remote = !empty($cur['db_remote']);
            if ($remote && hs_is_mysql_installed() && hs_mysql_provision_enabled() && !hs_mysql_provision_shared_mode()) {
                $grant = hs_mysql_apply_user_remote_grants($userId, true, $ips);
                if (empty($grant['ok'])) {
                    $error = $t['db_remote_grant_fail'] ?? 'Could not apply remote MySQL grants.';
                }
            }
            if ($error === '') {
                $success = $t['db_remote_saved'] ?? 'Remote MySQL settings saved';
                hs_panel_log($userId, 'db_remote_save');
            }
        } else {
            $newRemote = empty($cur['db_remote']);
            $patch['db_remote'] = $newRemote;
            if (hs_is_mysql_installed() && hs_mysql_provision_enabled() && !hs_mysql_provision_shared_mode()) {
                $ips = (string) ($cur['db_remote_ips'] ?? '');
                $grant = hs_mysql_apply_user_remote_grants($userId, $newRemote, $ips);
                if (empty($grant['ok'])) {
                    $error = $t['db_remote_grant_fail'] ?? 'Could not apply remote MySQL grants.';
                    unset($patch['db_remote']);
                }
            }
            if ($error === '') {
                $success = $newRemote ? ($t['db_remote_on'] ?? 'Remote access enabled') : ($t['db_remote_off'] ?? 'Remote access disabled');
                hs_panel_log($userId, 'db_remote');
            }
        }
    } elseif ($section === 'advanced') {
        if (isset($_POST['add_cron'])) {
            $cmd = trim((string) ($_POST['cron_cmd'] ?? ''));
            $sched = trim((string) ($_POST['cron_schedule'] ?? ''));
            if ($cmd !== '' && $sched !== '') {
                $cur = hs_user_settings_get($userId);
                $jobs = is_array($cur['cron_jobs'] ?? null) ? $cur['cron_jobs'] : [];
                $jobs[] = ['schedule' => $sched, 'command' => $cmd, 'created_at' => gmdate('c')];
                $patch['cron_jobs'] = $jobs;
                $success = $t['cron_added'] ?? 'Cron added';
                hs_panel_log($userId, 'cron_add', $sched);
            } else {
                $error = 'Invalid cron';
            }
        } elseif (isset($_POST['add_dns'])) {
            $res = hs_dns_save_record($userId, (string) ($_POST['dns_type'] ?? 'A'), (string) ($_POST['dns_host'] ?? ''), (string) ($_POST['dns_value'] ?? ''));
            if ($res['ok']) {
                $success = $t['dns_added'] ?? 'DNS record added';
                hs_panel_log($userId, 'dns_add', (string) ($res['label'] ?? ''));
            } else {
                $error = $t['dns_invalid'] ?? 'Invalid DNS record';
            }
        } elseif (isset($_POST['delete_dns'])) {
            $idx = (int) ($_POST['dns_index'] ?? -1);
            if (hs_dns_delete_record($userId, $idx)) {
                $success = $t['dns_deleted'] ?? 'DNS record deleted';
                hs_panel_log($userId, 'dns_delete', (string) $idx);
            } else {
                $error = $t['dns_delete_fail'] ?? 'Could not delete record';
            }
        } elseif (isset($_POST['save_git'])) {
            require_once __DIR__ . '/git-deploy.php';
            $cur = hs_user_settings_get($userId);
            $patch['git_url'] = trim((string) ($_POST['git_url'] ?? ''));
            $patch['git_branch'] = hs_git_sanitize_branch((string) ($_POST['git_branch'] ?? 'main'));
            $patch['git_deploy_subdir'] = hs_git_sanitize_subdir((string) ($_POST['git_deploy_subdir'] ?? ''));
            $newToken = trim((string) ($_POST['git_token'] ?? ''));
            if ($newToken !== '') {
                $patch['git_token'] = $newToken;
            } elseif (($cur['git_token'] ?? '') !== '') {
                $patch['git_token'] = (string) $cur['git_token'];
            }
            $success = $t['btn_save'] ?? 'Saved';
            hs_panel_log($userId, 'git_save');
        } elseif (isset($_POST['git_deploy'])) {
            require_once __DIR__ . '/git-deploy.php';
            $cur = hs_user_settings_get($userId);
            $url = (string) ($cur['git_url'] ?? '');
            if ($url === '' || hs_git_parse_repo($url) === null) {
                $error = $t['git_url_required'] ?? 'Set repository URL first';
            } else {
                $username = (string) ($user['username'] ?? 'user');
                $res = hs_git_run_deploy($username, $cur);
                if (!empty($res['ok'])) {
                    $patch['git_last_deploy'] = gmdate('c');
                    $patch['git_last_output'] = (string) ($res['output'] ?? 'OK');
                    $success = $t['git_deployed'] ?? 'Deploy queued';
                    hs_panel_log($userId, 'git_deploy', $url);
                } else {
                    $error = match ($res['error'] ?? '') {
                        'download_failed' => $t['git_err_download'] ?? 'Could not download from GitHub',
                        'invalid_url' => $t['git_url_required'] ?? 'Invalid URL',
                        default => (string) ($res['error'] ?? $t['git_err_deploy'] ?? 'Deploy failed'),
                    };
                }
            }
        } elseif (isset($_POST['save_htpasswd'])) {
            $patch['htpasswd_user'] = trim((string) ($_POST['ht_user'] ?? ''));
            $patch['htpasswd_pass'] = trim((string) ($_POST['ht_pass'] ?? ''));
            $success = $t['btn_save'] ?? 'Saved';
            hs_panel_log($userId, 'htpasswd');
        } elseif (isset($_POST['add_ip'])) {
            $ip = trim((string) ($_POST['ip_addr'] ?? ''));
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $cur = hs_user_settings_get($userId);
                $list = is_array($cur['ip_allowlist'] ?? null) ? $cur['ip_allowlist'] : [];
                $list[] = $ip;
                $patch['ip_allowlist'] = array_values(array_unique($list));
                $success = $t['btn_save'] ?? 'Saved';
                hs_panel_log($userId, 'ip_add', $ip);
            } else {
                $error = 'Invalid IP';
            }
        } elseif (isset($_POST['save_folder_perms'])) {
            require_once __DIR__ . '/file-manager.php';
            $mode = trim((string) ($_POST['chmod_mode'] ?? ''));
            $res = hs_fm_chmod_user_root($user, $mode);
            if (!empty($res['ok'])) {
                $success = $t['adv_perms_saved'] ?? 'Permissions updated';
                hs_panel_log($userId, 'folder_chmod', (string) ($res['perms'] ?? $mode));
            } else {
                $error = match ($res['error'] ?? '') {
                    'invalid_mode' => $t['adv_perms_invalid'] ?? 'Invalid permissions mode',
                    'chmod_failed' => $t['adv_perms_failed'] ?? 'Could not change permissions',
                    default => $t['adv_perms_failed'] ?? 'Could not change permissions',
                };
            }
        } elseif (isset($_POST['toggle_hotlink'])) {
            require_once __DIR__ . '/security-panel.php';
            $cur = hs_user_settings_get($userId);
            $new = empty($cur['hotlink_protect']);
            $domain = (string) ($cur['primary_domain'] ?? hs_default_primary_domain());
            if (hs_sec_set_hotlink($user, $new, $domain)['ok']) {
                $patch['hotlink_protect'] = $new;
                $success = $t['btn_save'] ?? 'Saved';
                hs_panel_log($userId, 'hotlink');
            } else {
                $error = $t['sec_htaccess_error'] ?? 'Could not update .htaccess';
            }
        } elseif (isset($_POST['toggle_indexing'])) {
            require_once __DIR__ . '/security-panel.php';
            $cur = hs_user_settings_get($userId);
            $newAllow = empty($cur['search_indexing']);
            if (hs_sec_set_indexing($user, $newAllow)['ok']) {
                $patch['search_indexing'] = $newAllow;
                $success = $t['btn_save'] ?? 'Saved';
                hs_panel_log($userId, 'indexing');
            } else {
                $error = $t['sec_htaccess_error'] ?? 'Could not update .htaccess';
            }
        }
    } elseif ($section === 'api' && (isset($_POST['save_ai_general']) || isset($_POST['save_openai_settings']) || isset($_POST['save_grok_settings']) || isset($_POST['save_ai_settings']))) {
        require_once __DIR__ . '/support-ai.php';
        $cur = hs_user_settings_get($userId);
        $ai = hs_ai_normalize(is_array($cur['ai'] ?? null) ? $cur['ai'] : []);
        if (isset($_POST['save_ai_general']) || isset($_POST['save_ai_settings'])) {
            $ai['enabled'] = !empty($_POST['ai_enabled']);
            $ai['provider'] = in_array((string) ($_POST['ai_provider'] ?? ''), ['openai', 'grok'], true)
                ? (string) $_POST['ai_provider'] : 'openai';
        }
        if (isset($_POST['save_openai_settings']) || isset($_POST['save_ai_settings'])) {
            $newKey = trim((string) ($_POST['openai_api_key'] ?? ''));
            $ai['openai_api_key'] = $newKey !== '' ? $newKey : (string) ($cur['ai']['openai_api_key'] ?? '');
            $ai['openai_model'] = trim((string) ($_POST['openai_model'] ?? 'gpt-4o-mini')) ?: 'gpt-4o-mini';
        }
        if (isset($_POST['save_grok_settings']) || isset($_POST['save_ai_settings'])) {
            $newKey = trim((string) ($_POST['grok_api_key'] ?? ''));
            $ai['grok_api_key'] = $newKey !== '' ? $newKey : (string) ($cur['ai']['grok_api_key'] ?? '');
            $ai['grok_model'] = trim((string) ($_POST['grok_model'] ?? 'grok-3-mini')) ?: 'grok-3-mini';
        }
        $patch['ai'] = $ai;
        $success = $t['btn_save'] ?? 'Saved';
        hs_panel_log($userId, 'ai_settings');
    } elseif ($section === 'wordpress') {
        require_once __DIR__ . '/wordpress-install.php';
        if (isset($_POST['install_wordpress'])) {
            $res = hs_wordpress_install(
                $user,
                (string) ($_POST['wp_slug'] ?? ''),
                (string) ($_POST['wp_title'] ?? ''),
                (string) ($_POST['wp_admin_user'] ?? 'admin'),
                (string) ($_POST['wp_admin_email'] ?? ''),
                (string) ($_POST['wp_admin_pass'] ?? ''),
                (string) ($_POST['install_base'] ?? '')
            );
            if ($res['ok']) {
                $success = $t['wp_install_success'] ?? 'WordPress installed';
                $refresh = true;
                hs_panel_log($userId, 'wp_install', (string) ($_POST['wp_slug'] ?? ''));
            } else {
                $error = match ($res['error'] ?? '') {
                    'limit' => $t['installer_error_limit'] ?? 'Site limit reached',
                    'slug_taken', 'path_exists' => $t['installer_error_slug'] ?? 'Slug taken',
                    'email' => $t['wp_install_error_email'] ?? 'Invalid email',
                    'password' => $t['wp_install_error_pass'] ?? 'Password min 8 chars',
                    'db', 'provision_config' => $t['db_provision_config'] ?? 'Database not configured',
                    'download' => $t['wp_install_error_download'] ?? 'Could not download WordPress',
                    'plugins_library', 'plugins_mkdir', 'plugins' => $t['wp_install_error_plugins'] ?? 'BILOHASH plugins library not found',
                    default => str_starts_with((string) ($res['error'] ?? ''), 'plugin_')
                        ? ($t['wp_install_error_plugins'] ?? 'Could not install BILOHASH plugins')
                        : ($t['wp_install_error'] ?? ($res['error'] ?? 'Install failed')),
                };
            }
        } elseif (isset($_POST['save_wp_security'])) {
            $res = hs_wordpress_update_security($userId, $user, (string) ($_POST['wp_site_id'] ?? ''), [
                'title' => (string) ($_POST['wp_site_title'] ?? ''),
                'admin_email' => (string) ($_POST['wp_admin_email'] ?? ''),
                'auto_update' => !empty($_POST['wp_auto_update']),
            ]);
            if ($res['ok']) {
                $success = $t['wp_security_saved'] ?? 'Saved';
                $refresh = true;
                hs_panel_log($userId, 'wp_security');
            } else {
                $error = $t['wp_security_error'] ?? 'Site not found';
            }
        }
    }

    if ($patch !== []) {
        hs_user_settings_save($userId, $patch);
        if (isset($patch['backup_schedule']) || isset($patch['backup_auto'])) {
            require_once __DIR__ . '/backups.php';
            hs_backup_sync_cron($userId, $user);
        }
    }

    return ['error' => $error, 'success' => $success, 'refresh' => $refresh];
}

function hs_copy_user_site(array $user, string $srcSlug, string $newSlug): array
{
    require_once __DIR__ . '/installer.php';

    $newSlug = hs_slugify($newSlug);
    if ($newSlug === '' || !hs_user_can_add_site($user)) {
        return ['ok' => false, 'error' => 'limit'];
    }

    $srcSite = null;
    foreach (hs_sites_for_user((string) $user['id']) as $s) {
        if (($s['slug'] ?? '') === $srcSlug) {
            $srcSite = $s;
            break;
        }
    }
    if ($srcSite === null) {
        return ['ok' => false, 'error' => 'source_not_found'];
    }

    $installBase = hs_install_normalize_base($user, (string) ($srcSite['install_base'] ?? hs_install_default_base($user)));
    foreach (hs_sites_for_user((string) $user['id']) as $s) {
        $sameBase = hs_install_normalize_base($user, (string) ($s['install_base'] ?? hs_install_default_base($user))) === $installBase;
        if ($sameBase && ($s['slug'] ?? '') === $newSlug) {
            return ['ok' => false, 'error' => 'slug_taken'];
        }
    }

    $srcRel = hs_install_path_rel($user, $srcSite);
    $srcPath = hs_public_path($srcRel);
    if (!is_dir($srcPath)) {
        return ['ok' => false, 'error' => 'source_missing'];
    }

    $destRel = hs_install_site_at_root($user, $srcSite)
        ? trim($installBase . '/' . $newSlug, '/')
        : trim($installBase . '/' . $newSlug, '/');
    $destPath = hs_public_path($destRel);
    if (is_dir($destPath) || is_file($destPath)) {
        return ['ok' => false, 'error' => 'path_exists'];
    }

    if (!hs_recursive_copy($srcPath, $destPath)) {
        return ['ok' => false, 'error' => 'copy_failed'];
    }

    if (!hs_site_add_for_user((string) $user['id'], [
        'id' => hs_new_id('s'),
        'slug' => $newSlug,
        'title' => ($srcSite['title'] ?? $newSlug) . ' (copy)',
        'domain' => '',
        'app' => $srcSite['app'] ?? 'empty',
        'install_base' => $installBase,
        'status' => 'active',
        'created_at' => gmdate('c'),
    ])) {
        @hs_recursive_rmdir($destPath);
        return ['ok' => false, 'error' => 'save'];
    }

    return ['ok' => true, 'dest_path' => $destRel, 'dest_slug' => $newSlug];
}

/** Recursively remove a directory (rollback helper). */
function hs_recursive_rmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }
    @rmdir($dir);
}

function hs_recursive_copy(string $src, string $dest): bool
{
    if (!is_dir($dest) && !mkdir($dest, 0755, true)) {
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
        } elseif (!copy($item->getPathname(), $target)) {
            return false;
        }
    }
    return true;
}

function hs_create_staging_site(array $user, string $slug): array
{
    $slug = hs_slugify($slug !== '' ? $slug : 'staging');
    $stagingSlug = $slug . '-staging';
    $sites = hs_sites_for_user((string) $user['id']);
    $wpSite = null;
    foreach ($sites as $s) {
        if (($s['app'] ?? '') === 'wordpress') {
            $wpSite = $s;
            break;
        }
    }
    if ($wpSite === null && $sites !== []) {
        $wpSite = $sites[0];
    }
    if ($wpSite === null) {
        return ['ok' => false, 'error' => 'no_site'];
    }
    return hs_copy_user_site($user, (string) $wpSite['slug'], $stagingSlug);
}

function hs_panel_alerts(array $ctx): string
{
    $html = '';
    if (!empty($ctx['success'])) {
        $html .= '<div class="hs-alert hs-alert-success">' . hs_h((string) $ctx['success']) . '</div>';
    }
    if (!empty($ctx['error'])) {
        $html .= '<div class="hs-alert hs-alert-error">' . hs_h((string) $ctx['error']) . '</div>';
    }
    return $html;
}

/** @return array{ok:bool,label?:string} */
function hs_dns_save_record(string $userId, string $type, string $host, string $value, int $ttl = 3600, int $priority = 10): array
{
    $type = strtoupper(trim($type));
    $host = trim($host);
    $value = trim($value);
    $allowed = ['A', 'AAAA', 'CNAME', 'TXT', 'MX', 'NS', 'SRV'];
    if (!in_array($type, $allowed, true) || $host === '' || $value === '') {
        return ['ok' => false];
    }
    if (strlen($host) > 128 || strlen($value) > 512) {
        return ['ok' => false];
    }
    $cur = hs_user_settings_get($userId);
    $recs = is_array($cur['dns_records'] ?? null) ? $cur['dns_records'] : [];
    $ttl = max(300, min(86400, $ttl));
    $entry = [
        'id' => 'dns_' . bin2hex(random_bytes(4)),
        'type' => $type,
        'host' => $host,
        'value' => $value,
        'ttl' => $ttl,
        'created_at' => gmdate('c'),
    ];
    if ($type === 'MX') {
        $entry['priority'] = max(0, min(100, $priority));
    }
    $recs[] = $entry;
    if (!hs_user_settings_save($userId, ['dns_records' => $recs])) {
        return ['ok' => false];
    }
    return ['ok' => true, 'label' => $type . ' ' . $host];
}

function hs_dns_delete_record(string $userId, int $index): bool
{
    if ($index < 0) {
        return false;
    }
    $cur = hs_user_settings_get($userId);
    $recs = is_array($cur['dns_records'] ?? null) ? $cur['dns_records'] : [];
    if (!isset($recs[$index])) {
        return false;
    }
    if (!empty($recs[$index]['system'])) {
        return false;
    }
    array_splice($recs, $index, 1);
    return hs_user_settings_save($userId, ['dns_records' => array_values($recs)]);
}

/** @return array{ok:bool,error?:string} */
function hs_dns_update_record(string $userId, int $index, string $type, string $host, string $value, int $ttl = 3600, int $priority = 0): array
{
    if ($index < 0) {
        return ['ok' => false, 'error' => 'invalid'];
    }
    $type = strtoupper(trim($type));
    $host = trim($host);
    $value = trim($value);
    $allowed = ['A', 'AAAA', 'CNAME', 'TXT', 'MX', 'NS', 'SRV'];
    if (!in_array($type, $allowed, true) || $host === '' || $value === '') {
        return ['ok' => false, 'error' => 'invalid'];
    }
    $cur = hs_user_settings_get($userId);
    $recs = is_array($cur['dns_records'] ?? null) ? $cur['dns_records'] : [];
    if (!isset($recs[$index]) || !empty($recs[$index]['system'])) {
        return ['ok' => false, 'error' => 'locked'];
    }
    $recs[$index]['type'] = $type;
    $recs[$index]['host'] = $host;
    $recs[$index]['value'] = $value;
    $recs[$index]['ttl'] = max(300, min(86400, $ttl));
    if ($type === 'MX') {
        $recs[$index]['priority'] = max(0, min(100, $priority));
    }
    $recs[$index]['updated_at'] = gmdate('c');
    if (!hs_user_settings_save($userId, ['dns_records' => $recs])) {
        return ['ok' => false, 'error' => 'save'];
    }
    return ['ok' => true];
}

function hs_panel_toggle_form(string $name, bool $on, array $t, string $labelOn = '', string $labelOff = ''): string
{
    $labelOn = $labelOn !== '' ? $labelOn : ($t['btn_save'] ?? 'Save');
    $labelOff = $labelOff !== '' ? $labelOff : $labelOn;
    return '<form method="post" style="display:inline">' . hs_csrf_field()
        . '<button type="submit" name="' . hs_h($name) . '" value="1" class="hs-btn ' . ($on ? 'hs-btn-ghost' : 'hs-btn-primary') . '">'
        . hs_h($on ? $labelOff : $labelOn) . '</button></form>';
}

function hs_panel_status_badge(bool $on, array $t): string
{
    $txt = $on ? ($t['sec_state_on'] ?? 'Active') : ($t['sec_state_off'] ?? 'Off');
    return '<span class="hs-sec-state' . ($on ? ' is-on' : ' is-off') . '">' . hs_h($txt) . '</span>';
}

function hs_panel_toggle_switch(string $name, bool $on, array $t): string
{
    return '<form method="post" class="hs-sec-toggle-form">' . hs_csrf_field()
        . '<input type="hidden" name="' . hs_h($name) . '" value="1">'
        . '<div class="hs-sec-toggle-control">'
        . hs_panel_status_badge($on, $t)
        . '<label class="hs-switch hs-switch-lg" aria-label="' . hs_h($on ? ($t['sec_state_on'] ?? 'On') : ($t['sec_state_off'] ?? 'Off')) . '">'
        . '<input type="checkbox"' . ($on ? ' checked' : '') . ' onchange="this.form.submit()">'
        . '<span class="hs-switch-ui"></span></label></div></form>';
}

/** @param list<array{0:string,1:string,2?:bool}> $detailRows */
function hs_panel_sec_card(string $title, string $icon, bool $on, string $toggleName, array $detailRows, array $t, ?string $footer = null): string
{
    $body = '';
    foreach ($detailRows as $row) {
        $val = $row[1];
        $cls = 'hp-status-val';
        if (array_key_exists(2, $row)) {
            $cls .= $row[2] ? ' hp-status-ok' : ' hp-status-off';
        }
        $body .= '<div class="hp-status-row"><span>' . hs_h($row[0]) . '</span><span class="' . $cls . '">' . $val . '</span></div>';
    }
    $html = '<section class="hp-card hs-sec-card">'
        . '<div class="hs-sec-card-head">'
        . '<div class="hs-sec-card-title"><span class="hs-sec-card-icon"><i class="fa-solid ' . hs_h($icon) . '"></i></span>'
        . '<h2 class="hp-card-title" style="margin:0;border:0;padding:0">' . hs_h($title) . '</h2></div>'
        . hs_panel_toggle_switch($toggleName, $on, $t)
        . '</div><div class="hp-card-body">' . $body . '</div>';
    if ($footer !== null && $footer !== '') {
        $html .= '<div class="hp-card-foot">' . $footer . '</div>';
    }
    return $html . '</section>';
}