<?php
declare(strict_types=1);

/**
 * Domain owner / WHOIS contacts for panel Domains → Contacts tab.
 */

require_once __DIR__ . '/user-settings.php';
require_once __DIR__ . '/countries.php';
require_once __DIR__ . '/storage.php';

/**
 * @return array{first_name:string,last_name:string,phone:string,address:string,city:string,postal:string,country:string,state:string,company:string,email:string}
 */
function hs_panel_registrant_current(array $user): array
{
    $userId = (string) ($user['id'] ?? '');
    $settings = $userId !== '' ? hs_user_settings_get($userId) : [];
    $r = is_array($settings['registrant'] ?? null) ? $settings['registrant'] : [];
    $profile = is_array($user['profile'] ?? null) ? $user['profile'] : [];
    $pick = static function (string $key) use ($r, $profile): string {
        $v = trim((string) ($r[$key] ?? ''));
        if ($v !== '') {
            return $v;
        }

        return trim((string) ($profile[$key] ?? ''));
    };

    return [
        'first_name' => $pick('first_name'),
        'last_name' => $pick('last_name'),
        'phone' => $pick('phone'),
        'address' => $pick('address'),
        'city' => $pick('city'),
        'postal' => $pick('postal'),
        'country' => strtoupper($pick('country')),
        'state' => $pick('state'),
        'company' => $pick('company'),
        'email' => strtolower(trim((string) ($user['email'] ?? ''))),
    ];
}

function hs_panel_registrant_is_complete(array $user): bool
{
    $r = hs_panel_registrant_current($user);
    foreach (['first_name', 'last_name', 'phone', 'address', 'city', 'postal', 'country'] as $key) {
        if (trim((string) ($r[$key] ?? '')) === '') {
            return false;
        }
    }
    if (!hs_country_valid((string) $r['country'])) {
        return false;
    }

    return true;
}

/**
 * @return array{ok:bool,error?:string,registrant?:array<string,string>}
 */
function hs_panel_registrant_save_from_post(array $user, array $post): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return ['ok' => false, 'error' => 'user'];
    }
    $registrant = [
        'first_name' => trim((string) ($post['reg_first_name'] ?? '')),
        'last_name' => trim((string) ($post['reg_last_name'] ?? '')),
        'phone' => trim((string) ($post['reg_phone'] ?? '')),
        'address' => trim((string) ($post['reg_address'] ?? '')),
        'city' => trim((string) ($post['reg_city'] ?? '')),
        'postal' => trim((string) ($post['reg_postal'] ?? '')),
        'country' => strtoupper(trim((string) ($post['reg_country'] ?? ''))),
        'state' => trim((string) ($post['reg_state'] ?? '')),
        'company' => trim((string) ($post['reg_company'] ?? '')),
    ];
    foreach (['first_name', 'last_name', 'phone', 'address', 'city', 'postal', 'country'] as $key) {
        if ($registrant[$key] === '') {
            return ['ok' => false, 'error' => 'incomplete'];
        }
    }
    if (!hs_country_valid($registrant['country'])) {
        return ['ok' => false, 'error' => 'country'];
    }
    if (!hs_user_settings_save($userId, ['registrant' => $registrant])) {
        return ['ok' => false, 'error' => 'save'];
    }
    hs_user_update($userId, static function (array &$u) use ($registrant): void {
        $profile = is_array($u['profile'] ?? null) ? $u['profile'] : [];
        $u['profile'] = array_merge($profile, $registrant);
        if (trim((string) ($u['name'] ?? '')) === '') {
            $u['name'] = trim($registrant['first_name'] . ' ' . $registrant['last_name']);
        }
    });

    return ['ok' => true, 'registrant' => $registrant];
}

/** HTML form for domain owner / WHOIS contacts. */
function hs_panel_registrant_form_html(array $user, array $t, string $lang): string
{
    $r = hs_panel_registrant_current($user);
    $complete = hs_panel_registrant_is_complete($user);
    $countries = hs_countries_list($lang, $t);
    $opts = '<option value="">' . hs_h($t['register_country'] ?? 'Country') . '</option>';
    foreach ($countries as $code => $label) {
        $sel = $r['country'] === $code ? ' selected' : '';
        $opts .= '<option value="' . hs_h($code) . '"' . $sel . '>' . hs_h($label) . '</option>';
    }
    $status = $complete
        ? '<p class="hs-alert hs-alert-success" style="margin-bottom:.75rem">' . hs_h($t['dom_registrant_ok'] ?? 'Contacts ready for domain registration.') . '</p>'
        : '<p class="hs-alert hs-alert-warn" style="margin-bottom:.75rem">' . hs_h($t['dom_registrant_required'] ?? 'Save owner contacts before paying — required by the domain registry.') . '</p>';

    return '<div id="hs-dom-registrant">' . hs_render_card(
        $t['dom_registrant_title'] ?? 'Domain owner contacts (WHOIS)',
        $status
        . '<p class="hp-muted">' . hs_h($t['dom_registrant_lead'] ?? 'Registrant data for domain registration. Stored in your account profile.') . '</p>'
        . '<form method="post" class="hp-stack hs-dom-registrant-form">' . hs_csrf_field()
        . '<div class="hp-grid-2">'
        . '<div class="hs-field"><label>' . hs_h($t['register_first_name'] ?? 'First name') . ' *</label>'
        . '<input type="text" name="reg_first_name" required value="' . hs_h($r['first_name']) . '" autocomplete="given-name"></div>'
        . '<div class="hs-field"><label>' . hs_h($t['register_last_name'] ?? 'Last name') . ' *</label>'
        . '<input type="text" name="reg_last_name" required value="' . hs_h($r['last_name']) . '" autocomplete="family-name"></div>'
        . '</div>'
        . '<div class="hp-grid-2">'
        . '<div class="hs-field"><label>' . hs_h($t['register_phone'] ?? 'Phone') . ' *</label>'
        . '<input type="tel" name="reg_phone" required value="' . hs_h($r['phone']) . '" placeholder="+47 …" autocomplete="tel"></div>'
        . '<div class="hs-field"><label>' . hs_h($t['register_company'] ?? 'Company') . '</label>'
        . '<input type="text" name="reg_company" value="' . hs_h($r['company']) . '" autocomplete="organization"></div>'
        . '</div>'
        . '<div class="hs-field"><label>' . hs_h($t['register_address'] ?? 'Address') . ' *</label>'
        . '<input type="text" name="reg_address" required value="' . hs_h($r['address']) . '" autocomplete="street-address"></div>'
        . '<div class="hp-grid-2">'
        . '<div class="hs-field"><label>' . hs_h($t['register_city'] ?? 'City') . ' *</label>'
        . '<input type="text" name="reg_city" required value="' . hs_h($r['city']) . '" autocomplete="address-level2"></div>'
        . '<div class="hs-field"><label>' . hs_h($t['register_postal'] ?? 'Postal code') . ' *</label>'
        . '<input type="text" name="reg_postal" required value="' . hs_h($r['postal']) . '" autocomplete="postal-code"></div>'
        . '</div>'
        . '<div class="hp-grid-2">'
        . '<div class="hs-field"><label>' . hs_h($t['register_country'] ?? 'Country') . ' *</label>'
        . '<select name="reg_country" required>' . $opts . '</select></div>'
        . '<div class="hs-field"><label>' . hs_h($t['register_state'] ?? 'State / region') . '</label>'
        . '<input type="text" name="reg_state" value="' . hs_h($r['state']) . '" autocomplete="address-level1"></div>'
        . '</div>'
        . '<p class="hp-muted" style="font-size:.85rem">' . hs_h($t['dom_registrant_email_note'] ?? 'Registry email') . ': <code>' . hs_h($r['email']) . '</code></p>'
        . '<button type="submit" name="save_registrant" value="1" class="hs-btn hs-btn-primary">'
        . '<i class="fa-solid fa-floppy-disk"></i> ' . hs_h($t['dom_registrant_save'] ?? 'Save contacts') . '</button>'
        . '</form>'
    ) . '</div>';
}
