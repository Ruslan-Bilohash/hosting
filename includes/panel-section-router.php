<?php
declare(strict_types=1);

/** @var string $hs_section */
/** @var string $hs_section_script */
/** @var string|null $hs_section_default_tab */

require_once dirname(__DIR__) . '/includes/panel-bootstrap.php';

if ($hs_section === 'websites' && ($_GET['tab'] ?? '') === 'support') {
    header('Location: ' . hs_url(hs_panel_path('support.php')), true, 301);
    exit;
}

if ($hs_section === 'advanced' && ($_GET['tab'] ?? '') === 'ai') {
    header('Location: ' . hs_url(hs_panel_tab_href('api', 'overview')), true, 301);
    exit;
}

if ($hs_section === 'api' && in_array($_GET['tab'] ?? '', ['ai'], true)) {
    header('Location: ' . hs_url(hs_panel_tab_href('api', 'overview')), true, 301);
    exit;
}

if ($hs_section === 'websites' && ($_GET['tab'] ?? '') === 'dev') {
    header('Location: ' . hs_url(hs_panel_tab_href('websites', 'overview')), true, 301);
    exit;
}

require_once dirname(__DIR__) . '/includes/panel-tabs.php';
require_once dirname(__DIR__) . '/includes/panel-features.php';
require_once dirname(__DIR__) . '/includes/panel-section-content.php';

$tab = hs_panel_tab_id($hs_section, $_GET['tab'] ?? $hs_section_default_tab ?? null);
$panel_active = hs_panel_tab_nav_key($hs_section, $tab);
$page_title = $t['nav_' . $hs_section] ?? ($t['nav_' . str_replace('-', '_', $hs_section)] ?? ucfirst($hs_section));
foreach (hs_panel_section_tabs($hs_section) as $ti) {
    if ($ti['id'] === $tab) {
        $page_title = $t[$ti['label_key']] ?? $page_title;
        break;
    }
}
$panel_tip_key = $hs_section;
if ($hs_section === 'performance') {
    $GLOBALS['panel_perf_mode'] = true;
    if ($tab === 'speed') {
        $GLOBALS['panel_speed_mode'] = true;
    }
}
if ($hs_section === 'domains' && in_array($tab, ['overview', 'register'], true)) {
    $GLOBALS['panel_domains_mode'] = true;
    $GLOBALS['panel_domains_pending_mode'] = true;
}
if ($hs_section === 'databases' && $tab === 'manage') {
    $GLOBALS['panel_databases_mode'] = true;
}

$error = '';
$success = '';
$userId = (string) $user['id'];

if ($hs_section === 'domains') {
    require_once dirname(__DIR__) . '/includes/domain-orders.php';
    hs_domain_orders_poll_user($userId);
    $hs_user_settings = hs_user_settings_get($userId);
    hs_user_domain_registry_ensure($userId, $hs_user_settings);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handled = false;
    $featureSections = ['performance', 'security', 'websites', 'files', 'advanced', 'api', 'wordpress'];
    if ($hs_section === 'files' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])) {
        $res = hs_panel_handle_post('files', $userId, $user, $t);
        $error = $res['error'];
        $success = $res['success'];
        $hs_user_settings = hs_user_settings_get($userId);
        $handled = true;
    }
    if (!$handled && in_array($hs_section, $featureSections, true)) {
        $res = hs_panel_handle_post($hs_section, $userId, $user, $t);
        $error = $res['error'];
        $success = $res['success'];
        if ($res['refresh']) {
            $hs_sites = hs_sites_for_user($userId);
        }
        $hs_user_settings = hs_user_settings_get($userId);
        $handled = true;
    }

    if ($hs_section === 'domains') {
        if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
            $error = $t['register_error_csrf'] ?? '';
        } elseif (isset($_POST['save_primary'])) {
            $primary = trim((string) ($_POST['primary_domain'] ?? ''));
            if ($primary !== '' && preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i', $primary)
                && hs_user_settings_save($userId, ['primary_domain' => strtolower($primary)])) {
                $hs_user_settings = hs_user_settings_get($userId);
                hs_user_domain_registry_sync($userId, $hs_user_settings);
                $hs_user_settings = hs_user_settings_get($userId);
                $success = $t['btn_save'] ?? 'Saved';
                hs_panel_log($userId, 'domain_primary', strtolower($primary));
            } else {
                $error = 'Invalid domain';
            }
        } elseif (isset($_POST['add_subdomain'])) {
            $sub = trim((string) ($_POST['subdomain'] ?? ''));
            $folder = trim((string) ($_POST['subdomain_folder'] ?? ''));
            if (hs_add_subdomain($userId, $sub, $folder)) {
                $hs_user_settings = hs_user_settings_get($userId);
                $success = $t['subdomain_added'] ?? $t['btn_add_domain'] ?? 'Added';
                hs_panel_log($userId, 'subdomain_add', $sub . ($folder !== '' ? ' → ' . $folder : ''));
            } else {
                $error = $t['subdomain_add_error'] ?? 'Invalid or duplicate subdomain';
            }
        } elseif (isset($_POST['add_extra_domain'])) {
            $extra = strtolower(trim((string) ($_POST['extra_domain'] ?? '')));
            $settings = hs_user_settings_get($userId);
            $list = is_array($settings['extra_domains'] ?? null) ? $settings['extra_domains'] : [];
            if ($extra !== '' && preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $extra)
                && !in_array($extra, $list, true)) {
                $list[] = $extra;
                hs_user_settings_save($userId, ['extra_domains' => $list]);
                $hs_user_settings = hs_user_settings_get($userId);
                hs_user_domain_registry_sync($userId, $hs_user_settings);
                $hs_user_settings = hs_user_settings_get($userId);
                $hs_domain_choices = hs_user_domain_choices($hs_user_settings);
                require_once dirname(__DIR__) . '/includes/domain-workspace.php';
                hs_domain_auto_bind_site($user, $extra, false);
                $success = $t['btn_add_domain'] ?? 'Added';
                hs_panel_log($userId, 'extra_domain_add', $extra);
            } else {
                $error = 'Invalid or duplicate domain';
            }
        } elseif (isset($_POST['save_domain_folder'])) {
            require_once dirname(__DIR__) . '/includes/domain-workspace.php';
            $dom = strtolower(trim((string) ($_POST['domain'] ?? '')));
            $folder = trim((string) ($_POST['domain_folder'] ?? ''));
            $res = hs_domain_assign_folder($user, $dom, $folder);
            if (!empty($res['ok'])) {
                $hs_user_settings = hs_user_settings_get($userId);
                $success = str_replace(
                    ['{domain}', '{folder}'],
                    [$dom, 'public_html/' . (string) ($res['rel'] ?? '') . '/'],
                    $t['dom_folder_saved'] ?? 'Domain {domain} → folder {folder}'
                );
                hs_panel_log($userId, 'domain_folder', $dom . ' → ' . ($res['rel'] ?? ''));
            } else {
                $error = match ($res['error'] ?? '') {
                    'forbidden' => $t['dom_folder_forbidden'] ?? 'Folder must be under your account.',
                    'domain' => $t['dom_folder_bad_domain'] ?? 'Invalid domain.',
                    default => $t['dom_folder_save_fail'] ?? 'Could not save domain folder.',
                };
            }
        } elseif (isset($_POST['add_redirect'])) {
            $res = hs_panel_handle_post('domains', $userId, $user, $t);
            $error = $res['error'];
            $success = $res['success'];
            $hs_user_settings = hs_user_settings_get($userId);
        } elseif (isset($_POST['add_dns'])) {
            $res = hs_dns_save_record(
                $userId,
                (string) ($_POST['dns_type'] ?? 'A'),
                (string) ($_POST['dns_host'] ?? ''),
                (string) ($_POST['dns_value'] ?? ''),
                (int) ($_POST['dns_ttl'] ?? 3600),
                (int) ($_POST['dns_priority'] ?? 10)
            );
            if ($res['ok']) {
                $hs_user_settings = hs_user_settings_get($userId);
                $success = $t['dns_added'] ?? 'DNS record added';
            } else {
                $error = $t['dns_invalid'] ?? 'Invalid DNS record';
            }
        } elseif (isset($_POST['edit_dns'])) {
            $res = hs_dns_update_record(
                $userId,
                (int) ($_POST['dns_index'] ?? -1),
                (string) ($_POST['dns_type'] ?? 'A'),
                (string) ($_POST['dns_host'] ?? ''),
                (string) ($_POST['dns_value'] ?? ''),
                (int) ($_POST['dns_ttl'] ?? 3600),
                (int) ($_POST['dns_priority'] ?? 10)
            );
            if ($res['ok']) {
                $hs_user_settings = hs_user_settings_get($userId);
                $success = $t['dns_updated'] ?? 'DNS record updated';
            } else {
                $error = $t['dns_invalid'] ?? 'Invalid DNS record';
            }
        } elseif (isset($_POST['delete_dns'])) {
            if (hs_dns_delete_record($userId, (int) ($_POST['dns_index'] ?? -1))) {
                $hs_user_settings = hs_user_settings_get($userId);
                $success = $t['dns_deleted'] ?? 'DNS record deleted';
            } else {
                $error = $t['dns_delete_fail'] ?? 'Could not delete record';
            }
        } elseif (isset($_POST['delete_domain'])) {
            $dom = strtolower(trim((string) ($_POST['domain_name'] ?? '')));
            $res = hs_domain_remove($userId, $dom);
            if (!empty($res['ok'])) {
                $hs_user_settings = hs_user_settings_get($userId);
                $hs_domain_choices = hs_user_domain_choices($hs_user_settings);
                $hs_active_domain = hs_active_domain($hs_user_settings);
                $success = str_replace('{domain}', $dom, $t['dom_delete_success'] ?? 'Domain removed');
                hs_panel_log($userId, 'domain_delete', $dom);
            } else {
                $error = match ($res['error'] ?? '') {
                    'pending' => $t['dom_delete_blocked_pending'] ?? 'Registration in progress',
                    'purchased_active' => str_replace(
                        ['{date}', '{days}'],
                        [
                            ($res['expires_at'] ?? '') !== '' ? hs_format_date((string) $res['expires_at']) : '—',
                            (string) max(0, (int) ($res['days'] ?? 0)),
                        ],
                        $t['dom_delete_blocked_purchased'] ?? 'Cannot delete purchased domain before expiry'
                    ),
                    'not_found' => $t['dom_delete_not_found'] ?? 'Domain not found',
                    default => $t['dom_delete_fail'] ?? 'Could not delete domain',
                };
            }
        } elseif (isset($_POST['order_domain'])) {
            $domain = trim((string) ($_POST['domain'] ?? ''));
            $res = hs_domain_order_create($userId, $domain, $user);
            if (!empty($res['ok'])) {
                $hs_user_settings = hs_user_settings_get($userId);
                $success = $t['dom_register_success'] ?? 'Domain order submitted';
                hs_panel_log($userId, 'domain_order', (string) ($res['order']['domain'] ?? $domain));
                require_once dirname(__DIR__) . '/includes/order-notifications.php';
                hs_notify_order_event('domain_ordered', $user, [
                    'domain' => (string) ($res['order']['domain'] ?? $domain),
                    'price_nok' => (float) ($res['order']['price'] ?? 0),
                    'lang' => $lang,
                ]);
            } else {
                $error = match ($res['error'] ?? '') {
                    'taken', 'unavailable' => $t['dom_register_error_taken'] ?? 'Domain is not available',
                    'duplicate' => $t['dom_register_error_pending'] ?? 'You already have a pending order for this domain',
                    default => $t['dom_register_error_invalid'] ?? 'Could not submit order',
                };
            }
        } elseif (isset($_POST['save_registrant'])) {
            require_once dirname(__DIR__) . '/includes/panel-registrant.php';
            $res = hs_panel_registrant_save_from_post($user, $_POST);
            if (!empty($res['ok'])) {
                $user = hs_user_by_id($userId) ?? $user;
                $hs_user_settings = hs_user_settings_get($userId);
                $success = $t['dom_registrant_saved'] ?? 'Domain owner contacts saved';
                hs_panel_log($userId, 'registrant_save', (string) ($user['email'] ?? ''));
            } else {
                $error = match ($res['error'] ?? '') {
                    'incomplete' => $t['dom_registrant_required'] ?? 'Fill in all required contact fields',
                    'country' => $t['register_error_invalid_country'] ?? 'Invalid country',
                    default => $t['dom_registrant_save_fail'] ?? 'Could not save contacts',
                };
            }
        }
        $handled = true;
    }

    if ($hs_section === 'databases' && !$handled) {
        if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
            $error = $t['register_error_csrf'] ?? '';
        } elseif (isset($_POST['create_db'])) {
            $dbLabel = trim((string) ($_POST['db_label'] ?? ''));
            $dbWebsite = trim((string) ($_POST['db_website'] ?? ''));
            $dbRes = hs_create_database(
                $userId,
                (string) ($user['username'] ?? 'user'),
                $user,
                $dbLabel !== '' ? $dbLabel : null,
                $dbWebsite !== '' ? $dbWebsite : null
            );
            if (!empty($dbRes['ok'])) {
                $hs_user_settings = hs_user_settings_get($userId);
                $success = !empty($dbRes['entry']['provisioned'])
                    ? ($t['db_created_real'] ?? $t['db_created'] ?? 'Created')
                    : ($t['db_created'] ?? 'Created');
            } else {
                $error = match ($dbRes['error'] ?? '') {
                    'limit' => $t['db_limit'] ?? 'Database limit reached for your plan.',
                    'save' => $t['db_save_error'] ?? 'Could not save database record.',
                    'provision_config' => $t['db_provision_config'] ?? 'Server MySQL provisioning is not configured.',
                    default => (string) ($dbRes['error'] ?? $t['db_create_error'] ?? 'Could not create database.'),
                };
            }
        } elseif (isset($_POST['delete_db'])) {
            $dbId = trim((string) ($_POST['db_id'] ?? ''));
            $delRes = hs_delete_database($userId, $dbId, $user);
            if (!empty($delRes['ok'])) {
                $hs_user_settings = hs_user_settings_get($userId);
                $success = $t['db_deleted'] ?? 'Database deleted';
            } else {
                $error = match ($delRes['error'] ?? '') {
                    'primary' => $t['db_delete_primary'] ?? 'Primary database cannot be deleted.',
                    'shared' => $t['db_delete_shared'] ?? 'Shared database cannot be deleted.',
                    'demo' => $t['db_delete_demo'] ?? 'Demo account cannot delete databases.',
                    'not_found' => $t['db_delete_fail'] ?? 'Could not delete database.',
                    'save' => $t['db_save_error'] ?? 'Could not save settings.',
                    default => $t['db_delete_fail'] ?? 'Could not delete database.',
                };
            }
        } elseif (isset($_POST['toggle_db_remote']) || isset($_POST['save_db_remote'])) {
            $res = hs_panel_handle_post('databases', $userId, $user, $t);
            $error = $res['error'];
            $success = $res['success'];
            $hs_user_settings = hs_user_settings_get($userId);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($userId)) {
    if (!isset($_SESSION['panel_visits']) || !is_array($_SESSION['panel_visits'])) {
        $_SESSION['panel_visits'] = [];
    }
    $visitKey = $hs_section . ':' . $tab;
    $last = (int) ($_SESSION['panel_visits'][$visitKey] ?? 0);
    if (time() - $last >= 120) {
        hs_panel_log($userId, 'panel_visit', $hs_section . '/' . $tab);
        $_SESSION['panel_visits'][$visitKey] = time();
    }
}

if ($hs_section === 'security' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once dirname(__DIR__) . '/includes/security-panel.php';
    hs_sec_sync_htaccess($user, $hs_user_settings);
}

// Flash messages after PRG (delete / install)
if ($success === '' && $error === '') {
    if (isset($_GET['deleted'])) {
        $success = (string) ($t['site_deleted'] ?? 'Website deleted.');
    } elseif (isset($_GET['installed'])) {
        $pathHint = trim((string) ($_GET['path'] ?? ''));
        $success = (string) ($t['installer_success'] ?? 'Installed');
        if ($pathHint !== '') {
            $success .= ' → ' . $pathHint;
        }
    }
}
// Always reload sites after websites actions (avoid stale empty list)
if ($hs_section === 'websites') {
    $hs_sites = hs_sites_for_user($userId);
}

$ctx = [
    'user' => $user,
    't' => $t,
    'hs_user_settings' => $hs_user_settings,
    'hs_sites' => $hs_sites,
    'hs_resources' => $hs_resources,
    'hs_plan' => $hs_plan,
    'lang' => $lang,
    'error' => $error,
    'success' => $success,
];

ob_start();
echo hs_panel_render_tabs($hs_section, $tab, $t, $hs_section_script);
echo hs_panel_section_content($hs_section, $tab, $ctx);
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';