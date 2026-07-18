<?php
declare(strict_types=1);

/**
 * SEO product catalog for /seo/ hosting-for-* landings.
 * Positioning: SEO-ready scripts, AI-assisted content, no-code admin for any employee,
 * optional professional setup by SolaSkinner.
 *
 * @return array<string, array<string, mixed>>
 */
function hs_seo_apps_catalog(): array
{
    return [
        'shop' => [
            'slug' => 'shop',
            'file' => 'hosting-for-shop.php',
            'name' => 'Shop',
            'icon' => 'fa-bag-shopping',
            'installer' => 'Shop',
            'keywords' => [
                'en' => 'buy hosting free shop CMS Europe, cheap ecommerce hosting Norway Germany, SEO online store no-code, AI product content, SolaSkinner shop install',
                'no' => 'kjøp hosting gratis nettbutikk CMS, billig e-handel hosting Norge, SEO nettbutikk uten koding, AI produkttekst, SolaSkinner',
                'uk' => 'купити хостинг безкоштовний інтернет-магазин CMS, дешевий e-commerce хостинг Європа, SEO магазин без коду, AI тексти товарів, SolaSkinner',
            ],
            'tagline' => [
                'en' => 'Launch a selling online store free: catalog, cart, orders — SEO + AI product texts, no developer, pay hosting only',
                'no' => 'Start salgsnettbutikk gratis: katalog, handlekurv, ordrer — SEO + AI-tekster, uten utvikler, betal bare hosting',
                'uk' => 'Запустіть магазин, що продає: каталог, кошик, замовлення — SEO + AI-тексти, без програміста, платите лише хостинг',
            ],
            'intro' => [
                'en' => 'Shop is a free PHP e-commerce CMS on SolaSkinner built to convert visitors into orders: clean product URLs, meta fields and fast EU SSD for Google visibility. AI-assisted publishing fills titles and descriptions so your catalog goes live faster — without hiring a copywriter for every SKU. Any employee can change prices, photos and stock in a no-code admin. Install Shop free in one click, connect your domain and free SSL, start selling. Need a turnkey launch? We install and configure Shop for your brand.',
                'no' => 'Shop er et gratis PHP e-handels-CMS på SolaSkinner laget for salg: rene produkt-URL-er, meta-felt og rask EU-SSD for synlighet. AI fyller titler og beskrivelser raskere. Ansatte endrer priser, bilder og lager uten koding. Installer gratis med ett klikk, koble domene og gratis SSL. Vil du ha ferdig oppsett? Vi installerer og tilpasser Shop for deg.',
                'uk' => 'Shop — безкоштовна PHP e-commerce CMS на SolaSkinner для продажів: чисті URL товарів, meta, швидкий EU SSD для Google. AI прискорює назви й описи — каталог живе швидше без копірайтера на кожен SKU. Працівник змінює ціни, фото й залишки без коду. Установка без окремої ліцензії магазину: 1 клік, домен, SSL — і приймаєте замовлення. Потрібен запуск «під ключ»? Встановимо й налаштуємо Shop під бренд.',
            ],
            'features' => [
                'en' => [
                    'SEO-friendly product URLs, titles and meta',
                    'AI-assisted product & category content publishing',
                    'No-code admin: prices, stock, photos for any staff member',
                    'Catalog, cart, checkout and order management',
                    'Multilingual storefront for EU markets',
                    'One-click install + free SSL on SolaSkinner',
                    'Optional: we configure Shop for your brand',
                ],
                'no' => [
                    'SEO-vennlige produkt-URL-er, titler og meta',
                    'AI-hjelp til produkt- og kategoritekster',
                    'Admin uten koding — priser, lager, bilder for alle ansatte',
                    'Katalog, handlekurv, kasse og ordrer',
                    'Flerspråklig butikk for EU',
                    'Ett-klikks install + gratis SSL',
                    'Valgfritt: vi setter opp Shop for deg',
                ],
                'uk' => [
                    'SEO-URL товарів, заголовки та meta',
                    'AI-допомога з текстами товарів і категорій',
                    'Адмінка без коду — ціни, склад, фото для будь-якого працівника',
                    'Каталог, кошик, checkout і замовлення',
                    'Мультимова для ринків Європи',
                    'Установка в 1 клік + безкоштовний SSL',
                    'За потреби: ми налаштуємо Shop за вас',
                ],
            ],
            'use_cases' => [
                'en' => ['Local retail & brands', 'Staff-run online stores', 'Dropshipping pilots', 'Agency client shops'],
                'no' => ['Lokal retail og merkevarer', 'Butikk drevet av ansatte', 'Dropshipping-piloter', 'Byrå-butikker'],
                'uk' => ['Локальний ритейл і бренди', 'Магазин під керуванням персоналу', 'Пілоти dropshipping', 'Магазини клієнтів агенцій'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Do I need a programmer?', 'a' => 'No. Shop is built so any employee or beginner can manage products and orders in the admin. If you want, SolaSkinner can install and configure it for you.'],
                    ['q' => 'How does AI help?', 'a' => 'AI-assisted content tools help draft product titles and descriptions faster so your catalog is SEO-ready without writing everything from scratch.'],
                    ['q' => 'Is Shop free on SolaSkinner?', 'a' => 'Yes. Free one-click installer on hosting plans — you pay hosting; Shop is included.'],
                ],
                'no' => [
                    ['q' => 'Trenger jeg programmerer?', 'a' => 'Nei. Ansatte og nybegynnere kan styre produkter og ordrer. Vi kan også installere og sette opp for deg.'],
                    ['q' => 'Hvordan hjelper AI?', 'a' => 'AI hjelper med produkttekster og SEO-innhold raskere, uten å starte fra blank side.'],
                    ['q' => 'Er Shop gratis?', 'a' => 'Ja — gratis installatør. Du betaler hosting; Shop er inkludert.'],
                ],
                'uk' => [
                    ['q' => 'Чи потрібен програміст?', 'a' => 'Ні. Будь-який працівник або новачок керує товарами й замовленнями. За потреби ми встановимо й налаштуємо все за вас.'],
                    ['q' => 'Як допомагає AI?', 'a' => 'AI прискорює тексти товарів і SEO-описи — каталог наповнюється швидше без «з нуля».'],
                    ['q' => 'Shop безкоштовний?', 'a' => 'Так — безкоштовний інсталятор. Платите хостинг; Shop у комплекті.'],
                ],
            ],
        ],
        'booking' => [
            'slug' => 'booking',
            'file' => 'hosting-for-booking.php',
            'name' => 'Booking',
            'icon' => 'fa-calendar-check',
            'installer' => 'Booking',
            'keywords' => [
                'en' => 'SEO booking system, appointment CMS no-code, salon booking AI content, free booking hosting',
                'no' => 'SEO booking system, timebestilling uten koding, salong booking, gratis booking hosting',
                'uk' => 'SEO система бронювання, онлайн-запис без коду, CMS салону, хостинг booking',
            ],
            'tagline' => [
                'en' => 'Online appointments for salons and clinics — SEO pages, simple admin, optional AI service texts',
                'no' => 'Netbooking for salong og klinikk — SEO-sider, enkel admin, valgfri AI-tekst for tjenester',
                'uk' => 'Онлайн-запис для салонів і клінік — SEO-сторінки, проста адмінка, AI-тексти послуг',
            ],
            'intro' => [
                'en' => 'Booking is a free appointment CMS optimized for local SEO: service pages, clear structure and fast mobile booking. Staff can edit services, hours and slots without programming. AI can help draft service descriptions so new offers go live quickly. Need it ready for Monday? We set up Booking on your domain with SSL.',
                'no' => 'Booking er et gratis timebestillings-CMS for lokal SEO: tjenestesider, klar struktur og mobil booking. Ansatte endrer tjenester, åpningstider og tider uten koding. AI kan hjelpe med tjenestebeskrivelser. Trenger du det klart fort? Vi setter opp Booking med SSL på ditt domene.',
                'uk' => 'Booking — безкоштовна CMS онлайн-запису під локальне SEO: сторінки послуг, зрозуміла структура, мобільне бронювання. Персонал змінює послуги, графік і слоти без програмування. AI допомагає з описами послуг. Потрібно «під ключ»? Налаштуємо Booking на вашому домені з SSL.',
            ],
            'features' => [
                'en' => [
                    'SEO-ready service & booking pages',
                    'AI help for service descriptions and promos',
                    'Calendar booking any employee can manage',
                    'No-code admin for hours, prices and staff resources',
                    'Mobile-first public booking form',
                    'One-click install + free SSL',
                    'Optional professional setup by our team',
                ],
                'no' => [
                    'SEO-klare tjeneste- og bookingsider',
                    'AI-hjelp til tjenestetekster',
                    'Kalenderbooking ansatte kan styre',
                    'Admin uten koding for tider, priser og ressurser',
                    'Mobil booking for kunder',
                    'Ett-klikks install + gratis SSL',
                    'Valgfri profesjonell oppsett fra oss',
                ],
                'uk' => [
                    'SEO-сторінки послуг і запису',
                    'AI-допомога з описами послуг',
                    'Календар, яким керує будь-який працівник',
                    'Адмінка без коду: графік, ціни, ресурси',
                    'Мобільна форма бронювання',
                    'Установка в 1 клік + SSL',
                    'За потреби — налаштування нашою командою',
                ],
            ],
            'use_cases' => [
                'en' => ['Salons & barbers', 'Clinics & therapists', 'Consultants', 'Studios & rentals'],
                'no' => ['Salong og barber', 'Klinikk og terapeut', 'Konsulenter', 'Studio og utleie'],
                'uk' => ['Салони та барбершопи', 'Клініки та терапевти', 'Консультанти', 'Студії та оренда'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Can a receptionist manage Booking alone?', 'a' => 'Yes. The panel is for everyday staff — no developer needed. We can also configure schedules and services for you.'],
                    ['q' => 'Is it good for Google local search?', 'a' => 'Yes — clean structure, HTTPS, fast EU hosting and editable content for service SEO.'],
                    ['q' => 'Free?', 'a' => 'Yes on SolaSkinner free installer; hosting plan only.'],
                ],
                'no' => [
                    ['q' => 'Kan resepsjon styre Booking alene?', 'a' => 'Ja. Panelet er for daglig bruk uten utvikler. Vi kan også sette opp for deg.'],
                    ['q' => 'Bra for lokal SEO?', 'a' => 'Ja — ren struktur, HTTPS, rask EU-hosting og redigerbart innhold.'],
                    ['q' => 'Gratis?', 'a' => 'Ja via gratis installatør; bare hostingplan.'],
                ],
                'uk' => [
                    ['q' => 'Чи впорається адміністратор без IT?', 'a' => 'Так. Панель для щоденної роботи без розробника. Можемо налаштувати за вас.'],
                    ['q' => 'Чи підходить для локального SEO?', 'a' => 'Так — структура, HTTPS, швидкий EU-хостинг і редагований контент послуг.'],
                    ['q' => 'Безкоштовно?', 'a' => 'Так, безкоштовний інсталятор; платите лише хостинг.'],
                ],
            ],
        ],
        'auction' => [
            'slug' => 'auction',
            'file' => 'hosting-for-auction.php',
            'name' => 'Auction',
            'icon' => 'fa-gavel',
            'installer' => 'Auction',
            'keywords' => [
                'en' => 'SEO auction website CMS, online bidding platform no-code, free auction script hosting',
                'no' => 'SEO auksjon CMS, budplattform uten koding, gratis auksjon script hosting',
                'uk' => 'SEO аукціон CMS, платформа ставок без коду, безкоштовний auction хостинг',
            ],
            'tagline' => [
                'en' => 'Timed auctions and bids — SEO lot pages, AI listing help, admin for non-developers',
                'no' => 'Tidsbestemte auksjoner og bud — SEO-lottsider, AI-hjelp til annonser, admin uten utvikler',
                'uk' => 'Аукціони зі ставками — SEO-сторінки лотів, AI для оголошень, адмінка без розробника',
            ],
            'intro' => [
                'en' => 'Auction is a free CMS for bidding markets with SEO-oriented lot pages and category structure. Publish lots faster with AI-assisted titles and descriptions. Managers run cycles and listings without code. We can deploy Auction on your domain if your team prefers a ready marketplace.',
                'no' => 'Auction er et gratis CMS for budmarkeder med SEO-lotter og kategorier. AI hjelper med titler og beskrivelser. Ledere styrer sykluser og annonser uten koding. Vi kan rulle ut Auction på ditt domene om dere vil ha ferdig markedsplass.',
                'uk' => 'Auction — безкоштовна CMS торгів із SEO-сторінками лотів і категоріями. AI прискорює заголовки й описи. Менеджери ведуть цикли й оголошення без коду. За потреби розгорнемо Auction на вашому домені «під ключ».',
            ],
            'features' => [
                'en' => [
                    'SEO lot URLs and category structure',
                    'AI-assisted lot titles and descriptions',
                    'Bidding workflow staff can operate without code',
                    'Timers and auction cycle management',
                    'Panel install in minutes + free SSL',
                    'Optional setup by SolaSkinner',
                ],
                'no' => [
                    'SEO-URL for lotter og kategorier',
                    'AI-hjelp til lottekster',
                    'Budflyt uten koding for ansatte',
                    'Tidsstyring av auksjonssykluser',
                    'Panelinstall + gratis SSL',
                    'Valgfritt oppsett fra SolaSkinner',
                ],
                'uk' => [
                    'SEO-URL лотів і категорії',
                    'AI-тексти лотів',
                    'Ставки під керуванням персоналу без коду',
                    'Таймери та цикли торгів',
                    'Установка з панелі + SSL',
                    'Опційне налаштування SolaSkinner',
                ],
            ],
            'use_cases' => [
                'en' => ['Collector items', 'Charity auctions', 'Niche B2C markets', 'Agency demos'],
                'no' => ['Samleobjekter', 'Veldedighetsauksjoner', 'Nisjemarkeder', 'Byrå-demoer'],
                'uk' => ['Колекційні товари', 'Благодійні аукціони', 'Нішеві ринки', 'Демо для агенцій'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Need developers to run auctions?', 'a' => 'No daily coding. Staff manage lots and bids in the admin; we can configure the first launch.'],
                    ['q' => 'SEO for lots?', 'a' => 'Yes — structured pages, meta-ready content and fast HTTPS hosting.'],
                    ['q' => 'Extra license?', 'a' => 'No separate Auction license — hosting plan only.'],
                ],
                'no' => [
                    ['q' => 'Trenger utviklere i daglig drift?', 'a' => 'Nei. Ansatte styrer lotter og bud; vi kan sette opp første lansering.'],
                    ['q' => 'SEO for lotter?', 'a' => 'Ja — strukturerte sider, meta og rask HTTPS.'],
                    ['q' => 'Egen lisens?', 'a' => 'Nei — bare hostingplan.'],
                ],
                'uk' => [
                    ['q' => 'Чи потрібні розробники щодня?', 'a' => 'Ні. Персонал веде лоти й ставки; перший запуск можемо налаштувати ми.'],
                    ['q' => 'SEO для лотів?', 'a' => 'Так — структуровані сторінки, meta і швидкий HTTPS.'],
                    ['q' => 'Окрема ліцензія?', 'a' => 'Ні — лише тариф хостингу.'],
                ],
            ],
        ],
        'freelance' => [
            'slug' => 'freelance',
            'file' => 'hosting-for-freelance.php',
            'name' => 'Freelance',
            'icon' => 'fa-briefcase',
            'installer' => 'Freelance',
            'keywords' => [
                'en' => 'SEO freelance marketplace CMS, job board no-code, AI job posts, free freelance platform hosting',
                'no' => 'SEO freelance markedsplass, jobbportal uten koding, AI jobbannonser, gratis frilansplattform',
                'uk' => 'SEO біржа фрілансу CMS, дошка вакансій без коду, AI оголошення, хостинг freelance',
            ],
            'tagline' => [
                'en' => 'Talent and project marketplace — SEO listings, AI job copy, simple for moderators',
                'no' => 'Talent- og prosjektmarkedsplass — SEO-annonser, AI-tekst, enkel for moderatorer',
                'uk' => 'Біржа талантів і проєктів — SEO-оголошення, AI-тексти, проста для модераторів',
            ],
            'intro' => [
                'en' => 'Freelance launches a services or talent board with SEO-friendly listing structure. AI helps draft job and profile texts so moderators publish faster. No programming for daily operations — categories, posts and approvals stay in a clear admin. We can brand and configure the marketplace for your niche.',
                'no' => 'Freelance starter tjeneste- eller talenttavle med SEO-struktur. AI hjelper med jobb- og profiltekster. Daglig drift uten koding — kategorier, poster og godkjenning i klar admin. Vi kan brande og sette opp markedsplassen for din nisje.',
                'uk' => 'Freelance — біржа послуг або талантів із SEO-структурою оголошень. AI допомагає з текстами вакансій і профілів. Щоденна робота без коду — категорії, пости, модерація в зрозумілій адмінці. Можемо забрендувати й налаштувати біржу під вашу нішу.',
            ],
            'features' => [
                'en' => [
                    'SEO structure for jobs, profiles and categories',
                    'AI-assisted job and listing copy',
                    'Moderator-friendly admin without code',
                    'Multilingual EU markets ready',
                    'One-click install + free SSL',
                    'Optional niche setup by our team',
                ],
                'no' => [
                    'SEO-struktur for jobber, profiler og kategorier',
                    'AI-hjelp til jobbtekster',
                    'Admin for moderatorer uten koding',
                    'Klart for flerspråklige EU-markeder',
                    'Ett-klikks install + gratis SSL',
                    'Valgfritt nisjeoppsett fra oss',
                ],
                'uk' => [
                    'SEO-структура вакансій, профілів, категорій',
                    'AI-тексти оголошень',
                    'Адмінка для модераторів без коду',
                    'Готовність до мультимовних ринків EU',
                    'Установка в 1 клік + SSL',
                    'Опційне налаштування під нішу',
                ],
            ],
            'use_cases' => [
                'en' => ['Local service boards', 'Agency talent pools', 'Niche freelance verticals', 'MVP marketplaces'],
                'no' => ['Lokale tjenestetavler', 'Byrå talentpool', 'Nisje-frilans', 'MVP-markedsplasser'],
                'uk' => ['Локальні дошки послуг', 'Пули талантів агенцій', 'Нішевий фріланс', 'MVP-майданчики'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Can non-tech staff moderate?', 'a' => 'Yes — built for moderators and operators, not developers. Setup help available.'],
                    ['q' => 'SEO for job posts?', 'a' => 'Listing structure and meta-ready pages support organic discovery.'],
                    ['q' => 'Free install?', 'a' => 'Yes on SolaSkinner free CMS installer.'],
                ],
                'no' => [
                    ['q' => 'Kan ikke-tekniske moderere?', 'a' => 'Ja — for moderatorer, ikke utviklere. Oppsettshjelp finnes.'],
                    ['q' => 'SEO for jobbposter?', 'a' => 'Struktur og meta-klare sider støtter organisk synlighet.'],
                    ['q' => 'Gratis install?', 'a' => 'Ja på SolaSkinner gratis CMS-installatør.'],
                ],
                'uk' => [
                    ['q' => 'Чи зможе модерувати не-технічний персонал?', 'a' => 'Так — для модераторів, не розробників. Допомога з налаштуванням є.'],
                    ['q' => 'SEO оголошень?', 'a' => 'Структура й meta-готові сторінки підтримують органічний пошук.'],
                    ['q' => 'Безкоштовна установка?', 'a' => 'Так, безкоштовний інсталятор SolaSkinner.'],
                ],
            ],
        ],
        'pizza' => [
            'slug' => 'pizza',
            'file' => 'hosting-for-pizza.php',
            'name' => 'Pizza',
            'icon' => 'fa-pizza-slice',
            'installer' => 'Pizza',
            'keywords' => [
                'en' => 'SEO restaurant website CMS, pizza menu ordering no-code, AI menu texts, food delivery site hosting',
                'no' => 'SEO restaurant CMS, pizza meny bestilling uten koding, AI menytekst, matlevering hosting',
                'uk' => 'SEO сайт ресторану CMS, меню піци без коду, AI тексти меню, хостинг доставки їжі',
            ],
            'tagline' => [
                'en' => 'Restaurant menu and online orders — SEO for local food search, AI dish copy, kitchen staff can update',
                'no' => 'Restaurantmeny og netbestilling — SEO for lokal mat, AI-rettetekst, kjøkken/ansatte kan oppdatere',
                'uk' => 'Меню й онлайн-замовлення — SEO для локального пошуку їжі, AI-описи страв, оновлення персоналом',
            ],
            'intro' => [
                'en' => 'Pizza is a free restaurant CMS for menus and orders, tuned for local SEO (city + cuisine queries). AI helps write dish and combo descriptions. Waiters or managers change the menu without developers. We can launch your pizzeria site with domain, SSL and starter menu.',
                'no' => 'Pizza er et gratis restaurant-CMS for meny og ordrer, tilpasset lokal SEO. AI hjelper med rettetekster. Ansatte endrer meny uten utviklere. Vi kan lansere pizzeria-siden med domene, SSL og startmeny.',
                'uk' => 'Pizza — безкоштовна CMS меню й замовлень під локальне SEO. AI допомагає з описами страв. Офіціанти чи менеджери змінюють меню без розробників. Можемо запустити сайт піцерії з доменом, SSL і стартовим меню.',
            ],
            'features' => [
                'en' => [
                    'Local SEO structure for menu and location pages',
                    'AI-assisted dish and promo texts',
                    'No-code menu updates for kitchen/staff',
                    'Mobile order UX',
                    'One-click install + free SSL',
                    'Optional full setup for your restaurant',
                ],
                'no' => [
                    'Lokal SEO for meny og stedsider',
                    'AI-hjelp til retter og kampanjer',
                    'Menyoppdatering uten koding',
                    'Mobil bestillings-UX',
                    'Ett-klikks install + gratis SSL',
                    'Valgfritt fullt oppsett for restauranten',
                ],
                'uk' => [
                    'Локальне SEO меню й сторінок локації',
                    'AI-тексти страв і акцій',
                    'Оновлення меню без коду',
                    'Мобільний UX замовлення',
                    'Установка в 1 клік + SSL',
                    'Опційний повний запуск для закладу',
                ],
            ],
            'use_cases' => [
                'en' => ['Pizzerias', 'Cafés', 'Dark kitchens', 'Food trucks online'],
                'no' => ['Pizzeria', 'Kafe', 'Dark kitchen', 'Food truck online'],
                'uk' => ['Піцерії', 'Кафе', 'Dark kitchen', 'Фудтраки онлайн'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Can kitchen staff update the menu?', 'a' => 'Yes — no coding. Change items, prices and photos in the admin.'],
                    ['q' => 'Help with first launch?', 'a' => 'Yes — we can install, connect domain/SSL and prepare starter content.'],
                    ['q' => 'Free?', 'a' => 'Yes with SolaSkinner free installer.'],
                ],
                'no' => [
                    ['q' => 'Kan ansatte oppdatere menyen?', 'a' => 'Ja — uten koding. Endre retter, priser og bilder i admin.'],
                    ['q' => 'Hjelp til første lansering?', 'a' => 'Ja — install, domene/SSL og startinnhold.'],
                    ['q' => 'Gratis?', 'a' => 'Ja med SolaSkinner gratis installatør.'],
                ],
                'uk' => [
                    ['q' => 'Чи оновить меню персонал?', 'a' => 'Так — без коду. Страви, ціни, фото в адмінці.'],
                    ['q' => 'Допомога з першим запуском?', 'a' => 'Так — установка, домен/SSL і стартовий контент.'],
                    ['q' => 'Безкоштовно?', 'a' => 'Так, безкоштовний інсталятор SolaSkinner.'],
                ],
            ],
        ],
        'today' => [
            'slug' => 'today',
            'file' => 'hosting-for-today.php',
            'name' => 'Today',
            'icon' => 'fa-newspaper',
            'installer' => 'Today',
            'demo_url' => 'https://bilohash.com/today/',
            'product_url' => 'https://bilohash.com/today/site/',
            'screens' => [
                ['src' => 'https://bilohash.com/today/assets/images/screens/main.webp', 'jpg' => 'https://bilohash.com/today/assets/images/screens/main.jpg', 'title' => ['en' => 'Homepage & newsroom', 'uk' => 'Головна та редакція', 'no' => 'Forside og redaksjon'], 'desc' => ['en' => 'Breaking ticker, featured stories, SEO poster.', 'uk' => 'Breaking-стрічка, featured, SEO-постер.', 'no' => 'Breaking-ticker, utvalgte saker, SEO-poster.']],
                ['src' => 'https://bilohash.com/today/assets/images/screens/articles.webp', 'jpg' => 'https://bilohash.com/today/assets/images/screens/articles.jpg', 'title' => ['en' => 'Articles list', 'uk' => 'Список статей', 'no' => 'Artikkelliste'], 'desc' => ['en' => 'SEO cards with covers and categories.', 'uk' => 'SEO-картки з обкладинками.', 'no' => 'SEO-kort med cover og kategorier.']],
                ['src' => 'https://bilohash.com/today/assets/images/screens/category.webp', 'jpg' => 'https://bilohash.com/today/assets/images/screens/category.jpg', 'title' => ['en' => 'Categories', 'uk' => 'Категорії', 'no' => 'Kategorier'], 'desc' => ['en' => 'Topic hubs with clean URLs.', 'uk' => 'Тематичні хаби з чистими URL.', 'no' => 'Temahuber med rene URL-er.']],
                ['src' => 'https://bilohash.com/today/assets/images/screens/seo-agent.webp', 'jpg' => 'https://bilohash.com/today/assets/images/screens/seo-agent.jpg', 'title' => ['en' => 'SEO agents', 'uk' => 'SEO-агенти', 'no' => 'SEO-agenter'], 'desc' => ['en' => 'Six agents audit meta, schema and hosting.', 'uk' => 'Шість агентів: meta, schema, хостинг.', 'no' => 'Seks agenter: meta, schema, hosting.']],
                ['src' => 'https://bilohash.com/today/assets/images/screens/seo-admin-tools.webp', 'jpg' => 'https://bilohash.com/today/assets/images/screens/seo-admin-tools.jpg', 'title' => ['en' => 'Admin SEO tools', 'uk' => 'Адмін SEO', 'no' => 'Admin SEO-verktøy'], 'desc' => ['en' => 'Fix titles and excerpts without code.', 'uk' => 'Title і excerpt без коду.', 'no' => 'Fiks titler og utdrag uten koding.']],
                ['src' => 'https://bilohash.com/today/assets/images/screens/journalist.webp', 'jpg' => 'https://bilohash.com/today/assets/images/screens/journalist.jpg', 'title' => ['en' => 'Journalist profiles', 'uk' => 'Журналісти', 'no' => 'Journalistprofiler'], 'desc' => ['en' => 'E-E-A-T author pages.', 'uk' => 'Авторські сторінки E-E-A-T.', 'no' => 'Forfattersider for E-E-A-T.']],
                ['src' => 'https://bilohash.com/today/assets/images/screens/footer.webp', 'jpg' => 'https://bilohash.com/today/assets/images/screens/footer.jpg', 'title' => ['en' => 'Footer & languages', 'uk' => 'Футер і мови', 'no' => 'Footer og språk'], 'desc' => ['en' => 'Nav, languages, hosting CTA.', 'uk' => 'Навігація, мови, CTA хостингу.', 'no' => 'Navigasjon, språk, hosting-CTA.']],
            ],
            'keywords' => [
                'en' => 'Today CMS news portal, SEO agents Schema.org NewsArticle, free news script hosting Europe, bilohash today demo',
                'no' => 'Today CMS nyhetsportal, SEO-agenter Schema.org, gratis nyhetsskript hosting Europa',
                'uk' => 'Today CMS новинний портал, SEO-агенти Schema.org NewsArticle, безкоштовний news скрипт хостинг',
            ],
            'tagline' => [
                'en' => 'News portal CMS with SEO agents — screenshots, Schema.org, no-code publishing',
                'no' => 'Nyhetsportal-CMS med SEO-agenter — skjermbilder, Schema.org, publisering uten koding',
                'uk' => 'Новинна CMS з SEO-агентами — скріншоти, Schema.org, публікація без коду',
            ],
            'intro' => [
                'en' => 'Today is a free multilingual PHP news CMS for organic traffic and real newsrooms. Live product at bilohash.com/today with screenshots of homepage, articles, categories, journalist profiles, SEO agents and admin tools (WebP-optimized). Clean article URLs, meta fields, Schema.org NewsArticle and six SEO agents help small teams publish without developers. Install on SolaSkinner EU SSD with free SSL — or we configure the newsroom for you.',
                'no' => 'Today er et gratis flerspråklig PHP nyhets-CMS for organisk trafikk. Live på bilohash.com/today med skjermbilder av forside, artikler, kategorier, journalister, SEO-agenter og admin (WebP). Rene URL-er, meta, Schema.org NewsArticle og seks SEO-agenter. Installer på SolaSkinner EU-SSD med gratis SSL — eller vi setter opp redaksjonen for deg.',
                'uk' => 'Today — безкоштовна мультимовна PHP CMS новин для органіки. Live bilohash.com/today зі скріншотами головної, статей, категорій, журналістів, SEO-агентів і адмінки (WebP). Чисті URL, meta, Schema.org NewsArticle і шість SEO-агентів. Установка на SolaSkinner EU SSD з SSL — або налаштуємо редакцію за вас.',
            ],
            'features' => [
                'en' => [
                    'Live screenshots: homepage, articles, categories, SEO agents, admin',
                    'SEO titles, URLs, categories and meta for every story',
                    'Six SEO agents (meta, schema, technical, content, social, hosting)',
                    'Schema.org NewsArticle + Open Graph out of the box',
                    'Journalist profiles for E-E-A-T',
                    'No-code admin — beginners publish daily',
                    'One-click install + free SSL on SolaSkinner',
                    'Optional full newsroom setup by our team',
                ],
                'no' => [
                    'Live-skjermbilder: forside, artikler, kategorier, SEO-agenter, admin',
                    'SEO-titler, URL-er, kategorier og meta',
                    'Seks SEO-agenter (meta, schema, teknisk, innhold, sosialt, hosting)',
                    'Schema.org NewsArticle + Open Graph',
                    'Journalistprofiler for E-E-A-T',
                    'Admin uten koding — nybegynnere publiserer daglig',
                    'Ett-klikks install + gratis SSL',
                    'Valgfritt redaksjonsoppsett fra oss',
                ],
                'uk' => [
                    'Live-скріншоти: головна, статті, категорії, SEO-агенти, адмінка',
                    'SEO-заголовки, URL, категорії та meta',
                    'Шість SEO-агентів (meta, schema, технічне, контент, соцмережі, хостинг)',
                    'Schema.org NewsArticle + Open Graph',
                    'Профілі журналістів для E-E-A-T',
                    'Адмінка без коду — новачки публікують щодня',
                    'Установка в 1 клік + безкоштовний SSL',
                    'Опційне налаштування редакції нами',
                ],
            ],
            'use_cases' => [
                'en' => ['Local news', 'Niche magazines', 'Company newsrooms', 'Blogs at scale', 'BILOHASH product changelogs'],
                'no' => ['Lokalnyheter', 'Nisjemagasin', 'Bedriftsnyheter', 'Blogg i skala', 'BILOHASH produktnyheter'],
                'uk' => ['Локальні новини', 'Нішеві журнали', 'Корпоративні новини', 'Блоги', 'Чейнджлоги BILOHASH'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Where can I see screenshots?', 'a' => 'Product gallery: bilohash.com/today/site/ — WebP screens of homepage, articles, SEO agents and admin.'],
                    ['q' => 'Can interns publish articles?', 'a' => 'Yes. The editor UI is for non-developers; SEO agents guide titles and meta.'],
                    ['q' => 'Good for rankings?', 'a' => 'Yes — structure, HTTPS, Schema.org, speed on EU SSD and editable meta support SEO strategy.'],
                    ['q' => 'Free on SolaSkinner?', 'a' => 'Yes — free install; pay only hosting. Live demo on bilohash.com/today/.'],
                ],
                'no' => [
                    ['q' => 'Hvor ser jeg skjermbilder?', 'a' => 'Produktgalleri: bilohash.com/today/site/ — WebP av forside, artikler, SEO-agenter og admin.'],
                    ['q' => 'Kan nybegynnere publisere?', 'a' => 'Ja. Admin uten utvikler; SEO-agenter hjelper med titler og meta.'],
                    ['q' => 'Bra for rangering?', 'a' => 'Ja — struktur, HTTPS, Schema.org, rask EU-SSD.'],
                    ['q' => 'Gratis?', 'a' => 'Ja — gratis install; bare hosting. Live: bilohash.com/today/.'],
                ],
                'uk' => [
                    ['q' => 'Де скріншоти?', 'a' => 'Галерея продукту: bilohash.com/today/site/ — WebP головної, статей, SEO-агентів і адмінки.'],
                    ['q' => 'Чи публікуватимуть новачки?', 'a' => 'Так. UI без розробника; SEO-агенти допомагають з title і meta.'],
                    ['q' => 'Чи добре для ранжування?', 'a' => 'Так — структура, HTTPS, Schema.org, швидкий EU SSD.'],
                    ['q' => 'Безкоштовно?', 'a' => 'Так — безкоштовна установка; платите хостинг. Демо: bilohash.com/today/.'],
                ],
            ],
        ],
        'gamehub' => [
            'slug' => 'gamehub',
            'file' => 'hosting-for-gamehub.php',
            'name' => 'GameHub',
            'icon' => 'fa-gamepad',
            'installer' => 'GameHub',
            'keywords' => [
                'en' => 'SEO game portal CMS, game catalog AI content, gaming website no-code hosting',
                'no' => 'SEO spillportal CMS, spillkatalog AI-innhold, gaming side uten koding',
                'uk' => 'SEO ігровий портал CMS, каталог ігор AI контент, gaming сайт без коду',
            ],
            'tagline' => [
                'en' => 'Game catalog portal — SEO listings, AI game blurbs, community managers can update',
                'no' => 'Spillkatalog-portal — SEO-lister, AI-spilltekster, community-ansvarlige kan oppdatere',
                'uk' => 'Каталог ігор — SEO-картки, AI-описи, оновлення community-менеджерами',
            ],
            'intro' => [
                'en' => 'GameHub is a free CMS for game directories with SEO-friendly listing pages. AI speeds up short descriptions and category intros. Community managers publish without code. We can install GameHub and prepare the first catalog structure for your niche.',
                'no' => 'GameHub er et gratis CMS for spillkataloger med SEO-lister. AI fremskynder korte beskrivelser. Community-ansvarlige publiserer uten koding. Vi kan installere GameHub og klargjøre første katalogstruktur.',
                'uk' => 'GameHub — безкоштовна CMS каталогів ігор із SEO-сторінками. AI прискорює короткі описи. Community-менеджери публікують без коду. Можемо встановити GameHub і підготувати структуру каталогу під нішу.',
            ],
            'features' => [
                'en' => [
                    'SEO game listing structure',
                    'AI-assisted blurbs and category text',
                    'No-code updates for managers',
                    'Engaging public UI on fast SSD',
                    'Free SSL + one-click install',
                    'Optional catalog setup help',
                ],
                'no' => [
                    'SEO-struktur for spilloppføringer',
                    'AI-hjelp til tekster og kategorier',
                    'Oppdateringer uten koding',
                    'Engasjerende UI på rask SSD',
                    'Gratis SSL + ett-klikks install',
                    'Valgfri hjelp til katalogoppsett',
                ],
                'uk' => [
                    'SEO-структура карток ігор',
                    'AI-тексти й категорії',
                    'Оновлення без коду',
                    'Яскравий UI на швидкому SSD',
                    'SSL + установка в 1 клік',
                    'Опційна допомога з каталогом',
                ],
            ],
            'use_cases' => [
                'en' => ['Indie catalogs', 'Clan / community sites', 'Review hubs', 'Promo portals'],
                'no' => ['Indie-kataloger', 'Clan/community', 'Anmeldelsessider', 'Kampanjeportaler'],
                'uk' => ['Indie-каталоги', 'Клани / community', 'Огляди', 'Промо-портали'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Need a developer for content?', 'a' => 'No — managers update listings; AI helps with short SEO copy.'],
                    ['q' => 'Free?', 'a' => 'Yes — free installer on SolaSkinner.'],
                    ['q' => 'Own domain?', 'a' => 'Yes — connect domain and free SSL in the panel.'],
                ],
                'no' => [
                    ['q' => 'Trenger utvikler for innhold?', 'a' => 'Nei — ansvarlige oppdaterer lister; AI hjelper med SEO-tekst.'],
                    ['q' => 'Gratis?', 'a' => 'Ja — gratis installatør.'],
                    ['q' => 'Eget domene?', 'a' => 'Ja — domene og gratis SSL i panelet.'],
                ],
                'uk' => [
                    ['q' => 'Потрібен розробник для контенту?', 'a' => 'Ні — менеджери оновлюють списки; AI допомагає з SEO-текстами.'],
                    ['q' => 'Безкоштовно?', 'a' => 'Так — безкоштовний інсталятор.'],
                    ['q' => 'Свій домен?', 'a' => 'Так — домен і SSL у панелі.'],
                ],
            ],
        ],
        'tavle' => [
            'slug' => 'tavle',
            'file' => 'hosting-for-tavle.php',
            'name' => 'Bilen CMS',
            'icon' => 'fa-car',
            'installer' => 'Bilen CMS',
            'keywords' => [
                'en' => 'SEO car classifieds CMS, auto marketplace no-code, AI vehicle ads, Bilen CMS hosting',
                'no' => 'SEO bilannonser CMS, bilmarkedsplass uten koding, AI bilannonse, Bilen CMS hosting',
                'uk' => 'SEO автооголошення CMS, автобазар без коду, AI оголошення авто, Bilen CMS',
            ],
            'tagline' => [
                'en' => 'Vehicle classifieds — SEO listings, AI ad texts, dealers and staff publish without code',
                'no' => 'Bilannonser — SEO-lister, AI-annonsetekst, forhandlere og ansatte publiserer uten koding',
                'uk' => 'Автооголошення — SEO-картки, AI-тексти, дилери й персонал публікують без коду',
            ],
            'intro' => [
                'en' => 'Bilen CMS (Tavle) powers car and vehicle boards with SEO-ready listing pages. AI helps write ad titles and descriptions so inventory goes online faster. Sales staff manage ads without developers. We can configure Bilen CMS for your region and brand.',
                'no' => 'Bilen CMS (Tavle) driver bil- og kjøretøyannonser med SEO-lister. AI hjelper med annonsetitler og beskrivelser. Selgere styrer annonser uten utviklere. Vi kan sette opp Bilen CMS for din region og merkevare.',
                'uk' => 'Bilen CMS (Tavle) — автооголошення з SEO-сторінками. AI допомагає з заголовками й описами, щоб лоти швидше виходили онлайн. Менеджери ведуть оголошення без розробників. Можемо налаштувати Bilen CMS під регіон і бренд.',
            ],
            'features' => [
                'en' => [
                    'SEO vehicle listing structure',
                    'AI-assisted ad titles and descriptions',
                    'No-code admin for sales teams',
                    'Mobile listing UX',
                    'Panel install + free SSL',
                    'Optional regional setup by us',
                ],
                'no' => [
                    'SEO-struktur for bilannonser',
                    'AI-hjelp til annonsetekster',
                    'Admin uten koding for salgsteam',
                    'Mobil annonse-UX',
                    'Panelinstall + gratis SSL',
                    'Valgfritt regionalt oppsett',
                ],
                'uk' => [
                    'SEO-структура авто-оголошень',
                    'AI-тексти оголошень',
                    'Адмінка без коду для відділу продажів',
                    'Мобільний UX',
                    'Установка з панелі + SSL',
                    'Опційне регіональне налаштування',
                ],
            ],
            'use_cases' => [
                'en' => ['Regional auto boards', 'Dealer showcases', 'Motorcycle / boat niches', 'Agency verticals'],
                'no' => ['Regionale biltavler', 'Forhandler-utstillinger', 'MC / båt-nisjer', 'Byrå-vertikaler'],
                'uk' => ['Регіональні автодошки', 'Сайти дилерів', 'Мото / човни', 'Вертикалі агенцій'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Can sales people add cars alone?', 'a' => 'Yes — forms and admin are for non-developers. We can train or pre-configure.'],
                    ['q' => 'SEO for vehicle ads?', 'a' => 'Yes — structured listings, meta-ready content, fast HTTPS.'],
                    ['q' => 'Free?', 'a' => 'Yes on SolaSkinner free CMS installer.'],
                ],
                'no' => [
                    ['q' => 'Kan selgere legge inn biler alene?', 'a' => 'Ja — admin for ikke-utviklere. Vi kan opplære eller forhåndskonfigurere.'],
                    ['q' => 'SEO for bilannonser?', 'a' => 'Ja — struktur, meta og rask HTTPS.'],
                    ['q' => 'Gratis?', 'a' => 'Ja på SolaSkinner gratis CMS-installatør.'],
                ],
                'uk' => [
                    ['q' => 'Чи додасть авто менеджер сам?', 'a' => 'Так — адмінка для не-розробників. Можемо навчити або пре-налаштувати.'],
                    ['q' => 'SEO авто-оголошень?', 'a' => 'Так — структура, meta, швидкий HTTPS.'],
                    ['q' => 'Безкоштовно?', 'a' => 'Так, безкоштовний інсталятор SolaSkinner.'],
                ],
            ],
        ],
        'faktura' => [
            'slug' => 'faktura',
            'file' => 'hosting-for-faktura.php',
            'name' => 'Faktura',
            'icon' => 'fa-file-invoice-dollar',
            'installer' => 'Faktura',
            'keywords' => [
                'en' => 'self-hosted invoicing CMS, faktura system no-code, invoice software Europe hosting',
                'no' => 'self-hostet faktura CMS, fakturasystem uten koding, fakturaprogram Norge hosting',
                'uk' => 'self-hosted CMS рахунків, faktura без коду, інвойси хостинг Європа',
            ],
            'tagline' => [
                'en' => 'Invoicing for freelancers and SMEs — clear admin, self-hosted data, we can set it up',
                'no' => 'Fakturering for frilans og SMB — klar admin, egne data, vi kan sette opp',
                'uk' => 'Рахунки для ФОП і МСБ — зрозуміла адмінка, свої дані, можемо налаштувати',
            ],
            'intro' => [
                'en' => 'Faktura is a free self-hosted invoicing CMS so you keep client and invoice data on your own European hosting. Everyday use needs no programming: create invoices and clients in a simple admin. Templates stay business-ready; we can install Faktura, SSL and branding for you.',
                'no' => 'Faktura er et gratis self-hostet faktura-CMS — kundedata og fakturaer på din europeiske hosting. Daglig bruk uten koding: fakturaer og kunder i enkel admin. Vi kan installere Faktura, SSL og branding for deg.',
                'uk' => 'Faktura — безкоштовна self-hosted CMS рахунків: дані клієнтів на вашому європейському хостингу. Щодня без коду: рахунки й клієнти в простій адмінці. Можемо встановити Faktura, SSL і брендинг за вас.',
            ],
            'features' => [
                'en' => [
                    'Invoice documents and client records',
                    'No-code daily invoicing for office staff',
                    'Self-hosted control of business data',
                    'Business branding on your domain',
                    'One-click install + free SSL',
                    'Optional setup and onboarding by us',
                ],
                'no' => [
                    'Fakturadokumenter og kunderegister',
                    'Daglig fakturering uten koding',
                    'Egenkontroll av bedriftsdata',
                    'Branding på eget domene',
                    'Ett-klikks install + gratis SSL',
                    'Valgfritt oppsett og onboarding',
                ],
                'uk' => [
                    'Документи рахунків і база клієнтів',
                    'Щоденна робота без коду',
                    'Дані бізнесу на вашому хостингу',
                    'Брендинг на своєму домені',
                    'Установка в 1 клік + SSL',
                    'Опційне налаштування й онбординг',
                ],
            ],
            'use_cases' => [
                'en' => ['Freelancers', 'Consultants', 'Small LLCs / ENK', 'Agencies billing clients'],
                'no' => ['Frilansere', 'Konsulenter', 'ENK / AS små', 'Byråfaktura til kunder'],
                'uk' => ['Фрілансери', 'Консультанти', 'ФОП / малий бізнес', 'Агенції'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Do I need IT staff?', 'a' => 'No for daily invoices. We can install and brand Faktura if you want zero setup work.'],
                    ['q' => 'Data location?', 'a' => 'On your SolaSkinner account — European SSD hosting.'],
                    ['q' => 'Self-hosted invoicing?', 'a' => 'Yes — for freelancers and SMEs who want invoices on their own hosting.'],
                ],
                'no' => [
                    ['q' => 'Trenger IT-ansatte?', 'a' => 'Ikke til daglig faktura. Vi kan installere og brande om du vil.'],
                    ['q' => 'Hvor ligger data?', 'a' => 'På din SolaSkinner-konto — europeisk SSD.'],
                    ['q' => 'Self-host faktura?', 'a' => 'Ja — for frilansere og SMB som vil ha fakturaer på egen hosting.'],
                ],
                'uk' => [
                    ['q' => 'Чи потрібен IT-відділ?', 'a' => 'Ні для щоденних рахунків. Можемо встановити й забрендувати Faktura.'],
                    ['q' => 'Де дані?', 'a' => 'На вашому акаунті SolaSkinner — європейський SSD.'],
                    ['q' => 'Self-hosted рахунки?', 'a' => 'Так — для ФОП і МСБ, які хочуть рахунки на власному хостингу.'],
                ],
            ],
        ],
        'lending' => [
            'slug' => 'lending',
            'file' => 'hosting-for-lending.php',
            'name' => 'Business Landing',
            'icon' => 'fa-store',
            'installer' => 'Business Landing',
            'keywords' => [
                'en' => 'SEO business landing page CMS, conversion landing AI copy, no-code lead page hosting',
                'no' => 'SEO business landing CMS, konverteringslanding AI-tekst, leadside uten koding',
                'uk' => 'SEO бізнес-лендінг CMS, конверсійний landing AI тексти, лендінг без коду',
            ],
            'tagline' => [
                'en' => 'Conversion landings — SEO blocks, AI headline help, marketers edit without code',
                'no' => 'Konverteringslandinger — SEO-blokker, AI-overskrifter, markedsførere redigerer uten koding',
                'uk' => 'Конверсійні лендінги — SEO-блоки, AI-заголовки, маркетологи редагують без коду',
            ],
            'intro' => [
                'en' => 'Business Landing is a free CMS for campaign and company pages built for SEO and paid traffic: clear blocks, CTAs and lead forms. AI helps draft headlines and offer text. Marketers and assistants change copy without developers. We can launch your first landing with domain and SSL.',
                'no' => 'Business Landing er et gratis CMS for kampanje- og bedriftssider for SEO og betalt trafikk: blokker, CTA og leadskjema. AI hjelper med overskrifter og tilbudstekst. Markedsførere endrer tekst uten utviklere. Vi kan lansere første landing med domene og SSL.',
                'uk' => 'Business Landing — безкоштовна CMS лендінгів під SEO й платний трафік: блоки, CTA, форми лідів. AI допомагає з заголовками й оферами. Маркетологи змінюють тексти без розробників. Можемо запустити перший лендінг із доменом і SSL.',
            ],
            'features' => [
                'en' => [
                    'SEO-friendly landing structure and headings',
                    'AI help for CTAs, offers and headlines',
                    'No-code edits for marketing staff',
                    'Lead capture oriented layout',
                    'Fast mobile pages on EU SSD',
                    'One-click install + optional full setup',
                ],
                'no' => [
                    'SEO-vennlig landing-struktur',
                    'AI-hjelp til CTA, tilbud og overskrifter',
                    'Redigering uten koding for marked',
                    'Layout for lead-fangst',
                    'Rask mobilside på EU-SSD',
                    'Ett-klikks install + valgfritt fullt oppsett',
                ],
                'uk' => [
                    'SEO-структура лендінгу та заголовків',
                    'AI-допомога з CTA, оферами, headline',
                    'Редагування без коду для маркетингу',
                    'Верстка під збір лідів',
                    'Швидка мобільна сторінка на EU SSD',
                    'Установка в 1 клік + опційний повний запуск',
                ],
            ],
            'use_cases' => [
                'en' => ['Product launches', 'Local services', 'Agency client LPs', 'Ad campaign pages'],
                'no' => ['Produktlansering', 'Lokale tjenester', 'Byrå-landinger', 'Annonsekampanjer'],
                'uk' => ['Запуски продуктів', 'Локальні послуги', 'Лендінги клієнтів', 'Рекламні кампанії'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Can marketing change text alone?', 'a' => 'Yes — no code. AI can speed up first drafts for SEO and ads.'],
                    ['q' => 'You set it up for us?', 'a' => 'Yes — install, domain, SSL and starter blocks on request.'],
                    ['q' => 'Free landing CMS?', 'a' => 'Yes in the SolaSkinner installer.'],
                ],
                'no' => [
                    ['q' => 'Kan marked endre tekst alene?', 'a' => 'Ja — uten koding. AI fremskynder utkast for SEO og annonser.'],
                    ['q' => 'Setter dere opp for oss?', 'a' => 'Ja — install, domene, SSL og startblokker på forespørsel.'],
                    ['q' => 'Gratis landing-CMS?', 'a' => 'Ja i SolaSkinner-installatøren.'],
                ],
                'uk' => [
                    ['q' => 'Чи змінить текст маркетинг сам?', 'a' => 'Так — без коду. AI прискорює чернетки під SEO й рекламу.'],
                    ['q' => 'Налаштуєте за нас?', 'a' => 'Так — установка, домен, SSL і стартові блоки за запитом.'],
                    ['q' => 'Безкоштовний лендінг?', 'a' => 'Так, у безкоштовному інсталяторі SolaSkinner.'],
                ],
            ],
        ],
        'hosting' => [
            'slug' => 'hosting',
            'file' => 'hosting-for-hosting.php',
            'name' => 'Hosting CMS',
            'icon' => 'fa-server',
            'installer' => 'Hosting CMS',
            'keywords' => [
                'en' => 'white label hosting CMS, multi-tenant hosting panel PHP, reseller hosting software no-code ops',
                'no' => 'white label hosting CMS, multi-tenant hostingpanel, reseller hosting uten tung koding',
                'uk' => 'white label хостинг CMS, multi-tenant панель, реселер хостинг без складного коду',
            ],
            'tagline' => [
                'en' => 'White-label hosting panel for agencies — client accounts, sites and plans you can operate without deep coding',
                'no' => 'White-label hostingpanel for byrå — klientkontoer, nettsteder og planer uten dyp koding',
                'uk' => 'White-label хостинг-панель для агенцій — акаунти, сайти й тарифи без глибокого коду',
            ],
            'intro' => [
                'en' => 'Hosting CMS is a PHP multi-client control layer for agencies and resellers: accounts, websites, plans and admin tools under your brand. Operators work in a structured panel; advanced customization is optional. Pair with SolaSkinner EU SSD, free SSL and MySQL. Need a branded panel ready for clients? We can install and walk you through operations.',
                'no' => 'Hosting CMS er et multi-klient kontrollag for byrå og resellere: kontoer, nettsteder, planer og admin under ditt merke. Operatører jobber i strukturert panel. Kjør på SolaSkinner EU-SSD med gratis SSL og MySQL. Vil du ha merket panel klart? Vi installerer og viser daglig drift.',
                'uk' => 'Hosting CMS — multi-client шар керування для агенцій і реселерів: акаунти, сайти, тарифи, адмінка під вашим брендом. Оператори працюють у структурованій панелі. На EU SSD SolaSkinner з SSL і MySQL. Потрібна готова брендована панель? Встановимо й проведемо онбординг.',
            ],
            'features' => [
                'en' => [
                    'Multi-client / multi-site structure',
                    'Hosting-style admin and client workflows',
                    'Plans and account foundations',
                    'Self-hosted branding and data control',
                    'Domain + free SSL + MySQL on EU SSD',
                    'Optional install and operator training',
                ],
                'no' => [
                    'Struktur for flere klienter og nettsteder',
                    'Hosting-lignende admin- og klientflyt',
                    'Grunnlag for planer og kontoer',
                    'Self-hostet merkevare og datakontroll',
                    'Domene + gratis SSL + MySQL på EU-SSD',
                    'Valgfri install og opplæring',
                ],
                'uk' => [
                    'Структура під багатьох клієнтів і сайти',
                    'Адмін- і клієнтські сценарії «як у хостингу»',
                    'Основа для тарифів і акаунтів',
                    'Self-hosted бренд і контроль даних',
                    'Домен + SSL + MySQL на EU SSD',
                    'Опційна установка й навчання операторів',
                ],
            ],
            'use_cases' => [
                'en' => ['Web agencies', 'Hosting resellers', 'Freelancers with many clients', 'White-label managed hosting'],
                'no' => ['Webbyrå', 'Hosting-resellere', 'Frilansere med mange kunder', 'White-label managed hosting'],
                'uk' => ['Веб-агенції', 'Реселери хостингу', 'Фрілансери з багатьма клієнтами', 'White-label managed-хостинг'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Same as a SolaSkinner account?', 'a' => 'No. SolaSkinner is infrastructure; Hosting CMS is a product panel you brand for end clients.'],
                    ['q' => 'Can non-devs operate it?', 'a' => 'Daily account and site ops are panel-based; we can train your team or configure the first instance.'],
                    ['q' => 'Own domain?', 'a' => 'Yes — domain + free SSL, then Hosting CMS in the site folder.'],
                ],
                'no' => [
                    ['q' => 'Samme som SolaSkinner-konto?', 'a' => 'Nei. SolaSkinner er infrastruktur; Hosting CMS er produktpanel du brander for kunder.'],
                    ['q' => 'Kan ikke-utviklere drifte?', 'a' => 'Daglig drift er panelbasert; vi kan trene teamet eller sette opp første instans.'],
                    ['q' => 'Eget domene?', 'a' => 'Ja — domene + gratis SSL, deretter Hosting CMS i sidemappen.'],
                ],
                'uk' => [
                    ['q' => 'Це той самий акаунт SolaSkinner?', 'a' => 'Ні. SolaSkinner — інфраструктура; Hosting CMS — продуктова панель під ваш бренд.'],
                    ['q' => 'Чи впораються не-розробники?', 'a' => 'Щоденна робота через панель; можемо навчити команду або налаштувати перший інстанс.'],
                    ['q' => 'Свій домен?', 'a' => 'Так — домен + SSL, далі Hosting CMS у теці сайту.'],
                ],
            ],
        ],
        'news' => [
            'slug' => 'news',
            'file' => 'hosting-for-news.php',
            'name' => 'News',
            'icon' => 'fa-bullhorn',
            'installer' => 'News',
            'keywords' => [
                'en' => 'SEO corporate news CMS, AI press posts, company news site no-code hosting',
                'no' => 'SEO bedriftsnyheter CMS, AI presseposter, firmasider uten koding',
                'uk' => 'SEO корпоративні новини CMS, AI прес-пости, новини компанії без коду',
            ],
            'tagline' => [
                'en' => 'Corporate news CMS — SEO posts, AI drafts, PR or office staff can publish',
                'no' => 'CMS for bedriftsnyheter — SEO-poster, AI-utkast, PR eller kontor kan publisere',
                'uk' => 'Корпоративні новини — SEO-пости, AI-чернетки, публікує PR або офіс',
            ],
            'intro' => [
                'en' => 'News is a lighter free CMS for company updates and press-style posts with SEO basics built in. AI helps draft announcements; assistants publish without WordPress complexity. Ideal when you want a clean corporate feed. We can set up News under your domain with SSL.',
                'no' => 'News er et lettere gratis CMS for bedriftsoppdateringer med SEO-grunnlag. AI hjelper med utkast; assistenter publiserer uten tung WordPress. Vi kan sette opp News under ditt domene med SSL.',
                'uk' => 'News — легша безкоштовна CMS оновлень компанії з базовим SEO. AI допомагає з чернетками; асистенти публікують без важкого WordPress. Можемо налаштувати News на вашому домені з SSL.',
            ],
            'features' => [
                'en' => [
                    'SEO-ready news posts and categories',
                    'AI-assisted announcement drafts',
                    'Simple publish flow for office/PR staff',
                    'Lighter than full media portals (see Today)',
                    'One-click install + free SSL',
                    'Optional corporate setup by us',
                ],
                'no' => [
                    'SEO-klare nyhetsposter og kategorier',
                    'AI-utkast til kunngjøringer',
                    'Enkel publisering for kontor/PR',
                    'Lettere enn full mediaportal (se Today)',
                    'Ett-klikks install + gratis SSL',
                    'Valgfritt bedriftsoppsett',
                ],
                'uk' => [
                    'SEO-готові новини та категорії',
                    'AI-чернетки оголошень',
                    'Проста публікація для офісу/PR',
                    'Легше за повний медіа-портал (див. Today)',
                    'Установка в 1 клік + SSL',
                    'Опційне корпоративне налаштування',
                ],
            ],
            'use_cases' => [
                'en' => ['Company blogs', 'Product changelogs', 'Press corners', 'Public corporate updates'],
                'no' => ['Bedriftsblogg', 'Produktendringer', 'Presse', 'Offentlige bedriftsnyheter'],
                'uk' => ['Корпоративний блог', 'Чейнджлоги', 'Преса', 'Публічні новини компанії'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Difference vs Today?', 'a' => 'Today is a fuller news portal; News is lighter corporate news. Both free; both support SEO and simple publishing.'],
                    ['q' => 'Non-tech publishers?', 'a' => 'Yes — built for PR and office roles; AI helps with first drafts.'],
                    ['q' => 'Free install?', 'a' => 'Yes on SolaSkinner free installer.'],
                ],
                'no' => [
                    ['q' => 'Forskjell fra Today?', 'a' => 'Today er full portal; News er lettere bedriftsnyheter. Begge gratis med enkel publisering.'],
                    ['q' => 'Ikke-tekniske redaktører?', 'a' => 'Ja — for PR og kontor; AI hjelper med utkast.'],
                    ['q' => 'Gratis install?', 'a' => 'Ja på SolaSkinner gratis installatør.'],
                ],
                'uk' => [
                    ['q' => 'Різниця з Today?', 'a' => 'Today — повніший портал; News — легші корпоративні новини. Обидва безкоштовні й прості для публікації.'],
                    ['q' => 'Не-технічні редактори?', 'a' => 'Так — для PR і офісу; AI допомагає з чернетками.'],
                    ['q' => 'Безкоштовна установка?', 'a' => 'Так, безкоштовний інсталятор SolaSkinner.'],
                ],
            ],
        ],
        'wordpress' => [
            'slug' => 'wordpress',
            'file' => 'hosting-for-wordpress.php',
            'name' => 'WordPress',
            'icon' => 'fa-wordpress',
            'icon_brand' => true,
            'installer' => 'WordPress',
            'keywords' => [
                'en' => 'free WordPress hosting Europe, free WordPress install one click, easy WordPress setup SSL, SolaSkinner WordPress free',
                'no' => 'gratis WordPress hosting Europa, gratis WordPress install ett klikk, enkel WordPress oppsett SSL, SolaSkinner',
                'uk' => 'безкоштовний WordPress хостинг Європа, безкоштовна установка WordPress 1 клік, просто встановити WordPress SSL, SolaSkinner',
            ],
            'tagline' => [
                'en' => 'WordPress is free — and on SolaSkinner it is extremely easy to install (1 click, free SSL)',
                'no' => 'WordPress er gratis — og hos SolaSkinner er det ekstremt enkelt å installere (ett klikk, gratis SSL)',
                'uk' => 'WordPress і так безкоштовний — а на SolaSkinner його дуже просто встановити (1 клік, безкоштовний SSL)',
            ],
            'intro' => [
                'en' => 'WordPress is free open-source software — you never pay a license for the CMS itself. On SolaSkinner we make the rest just as simple: open the panel → Installer → WordPress → one click. MySQL, free SSL and site folder are prepared for you, so a beginner can go live in minutes without terminal or manual downloads. Then install your favourite free themes, SEO plugins and AI writing plugins. You only pay the hosting plan — not WordPress, not the installer. Prefer turnkey? We can click-install and hand over admin access for you.',
                'no' => 'WordPress er gratis open source — du betaler aldri lisens for selve CMS-et. Hos SolaSkinner er resten like enkelt: panel → Installer → WordPress → ett klikk. MySQL, gratis SSL og mappe settes opp for deg, så en nybegynner er live på minutter uten terminal eller manuell nedlasting. Deretter: gratis temaer, SEO-plugins og AI-plugins. Du betaler bare hosting — ikke WordPress, ikke installatøren. Vil du ha det ferdig? Vi installerer og gir deg admin-tilgang.',
                'uk' => 'WordPress — безкоштовне open-source ПЗ: за саму CMS ви не платите. На SolaSkinner все інше так само просто: панель → Інсталятор → WordPress → один клік. MySQL, безкоштовний SSL і папка сайту готуються за вас — новачок запускає сайт за хвилини без терміналу й ручних завантажень. Далі — безкоштовні теми, SEO- та AI-плагіни. Платите лише хостинг, не WordPress і не інсталятор. Потрібно «під ключ»? Встановимо й віддамо доступ до адмінки.',
            ],
            'features' => [
                'en' => [
                    'WordPress CMS is free (no license fee)',
                    'Extremely easy 1-click install from the panel',
                    'Ready in minutes — no SSH, no manual zip upload',
                    'Free SSL + MySQL created automatically',
                    'SEO & AI plugins: install what you need after setup',
                    'File manager and optional migration help',
                ],
                'no' => [
                    'WordPress-CMS er gratis (ingen lisensavgift)',
                    'Ekstremt enkel install med ett klikk i panelet',
                    'Live på minutter — uten SSH eller manuell zip',
                    'Gratis SSL + MySQL opprettes automatisk',
                    'SEO- og AI-plugins: installer det du trenger etterpå',
                    'Filbehandler og valgfri migreringshjelp',
                ],
                'uk' => [
                    'CMS WordPress безкоштовна (без ліцензії)',
                    'Дуже проста установка в 1 клік з панелі',
                    'Готово за хвилини — без SSH і ручного zip',
                    'Безкоштовний SSL + MySQL створюються автоматично',
                    'SEO- та AI-плагіни: ставте потрібні після запуску',
                    'Файловий менеджер і опційна допомога з міграцією',
                ],
            ],
            'use_cases' => [
                'en' => ['Blogs', 'Company sites', 'Landing pages', 'Agencies & freelancers'],
                'no' => ['Blogg', 'Bedriftssider', 'Landingssider', 'Byrå og frilansere'],
                'uk' => ['Блоги', 'Сайти компаній', 'Лендінги', 'Агенції та фрилансери'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Is WordPress free?', 'a' => 'Yes. WordPress.org software is free forever. On SolaSkinner the installer and SSL are free too — you only pay the hosting plan.'],
                    ['q' => 'How hard is install?', 'a' => 'Very easy: log in → Installer → choose WordPress → one click. No technical skills required for a standard site.'],
                    ['q' => 'How long until the site is online?', 'a' => 'Usually a few minutes after you click install — then open the site URL and finish the short WordPress welcome wizard.'],
                    ['q' => 'AI for content?', 'a' => 'Yes — after install, add popular free or paid AI writing plugins; hosting is ready.'],
                ],
                'no' => [
                    ['q' => 'Er WordPress gratis?', 'a' => 'Ja. WordPress.org er gratis for alltid. Hos SolaSkinner er også installatør og SSL gratis — du betaler bare hostingplanen.'],
                    ['q' => 'Hvor vanskelig er install?', 'a' => 'Veldig enkelt: logg inn → Installer → WordPress → ett klikk. Ingen teknisk bakgrunn trengs for en vanlig side.'],
                    ['q' => 'Hvor fort er siden online?', 'a' => 'Ofte noen minutter etter klikk — så åpner du URL-en og fullfører den korte WordPress-veiviseren.'],
                    ['q' => 'AI for innhold?', 'a' => 'Ja — etter install kan du legge til populære AI-plugins; hostingen er klar.'],
                ],
                'uk' => [
                    ['q' => 'WordPress безкоштовний?', 'a' => 'Так. ПЗ WordPress.org безкоштовне назавжди. На SolaSkinner інсталятор і SSL теж безкоштовні — платите лише тариф хостингу.'],
                    ['q' => 'Наскільки складно встановити?', 'a' => 'Дуже просто: увійти → Інсталятор → WordPress → один клік. Для звичайного сайту технічні навички не потрібні.'],
                    ['q' => 'Скільки до «сайту онлайн»?', 'a' => 'Зазвичай кілька хвилин після кліку — далі відкриваєте URL і короткий майстер WordPress.'],
                    ['q' => 'AI для контенту?', 'a' => 'Так — після установки ставте популярні AI-плагіни; хостинг готовий.'],
                ],
            ],
        ],
        '3d' => [
            'slug' => '3d',
            'file' => 'hosting-for-3d.php',
            'name' => '3D',
            'icon' => 'fa-cube',
            'installer' => '3D',
            'keywords' => [
                'en' => 'SEO 3D showcase website, 3D portfolio CMS no-code, WebGL product demo hosting',
                'no' => 'SEO 3D showcase, 3D portefølje CMS uten koding, WebGL produktdemo hosting',
                'uk' => 'SEO 3D вітрина, 3D портфоліо CMS без коду, WebGL демо хостинг',
            ],
            'tagline' => [
                'en' => '3D product showcases — visual SEO pages, simple content updates, optional setup',
                'no' => '3D produktutstillinger — visuelle SEO-sider, enkle innholdoppdateringer, valgfritt oppsett',
                'uk' => '3D-вітрини продукту — візуальні SEO-сторінки, просте оновлення контенту, опційне налаштування',
            ],
            'intro' => [
                'en' => '3D is a free CMS for immersive product and portfolio showcases on fast EU SSD. Page structure supports SEO for brand and product discovery; non-developers can update core content after setup. Advanced 3D assets are design content — we can install the base CMS and help with first presentation pages.',
                'no' => '3D er et gratis CMS for immersive produkt- og porteføljeutstillinger på rask EU-SSD. Strukturelt klart for SEO; etter oppsett kan ikke-utviklere oppdatere kjerne-innhold. Avanserte 3D-assets er design — vi kan installere basis-CMS og hjelpe med første sider.',
                'uk' => '3D — безкоштовна CMS для іммерсивних вітрин і портфоліо на швидкому EU SSD. Структура під SEO бренду й продукту; після запуску контент оновлюють без розробників. Складні 3D-ассети — дизайн; ми встановимо базову CMS і допоможемо з першими сторінками.',
            ],
            'features' => [
                'en' => [
                    '3D-oriented presentation layout',
                    'SEO-friendly public pages on SSD',
                    'Content updates without daily coding',
                    'Domain + free SSL',
                    'One-click install',
                    'Optional first-page setup help',
                ],
                'no' => [
                    '3D-orientert presentasjon',
                    'SEO-vennlige sider på SSD',
                    'Innholdoppdateringer uten daglig koding',
                    'Domene + gratis SSL',
                    'Ett-klikks install',
                    'Valgfri hjelp til første sider',
                ],
                'uk' => [
                    '3D-орієнтована презентація',
                    'SEO-сторінки на SSD',
                    'Оновлення контенту без щоденного коду',
                    'Домен + SSL',
                    'Установка в 1 клік',
                    'Опційна допомога з першими сторінками',
                ],
            ],
            'use_cases' => [
                'en' => ['Design portfolios', 'Product 3D demos', 'Architecture previews', 'Event showcases'],
                'no' => ['Designportefølje', 'Produkt-3D', 'Arkitektur', 'Event-show'],
                'uk' => ['Дизайн-портфоліо', '3D-демо продукту', 'Архітектура', 'Івент-шоукейси'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'Need WebGL skills every day?', 'a' => 'Base CMS is free; advanced assets are optional design work. We can set up the base site.'],
                    ['q' => 'SEO for showcases?', 'a' => 'Public pages and HTTPS on fast hosting support discoverability.'],
                    ['q' => 'Free?', 'a' => 'Yes via free SolaSkinner installer.'],
                ],
                'no' => [
                    ['q' => 'Trenger WebGL hver dag?', 'a' => 'Basis-CMS er gratis; avanserte assets er valgfritt design. Vi kan sette opp basen.'],
                    ['q' => 'SEO for showcase?', 'a' => 'Offentlige sider og HTTPS på rask hosting støtter synlighet.'],
                    ['q' => 'Gratis?', 'a' => 'Ja via gratis SolaSkinner-installatør.'],
                ],
                'uk' => [
                    ['q' => 'Чи потрібен WebGL щодня?', 'a' => 'Базова CMS безкоштовна; складні ассети — за потреби. Базу можемо налаштувати ми.'],
                    ['q' => 'SEO вітрин?', 'a' => 'Публічні сторінки й HTTPS на швидкому хостингу підтримують видимість.'],
                    ['q' => 'Безкоштовно?', 'a' => 'Так, безкоштовний інсталятор SolaSkinner.'],
                ],
            ],
        ],
        'ai' => [
            'slug' => 'ai',
            'file' => 'hosting-for-ai.php',
            'name' => 'AI for SEO',
            'icon' => 'fa-robot',
            'installer' => 'AI for SEO',
            'keywords' => [
                'en' => 'AI for SEO, AI content publishing CMS, SEO writing assistant hosting, free AI SEO tool SolaSkinner',
                'no' => 'AI for SEO, AI innholdspublisering CMS, SEO skriveassistent hosting, gratis AI SEO SolaSkinner',
                'uk' => 'AI для SEO, AI публікація контенту CMS, SEO асистент текстів, безкоштовний AI SEO SolaSkinner',
            ],
            'tagline' => [
                'en' => 'AI for SEO: generate and publish search-ready content without programming — any team member can use it',
                'no' => 'AI for SEO: generer og publiser søkeklart innhold uten koding — alle i teamet kan bruke det',
                'uk' => 'AI для SEO: генеруйте й публікуйте контент під пошук без програмування — впорається будь-який працівник',
            ],
            'intro' => [
                'en' => 'AI for SEO is a free CMS focused on search-oriented content: titles, descriptions, pages and publishing workflows powered by artificial intelligence. Marketers, editors and beginners publish without writing code or hiring a developer for every text. Structure is adapted for SEO; you keep control on your SolaSkinner hosting with free SSL. Prefer turnkey? We install AI for SEO, connect domain and guide your first content workflow.',
                'no' => 'AI for SEO er et gratis CMS for søkeorientert innhold: titler, beskrivelser, sider og publisering med kunstig intelligens. Markedsførere, redaktører og nybegynnere publiserer uten koding. Struktur tilpasset SEO; du eier stacken på SolaSkinner med gratis SSL. Vil du ha nøkkelklart? Vi installerer AI for SEO, kobler domene og viser første innholdsflyt.',
                'uk' => 'AI for SEO — безкоштовна CMS під пошуковий контент: заголовки, описи, сторінки та публікація зі штучним інтелектом. Маркетологи, редактори й новачки публікують без коду й без розробника на кожен текст. Структура адаптована під SEO; усе на вашому хостингу SolaSkinner з SSL. Потрібно «під ключ»? Встановимо AI for SEO, підключимо домен і проведемо перший контент-процес.',
            ],
            'features' => [
                'en' => [
                    'AI-assisted SEO titles, descriptions and page copy',
                    'Publish search-ready content without programming',
                    'Workflow for marketers, editors and beginners',
                    'SEO-oriented structure on fast EU hosting',
                    'Free SSL + one-click install on SolaSkinner',
                    'Optional full setup and training by our team',
                    'Keep content and keys under your account control',
                ],
                'no' => [
                    'AI-hjelp til SEO-titler, beskrivelser og sidetekst',
                    'Publiser søkeklart innhold uten koding',
                    'Flyt for marked, redaksjon og nybegynnere',
                    'SEO-orientert struktur på rask EU-hosting',
                    'Gratis SSL + ett-klikks install',
                    'Valgfritt fullt oppsett og opplæring',
                    'Innhold og nøkler under din konto',
                ],
                'uk' => [
                    'AI-допомога з SEO-заголовками, описами й текстами сторінок',
                    'Публікація контенту під пошук без програмування',
                    'Процес для маркетологів, редакторів і новачків',
                    'SEO-структура на швидкому EU-хостингу',
                    'Безкоштовний SSL + установка в 1 клік',
                    'Опційний повний запуск і навчання командою',
                    'Контент і ключі під контролем вашого акаунту',
                ],
            ],
            'use_cases' => [
                'en' => ['SEO content teams', 'Agencies scaling copy', 'SMEs without developers', 'Product & blog growth'],
                'no' => ['SEO-innholdsteam', 'Byrå som skalerer tekst', 'SMB uten utviklere', 'Produkt- og bloggvekst'],
                'uk' => ['SEO-контент команди', 'Агенції зі масштабом текстів', 'МСБ без розробників', 'Ріст блогу й продукту'],
            ],
            'faq' => [
                'en' => [
                    ['q' => 'What is AI for SEO?', 'a' => 'A free CMS on SolaSkinner to generate and publish search-oriented content with AI — no coding required for daily use.'],
                    ['q' => 'Can a beginner use it?', 'a' => 'Yes. Built for any employee; we can also configure and train your team.'],
                    ['q' => 'Do you include AI API keys?', 'a' => 'You host the CMS free; connect your own AI provider keys if the app requires them. We can help with setup.'],
                    ['q' => 'Why on SolaSkinner?', 'a' => 'EU SSD, free SSL, domains and free install — SEO stack and AI content in one hosting panel.'],
                ],
                'no' => [
                    ['q' => 'Hva er AI for SEO?', 'a' => 'Et gratis CMS på SolaSkinner for å generere og publisere søkeorientert innhold med AI — uten koding i daglig bruk.'],
                    ['q' => 'Kan nybegynnere bruke det?', 'a' => 'Ja. Laget for alle ansatte; vi kan også konfigurere og trene teamet.'],
                    ['q' => 'Inkluderer AI API-nøkler?', 'a' => 'CMS hostes gratis; koble egne AI-nøkler ved behov. Vi hjelper med oppsett.'],
                    ['q' => 'Hvorfor SolaSkinner?', 'a' => 'EU-SSD, gratis SSL, domener og gratis install — SEO og AI-innhold i ett panel.'],
                ],
                'uk' => [
                    ['q' => 'Що таке AI for SEO?', 'a' => 'Безкоштовна CMS на SolaSkinner для генерації й публікації контенту під пошук з AI — без коду в щоденній роботі.'],
                    ['q' => 'Чи впорається новачок?', 'a' => 'Так. Для будь-якого працівника; можемо налаштувати й навчити команду.'],
                    ['q' => 'Чи є AI API-ключі?', 'a' => 'CMS безкоштовна на хостингу; ключі AI-провайдера підключаєте свої за потреби. Допоможемо з setup.'],
                    ['q' => 'Чому SolaSkinner?', 'a' => 'EU SSD, SSL, домени й безкоштовна установка — SEO-стек і AI-контент в одній панелі.'],
                ],
            ],
        ],
    ];
}

/** @return list<string> */
function hs_seo_apps_order(): array
{
    return [
        'shop', 'booking', 'auction', 'freelance', 'pizza', 'today', 'gamehub',
        'tavle', 'faktura', 'lending', 'hosting', 'news', 'wordpress', '3d', 'ai',
    ];
}

function hs_seo_app(string $slug): ?array
{
    $all = hs_seo_apps_catalog();
    return $all[$slug] ?? null;
}

/**
 * @param array<string, mixed> $app
 * @return array<string, mixed>
 */
function hs_seo_app_lang(array $app, string $lang): array
{
    $L = in_array($lang, ['en', 'no', 'uk', 'lt', 'pl', 'sv'], true) ? $lang : 'en';
    $pick = static function (array $map) use ($L): mixed {
        return $map[$L] ?? $map['en'] ?? null;
    };

    $screensRaw = is_array($app['screens'] ?? null) ? $app['screens'] : [];
    $screens = [];
    foreach ($screensRaw as $sc) {
        if (!is_array($sc)) {
            continue;
        }
        $titleMap = is_array($sc['title'] ?? null) ? $sc['title'] : [];
        $descMap = is_array($sc['desc'] ?? null) ? $sc['desc'] : [];
        $screens[] = [
            'src' => (string) ($sc['src'] ?? ''),
            'jpg' => (string) ($sc['jpg'] ?? $sc['src'] ?? ''),
            'title' => (string) ($titleMap[$L] ?? $titleMap['en'] ?? ''),
            'desc' => (string) ($descMap[$L] ?? $descMap['en'] ?? ''),
        ];
    }

    return [
        'slug' => (string) ($app['slug'] ?? ''),
        'file' => (string) ($app['file'] ?? ''),
        'name' => (string) ($app['name'] ?? ''),
        'icon' => (string) ($app['icon'] ?? 'fa-cube'),
        'icon_brand' => !empty($app['icon_brand']),
        'installer' => (string) ($app['installer'] ?? $app['name'] ?? ''),
        'demo_url' => (string) ($app['demo_url'] ?? ''),
        'product_url' => (string) ($app['product_url'] ?? ''),
        'tagline' => (string) $pick((array) ($app['tagline'] ?? [])),
        'intro' => (string) $pick((array) ($app['intro'] ?? [])),
        'keywords' => (string) $pick((array) ($app['keywords'] ?? [])),
        'features' => (array) $pick((array) ($app['features'] ?? [])),
        'use_cases' => (array) $pick((array) ($app['use_cases'] ?? [])),
        'faq' => (array) $pick((array) ($app['faq'] ?? [])),
        'screens' => $screens,
    ];
}
