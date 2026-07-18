<?php
declare(strict_types=1);

/**
 * Homepage control-panel block: multilingual feature list + WebP screenshots + SEO article links.
 *
 * @param array<string, mixed> $t
 */
function hs_home_panel_feature_items(array $t): array
{
    return [
        [
            'key' => 'landing',
            'icon' => 'fa-paintbrush',
            'title' => (string) ($t['panel_feat_landing'] ?? 'Landing page builder'),
            'desc' => (string) ($t['panel_feat_landing_desc'] ?? ''),
            'shot' => 'builder_website_main.webp',
            'seo' => 'seo/',
        ],
        [
            'key' => 'installer',
            'icon' => 'fa-box-open',
            'title' => (string) ($t['panel_feat_installer'] ?? 'CMS installer'),
            'desc' => (string) ($t['panel_feat_installer_desc'] ?? ''),
            'shot' => 'auto_installer.webp',
            'seo' => 'seo/',
        ],
        [
            'key' => 'files',
            'icon' => 'fa-folder-open',
            'title' => (string) ($t['panel_feat_files'] ?? 'File manager'),
            'desc' => (string) ($t['panel_feat_files_desc'] ?? ''),
            'shot' => 'file_manager.webp',
            'seo' => '',
        ],
        [
            'key' => 'domains',
            'icon' => 'fa-globe',
            'title' => (string) ($t['panel_feat_domains'] ?? 'Domains & DNS'),
            'desc' => (string) ($t['panel_feat_domains_desc'] ?? ''),
            'shot' => 'domains.webp',
            'seo' => 'domain',
        ],
        [
            'key' => 'security',
            'icon' => 'fa-shield-halved',
            'title' => (string) ($t['panel_feat_security'] ?? 'Security & SSL'),
            'desc' => (string) ($t['panel_feat_security_desc'] ?? ''),
            'shot' => 'security.webp',
            'seo' => '',
        ],
        [
            'key' => 'backup',
            'icon' => 'fa-cloud-arrow-up',
            'title' => (string) ($t['panel_feat_backup'] ?? 'Backups'),
            'desc' => (string) ($t['panel_feat_backup_desc'] ?? ''),
            'shot' => 'backups.webp',
            'seo' => '',
        ],
        [
            'key' => 'db',
            'icon' => 'fa-database',
            'title' => (string) ($t['panel_feat_db'] ?? 'MySQL & phpMyAdmin'),
            'desc' => (string) ($t['panel_feat_db_desc'] ?? ''),
            'shot' => 'databases_management.webp',
            'seo' => '',
        ],
        [
            'key' => 'ssh',
            'icon' => 'fa-terminal',
            'title' => (string) ($t['panel_feat_ssh'] ?? 'SSH, PHP & Git'),
            'desc' => (string) ($t['panel_feat_ssh_desc'] ?? ''),
            'shot' => 'ssh_access.webp',
            'seo' => '',
        ],
        [
            'key' => 'perf',
            'icon' => 'fa-gauge-high',
            'title' => (string) ($t['panel_feat_perf'] ?? 'Speed & cache'),
            'desc' => (string) ($t['panel_feat_perf_desc'] ?? ''),
            'shot' => 'performance.webp',
            'seo' => '',
        ],
        [
            'key' => 'email',
            'icon' => 'fa-envelope',
            'title' => (string) ($t['panel_feat_email'] ?? 'Email & analytics'),
            'desc' => (string) ($t['panel_feat_email_desc'] ?? ''),
            'shot' => 'manager_email.webp',
            'seo' => '',
        ],
        [
            'key' => 'support',
            'icon' => 'fa-headset',
            'title' => (string) ($t['panel_feat_support'] ?? 'AI-assisted support'),
            'desc' => (string) ($t['panel_feat_support_desc'] ?? ''),
            'shot' => 'support.webp',
            'seo' => '',
        ],
        [
            'key' => 'i18n',
            'icon' => 'fa-language',
            'title' => (string) ($t['panel_feat_i18n'] ?? $t['feat_i18n'] ?? 'Multilingual'),
            'desc' => (string) ($t['panel_feat_i18n_desc'] ?? $t['feat_i18n_desc'] ?? ''),
            'shot' => 'dashboard_client.webp',
            'seo' => 'seo/',
        ],
    ];
}

/**
 * SEO guide cards with screenshots (homepage panel block).
 *
 * @param array<string, mixed> $t
 * @return list<array{slug:string,title:string,desc:string,href:string,shot:string}>
 */
function hs_home_panel_seo_guides(array $t): array
{
    $items = [
        ['slug' => 'shop', 'file' => 'hosting-for-shop.php', 'shot' => 'auto_installer.webp', 'key' => 'panel_seo_shop'],
        ['slug' => 'booking', 'file' => 'hosting-for-booking.php', 'shot' => 'website.webp', 'key' => 'panel_seo_booking'],
        ['slug' => 'wordpress', 'file' => 'hosting-for-wordpress.php', 'shot' => 'install_wordpress.webp', 'key' => 'panel_seo_wordpress'],
        ['slug' => 'ai', 'file' => 'hosting-for-ai.php', 'shot' => 'chat_gpt_api.webp', 'key' => 'panel_seo_ai'],
        ['slug' => 'today', 'file' => 'hosting-for-today.php', 'shot' => 'analytics.webp', 'key' => 'panel_seo_today'],
        ['slug' => 'faktura', 'file' => 'hosting-for-faktura.php', 'shot' => 'invoices.webp', 'key' => 'panel_seo_faktura'],
    ];
    $out = [];
    foreach ($items as $it) {
        $out[] = [
            'slug' => $it['slug'],
            'title' => (string) ($t[$it['key'] . '_title'] ?? ucfirst($it['slug'])),
            'desc' => (string) ($t[$it['key'] . '_desc'] ?? ''),
            'href' => hs_url('seo/' . $it['file']),
            'shot' => $it['shot'],
        ];
    }

    return $out;
}

/**
 * Resolve screenshot URL. Prefer exact extension; if $preferWebp, try .webp first.
 */
function hs_home_shot_url(string $file, bool $preferWebp = true): string
{
    $file = basename(str_replace(['..', '\\'], ['', '/'], $file));
    $root = dirname(__DIR__);
    $base = preg_replace('/\.(jpe?g|png|webp)$/i', '', $file) ?? $file;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION) ?: 'webp');

    $names = [];
    if ($preferWebp) {
        $names[] = $base . '.webp';
        if ($ext !== 'webp') {
            $names[] = $file;
        }
        $names[] = $base . '.jpg';
        $names[] = $base . '.png';
    } else {
        // Exact / fallback chain without forcing WebP (for <img> JPEG fallback)
        $names[] = $file;
        if ($ext !== 'jpg' && $ext !== 'jpeg') {
            $names[] = $base . '.jpg';
        }
        $names[] = $base . '.png';
        $names[] = $base . '.webp';
    }
    $names = array_values(array_unique($names));

    $dirs = ['assets/screenshots/', 'screenshot/', 'assets/'];
    foreach ($names as $name) {
        foreach ($dirs as $dir) {
            $rel = $dir . $name;
            $abs = $root . '/' . $rel;
            if (is_file($abs)) {
                $v = (string) (@filemtime($abs) ?: time());
                $url = hs_url($rel);

                return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . rawurlencode($v);
            }
        }
    }

    return hs_url('assets/screenshots/' . $base . ($preferWebp ? '.webp' : '.jpg'));
}

/**
 * Build <picture> tag: WebP primary + JPEG fallback for older browsers.
 */
function hs_home_shot_picture(string $file, string $alt, int $w = 960, int $h = 540, string $class = ''): string
{
    $file = basename(str_replace(['..', '\\'], ['', '/'], $file));
    $base = preg_replace('/\.(jpe?g|png|webp)$/i', '', $file) ?? $file;
    $webpUrl = hs_home_shot_url($base . '.webp', true);
    $jpgUrl = hs_home_shot_url($base . '.jpg', false);
    $cls = $class !== '' ? ' class="' . hs_h($class) . '"' : '';

    return '<picture>'
        . '<source srcset="' . hs_h($webpUrl) . '" type="image/webp">'
        . '<img src="' . hs_h($jpgUrl) . '" alt="' . hs_h($alt) . '" width="' . $w . '" height="' . $h . '" loading="lazy" decoding="async"' . $cls . '>'
        . '</picture>';
}

/**
 * @param array<string, mixed> $t
 */
function hs_render_home_panel_block(array $t, string $lang): string
{
    $langs = ['EN', 'NO', 'UK', 'LT', 'PL', 'SV'];
    $features = hs_home_panel_feature_items($t);
    $guides = hs_home_panel_seo_guides($t);
    $zoom = hs_h((string) ($t['shot_zoom_action'] ?? 'enlarge'));

    $html = '<section class="hs-home-section hs-home-panel-block" id="panel" aria-labelledby="panel-block-title">';
    $html .= '<div class="hs-home-wrap">';
    $html .= '<header class="hs-home-section-head">';
    $html .= '<p class="hs-home-kicker"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i> '
        . hs_h((string) ($t['panel_badge'] ?? 'Client panel')) . '</p>';
    $html .= '<h2 id="panel-block-title">' . hs_h((string) ($t['panel_title'] ?? 'Control panel')) . '</h2>';
    $html .= '<p class="hs-home-panel-lead">' . hs_h((string) ($t['panel_lead'] ?? '')) . '</p>';
    $html .= '<p class="hs-home-panel-desc">' . hs_h((string) ($t['panel_desc'] ?? '')) . '</p>';
    $html .= '<div class="hs-home-panel-langs" role="list" aria-label="' . hs_h((string) ($t['panel_langs_label'] ?? 'Languages')) . '">';
    foreach ($langs as $code) {
        $html .= '<span class="hs-home-panel-lang" role="listitem">' . hs_h($code) . '</span>';
    }
    $html .= '</div>';
    $html .= '<p class="hs-home-panel-langs-note">' . hs_h((string) ($t['panel_langs_note'] ?? $t['feat_i18n_desc'] ?? '')) . '</p>';
    $html .= '</header>';

    // Hero screenshots — lightbox (no new tab)
    $html .= '<div class="hs-home-panel-shots">';
    foreach (
        [
            ['dashboard.webp', (string) ($t['panel_shot_dash'] ?? 'Dashboard')],
            ['dashboard_client.webp', (string) ($t['panel_shot_client'] ?? 'Client panel')],
        ] as [$shotFile, $cap]
    ) {
        $html .= '<figure class="hs-home-panel-shot">';
        $html .= '<button type="button" class="hs-home-panel-shot-link hs-shot-zoom-trigger" data-hs-shot-zoom'
            . ' aria-label="' . hs_h($cap . ' — ' . $zoom) . '">';
        $html .= hs_home_shot_picture($shotFile, $cap, 960, 540, 'hs-home-panel-shot-img');
        $html .= '<span class="hs-shot-zoom-hint" aria-hidden="true"><i class="fa-solid fa-magnifying-glass-plus"></i></span>';
        $html .= '</button><figcaption>' . hs_h($cap) . '</figcaption></figure>';
    }
    $html .= '</div>';

    // Feature grid
    $html .= '<div class="hs-home-panel-feat-grid">';
    foreach ($features as $f) {
        $shotFile = (string) $f['shot'];
        $html .= '<article class="hs-home-panel-feat">';
        $html .= '<div class="hs-home-panel-feat-top">';
        $html .= '<span class="hs-home-panel-feat-icon"><i class="fa-solid ' . hs_h((string) $f['icon']) . '" aria-hidden="true"></i></span>';
        $html .= '<h3>' . hs_h((string) $f['title']) . '</h3>';
        $html .= '</div>';
        $html .= '<p>' . hs_h((string) $f['desc']) . '</p>';
        $html .= '<button type="button" class="hs-home-panel-feat-shot hs-shot-zoom-trigger" data-hs-shot-zoom'
            . ' aria-label="' . hs_h((string) $f['title'] . ' — ' . $zoom) . '">';
        $html .= hs_home_shot_picture($shotFile, (string) $f['title'], 480, 270);
        $html .= '<span class="hs-shot-zoom-hint" aria-hidden="true"><i class="fa-solid fa-magnifying-glass-plus"></i></span>';
        $html .= '<span class="hs-home-panel-feat-shot-label">' . hs_h((string) ($t['panel_view_shot'] ?? 'Screenshot')) . '</span></button>';
        if (($f['seo'] ?? '') !== '') {
            $html .= '<a class="hs-home-panel-feat-seo" href="' . hs_h(hs_url((string) $f['seo'])) . '">'
                . hs_h((string) ($t['panel_seo_more'] ?? 'SEO guide →')) . '</a>';
        }
        $html .= '</article>';
    }
    $html .= '</div>';

    // SEO articles with screenshots
    $html .= '<div class="hs-home-panel-seo">';
    $html .= '<header class="hs-home-section-head hs-home-section-head--sm">';
    $html .= '<h3>' . hs_h((string) ($t['panel_seo_title'] ?? 'CMS guides for SEO')) . '</h3>';
    $html .= '<p>' . hs_h((string) ($t['panel_seo_lead'] ?? '')) . '</p>';
    $html .= '</header>';
    $html .= '<div class="hs-home-panel-seo-grid">';
    foreach ($guides as $g) {
        $shotFile = $g['shot'];
        $shot = hs_home_shot_url($shotFile);
        $html .= '<a class="hs-home-panel-seo-card" href="' . hs_h($g['href']) . '">';
        $html .= '<span class="hs-home-panel-seo-thumb">' . hs_home_shot_picture($shotFile, (string) $g['title'], 400, 225) . '</span>';
        $html .= '<strong>' . hs_h($g['title']) . '</strong>';
        $html .= '<span>' . hs_h($g['desc']) . '</span>';
        $html .= '<em>' . hs_h((string) ($t['panel_seo_read'] ?? 'Read guide →')) . '</em>';
        $html .= '</a>';
    }
    $html .= '</div>';
    $html .= '<p class="hs-home-cms-note"><a href="' . hs_h(hs_url('seo/')) . '">'
        . hs_h((string) ($t['seo_internal_all'] ?? 'All CMS hosting guides →')) . '</a></p>';
    $html .= '</div>';

    $html .= '<div class="hs-home-cta" style="margin-top:1.5rem;justify-content:center">';
    $html .= '<a class="hs-btn hs-btn-primary" href="' . hs_h(hs_url('register.php')) . '">'
        . hs_h((string) ($t['panel_cta'] ?? 'Try the panel')) . '</a>';
    $html .= '<a class="hs-btn hs-btn-ghost" href="' . hs_h(hs_url('login.php')) . '">'
        . hs_h((string) ($t['nav_login'] ?? 'Login')) . '</a>';
    $html .= '</div>';

    $html .= '</div></section>';

    return $html;
}
