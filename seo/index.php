<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/seo-apps-catalog.php';

$L = in_array($lang, ['uk', 'en', 'no', 'lt', 'pl', 'sv'], true) ? $lang : 'en';
$titles = [
    'uk' => '15+ CMS безкоштовно + хостинг ЄС — SEO, AI, без коду | SolaSkinner',
    'en' => '15+ free CMS + EU hosting — SEO, AI content, no-code | SolaSkinner',
    'no' => '15+ gratis CMS + EU-hosting — SEO, AI, uten koding | SolaSkinner',
    'lt' => '15+ nemokamų CMS + ES hostingas — SEO, AI, be kodo | SolaSkinner',
    'pl' => '15+ darmowych CMS + hosting UE — SEO, AI, bez kodu | SolaSkinner',
    'sv' => '15+ gratis CMS + EU-hosting — SEO, AI, utan kod | SolaSkinner',
];
$descs = [
    'uk' => 'Shop, Booking, WordPress, AI for SEO, Today, Faktura… Встановіть CMS 0 € на EU SSD з SSL. AI-контент, SEO-структура, адмінка без програміста. Демо онлайн · реєстрація за хвилини · setup під ключ.',
    'en' => 'Shop, Booking, WordPress, AI for SEO, Today, Faktura… Install CMS free on EU SSD with SSL. AI content, SEO structure, no-code admin. Live demos · signup in minutes · full setup available.',
    'no' => 'Shop, Booking, WordPress, AI for SEO, Today, Faktura… Installer CMS gratis på EU-SSD med SSL. AI-innhold, SEO, admin uten koding. Live-demo · registrering på minutter.',
    'lt' => 'Shop, Booking, WordPress, AI for SEO, Today, Faktura… Įdiekite CMS nemokamai ant ES SSD su SSL. AI turinys, SEO, admin be kodo. Demo · greita registracija.',
    'pl' => 'Shop, Booking, WordPress, AI for SEO, Today, Faktura… Zainstaluj CMS za darmo na EU SSD z SSL. AI, SEO, panel bez kodu. Demo · szybka rejestracja.',
    'sv' => 'Shop, Booking, WordPress, AI for SEO, Today, Faktura… Installera CMS gratis på EU-SSD med SSL. AI-innehåll, SEO, admin utan kod. Live-demo · snabb registrering.',
];
$page_title = $titles[$L];
$seo = [
    'type' => 'page',
    'path' => 'seo/',
    'title' => $page_title,
    'description' => $descs[$L],
    'keywords' => 'free CMS hosting Europe, SEO shop booking wordpress, AI content no-code, SolaSkinner, cheap hosting Norway Germany Lithuania',
    'og_type' => 'website',
    'breadcrumb' => [
        ['name' => (string) ($t['nav_home'] ?? 'Home'), 'item' => rtrim(HS_CANONICAL_URL, '/') . '/'],
        ['name' => $page_title, 'item' => rtrim(HS_CANONICAL_URL, '/') . '/seo/'],
    ],
];

$copy = [
    'uk' => [
        'h1' => 'Готові CMS для продажів і клієнтів — уже в тарифі хостингу',
        'lead' => $descs[$L],
        'sub' => 'Оберіть продукт → дивіться демо → реєструйте хостинг → встановіть в 1 клік. Платите лише хостинг — CMS у комплекті.',
        'cta_reg' => 'Почати — реєстрація',
        'cta_domain' => 'Знайти домен',
        'cta_plans' => 'Тарифи хостингу',
        'value_title' => 'Чому це вигідно купувати',
        'values' => [
            ['i' => 'fa-tags', 't' => 'CMS 0 €', 'd' => 'CMS 0 € у тарифі — без окремої ліцензії на скрипт'],
            ['i' => 'fa-magnifying-glass-chart', 't' => 'SEO з коробки', 'd' => 'Структура, meta, швидкий EU SSD і HTTPS для органіки'],
            ['i' => 'fa-robot', 't' => 'AI-контент', 'd' => 'Швидше наповнення каталогу, послуг і сторінок'],
            ['i' => 'fa-user-check', 't' => 'Без коду', 'd' => 'Працівник керує цінами й контентом без програміста'],
            ['i' => 'fa-server', 't' => 'EU-хостинг', 'd' => 'SSD, SSL, панель EN/NO/UK/LT/PL/SV'],
            ['i' => 'fa-handshake', 't' => 'Під ключ', 'd' => 'Можемо встановити й налаштувати за вас'],
        ],
        'how_title' => 'Шлях клієнта до покупки',
        'how' => [
            'Відкрийте демо потрібної CMS',
            'Зареєструйте хостинг SolaSkinner',
            'Installer → 1 клік → домен + SSL → продажі',
        ],
        'pick' => 'Оберіть CMS для вашого бізнесу',
        'demo' => 'Демо',
        'more' => 'Детальніше та ціна хостингу →',
        'bottom_t' => 'Готові запустити сайт і приймати клієнтів?',
        'bottom_d' => 'Реєстрація займає хвилини. CMS у комплекті. Підтримка й setup — за потреби.',
    ],
    'en' => [
        'h1' => 'Ready-made CMS to win customers — included with hosting',
        'lead' => $descs[$L],
        'sub' => 'Pick a product → open the demo → register hosting → install in 1 click. Pay hosting only — CMS is included.',
        'cta_reg' => 'Start — sign up',
        'cta_domain' => 'Find a domain',
        'cta_plans' => 'Hosting plans',
        'value_title' => 'Why buyers choose this',
        'values' => [
            ['i' => 'fa-tags', 't' => 'CMS €0', 'd' => 'CMS €0 on the plan — no separate script license'],
            ['i' => 'fa-magnifying-glass-chart', 't' => 'SEO built-in', 'd' => 'Structure, meta, fast EU SSD and HTTPS for organic traffic'],
            ['i' => 'fa-robot', 't' => 'AI content', 'd' => 'Fill products, services and pages faster'],
            ['i' => 'fa-user-check', 't' => 'No-code', 'd' => 'Staff manage prices and content without a developer'],
            ['i' => 'fa-server', 't' => 'EU hosting', 'd' => 'SSD, SSL, panel EN/NO/UK/LT/PL/SV'],
            ['i' => 'fa-handshake', 't' => 'Done-for-you', 'd' => 'We can install and configure for you'],
        ],
        'how_title' => 'Path to purchase',
        'how' => [
            'Open the demo of the CMS you need',
            'Register SolaSkinner hosting',
            'Installer → 1 click → domain + SSL → sell',
        ],
        'pick' => 'Choose a CMS for your business',
        'demo' => 'Demo',
        'more' => 'Details & hosting offer →',
        'bottom_t' => 'Ready to launch and get customers?',
        'bottom_d' => 'Signup takes minutes. CMS included. Support and setup available.',
    ],
    'no' => [
        'h1' => 'Ferdige CMS for kunder — inkludert i hostingplanen',
        'lead' => $descs[$L],
        'sub' => 'Velg produkt → se demo → registrer hosting → installer med 1 klikk. Betal bare hosting — CMS er inkludert.',
        'cta_reg' => 'Start — registrer',
        'cta_domain' => 'Finn domene',
        'cta_plans' => 'Hostingplaner',
        'value_title' => 'Hvorfor kjøpe her',
        'values' => [
            ['i' => 'fa-tags', 't' => 'CMS 0 €', 'd' => 'CMS 0 € i planen — ingen egen skriptlisens'],
            ['i' => 'fa-magnifying-glass-chart', 't' => 'SEO innebygd', 'd' => 'Struktur, meta, rask EU-SSD og HTTPS'],
            ['i' => 'fa-robot', 't' => 'AI-innhold', 'd' => 'Raskere produkt- og tjenestetekster'],
            ['i' => 'fa-user-check', 't' => 'Uten koding', 'd' => 'Ansatte styrer priser og innhold'],
            ['i' => 'fa-server', 't' => 'EU-hosting', 'd' => 'SSD, SSL, flerspråklig panel'],
            ['i' => 'fa-handshake', 't' => 'Vi setter opp', 'd' => 'Install og config for deg'],
        ],
        'how_title' => 'Veien til kjøp',
        'how' => ['Åpne demo', 'Registrer hosting', '1-klikks install → domene → salg'],
        'pick' => 'Velg CMS for virksomheten',
        'demo' => 'Demo',
        'more' => 'Detaljer og hostingtilbud →',
        'bottom_t' => 'Klar til å starte og få kunder?',
        'bottom_d' => 'Registrering på minutter. CMS inkludert. Support og setup tilgjengelig.',
    ],
    'lt' => [
        'h1' => 'Paruoštos CMS klientams — jau įskaičiuota į hostingą',
        'lead' => $descs[$L],
        'sub' => 'Pasirinkite → demo → registracija → 1 paspaudimu. Mokate tik hostingą — CMS įskaičiuota.',
        'cta_reg' => 'Pradėti — registracija',
        'cta_domain' => 'Rasti domeną',
        'cta_plans' => 'Hosting planai',
        'value_title' => 'Kodėl verta pirkti',
        'values' => [
            ['i' => 'fa-tags', 't' => 'CMS 0 €', 'd' => 'CMS 0 € plane — be atskiros skripto licencijos'],
            ['i' => 'fa-magnifying-glass-chart', 't' => 'SEO', 'd' => 'Struktūra, meta, greitas ES SSD'],
            ['i' => 'fa-robot', 't' => 'AI', 'd' => 'Greitesnis turinys'],
            ['i' => 'fa-user-check', 't' => 'Be kodo', 'd' => 'Darbuotojai valdo patys'],
            ['i' => 'fa-server', 't' => 'ES hostingas', 'd' => 'SSD, SSL, daugiakalbis skydelis'],
            ['i' => 'fa-handshake', 't' => 'Setup', 'd' => 'Galime sukonfigūruoti už jus'],
        ],
        'how_title' => 'Kelias iki pirkimo',
        'how' => ['Atidarykite demo', 'Registruokite hostingą', '1 paspaudimas → domenas → pardavimai'],
        'pick' => 'Pasirinkite CMS verslui',
        'demo' => 'Demo',
        'more' => 'Detaliau ir hostingo pasiūlymas →',
        'bottom_t' => 'Pasiruošę paleisti ir gauti klientų?',
        'bottom_d' => 'Registracija greita. CMS įskaičiuota. Pagalba ir setup — pagal poreikį.',
    ],
    'pl' => [
        'h1' => 'Gotowe CMS dla klientów — już w cenie hostingu',
        'lead' => $descs[$L],
        'sub' => 'Wybierz → demo → rejestracja → 1 klik. Płacisz tylko hosting — CMS w zestawie.',
        'cta_reg' => 'Zacznij — rejestracja',
        'cta_domain' => 'Znajdź domenę',
        'cta_plans' => 'Plany hostingu',
        'value_title' => 'Dlaczego warto kupić',
        'values' => [
            ['i' => 'fa-tags', 't' => 'CMS 0 €', 'd' => 'CMS 0 € w planie — bez osobnej licencji skryptu'],
            ['i' => 'fa-magnifying-glass-chart', 't' => 'SEO', 'd' => 'Struktura, meta, szybki EU SSD'],
            ['i' => 'fa-robot', 't' => 'AI', 'd' => 'Szybsze treści'],
            ['i' => 'fa-user-check', 't' => 'Bez kodu', 'd' => 'Pracownicy zarządzają sami'],
            ['i' => 'fa-server', 't' => 'Hosting UE', 'd' => 'SSD, SSL, panel wielojęzyczny'],
            ['i' => 'fa-handshake', 't' => 'Setup', 'd' => 'Możemy skonfigurować za Ciebie'],
        ],
        'how_title' => 'Ścieżka zakupu',
        'how' => ['Otwórz demo', 'Zarejestruj hosting', '1 klik → domena → sprzedaż'],
        'pick' => 'Wybierz CMS dla biznesu',
        'demo' => 'Demo',
        'more' => 'Szczegóły i oferta hostingu →',
        'bottom_t' => 'Gotowy na start i klientów?',
        'bottom_d' => 'Rejestracja w minuty. CMS w zestawie. Support i setup dostępne.',
    ],
    'sv' => [
        'h1' => 'Färdiga CMS för kunder — ingår i hostingplanen',
        'lead' => $descs[$L],
        'sub' => 'Välj → demo → registrera → 1 klick. Betala bara hosting — CMS ingår.',
        'cta_reg' => 'Starta — registrera',
        'cta_domain' => 'Hitta domän',
        'cta_plans' => 'Hostingplaner',
        'value_title' => 'Varför köpa här',
        'values' => [
            ['i' => 'fa-tags', 't' => 'CMS 0 €', 'd' => 'CMS 0 € i planen — ingen separat skriptlicens'],
            ['i' => 'fa-magnifying-glass-chart', 't' => 'SEO', 'd' => 'Struktur, meta, snabb EU-SSD'],
            ['i' => 'fa-robot', 't' => 'AI', 'd' => 'Snabbare innehåll'],
            ['i' => 'fa-user-check', 't' => 'Utan kod', 'd' => 'Personal hanterar själva'],
            ['i' => 'fa-server', 't' => 'EU-hosting', 'd' => 'SSD, SSL, flerspråkig panel'],
            ['i' => 'fa-handshake', 't' => 'Setup', 'd' => 'Vi kan sätta upp åt dig'],
        ],
        'how_title' => 'Vägen till köp',
        'how' => ['Öppna demo', 'Registrera hosting', '1 klick → domän → sälj'],
        'pick' => 'Välj CMS för din verksamhet',
        'demo' => 'Demo',
        'more' => 'Detaljer och hostingerbjudande →',
        'bottom_t' => 'Redo att lansera och få kunder?',
        'bottom_d' => 'Registrering tar minuter. CMS ingår. Support och setup finns.',
    ],
];
$c = $copy[$L] ?? $copy['en'];

ob_start();
?>
<div class="hs-seo-hub">
  <header class="hs-seo-hub-head">
    <p class="hs-seo-app-kicker"><i class="fa-solid fa-sun" aria-hidden="true"></i> SolaSkinner · CMS + hosting</p>
    <h1><?= hs_h($c['h1']) ?></h1>
    <p class="hs-seo-hub-lead"><?= hs_h($c['lead']) ?></p>
    <p class="hp-muted"><?= hs_h($c['sub']) ?></p>
    <div class="hs-seo-app-ctas" style="margin-top:1rem">
      <a class="hs-btn hs-btn-primary" href="<?= hs_h(hs_url('register.php')) ?>"><i class="fa-solid fa-rocket"></i> <?= hs_h($c['cta_reg']) ?></a>
      <a class="hs-btn hs-btn-ghost" href="<?= hs_h(hs_url('domain')) ?>"><i class="fa-solid fa-globe"></i> <?= hs_h($c['cta_domain']) ?></a>
      <a class="hs-btn hs-btn-ghost" href="<?= hs_h(hs_url('register.php')) ?>#plans"><i class="fa-solid fa-tags"></i> <?= hs_h($c['cta_plans']) ?></a>
    </div>
  </header>

  <section class="hs-seo-hub-values" aria-label="value">
    <h2><?= hs_h($c['value_title']) ?></h2>
    <div class="hs-seo-value-grid">
      <?php foreach ($c['values'] as $v): ?>
        <article class="hs-seo-value-card">
          <span class="hs-seo-hub-icon"><i class="fa-solid <?= hs_h($v['i']) ?>" aria-hidden="true"></i></span>
          <strong><?= hs_h($v['t']) ?></strong>
          <span><?= hs_h($v['d']) ?></span>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="hs-seo-hub-how">
    <h2><?= hs_h($c['how_title']) ?></h2>
    <ol class="hs-seo-app-steps">
      <?php foreach ($c['how'] as $i => $step): ?>
        <li><span class="hs-seo-app-step-num"><?= (int) $i + 1 ?></span> <?= hs_h($step) ?></li>
      <?php endforeach; ?>
    </ol>
  </section>

  <h2 class="hs-seo-hub-pick"><?= hs_h($c['pick']) ?></h2>
  <div class="hs-seo-hub-grid">
    <?php
    if (is_file(dirname(__DIR__) . '/includes/seo-app-page.php')) {
        require_once dirname(__DIR__) . '/includes/seo-app-page.php';
    }
    foreach (hs_seo_apps_order() as $slug):
        $raw = hs_seo_app($slug);
        if ($raw === null) {
            continue;
        }
        $app = hs_seo_app_lang($raw, $L);
        $iconClass = !empty($raw['icon_brand']) ? 'fa-brands ' . $app['icon'] : 'fa-solid ' . $app['icon'];
        $demoUrl = function_exists('hs_seo_app_demo_url')
            ? hs_seo_app_demo_url($slug)
            : (function_exists('hs_app_demo_url') ? hs_app_demo_url($slug) : 'https://bilohash.com/');
        ?>
      <article class="hs-seo-hub-card-wrap">
        <?php
          $langQs = ($L !== '' && $L !== 'en') ? ['lang' => $L] : [];
          $seoHref = hs_url('seo/' . (string) $app['file'], $langQs);
        ?>
        <a class="hs-seo-hub-card" href="<?= hs_h($seoHref) ?>">
          <span class="hs-seo-hub-icon"><i class="<?= hs_h($iconClass) ?>" aria-hidden="true"></i></span>
          <strong><?= hs_h($app['name']) ?></strong>
          <span class="hs-seo-hub-card-desc"><?= hs_h($app['tagline']) ?></span>
          <span class="hs-seo-hub-more"><?= hs_h($c['more']) ?></span>
        </a>
        <div class="hs-seo-hub-card-actions">
          <a class="hs-seo-hub-demo" href="<?= hs_h($demoUrl) ?>" target="_blank" rel="noopener noreferrer">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> <?= hs_h($c['demo']) ?>
          </a>
          <a class="hs-seo-hub-buy" href="<?= hs_h(hs_url('register.php', $langQs)) ?>">
            <i class="fa-solid fa-rocket" aria-hidden="true"></i> <?= hs_h($c['cta_reg']) ?>
          </a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <section class="hs-seo-app-bottom hs-seo-app-bottom-strong" style="margin-top:2.5rem">
    <h2><?= hs_h($c['bottom_t']) ?></h2>
    <p><?= hs_h($c['bottom_d']) ?></p>
    <div class="hs-seo-app-ctas">
      <a class="hs-btn hs-btn-primary" href="<?= hs_h(hs_url('register.php')) ?>"><i class="fa-solid fa-rocket"></i> <?= hs_h($c['cta_reg']) ?></a>
      <a class="hs-btn hs-btn-ghost" href="<?= hs_h(hs_url('domain')) ?>"><i class="fa-solid fa-globe"></i> <?= hs_h($c['cta_domain']) ?></a>
    </div>
  </section>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-public.php';
