<?php
declare(strict_types=1);

/** Display currency per UI language (base prices in NOK). */
function hs_currency_for_lang(string $lang): string
{
    return match ($lang) {
        'no' => 'NOK',
        'uk' => 'UAH',
        default => 'EUR',
    };
}

function hs_currency_symbol(string $currency): string
{
    return match (strtoupper($currency)) {
        'NOK' => 'kr',
        'UAH' => '₴',
        'EUR' => '€',
        'USD' => '$',
        default => $currency,
    };
}

/** @return array<string, float> NOK => target currency (1 NOK = X) */
function hs_exchange_rates(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $fallback = ['EUR' => 0.088, 'UAH' => 3.85, 'USD' => 0.095, 'NOK' => 1.0];
    $file = HS_DATA_DIR . '/exchange-rates.json';
    if (is_readable($file)) {
        $raw = json_decode((string) file_get_contents($file), true);
        if (is_array($raw) && ($raw['fetched_at'] ?? 0) > time() - 43200 && is_array($raw['rates'] ?? null)) {
            $cache = array_merge($fallback, $raw['rates']);
            return $cache;
        }
    }

    $rates = $fallback;
    $ctx = stream_context_create(['http' => ['timeout' => 6, 'user_agent' => 'BILOHASH-Hosting/1.0']]);
    $json = @file_get_contents('https://open.er-api.com/v6/latest/NOK', false, $ctx);
    if (is_string($json)) {
        $data = json_decode($json, true);
        if (($data['result'] ?? '') === 'success' && is_array($data['rates'] ?? null)) {
            foreach (['EUR', 'UAH', 'USD'] as $code) {
                if (isset($data['rates'][$code]) && is_numeric($data['rates'][$code])) {
                    $rates[$code] = (float) $data['rates'][$code];
                }
            }
        }
    }

    if (is_dir(HS_DATA_DIR)) {
        @file_put_contents($file, json_encode([
            'fetched_at' => time(),
            'rates' => $rates,
        ], JSON_UNESCAPED_UNICODE));
    }

    $cache = $rates;
    return $cache;
}

function hs_convert_nok(float $nok, string $currency): float
{
    $currency = strtoupper($currency);
    if ($currency === 'NOK') {
        return $nok;
    }
    $rate = hs_exchange_rates()[$currency] ?? 1.0;
    $amount = $nok * $rate;
    return $currency === 'UAH' ? round($amount, 0) : round($amount, 2);
}

function hs_format_nok_price(float $nok, string $lang): string
{
    $currency = hs_currency_for_lang($lang);
    $amount = hs_convert_nok($nok, $currency);
    $sym = hs_currency_symbol($currency);

    if ($currency === 'NOK') {
        return number_format($amount, 0, ',', ' ') . ' ' . $sym;
    }
    if ($currency === 'UAH') {
        return number_format($amount, 0, '.', ' ') . ' ' . $sym;
    }
    return $sym . number_format($amount, 2, '.', ' ');
}

/** Domain registry prices are stored in EUR per year. */
function hs_format_eur_price(float $eur, string $lang, string $periodSuffix = ''): string
{
    $rates = hs_exchange_rates();
    $currency = hs_currency_for_lang($lang);

    if ($currency === 'EUR') {
        $text = '€' . number_format($eur, 2, '.', ' ');
    } elseif ($currency === 'NOK') {
        $nok = $eur / max($rates['EUR'], 0.001);
        $text = number_format(round($nok, 0), 0, ',', ' ') . ' kr';
    } else {
        $nok = $eur / max($rates['EUR'], 0.001);
        $uah = hs_convert_nok($nok, 'UAH');
        $text = number_format($uah, 0, '.', ' ') . ' ₴';
    }

    return $text . $periodSuffix;
}