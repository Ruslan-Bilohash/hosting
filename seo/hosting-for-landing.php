<?php
declare(strict_types=1);

/**
 * Alias for Business Landing (catalog slug: lending).
 * Public name "Business Landing" → SEO file hosting-for-landing.php
 */
require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/seo-app-page.php';

hs_seo_render_app_page('lending');
