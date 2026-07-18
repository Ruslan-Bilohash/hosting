<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

hs_admin_require();
$admin_active = 'ops-guide';
$page_title = $t['admin_ops_guide_title'] ?? 'Ops guide';

/** @return array<string, string|list<string>> */
function hs_admin_ops_guide_copy(string $lang): array
{
    if ($lang === 'no') {
        return [
            'lead' => 'Hvordan hosting og domene kobles automatisk etter betaling — og hva du gjør hvis noe feiler.',
            'auto_title' => 'Hva skjer automatisk',
            'auto_lead' => 'Etter vellykket betaling (Stripe / PayPal / demo) trenger du vanligvis ikke å koble noe manuelt i CMS.',
            'flow_title' => 'Flyt steg for steg',
            'manual_title' => 'Det du vanligvis IKKE trenger å gjøre',
            'intervene_title' => 'Når du må gripe inn',
            'check_title' => 'Feilsøkings-sjekkliste',
            'infra_title' => 'Infrastruktur (Stellar + Nebula)',
            'notify_title' => 'Varsler',
            'links_title' => 'Nyttige admin-lenker',
            'steps' => [
                'Kunden registrerer seg med hosting og/eller domene i handlekurven (eller kjøper domene senere i panelet).',
                'Betaling bekreftes (Stripe/PayPal/demo).',
                'Hosting aktiveres: subscription_status = active, paid_until settes.',
                'Domene: bestilling opprettes → Namecheap API-registrering (hvis konfigurert).',
                'Bind: primary/active domain + mappe public_html/{bruker}/{domene}/.',
                'Fakturaer merkes betalt; valgfritt: egen cPanel på Nebula-reseller.',
                'E-post til support@ + Telegram-varsel til operatør.',
            ],
            'auto_table' => [
                ['Aktiver hosting-abonnement', 'Ja'],
                ['Nettstedmappe public_html/{user}/{domain}/', 'Ja'],
                ['Sett primærdomene i kundepanelet', 'Ja'],
                ['Merk faktura betalt', 'Ja'],
                ['Registrer domene via Namecheap API', 'Ja (hvis API OK)'],
                ['Bind selv om registry fortsatt venter', 'Ja'],
                ['cPanel på Nebula (hvis WHM er på)', 'Ja etter hosting-betaling'],
            ],
            'manual_items' => [
                'Opprette nettstedmappe manuelt',
                'Sette primary_domain i kundens innstillinger for hånd',
                '«Sy» hosting og domene sammen i CMS i et ekstra steg',
            ],
            'intervene_items' => [
                ['Namecheap API / IP-whitelist', 'Registrering feiler → sjekk Domain orders, API-nøkkel, NC_CLIENT_IP i whitelist. Registrer manuelt i Namecheap ved behov, deretter bind/retry.'],
                ['DNS / navneservere', 'Domene må peke til hosting (dns1/dns2.namecheaphosting.com eller server-IP). Eksternt registrert domene: kunden eller du endrer NS.'],
                ['SSL', 'Ofte automatisk (Let’s Encrypt). Vent på DNS-propagering; sjekk sertifikat i cPanel.'],
                ['WHOIS-kontakter', 'Kunden skal fylle eierkontakter før kjøp (fanen Kontakter i Domener).'],
                ['Nebula-pool full (30 GB)', 'Ingen ny cPanel → frigjør plass eller oppgrader pool; bruk cPanel-pool i admin.'],
                ['Betaling mottatt, konto fortsatt pending', 'Sjekk Payments / payment-return; fullfør manuelt eller be kunden betale på nytt.'],
            ],
            'check_items' => [
                'Finnes betalt faktura for kunden under Invoices / admin klient?',
                'Er subscription_status = active og paid_until satt?',
                'Finnes domain-order for domenet (pending / active / registered)?',
                'Finnes mappen public_html/{username}/{domain}/ (Filer / File Manager)?',
                'Viser Domener i kundepanelet riktig primærdomene (ikke solaskinner.com)?',
                'Telegram/e-post: kom det ops-varsel ved registrering/betaling?',
                'WHM: er cPanel-pool enabled og er det ledig disk?',
            ],
            'infra_body' => 'To lag: (1) Stellar shared (solaffhv) — Solaskinner-panelet og CMS. (2) Reseller Nebula (bilomiwy / host15) — kunde-cPanel via WHM. «for bilohash.com» i Namecheap er bare primary-label på reseller-pakken, ikke kundens merkevare.',
            'notify_body' => 'Operatør får e-post på support@solaskinner.com og Telegram ved ny registrering, hosting-aktivering, fornyelse, domene og betalte fakturaer. Klienten får egen bekreftelse (unntatt ren registrering uten betaling).',
            'footer' => 'Oppsummert: normal flyt er helautomatisk. Du griper bare inn ved API-, DNS- eller cPanel-pool-feil.',
        ];
    }

    if ($lang === 'en') {
        return [
            'lead' => 'How hosting and domain are linked automatically after payment — and what to do when something fails.',
            'auto_title' => 'What happens automatically',
            'auto_lead' => 'After a successful payment (Stripe / PayPal / demo) you usually do not need to manually link domain and hosting in the CMS.',
            'flow_title' => 'Flow step by step',
            'manual_title' => 'What you usually do NOT need to do',
            'intervene_title' => 'When you need to intervene',
            'check_title' => 'Troubleshooting checklist',
            'infra_title' => 'Infrastructure (Stellar + Nebula)',
            'notify_title' => 'Notifications',
            'links_title' => 'Useful admin links',
            'steps' => [
                'Client registers with hosting and/or domain in cart (or buys a domain later in the panel).',
                'Payment is confirmed (Stripe/PayPal/demo).',
                'Hosting activates: subscription_status = active, paid_until is set.',
                'Domain: order is created → Namecheap API registration (if configured).',
                'Bind: primary/active domain + folder public_html/{user}/{domain}/.',
                'Invoices marked paid; optional: dedicated cPanel on Nebula reseller.',
                'Email to support@ + Telegram alert to operator.',
            ],
            'auto_table' => [
                ['Activate hosting subscription', 'Yes'],
                ['Site folder public_html/{user}/{domain}/', 'Yes'],
                ['Set primary domain in client panel', 'Yes'],
                ['Mark invoice paid', 'Yes'],
                ['Register domain via Namecheap API', 'Yes (if API OK)'],
                ['Bind even if registry still pending', 'Yes'],
                ['cPanel on Nebula (if WHM enabled)', 'Yes after hosting payment'],
            ],
            'manual_items' => [
                'Create the site folder manually',
                'Set primary_domain in client settings by hand',
                'Extra CMS step to “merge” hosting and domain',
            ],
            'intervene_items' => [
                ['Namecheap API / IP whitelist', 'Registration fails → check Domain orders, API key, NC_CLIENT_IP whitelist. Register manually in Namecheap if needed, then bind/retry.'],
                ['DNS / nameservers', 'Domain must point to hosting (dns1/dns2.namecheaphosting.com or server IP). External registrar: client or you change NS.'],
                ['SSL', 'Often automatic (Let’s Encrypt). Wait for DNS propagation; check certificate in cPanel.'],
                ['WHOIS contacts', 'Client should fill owner contacts before purchase (Contacts tab under Domains).'],
                ['Nebula pool full (30 GB)', 'No new cPanel → free space or upgrade pool; use cPanel pool in admin.'],
                ['Payment received, account still pending', 'Check Payments / payment-return; fulfill manually or ask client to pay again.'],
            ],
            'check_items' => [
                'Is there a paid invoice for the client (Invoices / client admin)?',
                'Is subscription_status = active and paid_until set?',
                'Is there a domain-order for the domain (pending / active / registered)?',
                'Does folder public_html/{username}/{domain}/ exist (Files / File Manager)?',
                'Does Domains in the client panel show the correct primary (not solaskinner.com)?',
                'Telegram/email: did ops get a registration/payment alert?',
                'WHM: is cPanel pool enabled and is disk free?',
            ],
            'infra_body' => 'Two layers: (1) Stellar shared (solaffhv) — Solaskinner panel and CMS. (2) Reseller Nebula (bilomiwy / host15) — client cPanels via WHM. “for bilohash.com” in Namecheap is only the reseller package primary label, not the client brand.',
            'notify_body' => 'Operators get email at support@solaskinner.com and Telegram on new registration, hosting activation, renewals, domains, and paid invoices. Clients get their own confirmation (except bare registration without payment).',
            'footer' => 'Summary: normal flow is fully automatic. Intervene only for API, DNS, or cPanel pool failures.',
        ];
    }

    // uk (default)
    return [
        'lead' => 'Як хостинг і домен з’єднуються автоматично після оплати — і що робити, якщо щось пішло не так.',
        'auto_title' => 'Що відбувається автоматично',
        'auto_lead' => 'Після успішної оплати (Stripe / PayPal / demo) вам зазвичай не потрібно вручну «зшивати» домен і хостинг у CMS.',
        'flow_title' => 'Ланцюг крок за кроком',
        'manual_title' => 'Що зазвичай НЕ треба робити вручну',
        'intervene_title' => 'Коли потрібні ваші дії',
        'check_title' => 'Чеклист, якщо щось не вийшло',
        'infra_title' => 'Інфраструктура (Stellar + Nebula)',
        'notify_title' => 'Сповіщення',
        'links_title' => 'Корисні посилання в адмінці',
        'steps' => [
            'Клієнт реєструється з хостингом і/або доменом у кошику (або купує домен пізніше в панелі).',
            'Оплата підтверджується (Stripe / PayPal / demo).',
            'Хостинг активується: subscription_status = active, виставляється paid_until.',
            'Домен: створюється order → реєстрація через Namecheap API (якщо налаштовано).',
            'Bind: primary/active domain + папка public_html/{user}/{domain}/.',
            'Рахунки позначаються оплаченими; за потреби — окремий cPanel на Reseller Nebula.',
            'Лист на support@ + повідомлення в Telegram оператору.',
        ],
        'auto_table' => [
            ['Активація тарифу хостингу', 'Так'],
            ['Папка сайту public_html/{user}/{domain}/', 'Так'],
            ['Primary domain у панелі клієнта', 'Так'],
            ['Рахунок → «оплачено»', 'Так'],
            ['Реєстрація домену в Namecheap API', 'Так (якщо API OK)'],
            ['Прив’язка навіть якщо registry ще pending', 'Так'],
            ['cPanel на Nebula (якщо WHM увімкнено)', 'Так після оплати хостингу'],
        ],
        'manual_items' => [
            'Створювати папку сайту вручну',
            'Прописувати primary_domain у клієнта вручну',
            'Окремим кроком «зшивати» домен і hosting у CMS',
        ],
        'intervene_items' => [
            ['Namecheap API / whitelist IP', 'Реєстрація домену не пройшла → перевірте Domain orders, API-ключ, NC_CLIENT_IP у whitelist Namecheap. За потреби зареєструйте домен вручну в Namecheap, потім bind/retry.'],
            ['DNS / NS', 'Домен має дивитись на ваш хостинг (dns1/dns2.namecheaphosting.com або IP сервера). Якщо домен куплений «деінде» — NS міняє клієнт або ви.'],
            ['SSL', 'Часто auto (Let’s Encrypt). Зачекайте DNS-пропагацію; перевірте сертифікат у cPanel.'],
            ['WHOIS-контакти', 'Клієнт має заповнити контакти власника перед покупкою (вкладка «Контакти» у Доменах).'],
            ['Пул Nebula повний (30 GB)', 'cPanel не створюється → звільніть місце або збільште пул; див. «Пул cPanel».'],
            ['Оплата є, акаунт ще pending', 'Перевірте Payments / payment-return; виконайте fulfill вручну або попросіть клієнта оплатити знову.'],
        ],
        'check_items' => [
            'Чи є оплачений рахунок у клієнта (Invoices / клієнти)?',
            'Чи subscription_status = active і paid_until заповнено?',
            'Чи є domain-order для домену (pending / active / registered)?',
            'Чи існує папка public_html/{username}/{domain}/ (Файли)?',
            'Чи в панелі клієнта «Домени» показує правильний primary (не solaskinner.com)?',
            'Telegram / email: чи прийшло ops-сповіщення про реєстрацію/оплату?',
            'WHM: чи cPanel pool enabled і чи є вільний диск?',
        ],
        'infra_body' => 'Два шари: (1) Stellar shared (solaffhv) — панель Solaskinner і CMS. (2) Reseller Nebula (bilomiwy / host15) — cPanel клієнтів через WHM. «for bilohash.com» у Namecheap — лише label primary підписки reseller, не бренд клієнта.',
        'notify_body' => 'Оператору: email на support@solaskinner.com і Telegram при реєстрації, активації хостингу, продовженні, доменах і оплаті рахунків. Клієнту — власне підтвердження (крім «голої» реєстрації без оплати).',
        'footer' => 'Підсумок: нормальний flow — повністю автоматичний. Втручаєтесь лише при збоях API, DNS або пулу cPanel.',
    ];
}

$g = hs_admin_ops_guide_copy($lang);

ob_start();
?>
<style>
.hs-ops-guide { max-width: 920px; }
.hs-ops-guide h2 { margin: 1.75rem 0 .65rem; font-size: 1.15rem; display: flex; align-items: center; gap: .5rem; }
.hs-ops-guide h2 i { color: var(--hs-accent, #ea580c); }
.hs-ops-guide .hs-ops-lead { font-size: 1.02rem; margin-bottom: 1rem; }
.hs-ops-guide ol.hs-ops-steps { margin: .5rem 0 0 1.2rem; line-height: 1.55; }
.hs-ops-guide ol.hs-ops-steps li { margin: .4rem 0; }
.hs-ops-guide table.hs-ops-table { width: 100%; border-collapse: collapse; margin-top: .5rem; }
.hs-ops-guide table.hs-ops-table th,
.hs-ops-guide table.hs-ops-table td { text-align: left; padding: .5rem .65rem; border-bottom: 1px solid var(--hs-border, #e5e7eb); font-size: .92rem; }
.hs-ops-guide table.hs-ops-table th { font-size: .72rem; text-transform: uppercase; color: #64748b; }
.hs-ops-guide .hs-ops-ok { color: #059669; font-weight: 700; }
.hs-ops-guide ul.hs-ops-list { margin: .4rem 0 0 1.15rem; line-height: 1.55; }
.hs-ops-guide .hs-ops-card { background: var(--hs-card, #fff); border: 1px solid var(--hs-border, #e5e7eb); border-radius: 12px; padding: 1rem 1.15rem; margin: .75rem 0; }
.hs-ops-guide .hs-ops-card h3 { margin: 0 0 .45rem; font-size: .95rem; }
.hs-ops-guide .hs-ops-card p { margin: 0; color: #475569; font-size: .9rem; line-height: 1.5; }
.hs-ops-guide .hs-ops-links { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .75rem; }
.hs-ops-guide .hs-ops-footer { margin-top: 1.5rem; padding: .9rem 1rem; border-radius: 10px; background: #fff7ed; border: 1px solid #fdba74; font-weight: 600; }
.hs-ops-guide code { font-size: .85em; background: #f1f5f9; padding: .1rem .35rem; border-radius: 4px; }
</style>

<div class="hs-ops-guide">
  <p class="hs-ops-lead hp-muted"><?= hs_h((string) $g['lead']) ?></p>

  <h2><i class="fa-solid fa-//"></i> <?= hs_h((string) $g['flow_title']) ?></h2>
  <div class="hs-ops-card">
    <ol class="hs-ops-steps">
      <?php foreach ($g['steps'] as $i => $step): ?>
        <li><strong><?= (int) $i + 1 ?>.</strong> <?= hs_h((string) $step) ?></li>
      <?php endforeach; ?>
    </ol>
  </div>

  <h2><i class="fa-solid fa-robot"></i> <?= hs_h((string) $g['auto_title']) ?></h2>
  <p class="hp-muted"><?= hs_h((string) $g['auto_lead']) ?></p>
  <table class="hs-ops-table">
    <thead>
      <tr>
        <th><?= hs_h($t['invoice_col_desc'] ?? 'Item') ?></th>
        <th><?= hs_h($lang === 'uk' ? 'Авто' : ($lang === 'no' ? 'Auto' : 'Auto')) ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($g['auto_table'] as $row): ?>
        <tr>
          <td><?= hs_h((string) $row[0]) ?></td>
          <td class="hs-ops-ok"><?= hs_h((string) $row[1]) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2><i class="fa-solid fa-ban"></i> <?= hs_h((string) $g['manual_title']) ?></h2>
  <ul class="hs-ops-list">
    <?php foreach ($g['manual_items'] as $item): ?>
      <li><?= hs_h((string) $item) ?></li>
    <?php endforeach; ?>
  </ul>

  <h2><i class="fa-solid fa-hand"></i> <?= hs_h((string) $g['intervene_title']) ?></h2>
  <?php foreach ($g['intervene_items'] as $item): ?>
    <div class="hs-ops-card">
      <h3><i class="fa-solid fa-triangle-exclamation" style="color:#d97706;margin-right:.35rem"></i><?= hs_h((string) $item[0]) ?></h3>
      <p><?= hs_h((string) $item[1]) ?></p>
    </div>
  <?php endforeach; ?>

  <h2><i class="fa-solid fa-list-check"></i> <?= hs_h((string) $g['check_title']) ?></h2>
  <ul class="hs-ops-list">
    <?php foreach ($g['check_items'] as $item): ?>
      <li><?= hs_h((string) $item) ?></li>
    <?php endforeach; ?>
  </ul>

  <h2><i class="fa-solid fa-server"></i> <?= hs_h((string) $g['infra_title']) ?></h2>
  <div class="hs-ops-card">
    <p><?= hs_h((string) $g['infra_body']) ?></p>
  </div>

  <h2><i class="fa-solid fa-bell"></i> <?= hs_h((string) $g['notify_title']) ?></h2>
  <div class="hs-ops-card">
    <p><?= hs_h((string) $g['notify_body']) ?></p>
  </div>

  <h2><i class="fa-solid fa-link"></i> <?= hs_h((string) $g['links_title']) ?></h2>
  <div class="hs-ops-links">
    <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('clients.php')) ?>"><i class="fa-solid fa-users"></i> <?= hs_h($t['admin_clients_manage'] ?? 'Clients') ?></a>
    <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('namecheap.php')) ?>"><i class="fa-solid fa-globe"></i> <?= hs_h($t['admin_namecheap_title'] ?? 'Domains') ?></a>
    <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('payments.php')) ?>"><i class="fa-solid fa-credit-card"></i> <?= hs_h($t['admin_payments_title'] ?? 'Payments') ?></a>
    <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('cpanel-pool.php')) ?>"><i class="fa-solid fa-server"></i> <?= hs_h($t['admin_cpanel_pool_title'] ?? 'cPanel pool') ?></a>
    <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('support.php')) ?>"><i class="fa-solid fa-headset"></i> <?= hs_h($t['admin_support_title'] ?? 'Support') ?></a>
  </div>

  <div class="hs-ops-footer">
    <i class="fa-solid fa-circle-info"></i>
    <?= hs_h((string) $g['footer']) ?>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';
