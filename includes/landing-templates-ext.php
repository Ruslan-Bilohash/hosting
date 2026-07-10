<?php
declare(strict_types=1);

/** @return array<string, array{nav_style?:string,footer_style?:string,nav_cta_text?:string,nav_cta_url?:string}> */
function hs_landing_template_chrome_presets(): array
{
    return [
        'dentist' => ['nav_style' => 'cta', 'footer_style' => 'mega', 'nav_cta_url' => '#contact'],
        'lawyer' => ['nav_style' => 'dark', 'footer_style' => 'dark'],
        'fitness' => ['nav_style' => 'gradient', 'footer_style' => 'gradient'],
        'beauty' => ['nav_style' => 'centered', 'footer_style' => 'wave'],
        'hotel' => ['nav_style' => 'glass', 'footer_style' => 'mega'],
        'construction' => ['nav_style' => 'bordered', 'footer_style' => 'split'],
        'education' => ['nav_style' => 'pill', 'footer_style' => 'columns'],
        'medical' => ['nav_style' => 'cta', 'footer_style' => 'contact', 'nav_cta_url' => '#contact'],
        'auto' => ['nav_style' => 'dark', 'footer_style' => 'compact'],
        'photographer' => ['nav_style' => 'minimal', 'footer_style' => 'branded'],
        'bakery' => ['nav_style' => 'stacked', 'footer_style' => 'wave'],
        'real_estate' => ['nav_style' => 'cta', 'footer_style' => 'split', 'nav_cta_url' => '#contact'],
        'cleaning' => ['nav_style' => 'classic', 'footer_style' => 'compact'],
        'it_services' => ['nav_style' => 'dark', 'footer_style' => 'mega'],
        'wedding' => ['nav_style' => 'centered', 'footer_style' => 'gradient'],
        'veterinary' => ['nav_style' => 'pill', 'footer_style' => 'contact'],
        'coaching' => ['nav_style' => 'gradient', 'footer_style' => 'centered'],
        'nonprofit' => ['nav_style' => 'stacked', 'footer_style' => 'social'],
        'barber' => ['nav_style' => 'bordered', 'footer_style' => 'dark'],
        'florist' => ['nav_style' => 'glass', 'footer_style' => 'wave'],
        'taxi' => ['nav_style' => 'cta', 'footer_style' => 'compact', 'nav_cta_url' => '#contact'],
        'music_studio' => ['nav_style' => 'dark', 'footer_style' => 'branded'],
        'yoga' => ['nav_style' => 'centered', 'footer_style' => 'gradient'],
        'pharmacy' => ['nav_style' => 'classic', 'footer_style' => 'columns'],
        'accounting' => ['nav_style' => 'minimal', 'footer_style' => 'split'],
    ];
}

/** @return array<string, mixed>|null */
function hs_landing_template_meta_ext(string $templateId, array $t): ?array
{
    $all = hs_landing_business_template_catalog($t);
    if (!isset($all[$templateId])) {
        return null;
    }
    $cat = $all[$templateId];
    $chrome = hs_landing_template_chrome_presets()[$templateId] ?? [];
    foreach ((array) ($cat['blocks'] ?? []) as $row) {
        if (($row['type'] ?? '') !== 'hero') {
            continue;
        }
        $cta = (string) (($row['overrides'] ?? [])['cta_text'] ?? '');
        if ($cta !== '' && !isset($chrome['nav_cta_text']) && in_array($chrome['nav_style'] ?? '', ['cta', 'gradient', 'glass'], true)) {
            $chrome['nav_cta_text'] = $cta;
        }
        break;
    }

    return array_merge([
        'theme' => (string) ($cat['theme'] ?? 'emerald'),
        'color' => (string) ($cat['color'] ?? '#059669'),
        'tagline' => (string) ($cat['tagline'] ?? ''),
        'business_name' => (string) ($cat['business_name'] ?? ''),
        'icon_set' => (string) ($cat['icon_set'] ?? 'business'),
        'nav_links' => $cat['nav_links'] ?? [],
    ], $chrome);
}

/** @return list<array<string, mixed>>|null */
function hs_landing_template_blocks_ext(string $templateId, array $t): ?array
{
    $all = hs_landing_business_template_catalog($t);
    if (!isset($all[$templateId])) {
        return null;
    }
    $mk = static function (string $type, array $overrides = []) use ($t): array {
        $block = hs_landing_default_block($type, $t);
        $block['id'] = 'b_' . $type . '_' . substr(bin2hex(random_bytes(4)), 0, 6);

        return array_merge($block, $overrides);
    };
    $spec = $all[$templateId]['blocks'] ?? [];
    $out = [];
    foreach ($spec as $row) {
        $type = (string) ($row['type'] ?? '');
        $overrides = (array) ($row['overrides'] ?? []);
        if ($type === '') {
            continue;
        }
        $out[] = $mk($type, $overrides);
    }

    return $out;
}

/** @return array<string, array{label:string,desc:string,icon:string,blocks:list<array<string,mixed>>,meta:array<string,mixed>}> */
function hs_landing_page_templates_ext(array $t): array
{
    $out = [];
    foreach (hs_landing_business_template_catalog($t) as $id => $cat) {
        $blocks = hs_landing_template_blocks_ext($id, $t);
        if ($blocks === null) {
            continue;
        }
        $meta = hs_landing_template_meta_ext($id, $t) ?? [];
        $out[$id] = [
            'label' => (string) ($cat['label'] ?? $id),
            'desc' => (string) ($cat['desc'] ?? ''),
            'icon' => (string) ($cat['icon'] ?? 'fa-file'),
            'blocks' => $blocks,
            'meta' => $meta,
        ];
    }

    return $out;
}

/**
 * @return array<string, array<string, mixed>>
 */
function hs_landing_business_template_catalog(array $t): array
{
    $nav = static function (array $links): array {
        $out = [];
        foreach ($links as $l) {
            $out[] = ['label' => $l[0], 'url' => $l[1], 'on' => true];
        }

        return $out;
    };

    return [
        'dentist' => [
            'label' => $t['landing_tpl_dentist'] ?? 'Стоматологія',
            'desc' => $t['landing_tpl_dentist_desc'] ?? 'Клініка, послуги, ціни, запис на прийом',
            'icon' => 'fa-tooth',
            'theme' => 'teal', 'color' => '#0d9488', 'icon_set' => 'health',
            'business_name' => $t['landing_tpl_dentist_name'] ?? 'DentaSmile',
            'tagline' => $t['landing_tpl_dentist_tag'] ?? 'Сучасна стоматологія без болю',
            'nav_links' => $nav([['Послуги', '#services'], ['Ціни', '#pricing'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Здорова посмішка для всієї родини', 'subtitle' => 'Безболісне лікування, досвідчені лікарі, сучасне обладнання.', 'cta_text' => 'Записатися', 'cta_url' => '#contact']],
                ['type' => 'trust', 'overrides' => ['variant' => 'row', 'items' => [['icon' => 'fa-shield-halved', 'title' => 'Стерильність', 'text' => 'Європейські стандарти'], ['icon' => 'fa-user-doctor', 'title' => '15+ років', 'text' => 'Досвід лікарів'], ['icon' => 'fa-heart', 'title' => 'Без болю', 'text' => 'Сучасна анестезія']]]],
                ['type' => 'services', 'overrides' => ['section_title' => 'Наші послуги', 'items' => [['icon' => 'fa-tooth', 'title' => 'Лікування', 'text' => 'Карієс, пломби, канали.', 'price' => 'від 800 ₴'], ['icon' => 'fa-wand-magic-sparkles', 'title' => 'Відбілювання', 'text' => 'Безпечне освітлення.', 'price' => 'від 2500 ₴'], ['icon' => 'fa-teeth', 'title' => 'Імплантація', 'text' => 'Надійні імпланти.', 'price' => 'від 12000 ₴']]]],
                ['type' => 'pricing', 'overrides' => ['variant' => 'compact', 'section_title' => 'Популярні пакети']],
                ['type' => 'testimonials', 'overrides' => ['section_title' => 'Відгуки пацієнтів']],
                ['type' => 'hours', 'overrides' => []],
                ['type' => 'contact', 'overrides' => ['variant' => 'card', 'title' => 'Запис на прийом', 'phone' => '+380 44 123 45 67']],
            ],
        ],
        'lawyer' => [
            'label' => $t['landing_tpl_lawyer'] ?? 'Юридична фірма',
            'desc' => $t['landing_tpl_lawyer_desc'] ?? 'Консультації, практика, довіра клієнтів',
            'icon' => 'fa-scale-balanced',
            'theme' => 'midnight', 'color' => '#1e3a5f', 'icon_set' => 'business',
            'business_name' => $t['landing_tpl_lawyer_name'] ?? 'LexGuard Partners',
            'tagline' => $t['landing_tpl_lawyer_tag'] ?? 'Захист ваших прав і інтересів',
            'nav_links' => $nav([['Послуги', '#services'], ['Про нас', '#about'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'minimal', 'title' => 'Професійна юридична допомога', 'subtitle' => 'Судові спори, договори, корпоративне право.', 'cta_text' => 'Консультація', 'cta_url' => '#contact']],
                ['type' => 'stats_bar', 'overrides' => ['variant' => 'dark', 'items' => [['value' => '500+', 'label' => 'Виграних справ'], ['value' => '15', 'label' => 'Років досвіду'], ['value' => '98%', 'label' => 'Успішних кейсів']]]],
                ['type' => 'services', 'overrides' => ['variant' => 'cards', 'section_title' => 'Напрямки практики']],
                ['type' => 'about', 'overrides' => ['variant' => 'split', 'title' => 'Досвід, якому довіряють']],
                ['type' => 'icon_list', 'overrides' => ['section_title' => 'Чому обирають нас', 'variant' => 'check']],
                ['type' => 'faq', 'overrides' => ['section_title' => 'Часті питання']],
                ['type' => 'contact_bar', 'overrides' => ['variant' => 'gradient', 'phone' => '+380 44 200 00 00', 'email' => 'office@lexguard.ua']],
                ['type' => 'contact', 'overrides' => ['variant' => 'split']],
            ],
        ],
        'fitness' => [
            'label' => $t['landing_tpl_fitness'] ?? 'Фітнес-клуб',
            'desc' => $t['landing_tpl_fitness_desc'] ?? 'Тренування, абонементи, тренери',
            'icon' => 'fa-dumbbell',
            'theme' => 'rose', 'color' => '#e11d48', 'icon_set' => 'health',
            'business_name' => $t['landing_tpl_fitness_name'] ?? 'PowerFit Gym',
            'tagline' => $t['landing_tpl_fitness_tag'] ?? 'Сильніше тіло — сильніший дух',
            'nav_links' => $nav([['Програми', '#services'], ['Тарифи', '#pricing'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'centered', 'title' => 'Тренуйся на максимум', 'subtitle' => 'Сучасний зал, персональні тренери, групові заняття.', 'cta_text' => 'Спробувати безкоштовно', 'cta_url' => '#contact']],
                ['type' => 'features', 'overrides' => ['variant' => 'cards', 'section_title' => 'Що всередині']],
                ['type' => 'pricing', 'overrides' => ['section_title' => 'Абонементи']],
                ['type' => 'team', 'overrides' => ['section_title' => 'Тренери']],
                ['type' => 'gallery', 'overrides' => ['section_title' => 'Наш зал']],
                ['type' => 'cta', 'overrides' => ['variant' => 'banner', 'title' => 'Перше тренування — безкоштовно!']],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'beauty' => [
            'label' => $t['landing_tpl_beauty'] ?? 'Салон краси',
            'desc' => $t['landing_tpl_beauty_desc'] ?? 'Послуги, прайс, запис онлайн',
            'icon' => 'fa-spa',
            'theme' => 'rose', 'color' => '#f43f5e', 'icon_set' => 'health',
            'business_name' => $t['landing_tpl_beauty_name'] ?? 'Glow Studio',
            'tagline' => $t['landing_tpl_beauty_tag'] ?? 'Краса, в якій ви впевнені',
            'nav_links' => $nav([['Послуги', '#services'], ['Ціни', '#menu'], ['Запис', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'centered', 'title' => 'Ваша краса — наш профіль', 'subtitle' => 'Стрижки, манікюр, косметологія, візаж.', 'cta_text' => 'Записатися', 'cta_url' => '#contact']],
                ['type' => 'gallery', 'overrides' => ['variant' => 'masonry', 'section_title' => 'Наші роботи']],
                ['type' => 'menu', 'overrides' => ['section_title' => 'Прайс-лист', 'variant' => 'elegant']],
                ['type' => 'testimonials', 'overrides' => []],
                ['type' => 'social', 'overrides' => ['variant' => 'pills']],
                ['type' => 'contact', 'overrides' => ['variant' => 'card']],
            ],
        ],
        'hotel' => [
            'label' => $t['landing_tpl_hotel'] ?? 'Готель / B&B',
            'desc' => $t['landing_tpl_hotel_desc'] ?? 'Номери, зручності, бронювання',
            'icon' => 'fa-hotel',
            'theme' => 'gold', 'color' => '#ca8a04', 'icon_set' => 'travel',
            'business_name' => $t['landing_tpl_hotel_name'] ?? 'Grand View Hotel',
            'tagline' => $t['landing_tpl_hotel_tag'] ?? 'Комфорт і вид, який запамʼятається',
            'nav_links' => $nav([['Номери', '#services'], ['Галерея', '#gallery'], ['Бронь', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Відпочинок преміум-класу', 'subtitle' => 'Центр міста, сніданок включено, безкоштовний Wi‑Fi.', 'cta_text' => 'Забронювати', 'cta_url' => '#contact']],
                ['type' => 'features', 'overrides' => ['section_title' => 'Зручності готелю']],
                ['type' => 'gallery', 'overrides' => ['section_title' => 'Номери та інтерʼєр']],
                ['type' => 'pricing', 'overrides' => ['section_title' => 'Тарифи на номери', 'variant' => 'cards']],
                ['type' => 'testimonials', 'overrides' => ['section_title' => 'Відгуки гостей']],
                ['type' => 'map', 'overrides' => []],
                ['type' => 'contact', 'overrides' => ['variant' => 'split']],
            ],
        ],
        'construction' => [
            'label' => $t['landing_tpl_construction'] ?? 'Будівництво',
            'desc' => $t['landing_tpl_construction_desc'] ?? 'Ремонт, будівництво, кошторис',
            'icon' => 'fa-helmet-safety',
            'theme' => 'sunset', 'color' => '#ea580c', 'icon_set' => 'business',
            'business_name' => $t['landing_tpl_construction_name'] ?? 'BuildPro',
            'tagline' => $t['landing_tpl_construction_tag'] ?? 'Будуємо якісно — в строк і в бюджет',
            'nav_links' => $nav([['Послуги', '#services'], ['Проєкти', '#gallery'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Ремонт і будівництво під ключ', 'subtitle' => 'Квартири, будинки, комерційні обʼєкти.', 'cta_text' => 'Безкоштовний кошторис', 'cta_url' => '#contact']],
                ['type' => 'stats_bar', 'overrides' => []],
                ['type' => 'services', 'overrides' => ['section_title' => 'Наші роботи']],
                ['type' => 'gallery', 'overrides' => ['section_title' => 'Реалізовані проєкти']],
                ['type' => 'steps', 'overrides' => ['section_title' => 'Як ми працюємо']],
                ['type' => 'callout', 'overrides' => ['variant' => 'promo', 'title' => 'Акція', 'text' => 'Безкоштовний виїзд інженера та кошторис.']],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'education' => [
            'label' => $t['landing_tpl_education'] ?? 'Освіта / курси',
            'desc' => $t['landing_tpl_education_desc'] ?? 'Курси, програми, запис',
            'icon' => 'fa-graduation-cap',
            'theme' => 'indigo', 'color' => '#4f46e5', 'icon_set' => 'business',
            'business_name' => $t['landing_tpl_education_name'] ?? 'SkillUp Academy',
            'tagline' => $t['landing_tpl_education_tag'] ?? 'Навчання, що відкриває карʼєру',
            'nav_links' => $nav([['Курси', '#services'], ['Відгуки', '#reviews'], ['Запис', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'centered', 'title' => 'Онлайн і офлайн курси', 'subtitle' => 'IT, дизайн, мови, бізнес-навички.', 'cta_text' => 'Обрати курс', 'cta_url' => '#contact']],
                ['type' => 'cards', 'overrides' => ['section_title' => 'Популярні програми']],
                ['type' => 'features', 'overrides' => ['section_title' => 'Чому SkillUp']],
                ['type' => 'team', 'overrides' => ['section_title' => 'Викладачі']],
                ['type' => 'testimonials', 'overrides' => []],
                ['type' => 'faq', 'overrides' => []],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'medical' => [
            'label' => $t['landing_tpl_medical'] ?? 'Медична клініка',
            'desc' => $t['landing_tpl_medical_desc'] ?? 'Лікарі, послуги, запис',
            'icon' => 'fa-hospital',
            'theme' => 'ocean', 'color' => '#0284c7', 'icon_set' => 'health',
            'business_name' => $t['landing_tpl_medical_name'] ?? 'MediCare Clinic',
            'tagline' => $t['landing_tpl_medical_tag'] ?? 'Турбота про здоровʼя вашої родини',
            'nav_links' => $nav([['Послуги', '#services'], ['Лікарі', '#team'], ['Запис', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Сучасна клініка поруч', 'subtitle' => 'Діагностика, терапія, сімейна медицина.', 'cta_text' => 'Записатися', 'cta_url' => '#contact']],
                ['type' => 'trust', 'overrides' => []],
                ['type' => 'services', 'overrides' => ['section_title' => 'Медичні послуги']],
                ['type' => 'team', 'overrides' => ['section_title' => 'Наші лікарі']],
                ['type' => 'hours', 'overrides' => []],
                ['type' => 'faq', 'overrides' => []],
                ['type' => 'contact_bar', 'overrides' => ['phone' => '+380 44 300 00 00']],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'auto' => [
            'label' => $t['landing_tpl_auto'] ?? 'Автосервіс',
            'desc' => $t['landing_tpl_auto_desc'] ?? 'Ремонт, діагностика, шиномонтаж',
            'icon' => 'fa-car',
            'theme' => 'slate', 'color' => '#475569', 'icon_set' => 'business',
            'business_name' => $t['landing_tpl_auto_name'] ?? 'AutoMaster',
            'tagline' => $t['landing_tpl_auto_tag'] ?? 'Надійний сервіс для вашого авто',
            'nav_links' => $nav([['Послуги', '#services'], ['Ціни', '#pricing'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Ремонт будь-якої складності', 'subtitle' => 'Діагностика, ТО, кузовні роботи, шини.', 'cta_text' => 'Записатися', 'cta_url' => '#contact']],
                ['type' => 'icon_list', 'overrides' => ['section_title' => 'Що ми робимо']],
                ['type' => 'pricing', 'overrides' => ['variant' => 'compact', 'section_title' => 'Популярні послуги']],
                ['type' => 'gallery', 'overrides' => ['variant' => 'row', 'section_title' => 'Наш сервіс']],
                ['type' => 'testimonials', 'overrides' => []],
                ['type' => 'contact', 'overrides' => ['phone' => '+380 67 111 22 33']],
            ],
        ],
        'photographer' => [
            'label' => $t['landing_tpl_photographer'] ?? 'Фотограф',
            'desc' => $t['landing_tpl_photographer_desc'] ?? 'Портфоліо, пакети, бронювання',
            'icon' => 'fa-camera',
            'theme' => 'violet', 'color' => '#7c3aed', 'icon_set' => 'creative',
            'business_name' => $t['landing_tpl_photographer_name'] ?? 'Lens & Light',
            'tagline' => $t['landing_tpl_photographer_tag'] ?? 'Моменти, які хочеться зберегти',
            'nav_links' => $nav([['Портфоліо', '#gallery'], ['Пакети', '#pricing'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'minimal', 'title' => 'Фото та відео на будь-яку подію', 'subtitle' => 'Весілля, портрети, комерційні зйомки.', 'cta_text' => 'Забронювати дату', 'cta_url' => '#contact']],
                ['type' => 'gallery', 'overrides' => ['variant' => 'masonry', 'section_title' => 'Портфоліо']],
                ['type' => 'pricing', 'overrides' => ['section_title' => 'Пакети зйомок']],
                ['type' => 'about', 'overrides' => ['variant' => 'centered']],
                ['type' => 'testimonials', 'overrides' => ['variant' => 'quote']],
                ['type' => 'buttons', 'overrides' => ['variant' => 'center', 'items' => [['text' => 'Instagram', 'url' => '#', 'style' => 'outline'], ['text' => 'Замовити зйомку', 'url' => '#contact', 'style' => 'primary']]]],
                ['type' => 'contact', 'overrides' => ['variant' => 'minimal']],
            ],
        ],
        'bakery' => [
            'label' => $t['landing_tpl_bakery'] ?? 'Пекарня / кондитерська',
            'desc' => $t['landing_tpl_bakery_desc'] ?? 'Випічка, торти, меню',
            'icon' => 'fa-cake-candles',
            'theme' => 'sunset', 'color' => '#ea580c', 'icon_set' => 'shop',
            'business_name' => $t['landing_tpl_bakery_name'] ?? 'Sweet Crumb',
            'tagline' => $t['landing_tpl_bakery_tag'] ?? 'Свіжа випічка щодня',
            'nav_links' => $nav([['Меню', '#menu'], ['Галерея', '#gallery'], ['Замовити', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'centered', 'title' => 'Смак дитинства і аромат свіжості', 'subtitle' => 'Хліб, випічка, торти на замовлення.', 'cta_text' => 'Замовити торт', 'cta_url' => '#contact']],
                ['type' => 'menu', 'overrides' => ['section_title' => 'Меню дня']],
                ['type' => 'gallery', 'overrides' => ['variant' => 'grid']],
                ['type' => 'callout', 'overrides' => ['variant' => 'tip', 'text' => 'Приймаємо замовлення на торти за 3 дні.']],
                ['type' => 'hours', 'overrides' => []],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'real_estate' => [
            'label' => $t['landing_tpl_real_estate'] ?? 'Нерухомість',
            'desc' => $t['landing_tpl_real_estate_desc'] ?? 'Обʼєкти, агенти, консультація',
            'icon' => 'fa-house',
            'theme' => 'emerald', 'color' => '#059669', 'icon_set' => 'business',
            'business_name' => $t['landing_tpl_real_estate_name'] ?? 'HomeKey Realty',
            'tagline' => $t['landing_tpl_real_estate_tag'] ?? 'Знайдемо дім вашої мрії',
            'nav_links' => $nav([['Обʼєкти', '#gallery'], ['Послуги', '#services'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Купівля, продаж, оренда', 'subtitle' => 'Квартири, будинки, комерція по всій Україні.', 'cta_text' => 'Підібрати обʼєкт', 'cta_url' => '#contact']],
                ['type' => 'gallery', 'overrides' => ['section_title' => 'Актуальні пропозиції']],
                ['type' => 'services', 'overrides' => ['section_title' => 'Послуги агентства']],
                ['type' => 'team', 'overrides' => ['section_title' => 'Рієлтори']],
                ['type' => 'stats_bar', 'overrides' => []],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'cleaning' => [
            'label' => $t['landing_tpl_cleaning'] ?? 'Клінінг',
            'desc' => $t['landing_tpl_cleaning_desc'] ?? 'Прибирання офісів і квартир',
            'icon' => 'fa-broom',
            'theme' => 'teal', 'color' => '#0d9488', 'icon_set' => 'business',
            'business_name' => $t['landing_tpl_cleaning_name'] ?? 'Clean&Fresh',
            'tagline' => $t['landing_tpl_cleaning_tag'] ?? 'Ідеальна чистота без зусиль',
            'nav_links' => $nav([['Послуги', '#services'], ['Ціни', '#pricing'], ['Замовити', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Професійне прибирання', 'subtitle' => 'Квартири, офіси, після ремонту.', 'cta_text' => 'Замовити прибирання', 'cta_url' => '#contact']],
                ['type' => 'features', 'overrides' => []],
                ['type' => 'pricing', 'overrides' => ['section_title' => 'Тарифи']],
                ['type' => 'icon_list', 'overrides' => ['variant' => 'check', 'section_title' => 'Що входить у послугу']],
                ['type' => 'testimonials', 'overrides' => []],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'it_services' => [
            'label' => $t['landing_tpl_it'] ?? 'IT-послуги',
            'desc' => $t['landing_tpl_it_desc'] ?? 'Розробка, підтримка, хмара',
            'icon' => 'fa-laptop-code',
            'theme' => 'indigo', 'color' => '#4f46e5', 'icon_set' => 'tech',
            'business_name' => $t['landing_tpl_it_name'] ?? 'DevStack UA',
            'tagline' => $t['landing_tpl_it_tag'] ?? 'Цифрові рішення для бізнесу',
            'nav_links' => $nav([['Послуги', '#services'], ['Кейси', '#gallery'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Розробка сайтів і застосунків', 'subtitle' => 'Від ідеї до продакшну — під ключ.', 'cta_text' => 'Обговорити проєкт', 'cta_url' => '#contact']],
                ['type' => 'logos', 'overrides' => ['section_title' => 'Технології']],
                ['type' => 'services', 'overrides' => ['variant' => 'cards']],
                ['type' => 'comparison', 'overrides' => []],
                ['type' => 'testimonials', 'overrides' => []],
                ['type' => 'cta', 'overrides' => ['variant' => 'inline']],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'wedding' => [
            'label' => $t['landing_tpl_wedding'] ?? 'Весільне агентство',
            'desc' => $t['landing_tpl_wedding_desc'] ?? 'Організація свят, пакети',
            'icon' => 'fa-heart',
            'theme' => 'rose', 'color' => '#e11d48', 'icon_set' => 'creative',
            'business_name' => $t['landing_tpl_wedding_name'] ?? 'Forever Day',
            'tagline' => $t['landing_tpl_wedding_tag'] ?? 'Ваш ідеальний день — наша місія',
            'nav_links' => $nav([['Послуги', '#services'], ['Галерея', '#gallery'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'centered', 'title' => 'Весілля мрії без стресу', 'subtitle' => 'Декор, координація, підрядники — все під ключ.', 'cta_text' => 'Безкоштовна консультація', 'cta_url' => '#contact']],
                ['type' => 'gallery', 'overrides' => ['section_title' => 'Наші весілля']],
                ['type' => 'services', 'overrides' => ['section_title' => 'Пакети послуг']],
                ['type' => 'quote', 'overrides' => []],
                ['type' => 'testimonials', 'overrides' => []],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'veterinary' => [
            'label' => $t['landing_tpl_vet'] ?? 'Ветклініка',
            'desc' => $t['landing_tpl_vet_desc'] ?? 'Лікування тварин, вакцинація',
            'icon' => 'fa-paw',
            'theme' => 'lime', 'color' => '#65a30d', 'icon_set' => 'health',
            'business_name' => $t['landing_tpl_vet_name'] ?? 'PetCare Vet',
            'tagline' => $t['landing_tpl_vet_tag'] ?? 'Турбота про ваших улюбленців',
            'nav_links' => $nav([['Послуги', '#services'], ['Лікарі', '#team'], ['Запис', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Ветеринарна клініка 24/7', 'subtitle' => 'Діагностика, хірургія, стаціонар для тварин.', 'cta_text' => 'Записатися', 'cta_url' => '#contact']],
                ['type' => 'services', 'overrides' => ['section_title' => 'Послуги']],
                ['type' => 'team', 'overrides' => ['section_title' => 'Ветеринари']],
                ['type' => 'alert', 'overrides' => ['variant' => 'info', 'title' => 'Екстрена допомога', 'text' => 'Приймаємо екстрені випадки цілодобово.']],
                ['type' => 'hours', 'overrides' => []],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'coaching' => [
            'label' => $t['landing_tpl_coaching'] ?? 'Коучинг',
            'desc' => $t['landing_tpl_coaching_desc'] ?? 'Особистий розвиток, менторинг',
            'icon' => 'fa-user-tie',
            'theme' => 'violet', 'color' => '#7c3aed', 'icon_set' => 'business',
            'business_name' => $t['landing_tpl_coaching_name'] ?? 'Growth Mind Coach',
            'tagline' => $t['landing_tpl_coaching_tag'] ?? 'Розкрийте свій потенціал',
            'nav_links' => $nav([['Програми', '#services'], ['Про мене', '#about'], ['Сесія', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'minimal', 'title' => 'Коучинг для лідерів і підприємців', 'subtitle' => '1:1 сесії, групові програми, корпоративний коучинг.', 'cta_text' => 'Безкоштовна діагностика', 'cta_url' => '#contact']],
                ['type' => 'about', 'overrides' => ['variant' => 'split']],
                ['type' => 'cards', 'overrides' => ['section_title' => 'Формати роботи']],
                ['type' => 'testimonials', 'overrides' => []],
                ['type' => 'steps', 'overrides' => ['section_title' => 'Як проходить коучинг']],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'nonprofit' => [
            'label' => $t['landing_tpl_nonprofit'] ?? 'Благодійність',
            'desc' => $t['landing_tpl_nonprofit_desc'] ?? 'Місія, проєкти, донат',
            'icon' => 'fa-hand-holding-heart',
            'theme' => 'emerald', 'color' => '#059669', 'icon_set' => 'business',
            'business_name' => $t['landing_tpl_nonprofit_name'] ?? 'HelpUA Foundation',
            'tagline' => $t['landing_tpl_nonprofit_tag'] ?? 'Разом робимо більше',
            'nav_links' => $nav([['Місія', '#about'], ['Проєкти', '#gallery'], ['Допомогти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'centered', 'title' => 'Допомагаємо тим, хто потребує', 'subtitle' => 'Прозорі звіти, реальна допомога, волонтери по всій країні.', 'cta_text' => 'Підтримати', 'cta_url' => '#contact']],
                ['type' => 'stats_bar', 'overrides' => ['variant' => 'boxed']],
                ['type' => 'about', 'overrides' => []],
                ['type' => 'gallery', 'overrides' => ['section_title' => 'Наші проєкти']],
                ['type' => 'buttons', 'overrides' => ['variant' => 'center', 'items' => [['text' => 'Зробити донат', 'url' => '#contact', 'style' => 'primary'], ['text' => 'Стати волонтером', 'url' => '#contact', 'style' => 'outline']]]],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'barber' => [
            'label' => $t['landing_tpl_barber'] ?? 'Барбершоп',
            'desc' => $t['landing_tpl_barber_desc'] ?? 'Стрижки, борода, прайс',
            'icon' => 'fa-scissors',
            'theme' => 'midnight', 'color' => '#1e3a5f', 'icon_set' => 'business',
            'business_name' => $t['landing_tpl_barber_name'] ?? 'Black Blade Barbers',
            'tagline' => $t['landing_tpl_barber_tag'] ?? 'Стиль і майстерність',
            'nav_links' => $nav([['Послуги', '#menu'], ['Галерея', '#gallery'], ['Запис', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'minimal', 'title' => 'Класичний барбершоп', 'subtitle' => 'Стрижки, борода, догляд — для справжніх чоловіків.', 'cta_text' => 'Записатися', 'cta_url' => '#contact']],
                ['type' => 'menu', 'overrides' => ['section_title' => 'Прайс']],
                ['type' => 'gallery', 'overrides' => ['variant' => 'row']],
                ['type' => 'team', 'overrides' => ['section_title' => 'Барбери', 'variant' => 'list']],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'florist' => [
            'label' => $t['landing_tpl_florist'] ?? 'Квіткова студія',
            'desc' => $t['landing_tpl_florist_desc'] ?? 'Букети, доставка, події',
            'icon' => 'fa-seedling',
            'theme' => 'rose', 'color' => '#f43f5e', 'icon_set' => 'creative',
            'business_name' => $t['landing_tpl_florist_name'] ?? 'Bloom & Co',
            'tagline' => $t['landing_tpl_florist_tag'] ?? 'Квіти, що говорять замість слів',
            'nav_links' => $nav([['Букети', '#gallery'], ['Доставка', '#services'], ['Замовити', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'centered', 'title' => 'Свіжі квіти з доставкою', 'subtitle' => 'Букети на свято, весілля, щоденна радість.', 'cta_text' => 'Замовити букет', 'cta_url' => '#contact']],
                ['type' => 'gallery', 'overrides' => ['section_title' => 'Популярні букети']],
                ['type' => 'services', 'overrides' => ['section_title' => 'Послуги']],
                ['type' => 'callout', 'overrides' => ['variant' => 'promo', 'text' => 'Безкоштовна доставка від 1500 ₴ по місту.']],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'taxi' => [
            'label' => $t['landing_tpl_taxi'] ?? 'Таксі / трансфер',
            'desc' => $t['landing_tpl_taxi_desc'] ?? 'Поїздки, тарифи, застосунок',
            'icon' => 'fa-taxi',
            'theme' => 'gold', 'color' => '#ca8a04', 'icon_set' => 'travel',
            'business_name' => $t['landing_tpl_taxi_name'] ?? 'CityRide',
            'tagline' => $t['landing_tpl_taxi_tag'] ?? 'Швидко, безпечно, за фіксованою ціною',
            'nav_links' => $nav([['Тарифи', '#pricing'], ['Застосунок', '#app'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Таксі та трансфер 24/7', 'subtitle' => 'Місто, аеропорт, міжміські поїздки.', 'cta_text' => 'Викликати авто', 'cta_url' => '#contact']],
                ['type' => 'features', 'overrides' => ['section_title' => 'Переваги CityRide']],
                ['type' => 'pricing', 'overrides' => ['variant' => 'compact', 'section_title' => 'Тарифи']],
                ['type' => 'app_cta', 'overrides' => []],
                ['type' => 'contact_bar', 'overrides' => ['phone' => '+380 44 500 50 50']],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'music_studio' => [
            'label' => $t['landing_tpl_music'] ?? 'Музична студія',
            'desc' => $t['landing_tpl_music_desc'] ?? 'Запис, продакшн, уроки',
            'icon' => 'fa-music',
            'theme' => 'violet', 'color' => '#7c3aed', 'icon_set' => 'creative',
            'business_name' => $t['landing_tpl_music_name'] ?? 'SoundLab Studio',
            'tagline' => $t['landing_tpl_music_tag'] ?? 'Записуйте музику професійно',
            'nav_links' => $nav([['Послуги', '#services'], ['Студія', '#gallery'], ['Бронь', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'centered', 'title' => 'Студія звукозапису', 'subtitle' => 'Вокал, інструменти, зведення, мастеринг.', 'cta_text' => 'Забронювати час', 'cta_url' => '#contact']],
                ['type' => 'services', 'overrides' => []],
                ['type' => 'gallery', 'overrides' => ['section_title' => 'Наша студія']],
                ['type' => 'pricing', 'overrides' => ['section_title' => 'Тарифи на годину']],
                ['type' => 'testimonials', 'overrides' => []],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'yoga' => [
            'label' => $t['landing_tpl_yoga'] ?? 'Йога / велнес',
            'desc' => $t['landing_tpl_yoga_desc'] ?? 'Заняття, розклад, абонементи',
            'icon' => 'fa-spa',
            'theme' => 'teal', 'color' => '#0d9488', 'icon_set' => 'health',
            'business_name' => $t['landing_tpl_yoga_name'] ?? 'ZenFlow Yoga',
            'tagline' => $t['landing_tpl_yoga_tag'] ?? 'Баланс тіла і розуму',
            'nav_links' => $nav([['Заняття', '#services'], ['Розклад', '#hours'], ['Запис', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'centered', 'title' => 'Йога для кожного рівня', 'subtitle' => 'Групові та індивідуальні заняття, медитація.', 'cta_text' => 'Пробне заняття', 'cta_url' => '#contact']],
                ['type' => 'features', 'overrides' => []],
                ['type' => 'hours', 'overrides' => ['title' => 'Розклад занять']],
                ['type' => 'pricing', 'overrides' => ['section_title' => 'Абонементи']],
                ['type' => 'team', 'overrides' => ['section_title' => 'Інструктори']],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'pharmacy' => [
            'label' => $t['landing_tpl_pharmacy'] ?? 'Аптека',
            'desc' => $t['landing_tpl_pharmacy_desc'] ?? 'Ліки, консультація, доставка',
            'icon' => 'fa-pills',
            'theme' => 'ocean', 'color' => '#0284c7', 'icon_set' => 'health',
            'business_name' => $t['landing_tpl_pharmacy_name'] ?? 'HealthPlus Pharmacy',
            'tagline' => $t['landing_tpl_pharmacy_tag'] ?? 'Турбота про ваше здоровʼя поруч',
            'nav_links' => $nav([['Послуги', '#services'], ['Години', '#hours'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Аптека з доставкою', 'subtitle' => 'Ліки, вітаміни, консультація фармацевта.', 'cta_text' => 'Замовити', 'cta_url' => '#contact']],
                ['type' => 'trust', 'overrides' => []],
                ['type' => 'services', 'overrides' => ['section_title' => 'Послуги аптеки']],
                ['type' => 'hours', 'overrides' => []],
                ['type' => 'alert', 'overrides' => ['variant' => 'warning', 'title' => 'Важливо', 'text' => 'Рецептурні препарати — за наявності рецепта.']],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
        'accounting' => [
            'label' => $t['landing_tpl_accounting'] ?? 'Бухгалтерія',
            'desc' => $t['landing_tpl_accounting_desc'] ?? 'Облік, звітність, консультації',
            'icon' => 'fa-calculator',
            'theme' => 'slate', 'color' => '#475569', 'icon_set' => 'business',
            'business_name' => $t['landing_tpl_accounting_name'] ?? 'FinClear Accounting',
            'tagline' => $t['landing_tpl_accounting_tag'] ?? 'Чистий облік — спокійний бізнес',
            'nav_links' => $nav([['Послуги', '#services'], ['Тарифи', '#pricing'], ['Контакти', '#contact']]),
            'blocks' => [
                ['type' => 'hero', 'overrides' => ['variant' => 'split', 'title' => 'Бухгалтерія для ФОП і ТОВ', 'subtitle' => 'Звітність, податки, payroll — без помилок.', 'cta_text' => 'Консультація', 'cta_url' => '#contact']],
                ['type' => 'icon_list', 'overrides' => ['section_title' => 'Що ми ведемо']],
                ['type' => 'pricing', 'overrides' => ['section_title' => 'Тарифи обслуговування']],
                ['type' => 'faq', 'overrides' => []],
                ['type' => 'contact', 'overrides' => []],
            ],
        ],
    ];
}