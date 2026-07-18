<?php
declare(strict_types=1);

require_once __DIR__ . '/legal-content.php';

function hs_has_local_legal(): bool
{
    // Prefer local legal pages whenever they exist on disk (Solaskinner / white-label).
    $root = dirname(__DIR__);
    if (is_file($root . '/privacy.php') && is_file($root . '/terms.php') && is_file($root . '/cookies.php')) {
        return true;
    }
    return function_exists('hs_host_profile_flag') && hs_host_profile_flag('white_label');
}

function hs_legal_brand(): string
{
    if (function_exists('hs_resolve_host_profile')) {
        $profile = hs_resolve_host_profile();
        if (!empty($profile['site_name'])) {
            return (string) $profile['site_name'];
        }
    }
    return defined('HS_SITE_NAME') ? HS_SITE_NAME : 'Hosting';
}

function hs_legal_email(array $t): string
{
    return (string) ($t['footer_email'] ?? 'support@solaskinner.com');
}

function hs_legal_url(string $page, string $lang): string
{
    if (hs_has_local_legal()) {
        return hs_url($page, $lang !== 'en' ? ['lang' => $lang] : []);
    }
    $map = [
        'terms.php' => '/website/terms.php',
        'privacy.php' => '/website/privacy-policy.php',
        'cookies.php' => '/website/cookies.php',
    ];
    return hs_bilohash_external_url($map[$page] ?? $page, $lang);
}

function hs_legal_external_url(string $path, string $lang): string
{
    $map = [
        '/website/terms.php' => 'terms.php',
        '/website/privacy-policy.php' => 'privacy.php',
        '/website/cookies.php' => 'cookies.php',
    ];
    if (hs_has_local_legal() && isset($map[$path])) {
        return hs_legal_url($map[$path], $lang);
    }
    return hs_bilohash_external_url($path, $lang);
}

/** @return array<string, string> */
function hs_legal_ui(string $lang): array
{
    $all = [
        'no' => [
            'home' => '← Forside',
            'nav_terms' => 'Vilkår',
            'nav_privacy' => 'Personvern',
            'nav_cookies' => 'Cookies',
            'nav_domains' => 'Domener',
            'legal_badge' => 'Juridisk',
            'contact_privacy' => 'Spørsmål om personvern eller cookies?',
        ],
        'uk' => [
            'home' => '← Головна',
            'nav_terms' => 'Умови',
            'nav_privacy' => 'Конфіденційність',
            'nav_cookies' => 'Cookie',
            'nav_domains' => 'Домени',
            'legal_badge' => 'Правові документи',
            'contact_privacy' => 'Питання щодо конфіденційності або cookie?',
        ],
        'en' => [
            'home' => '← Home',
            'nav_terms' => 'Terms',
            'nav_privacy' => 'Privacy',
            'nav_cookies' => 'Cookies',
            'nav_domains' => 'Domains',
            'legal_badge' => 'Legal',
            'contact_privacy' => 'Questions about privacy or cookies?',
        ],
    ];
    return $all[$lang] ?? $all['en'];
}

function hs_legal_render_nav_pills(string $lang, string $active, array $ui): string
{
    $items = [
        'terms.php' => $ui['nav_terms'],
        'privacy.php' => $ui['nav_privacy'],
        'cookies.php' => $ui['nav_cookies'],
        'domain-registration.php' => $ui['nav_domains'] ?? 'Domains',
    ];
    $html = '<nav class="hs-legal-pills" aria-label="' . hs_h($ui['legal_badge']) . '">';
    foreach ($items as $page => $label) {
        $cls = $page === $active ? ' is-active' : '';
        $html .= '<a href="' . hs_h(hs_legal_url($page, $lang)) . '" class="hs-legal-pill' . $cls . '">' . hs_h($label) . '</a>';
    }
    return $html . '</nav>';
}

/** @param array<string, mixed> $doc */
function hs_legal_render_page(array $doc, string $lang, string $activePage, array $t): string
{
    $ui = hs_legal_ui($lang);
    $email = hs_legal_email($t);
    $html = '<article class="hs-legal-page">'
        . '<header class="hs-legal-head">'
        . '<span class="hs-legal-badge">' . hs_h($ui['legal_badge']) . '</span>'
        . '<h1>' . hs_h((string) ($doc['title'] ?? '')) . '</h1>'
        . '<p class="hs-legal-updated">' . hs_h((string) ($doc['last_updated'] ?? '')) . '</p>'
        . '<p class="hs-legal-intro">' . ($doc['intro'] ?? '') . '</p>'
        . hs_legal_render_nav_pills($lang, $activePage, $ui)
        . '</header>'
        . '<div class="hs-legal-sections">';
    foreach ((array) ($doc['sections'] ?? []) as $section) {
        if (!is_array($section)) {
            continue;
        }
        $html .= '<section class="hs-legal-section">'
            . '<h2>' . hs_h((string) ($section['title'] ?? '')) . '</h2>'
            . '<div class="hs-legal-prose">' . ($section['body'] ?? '') . '</div>'
            . '</section>';
    }
    $html .= '</div>';
    if (!empty($doc['contact_title'])) {
        $html .= '<footer class="hs-legal-contact">'
            . '<h3>' . hs_h((string) $doc['contact_title']) . '</h3>'
            . '<p>' . hs_h((string) ($doc['contact_text'] ?? '')) . '</p>'
            . '<a href="mailto:' . hs_h($email) . '" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-envelope"></i> ' . hs_h($email) . '</a>'
            . '</footer>';
    }
    if (!empty($doc['cookie_rows']) && is_array($doc['cookie_rows'])) {
        $html .= '<div class="hs-table-wrap hs-legal-cookie-table"><table class="hs-table"><thead><tr>'
            . '<th>' . hs_h($doc['table_name'] ?? 'Name') . '</th>'
            . '<th>' . hs_h($doc['table_type'] ?? 'Type') . '</th>'
            . '<th>' . hs_h($doc['table_purpose'] ?? 'Purpose') . '</th>'
            . '<th>' . hs_h($doc['table_duration'] ?? 'Duration') . '</th>'
            . '</tr></thead><tbody>';
        foreach ($doc['cookie_rows'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $html .= '<tr><td><code>' . hs_h((string) ($row['name'] ?? '')) . '</code></td>'
                . '<td>' . hs_h((string) ($row['type'] ?? '')) . '</td>'
                . '<td>' . hs_h((string) ($row['purpose'] ?? '')) . '</td>'
                . '<td>' . hs_h((string) ($row['duration'] ?? '')) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    // Interactive manage / delete panel on the cookies legal page
    if ($activePage === 'cookies.php') {
        $html .= hs_render_cookie_manager($lang);
    }
    return $html . '</article>';
}

/** @return array<string, string> */
function hs_cookie_consent_strings(string $lang): array
{
    $all = [
        'no' => [
            'title' => 'Vi bruker informasjonskapsler',
            'text' => 'Nødvendige cookies brukes for sikker innlogging, språkvalg og drift. Valgfrie funksjonelle cookies lagres kun hvis du samtykker. Du kan endre valget når som helst.',
            'accept_all' => 'Godta alle',
            'reject_optional' => 'Kun nødvendige',
            'settings' => 'Innstillinger',
            'save' => 'Lagre valg',
            'link_privacy' => 'Personvern',
            'link_cookies' => 'Cookies',
            'link_terms' => 'Vilkår',
            'cat_necessary' => 'Nødvendige',
            'cat_necessary_desc' => 'Sesjon, CSRF, språk og lagring av samtykke.',
            'cat_functional' => 'Funksjonelle',
            'cat_functional_desc' => 'UI-preferanser i nettleseren (f.eks. panel-layout).',
            'always_on' => 'Alltid på',
            'mgr_title' => 'Administrer cookies',
            'mgr_lead' => 'Endre samtykke, se lagrede data i denne nettleseren, eller slett cookies vi kan fjerne herfra.',
            'mgr_status' => 'Nåværende valg',
            'mgr_status_none' => 'Ikke valgt ennå',
            'mgr_status_all' => 'Alle cookies (inkl. funksjonelle)',
            'mgr_status_essential' => 'Kun nødvendige',
            'mgr_list_title' => 'Funnet i denne nettleseren',
            'mgr_list_empty' => 'Ingen lesbare cookies/localStorage funnet.',
            'mgr_list_cookie' => 'Cookie',
            'mgr_list_ls' => 'localStorage',
            'mgr_list_ss' => 'sessionStorage',
            'mgr_save_ok' => 'Valget er lagret.',
            'mgr_delete' => 'Slett cookies og nullstill samtykke',
            'mgr_delete_hint' => 'Sletter lesbare cookies og hs_* lagring. HttpOnly-sesjon (PHPSESSID) fjernes ved utlogging. Banner vises på nytt.',
            'mgr_delete_ok' => 'Cookies slettet. Samtykke er nullstilt.',
            'mgr_refresh' => 'Oppdater liste',
            'mgr_note' => 'Nødvendige cookies kan settes på nytt ved neste innlogging eller språkvalg. Du logger ikke automatisk ut.',
        ],
        'uk' => [
            'title' => 'Ми використовуємо cookie',
            'text' => 'Необхідні cookie потрібні для безпечного входу, мови та роботи сайту. Функціональні — лише за вашою згодою. Вибір можна змінити будь-коли.',
            'accept_all' => 'Прийняти всі',
            'reject_optional' => 'Лише необхідні',
            'settings' => 'Налаштування',
            'save' => 'Зберегти',
            'link_privacy' => 'Конфіденційність',
            'link_cookies' => 'Cookie',
            'link_terms' => 'Умови',
            'cat_necessary' => 'Необхідні',
            'cat_necessary_desc' => 'Сесія, CSRF, мова, збереження згоди.',
            'cat_functional' => 'Функціональні',
            'cat_functional_desc' => 'Налаштування інтерфейсу в браузері.',
            'always_on' => 'Завжди увімкнено',
            'mgr_title' => 'Керування cookie',
            'mgr_lead' => 'Змініть згоду, перегляньте дані в цьому браузері або видаліть cookie, які можна стерти з сайту.',
            'mgr_status' => 'Поточний вибір',
            'mgr_status_none' => 'Ще не обрано',
            'mgr_status_all' => 'Усі cookie (включно з функціональними)',
            'mgr_status_essential' => 'Лише необхідні',
            'mgr_list_title' => 'Знайдено в цьому браузері',
            'mgr_list_empty' => 'Читабельні cookie/localStorage не знайдено.',
            'mgr_list_cookie' => 'Cookie',
            'mgr_list_ls' => 'localStorage',
            'mgr_list_ss' => 'sessionStorage',
            'mgr_save_ok' => 'Вибір збережено.',
            'mgr_delete' => 'Видалити cookie та скинути згоду',
            'mgr_delete_hint' => 'Видаляє читабельні cookie та hs_* у сховищі. HttpOnly-сесію (PHPSESSID) знімає вихід із акаунта. Банер згоди з’явиться знову.',
            'mgr_delete_ok' => 'Cookie видалено. Згоду скинуто.',
            'mgr_refresh' => 'Оновити список',
            'mgr_note' => 'Необхідні cookie можуть з’явитися знову після входу або зміни мови. Автоматичний вихід не виконується.',
        ],
        'en' => [
            'title' => 'We use cookies',
            'text' => 'Essential cookies are required for secure login, language and site operation. Optional functional cookies are stored only if you consent. You can change your choice anytime.',
            'accept_all' => 'Accept all',
            'reject_optional' => 'Essential only',
            'settings' => 'Settings',
            'save' => 'Save choices',
            'link_privacy' => 'Privacy',
            'link_cookies' => 'Cookies',
            'link_terms' => 'Terms',
            'cat_necessary' => 'Essential',
            'cat_necessary_desc' => 'Session, CSRF, language and consent storage.',
            'cat_functional' => 'Functional',
            'cat_functional_desc' => 'UI preferences in your browser (e.g. panel layout).',
            'always_on' => 'Always on',
            'mgr_title' => 'Manage cookies',
            'mgr_lead' => 'Change your consent, review data stored in this browser, or delete cookies we can remove from here.',
            'mgr_status' => 'Current choice',
            'mgr_status_none' => 'Not chosen yet',
            'mgr_status_all' => 'All cookies (including functional)',
            'mgr_status_essential' => 'Essential only',
            'mgr_list_title' => 'Found in this browser',
            'mgr_list_empty' => 'No readable cookies/localStorage found.',
            'mgr_list_cookie' => 'Cookie',
            'mgr_list_ls' => 'localStorage',
            'mgr_list_ss' => 'sessionStorage',
            'mgr_save_ok' => 'Your choice was saved.',
            'mgr_delete' => 'Delete cookies and reset consent',
            'mgr_delete_hint' => 'Removes readable cookies and hs_* storage. HttpOnly session (PHPSESSID) is cleared when you log out. The consent banner will show again.',
            'mgr_delete_ok' => 'Cookies deleted. Consent was reset.',
            'mgr_refresh' => 'Refresh list',
            'mgr_note' => 'Essential cookies may be set again on next login or language change. You are not logged out automatically.',
        ],
    ];
    return $all[$lang] ?? $all['en'];
}

/**
 * Interactive cookie preferences + delete UI for cookies.php.
 */
function hs_render_cookie_manager(string $lang): string
{
    $c = hs_cookie_consent_strings($lang);
    return '<section id="hs-cookie-manager" class="hs-cookie-manager" data-hs-cookie-manager>'
        . '<h2><i class="fa-solid fa-sliders" aria-hidden="true"></i> ' . hs_h($c['mgr_title']) . '</h2>'
        . '<p class="hs-cookie-manager-lead">' . hs_h($c['mgr_lead']) . '</p>'
        . '<p class="hs-cookie-manager-status">' . hs_h($c['mgr_status']) . ': <strong data-hs-cc-status>—</strong></p>'
        . '<div class="hs-cookie-manager-cats">'
        . '<label class="hs-cookie-cat is-locked">'
        . '<input type="checkbox" checked disabled data-hs-cc-necessary>'
        . '<span><strong>' . hs_h($c['cat_necessary']) . '</strong> <em>' . hs_h($c['always_on']) . '</em><br>'
        . '<span class="hs-cookie-cat-desc">' . hs_h($c['cat_necessary_desc']) . '</span></span></label>'
        . '<label class="hs-cookie-cat">'
        . '<input type="checkbox" data-hs-cc-functional>'
        . '<span><strong>' . hs_h($c['cat_functional']) . '</strong><br>'
        . '<span class="hs-cookie-cat-desc">' . hs_h($c['cat_functional_desc']) . '</span></span></label>'
        . '</div>'
        . '<div class="hs-cookie-manager-actions">'
        . '<button type="button" class="hs-btn hs-btn-primary" data-hs-cc-mgr-accept-all>' . hs_h($c['accept_all']) . '</button>'
        . '<button type="button" class="hs-btn hs-btn-ghost" data-hs-cc-mgr-essential>' . hs_h($c['reject_optional']) . '</button>'
        . '<button type="button" class="hs-btn hs-btn-ghost" data-hs-cc-mgr-save>' . hs_h($c['save']) . '</button>'
        . '<button type="button" class="hs-btn hs-btn-ghost" data-hs-cc-mgr-refresh>' . hs_h($c['mgr_refresh']) . '</button>'
        . '</div>'
        . '<p class="hs-cookie-manager-msg" data-hs-cc-msg hidden role="status"></p>'
        . '<div class="hs-cookie-manager-delete">'
        . '<button type="button" class="hs-btn hs-btn-danger" data-hs-cc-mgr-delete><i class="fa-solid fa-trash"></i> ' . hs_h($c['mgr_delete']) . '</button>'
        . '<p class="hp-muted">' . hs_h($c['mgr_delete_hint']) . '</p>'
        . '</div>'
        . '<h3>' . hs_h($c['mgr_list_title']) . '</h3>'
        . '<div class="hs-table-wrap"><table class="hs-table hs-cookie-store-table"><thead><tr>'
        . '<th>Type</th><th>Key</th><th>Value</th></tr></thead>'
        . '<tbody data-hs-cc-store-list></tbody></table></div>'
        . '<p class="hp-muted hs-cookie-manager-note">' . hs_h($c['mgr_note']) . '</p>'
        . '</section>';
}

function hs_render_cookie_consent(array $t, string $lang): string
{
    if (!hs_has_local_legal()) {
        return '';
    }
    $c = hs_cookie_consent_strings($lang);
    $cfg = json_encode([
        'storageKey' => 'hs_cookie_consent',
        'privacyUrl' => hs_legal_url('privacy.php', $lang),
        'cookiesUrl' => hs_legal_url('cookies.php', $lang),
        'termsUrl' => hs_legal_url('terms.php', $lang),
        'strings' => $c,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    return '<div id="hs-cookie-consent" class="hs-cookie-consent" hidden>'
        . '<div class="hs-cookie-consent-inner">'
        . '<p class="hs-cookie-consent-title"><i class="fa-solid fa-cookie-bite"></i> ' . hs_h($c['title']) . '</p>'
        . '<p class="hs-cookie-consent-text">' . hs_h($c['text']) . '</p>'
        . '<div class="hs-cookie-consent-actions">'
        . '<button type="button" class="hs-btn hs-btn-primary" data-hs-cc-accept-all>' . hs_h($c['accept_all']) . '</button>'
        . '<button type="button" class="hs-btn hs-btn-ghost" data-hs-cc-reject>' . hs_h($c['reject_optional']) . '</button>'
        . '<button type="button" class="hs-btn hs-btn-ghost" data-hs-cc-settings>' . hs_h($c['settings']) . '</button>'
        . '</div>'
        . '<div class="hs-cookie-consent-links">'
        . '<a href="' . hs_h(hs_legal_url('privacy.php', $lang)) . '">' . hs_h($c['link_privacy']) . '</a>'
        . '<a href="' . hs_h(hs_legal_url('cookies.php', $lang)) . '">' . hs_h($c['link_cookies']) . '</a>'
        . '<a href="' . hs_h(hs_legal_url('terms.php', $lang)) . '">' . hs_h($c['link_terms']) . '</a>'
        . '</div></div></div>'
        . '<script>window.HS_COOKIE_CONSENT=' . $cfg . ';</script>';
}