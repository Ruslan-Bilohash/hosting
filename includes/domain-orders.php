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
    $rows = hs_read_json(hs_domain_orders_file());
    return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
}

/** @param list<array<string, mixed>> $orders */
function hs_domain_orders_save(array $orders): bool
{
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

/** @return 'pending_registration'|'active'|'expiring'|'expired' */
function hs_domain_registry_display_status(array $entry): string
{
    if (!empty($entry['pending_registration'])) {
        return 'pending_registration';
    }
    return hs_domain_registry_status((string) ($entry['expires_at'] ?? ''));
}

function hs_domain_registry_add_pending(string $userId, string $domain, string $orderId): void
{
    $settings = hs_user_settings_get($userId);
    $registry = hs_user_domain_registry_ensure($userId, $settings);
    $domain = strtolower(trim($domain));
    foreach ($registry as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        if (strtolower((string) ($entry['domain'] ?? '')) === $domain) {
            return;
        }
    }
    $registry[] = [
        'domain' => $domain,
        'role' => 'primary',
        'registered_at' => gmdate('c'),
        'expires_at' => '',
        'pending_registration' => true,
        'order_id' => $orderId,
    ];
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

/** @return array{ok:bool,order?:array<string,mixed>,error?:string} */
function hs_domain_order_create(string $userId, string $domain, array $user): array
{
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return ['ok' => false, 'error' => 'invalid'];
    }
    $full = $p['full'];

    if (hs_domain_reserved_in_cms($full)) {
        return ['ok' => false, 'error' => 'taken'];
    }

    $check = hs_domain_check_availability($full);
    if (!$check['ok'] || empty($check['available'])) {
        return ['ok' => false, 'error' => 'unavailable'];
    }

    foreach (hs_domain_orders_for_user($userId) as $existing) {
        if (strtolower((string) ($existing['domain'] ?? '')) === $full && ($existing['status'] ?? '') === 'pending') {
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
    ];

    $orders = hs_domain_orders();
    $orders[] = $order;
    if (!hs_domain_orders_save($orders)) {
        return ['ok' => false, 'error' => 'save'];
    }

    hs_domain_registry_add_pending($userId, $full, (string) $order['id']);

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

    $settings = hs_user_settings_get($userId);
    $hasPrimary = trim((string) ($settings['primary_domain'] ?? '')) !== '';
    if (!hs_domain_bind_to_user($userId, $domain, !$hasPrimary, true)) {
        return ['ok' => false, 'error' => 'bind'];
    }

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