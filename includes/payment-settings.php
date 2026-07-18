<?php
declare(strict_types=1);

/** @return array<string, mixed> */
function hs_payment_settings_defaults(): array
{
    return [
        'mode' => 'test',
        'charge_currency' => 'EUR',
        'simulated_enabled' => true,
        'stripe_enabled' => false,
        'stripe_test_pk' => '',
        'stripe_test_sk' => '',
        'stripe_live_pk' => '',
        'stripe_live_sk' => '',
        'stripe_webhook_test_secret' => '',
        'stripe_webhook_live_secret' => '',
        'paypal_enabled' => false,
        'paypal_test_client_id' => '',
        'paypal_test_secret' => '',
        'paypal_live_client_id' => '',
        'paypal_live_secret' => '',
    ];
}

function hs_payment_settings_file(): string
{
    return HS_DATA_DIR . '/payments.config.php';
}

/** @return array<string, mixed> */
function hs_payment_settings_load(bool $fresh = false): array
{
    static $cache = null;
    if (!$fresh && is_array($cache)) {
        return $cache;
    }
    $defaults = hs_payment_settings_defaults();
    $file = hs_payment_settings_file();
    if (!is_readable($file)) {
        $cache = $defaults;
        return $cache;
    }
    $raw = include $file;
    if (!is_array($raw)) {
        $cache = $defaults;
        return $cache;
    }
    $cache = array_merge($defaults, $raw);
    return $cache;
}

/** @param array<string, mixed> $settings */
function hs_payment_settings_save(array $settings): bool
{
    $defaults = hs_payment_settings_defaults();
    $merged = array_merge($defaults, $settings);
    $merged['mode'] = ($merged['mode'] ?? 'test') === 'live' ? 'live' : 'test';
    $merged['charge_currency'] = strtoupper((string) ($merged['charge_currency'] ?? 'EUR'));
    if (!in_array($merged['charge_currency'], ['EUR', 'USD', 'NOK', 'UAH'], true)) {
        $merged['charge_currency'] = 'EUR';
    }

    $lines = ["<?php", "declare(strict_types=1);", "", "/** Payment gateways — managed in admin/payments.php */", "return ["];
    foreach ($merged as $key => $value) {
        if (is_bool($value)) {
            $export = $value ? 'true' : 'false';
        } elseif (is_int($value) || is_float($value)) {
            $export = (string) $value;
        } else {
            $export = var_export((string) $value, true);
        }
        $lines[] = "    " . var_export((string) $key, true) . ' => ' . $export . ',';
    }
    $lines[] = '];';
    $lines[] = '';

    if (!is_dir(HS_DATA_DIR)) {
        @mkdir(HS_DATA_DIR, 0750, true);
    }
    $ok = file_put_contents(hs_payment_settings_file(), implode("\n", $lines), LOCK_EX);
    if ($ok === false) {
        return false;
    }
    hs_payment_settings_load(true);
    return true;
}

function hs_payment_mode(): string
{
    $s = hs_payment_settings_load();
    return ($s['mode'] ?? 'test') === 'live' ? 'live' : 'test';
}

function hs_payment_is_test_mode(): bool
{
    return hs_payment_mode() !== 'live';
}

function hs_payment_charge_currency(): string
{
    return strtoupper((string) (hs_payment_settings_load()['charge_currency'] ?? 'EUR'));
}

function hs_payment_secret_value(string $testKey, string $liveKey): string
{
    $s = hs_payment_settings_load();
    $key = hs_payment_is_test_mode() ? $testKey : $liveKey;
    return trim((string) ($s[$key] ?? ''));
}

function hs_payment_stripe_publishable_key(): string
{
    return hs_payment_secret_value('stripe_test_pk', 'stripe_live_pk');
}

function hs_payment_stripe_secret_key(): string
{
    return hs_payment_secret_value('stripe_test_sk', 'stripe_live_sk');
}

function hs_payment_stripe_webhook_secret(): string
{
    return hs_payment_secret_value('stripe_webhook_test_secret', 'stripe_webhook_live_secret');
}

function hs_payment_paypal_client_id(): string
{
    return hs_payment_secret_value('paypal_test_client_id', 'paypal_live_client_id');
}

function hs_payment_paypal_secret(): string
{
    return hs_payment_secret_value('paypal_test_secret', 'paypal_live_secret');
}

function hs_payment_stripe_enabled(): bool
{
    $s = hs_payment_settings_load();
    return !empty($s['stripe_enabled']) && hs_payment_stripe_secret_key() !== '';
}

function hs_payment_paypal_enabled(): bool
{
    $s = hs_payment_settings_load();
    return !empty($s['paypal_enabled'])
        && hs_payment_paypal_client_id() !== ''
        && hs_payment_paypal_secret() !== '';
}

function hs_payment_mask_secret(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (strlen($value) <= 8) {
        return str_repeat('•', strlen($value));
    }
    return substr($value, 0, 4) . str_repeat('•', max(4, strlen($value) - 8)) . substr($value, -4);
}

/** Keep existing secret when admin leaves masked placeholder unchanged. */
function hs_payment_merge_secret(string $posted, string $current): string
{
    $posted = trim($posted);
    if ($posted === '' || str_contains($posted, '•')) {
        return $current;
    }
    return $posted;
}