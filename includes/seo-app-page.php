<?php
declare(strict_types=1);

require_once __DIR__ . '/seo-apps-catalog.php';
if (is_file(__DIR__ . '/ecosystem-catalog.php')) {
    require_once __DIR__ . '/ecosystem-catalog.php';
}

/**
 * Demo URL on bilohash.com for SEO product landings.
 */
function hs_seo_app_demo_url(string $slug): string
{
    if (function_exists('hs_app_demo_url')) {
        return hs_app_demo_url($slug);
    }
    $map = [
        'shop' => 'https://bilohash.com/shop/site/',
        'booking' => 'https://bilohash.com/booking/site/',
        'auction' => 'https://bilohash.com/auction/site/',
        'freelance' => 'https://bilohash.com/freelance/site/',
        'pizza' => 'https://bilohash.com/pizza/site/',
        'today' => 'https://bilohash.com/today/',
        'gamehub' => 'https://bilohash.com/gamehub/site/',
        'tavle' => 'https://bilohash.com/tavle/site/',
        'faktura' => 'https://bilohash.com/faktura/',
        'lending' => 'https://bilohash.com/lending/',
        'hosting' => 'https://bilohash.com/hosting/',
        'news' => 'https://bilohash.com/news/',
        'wordpress' => 'https://bilohash.com/wordpress/',
        '3d' => 'https://bilohash.com/3d/',
        'ai' => 'https://bilohash.com/ai/',
    ];

    return $map[$slug] ?? ('https://bilohash.com/' . rawurlencode($slug) . '/');
}

/**
 * Render a full SEO landing for one app (called from seo/hosting-for-*.php).
 */
function hs_seo_render_app_page(string $slug): void
{
    global $lang, $t;

    $appRaw = hs_seo_app($slug);
    if ($appRaw === null) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }
    $app = hs_seo_app_lang($appRaw, (string) $lang);
    $name = $app['name'];
    $demoUrl = (string) ($app['demo_url'] ?? '') !== ''
        ? (string) $app['demo_url']
        : hs_seo_app_demo_url($slug);
    $productUrl = (string) ($app['product_url'] ?? '');
    $base = rtrim(defined('HS_CANONICAL_URL') ? HS_CANONICAL_URL : 'https://solaskinner.com', '/');

    $titleMap = [
        'uk' => "{$name} безкоштовно + хостинг ЄС — запуск за 1 клік | SolaSkinner",
        'en' => "Free {$name} CMS + EU hosting — launch in 1 click | SolaSkinner",
        'no' => "Gratis {$name} CMS + EU-hosting — live på 1 klikk | SolaSkinner",
        'lt' => "Nemokama {$name} CMS + ES hostingas — paleiskite 1 paspaudimu | SolaSkinner",
        'pl' => "Darmowy CMS {$name} + hosting UE — start w 1 klik | SolaSkinner",
        'sv' => "Gratis {$name}-CMS + EU-hosting — live på 1 klick | SolaSkinner",
    ];
    $descMap = [
        'uk' => "Встановіть {$name} безкоштовно на SolaSkinner: SEO-структура, AI для контенту, адмінка без коду. Платите лише хостинг (SSD, SSL, EU). Демо онлайн · налаштуємо під ключ. " . mb_substr($app['tagline'], 0, 90),
        'en' => "Install {$name} free on SolaSkinner: SEO structure, AI content help, no-code admin. Pay hosting only (SSD, SSL, EU). Live demo · we can set it up. " . mb_substr($app['tagline'], 0, 90),
        'no' => "Installer {$name} gratis på SolaSkinner: SEO, AI-innhold, admin uten koding. Betal bare hosting (SSD, SSL, EU). Live-demo · vi kan sette opp. " . mb_substr($app['tagline'], 0, 90),
        'lt' => "Įdiekite {$name} nemokamai SolaSkinner: SEO, AI turinys, admin be kodo. Mokate tik už hostingą (SSD, SSL, ES). Demo · galime sukonfigūruoti. " . mb_substr($app['tagline'], 0, 90),
        'pl' => "Zainstaluj {$name} za darmo na SolaSkinner: SEO, AI do treści, panel bez kodu. Płacisz tylko hosting (SSD, SSL, UE). Demo · skonfigurujemy. " . mb_substr($app['tagline'], 0, 90),
        'sv' => "Installera {$name} gratis på SolaSkinner: SEO, AI-innehåll, admin utan kod. Betala bara hosting (SSD, SSL, EU). Live-demo · vi sätter upp. " . mb_substr($app['tagline'], 0, 90),
    ];
    $L = in_array($lang, ['uk', 'en', 'no', 'lt', 'pl', 'sv'], true) ? $lang : 'en';
    $page_title = $titleMap[$L] ?? $titleMap['en'];
    $seo = [
        'type' => 'page',
        'path' => 'seo/' . $app['file'],
        'title' => $page_title,
        'description' => $descMap[$L] ?? $descMap['en'],
        'keywords' => $app['keywords'],
        'og_type' => 'article',
        'breadcrumb' => [
            ['name' => (string) ($t['nav_home'] ?? 'Home'), 'item' => $base . '/'],
            ['name' => (string) ($t['seo_hub_nav'] ?? 'CMS hosting'), 'item' => $base . '/seo/'],
            ['name' => $name, 'item' => $base . '/seo/' . $app['file']],
        ],
    ];

    $ui = [
        'uk' => [
            'how' => 'Як запустити бізнес-сайт за 3 кроки',
            'steps' => [
                'Створіть акаунт і оберіть план хостингу (CMS у комплекті безкоштовно)',
                'У панелі: Websites → Installer → ' . $name,
                'Підключіть домен + SSL — приймайте клієнтів уже сьогодні',
            ],
            'features' => 'Що отримуєте з ' . $name,
            'use' => 'Кому це вигідно',
            'faq' => 'Питання перед покупкою',
            'cta_reg' => 'Почати — реєстрація',
            'cta_reg_strong' => 'Запустити ' . $name . ' зараз',
            'cta_domain' => 'Купити / знайти домен',
            'cta_demo' => 'Дивитись живе демо',
            'cta_all' => 'Усі 15+ CMS',
            'related' => 'Клієнти також обирають',
            'why' => 'Чому купують хостинг саме на SolaSkinner',
            'why_items' => [
                '15+ CMS у комплекті з хостингом — окрема ліцензія на CMS не потрібна',
                'SEO з коробки: чисті URL, meta, швидкий EU SSD і HTTPS — більше органічного трафіку',
                'AI прискорює тексти й публікації — швидше вихід у продаж / запис / каталог',
                'Адмінка для співробітника без IT: ціни, контент, замовлення без програміста',
                'Під ключ за запитом: установка, домен, SSL, онбординг вашою командою',
                '15+ продуктів в 1 акаунті · панель UK / EN / NO / LT / PL / SV',
            ],
            'offer_title' => $name . ' у подарунок до хостингу',
            'offer_body' => 'Усі, хто купує хостинг, отримують ' . $name . ' безкоштовно. Встановлення в 1 клік на європейському SSD з безкоштовним SSL. Демо вже онлайн — реєстрація займає хвилини.',
            'offer_bullets' => [
                '0 € за ліцензію CMS на тарифах SolaSkinner',
                'Готовий SEO + AI-контент + проста адмінка',
                'EU-сервери · HTTPS · підтримка',
            ],
            'trust' => ['Безкоштовний SSL', 'EU SSD', '1 клік install', '15+ CMS', 'Мультимова панель', 'Можна під ключ'],
            'compare_title' => 'Що ви отримуєте на SolaSkinner',
            'compare_rows' => [
                ['Ліцензія CMS', 'Уже в тарифі', $name . ' 0 € + хостинг'],
                ['Контент і SEO', 'AI + структура в комплекті', 'Готово до органічного пошуку'],
                ['Контроль даних', 'Ваш хостинг і домен', 'Дані у вашому акаунті'],
                ['Запуск', '1 клік + демо сьогодні', 'Від реєстрації до live за хвилини'],
            ],
            'proof_title' => 'Що кажуть аргументи продажів',
            'proof_items' => [
                'Швидший time-to-market: від реєстрації до live-сайту за хвилини',
                'Прозора ціна: платите хостинг — CMS у комплекті',
                'Масштаб: один хостинг — Shop, Booking, WordPress, AI for SEO…',
            ],
            'bottom_title' => 'Готові приймати клієнтів з ' . $name . '?',
            'bottom_lead' => 'Зареєструйте хостинг, встановіть CMS і підключіть домен. Потрібна допомога — зробимо setup за вас.',
            'demo_note' => 'Живе демо продукту (bilohash.com) — перевірте UX перед покупкою хостингу',
            'kicker' => 'Безкоштовна CMS · SEO · AI · без коду',
            'h1' => $name . ' + хостинг у Європі',
            'h1_sub' => 'Запуск бізнесу онлайн без програміста — CMS уже в тарифі',
        ],
        'en' => [
            'how' => 'Go live in 3 steps',
            'steps' => [
                'Create an account and pick a hosting plan (CMS included free)',
                'In the panel: Websites → Installer → ' . $name,
                'Connect domain + SSL — start selling / booking today',
            ],
            'features' => 'What you get with ' . $name,
            'use' => 'Built for',
            'faq' => 'Before you buy',
            'cta_reg' => 'Start free signup',
            'cta_reg_strong' => 'Launch ' . $name . ' now',
            'cta_domain' => 'Find / buy a domain',
            'cta_demo' => 'See live demo',
            'cta_all' => 'All 15+ CMS apps',
            'related' => 'Customers also choose',
            'why' => 'Why buyers choose SolaSkinner hosting',
            'why_items' => [
                '15+ CMS included with hosting — no extra CMS license needed',
                'SEO-ready structure, meta, fast EU SSD and HTTPS for organic traffic',
                'AI helps publish content faster so you sell / book sooner',
                'No-code admin for staff: prices, content, orders without a developer',
                'Optional full setup: install, domain, SSL, onboarding by our team',
                '15+ apps in one account · panel EN / NO / UK / LT / PL / SV',
            ],
            'offer_title' => $name . ' free with your hosting',
            'offer_body' => 'Every hosting plan includes ' . $name . ' free. Install in one click on EU SSD with free SSL. Live demo is online — signup takes minutes.',
            'offer_bullets' => [
                '€0 CMS license on SolaSkinner plans',
                'SEO + AI content + simple admin included',
                'EU servers · HTTPS · human support',
            ],
            'trust' => ['Free SSL', 'EU SSD', '1-click install', '15+ CMS', 'Multilingual panel', 'Setup available'],
            'compare_title' => 'What you get with SolaSkinner',
            'compare_rows' => [
                ['CMS license', 'Included in the plan', $name . ' €0 + hosting'],
                ['Content & SEO', 'AI + structure included', 'Ready for organic search'],
                ['Data control', 'Your hosting and domain', 'Data stays in your account'],
                ['Launch speed', '1 click + demo today', 'Register → live in minutes'],
            ],
            'proof_title' => 'Conversion advantages',
            'proof_items' => [
                'Faster time-to-market: register → live site in minutes',
                'Clear pricing: pay hosting — CMS is included',
                'Scale: one host for Shop, Booking, WordPress, AI for SEO…',
            ],
            'bottom_title' => 'Ready to win customers with ' . $name . '?',
            'bottom_lead' => 'Register hosting, install the CMS, connect a domain. Need help? We set it up for you.',
            'demo_note' => 'Live product demo (bilohash.com) — try the UX before you buy hosting',
            'kicker' => 'Free CMS · SEO · AI · no-code',
            'h1' => $name . ' + hosting in Europe',
            'h1_sub' => 'Launch online without a developer — CMS is already in the plan',
        ],
        'no' => [
            'how' => 'Live på 3 trinn',
            'steps' => [
                'Opprett konto og velg hostingplan (CMS gratis inkludert)',
                'I panelet: Websites → Installer → ' . $name,
                'Koble domene + SSL — ta imot kunder i dag',
            ],
            'features' => 'Dette får du med ' . $name,
            'use' => 'Passer for',
            'faq' => 'Før du kjøper',
            'cta_reg' => 'Start registrering',
            'cta_reg_strong' => 'Start ' . $name . ' nå',
            'cta_domain' => 'Finn / kjøp domene',
            'cta_demo' => 'Se live-demo',
            'cta_all' => 'Alle 15+ CMS',
            'related' => 'Kunder velger også',
            'why' => 'Hvorfor kjøpe hosting hos SolaSkinner',
            'why_items' => [
                '15+ CMS inkludert med hosting — ingen egen CMS-lisens',
                'SEO-klart: struktur, meta, rask EU-SSD og HTTPS',
                'AI hjelper med innhold — raskere ut i markedet',
                'Admin uten koding for ansatte',
                'Valgfri full setup: install, domene, SSL',
                '15+ apper i én konto · panel EN / NO / UK / LT / PL / SV',
            ],
            'offer_title' => $name . ' gratis med hostingen',
            'offer_body' => 'Alle hostingplaner inkluderer ' . $name . ' gratis. Installer med ett klikk på EU-SSD med gratis SSL. Live-demo er online.',
            'offer_bullets' => [
                '0 € CMS-lisens på SolaSkinner-planer',
                'SEO + AI-innhold + enkel admin',
                'EU-servere · HTTPS · support',
            ],
            'trust' => ['Gratis SSL', 'EU SSD', '1-klikks install', '15+ CMS', 'Flerspråklig panel', 'Setup mulig'],
            'compare_title' => 'Dette får du hos SolaSkinner',
            'compare_rows' => [
                ['CMS-lisens', 'Inkludert i planen', $name . ' 0 € + hosting'],
                ['Innhold & SEO', 'AI + struktur inkludert', 'Klar for organisk søk'],
                ['Datakontroll', 'Din hosting og ditt domene', 'Data i din konto'],
                ['Lansering', '1 klikk + demo i dag', 'Registrer → live på minutter'],
            ],
            'proof_title' => 'Salgsfordeler',
            'proof_items' => [
                'Raskere time-to-market: registrer → live på minutter',
                'Tydelig pris: betal hosting — CMS er inkludert',
                'Skaler: én host for Shop, Booking, WordPress, AI…',
            ],
            'bottom_title' => 'Klar for kunder med ' . $name . '?',
            'bottom_lead' => 'Registrer hosting, installer CMS, koble domene. Trenger hjelp? Vi setter opp.',
            'demo_note' => 'Live produktdemo (bilohash.com) — test UX før du kjøper hosting',
            'kicker' => 'Gratis CMS · SEO · AI · uten koding',
            'h1' => $name . ' + hosting i Europa',
            'h1_sub' => 'Start online uten utvikler — CMS er allerede i planen',
        ],
        'lt' => [
            'how' => 'Paleiskite per 3 žingsnius',
            'steps' => [
                'Sukurkite paskyrą ir pasirinkite planą (CMS nemokamai)',
                'Skydelyje: Websites → Installer → ' . $name,
                'Prijunkite domeną + SSL — priimkite klientus šiandien',
            ],
            'features' => 'Ką gaunate su ' . $name,
            'use' => 'Kam tinka',
            'faq' => 'Prieš perkant',
            'cta_reg' => 'Pradėti registraciją',
            'cta_reg_strong' => 'Paleisti ' . $name . ' dabar',
            'cta_domain' => 'Rasti / pirkti domeną',
            'cta_demo' => 'Žiūrėti demo',
            'cta_all' => 'Visos 15+ CMS',
            'related' => 'Klientai taip pat renkasi',
            'why' => 'Kodėl rinktis SolaSkinner hostingą',
            'why_items' => [
                '15+ CMS įskaičiuota į hostingą — atskiros CMS licencijos nereikia',
                'SEO struktūra, meta, greitas ES SSD ir HTTPS',
                'AI padeda publikuoti turinį greičiau',
                'Administravimas be kodo darbuotojams',
                'Galimas pilnas setup: diegimas, domenas, SSL',
                '15+ programų vienoje paskyroje',
            ],
            'offer_title' => $name . ' nemokamai su hostingu',
            'offer_body' => 'Kiekvienas hosting planas apima ' . $name . ' nemokamai. Įdiekite 1 paspaudimu ant ES SSD su nemokamu SSL. Demo jau online.',
            'offer_bullets' => [
                '0 € CMS licencija SolaSkinner planuose',
                'SEO + AI turinys + paprasta admin',
                'ES serveriai · HTTPS · pagalba',
            ],
            'trust' => ['Nemokamas SSL', 'ES SSD', '1 paspaudimas', '15+ CMS', 'Daugiakalbis skydelis', 'Setup įmanomas'],
            'compare_title' => 'Ką gaunate su SolaSkinner',
            'compare_rows' => [
                ['CMS licencija', 'Įskaičiuota į planą', $name . ' 0 € + hostingas'],
                ['Turinys ir SEO', 'AI + struktūra įskaičiuota', 'Paruošta organinei paieškai'],
                ['Duomenų kontrolė', 'Jūsų hostingas ir domenas', 'Duomenys jūsų paskyroje'],
                ['Paleidimas', '1 paspaudimas + demo', 'Registracija → live per minutes'],
            ],
            'proof_title' => 'Pardavimo pranašumai',
            'proof_items' => [
                'Greitesnis startas: registracija → live per minutes',
                'Aiški kaina: mokate hostingą — CMS įskaičiuota',
                'Mastelis: vienas hostas Shop, Booking, WordPress, AI…',
            ],
            'bottom_title' => 'Pasiruošę klientams su ' . $name . '?',
            'bottom_lead' => 'Registruokite hostingą, įdiekite CMS, prijunkite domeną. Reikia pagalbos — sukonfigūruosime.',
            'demo_note' => 'Gyvas demo (bilohash.com) — išbandykite UX prieš pirkdami',
            'kicker' => 'Nemokama CMS · SEO · AI · be kodo',
            'h1' => $name . ' + hostingas Europoje',
            'h1_sub' => 'Pradėkite online be programuotojo — CMS jau įskaičiuota į planą',
        ],
        'pl' => [
            'how' => 'Start w 3 krokach',
            'steps' => [
                'Załóż konto i wybierz plan (CMS w cenie za darmo)',
                'W panelu: Websites → Installer → ' . $name,
                'Podłącz domenę + SSL — przyjmuj klientów dziś',
            ],
            'features' => 'Co zyskujesz z ' . $name,
            'use' => 'Dla kogo',
            'faq' => 'Przed zakupem',
            'cta_reg' => 'Zacznij rejestrację',
            'cta_reg_strong' => 'Uruchom ' . $name . ' teraz',
            'cta_domain' => 'Znajdź / kup domenę',
            'cta_demo' => 'Zobacz demo',
            'cta_all' => 'Wszystkie 15+ CMS',
            'related' => 'Klienci wybierają też',
            'why' => 'Dlaczego kupują hosting SolaSkinner',
            'why_items' => [
                '15+ CMS w cenie hostingu — bez osobnej licencji CMS',
                'SEO: struktura, meta, szybki EU SSD i HTTPS',
                'AI przyspiesza treści i publikacje',
                'Panel bez kodu dla pracowników',
                'Opcjonalny setup: instalacja, domena, SSL',
                '15+ aplikacji na jednym koncie',
            ],
            'offer_title' => $name . ' gratis z hostingiem',
            'offer_body' => 'Każdy plan hostingu obejmuje ' . $name . ' za darmo. Instalacja 1 kliknięciem na EU SSD z darmowym SSL. Demo jest online.',
            'offer_bullets' => [
                '0 € licencja CMS na planach SolaSkinner',
                'SEO + AI + prosty panel',
                'Serwery UE · HTTPS · support',
            ],
            'trust' => ['Darmowy SSL', 'EU SSD', '1-klik install', '15+ CMS', 'Wielojęzyczny panel', 'Setup dostępny'],
            'compare_title' => 'Co zyskujesz na SolaSkinner',
            'compare_rows' => [
                ['Licencja CMS', 'W cenie planu', $name . ' 0 € + hosting'],
                ['Treść i SEO', 'AI + struktura w zestawie', 'Gotowe pod wyszukiwarki'],
                ['Kontrola danych', 'Twój hosting i domena', 'Dane na Twoim koncie'],
                ['Start', '1 klik + demo dziś', 'Rejestracja → live w minuty'],
            ],
            'proof_title' => 'Przewagi sprzedażowe',
            'proof_items' => [
                'Szybszy time-to-market: rejestracja → live w minuty',
                'Jasna cena: płacisz hosting — CMS w zestawie',
                'Skala: jeden host na Shop, Booking, WordPress, AI…',
            ],
            'bottom_title' => 'Gotowy na klientów z ' . $name . '?',
            'bottom_lead' => 'Zarejestruj hosting, zainstaluj CMS, podłącz domenę. Potrzebujesz pomocy — skonfigurujemy.',
            'demo_note' => 'Live demo (bilohash.com) — sprawdź UX przed zakupem hostingu',
            'kicker' => 'Darmowy CMS · SEO · AI · bez kodu',
            'h1' => $name . ' + hosting w Europie',
            'h1_sub' => 'Start online bez programisty — CMS już w planie',
        ],
        'sv' => [
            'how' => 'Live på 3 steg',
            'steps' => [
                'Skapa konto och välj hostingplan (CMS gratis)',
                'I panelen: Websites → Installer → ' . $name,
                'Koppla domän + SSL — ta emot kunder idag',
            ],
            'features' => 'Det du får med ' . $name,
            'use' => 'För dig som',
            'faq' => 'Innan du köper',
            'cta_reg' => 'Starta registrering',
            'cta_reg_strong' => 'Starta ' . $name . ' nu',
            'cta_domain' => 'Hitta / köp domän',
            'cta_demo' => 'Se live-demo',
            'cta_all' => 'Alla 15+ CMS',
            'related' => 'Kunder väljer också',
            'why' => 'Varför köpa hosting hos SolaSkinner',
            'why_items' => [
                '15+ CMS ingår med hosting — ingen separat CMS-licens',
                'SEO-klart: struktur, meta, snabb EU-SSD och HTTPS',
                'AI hjälper dig publicera snabbare',
                'Admin utan kod för personal',
                'Valfri full setup: install, domän, SSL',
                '15+ appar på ett konto',
            ],
            'offer_title' => $name . ' gratis med hostingen',
            'offer_body' => 'Varje hostingplan inkluderar ' . $name . ' gratis. Installera med ett klick på EU-SSD med gratis SSL. Live-demo är online.',
            'offer_bullets' => [
                '0 € CMS-licens på SolaSkinner-planer',
                'SEO + AI-innehåll + enkel admin',
                'EU-servrar · HTTPS · support',
            ],
            'trust' => ['Gratis SSL', 'EU SSD', '1-klicks install', '15+ CMS', 'Flerspråkig panel', 'Setup finns'],
            'compare_title' => 'Det du får hos SolaSkinner',
            'compare_rows' => [
                ['CMS-licens', 'Ingår i planen', $name . ' 0 € + hosting'],
                ['Innehåll & SEO', 'AI + struktur ingår', 'Redo för organisk sök'],
                ['Datakontroll', 'Din hosting och din domän', 'Data i ditt konto'],
                ['Lansering', '1 klick + demo idag', 'Registrera → live på minuter'],
            ],
            'proof_title' => 'Säljfördelar',
            'proof_items' => [
                'Snabbare time-to-market: registrera → live på minuter',
                'Tydlig pris: betala hosting — CMS ingår',
                'Skala: en host för Shop, Booking, WordPress, AI…',
            ],
            'bottom_title' => 'Redo för kunder med ' . $name . '?',
            'bottom_lead' => 'Registrera hosting, installera CMS, koppla domän. Behöver hjälp? Vi sätter upp.',
            'demo_note' => 'Live produktdemo (bilohash.com) — testa UX innan du köper hosting',
            'kicker' => 'Gratis CMS · SEO · AI · utan kod',
            'h1' => $name . ' + hosting i Europa',
            'h1_sub' => 'Starta online utan utvecklare — CMS ingår redan i planen',
        ],
    ];
    $u = $ui[$L] ?? $ui['en'];
    // Ensure optional keys exist for partial language packs
    $u = array_merge($ui['en'], $u);

    // Related apps for internal SEO links
    $order = hs_seo_apps_order();
    $idx = array_search($slug, $order, true);
    $related = [];
    if ($idx !== false) {
        $candidates = array_values(array_filter(
            $order,
            static fn (string $s): bool => $s !== $slug
        ));
        // Prefer neighbors + high-priority apps
        $prefer = [];
        if ($idx > 0) {
            $prefer[] = $order[$idx - 1];
        }
        if ($idx < count($order) - 1) {
            $prefer[] = $order[$idx + 1];
        }
        foreach (['shop', 'booking', 'wordpress', 'today', 'ai', 'faktura'] as $p) {
            if ($p !== $slug && !in_array($p, $prefer, true)) {
                $prefer[] = $p;
            }
        }
        foreach ($prefer as $p) {
            if (count($related) >= 6) {
                break;
            }
            $ra = hs_seo_app($p);
            if ($ra !== null) {
                $related[] = $ra;
            }
        }
    }

    // JSON-LD FAQ + SoftwareApplication
    $faqEntities = [];
    foreach ($app['faq'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $faqEntities[] = [
            '@type' => 'Question',
            'name' => (string) ($row['q'] ?? ''),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => (string) ($row['a'] ?? ''),
            ],
        ];
    }
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'SoftwareApplication',
                'name' => $name,
                'applicationCategory' => 'WebApplication',
                'operatingSystem' => 'Web',
                'description' => $app['tagline'],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'EUR',
                    'description' => 'Free CMS install on SolaSkinner hosting plans',
                ],
                'url' => $base . '/seo/' . $app['file'],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => $faqEntities,
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $base . '/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'CMS hosting', 'item' => $base . '/seo/'],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => $base . '/seo/' . $app['file']],
                ],
            ],
        ],
    ];

    $iconClass = !empty($app['icon_brand']) ? 'fa-brands ' . $app['icon'] : 'fa-solid ' . $app['icon'];

    $regUrl = hs_url('register.php');
    $domainUrl = hs_url('domain');

    ob_start();
    ?>
<div class="hs-seo-app">
  <header class="hs-seo-app-hero">
    <p class="hs-seo-app-kicker"><i class="<?= hs_h($iconClass) ?>" aria-hidden="true"></i> SolaSkinner · <?= hs_h($u['kicker'] ?? 'free CMS') ?></p>
    <h1><?= hs_h((string) ($u['h1'] ?? (($L === 'uk' ? 'Хостинг для ' : 'Hosting for ') . $name))) ?></h1>
    <p class="hs-seo-app-h1sub"><?= hs_h((string) ($u['h1_sub'] ?? $app['tagline'])) ?></p>
    <p class="hs-seo-app-tagline"><?= hs_h($app['tagline']) ?></p>
    <p class="hs-seo-app-intro"><?= hs_h($app['intro']) ?></p>
    <div class="hs-seo-trust">
      <?php foreach ((array) ($u['trust'] ?? []) as $tb): ?>
        <span class="hs-seo-trust-item"><i class="fa-solid fa-check" aria-hidden="true"></i> <?= hs_h((string) $tb) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="hs-seo-app-ctas">
      <a class="hs-btn hs-btn-primary" href="<?= hs_h($regUrl) ?>"><i class="fa-solid fa-rocket"></i> <?= hs_h((string) ($u['cta_reg_strong'] ?? $u['cta_reg'])) ?></a>
      <a class="hs-btn hs-btn-ghost" href="<?= hs_h($demoUrl) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square"></i> <?= hs_h($u['cta_demo']) ?></a>
      <a class="hs-btn hs-btn-ghost" href="<?= hs_h($domainUrl) ?>"><i class="fa-solid fa-globe"></i> <?= hs_h($u['cta_domain']) ?></a>
      <?php if ($productUrl !== ''): ?>
      <a class="hs-btn hs-btn-ghost" href="<?= hs_h($productUrl) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-images"></i> <?= hs_h($u['cta_product'] ?? ($L === 'uk' ? 'Скріншоти' : 'Screenshots')) ?></a>
      <?php endif; ?>
      <a class="hs-btn hs-btn-ghost" href="<?= hs_h(hs_url('seo/')) ?>"><?= hs_h($u['cta_all']) ?></a>
    </div>
    <p class="hs-seo-app-demo-note hp-muted"><i class="fa-solid fa-link" aria-hidden="true"></i> <?= hs_h($u['demo_note']) ?>: <a href="<?= hs_h($demoUrl) ?>" target="_blank" rel="noopener noreferrer"><?= hs_h($demoUrl) ?></a>
      <?php if ($productUrl !== ''): ?> · <a href="<?= hs_h($productUrl) ?>" target="_blank" rel="noopener noreferrer"><?= hs_h($productUrl) ?></a><?php endif; ?>
    </p>
  </header>

  <section class="hs-seo-offer" aria-label="offer">
    <div class="hs-seo-offer-main">
      <h2><?= hs_h((string) ($u['offer_title'] ?? '')) ?></h2>
      <p><?= hs_h((string) ($u['offer_body'] ?? '')) ?></p>
      <ul class="hs-seo-offer-bullets">
        <?php foreach ((array) ($u['offer_bullets'] ?? []) as $ob): ?>
          <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?= hs_h((string) $ob) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="hs-seo-offer-cta">
      <a class="hs-btn hs-btn-primary" href="<?= hs_h($regUrl) ?>"><i class="fa-solid fa-credit-card"></i> <?= hs_h((string) ($u['cta_reg_strong'] ?? $u['cta_reg'])) ?></a>
      <a class="hs-btn hs-btn-ghost" href="<?= hs_h($demoUrl) ?>" target="_blank" rel="noopener noreferrer"><?= hs_h($u['cta_demo']) ?></a>
    </div>
  </section>

  <?php if (!empty($app['screens'])): ?>
  <section class="hs-seo-app-section hs-seo-app-screens" id="screens">
    <h2><?= hs_h($u['screens'] ?? ($L === 'uk' ? 'Скріншоти' : 'Screenshots')) ?></h2>
    <p class="hp-muted"><?= hs_h($u['screens_lead'] ?? ($L === 'uk' ? 'Реальний UI Today CMS (WebP).' : 'Real Today CMS UI (WebP).')) ?></p>
    <div class="hs-seo-screen-grid">
      <?php foreach ($app['screens'] as $sc):
          if (!is_array($sc) || ($sc['src'] ?? '') === '') {
              continue;
          }
          $src = (string) $sc['src'];
          $jpg = (string) ($sc['jpg'] ?? $src);
          ?>
      <figure class="hs-seo-screen-card">
        <a href="<?= hs_h($src) ?>" target="_blank" rel="noopener noreferrer">
          <picture>
            <source srcset="<?= hs_h($src) ?>" type="image/webp">
            <img src="<?= hs_h($jpg) ?>" alt="<?= hs_h((string) ($sc['title'] ?? $name)) ?>" width="640" height="400" loading="lazy" decoding="async">
          </picture>
        </a>
        <figcaption>
          <strong><?= hs_h((string) ($sc['title'] ?? '')) ?></strong>
          <?php if (($sc['desc'] ?? '') !== ''): ?><span><?= hs_h((string) $sc['desc']) ?></span><?php endif; ?>
        </figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="hs-seo-app-section">
    <h2><?= hs_h($u['features']) ?></h2>
    <ul class="hs-seo-app-list">
      <?php foreach ($app['features'] as $f): ?>
        <li><i class="fa-solid fa-check" aria-hidden="true"></i> <?= hs_h((string) $f) ?></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="hs-seo-app-section">
    <h2><?= hs_h($u['use']) ?></h2>
    <div class="hs-seo-app-chips">
      <?php foreach ($app['use_cases'] as $uc): ?>
        <span class="hs-seo-app-chip"><?= hs_h((string) $uc) ?></span>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="hs-seo-app-section">
    <h2><?= hs_h($u['how']) ?></h2>
    <ol class="hs-seo-app-steps">
      <?php foreach ($u['steps'] as $i => $step): ?>
        <li><span class="hs-seo-app-step-num"><?= (int) $i + 1 ?></span> <?= hs_h((string) $step) ?></li>
      <?php endforeach; ?>
    </ol>
  </section>

  <section class="hs-seo-app-section">
    <h2><?= hs_h($u['why']) ?></h2>
    <ul class="hs-seo-app-list">
      <?php foreach ($u['why_items'] as $w): ?>
        <li><i class="fa-solid fa-sun" aria-hidden="true"></i> <?= hs_h((string) $w) ?></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <?php if (!empty($u['compare_rows'])): ?>
  <section class="hs-seo-app-section hs-seo-compare">
    <h2><?= hs_h((string) ($u['compare_title'] ?? 'Compare')) ?></h2>
    <div class="hs-seo-compare-table" role="table">
      <?php foreach ((array) $u['compare_rows'] as $row):
          if (!is_array($row) || count($row) < 3) {
              continue;
          }
          ?>
        <div class="hs-seo-compare-row" role="row">
          <div role="cell"><strong><?= hs_h((string) $row[0]) ?></strong></div>
          <div role="cell" class="is-muted"><?= hs_h((string) $row[1]) ?></div>
          <div role="cell" class="is-win"><i class="fa-solid fa-check" aria-hidden="true"></i> <?= hs_h((string) $row[2]) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($u['proof_items'])): ?>
  <section class="hs-seo-app-section">
    <h2><?= hs_h((string) ($u['proof_title'] ?? '')) ?></h2>
    <ul class="hs-seo-app-list">
      <?php foreach ((array) $u['proof_items'] as $pi): ?>
        <li><i class="fa-solid fa-chart-line" aria-hidden="true"></i> <?= hs_h((string) $pi) ?></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <?php if ($app['faq'] !== []): ?>
  <section class="hs-seo-app-section">
    <h2><?= hs_h($u['faq']) ?></h2>
    <div class="hs-seo-app-faq">
      <?php foreach ($app['faq'] as $row): ?>
        <?php if (!is_array($row)) {
            continue;
        } ?>
        <details class="hs-seo-app-faq-item">
          <summary><?= hs_h((string) ($row['q'] ?? '')) ?></summary>
          <p><?= hs_h((string) ($row['a'] ?? '')) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($related !== []): ?>
  <section class="hs-seo-app-section">
    <h2><?= hs_h($u['related']) ?></h2>
    <div class="hs-seo-related">
      <?php foreach ($related as $ra): ?>
        <a href="<?= hs_h(hs_url('seo/' . (string) ($ra['file'] ?? ''))) ?>"><?= hs_h((string) ($ra['name'] ?? '')) ?></a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="hs-seo-app-bottom hs-seo-app-bottom-strong">
    <h2><?= hs_h((string) ($u['bottom_title'] ?? $u['cta_reg_strong'] ?? $u['cta_reg'])) ?></h2>
    <p><?= hs_h((string) ($u['bottom_lead'] ?? '')) ?></p>
    <div class="hs-seo-app-ctas">
      <a class="hs-btn hs-btn-primary" href="<?= hs_h($regUrl) ?>"><i class="fa-solid fa-rocket"></i> <?= hs_h((string) ($u['cta_reg_strong'] ?? $u['cta_reg'])) ?></a>
      <a class="hs-btn hs-btn-ghost" href="<?= hs_h($demoUrl) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square"></i> <?= hs_h($u['cta_demo']) ?></a>
      <a class="hs-btn hs-btn-ghost" href="<?= hs_h($domainUrl) ?>"><i class="fa-solid fa-globe"></i> <?= hs_h($u['cta_domain']) ?></a>
      <a class="hs-btn hs-btn-ghost" href="<?= hs_h(hs_url('seo/')) ?>"><?= hs_h($u['cta_all']) ?></a>
    </div>
  </section>
</div>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php
    $content = ob_get_clean();
    $page_title = $page_title;
    require dirname(__DIR__) . '/includes/layout-public.php';
}
