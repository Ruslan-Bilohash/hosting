<?php
declare(strict_types=1);

/** @var array<string, string>|null */
$GLOBALS['hs_countries_en'] = null;

/**
 * @return array<string, string> code => English name
 */
function hs_countries_en(): array
{
    if (is_array($GLOBALS['hs_countries_en'])) {
        return $GLOBALS['hs_countries_en'];
    }

    $path = __DIR__ . '/../data/countries.json';
    $raw = is_readable($path) ? file_get_contents($path) : false;
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    $map = [];

    if (is_array($decoded)) {
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = strtoupper((string) ($row['code'] ?? ''));
            $en = (string) ($row['en'] ?? '');
            if ($code !== '' && $en !== '') {
                $map[$code] = $en;
            }
        }
    }

    $GLOBALS['hs_countries_en'] = $map;

    return $map;
}

function hs_country_locale(string $lang): string
{
    return match ($lang) {
        'uk' => 'uk',
        'no' => 'nb',
        'en' => 'en',
        default => 'en',
    };
}

function hs_country_label(string $code, string $lang, array $t = []): string
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return '';
    }

    $overrideKey = 'country_' . strtolower($code);
    if (!empty($t[$overrideKey])) {
        return (string) $t[$overrideKey];
    }

    if (extension_loaded('intl')) {
        $locale = hs_country_locale($lang);
        $region = Locale::getDisplayRegion('und-' . $code, $locale);
        if (is_string($region) && $region !== '') {
            return $region;
        }
    }

    return hs_countries_en()[$code] ?? $code;
}

function hs_countries_list(string $lang, array $t = []): array
{
    $list = [];
    foreach (array_keys(hs_countries_en()) as $code) {
        $list[$code] = hs_country_label($code, $lang, $t);
    }

    $locale = hs_country_locale($lang);
    if (class_exists('Collator')) {
        $collator = new Collator($locale);
        uasort($list, static function (string $a, string $b) use ($collator): int {
            return $collator->compare($a, $b);
        });
    } else {
        uasort($list, static fn(string $a, string $b): int => strcasecmp($a, $b));
    }

    return $list;
}

function hs_country_valid(string $code): bool
{
    $code = strtoupper(trim($code));

    return $code !== '' && isset(hs_countries_en()[$code]);
}

function hs_country_escape(string $value): string
{
    if (function_exists('hs_h')) {
        return hs_h($value);
    }

    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function hs_render_country_options(string $lang, string $selected, array $t = []): string
{
    $selected = strtoupper(trim($selected));
    $popular = ['NO', 'UA', 'SE', 'LT', 'DE', 'GB', 'US'];
    $all = hs_countries_list($lang, $t);
    $popularLabel = (string) ($t['countries_popular'] ?? 'Popular');
    $separator = (string) ($t['countries_separator'] ?? '────────');

    $html = '';
    $popularCodes = $popular;
    if ($selected !== '' && in_array($selected, $popular, true)) {
        $popularCodes = array_values(array_unique(array_merge([$selected], $popular)));
    }

    $html .= '<optgroup label="' . hs_country_escape($popularLabel) . '">';
    foreach ($popularCodes as $code) {
        if (!isset($all[$code])) {
            continue;
        }
        $sel = $selected === $code ? ' selected' : '';
        $html .= '<option value="' . hs_country_escape($code) . '"' . $sel . '>'
            . hs_country_escape($all[$code]) . '</option>';
    }
    $html .= '</optgroup>';

    $html .= '<option disabled>' . hs_country_escape($separator) . '</option>';

    foreach ($all as $code => $label) {
        if (in_array($code, $popular, true)) {
            continue;
        }
        $sel = $selected === $code ? ' selected' : '';
        $html .= '<option value="' . hs_country_escape($code) . '"' . $sel . '>'
            . hs_country_escape($label) . '</option>';
    }

    return $html;
}