<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/domain-store.php';
require_once __DIR__ . '/user-settings.php';
require_once __DIR__ . '/plan-specs.php';
require_once __DIR__ . '/installer.php';

function hs_domain_orders_file(): string
{
    return hs_data_file('domain-orders');
}

/** @return list<array<string, mixed>> */
function hs_domain_orders(): array
{
    if (hs_is_mysql_installed()) {
        return array_values(array_filter(hs_db_load_collection('domain_orders'), 'is_array'));
    }
    $rows = hs_read_json(hs_domain_orders_file());
    return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
}

/** @param list<array<string, mixed>> $orders */
function hs_domain_orders_save(array $orders): bool
{
    if (hs_is_mysql_installed()) {
        return hs_db_save_collection('domain_orders', array_values($orders), 'id');
    }
    return hs_write_json(hs_domain_orders_file(), array_values($orders));
}

function hs_domain_order_by_id(string $id): ?array
{
    foreach (hs_domain_orders() as $order) {
        if ((string) ($order['id'] ?? '') === $id) {
            return $order;
        }
    }
    return null;
}

/** @return list<array<string, mixed>> */
function hs_domain_orders_for_user(string $userId): array
{
    $out = [];
    foreach (hs_domain_orders() as $order) {
        if ((string) ($order['user_id'] ?? '') === $userId) {
            $out[] = $order;
        }
    }
    usort($out, static fn(array $a, array $b): int => strcmp((string) ($b['ordered_at'] ?? ''), (string) ($a['ordered_at'] ?? '')));
    return $out;
}

/** @return list<array<string, mixed>> */
function hs_domain_orders_pending(): array
{
    $out = [];
    foreach (hs_domain_orders() as $order) {
        if (($order['status'] ?? '') === 'pending') {
            $out[] = $order;
        }
    }
    usort($out, static fn(array $a, array $b): int => strcmp((string) ($a['ordered_at'] ?? ''), (string) ($b['ordered_at'] ?? '')));
    return $out;
}

function hs_domain_order_pending_for_domain(string $domain): ?array
{
    $domain = strtolower(trim($domain));
    foreach (hs_domain_orders() as $order) {
        if (strtolower((string) ($order['domain'] ?? '')) === $domain && ($order['status'] ?? '') === 'pending') {
            return $order;
        }
    }
    return null;
}

function hs_domain_reserved_in_cms(string $domain): bool
{
    if (hs_domain_taken_in_cms($domain)) {
        return true;
    }
    return hs_domain_order_pending_for_domain($domain) !== null;
}

/** @return array{live:bool,method?:string} */
function hs_domain_propagation_live(string $domain): array
{
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return ['live' => false];
    }
    $full = $p['full'];
    $targetIp = (string) (hs_server_constants()['ip'] ?? '');

    if ($targetIp !== '') {
        $records = @dns_get_record($full, DNS_A);
        if (is_array($records)) {
            foreach ($records as $rec) {
                if (is_array($rec) && ($rec['ip'] ?? '') === $targetIp) {
                    return ['live' => true, 'method' => 'dns'];
                }
            }
        }
        $wwwRecords = @dns_get_record('www.' . $full, DNS_A);
        if (is_array($wwwRecords)) {
            foreach ($wwwRecords as $rec) {
                if (is_array($rec) && ($rec['ip'] ?? '') === $targetIp) {
                    return ['live' => true, 'method' => 'dns_www'];
                }
            }
        }
    }

    $ctx = stream_context_create([
        'http' => ['timeout' => 6, 'method' => 'HEAD', 'ignore_errors' => true],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    foreach (['https://' . $full . '/', 'http://' . $full . '/'] as $url) {
        $headers = @get_headers($url, true, $ctx);
        if (!is_array($headers) || !isset($headers[0])) {
            continue;
        }
        $status = (string) $headers[0];
        if (preg_match('/\s(200|301|302|303|307|308)\s/', $status) === 1) {
            return ['live' => true, 'method' => 'http'];
        }
    }

    return ['live' => false];
}

/**
 * True when client still must pay for this domain (not registered at Namecheap yet).
 *
 * @param array<string, mixed> $entry
 */
function hs_domain_entry_awaiting_payment(string $userId, array $entry): bool
{
    if (!empty($entry['purchased']) || !empty($entry['payment_confirmed']) || !empty($entry['registry_registered'])) {
        return false;
    }
    $domain = strtolower(trim((string) ($entry['domain'] ?? '')));
    if ($domain === '') {
        return false;
    }
    $hasPaidOrder = false;
    $hasUnpaidOrder = false;
    foreach (hs_domain_orders_for_user($userId) as $order) {
        if (strtolower((string) ($order['domain'] ?? '')) !== $domain) {
            continue;
        }
        $status = (string) ($order['status'] ?? '');
        if ($status === 'active' || !empty($order['payment_confirmed']) || !empty($order['registry_registered'])) {
            $hasPaidOrder = true;
            break;
        }
        if ($status === 'pending') {
            $hasUnpaidOrder = true;
        }
    }
    if ($hasPaidOrder) {
        return false;
    }
    if ($hasUnpaidOrder || !empty($entry['pending_payment'])) {
        return true;
    }
    // Primary/parked wish without a paid order (e.g. domain from register form).
    if (empty($entry['order_id']) && empty($entry['purchased'])) {
        return true;
    }

    return false;
}

/**
 * Normalize registry rows: unpaid domains must not look like Namecheap registration in progress.
 *
 * @param list<array<string,mixed>> $registry
 * @return list<array<string,mixed>>
 */
function hs_domain_registry_normalize_unpaid(string $userId, array $registry): array
{
    $out = [];
    foreach ($registry as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $domain = strtolower(trim((string) ($entry['domain'] ?? '')));
        if ($domain === '') {
            continue;
        }
        if (hs_domain_entry_awaiting_payment($userId, $entry)) {
            $entry['pending_payment'] = true;
            // Do not show “awaiting registration / DNS 48h” until payment is confirmed.
            unset($entry['pending_registration']);
            $entry['expires_at'] = '';
        } else {
            unset($entry['pending_payment']);
        }
        $out[] = $entry;
    }

    return $out;
}

/**
 * After successful payment for a domain:
 * 1) mark order paid
 * 2) register at Namecheap (when API works)
 * 3) create public_html/{user}/{domain}/
 * 4) bind domain → that folder + rebuild routes
 *
 * @param array<string, mixed> $user
 * @param array{price?:float,payment_provider?:string,payment_ref?:string,as_primary?:bool,skip_notify?:bool} $meta
 * @return array{ok:bool,order?:array<string,mixed>,registered?:bool,folder?:string,rel?:string,error?:string}
 */
function hs_domain_fulfill_paid(string $userId, string $domain, array $user, array $meta = []): array
{
    require_once __DIR__ . '/domain-workspace.php';
    require_once __DIR__ . '/domain-store.php';

    $p = hs_domain_parse($domain);
    if ($p === null || $userId === '') {
        return ['ok' => false, 'error' => 'invalid'];
    }
    $full = $p['full'];

    // 1) Create or reuse domain order, mark paid
    $orderRes = hs_domain_order_create($userId, $full, $user, true);
    if (empty($orderRes['ok']) || empty($orderRes['order'])) {
        // Reuse active/pending by domain
        $existing = null;
        foreach (hs_domain_orders_for_user($userId) as $o) {
            if (strtolower((string) ($o['domain'] ?? '')) === $full) {
                $existing = $o;
                break;
            }
        }
        if ($existing === null) {
            return ['ok' => false, 'error' => (string) ($orderRes['error'] ?? 'order')];
        }
        $orderRes = ['ok' => true, 'order' => $existing, 'reused' => true];
    }

    $order = $orderRes['order'];
    $orderId = (string) ($order['id'] ?? '');
    $orders = hs_domain_orders();
    $oi = null;
    foreach ($orders as $i => $ord) {
        if ((string) ($ord['id'] ?? '') === $orderId) {
            $oi = $i;
            break;
        }
    }
    if ($oi === null) {
        return ['ok' => false, 'error' => 'order_missing'];
    }

    $orders[$oi]['payment_confirmed'] = true;
    if (isset($meta['price']) && (float) $meta['price'] > 0) {
        $orders[$oi]['price'] = (float) $meta['price'];
    }
    if (!empty($meta['payment_provider'])) {
        $orders[$oi]['payment_provider'] = (string) $meta['payment_provider'];
        $orders[$oi]['payment_ref'] = (string) ($meta['payment_ref'] ?? '');
    }
    // Registry row: paid / awaiting Namecheap
    hs_domain_registry_add_pending($userId, $full, $orderId, true);

    // 2) Register at Namecheap
    hs_domain_try_registry_register($userId, $full, $user, $orders[$oi]);
    $registered = !empty($orders[$oi]['registry_registered']);
    $registryError = (string) ($orders[$oi]['registry_error'] ?? '');

    // 3–4) Always create domain folder + bind (even if Namecheap failed — client can still host files)
    $settings = hs_user_settings_get($userId);
    $hasPrimary = trim((string) ($settings['primary_domain'] ?? '')) !== ''
        && !(function_exists('hs_domain_is_host_brand') && hs_domain_is_host_brand((string) $settings['primary_domain']));
    $asPrimary = array_key_exists('as_primary', $meta)
        ? !empty($meta['as_primary'])
        : !$hasPrimary;

    $folderKey = hs_domain_folder_name($full);
    if ($folderKey === '') {
        $folderKey = 'site';
    }
    hs_domain_roots_save($userId, $full, $folderKey);

    if (!hs_domain_bind_to_user($userId, $full, $asPrimary, true)) {
        $fresh = hs_user_by_id($userId) ?? $user;
        hs_domain_auto_bind_site($fresh, $full, false);
    }
    // Re-assert domain-named folder after bind
    hs_domain_roots_save($userId, $full, $folderKey);
    $fresh = hs_user_by_id($userId) ?? $user;
    hs_domain_auto_bind_site($fresh, $full, false);

    $fresh = hs_user_by_id($userId) ?? $user;
    $rel = hs_domain_docroot_rel($fresh, $full);
    $path = hs_public_path($rel);
    hs_domain_seed_docroot_files($fresh, $full, $path);

    // 5) Persist order status
    if ($registered) {
        $orders[$oi]['status'] = 'active';
        $orders[$oi]['activated_at'] = gmdate('c');
        $orders[$oi]['last_check_live'] = true;
        $orders[$oi]['last_check_at'] = gmdate('c');
        hs_domain_orders_save($orders);
        hs_domain_registry_activate_entry($userId, $full);
        $settings = hs_user_settings_get($userId);
        if (function_exists('hs_user_domain_registry_sync')) {
            hs_user_domain_registry_sync($userId, $settings);
        }
    } else {
        hs_domain_orders_save($orders);
    }

    hs_rebuild_global_domain_routes();

    return [
        'ok' => true,
        'order' => $orders[$oi],
        'registered' => $registered,
        'folder' => $folderKey,
        'rel' => $rel,
        'registry_error' => $registryError,
    ];
}

/**
 * After client payment: register domain at Namecheap (or mark local when API not configured).
 *
 * @param array<string, mixed> $user
 * @param array<string, mixed> $order by-ref row from domain-orders
 */
function hs_domain_try_registry_register(string $userId, string $domain, array $user, array &$order): void
{
    require_once __DIR__ . '/providers/namecheap-api.php';
    $order['registry_at'] = gmdate('c');
    $res = hs_namecheap_register_for_user($userId, $domain, $user);
    if (!empty($res['ok']) && empty($res['error'])) {
        if (!empty($res['skipped'])) {
            // Namecheap not configured — local bind only (dev/demo).
            $order['registry_registered'] = true;
            $order['registry_source'] = (string) ($res['source'] ?? 'local');
            $order['registry_skipped'] = true;
        } else {
            $order['registry_registered'] = true;
            $order['registry_source'] = (string) ($res['source'] ?? 'namecheap');
            if (isset($res['charged'])) {
                $order['registry_charged'] = $res['charged'];
            }
        }
        unset($order['registry_error']);
        return;
    }
    $order['registry_registered'] = false;
    $order['registry_error'] = (string) ($res['error'] ?? 'register_failed');
    if (!empty($res['errors']) && is_array($res['errors'])) {
        $order['registry_errors'] = $res['errors'];
    }
}

/** @return 'pending_payment'|'pending_registration'|'active'|'expiring'|'expired' */
function hs_domain_registry_display_status(array $entry, string $userId = ''): string
{
    if ($userId !== '' && hs_domain_entry_awaiting_payment($userId, $entry)) {
        return 'pending_payment';
    }
    if (!empty($entry['pending_payment']) && empty($entry['purchased']) && empty($entry['payment_confirmed'])) {
        return 'pending_payment';
    }
    if (!empty($entry['pending_registration'])) {
        return 'pending_registration';
    }

    return hs_domain_registry_status((string) ($entry['expires_at'] ?? ''));
}

function hs_domain_registry_add_pending(string $userId, string $domain, string $orderId, bool $paid = false): void
{
    $settings = hs_user_settings_get($userId);
    $registry = hs_user_domain_registry_ensure($userId, $settings);
    $domain = strtolower(trim($domain));
    foreach ($registry as &$entry) {
        if (!is_array($entry)) {
            continue;
        }
        if (strtolower((string) ($entry['domain'] ?? '')) !== $domain) {
            continue;
        }
        $entry['order_id'] = $orderId;
        if ($paid) {
            $entry['pending_registration'] = true;
            $entry['payment_confirmed'] = true;
            unset($entry['pending_payment']);
        } else {
            $entry['pending_payment'] = true;
            unset($entry['pending_registration']);
        }
        hs_user_settings_save($userId, ['domain_registry' => $registry]);

        return;
    }
    unset($entry);
    $row = [
        'domain' => $domain,
        'role' => 'primary',
        'registered_at' => gmdate('c'),
        'expires_at' => '',
        'order_id' => $orderId,
    ];
    if ($paid) {
        $row['pending_registration'] = true;
        $row['payment_confirmed'] = true;
    } else {
        $row['pending_payment'] = true;
    }
    $registry[] = $row;
    hs_user_settings_save($userId, ['domain_registry' => $registry]);
}

function hs_domain_registry_activate_entry(string $userId, string $domain): void
{
    $settings = hs_user_settings_get($userId);
    $registry = hs_user_domain_registry_ensure($userId, $settings);
    $domain = strtolower(trim($domain));
    $now = gmdate('c');
    $expires = gmdate('c', strtotime('+1 year'));
    $changed = false;
    foreach ($registry as &$entry) {
        if (!is_array($entry)) {
            continue;
        }
        if (strtolower((string) ($entry['domain'] ?? '')) !== $domain) {
            continue;
        }
        $entry['purchased'] = true;
        unset($entry['pending_registration']);
        $entry['registered_at'] = $entry['registered_at'] ?? $now;
        $entry['expires_at'] = $expires;
        $entry['role'] = 'primary';
        $changed = true;
    }
    unset($entry);
    if ($changed) {
        hs_user_settings_save($userId, ['domain_registry' => $registry]);
    }
}

/**
 * @param bool $fromPayment when true (post-checkout), skip availability re-check and mark paid path
 * @return array{ok:bool,order?:array<string,mixed>,error?:string}
 */
function hs_domain_order_create(string $userId, string $domain, array $user, bool $fromPayment = false): array
{
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return ['ok' => false, 'error' => 'invalid'];
    }
    $full = $p['full'];

    if (!$fromPayment && hs_domain_reserved_in_cms($full)) {
        // Allow same user re-submit
        $own = false;
        foreach (hs_domain_orders_for_user($userId) as $existing) {
            if (strtolower((string) ($existing['domain'] ?? '')) === $full) {
                $own = true;
                break;
            }
        }
        if (!$own) {
            return ['ok' => false, 'error' => 'taken'];
        }
    }

    if (!$fromPayment) {
        $check = hs_domain_check_availability($full);
        if (!$check['ok'] || empty($check['available'])) {
            return ['ok' => false, 'error' => 'unavailable'];
        }
    }

    foreach (hs_domain_orders_for_user($userId) as $existing) {
        if (strtolower((string) ($existing['domain'] ?? '')) === $full && ($existing['status'] ?? '') === 'pending') {
            // After payment, reuse unpaid pending order instead of failing.
            if ($fromPayment) {
                return ['ok' => true, 'order' => $existing, 'reused' => true];
            }

            return ['ok' => false, 'error' => 'duplicate', 'order' => $existing];
        }
    }

    $price = hs_domain_price($full);
    $order = [
        'id' => hs_new_id('do'),
        'user_id' => $userId,
        'username' => (string) ($user['username'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'domain' => $full,
        'price' => $price,
        'status' => 'pending',
        'ordered_at' => gmdate('c'),
        'activated_at' => null,
        'last_check_at' => null,
        'last_check_live' => false,
        'payment_confirmed' => $fromPayment,
    ];

    $orders = hs_domain_orders();
    $orders[] = $order;
    if (!hs_domain_orders_save($orders)) {
        return ['ok' => false, 'error' => 'save'];
    }

    hs_domain_registry_add_pending($userId, $full, (string) $order['id'], $fromPayment);

    return ['ok' => true, 'order' => $order];
}

/** @return array{ok:bool,activated?:bool,order?:array<string,mixed>,error?:string} */
function hs_domain_order_activate(string $orderId): array
{
    $orders = hs_domain_orders();
    $idx = null;
    foreach ($orders as $i => $order) {
        if ((string) ($order['id'] ?? '') === $orderId) {
            $idx = $i;
            break;
        }
    }
    if ($idx === null) {
        return ['ok' => false, 'error' => 'not_found'];
    }

    $order = $orders[$idx];
    if (($order['status'] ?? '') !== 'pending') {
        return ['ok' => true, 'activated' => false, 'order' => $order];
    }

    $userId = (string) ($order['user_id'] ?? '');
    $domain = (string) ($order['domain'] ?? '');
    $user = hs_user_by_id($userId);
    if ($user === null || $domain === '') {
        return ['ok' => false, 'error' => 'user'];
    }

    // Paid domain: folder + bind + routes (Namecheap already done before activate when via fulfill)
    if (function_exists('hs_domain_fulfill_paid') && empty($order['payment_confirmed'])) {
        // Should not happen on normal path; still ensure folder
    }
    require_once __DIR__ . '/domain-workspace.php';
    $settings = hs_user_settings_get($userId);
    $hasPrimary = trim((string) ($settings['primary_domain'] ?? '')) !== '';
    $folderKey = hs_domain_folder_name($domain);
    if ($folderKey !== '') {
        hs_domain_roots_save($userId, $domain, $folderKey);
    }
    if (!hs_domain_bind_to_user($userId, $domain, !$hasPrimary, true)) {
        return ['ok' => false, 'error' => 'bind'];
    }
    $user = hs_user_by_id($userId) ?? $user;
    hs_domain_auto_bind_site($user, $domain, false);

    hs_domain_registry_activate_entry($userId, $domain);
    $settings = hs_user_settings_get($userId);
    hs_user_domain_registry_sync($userId, $settings);

    hs_ensure_user_workspace($user);

    $orders[$idx]['status'] = 'active';
    $orders[$idx]['activated_at'] = gmdate('c');
    $orders[$idx]['last_check_live'] = true;
    $orders[$idx]['last_check_at'] = gmdate('c');
    if (!hs_domain_orders_save($orders)) {
        return ['ok' => false, 'error' => 'save'];
    }

    $activatedUser = hs_user_by_id($userId);
    if ($activatedUser !== null) {
        require_once __DIR__ . '/order-notifications.php';
        hs_notify_order_event('domain_activated', $activatedUser, [
            'domain' => $domain,
            'price_nok' => (float) ($orders[$idx]['price'] ?? 0),
            'lang' => 'uk',
        ]);
    }

    return ['ok' => true, 'activated' => true, 'order' => $orders[$idx]];
}

/** @return list<array<string,mixed>> */
function hs_domain_orders_poll_user(string $userId): array
{
    $updated = [];
    foreach (hs_domain_orders_for_user($userId) as $order) {
        if (($order['status'] ?? '') !== 'pending') {
            continue;
        }
        $orderId = (string) ($order['id'] ?? '');
        $domain = (string) ($order['domain'] ?? '');
        if ($orderId === '' || $domain === '') {
            continue;
        }

        $live = hs_domain_propagation_live($domain);
        $orders = hs_domain_orders();
        foreach ($orders as $i => $row) {
            if ((string) ($row['id'] ?? '') !== $orderId) {
                continue;
            }
            $orders[$i]['last_check_at'] = gmdate('c');
            $orders[$i]['last_check_live'] = !empty($live['live']);
            hs_domain_orders_save($orders);
            break;
        }

        if (!empty($live['live'])) {
            $res = hs_domain_order_activate($orderId);
            if (!empty($res['activated']) && !empty($res['order'])) {
                $updated[] = $res['order'];
            }
        }
    }
    return $updated;
}