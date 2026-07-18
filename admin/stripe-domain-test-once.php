<?php
declare(strict_types=1);

/**
 * One-shot: test live Stripe + prepare 1 EUR domain checkout.
 * Protect with ?token=HS_ONE_SHOT_TOKEN or admin session.
 * Delete this file after the test.
 */
require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/payments.php';
require_once dirname(__DIR__) . '/includes/providers/namecheap-api.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/domain-store.php';

header('Content-Type: application/json; charset=utf-8');

// Super-admin session (hs_admin_logged) or secret token
if (!hs_admin_or_token_allow(['HS_ONE_SHOT_TOKEN', 'HS_ONCE_TOKEN'], ['sola-stripe-domain-test-2026'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden', 'hint' => 'Admin login or ?token= required'], JSON_UNESCAPED_UNICODE);
    exit;
}

$doPay = !empty($_GET['pay']) || !empty($_POST['pay']);
$domainHint = strtolower(trim((string) ($_GET['domain'] ?? $_POST['domain'] ?? '')));
$amountEur = (float) ($_GET['amount'] ?? $_POST['amount'] ?? 1.0);
if ($amountEur < 0.5) {
    $amountEur = 0.5;
}
if ($amountEur > 20) {
    $amountEur = 20.0;
}

$out = [
    'ok' => true,
    'mode' => hs_payment_mode(),
    'stripe_enabled' => hs_payment_stripe_enabled(),
    'currency' => hs_payment_charge_currency(),
    'livemode_key' => str_starts_with(hs_payment_stripe_secret_key(), 'sk_live_'),
];

// 1) Stripe connection
$stripeTest = hs_payment_test_stripe();
$out['stripe_test'] = $stripeTest;
if (empty($stripeTest['ok'])) {
    $out['ok'] = false;
    $out['error'] = 'stripe_test_failed';
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 2) Namecheap balance + find cheap available domain
$out['namecheap'] = ['configured' => function_exists('hs_namecheap_configured') ? hs_namecheap_configured() : null];
$bal = hs_namecheap_call('namecheap.users.getBalances');
$out['namecheap']['balance_ok'] = !empty($bal['ok']);
if (!empty($bal['ok']) && isset($bal['xml']->CommandResponse->UserGetBalancesResult)) {
    $n = $bal['xml']->CommandResponse->UserGetBalancesResult;
    $out['namecheap']['available'] = (string) ($n['AvailableBalance'] ?? '');
    $out['namecheap']['currency'] = (string) ($n['Currency'] ?? '');
    $out['namecheap']['account'] = (string) ($n['AccountBalance'] ?? '');
} else {
    $out['namecheap']['error'] = $bal['error'] ?? ($bal['errors'] ?? 'balance_failed');
}

$tlds = ['online', 'site', 'website', 'click', 'space', 'shop'];
$picked = null;
$candidates = [];

if ($domainHint !== '' && preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.[a-z0-9.-]+$/i', $domainHint)) {
    $candidates[] = $domainHint;
}
$slug = 'sola' . substr((string) time(), -6);
foreach ($tlds as $tld) {
    $candidates[] = $slug . '.' . $tld;
}

foreach ($candidates as $d) {
    $chk = hs_namecheap_call('namecheap.domains.check', ['DomainList' => $d]);
    if (empty($chk['ok']) || !isset($chk['xml']->CommandResponse->DomainCheckResult)) {
        $out['checks'][] = ['domain' => $d, 'error' => $chk['error'] ?? 'check_failed'];
        continue;
    }
    $nodes = $chk['xml']->CommandResponse->DomainCheckResult;
    // can be single or list
    if (!is_array($nodes) && !($nodes instanceof Traversable)) {
        $nodes = [$nodes];
    }
    foreach ($nodes as $node) {
        $name = strtolower((string) ($node['Domain'] ?? $d));
        $avail = strtolower((string) ($node['Available'] ?? '')) === 'true';
        $premium = strtolower((string) ($node['IsPremiumName'] ?? 'false')) === 'true';
        $out['checks'][] = ['domain' => $name, 'available' => $avail, 'premium' => $premium];
        if ($avail && !$premium && $picked === null) {
            $picked = $name;
        }
    }
    if ($picked !== null) {
        break;
    }
}

$out['picked_domain'] = $picked;

if ($picked === null) {
    $out['ok'] = false;
    $out['error'] = 'no_available_domain';
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 3) Optionally create pending + Stripe Checkout Session (pay=1)
if (!$doPay) {
    $tokenHint = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
    if ($tokenHint === '') {
        $tokenHint = 'sola-stripe-domain-test-2026';
    }
    $out['next'] = 'Add &pay=1 to create a live Stripe Checkout for this domain (amount EUR ' . $amountEur . ')';
    $out['pay_url_hint'] = hs_absolute_url('admin/stripe-domain-test-once.php', [
        'token' => $tokenHint,
        'pay' => '1',
        'domain' => $picked,
        'amount' => (string) $amountEur,
    ]);
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Resolve / create a test client user for fulfillment
$users = function_exists('hs_users') ? hs_users() : [];
$testUser = null;
foreach ($users as $u) {
    if (!is_array($u)) {
        continue;
    }
    $email = strtolower((string) ($u['email'] ?? ''));
    $uname = strtolower((string) ($u['username'] ?? ''));
    if (str_contains($email, 'test') || $uname === 'test' || $uname === 'demo' || str_contains($email, 'solaskinner')) {
        $testUser = $u;
        break;
    }
}
if ($testUser === null && $users !== []) {
    $testUser = $users[0];
}
if ($testUser === null) {
    $out['ok'] = false;
    $out['error'] = 'no_user';
    $out['hint'] = 'Create a client account first (register.php), then re-run with pay=1';
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$userId = (string) ($testUser['id'] ?? '');
// Attach domain as pending domain-only order
$testUser['order_type'] = 'domain';
$testUser['pending_domain'] = $picked;
$testUser['pending_domains'] = [$picked];
$testUser['plan'] = 'domain';

// Persist pending domains on user if storage helpers exist
if (function_exists('hs_user_update') && $userId !== '') {
    hs_user_update($userId, static function (array &$u) use ($picked): void {
        $u['order_type'] = 'domain';
        $u['pending_domain'] = $picked;
        $u['pending_domains'] = [$picked];
        $u['plan'] = 'domain';
    });
}

$priced = [
    'hosting_nok' => 0,
    'hosting_discount_nok' => 0,
    'domain_eur' => $amountEur,
    'domain_discount_eur' => 0,
];

$pendingRes = hs_payment_create_pending($testUser, 'stripe', $priced, null, false, true, 'uk');
if (empty($pendingRes['ok'])) {
    // Fallback: create pending row manually if order_type blocks amounts
    $pendingId = hs_payment_new_pending_id();
    $row = [
        'id' => $pendingId,
        'user_id' => $userId,
        'provider' => 'stripe',
        'status' => 'pending',
        'amount_eur' => $amountEur,
        'currency' => hs_payment_charge_currency(),
        'order_type' => 'domain',
        'want_hosting' => false,
        'want_domain' => true,
        'pending_domain' => $picked,
        'pending_domains' => [$picked],
        'coupon_code' => '',
        'priced' => $priced,
        'lang' => 'uk',
        'created_at' => gmdate('c'),
        'test_product' => true,
    ];
    if (!hs_payment_pending_save($row)) {
        $out['ok'] = false;
        $out['error'] = 'pending_save_failed';
        $out['pending_error'] = $pendingRes['error'] ?? null;
        echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    $pending = $row;
} else {
    $pending = $pendingRes['pending'];
    // ensure amount is the test amount
    $pending['amount_eur'] = $amountEur;
    $pending['pending_domain'] = $picked;
    $pending['want_domain'] = true;
    $pending['want_hosting'] = false;
    $pending['order_type'] = 'domain';
    $pending['test_product'] = true;
    hs_payment_pending_save($pending);
}

$description = 'Domain: ' . $picked . ' (test 1 year)';
$session = hs_stripe_create_checkout_session($pending, $description);
$out['pending_id'] = (string) ($pending['id'] ?? '');
$out['user_id'] = $userId;
$out['user_email'] = (string) ($testUser['email'] ?? '');
$out['user_username'] = (string) ($testUser['username'] ?? '');
$out['amount_eur'] = $amountEur;
$out['stripe_session'] = [
    'ok' => !empty($session['ok']),
    'error' => $session['error'] ?? null,
    'session_id' => $session['session_id'] ?? null,
    'url' => $session['url'] ?? null,
    'raw' => empty($session['ok']) ? ($session['raw'] ?? null) : null,
];

if (empty($session['ok'])) {
    $out['ok'] = false;
    $out['error'] = 'stripe_session_failed';
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
