<?php
declare(strict_types=1);

require_once __DIR__ . '/domain-store.php';

/** @return list<string> */
function hs_domain_cart_normalize_list(array|string $raw): array
{
    if (is_string($raw)) {
        $raw = array_map('trim', explode(',', $raw));
    }
    $out = [];
    $seen = [];
    foreach ($raw as $item) {
        $d = hs_domain_normalize((string) $item);
        if ($d === null || isset($seen[$d])) {
            continue;
        }
        $seen[$d] = true;
        $out[] = $d;
    }

    return $out;
}

/** @return list<string> */
function hs_domain_cart_list(): array
{
    require_once __DIR__ . '/security.php';
    hs_session_start();
    $raw = $_SESSION['hs_domain_cart'] ?? [];
    if (!is_array($raw)) {
        return [];
    }

    return hs_domain_cart_normalize_list($raw);
}

/** @param list<string> $domains */
function hs_domain_cart_set(array $domains): void
{
    require_once __DIR__ . '/security.php';
    hs_session_start();
    $domains = hs_domain_cart_normalize_list($domains);
    $_SESSION['hs_domain_cart'] = $domains;
    $_SESSION['hs_pending_domain'] = $domains[0] ?? null;
    if ($domains === []) {
        unset($_SESSION['hs_pending_domain']);
    }
}

function hs_domain_cart_clear(): void
{
    require_once __DIR__ . '/security.php';
    hs_session_start();
    unset($_SESSION['hs_domain_cart'], $_SESSION['hs_pending_domain']);
}

/** @param list<string> $domains */
function hs_domains_total_price_eur(array $domains): float
{
    $sum = 0.0;
    foreach ($domains as $domain) {
        $sum += (float) (hs_domain_price($domain) ?? 0);
    }

    return round($sum, 2);
}

/** Customer-facing domain total at checkout (EUR). Free when bundled with paid hosting. */
function hs_checkout_domain_price_eur(array $user): float
{
    require_once __DIR__ . '/order-types.php';
    $domains = hs_user_pending_domains($user);
    if ($domains === []) {
        return 0.0;
    }
    $orderType = hs_user_order_type($user);
    if (hs_order_includes_hosting($orderType) && hs_order_includes_domain($user)) {
        return 0.0;
    }

    return hs_domains_total_price_eur($domains);
}

/** @param array<string, mixed> $user @return list<string> */
function hs_user_pending_domains(array $user): array
{
    $list = $user['pending_domains'] ?? null;
    if (is_array($list) && $list !== []) {
        return hs_domain_cart_normalize_list($list);
    }
    $single = trim((string) ($user['pending_domain'] ?? ''));
    if ($single !== '') {
        $norm = hs_domain_normalize($single);

        return $norm !== null ? [$norm] : [];
    }

    return [];
}

/**
 * Domains from session cart (public domain search) + user pending fields.
 *
 * @param array<string, mixed> $user
 * @return list<string>
 */
function hs_user_pending_domains_with_session(array $user): array
{
    require_once __DIR__ . '/security.php';
    hs_session_start();
    $merged = array_merge(hs_user_pending_domains($user), hs_domain_cart_list());
    $single = trim((string) ($_SESSION['hs_pending_domain'] ?? ''));
    if ($single !== '') {
        $merged[] = $single;
    }

    return hs_domain_cart_normalize_list($merged);
}

/**
 * Persist session domain cart onto a pending user so domain-only checkout works.
 *
 * @param array<string, mixed> $user
 * @param list<string> $extraDomains optional domains typed on activate form
 * @return array<string, mixed> updated user (or original if nothing changed / not pending)
 */
function hs_user_sync_pending_domains(array $user, array $extraDomains = []): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return $user;
    }
    if ((string) ($user['subscription_status'] ?? 'active') !== 'pending') {
        return $user;
    }

    $merged = hs_user_pending_domains_with_session($user);
    foreach ($extraDomains as $d) {
        $merged[] = (string) $d;
    }
    $merged = hs_domain_cart_normalize_list($merged);
    $current = hs_user_pending_domains($user);
    if ($merged === $current) {
        if ($merged !== []) {
            hs_domain_cart_set($merged);
        }

        return $user;
    }

    $ok = hs_user_update($userId, static function (array &$u) use ($merged): void {
        $u['pending_domains'] = $merged;
        $u['pending_domain'] = $merged[0] ?? '';
    });
    if (!$ok) {
        return $user;
    }
    if ($merged !== []) {
        hs_domain_cart_set($merged);
    } else {
        hs_domain_cart_clear();
    }
    $fresh = hs_user_by_id($userId);

    return is_array($fresh) ? $fresh : $user;
}

/**
 * Unpaid domains for an active client (registry / pending orders).
 *
 * @param array<string, mixed> $user
 * @return list<string>
 */
function hs_user_unpaid_domains(array $user): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return [];
    }
    require_once __DIR__ . '/domain-orders.php';
    require_once __DIR__ . '/user-settings.php';
    if (!function_exists('hs_user_domain_registry_ensure')) {
        require_once __DIR__ . '/panel-domains.php';
    }

    $out = [];
    $settings = hs_user_settings_get($userId);
    $registry = hs_user_domain_registry_ensure($userId, $settings);
    foreach ($registry as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        if (!hs_domain_entry_awaiting_payment($userId, $entry)) {
            continue;
        }
        $d = strtolower(trim((string) ($entry['domain'] ?? '')));
        if ($d !== '') {
            $out[] = $d;
        }
    }
    foreach (hs_domain_orders_for_user($userId) as $order) {
        if ((string) ($order['status'] ?? '') !== 'pending') {
            continue;
        }
        if (!empty($order['payment_confirmed']) || !empty($order['registry_registered'])) {
            continue;
        }
        $d = strtolower(trim((string) ($order['domain'] ?? '')));
        if ($d !== '') {
            $out[] = $d;
        }
    }

    return hs_domain_cart_normalize_list($out);
}

/**
 * True when active hosting client is paying for domain(s) only (not re-activating plan).
 *
 * @param array<string, mixed> $user
 */
function hs_panel_is_domain_purchase_mode(array $user): bool
{
    if ((string) ($user['subscription_status'] ?? '') !== 'active') {
        return false;
    }
    require_once __DIR__ . '/security.php';
    hs_session_start();
    if (!empty($_SESSION['hs_panel_domain_purchase'])) {
        return true;
    }
    $order = strtolower(trim((string) ($_GET['order'] ?? $_POST['order'] ?? '')));
    if ($order === 'domain') {
        return true;
    }
    if (!empty($_POST['panel_domain_purchase'])) {
        return true;
    }

    return false;
}

/**
 * Attach unpaid domain(s) for panel domain-only checkout on an active account.
 * Does not change plan or subscription_status.
 *
 * @param array<string, mixed> $user
 * @return array{user:array<string,mixed>,domains:list<string>,mode:bool}
 */
function hs_user_prepare_panel_domain_purchase(array $user, string $domainHint = ''): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '' || (string) ($user['subscription_status'] ?? '') !== 'active') {
        return ['user' => $user, 'domains' => [], 'mode' => false];
    }

    require_once __DIR__ . '/security.php';
    hs_session_start();

    $hint = hs_domain_normalize($domainHint);
    $unpaid = hs_user_unpaid_domains($user);
    $existing = hs_user_pending_domains($user);

    $domains = [];
    if ($hint !== null) {
        // Explicit domain from Pay button — allow even if not yet marked unpaid in registry.
        $domains = [$hint];
        // Prefer unpaid list when hint is among them (or alone).
        if ($unpaid !== [] && !in_array($hint, $unpaid, true)) {
            // New purchase not yet on registry — still allow checkout if domain parses.
            $domains = [$hint];
        }
    } elseif ($unpaid !== []) {
        $domains = $unpaid;
    } elseif ($existing !== []) {
        $domains = $existing;
    }

    $domains = hs_domain_cart_normalize_list($domains);
    if ($domains === []) {
        unset($_SESSION['hs_panel_domain_purchase']);

        return ['user' => $user, 'domains' => [], 'mode' => false];
    }

    $_SESSION['hs_panel_domain_purchase'] = 1;
    $current = hs_user_pending_domains($user);
    if ($current !== $domains) {
        $ok = hs_user_update($userId, static function (array &$u) use ($domains): void {
            $u['pending_domains'] = $domains;
            $u['pending_domain'] = $domains[0] ?? '';
        });
        if ($ok) {
            $fresh = hs_user_by_id($userId);
            if (is_array($fresh)) {
                $user = $fresh;
            }
        }
    }
    hs_domain_cart_set($domains);

    return ['user' => $user, 'domains' => $domains, 'mode' => true];
}

/** Clear panel domain-purchase session flag after payment or cancel. */
function hs_panel_domain_purchase_clear(): void
{
    require_once __DIR__ . '/security.php';
    hs_session_start();
    unset($_SESSION['hs_panel_domain_purchase']);
}

/** @param list<string> $domains */
function hs_render_domain_cart_picks(array $t, array $domains, array $opts = []): string
{
    if ($domains === []) {
        return '';
    }
    $changeable = !empty($opts['changeable']);
    $removable = !empty($opts['removable']);
    $label = (string) ($opts['label'] ?? (
        count($domains) > 1
            ? ($t['register_domains_selected'] ?? 'Your domains')
            : ($t['register_domain_selected'] ?? 'Your domain')
    ));
    $html = '<div class="hs-domain-cart-picks" data-hs-domain-cart-picks>';
    $html .= '<p class="hs-domain-cart-picks-label"><i class="fa-solid fa-globe" aria-hidden="true"></i> ' . hs_h($label) . '</p>';
    $html .= '<div class="hs-domain-cart-picks-list">';
    foreach ($domains as $domain) {
        $eur = (float) (hs_domain_price($domain) ?? 0);
        $price = hs_domain_format_price($eur, (string) ($opts['lang'] ?? 'en'));
        $html .= '<div class="hs-domain-cart-pick-row" data-domain="' . hs_h($domain) . '" data-price-eur="' . hs_h((string) round($eur, 2)) . '">';
        $html .= hs_render_domain_picked($domain, $t, [
            'label' => '',
            'price' => $price,
            'glow' => true,
            'status' => 'available',
            'changeable' => $changeable,
            'class' => 'hs-domain-picked--cart',
        ]);
        if ($removable) {
            $html .= '<button type="submit" class="hs-btn hs-btn-ghost hs-btn-sm hs-domain-cart-remove" name="remove_domain" value="'
                . hs_h($domain) . '" form="hs-panel-order-actions" title="'
                . hs_h($t['panel_order_remove_domain'] ?? 'Remove domain') . '">'
                . '<i class="fa-solid fa-xmark" aria-hidden="true"></i> '
                . hs_h($t['panel_order_remove'] ?? 'Remove') . '</button>';
        }
        $html .= '</div>';
    }
    $html .= '</div></div>';

    return $html;
}