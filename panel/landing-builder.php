<?php
declare(strict_types=1);

$panel_active = 'landing-builder';
$GLOBALS['panel_landing_mode'] = true;
$GLOBALS['panel_landing_focus'] = true;
$GLOBALS['panel_hide_tip'] = true;
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/landing-builder.php';

$page_title = $t['landing_builder_title'] ?? 'Landing page builder';
$panel_tip_key = 'websites';

$error = '';
$success = '';
$userId = (string) ($user['id'] ?? '');
$stored = is_array($hs_user_settings['landing_builder'] ?? null) ? $hs_user_settings['landing_builder'] : [];
$data = hs_landing_builder_normalize(array_merge(hs_landing_builder_defaults($user, $t), $stored), $user, $t);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
    } elseif (isset($_POST['save_landing'])) {
        $data = hs_landing_builder_from_post($_POST, $user, $t);
        if (hs_user_settings_save($userId, ['landing_builder' => $data])) {
            $hs_user_settings['landing_builder'] = $data;
            $success = $t['landing_saved'] ?? 'Draft saved';
        } else {
            $error = $t['landing_error_save'] ?? 'Could not save';
        }
    } elseif (isset($_POST['publish_landing'])) {
        $data = hs_landing_builder_from_post($_POST, $user, $t);
        $res = hs_landing_publish($user, $data);
        if ($res['ok']) {
            $data['published_at'] = gmdate('c');
            $data['published_url'] = (string) ($res['url'] ?? '');
            hs_user_settings_save($userId, ['landing_builder' => $data]);
            $hs_user_settings['landing_builder'] = $data;
            $success = ($t['landing_published'] ?? 'Published!') . ' ' . ($res['path'] ?? '');
        } else {
            $error = $t['landing_error_publish'] ?? 'Publish failed';
        }
    }
}

$previewUrl = (string) ($data['published_url'] ?? '');
if ($previewUrl === '' && (string) ($data['published_at'] ?? '') !== '') {
    $previewUrl = hs_landing_public_url($user);
}
$editorCfg = hs_landing_editor_config($data, $user, $t);
$cfgJson = json_encode($editorCfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
$blocksJson = json_encode($data['blocks'], JSON_UNESCAPED_UNICODE);
$navJson = json_encode($data['nav_links'], JSON_UNESCAPED_UNICODE);
$publishRel = 'public_html/' . hs_install_default_base($user) . '/index.html';
$landingTips = [
    ['icon' => 'fa-plus', 'text' => $t['landing_tip_widgets'] ?? 'Click a widget in the left panel to add it to the page.'],
    ['icon' => 'fa-sitemap', 'text' => $t['landing_tip_structure'] ?? 'Open Structure to see all blocks — tap one to edit.'],
    ['icon' => 'fa-desktop', 'text' => $t['landing_tip_devices'] ?? 'Switch desktop, tablet and mobile preview above the page canvas.'],
    ['icon' => 'fa-floppy-disk', 'text' => $t['landing_tip_save_publish'] ?? 'Save keeps a draft; Publish updates index.html in your site folder.'],
    ['icon' => 'fa-images', 'text' => $t['landing_tip_gallery'] ?? 'In Gallery blocks, upload or pick photos instead of color placeholders.'],
    ['icon' => 'fa-arrows-up-down', 'text' => $t['landing_tip_blocks_order'] ?? 'Drag blocks by the handle or use ↑↓; hide a block without deleting it.'],
    ['icon' => 'fa-mobile-screen', 'text' => $t['landing_tip_mobile_dock'] ?? 'On mobile, use the bottom bar to switch widgets, structure, preview and settings.'],
];
$landingSettings = hs_landing_settings_sections($t);

ob_start();
?>
<?php if ($success !== ''): ?><div class="elb-toast elb-toast-ok" data-elb-toast><?= hs_h($success) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="elb-toast elb-toast-err" data-elb-toast><?= hs_h($error) ?></div><?php endif; ?>

<div class="elb" data-hs-landing-builder data-elb-builder>
  <header class="elb-topbar">
    <div class="elb-topbar-group elb-topbar-left">
      <a href="<?= hs_h(hs_url(hs_panel_path(''))) ?>" class="elb-topbar-btn" title="<?= hs_h($t['landing_back_panel'] ?? 'Back to panel') ?>"><i class="fa-solid fa-arrow-left"></i></a>
      <span class="elb-brand"><i class="fa-solid fa-layer-group"></i> <?= hs_h($t['landing_elb_title'] ?? 'Page Builder') ?></span>
    </div>

    <div class="elb-topbar-group elb-topbar-right">
      <button type="button" class="elb-topbar-btn" data-elb-tips-open title="<?= hs_h($t['landing_tips_help_btn'] ?? 'Tips') ?>"><i class="fa-solid fa-circle-question"></i></button>
      <?php if ($previewUrl !== ''): ?>
      <a href="<?= hs_h($previewUrl) ?>" target="_blank" rel="noopener" class="elb-topbar-btn" title="<?= hs_h($t['landing_view_live'] ?? 'View live') ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
      <?php endif; ?>
      <button type="button" class="elb-topbar-btn" data-elb-toggle="left" title="<?= hs_h($t['landing_panel_left'] ?? 'Panel') ?>"><i class="fa-solid fa-bars"></i></button>
      <button type="button" class="elb-topbar-btn" data-elb-toggle="right" title="<?= hs_h($t['landing_panel_right'] ?? 'Edit') ?>"><i class="fa-solid fa-sliders"></i></button>
      <button type="submit" form="landing-form" name="save_landing" value="1" class="elb-topbar-btn elb-topbar-save"><i class="fa-solid fa-floppy-disk"></i><span><?= hs_h($t['landing_save'] ?? 'Save') ?></span></button>
      <button type="submit" form="landing-form" name="publish_landing" value="1" class="elb-topbar-btn elb-topbar-publish"><i class="fa-solid fa-rocket"></i><span><?= hs_h($t['landing_publish'] ?? 'Publish') ?></span></button>
    </div>
  </header>

  <div class="elb-workspace">
    <aside class="elb-panel elb-panel-left is-open" data-elb-panel="left">
      <header class="elb-panel-left-head">
        <span><?= hs_h($t['landing_panel_left_title'] ?? 'Library') ?></span>
        <button type="button" class="elb-panel-close" data-elb-close="left" aria-label="<?= hs_h($t['landing_close'] ?? 'Close') ?>"><i class="fa-solid fa-chevron-left"></i></button>
      </header>
      <div class="elb-panel-tabs">
        <button type="button" class="elb-ptab is-active" data-elb-ptab="elements" title="<?= hs_h($t['landing_tab_elements'] ?? 'Elements') ?>"><i class="fa-solid fa-plus"></i><span><?= hs_h($t['landing_tab_elements'] ?? 'Elements') ?></span></button>
        <button type="button" class="elb-ptab" data-elb-ptab="settings" title="<?= hs_h($t['landing_tab_settings'] ?? 'Settings') ?>"><i class="fa-solid fa-gear"></i><span><?= hs_h($t['landing_tab_settings'] ?? 'Settings') ?></span></button>
        <button type="button" class="elb-ptab" data-elb-ptab="structure" title="<?= hs_h($t['landing_tab_structure'] ?? 'Structure') ?>"><i class="fa-solid fa-sitemap"></i><span><?= hs_h($t['landing_tab_structure'] ?? 'Structure') ?></span></button>
      </div>
      <div class="elb-panel-scroll">
        <div class="elb-ppane is-active" data-elb-ppane="elements">
          <p class="elb-pane-hint"><?= hs_h($t['landing_elements_hint'] ?? 'Click a widget to add it to the page.') ?></p>
          <div class="elb-templates-section" data-elb-templates></div>
          <div class="elb-widget-search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" class="elb-widget-search" data-elb-widget-search placeholder="<?= hs_h($t['landing_widget_search'] ?? 'Search widgets…') ?>" autocomplete="off">
          </div>
          <div class="hs-landing-block-palette" data-landing-block-palette></div>
        </div>
        <div class="elb-ppane" data-elb-ppane="settings">
          <form method="post" id="landing-form" data-landing-editor autocomplete="off">
            <?= hs_csrf_field() ?>
            <input type="hidden" name="blocks_json" id="landing-blocks-json" value="<?= hs_h($blocksJson) ?>">
            <input type="hidden" name="nav_json" id="landing-nav-json" value="<?= hs_h($navJson) ?>">
            <input type="hidden" name="published_at" value="<?= hs_h((string) ($data['published_at'] ?? '')) ?>">
            <input type="hidden" name="published_url" value="<?= hs_h((string) ($data['published_url'] ?? '')) ?>">

            <p class="elb-pane-hint elb-settings-acc-hint"><i class="fa-solid fa-circle-info"></i> <?= hs_h($t['landing_settings_accordion_hint'] ?? 'One section open at a time — tap a header to switch.') ?></p>
            <div class="elb-spoilers" data-landing-accordions>
              <?php foreach ($landingSettings as $si => $item): ?>
              <article class="elb-spoiler<?= $si === 0 ? ' is-open' : '' ?>" data-landing-spoiler="<?= hs_h($item['id']) ?>">
                <button type="button" class="elb-spoiler-head" data-landing-spoiler-toggle aria-expanded="<?= $si === 0 ? 'true' : 'false' ?>">
                  <span class="elb-spoiler-icon"><i class="fa-solid <?= hs_h($item['icon']) ?>"></i></span>
                  <span class="elb-spoiler-meta">
                    <span class="elb-spoiler-title"><?= hs_h($item['label']) ?></span>
                    <?php if (($item['hint'] ?? '') !== ''): ?>
                    <span class="elb-spoiler-preview"><?= hs_h($item['hint']) ?></span>
                    <?php endif; ?>
                  </span>
                  <i class="fa-solid fa-chevron-down elb-spoiler-chevron" aria-hidden="true"></i>
                </button>
                <div class="elb-spoiler-body">
                  <?php if ($item['id'] === 'brand'): ?>
                  <div class="elb-fields-grid">
                    <?= hs_landing_panel_field(
                        $t['landing_business_name'] ?? 'Business name',
                        '<input type="text" name="business_name" value="' . hs_h((string) $data['business_name']) . '" data-landing-sync>',
                        $t['landing_hint_business_name'] ?? ''
                    ) ?>
                    <?= hs_landing_panel_field(
                        $t['landing_tagline'] ?? 'Tagline',
                        '<input type="text" name="tagline" value="' . hs_h((string) $data['tagline']) . '" data-landing-sync>',
                        $t['landing_hint_tagline'] ?? ''
                    ) ?>
                  </div>
                  <?= hs_landing_panel_field(
                      $t['landing_logo_icon'] ?? 'Logo icon',
                      hs_landing_logo_icon_picker_html((string) ($data['logo_icon'] ?? ''), $t, (string) ($data['icon_style'] ?? 'solid')),
                      $t['landing_hint_logo_icon'] ?? ''
                  ) ?>
                  <?php elseif ($item['id'] === 'theme'): ?>
                  <div class="elb-fields-stack">
                    <div class="elb-field">
                      <label class="elb-field-label"><?= hs_h($t['landing_sec_theme'] ?? 'Color theme') ?></label>
                      <p class="elb-field-hint"><?= hs_h($t['landing_hint_theme_pick'] ?? 'Accent color for buttons, links and highlights.') ?></p>
                      <div class="hs-landing-theme-grid" data-landing-themes>
                        <?php foreach (hs_landing_themes($t) as $key => $theme): ?>
                        <label class="hs-landing-theme-chip<?= ($data['theme'] ?? '') === $key ? ' is-active' : '' ?>">
                          <input type="radio" name="theme" value="<?= hs_h($key) ?>" <?= ($data['theme'] ?? '') === $key ? 'checked' : '' ?> data-landing-sync>
                          <span class="hs-landing-theme-swatch" style="background:<?= hs_h($theme['color']) ?>"></span>
                          <span><?= hs_h($theme['label']) ?></span>
                        </label>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <?= hs_landing_panel_field(
                        $t['landing_color_custom'] ?? 'Custom accent',
                        '<input type="color" name="color" value="' . hs_h((string) $data['color']) . '" data-landing-sync>',
                        $t['landing_hint_color_custom'] ?? 'Overrides the preset theme color.'
                    ) ?>
                    <?= hs_landing_panel_field(
                        $t['landing_gallery_palette_label'] ?? 'Gallery colors',
                        '<select name="gallery_palette" data-landing-sync>'
                        . implode('', array_map(static function (string $pk, array $pal) use ($data): string {
                            $sel = ($data['gallery_palette'] ?? '') === $pk ? ' selected' : '';
                            return '<option value="' . hs_h($pk) . '"' . $sel . '>' . hs_h($pal['label']) . '</option>';
                        }, array_keys(hs_landing_gallery_palettes($t)), hs_landing_gallery_palettes($t)))
                        . '</select>',
                        $t['landing_hint_gallery_palette'] ?? ''
                    ) ?>
                  </div>
                  <?php elseif ($item['id'] === 'icons'): ?>
                  <div class="elb-fields-stack">
                    <div class="elb-field">
                      <label class="elb-field-label"><?= hs_h($t['landing_icon_set_label'] ?? 'Icon set') ?></label>
                      <p class="elb-field-hint"><?= hs_h($t['landing_hint_icon_set'] ?? 'Style of icons in feature blocks.') ?></p>
                      <div class="hs-landing-icon-set-grid">
                        <?php foreach (hs_landing_icon_sets($t) as $sk => $set): ?>
                        <label class="hs-landing-icon-set-chip<?= ($data['icon_set'] ?? '') === $sk ? ' is-active' : '' ?>">
                          <input type="radio" name="icon_set" value="<?= hs_h($sk) ?>" <?= ($data['icon_set'] ?? '') === $sk ? 'checked' : '' ?> data-landing-sync>
                          <span class="hs-landing-icon-set-preview">
                            <?php foreach (array_slice($set['icons'], 0, 3) as $ic): ?>
                            <i class="fa-solid <?= hs_h($ic) ?>"></i>
                            <?php endforeach; ?>
                          </span>
                          <span><?= hs_h($set['label']) ?></span>
                        </label>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <div class="elb-field">
                      <label class="elb-field-label"><?= hs_h($t['landing_icon_style_label'] ?? 'Icon style') ?></label>
                      <p class="elb-field-hint"><?= hs_h($t['landing_hint_icon_style'] ?? 'Filled or outline icons.') ?></p>
                      <div class="hs-landing-style-row">
                        <?php foreach (hs_landing_icon_styles($t) as $stk => $stl): ?>
                        <label class="hs-landing-style-chip<?= ($data['icon_style'] ?? '') === $stk ? ' is-active' : '' ?>">
                          <input type="radio" name="icon_style" value="<?= hs_h($stk) ?>" <?= ($data['icon_style'] ?? '') === $stk ? 'checked' : '' ?> data-landing-sync>
                          <i class="<?= $stk === 'regular' ? 'fa-regular' : 'fa-solid' ?> fa-star"></i>
                          <?= hs_h($stl) ?>
                        </label>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-landing-apply-icons><?= hs_h($t['landing_apply_icon_set'] ?? 'Apply icon set to features') ?></button>
                  </div>
                  <?php elseif ($item['id'] === 'nav'): ?>
                  <div class="elb-fields-stack">
                    <?= hs_landing_panel_field(
                        $t['landing_nav_style'] ?? 'Header style',
                        '<select name="nav_style" data-landing-sync>'
                        . implode('', array_map(static function (string $key, string $label) use ($data): string {
                            $sel = ($data['nav_style'] ?? '') === $key ? ' selected' : '';
                            return '<option value="' . hs_h($key) . '"' . $sel . '>' . hs_h($label) . '</option>';
                        }, array_keys(hs_landing_nav_styles($t)), hs_landing_nav_styles($t)))
                        . '</select>',
                        $t['landing_hint_nav_style'] ?? 'Layout of the top navigation bar.'
                    ) ?>
                    <?= hs_landing_panel_field(
                        $t['landing_nav_burger'] ?? 'Burger menu',
                        '<select name="nav_burger" data-landing-sync>'
                        . implode('', array_map(static function (string $key, string $label) use ($data): string {
                            $sel = ($data['nav_burger'] ?? 'mobile') === $key ? ' selected' : '';
                            return '<option value="' . hs_h($key) . '"' . $sel . '>' . hs_h($label) . '</option>';
                        }, array_keys(hs_landing_burger_modes($t)), hs_landing_burger_modes($t)))
                        . '</select>',
                        $t['landing_hint_nav_burger'] ?? 'Show a hamburger menu on mobile or always.'
                    ) ?>
                    <div class="elb-fields-grid">
                      <?= hs_landing_panel_field(
                          $t['landing_nav_cta_text'] ?? 'Header button',
                          '<input type="text" name="nav_cta_text" value="' . hs_h((string) ($data['nav_cta_text'] ?? '')) . '" data-landing-sync placeholder="' . hs_h($t['landing_nav_cta_placeholder'] ?? 'Book now') . '">',
                          $t['landing_hint_nav_cta'] ?? 'Optional CTA button in the header (cta, glass, gradient styles).'
                      ) ?>
                      <?= hs_landing_panel_field(
                          $t['landing_nav_cta_url'] ?? 'Button URL',
                          '<input type="text" name="nav_cta_url" value="' . hs_h((string) ($data['nav_cta_url'] ?? '#contact')) . '" data-landing-sync placeholder="#contact">',
                          $t['landing_hint_nav_cta_url'] ?? 'Anchor or full URL for the header button.'
                      ) ?>
                    </div>
                    <p class="elb-field-hint"><?= hs_h($t['landing_hint_nav_section'] ?? 'Menu links at the top of the page. Use #about for on-page anchors.') ?></p>
                    <div data-landing-nav-editor></div>
                  </div>
                  <?php elseif ($item['id'] === 'messengers'): ?>
                  <div class="elb-fields-stack">
                    <p class="elb-field-hint elb-field-hint-top"><?= hs_h($t['landing_hint_messengers_section'] ?? 'Floating chat buttons and quick links to messengers.') ?></p>
                    <div class="elb-fields-grid">
                      <?php foreach (hs_landing_messenger_channels($t) as $chKey => $ch): ?>
                      <?= hs_landing_panel_field(
                          $ch['label'],
                          '<input type="text" name="msg_' . hs_h($chKey) . '" value="' . hs_h((string) ($data['msg_' . $chKey] ?? '')) . '" data-landing-sync placeholder="' . hs_h($ch['placeholder']) . '">',
                          $t['landing_hint_msg_' . $chKey] ?? ''
                      ) ?>
                      <?php endforeach; ?>
                    </div>
                    <label class="elb-check-row">
                      <input type="checkbox" name="msg_floating" value="1" data-landing-sync<?= !empty($data['msg_floating']) ? ' checked' : '' ?>>
                      <span><?= hs_h($t['landing_msg_floating'] ?? 'Show floating buttons on page') ?></span>
                    </label>
                    <div class="elb-fields-grid">
                      <?= hs_landing_panel_field(
                          $t['landing_msg_style'] ?? 'Floating style',
                          '<select name="msg_style" data-landing-sync>'
                          . implode('', array_map(static function (string $key, string $label) use ($data): string {
                              $sel = ($data['msg_style'] ?? 'stack') === $key ? ' selected' : '';
                              return '<option value="' . hs_h($key) . '"' . $sel . '>' . hs_h($label) . '</option>';
                          }, array_keys(hs_landing_msg_float_styles($t)), hs_landing_msg_float_styles($t)))
                          . '</select>',
                          $t['landing_hint_msg_style'] ?? ''
                      ) ?>
                      <?= hs_landing_panel_field(
                          $t['landing_msg_position'] ?? 'Position',
                          '<select name="msg_position" data-landing-sync>'
                          . implode('', array_map(static function (string $key, string $label) use ($data): string {
                              $sel = ($data['msg_position'] ?? 'right') === $key ? ' selected' : '';
                              return '<option value="' . hs_h($key) . '"' . $sel . '>' . hs_h($label) . '</option>';
                          }, array_keys(hs_landing_msg_positions($t)), hs_landing_msg_positions($t)))
                          . '</select>',
                          $t['landing_hint_msg_position'] ?? ''
                      ) ?>
                    </div>
                  </div>
                  <?php elseif ($item['id'] === 'footer'): ?>
                  <div class="elb-fields-stack">
                    <?= hs_landing_panel_field(
                        $t['landing_footer_style'] ?? 'Footer style',
                        '<select name="footer_style" data-landing-sync>'
                        . implode('', array_map(static function (string $key, string $label) use ($data): string {
                            $sel = ($data['footer_style'] ?? '') === $key ? ' selected' : '';
                            return '<option value="' . hs_h($key) . '"' . $sel . '>' . hs_h($label) . '</option>';
                        }, array_keys(hs_landing_footer_styles($t)), hs_landing_footer_styles($t)))
                        . '</select>',
                        $t['landing_hint_footer_style'] ?? 'Layout of the page footer.'
                    ) ?>
                    <?= hs_landing_panel_field(
                        $t['landing_footer_text'] ?? 'Footer note',
                        '<input type="text" name="footer_text" value="' . hs_h((string) ($data['footer_text'] ?? '')) . '" data-landing-sync>',
                        $t['landing_hint_footer_text'] ?? ''
                    ) ?>
                    <div class="elb-fields-grid">
                      <?= hs_landing_panel_field('Facebook', '<input type="url" name="social_facebook" value="' . hs_h((string) ($data['social_facebook'] ?? '')) . '" data-landing-sync placeholder="https://">', $t['landing_hint_social'] ?? 'Full URL to your profile.') ?>
                      <?= hs_landing_panel_field('Instagram', '<input type="url" name="social_instagram" value="' . hs_h((string) ($data['social_instagram'] ?? '')) . '" data-landing-sync placeholder="https://">', $t['landing_hint_social'] ?? '') ?>
                      <?= hs_landing_panel_field('LinkedIn', '<input type="url" name="social_linkedin" value="' . hs_h((string) ($data['social_linkedin'] ?? '')) . '" data-landing-sync placeholder="https://">', $t['landing_hint_social'] ?? '') ?>
                    </div>
                  </div>
                  <?php endif; ?>
                </div>
              </article>
              <?php endforeach; ?>
            </div>
            <p class="hp-muted hs-landing-path hs-landing-path-menu"><i class="fa-solid fa-folder-open"></i> <?= hs_h($t['landing_publish_path'] ?? 'Publishes to') ?>: <code><?= hs_h($publishRel) ?></code></p>
          </form>
        </div>
        <div class="elb-ppane" data-elb-ppane="structure">
          <p class="elb-pane-hint"><?= hs_h($t['landing_structure_hint'] ?? 'Page structure — tap a block to edit.') ?></p>
          <div class="elb-navigator" data-elb-navigator></div>
        </div>
      </div>
    </aside>

    <main class="elb-canvas-wrap" data-elb-canvas-wrap>
      <button type="button" class="elb-panel-reveal elb-panel-reveal-left" data-elb-reveal="left" hidden title="<?= hs_h($t['landing_panel_left'] ?? 'Panel') ?>"><i class="fa-solid fa-bars"></i><span><?= hs_h($t['landing_tab_elements'] ?? 'Elements') ?></span></button>
      <button type="button" class="elb-panel-reveal elb-panel-reveal-right" data-elb-reveal="right" hidden title="<?= hs_h($t['landing_panel_right'] ?? 'Edit') ?>"><i class="fa-solid fa-sliders"></i><span><?= hs_h($t['landing_edit_block'] ?? 'Edit') ?></span></button>
      <div class="elb-canvas-hint" data-elb-canvas-hint hidden><i class="fa-solid fa-hand-pointer"></i> <?= hs_h($t['landing_canvas_hint'] ?? 'Click a section in the preview to edit it') ?></div>
      <div class="elb-canvas-device is-desktop" data-elb-canvas-device="desktop">
        <div class="elb-canvas-device-bar" data-elb-canvas-toolbar>
          <div class="elb-devices elb-canvas-devices" role="group" aria-label="<?= hs_h($t['landing_devices'] ?? 'Preview device') ?>">
            <button type="button" class="elb-device is-active" data-elb-device="desktop" title="<?= hs_h($t['landing_device_desktop'] ?? 'Desktop') ?>"><i class="fa-solid fa-desktop"></i><span><?= hs_h($t['landing_device_desktop'] ?? 'Desktop') ?></span></button>
            <button type="button" class="elb-device" data-elb-device="tablet" title="<?= hs_h($t['landing_device_tablet'] ?? 'Tablet') ?>"><i class="fa-solid fa-tablet-screen-button"></i><span><?= hs_h($t['landing_device_tablet'] ?? 'Tablet') ?></span></button>
            <button type="button" class="elb-device" data-elb-device="mobile" title="<?= hs_h($t['landing_device_mobile'] ?? 'Mobile') ?>"><i class="fa-solid fa-mobile-screen"></i><span><?= hs_h($t['landing_device_mobile'] ?? 'Mobile') ?></span></button>
          </div>
          <p class="elb-canvas-toolbar-hint"><i class="fa-solid fa-hand-pointer"></i> <?= hs_h($t['landing_canvas_hint'] ?? 'Click a section in the preview to edit it') ?></p>
        </div>
        <iframe id="landing-preview-frame" class="elb-canvas-frame" title="<?= hs_h($t['landing_preview'] ?? 'Preview') ?>"></iframe>
      </div>
    </main>

    <aside class="elb-panel elb-panel-right" data-elb-panel="right">
      <header class="elb-panel-right-head">
        <span data-elb-right-title><?= hs_h($t['landing_edit_block'] ?? 'Edit block') ?></span>
        <button type="button" class="elb-panel-close" data-elb-close="right" aria-label="<?= hs_h($t['landing_close'] ?? 'Close') ?>"><i class="fa-solid fa-xmark"></i></button>
      </header>
      <div class="elb-panel-scroll">
        <p class="hs-landing-blocks-hint"><i class="fa-solid fa-pen-to-square"></i> <?= hs_h($t['landing_blocks_edit_hint'] ?? '') ?></p>
        <div class="hs-landing-blocks" data-landing-blocks></div>
      </div>
      <footer class="elb-edit-footer">
        <button type="button" class="hs-btn hs-btn-primary elb-add-widget-btn" data-elb-add-widget>
          <i class="fa-solid fa-plus"></i> <?= hs_h($t['landing_add_widget_btn'] ?? 'Add widget') ?>
        </button>
        <p class="elb-edit-footer-hint"><i class="fa-solid fa-grip-vertical"></i> <?= hs_h($t['landing_drag_footer_hint'] ?? 'Drag blocks by the handle to reorder.') ?></p>
      </footer>
    </aside>
  </div>

  <div class="elb-overlay" data-elb-overlay hidden></div>

  <div class="elb-tips" data-elb-tips hidden>
    <div class="elb-tips-backdrop" data-elb-tips-close></div>
    <div class="elb-tips-panel" role="dialog" aria-modal="true" aria-labelledby="elb-tips-title">
      <header class="elb-tips-head">
        <div class="elb-tips-head-text">
          <h2 id="elb-tips-title"><i class="fa-solid fa-lightbulb"></i> <?= hs_h($t['landing_tips_title'] ?? 'Builder tips') ?></h2>
          <p><?= hs_h($t['landing_tips_sub'] ?? 'Quick guide to building your start page.') ?></p>
        </div>
        <button type="button" class="elb-tips-close" data-elb-tips-close aria-label="<?= hs_h($t['landing_close'] ?? 'Close') ?>"><i class="fa-solid fa-xmark"></i></button>
      </header>
      <ul class="elb-tips-list">
        <?php foreach ($landingTips as $tip): ?>
        <li class="elb-tips-item">
          <span class="elb-tips-icon"><i class="fa-solid <?= hs_h($tip['icon']) ?>"></i></span>
          <span class="elb-tips-text"><?= hs_h($tip['text']) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <footer class="elb-tips-foot">
        <label class="elb-tips-dismiss">
          <input type="checkbox" data-elb-tips-dismiss>
          <span><?= hs_h($t['landing_tips_dismiss'] ?? 'Don\'t show again') ?></span>
        </label>
        <button type="button" class="elb-topbar-btn elb-topbar-publish elb-tips-ok" data-elb-tips-close><?= hs_h($t['landing_tips_got_it'] ?? 'Got it') ?></button>
      </footer>
    </div>
  </div>

  <nav class="elb-dock" aria-label="<?= hs_h($t['landing_mobile_nav'] ?? 'Builder navigation') ?>">
    <button type="button" class="elb-dock-btn" data-elb-dock="elements"><i class="fa-solid fa-plus"></i><span><?= hs_h($t['landing_tab_elements'] ?? 'Elements') ?></span></button>
    <button type="button" class="elb-dock-btn" data-elb-dock="structure"><i class="fa-solid fa-sitemap"></i><span><?= hs_h($t['landing_tab_structure'] ?? 'Structure') ?></span></button>
    <button type="button" class="elb-dock-btn is-active" data-elb-dock="canvas"><i class="fa-solid fa-eye"></i><span><?= hs_h($t['landing_preview'] ?? 'Preview') ?></span></button>
    <button type="button" class="elb-dock-btn" data-elb-dock="edit"><i class="fa-solid fa-sliders"></i><span><?= hs_h($t['landing_edit_block'] ?? 'Edit') ?></span></button>
    <button type="button" class="elb-dock-btn" data-elb-dock="settings"><i class="fa-solid fa-gear"></i><span><?= hs_h($t['landing_tab_settings'] ?? 'Settings') ?></span></button>
  </nav>
</div>
<script>window.HS_LANDING_CFG = <?= $cfgJson ?>;</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';