(function () {
  'use strict';

  var root = document.querySelector('[data-hs-landing-builder]');
  if (!root || !window.HS_LANDING_CFG) return;

  var cfg = window.HS_LANDING_CFG;
  var MSG = cfg.i18n || {};
  var form = document.getElementById('landing-form');
  var frame = document.getElementById('landing-preview-frame');
  var blocksEl = root.querySelector('[data-landing-blocks]');
  var paletteEl = root.querySelector('[data-landing-block-palette]');
  var navEl = root.querySelector('[data-landing-nav-editor]');
  var blocksInput = document.getElementById('landing-blocks-json');
  var navInput = document.getElementById('landing-nav-json');
  if (!form || !frame || !blocksEl || !paletteEl || !navEl) return;

  var state = JSON.parse(JSON.stringify(cfg.data || {}));
  var selectedBlockIdx = (state.blocks || []).length ? 0 : -1;
  var elbDevice = 'desktop';
  var paletteFilter = '';

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function uid(prefix) {
    return (prefix || 'b') + '_' + Math.random().toString(36).slice(2, 8);
  }

  function defaultBlock(type) {
    var reg = cfg.blockTypes[type];
    if (!reg) return null;
    var variants = Object.keys(reg.variants || {});
    var base = { id: uid('b_' + type), type: type, variant: variants[0] || '', on: true };
    if (type === 'hero') {
      return Object.assign(base, {
        title: state.business_name || 'Welcome',
        subtitle: state.tagline || '',
        cta_text: MSG.cta_text || 'Contact',
        cta_url: '#contact',
      });
    }
    if (type === 'features') {
      return Object.assign(base, {
        section_title: state.tagline || 'Features',
        items: [
          { icon: 'fa-bolt', title: 'Fast', text: 'Quick setup' },
          { icon: 'fa-shield-halved', title: 'Secure', text: 'SSL included' },
          { icon: 'fa-mobile-screen', title: 'Mobile', text: 'Responsive' },
        ],
      });
    }
    if (type === 'about') {
      return Object.assign(base, { title: 'About us', text: 'Your story here.' });
    }
    if (type === 'gallery') {
      return Object.assign(base, {
        section_title: 'Gallery',
        items: [
          { caption: 'Photo 1', image: '', hue: 160 },
          { caption: 'Photo 2', image: '', hue: 200 },
          { caption: 'Photo 3', image: '', hue: 280 },
        ],
      });
    }
    if (type === 'info') {
      return Object.assign(base, {
        title: 'Stats',
        text: '',
        quote: 'Great service!',
        author: 'Client',
        stats: [
          { value: '10+', label: 'Years' },
          { value: '500+', label: 'Clients' },
          { value: '24/7', label: 'Support' },
          { value: '99%', label: 'Uptime' },
        ],
      });
    }
    if (type === 'contact') {
      return Object.assign(base, {
        title: 'Contact',
        phone: '',
        email: '',
        address: '',
        cta_text: 'Contact',
        cta_url: '#contact',
      });
    }
    if (type === 'cta') {
      return Object.assign(base, {
        title: 'Ready to start?',
        text: 'Join us today.',
        cta_text: MSG.cta_text || 'Contact',
        cta_url: '#contact',
      });
    }
    if (type === 'testimonials') {
      return Object.assign(base, {
        section_title: 'Reviews',
        items: [
          { name: 'Anna K.', role: 'CEO', text: 'Great service!' },
          { name: 'Mark P.', role: 'Founder', text: 'Highly recommend.' },
        ],
      });
    }
    if (type === 'pricing') {
      return Object.assign(base, {
        section_title: 'Pricing',
        items: [
          { name: 'Starter', price: '29', period: '/mo', features: ['1 site', 'SSL'], cta_text: 'Choose', featured: false },
          { name: 'Pro', price: '59', period: '/mo', features: ['5 sites', 'Support'], cta_text: 'Choose', featured: true },
        ],
      });
    }
    if (type === 'faq') {
      return Object.assign(base, {
        section_title: 'FAQ',
        items: [
          { q: 'How to start?', a: 'Sign up and publish.' },
          { q: 'Can I edit design?', a: 'Yes, anytime.' },
        ],
      });
    }
    if (type === 'team') {
      return Object.assign(base, {
        section_title: 'Team',
        items: [
          { name: 'Alex', role: 'CEO', bio: 'Strategy lead.' },
          { name: 'Jamie', role: 'Design', bio: 'UX expert.' },
        ],
      });
    }
    if (type === 'logos') {
      return Object.assign(base, {
        section_title: 'Trusted by',
        items: [{ name: 'Acme' }, { name: 'Globex' }, { name: 'Initech' }],
      });
    }
    if (type === 'video') return Object.assign(base, { section_title: 'Video', video_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' });
    if (type === 'newsletter') return Object.assign(base, { title: 'Newsletter', text: 'Get updates.', cta_text: 'Subscribe' });
    if (type === 'timeline') return Object.assign(base, { section_title: 'Timeline', items: [{ year: '2020', title: 'Start', text: 'Founded.' }, { year: '2024', title: 'Now', text: 'Growing.' }] });
    if (type === 'services') return Object.assign(base, { section_title: 'Services', items: [{ icon: 'fa-wrench', title: 'Service', text: 'Description.', price: '' }] });
    if (type === 'heading') return Object.assign(base, { title: 'Section title', subtitle: '' });
    if (type === 'text') return Object.assign(base, { title: '', text: 'Your text here.' });
    if (type === 'image') return Object.assign(base, { image: '', caption: 'Caption' });
    if (type === 'divider') return base;
    if (type === 'spacer') return base;
    if (type === 'hours') return Object.assign(base, { title: 'Hours', items: [{ day: 'Mon–Fri', hours: '9–18' }] });
    if (type === 'map') return Object.assign(base, { title: 'Location', address: '', embed_url: '' });
    if (type === 'banner') return Object.assign(base, { text: 'Promo message', cta_text: 'Learn more', cta_url: '#contact' });
    if (type === 'quote') return Object.assign(base, { quote: 'Great quote.', author: 'Author', role: '' });
    if (type === 'download') return Object.assign(base, { section_title: 'Downloads', items: [{ label: 'File.pdf', url: '#', size: '1 MB' }] });
    if (type === 'alert') return Object.assign(base, { title: 'Notice', text: 'Important info.' });
    if (type === 'events') return Object.assign(base, { section_title: 'Events', items: [{ date: 'Mar 1', title: 'Event', location: 'City' }] });
    if (type === 'steps') return Object.assign(base, { section_title: 'Steps', items: [{ title: 'Step 1', text: 'Do this.' }] });
    if (type === 'countdown') {
      var d = new Date();
      d.setDate(d.getDate() + 30);
      return Object.assign(base, { title: 'Coming soon', text: '', countdown_date: d.toISOString().slice(0, 10) });
    }
    if (type === 'columns') return Object.assign(base, { section_title: '', items: [{ title: 'Col 1', text: 'Text' }, { title: 'Col 2', text: 'Text' }] });
    if (type === 'badges') return Object.assign(base, { section_title: 'Tags', items: [{ label: 'Tag' }] });
    if (type === 'buttons') return Object.assign(base, { section_title: '', items: [{ text: 'Get started', url: '#contact', style: 'primary' }, { text: 'Learn more', url: '#about', style: 'outline' }] });
    if (type === 'cards') return Object.assign(base, { section_title: 'Highlights', items: [{ icon: 'fa-star', title: 'Quality', text: 'Top service.', cta_text: '', cta_url: '' }] });
    if (type === 'social') return Object.assign(base, { section_title: 'Follow us', items: [{ network: 'facebook', url: 'https://facebook.com/', label: 'Facebook' }, { network: 'instagram', url: 'https://instagram.com/', label: 'Instagram' }] });
    if (type === 'trust') return Object.assign(base, { section_title: '', items: [{ icon: 'fa-shield-halved', title: 'Secure', text: 'Protected' }] });
    if (type === 'callout') return Object.assign(base, { title: 'Note', text: 'Important message.', cta_text: 'Contact', cta_url: '#contact' });
    if (type === 'media_text') return Object.assign(base, { title: 'Title', text: 'Description.', image: '', side: 'left', cta_text: 'Contact', cta_url: '#contact' });
    if (type === 'menu') return Object.assign(base, { section_title: 'Menu', items: [{ name: 'Item', desc: 'Description', price: '100 ₴' }] });
    if (type === 'stats_bar') return Object.assign(base, { items: [{ value: '100+', label: 'Clients' }] });
    if (type === 'comparison') return Object.assign(base, { section_title: 'Compare', left_title: 'Basic', right_title: 'Pro', items: [{ label: 'Feature', a: 'Yes', b: 'Yes' }] });
    if (type === 'contact_bar') return Object.assign(base, { phone: '', email: '', whatsapp: '', cta_text: 'Contact', cta_url: '#contact' });
    if (type === 'icon_list') return Object.assign(base, { section_title: 'Benefits', items: [{ icon: 'fa-check', title: 'Benefit', text: 'Details.' }] });
    if (type === 'app_cta') return Object.assign(base, { title: 'Download app', text: 'Available on iOS and Android.', ios_url: '#', android_url: '#' });
    if (type === 'messengers') return Object.assign(base, { section_title: 'Write to us', items: [{ channel: 'whatsapp', value: '+380501234567', label: 'WhatsApp' }, { channel: 'telegram', value: '@username', label: 'Telegram' }] });
    if (type === 'slider') return Object.assign(base, { section_title: '', variant: 'fade', height: 'md', autoplay: true, interval: 5000, arrows: true, dots: true, items: [{ image: '', hue: 160, title: 'Welcome', subtitle: 'Your headline', cta_text: 'Contact', cta_url: '#contact' }, { image: '', hue: 200, title: 'Quality', subtitle: 'Second slide', cta_text: '', cta_url: '#' }, { image: '', hue: 280, title: 'Get started', subtitle: 'Third slide', cta_text: 'Contact', cta_url: '#contact' }] });
    if (type === 'accordion') return Object.assign(base, { section_title: 'FAQ', items: [{ title: 'How does it work?', text: 'Simple process.' }, { title: 'Prices?', text: 'Transparent pricing.' }] });
    if (type === 'tabs') return Object.assign(base, { section_title: 'Details', items: [{ title: 'Overview', text: 'General info.' }, { title: 'Features', text: 'Key benefits.' }] });
    if (type === 'marquee') return Object.assign(base, { variant: 'text', text: 'Trusted · Fast · Quality · ', speed: 'normal', items: [{ label: 'Fast' }, { label: 'Quality' }] });
    return base;
  }

  function faPrefix(style) {
    return style === 'regular' ? 'fa-regular' : 'fa-solid';
  }

  function currentIconSetKey() {
    return (form.querySelector('[name="icon_set"]:checked') || {}).value || 'business';
  }

  function currentIconStyle() {
    return (form.querySelector('[name="icon_style"]:checked') || {}).value || 'solid';
  }

  function iconsForSet(key) {
    var set = (cfg.iconSets || {})[key];
    return set && set.icons ? set.icons : ['fa-star'];
  }

  function hexHue(hex) {
    hex = String(hex || '').replace('#', '');
    if (hex.length !== 6) return 160;
    var r = parseInt(hex.slice(0, 2), 16) / 255;
    var g = parseInt(hex.slice(2, 4), 16) / 255;
    var b = parseInt(hex.slice(4, 6), 16) / 255;
    var max = Math.max(r, g, b);
    var min = Math.min(r, g, b);
    if (max === min) return 0;
    var d = max - min;
    var h;
    if (max === r) h = (g - b) / d + (g < b ? 6 : 0);
    else if (max === g) h = (b - r) / d + 2;
    else h = (r - g) / d + 4;
    return Math.round(h * 60) % 360;
  }

  var galleryPickTarget = null;
  var galleryModalEl = null;
  var galleryFileInput = null;

  function galleryImageUrl(rel) {
    if (!rel) return '';
    if (/^https?:\/\//i.test(rel)) return rel;
    return (cfg.publicBase || '') + rel;
  }

  function galleryThumbStyle(item, hue) {
    if (item && item.image) {
      return 'background-image:url(' + galleryImageUrl(item.image) + ')';
    }
    var h = parseInt(hue, 10);
    if (isNaN(h)) h = 160;
    return 'background:linear-gradient(135deg,hsl(' + h + ',65%,45%),hsl(' + ((h + 40) % 360) + ',70%,55%))';
  }

  function galleryHues(data, count) {
    var palettes = cfg.galleryPalettes || {};
    var key = data.gallery_palette || 'brand';
    var pal = palettes[key] || palettes.brand || { hues: [] };
    var hues = pal.hues || [];
    if (!hues.length) {
      var base = hexHue(data.color);
      hues = [base, base + 25, base + 50, base + 75, base + 100, base + 125];
    }
    var out = [];
    for (var i = 0; i < count; i++) {
      out.push(((hues[i % hues.length] || 160) + 360) % 360);
    }
    return out;
  }

  function iconPickerHtml(blockIndex, itemIndex, selected) {
    var icons = iconsForSet(currentIconSetKey());
    var style = currentIconStyle();
    var prefix = faPrefix(style);
    var sel = selected || icons[0] || 'fa-star';
    var key = blockIndex + '-' + itemIndex;
    var html = '<div class="hs-landing-feat-icon-wrap" data-icon-picker="' + key + '">'
      + '<input type="hidden" data-b-item-icon' + key + ' value="' + esc(sel) + '">'
      + '<button type="button" class="hs-landing-feat-icon-btn" data-feat-icon-toggle="' + key + '" title="' + esc(MSG.pick_icon || 'Pick icon') + '">'
      + '<i class="' + prefix + ' ' + esc(sel) + '"></i></button>'
      + '<div class="hs-landing-icon-picker hs-landing-icon-picker-pop" hidden data-feat-icon-grid="' + key + '">';
    icons.forEach(function (ic) {
      html += '<button type="button" class="hs-landing-icon-pick' + (sel === ic ? ' is-active' : '') + '" data-pick-icon="' + esc(ic) + '" data-pick-for="' + key + '" title="' + esc(ic) + '">'
        + '<i class="' + prefix + ' ' + esc(ic) + '"></i></button>';
    });
    return html + '</div></div>';
  }

  function readFormMeta() {
    var theme = (form.querySelector('[name="theme"]:checked') || {}).value || 'emerald';
    var themes = cfg.themes || {};
    var color = (form.querySelector('[name="color"]') || {}).value;
    if (!/^#[0-9a-fA-F]{6}$/.test(color || '')) {
      color = (themes[theme] || {}).color || '#059669';
    }
    return {
      business_name: (form.querySelector('[name="business_name"]') || {}).value || '',
      tagline: (form.querySelector('[name="tagline"]') || {}).value || '',
      theme: theme,
      color: color,
      icon_set: currentIconSetKey(),
      icon_style: currentIconStyle(),
      gallery_palette: (form.querySelector('[name="gallery_palette"]') || {}).value || 'brand',
      logo_icon: (form.querySelector('[name="logo_icon"]') || form.querySelector('[data-landing-logo-icon-input]') || {}).value || '',
      nav_style: (form.querySelector('[name="nav_style"]') || {}).value || 'classic',
      nav_burger: (form.querySelector('[name="nav_burger"]') || {}).value || 'mobile',
      nav_cta_text: (form.querySelector('[name="nav_cta_text"]') || {}).value || '',
      nav_cta_url: (form.querySelector('[name="nav_cta_url"]') || {}).value || '#contact',
      msg_whatsapp: (form.querySelector('[name="msg_whatsapp"]') || {}).value || '',
      msg_telegram: (form.querySelector('[name="msg_telegram"]') || {}).value || '',
      msg_viber: (form.querySelector('[name="msg_viber"]') || {}).value || '',
      msg_messenger: (form.querySelector('[name="msg_messenger"]') || {}).value || '',
      msg_signal: (form.querySelector('[name="msg_signal"]') || {}).value || '',
      msg_skype: (form.querySelector('[name="msg_skype"]') || {}).value || '',
      msg_line: (form.querySelector('[name="msg_line"]') || {}).value || '',
      msg_floating: !!(form.querySelector('[name="msg_floating"]') || {}).checked,
      msg_style: (form.querySelector('[name="msg_style"]') || {}).value || 'stack',
      msg_position: (form.querySelector('[name="msg_position"]') || {}).value || 'right',
      footer_style: (form.querySelector('[name="footer_style"]') || {}).value || 'minimal',
      footer_text: (form.querySelector('[name="footer_text"]') || {}).value || '',
      social_facebook: (form.querySelector('[name="social_facebook"]') || {}).value || '',
      social_instagram: (form.querySelector('[name="social_instagram"]') || {}).value || '',
      social_linkedin: (form.querySelector('[name="social_linkedin"]') || {}).value || '',
      nav_links: state.nav_links || [],
      blocks: state.blocks || [],
    };
  }

  function syncHidden() {
    if (blocksInput) blocksInput.value = JSON.stringify(state.blocks || []);
    if (navInput) navInput.value = JSON.stringify(state.nav_links || []);
  }

  function applyPageTemplate(templateId) {
    var tpl = (cfg.pageTemplates || {})[templateId];
    if (!tpl || !tpl.blocks || !tpl.blocks.length) return;
    if (!window.confirm(MSG.template_confirm || 'Replace all blocks with this template?')) return;
    var meta = tpl.meta || {};
    if (meta.business_name) {
      var bnEl = form.querySelector('[name="business_name"]');
      if (bnEl) bnEl.value = meta.business_name;
      state.business_name = meta.business_name;
    }
    if (meta.tagline) {
      var tgEl = form.querySelector('[name="tagline"]');
      if (tgEl) tgEl.value = meta.tagline;
      state.tagline = meta.tagline;
    }
    if (meta.theme) {
      var themeInp = form.querySelector('[name="theme"][value="' + meta.theme + '"]');
      if (themeInp) {
        themeInp.checked = true;
        syncChipActive('theme');
        var themes = cfg.themes || {};
        var colInp = form.querySelector('[name="color"]');
        if (colInp && themes[meta.theme]) colInp.value = themes[meta.theme].color;
      }
    }
    if (meta.color) {
      var colorInp = form.querySelector('[name="color"]');
      if (colorInp) colorInp.value = meta.color;
    }
    if (meta.icon_set) {
      var iconSetInp = form.querySelector('[name="icon_set"][value="' + meta.icon_set + '"]');
      if (iconSetInp) {
        iconSetInp.checked = true;
        syncChipActive('icon_set');
      }
    }
    if (meta.nav_links && meta.nav_links.length) {
      state.nav_links = JSON.parse(JSON.stringify(meta.nav_links));
      renderNavEditor();
    }
    if (meta.nav_style) {
      var navStyleEl = form.querySelector('[name="nav_style"]');
      if (navStyleEl) navStyleEl.value = meta.nav_style;
    }
    if (meta.footer_style) {
      var footerStyleEl = form.querySelector('[name="footer_style"]');
      if (footerStyleEl) footerStyleEl.value = meta.footer_style;
    }
    if (meta.nav_cta_text) {
      var navCtaTextEl = form.querySelector('[name="nav_cta_text"]');
      if (navCtaTextEl) navCtaTextEl.value = meta.nav_cta_text;
    }
    if (meta.nav_cta_url) {
      var navCtaUrlEl = form.querySelector('[name="nav_cta_url"]');
      if (navCtaUrlEl) navCtaUrlEl.value = meta.nav_cta_url;
    }
    state.blocks = JSON.parse(JSON.stringify(tpl.blocks));
    selectedBlockIdx = 0;
    renderBlocks();
    renderNavigator();
    selectBlock(0, true);
    updatePreview();
    syncHidden();
  }

  function renderTemplates() {
    var wrap = root.querySelector('[data-elb-templates]');
    if (!wrap) return;
    var templates = cfg.pageTemplates || {};
    var ids = Object.keys(templates);
    if (!ids.length) {
      wrap.innerHTML = '';
      return;
    }
    var html = '<p class="hs-landing-palette-label">' + esc(MSG.page_templates || 'Page templates') + '</p>'
      + '<p class="elb-pane-hint elb-templates-hint">' + esc(MSG.page_templates_hint || '') + '</p><div class="elb-templates-grid">';
    ids.forEach(function (id) {
      var tpl = templates[id];
      html += '<button type="button" class="elb-template-btn" data-apply-template="' + esc(id) + '" title="' + esc(tpl.desc || '') + '">'
        + '<i class="fa-solid ' + esc(tpl.icon || 'fa-file') + '"></i>'
        + '<span class="elb-template-label">' + esc(tpl.label || id) + '</span>'
        + '<span class="elb-template-desc">' + esc(tpl.desc || '') + '</span></button>';
    });
    html += '</div>';
    wrap.innerHTML = html;
    wrap.querySelectorAll('[data-apply-template]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        applyPageTemplate(btn.getAttribute('data-apply-template') || '');
      });
    });
  }

  function renderPalette() {
    var types = Object.keys(cfg.blockTypes || {});
    var filtered = types.filter(function (type) {
      if (!paletteFilter) return true;
      var b = cfg.blockTypes[type];
      var label = (b && b.label ? b.label : type).toLowerCase();
      return label.indexOf(paletteFilter) !== -1 || type.indexOf(paletteFilter) !== -1;
    });
    var html = '<p class="hs-landing-palette-label">' + esc(MSG.widgets || MSG.add_block || 'Widgets') + '</p><div class="hs-landing-palette-btns">';
    if (!filtered.length) {
      html += '<p class="elb-pane-hint">' + esc(MSG.widget_search_empty || 'No widgets match.') + '</p>';
    }
    filtered.forEach(function (type) {
      var b = cfg.blockTypes[type];
      html += '<button type="button" class="hs-btn hs-btn-ghost hs-landing-add-btn" data-add-block="' + esc(type) + '">'
        + '<i class="fa-solid ' + esc(b.icon) + '"></i><span>' + esc(b.label) + '</span></button>';
    });
    html += '</div>';
    paletteEl.innerHTML = html;
    paletteEl.querySelectorAll('[data-add-block]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var blk = defaultBlock(btn.getAttribute('data-add-block'));
        if (!blk) return;
        state.blocks.push(blk);
        selectedBlockIdx = state.blocks.length - 1;
        renderBlocks();
        renderNavigator();
        selectBlock(selectedBlockIdx, true);
        updatePreview();
        syncHidden();
      });
    });
    renderTemplates();
  }

  function renderNavigator() {
    var nav = root.querySelector('[data-elb-navigator]');
    if (!nav) return;
    nav.innerHTML = (state.blocks || []).map(function (b, i) {
      var reg = cfg.blockTypes[b.type] || {};
      var off = b.on ? '' : ' is-off';
      var sel = i === selectedBlockIdx ? ' is-selected' : '';
      return '<div class="elb-nav-row' + off + sel + '" data-elb-nav-row="' + i + '">'
        + '<button type="button" class="elb-nav-item" data-elb-nav="' + i + '">'
        + '<span class="elb-nav-item-num">#' + (i + 1) + '</span>'
        + '<i class="fa-solid ' + esc(reg.icon || 'fa-cube') + '"></i>'
        + '<span class="elb-nav-item-label">' + esc(reg.label || b.type) + '</span>'
        + (b.on ? '' : '<span class="elb-nav-item-badge">' + esc(MSG.block_hidden || 'Hidden') + '</span>')
        + '</button>'
        + '<div class="elb-nav-row-actions">'
        + '<button type="button" class="hs-landing-icon-btn" data-nav-move="up" data-idx="' + i + '" title="' + esc(MSG.move_up) + '"' + (i === 0 ? ' disabled' : '') + '><i class="fa-solid fa-chevron-up"></i></button>'
        + '<button type="button" class="hs-landing-icon-btn" data-nav-move="down" data-idx="' + i + '" title="' + esc(MSG.move_down) + '"'
        + (i >= state.blocks.length - 1 ? ' disabled' : '') + '><i class="fa-solid fa-chevron-down"></i></button>'
        + '</div></div>';
    }).join('') || '<p class="elb-pane-hint">' + esc(MSG.no_blocks || 'No blocks yet') + '</p>';
    nav.querySelectorAll('[data-elb-nav]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        selectBlock(parseInt(btn.getAttribute('data-elb-nav'), 10), true);
      });
    });
    nav.querySelectorAll('[data-nav-move]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (btn.disabled) return;
        var idx = parseInt(btn.getAttribute('data-idx'), 10);
        moveBlock(idx, btn.getAttribute('data-nav-move') === 'up' ? idx - 1 : idx + 1);
        selectBlock(btn.getAttribute('data-nav-move') === 'up' ? idx - 1 : idx + 1, false);
      });
    });
  }

  function scrollToBlockEditor(idx) {
    var article = blocksEl.querySelector('[data-block-index="' + idx + '"]');
    if (!article) return;
    blocksEl.querySelectorAll('[data-block-index]').forEach(function (art) {
      var i = parseInt(art.getAttribute('data-block-index'), 10);
      var isSel = i === idx;
      art.classList.toggle('is-selected', isSel);
      art.classList.toggle('is-open', isSel);
      art.classList.toggle('hs-landing-block-collapsed', !isSel);
      var tog = art.querySelector('.hs-landing-block-toggle');
      if (tog) tog.setAttribute('aria-expanded', isSel ? 'true' : 'false');
    });
    var body = article.querySelector('.hs-landing-block-body');
    if (body && !article.classList.contains('hs-landing-block-off')) body.hidden = false;
    var scrollPane = root.querySelector('.elb-panel-right .elb-panel-scroll');
    if (scrollPane) {
      var paneTop = scrollPane.getBoundingClientRect().top;
      var artTop = article.getBoundingClientRect().top;
      scrollPane.scrollTop += artTop - paneTop - 12;
    } else {
      article.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function selectBlock(idx, openPanel) {
    if (idx < 0 || idx >= (state.blocks || []).length) return;
    if (idx !== selectedBlockIdx) pullBlocksFromDom();
    selectedBlockIdx = idx;
    if (openPanel) {
      openElbPanel('right', true);
      if (isMobileElb()) {
        setPanelOpen(root.querySelector('[data-elb-panel="left"]'), false);
        root.querySelectorAll('[data-elb-dock]').forEach(function (b) {
          b.classList.toggle('is-active', b.getAttribute('data-elb-dock') === 'edit');
        });
      }
    }
    renderBlocks();
    renderNavigator();
    var titleEl = root.querySelector('[data-elb-right-title]');
    if (titleEl) {
      var reg = cfg.blockTypes[(state.blocks[idx] || {}).type] || {};
      titleEl.textContent = (MSG.edit_block || 'Edit') + ': ' + (reg.label || (state.blocks[idx] || {}).type || '');
    }
    highlightPreviewBlock(idx);
    var hint = root.querySelector('[data-elb-canvas-hint]');
    if (hint && (state.blocks || []).length) hint.hidden = true;
    requestAnimationFrame(function () {
      scrollToBlockEditor(idx);
      requestAnimationFrame(function () { scrollToBlockEditor(idx); });
    });
    setTimeout(function () { scrollToBlockEditor(idx); }, 120);
  }

  function handlePreviewPickNav() {
    setElbTab('settings');
    setOpenSettingsSection('nav');
    openElbPanel('left', true);
    if (isMobileElb()) {
      setPanelOpen(root.querySelector('[data-elb-panel="right"]'), false);
    }
    highlightPreviewBlock(-1);
  }

  function handlePreviewPickMessengers() {
    setElbTab('settings');
    setOpenSettingsSection('messengers');
    openElbPanel('left', true);
    if (isMobileElb()) {
      setPanelOpen(root.querySelector('[data-elb-panel="right"]'), false);
    }
    highlightPreviewBlock(-1);
  }

  function handlePreviewPickBlock(idx) {
    if (isNaN(idx) || idx < 0 || idx >= (state.blocks || []).length) return;
    selectBlock(idx, true);
  }

  function onPreviewSectionActivate(shell) {
    if (!shell) return;
    if (shell.getAttribute('data-elb-pick') === 'nav') {
      handlePreviewPickNav();
      return;
    }
    if (shell.getAttribute('data-elb-pick') === 'messengers') {
      handlePreviewPickMessengers();
      return;
    }
    var idx = parseInt(shell.getAttribute('data-elb-idx'), 10);
    handlePreviewPickBlock(idx);
  }

  function bindPreviewSectionClicks() {
    var doc;
    try {
      doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
    } catch (e) {
      return;
    }
    if (!doc || !doc.documentElement) return;
    if (doc.documentElement.getAttribute('data-elb-clicks-bound') === '1') return;
    doc.documentElement.setAttribute('data-elb-clicks-bound', '1');
    doc.addEventListener('click', function (ev) {
      var bar = ev.target.closest('.elb-sec-bar');
      var shell = bar ? bar.closest('[data-elb-section]') : ev.target.closest('[data-elb-section]');
      if (!shell) return;
      ev.preventDefault();
      ev.stopPropagation();
      onPreviewSectionActivate(shell);
    }, true);
    doc.addEventListener('touchend', function (ev) {
      var bar = ev.target.closest('.elb-sec-bar');
      var shell = bar ? bar.closest('[data-elb-section]') : ev.target.closest('[data-elb-section]');
      if (!shell) return;
      ev.preventDefault();
      onPreviewSectionActivate(shell);
    }, { passive: false, capture: true });
  }

  function bindCanvasToolbar() {
    var toolbar = root.querySelector('[data-elb-canvas-toolbar]');
    if (!toolbar || toolbar.getAttribute('data-elb-toolbar-bound') === '1') return;
    toolbar.setAttribute('data-elb-toolbar-bound', '1');
    toolbar.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-elb-device]');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      setElbDevice(btn.getAttribute('data-elb-device'));
    });
    toolbar.addEventListener('touchend', function (e) {
      var btn = e.target.closest('[data-elb-device]');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      setElbDevice(btn.getAttribute('data-elb-device'));
    }, { passive: false });
  }

  function highlightPreviewBlock(idx) {
    try {
      var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
      if (doc) {
        doc.querySelectorAll('[data-elb-section]').forEach(function (s) {
          if (idx < 0) {
            s.classList.remove('is-elb-pick');
            return;
          }
          s.classList.toggle('is-elb-pick', parseInt(s.getAttribute('data-elb-idx'), 10) === idx);
        });
        if (idx >= 0) {
          var target = doc.querySelector('[data-elb-idx="' + idx + '"]');
          if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }
      if (frame.contentWindow) {
        frame.contentWindow.postMessage({ type: 'hs-elb-highlight', idx: idx }, '*');
      }
    } catch (e) { /* ignore */ }
  }

  var previewObserver = null;
  var previewLoadToken = 0;

  function resizePreviewFrame() {
    try {
      var doc = frame.contentDocument;
      if (!doc || !doc.body) {
        frame.style.height = '480px';
        return;
      }
      var h = Math.max(
        doc.documentElement.scrollHeight || 0,
        doc.body.scrollHeight || 0,
        doc.documentElement.offsetHeight || 0,
        doc.body.offsetHeight || 0,
        480
      );
      frame.style.height = h + 'px';
      var wrap = root.querySelector('[data-elb-canvas-device]');
      if (wrap) wrap.style.minHeight = Math.min(h + 40, 1200) + 'px';
    } catch (e) {
      frame.style.height = '480px';
    }
  }

  function setupPreviewResizeObserver() {
    try {
      var doc = frame.contentDocument;
      if (!doc || !doc.body || typeof ResizeObserver === 'undefined') return;
      if (previewObserver) previewObserver.disconnect();
      previewObserver = new ResizeObserver(function () { resizePreviewFrame(); });
      previewObserver.observe(doc.body);
      previewObserver.observe(doc.documentElement);
    } catch (e) { /* ignore */ }
  }

  function afterPreviewLoad() {
    syncPreviewFrameWidth();
    resizePreviewFrame();
    setupPreviewResizeObserver();
    applyPreviewDeviceInFrame();
    bindPreviewSectionClicks();
    if (selectedBlockIdx >= 0) highlightPreviewBlock(selectedBlockIdx);
  }

  var fieldHints = cfg.fieldHints || {};

  function fieldHint(key) {
    return fieldHints[key] || '';
  }

  function field(label, html, hintKey) {
    var h = hintKey ? fieldHint(hintKey) : '';
    var hintHtml = h ? '<p class="elb-field-hint">' + esc(h) + '</p>' : '';
    return '<div class="elb-field"><label class="elb-field-label">' + esc(label) + '</label>' + html + hintHtml + '</div>';
  }

  function fieldGroup(title, hint, inner) {
    var hintHtml = hint ? '<p class="elb-field-group-hint">' + esc(hint) + '</p>' : '';
    return '<section class="elb-field-group"><h4 class="elb-field-group-title">' + esc(title) + '</h4>' + hintHtml + inner + '</section>';
  }

  function readHexColorInput(val) {
    val = String(val || '').trim();
    return /^#[0-9a-fA-F]{6}$/.test(val) ? val : '';
  }

  function blockHasElementColors(block) {
    return ['text_color', 'heading_color', 'btn_color', 'btn_text_color'].some(function (k) {
      return /^#[0-9a-fA-F]{6}$/.test(block[k] || '');
    });
  }

  function blockSectionStyleAttr(block) {
    var parts = [];
    var c = String(block.color || '').trim();
    if (/^#[0-9a-fA-F]{6}$/.test(c)) {
      parts.push('--c:' + c, '--c2:color-mix(in srgb,' + c + ' 75%,#000)');
    }
    if (/^#[0-9a-fA-F]{6}$/.test(block.text_color || '')) parts.push('--elb-text:' + block.text_color);
    if (/^#[0-9a-fA-F]{6}$/.test(block.heading_color || '')) parts.push('--elb-heading:' + block.heading_color);
    if (/^#[0-9a-fA-F]{6}$/.test(block.btn_color || '')) parts.push('--elb-btn:' + block.btn_color);
    if (/^#[0-9a-fA-F]{6}$/.test(block.btn_text_color || '')) parts.push('--elb-btn-text:' + block.btn_text_color);
    return parts.length ? ' style="' + parts.join(';') + '"' : '';
  }

  function injectBlockColor(html, block) {
    if (!html) return '';
    var style = blockSectionStyleAttr(block);
    var hasColors = blockHasElementColors(block);
    if (!style && !hasColors) return html;
    var out = html;
    if (hasColors) {
      if (/<section\s+class="/.test(out)) {
        out = out.replace(/<section\s+class="/, '<section class="elb-custom-colors ');
      } else {
        out = out.replace(/<section/, '<section class="elb-custom-colors"');
      }
    }
    if (style) {
      out = out.replace(/<section\s/, '<section' + style + ' ');
    }
    return out;
  }

  function wrapBlockBackground(html, block) {
    if (!html || !block.bg_type) return html;
    var m = html.match(/^(<section[^>]*>)([\s\S]*)(<\/section>)$/i);
    if (!m) return html;
    var open = m[1];
    var inner = m[2];
    var close = m[3];
    var overlay = Math.max(0, Math.min(100, parseInt(block.bg_overlay, 10) || 40)) / 100;
    var bgDiv = '';
    if (block.bg_type === 'image' && block.bg_image) {
      bgDiv = '<div class="elb-block-bg" style="background-image:url(' + esc(galleryImageUrl(block.bg_image)) + ');background-size:cover;background-position:center;background-repeat:no-repeat"></div>';
    } else if (block.bg_type === 'color' && /^#[0-9a-fA-F]{6}$/.test(block.bg_color || '')) {
      bgDiv = '<div class="elb-block-bg" style="background:' + esc(block.bg_color) + ';background-size:cover;background-position:center"></div>';
    } else {
      return html;
    }
    if (/\bclass="/.test(open)) {
      open = open.replace(/\bclass="/, 'class="elb-has-bg ');
    } else {
      open = open.replace('<section', '<section class="elb-has-bg"');
    }
    var overlayCss = '--elb-bg-overlay:' + overlay;
    if (/\bstyle="/.test(open)) {
      open = open.replace(/\bstyle="/, 'style="' + overlayCss + ';');
    } else {
      open = open.replace('<section', '<section style="' + overlayCss + '"');
    }
    return open + bgDiv + '<div class="elb-block-bg-overlay" aria-hidden="true"></div><div class="elb-block-inner">' + inner + '</div>' + close;
  }

  function finalizeBlockHtml(html, block) {
    return wrapBlockBackground(injectBlockColor(html, block), block);
  }

  function blockBackgroundFields(index, block) {
    var bgType = block.bg_type || '';
    var bgColor = block.bg_color || '#e2e8f0';
    var overlay = typeof block.bg_overlay === 'number' ? block.bg_overlay : 40;
    var hasImg = !!(block.bg_image);
    return fieldGroup(MSG.field_group_bg || 'Background', MSG.field_group_bg_hint || '',
      field(MSG.bg_type || 'Background',
        '<select data-b-bg-type' + index + '>'
        + '<option value=""' + (bgType === '' ? ' selected' : '') + '>' + esc(MSG.bg_type_none || 'None') + '</option>'
        + '<option value="color"' + (bgType === 'color' ? ' selected' : '') + '>' + esc(MSG.bg_type_color || 'Color') + '</option>'
        + '<option value="image"' + (bgType === 'image' ? ' selected' : '') + '>' + esc(MSG.bg_type_image || 'Image') + '</option></select>',
        'bg_type')
      + field(MSG.bg_color || 'Background color',
        '<div class="elb-block-color-row"><input type="color" data-b-bg-color' + index + ' value="' + esc(/^#[0-9a-fA-F]{6}$/.test(bgColor) ? bgColor : '#e2e8f0') + '">'
        + '<input type="text" data-b-bg-color-text' + index + ' value="' + esc(block.bg_color || '') + '" placeholder="#e2e8f0" class="elb-block-color-text"></div>',
        'bg_color')
      + field(MSG.pick_photo || 'Photo',
        '<input type="hidden" data-b-bg-image' + index + ' value="' + esc(block.bg_image || '') + '">'
        + '<button type="button" class="hs-landing-gal-thumb' + (hasImg ? ' has-image' : '') + '" data-gal-pick="' + index + '-bg" style="' + (hasImg ? 'background-image:url(' + esc(galleryImageUrl(block.bg_image)) + ')' : '') + '">'
        + '<i class="fa-solid fa-image"></i></button>'
        + '<button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-b-bg-clear="' + index + '">' + esc(MSG.clear_photo || 'Remove') + '</button>',
        'bg_type_image')
      + field(MSG.bg_overlay || 'Dim overlay',
        '<input type="range" min="0" max="100" step="5" data-b-bg-overlay' + index + ' value="' + esc(String(overlay)) + '">'
        + '<span class="elb-range-val" data-b-bg-overlay-val' + index + '>' + esc(String(overlay)) + '%</span>',
        'bg_overlay')
    );
  }

  function miniColorField(index, key, label, value, hintKey) {
    var val = value || '';
    var picker = val || '#0f172a';
    return field(label,
      '<div class="elb-block-color-row elb-block-color-row-mini">'
      + '<input type="color" data-b-' + key + index + ' value="' + esc(picker) + '">'
      + '<input type="text" data-b-' + key + '-text' + index + ' value="' + esc(val) + '" placeholder="' + esc(MSG.color_inherit || 'Theme default') + '" class="elb-block-color-text">'
      + '</div>',
      hintKey);
  }

  function blockElementColorFields(index, block) {
    return fieldGroup(MSG.field_group_colors || 'Text & buttons', MSG.field_group_colors_hint || '',
      miniColorField(index, 'tcolor', MSG.text_color || 'Text color', block.text_color, 'text_color')
      + miniColorField(index, 'hcolor', MSG.heading_color || 'Heading color', block.heading_color, 'heading_color')
      + miniColorField(index, 'btncolor', MSG.btn_color || 'Button color', block.btn_color, 'btn_color')
      + miniColorField(index, 'btntextcolor', MSG.btn_text_color || 'Button text color', block.btn_text_color, 'btn_text_color')
    );
  }

  function blockColorField(index, block) {
    var themes = cfg.themes || {};
    var chips = '';
    Object.keys(themes).forEach(function (k) {
      var col = themes[k].color;
      chips += '<button type="button" class="elb-block-color-chip" data-b-color-chip="' + index + '" data-color="' + esc(col) + '" style="background:' + esc(col) + '" title="' + esc(themes[k].label || k) + '"></button>';
    });
    var val = block.color || '';
    var pickerVal = val || state.color || '#059669';
    return field(MSG.block_color || 'Block color',
      '<div class="elb-block-color-row">'
      + '<input type="color" data-b-color' + index + ' value="' + esc(pickerVal) + '">'
      + '<input type="text" data-b-color-text' + index + ' value="' + esc(val) + '" placeholder="#059669" class="elb-block-color-text">'
      + '<button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-b-color-reset="' + index + '">' + esc(MSG.block_color_reset || 'Use theme') + '</button>'
      + '</div><div class="elb-block-color-chips">' + chips + '</div>',
      'block_color');
  }

  function videoEmbedUrl(url) {
    var m = String(url || '').match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{6,})/);
    if (m) return 'https://www.youtube.com/embed/' + m[1];
    m = String(url || '').match(/vimeo\.com\/(?:video\/)?(\d+)/);
    if (m) return 'https://player.vimeo.com/video/' + m[1];
    return '';
  }

  function renderBlockEditor(block, index) {
    var reg = cfg.blockTypes[block.type] || {};
    var variants = reg.variants || {};
    var variantOpts = '';
    Object.keys(variants).forEach(function (k) {
      variantOpts += '<option value="' + esc(k) + '"' + (block.variant === k ? ' selected' : '') + '>' + esc(variants[k]) + '</option>';
    });

    var collapsed = index !== selectedBlockIdx;
    var offCls = block.on ? '' : ' hs-landing-block-off';
    var selCls = index === selectedBlockIdx ? ' is-selected is-open' : '';
    var collapsedCls = collapsed ? ' hs-landing-block-collapsed' : '';
    var visLabel = block.on ? (MSG.block_visible || 'Visible') : (MSG.block_hidden || 'Hidden');
    var regDesc = reg.desc || '';
    var html = '<article class="hs-landing-block' + offCls + selCls + collapsedCls + '" data-block-index="' + index + '" draggable="false">'
      + '<header class="hs-landing-block-head">'
      + '<button type="button" class="hs-landing-drag-handle" data-drag-handle title="' + esc(MSG.drag_block || 'Drag to reorder') + '" aria-label="' + esc(MSG.drag_block || 'Drag') + '">'
      + '<i class="fa-solid fa-grip-vertical"></i></button>'
      + '<span class="hs-landing-block-order" title="' + esc(MSG.block_order || 'Order') + '">#' + (index + 1) + '</span>'
      + '<div class="hs-landing-block-title-wrap">'
      + '<div class="hs-landing-block-title"><i class="fa-solid ' + esc(reg.icon || 'fa-cube') + '"></i> <strong>' + esc(reg.label || block.type) + '</strong></div>'
      + (regDesc ? '<p class="hs-landing-block-desc">' + esc(regDesc) + '</p>' : '')
      + '</div>'
      + '<button type="button" class="hs-landing-block-toggle" aria-expanded="' + (!collapsed) + '" title="' + esc(collapsed ? (MSG.spoiler_expand || 'Expand') : (MSG.spoiler_collapse || 'Collapse')) + '"><i class="fa-solid fa-chevron-down"></i></button>'
      + '<label class="hs-landing-vis-switch" title="' + esc(MSG.enabled || 'Show on page') + '">'
      + '<input type="checkbox" data-b-on' + index + ' ' + (block.on ? 'checked' : '') + '>'
      + '<span class="hs-landing-vis-track" aria-hidden="true"></span>'
      + '<span class="hs-landing-vis-text" data-vis-text' + index + '>' + esc(visLabel) + '</span></label>'
      + '<div class="hs-landing-block-actions">'
      + '<button type="button" class="hs-landing-icon-btn" data-move="up" data-idx="' + index + '" title="' + esc(MSG.move_up) + '"'
      + (index === 0 ? ' disabled' : '') + '><i class="fa-solid fa-chevron-up"></i></button>'
      + '<button type="button" class="hs-landing-icon-btn" data-move="down" data-idx="' + index + '" title="' + esc(MSG.move_down) + '"'
      + (index >= (state.blocks.length - 1) ? ' disabled' : '') + '><i class="fa-solid fa-chevron-down"></i></button>'
      + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove="' + index + '" title="' + esc(MSG.remove) + '"><i class="fa-solid fa-trash"></i></button>'
      + '</div></header>';

    var layoutInner = field(MSG.variant || 'Layout', '<select data-b-variant' + index + '>' + variantOpts + '</select>', 'variant')
      + blockColorField(index, block)
      + blockBackgroundFields(index, block)
      + blockElementColorFields(index, block);
    var contentInner = '';
    var itemsInner = '';

    html += '<div class="hs-landing-block-body"' + (block.on ? '' : ' hidden') + '>'
      + fieldGroup(MSG.field_group_layout || 'Layout', '', layoutInner);

    if (block.type === 'hero') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.subtitle, '<textarea rows="2" data-b-subtitle' + index + '>' + esc(block.subtitle) + '</textarea>', 'subtitle');
      contentInner += field(MSG.cta_text, '<input type="text" data-b-cta_text' + index + ' value="' + esc(block.cta_text) + '">', 'cta_text');
      contentInner += field(MSG.cta_url, '<input type="text" data-b-cta_url' + index + ' value="' + esc(block.cta_url) + '">', 'cta_url');
    } else if (block.type === 'features') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-feat-list" data-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-feat-item">'
          + '<div class="hs-landing-feat-item-head">'
          + '<span class="hs-landing-feat-item-num">#' + (ii + 1) + '</span>'
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-feat="' + index + '-' + ii + '" title="' + esc(MSG.remove_feat_item || MSG.remove || 'Remove') + '"'
          + ((block.items || []).length <= 1 ? ' disabled' : '') + '><i class="fa-solid fa-trash"></i></button>'
          + '</div>'
          + '<div class="hs-landing-feat-item-grid">'
          + iconPickerHtml(index, ii, item.icon)
          + '<div class="hs-landing-feat-fields">'
          + field(MSG.title, '<input type="text" data-b-item-title' + index + '-' + ii + ' value="' + esc(item.title) + '">', 'title')
          + field(MSG.text, '<textarea rows="2" data-b-item-text' + index + '-' + ii + '>' + esc(item.text) + '</textarea>', 'text')
          + '</div></div></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-item="' + index + '"><i class="fa-solid fa-plus"></i> ' + esc(MSG.add_item) + '</button>';
    } else if (block.type === 'about') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.text, '<textarea rows="3" data-b-text' + index + '>' + esc(block.text) + '</textarea>', 'text');
    } else if (block.type === 'gallery') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      var gHues = galleryHues(readFormMeta(), (block.items || []).length || 4);
      itemsInner += '<div class="hs-landing-items" data-gitems="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        var hue = item.hue != null ? item.hue : gHues[ii];
        var hasImg = !!(item.image);
        itemsInner += '<div class="hs-landing-gal-item">'
          + '<input type="hidden" data-b-gal-img' + index + '-' + ii + ' value="' + esc(item.image || '') + '">'
          + '<input type="hidden" data-b-gal-hue' + index + '-' + ii + ' value="' + esc(hue) + '">'
          + '<button type="button" class="hs-landing-gal-thumb' + (hasImg ? ' has-image' : '') + '" data-gal-pick="' + index + '-' + ii + '" style="' + galleryThumbStyle(item, hue) + '" title="' + esc(MSG.pick_photo || 'Choose photo') + '">'
          + (hasImg ? '' : '<i class="fa-solid fa-image"></i>') + '</button>'
          + '<input type="text" data-b-gal-cap' + index + '-' + ii + ' value="' + esc(item.caption) + '" placeholder="' + esc(MSG.caption) + '">'
          + '<button type="button" class="hs-landing-icon-btn" data-gal-clear="' + index + '-' + ii + '"' + (hasImg ? '' : ' disabled') + ' title="' + esc(MSG.clear_photo || 'Remove photo') + '"><i class="fa-solid fa-xmark"></i></button>'
          + '</div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-gal="' + index + '">+ ' + esc(MSG.add_gallery) + '</button>';
    } else if (block.type === 'info') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.text, '<textarea rows="2" data-b-text' + index + '>' + esc(block.text) + '</textarea>', 'text');
      contentInner += field(MSG.quote, '<input type="text" data-b-quote' + index + ' value="' + esc(block.quote) + '">', 'quote');
      contentInner += field(MSG.author, '<input type="text" data-b-author' + index + ' value="' + esc(block.author) + '">', 'author');
      itemsInner += '<div class="hs-landing-items" data-stats="' + index + '">';
      (block.stats || []).forEach(function (s, si) {
        itemsInner += '<div class="hs-landing-item-row"><input type="text" data-b-stat-val' + index + '-' + si + ' value="' + esc(s.value) + '" placeholder="' + esc(MSG.value) + '">'
          + '<input type="text" data-b-stat-lbl' + index + '-' + si + ' value="' + esc(s.label) + '" placeholder="' + esc(MSG.label) + '"></div>';
      });
      itemsInner += '</div>';
    } else if (block.type === 'contact') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.phone, '<input type="text" data-b-phone' + index + ' value="' + esc(block.phone) + '">', 'phone');
      contentInner += field(MSG.email, '<input type="email" data-b-email' + index + ' value="' + esc(block.email) + '">', 'email');
      contentInner += field(MSG.address, '<input type="text" data-b-address' + index + ' value="' + esc(block.address) + '">', 'address');
      contentInner += field(MSG.cta_text, '<input type="text" data-b-cta_text' + index + ' value="' + esc(block.cta_text) + '">', 'cta_text');
      contentInner += field(MSG.cta_url, '<input type="text" data-b-cta_url' + index + ' value="' + esc(block.cta_url) + '">', 'cta_url');
    } else if (block.type === 'cta') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.text, '<textarea rows="2" data-b-text' + index + '>' + esc(block.text) + '</textarea>', 'text');
      contentInner += field(MSG.cta_text, '<input type="text" data-b-cta_text' + index + ' value="' + esc(block.cta_text) + '">', 'cta_text');
      contentInner += field(MSG.cta_url, '<input type="text" data-b-cta_url' + index + ' value="' + esc(block.cta_url) + '">', 'cta_url');
    } else if (block.type === 'testimonials') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-test-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + field(MSG.title, '<input type="text" data-b-test-name' + index + '-' + ii + ' value="' + esc(item.name) + '">', 'title')
          + field(MSG.role || 'Role', '<input type="text" data-b-test-role' + index + '-' + ii + ' value="' + esc(item.role) + '">', 'role')
          + field(MSG.text, '<textarea rows="2" data-b-test-text' + index + '-' + ii + '>' + esc(item.text) + '</textarea>', 'text')
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-test="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-test="' + index + '">+ ' + esc(MSG.add_testimonial || 'Add review') + '</button>';
    } else if (block.type === 'pricing') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-price-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        var feats = (item.features || []).join('\n');
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + field(MSG.title, '<input type="text" data-b-price-name' + index + '-' + ii + ' value="' + esc(item.name) + '">', 'title')
          + '<div class="hs-landing-item-row"><input type="text" data-b-price-amt' + index + '-' + ii + ' value="' + esc(item.price) + '" placeholder="' + esc(MSG.price || 'Price') + '">'
          + '<input type="text" data-b-price-period' + index + '-' + ii + ' value="' + esc(item.period) + '" placeholder="' + esc(MSG.period || 'Period') + '"></div>'
          + field(MSG.features || 'Features', '<textarea rows="3" data-b-price-feats' + index + '-' + ii + '>' + esc(feats) + '</textarea>')
          + field(MSG.cta_text, '<input type="text" data-b-price-cta' + index + '-' + ii + ' value="' + esc(item.cta_text) + '">', 'cta_text')
          + '<label class="hs-landing-vis-switch"><input type="checkbox" data-b-price-feat' + index + '-' + ii + ' ' + (item.featured ? 'checked' : '') + '>'
          + '<span>' + esc(MSG.featured || 'Highlight') + '</span></label>'
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-price="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-price="' + index + '">+ ' + esc(MSG.add_plan || 'Add plan') + '</button>';
    } else if (block.type === 'faq') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-faq-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + field(MSG.question || 'Question', '<input type="text" data-b-faq-q' + index + '-' + ii + ' value="' + esc(item.q) + '">', 'question')
          + field(MSG.answer || 'Answer', '<textarea rows="2" data-b-faq-a' + index + '-' + ii + '>' + esc(item.a) + '</textarea>', 'answer')
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-faq="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-faq="' + index + '">+ ' + esc(MSG.add_faq || 'Add question') + '</button>';
    } else if (block.type === 'team') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-team-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + field(MSG.title, '<input type="text" data-b-team-name' + index + '-' + ii + ' value="' + esc(item.name) + '">', 'title')
          + field(MSG.role || 'Role', '<input type="text" data-b-team-role' + index + '-' + ii + ' value="' + esc(item.role) + '">', 'role')
          + field(MSG.bio || 'Bio', '<textarea rows="2" data-b-team-bio' + index + '-' + ii + '>' + esc(item.bio) + '</textarea>', 'bio')
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-team="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-team="' + index + '">+ ' + esc(MSG.add_member || 'Add member') + '</button>';
    } else if (block.type === 'logos') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-logo-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row">'
          + '<input type="text" data-b-logo-name' + index + '-' + ii + ' value="' + esc(item.name) + '" placeholder="' + esc(MSG.logo_name || 'Brand') + '">'
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-logo="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-logo="' + index + '">+ ' + esc(MSG.add_logo || 'Add logo') + '</button>';
    } else if (block.type === 'video') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      contentInner += field(MSG.video_url || 'Video URL', '<input type="url" data-b-video_url' + index + ' value="' + esc(block.video_url) + '">', 'video_url');
    } else if (block.type === 'newsletter') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.text, '<textarea rows="2" data-b-text' + index + '>' + esc(block.text) + '</textarea>', 'text');
      contentInner += field(MSG.cta_text, '<input type="text" data-b-cta_text' + index + ' value="' + esc(block.cta_text) + '">', 'cta_text');
    } else if (block.type === 'timeline') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-tl-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + field(MSG.year || 'Year', '<input type="text" data-b-tl-year' + index + '-' + ii + ' value="' + esc(item.year) + '">', 'year')
          + field(MSG.title, '<input type="text" data-b-tl-title' + index + '-' + ii + ' value="' + esc(item.title) + '">', 'title')
          + field(MSG.text, '<textarea rows="2" data-b-tl-text' + index + '-' + ii + '>' + esc(item.text) + '</textarea>', 'text')
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-tl="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-tl="' + index + '">+ ' + esc(MSG.add_timeline || 'Add') + '</button>';
    } else if (block.type === 'services') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-svc-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + iconPickerHtml(index, ii, item.icon)
          + field(MSG.title, '<input type="text" data-b-svc-title' + index + '-' + ii + ' value="' + esc(item.title) + '">', 'title')
          + field(MSG.price_label || 'Price', '<input type="text" data-b-svc-price' + index + '-' + ii + ' value="' + esc(item.price) + '">')
          + field(MSG.text, '<textarea rows="2" data-b-svc-text' + index + '-' + ii + '>' + esc(item.text) + '</textarea>', 'text')
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-svc="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-svc="' + index + '">+ ' + esc(MSG.add_service || 'Add') + '</button>';
    } else if (block.type === 'heading') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.subtitle, '<input type="text" data-b-subtitle' + index + ' value="' + esc(block.subtitle) + '">', 'subtitle');
    } else if (block.type === 'text') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.text, '<textarea rows="4" data-b-text' + index + '>' + esc(block.text) + '</textarea>', 'text');
    } else if (block.type === 'image') {
      var hasImgB = !!(block.image);
      contentInner += '<input type="hidden" data-b-image' + index + ' value="' + esc(block.image || '') + '">'
        + field(MSG.caption, '<input type="text" data-b-caption' + index + ' value="' + esc(block.caption) + '">', 'caption')
        + '<button type="button" class="hs-landing-gal-thumb' + (hasImgB ? ' has-image' : '') + '" data-gal-pick="' + index + '-img" style="' + (hasImgB ? 'background-image:url(' + esc(galleryImageUrl(block.image)) + ')' : '') + '" title="' + esc(MSG.pick_photo || 'Photo') + '">'
        + (hasImgB ? '' : '<i class="fa-solid fa-image"></i>') + '</button>';
    } else if (block.type === 'hours') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      itemsInner += '<div class="hs-landing-items" data-hours-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row">'
          + '<input type="text" data-b-hours-day' + index + '-' + ii + ' value="' + esc(item.day) + '" placeholder="' + esc(MSG.day || 'Day') + '">'
          + '<input type="text" data-b-hours-time' + index + '-' + ii + ' value="' + esc(item.hours) + '" placeholder="' + esc(MSG.hours || 'Hours') + '">'
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-hours="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-hours="' + index + '">+ ' + esc(MSG.add_hours || 'Add') + '</button>';
    } else if (block.type === 'map') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.address, '<input type="text" data-b-address' + index + ' value="' + esc(block.address) + '">', 'address');
      contentInner += field(MSG.embed_url || 'Embed URL', '<input type="url" data-b-embed_url' + index + ' value="' + esc(block.embed_url) + '">', 'embed_url');
    } else if (block.type === 'banner') {
      contentInner += field(MSG.text, '<input type="text" data-b-text' + index + ' value="' + esc(block.text) + '">', 'text');
      contentInner += field(MSG.cta_text, '<input type="text" data-b-cta_text' + index + ' value="' + esc(block.cta_text) + '">', 'cta_text');
      contentInner += field(MSG.cta_url, '<input type="text" data-b-cta_url' + index + ' value="' + esc(block.cta_url) + '">', 'cta_url');
    } else if (block.type === 'quote') {
      contentInner += field(MSG.quote, '<textarea rows="2" data-b-quote' + index + '>' + esc(block.quote) + '</textarea>', 'quote');
      contentInner += field(MSG.author, '<input type="text" data-b-author' + index + ' value="' + esc(block.author) + '">', 'author');
      contentInner += field(MSG.role || 'Role', '<input type="text" data-b-role' + index + ' value="' + esc(block.role) + '">', 'role');
    } else if (block.type === 'download') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-dl-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + field(MSG.title, '<input type="text" data-b-dl-label' + index + '-' + ii + ' value="' + esc(item.label) + '">', 'title')
          + field(MSG.file_url || 'URL', '<input type="text" data-b-dl-url' + index + '-' + ii + ' value="' + esc(item.url) + '">', 'file_url')
          + field(MSG.file_size || 'Size', '<input type="text" data-b-dl-size' + index + '-' + ii + ' value="' + esc(item.size) + '">')
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-dl="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-dl="' + index + '">+ ' + esc(MSG.add_download || 'Add') + '</button>';
    } else if (block.type === 'alert') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.text, '<textarea rows="2" data-b-text' + index + '>' + esc(block.text) + '</textarea>', 'text');
    } else if (block.type === 'events') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-ev-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + field(MSG.date || 'Date', '<input type="text" data-b-ev-date' + index + '-' + ii + ' value="' + esc(item.date) + '">', 'date')
          + field(MSG.title, '<input type="text" data-b-ev-title' + index + '-' + ii + ' value="' + esc(item.title) + '">', 'title')
          + field(MSG.location || 'Location', '<input type="text" data-b-ev-loc' + index + '-' + ii + ' value="' + esc(item.location) + '">', 'location')
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-ev="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-ev="' + index + '">+ ' + esc(MSG.add_event || 'Add') + '</button>';
    } else if (block.type === 'steps') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-step-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + field(MSG.title, '<input type="text" data-b-step-title' + index + '-' + ii + ' value="' + esc(item.title) + '">', 'title')
          + field(MSG.text, '<textarea rows="2" data-b-step-text' + index + '-' + ii + '>' + esc(item.text) + '</textarea>', 'text')
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-step="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-step="' + index + '">+ ' + esc(MSG.add_step || 'Add') + '</button>';
    } else if (block.type === 'countdown') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.text, '<input type="text" data-b-text' + index + ' value="' + esc(block.text) + '">', 'text');
      contentInner += field(MSG.countdown_date || 'Date', '<input type="date" data-b-countdown_date' + index + ' value="' + esc(block.countdown_date) + '">', 'countdown_date');
    } else if (block.type === 'columns') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-col-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + field(MSG.title, '<input type="text" data-b-col-title' + index + '-' + ii + ' value="' + esc(item.title) + '">', 'title')
          + field(MSG.text, '<textarea rows="2" data-b-col-text' + index + '-' + ii + '>' + esc(item.text) + '</textarea>', 'text')
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-col="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-col="' + index + '">+ ' + esc(MSG.add_column || 'Add') + '</button>';
    } else if (block.type === 'badges') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-badge-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row">'
          + '<input type="text" data-b-badge-label' + index + '-' + ii + ' value="' + esc(item.label) + '" placeholder="Tag">'
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-badge="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-badge="' + index + '">+ ' + esc(MSG.add_badge || 'Add') + '</button>';
    } else if (block.type === 'buttons') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title || '') + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-btn-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-stack"><input type="text" data-b-btn-text' + index + '-' + ii + ' value="' + esc(item.text) + '" placeholder="Text">'
          + '<input type="text" data-b-btn-url' + index + '-' + ii + ' value="' + esc(item.url) + '" placeholder="URL">'
          + '<select data-b-btn-style' + index + '-' + ii + '><option value="primary"' + (item.style === 'primary' ? ' selected' : '') + '>Primary</option><option value="secondary"' + (item.style === 'secondary' ? ' selected' : '') + '>Secondary</option><option value="outline"' + (item.style === 'outline' ? ' selected' : '') + '>Outline</option><option value="ghost"' + (item.style === 'ghost' ? ' selected' : '') + '>Ghost</option><option value="link"' + (item.style === 'link' ? ' selected' : '') + '>Link</option></select></div>';
      });
      itemsInner += '</div>';
    } else if (block.type === 'cards') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-card-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-stack"><input type="text" data-b-card-icon' + index + '-' + ii + ' value="' + esc(item.icon) + '" placeholder="fa-star">'
          + '<input type="text" data-b-card-title' + index + '-' + ii + ' value="' + esc(item.title) + '"><textarea rows="2" data-b-card-text' + index + '-' + ii + '>' + esc(item.text) + '</textarea>'
          + '<input type="text" data-b-card-cta' + index + '-' + ii + ' value="' + esc(item.cta_text) + '" placeholder="CTA text"><input type="text" data-b-card-ctaurl' + index + '-' + ii + ' value="' + esc(item.cta_url) + '" placeholder="CTA URL"></div>';
      });
      itemsInner += '</div>';
    } else if (block.type === 'social') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-social-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-stack"><input type="text" data-b-social-net' + index + '-' + ii + ' value="' + esc(item.network) + '" placeholder="facebook">'
          + '<input type="text" data-b-social-label' + index + '-' + ii + ' value="' + esc(item.label) + '"><input type="url" data-b-social-url' + index + '-' + ii + ' value="' + esc(item.url) + '"></div>';
      });
      itemsInner += '</div>';
    } else if (block.type === 'trust' || block.type === 'icon_list') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title || '') + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-iconlist-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-stack"><input type="text" data-b-ilist-icon' + index + '-' + ii + ' value="' + esc(item.icon) + '">'
          + '<input type="text" data-b-ilist-title' + index + '-' + ii + ' value="' + esc(item.title) + '"><textarea rows="2" data-b-ilist-text' + index + '-' + ii + '>' + esc(item.text) + '</textarea></div>';
      });
      itemsInner += '</div>';
    } else if (block.type === 'callout') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.text, '<textarea rows="2" data-b-text' + index + '>' + esc(block.text) + '</textarea>', 'text');
      contentInner += field(MSG.cta_text, '<input type="text" data-b-cta_text' + index + ' value="' + esc(block.cta_text) + '">', 'cta_text');
      contentInner += field(MSG.cta_url, '<input type="text" data-b-cta_url' + index + ' value="' + esc(block.cta_url) + '">', 'cta_url');
    } else if (block.type === 'media_text') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.text, '<textarea rows="3" data-b-text' + index + '>' + esc(block.text) + '</textarea>', 'text');
      contentInner += field(MSG.caption, '<input type="text" data-b-image' + index + ' value="' + esc(block.image) + '" placeholder="Image URL">', 'caption');
      contentInner += field('Side', '<select data-b-side' + index + '><option value="left"' + (block.side === 'left' ? ' selected' : '') + '>Left</option><option value="right"' + (block.side === 'right' ? ' selected' : '') + '>Right</option></select>');
      contentInner += field(MSG.cta_text, '<input type="text" data-b-cta_text' + index + ' value="' + esc(block.cta_text) + '">', 'cta_text');
      contentInner += field(MSG.cta_url, '<input type="text" data-b-cta_url' + index + ' value="' + esc(block.cta_url) + '">', 'cta_url');
    } else if (block.type === 'menu') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-menu-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-stack"><input type="text" data-b-menu-name' + index + '-' + ii + ' value="' + esc(item.name) + '">'
          + '<input type="text" data-b-menu-desc' + index + '-' + ii + ' value="' + esc(item.desc) + '"><input type="text" data-b-menu-price' + index + '-' + ii + ' value="' + esc(item.price) + '"></div>';
      });
      itemsInner += '</div>';
    } else if (block.type === 'stats_bar') {
      itemsInner += '<div class="hs-landing-items" data-statsbar-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row"><input type="text" data-b-sbar-val' + index + '-' + ii + ' value="' + esc(item.value) + '"><input type="text" data-b-sbar-lbl' + index + '-' + ii + ' value="' + esc(item.label) + '"></div>';
      });
      itemsInner += '</div>';
    } else if (block.type === 'comparison') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      contentInner += field('Left', '<input type="text" data-b-left_title' + index + ' value="' + esc(block.left_title) + '">');
      contentInner += field('Right', '<input type="text" data-b-right_title' + index + ' value="' + esc(block.right_title) + '">');
      itemsInner += '<div class="hs-landing-items" data-cmp-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-stack"><input type="text" data-b-cmp-label' + index + '-' + ii + ' value="' + esc(item.label) + '">'
          + '<input type="text" data-b-cmp-a' + index + '-' + ii + ' value="' + esc(item.a) + '"><input type="text" data-b-cmp-b' + index + '-' + ii + ' value="' + esc(item.b) + '"></div>';
      });
      itemsInner += '</div>';
    } else if (block.type === 'contact_bar') {
      contentInner += field(MSG.phone, '<input type="text" data-b-phone' + index + ' value="' + esc(block.phone) + '">', 'phone');
      contentInner += field(MSG.email, '<input type="email" data-b-email' + index + ' value="' + esc(block.email) + '">', 'email');
      contentInner += field('WhatsApp', '<input type="text" data-b-whatsapp' + index + ' value="' + esc(block.whatsapp || '') + '">');
      contentInner += field(MSG.cta_text, '<input type="text" data-b-cta_text' + index + ' value="' + esc(block.cta_text) + '">', 'cta_text');
      contentInner += field(MSG.cta_url, '<input type="text" data-b-cta_url' + index + ' value="' + esc(block.cta_url) + '">', 'cta_url');
    } else if (block.type === 'app_cta') {
      contentInner += field(MSG.title, '<input type="text" data-b-title' + index + ' value="' + esc(block.title) + '">', 'title');
      contentInner += field(MSG.text, '<textarea rows="2" data-b-text' + index + '>' + esc(block.text) + '</textarea>', 'text');
      contentInner += field('App Store URL', '<input type="url" data-b-ios' + index + ' value="' + esc(block.ios_url) + '">');
      contentInner += field('Google Play URL', '<input type="url" data-b-android' + index + ' value="' + esc(block.android_url) + '">');
    } else if (block.type === 'messengers') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-msg-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        var chOpts = Object.keys(cfg.messengerChannels || {}).map(function (ck) {
          var sel = (item.channel || '') === ck ? ' selected' : '';
          return '<option value="' + esc(ck) + '"' + sel + '>' + esc((cfg.messengerChannels[ck] || {}).label || ck) + '</option>';
        }).join('');
        itemsInner += '<div class="hs-landing-item-stack"><select data-b-msg-ch' + index + '-' + ii + '>' + chOpts + '</select>'
          + '<input type="text" data-b-msg-val' + index + '-' + ii + ' value="' + esc(item.value) + '" placeholder="phone / @user">'
          + '<input type="text" data-b-msg-label' + index + '-' + ii + ' value="' + esc(item.label) + '" placeholder="Label"></div>';
      });
      itemsInner += '</div>';
    } else if (block.type === 'slider') {
      var hOpts = '';
      Object.keys(cfg.sliderHeights || {}).forEach(function (hk) {
        hOpts += '<option value="' + esc(hk) + '"' + ((block.height || 'md') === hk ? ' selected' : '') + '>' + esc(cfg.sliderHeights[hk]) + '</option>';
      });
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title || '') + '">', 'section_title');
      contentInner += field(MSG.slider_height || 'Height', '<select data-b-height' + index + '>' + hOpts + '</select>');
      contentInner += '<label class="hs-landing-vis-switch"><input type="checkbox" data-b-autoplay' + index + ' ' + (block.autoplay !== false ? 'checked' : '') + '><span>' + esc(MSG.slider_autoplay || 'Autoplay') + '</span></label>';
      contentInner += field(MSG.slider_interval || 'Interval (ms)', '<input type="number" min="2000" max="15000" step="500" data-b-interval' + index + ' value="' + esc(String(block.interval || 5000)) + '">');
      contentInner += '<label class="hs-landing-vis-switch"><input type="checkbox" data-b-arrows' + index + ' ' + (block.arrows !== false ? 'checked' : '') + '><span>' + esc(MSG.slider_arrows || 'Arrows') + '</span></label>';
      contentInner += '<label class="hs-landing-vis-switch"><input type="checkbox" data-b-dots' + index + ' ' + (block.dots !== false ? 'checked' : '') + '><span>' + esc(MSG.slider_dots || 'Dots') + '</span></label>';
      var sHues = galleryHues(readFormMeta(), (block.items || []).length || 3);
      itemsInner += '<div class="hs-landing-items" data-sitems="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        var hue = item.hue != null ? item.hue : sHues[ii];
        var hasImg = !!(item.image);
        itemsInner += '<div class="hs-landing-gal-item hs-landing-slide-item">'
          + '<input type="hidden" data-b-sld-img' + index + '-' + ii + ' value="' + esc(item.image || '') + '">'
          + '<input type="hidden" data-b-sld-hue' + index + '-' + ii + ' value="' + esc(hue) + '">'
          + '<button type="button" class="hs-landing-gal-thumb' + (hasImg ? ' has-image' : '') + '" data-sld-pick="' + index + '-s' + ii + '" style="' + galleryThumbStyle(item, hue) + '" title="' + esc(MSG.pick_photo || 'Choose photo') + '">'
          + (hasImg ? '' : '<i class="fa-solid fa-image"></i>') + '</button>'
          + field(MSG.title, '<input type="text" data-b-sld-title' + index + '-' + ii + ' value="' + esc(item.title) + '">', 'title')
          + field(MSG.subtitle, '<input type="text" data-b-sld-sub' + index + '-' + ii + ' value="' + esc(item.subtitle) + '">', 'subtitle')
          + field(MSG.cta_text, '<input type="text" data-b-sld-cta' + index + '-' + ii + ' value="' + esc(item.cta_text) + '">', 'cta_text')
          + field(MSG.cta_url, '<input type="text" data-b-sld-ctaurl' + index + '-' + ii + ' value="' + esc(item.cta_url) + '">', 'cta_url')
          + '<button type="button" class="hs-landing-icon-btn" data-sld-clear="' + index + '-s' + ii + '"' + (hasImg ? '' : ' disabled') + ' title="' + esc(MSG.clear_photo || 'Remove photo') + '"><i class="fa-solid fa-xmark"></i></button>'
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-sld="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button>'
          + '</div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-sld="' + index + '">+ ' + esc(MSG.add_slide || 'Add slide') + '</button>';
    } else if (block.type === 'accordion') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-acc-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + field(MSG.title, '<input type="text" data-b-acc-title' + index + '-' + ii + ' value="' + esc(item.title) + '">', 'title')
          + field(MSG.text, '<textarea rows="2" data-b-acc-text' + index + '-' + ii + '>' + esc(item.text) + '</textarea>', 'text')
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-acc="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-acc="' + index + '">+ ' + esc(MSG.add_item) + '</button>';
    } else if (block.type === 'tabs') {
      contentInner += field(MSG.section_title, '<input type="text" data-b-section_title' + index + ' value="' + esc(block.section_title) + '">', 'section_title');
      itemsInner += '<div class="hs-landing-items" data-tab-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row hs-landing-item-stack">'
          + field(MSG.title, '<input type="text" data-b-tab-title' + index + '-' + ii + ' value="' + esc(item.title) + '">', 'title')
          + field(MSG.text, '<textarea rows="2" data-b-tab-text' + index + '-' + ii + '>' + esc(item.text) + '</textarea>', 'text')
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-tab="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-tab="' + index + '">+ ' + esc(MSG.add_item) + '</button>';
    } else if (block.type === 'marquee') {
      var speedOpts = ['slow', 'normal', 'fast'].map(function (sp) {
        return '<option value="' + sp + '"' + ((block.speed || 'normal') === sp ? ' selected' : '') + '>' + esc(MSG['marquee_speed_' + sp] || sp) + '</option>';
      }).join('');
      contentInner += field(MSG.text, '<input type="text" data-b-text' + index + ' value="' + esc(block.text) + '">', 'text');
      contentInner += field(MSG.marquee_speed || 'Speed', '<select data-b-speed' + index + '>' + speedOpts + '</select>');
      itemsInner += '<div class="hs-landing-items" data-mq-items="' + index + '">';
      (block.items || []).forEach(function (item, ii) {
        itemsInner += '<div class="hs-landing-item-row">'
          + '<input type="text" data-b-mq-label' + index + '-' + ii + ' value="' + esc(item.label) + '" placeholder="Tag">'
          + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-remove-mq="' + index + '-' + ii + '"><i class="fa-solid fa-trash"></i></button></div>';
      });
      itemsInner += '</div><button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-add-mq="' + index + '">+ ' + esc(MSG.add_badge) + '</button>';
    }

    if (contentInner) {
      html += fieldGroup(MSG.field_group_content || 'Content', '', '<div class="elb-fields-stack">' + contentInner + '</div>');
    }
    if (itemsInner) {
      html += fieldGroup(MSG.field_group_items || 'Items', '', '<div class="elb-fields-stack">' + itemsInner + '</div>');
    }

    html += '</div></article>';
    return html;
  }

  function readBlockFromDom(index) {
    var block = JSON.parse(JSON.stringify(state.blocks[index]));
    var q = function (sel) { return blocksEl.querySelector(sel); };
    block.on = !!(q('[data-b-on' + index + ']') || {}).checked;
    block.variant = (q('[data-b-variant' + index + ']') || {}).value || block.variant;
    var colorText = (q('[data-b-color-text' + index + ']') || {}).value || '';
    block.color = /^#[0-9a-fA-F]{6}$/.test(colorText) ? colorText : '';
    block.text_color = readHexColorInput((q('[data-b-tcolor-text' + index + ']') || {}).value);
    block.heading_color = readHexColorInput((q('[data-b-hcolor-text' + index + ']') || {}).value);
    block.btn_color = readHexColorInput((q('[data-b-btncolor-text' + index + ']') || {}).value);
    block.btn_text_color = readHexColorInput((q('[data-b-btntextcolor-text' + index + ']') || {}).value);
    block.bg_type = (q('[data-b-bg-type' + index + ']') || {}).value || '';
    if (!['', 'color', 'image'].includes(block.bg_type)) block.bg_type = '';
    block.bg_color = readHexColorInput((q('[data-b-bg-color-text' + index + ']') || {}).value);
    block.bg_image = (q('[data-b-bg-image' + index + ']') || {}).value || '';
    if (block.bg_image && !block.bg_type) block.bg_type = 'image';
    block.bg_overlay = parseInt((q('[data-b-bg-overlay' + index + ']') || {}).value || '40', 10);

    if (block.type === 'hero') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.subtitle = (q('[data-b-subtitle' + index + ']') || {}).value || '';
      block.cta_text = (q('[data-b-cta_text' + index + ']') || {}).value || '';
      block.cta_url = (q('[data-b-cta_url' + index + ']') || {}).value || '';
    } else if (block.type === 'features') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      (block.items || []);
      var rows = blocksEl.querySelectorAll('[data-items="' + index + '"] .hs-landing-feat-item');
      rows.forEach(function (row, ii) {
        block.items.push({
          icon: (row.querySelector('[data-b-item-icon' + index + '-' + ii + ']') || {}).value || 'fa-star',
          title: (row.querySelector('[data-b-item-title' + index + '-' + ii + ']') || {}).value || '',
          text: (row.querySelector('[data-b-item-text' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'about') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
    } else if (block.type === 'gallery') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      var grows = blocksEl.querySelectorAll('[data-gitems="' + index + '"] .hs-landing-gal-item');
      grows.forEach(function (row, ii) {
        block.items.push({
          caption: (row.querySelector('[data-b-gal-cap' + index + '-' + ii + ']') || {}).value || '',
          image: (row.querySelector('[data-b-gal-img' + index + '-' + ii + ']') || {}).value || '',
          hue: parseInt((row.querySelector('[data-b-gal-hue' + index + '-' + ii + ']') || {}).value || '160', 10),
        });
      });
    } else if (block.type === 'info') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
      block.quote = (q('[data-b-quote' + index + ']') || {}).value || '';
      block.author = (q('[data-b-author' + index + ']') || {}).value || '';
      block.stats = [];
      var srows = blocksEl.querySelectorAll('[data-stats="' + index + '"] .hs-landing-item-row');
      srows.forEach(function (row, si) {
        block.stats.push({
          value: (row.querySelector('[data-b-stat-val' + index + '-' + si + ']') || {}).value || '',
          label: (row.querySelector('[data-b-stat-lbl' + index + '-' + si + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'contact') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.phone = (q('[data-b-phone' + index + ']') || {}).value || '';
      block.email = (q('[data-b-email' + index + ']') || {}).value || '';
      block.address = (q('[data-b-address' + index + ']') || {}).value || '';
      block.cta_text = (q('[data-b-cta_text' + index + ']') || {}).value || '';
      block.cta_url = (q('[data-b-cta_url' + index + ']') || {}).value || '';
    } else if (block.type === 'cta') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
      block.cta_text = (q('[data-b-cta_text' + index + ']') || {}).value || '';
      block.cta_url = (q('[data-b-cta_url' + index + ']') || {}).value || '';
    } else if (block.type === 'testimonials') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-test-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          name: (row.querySelector('[data-b-test-name' + index + '-' + ii + ']') || {}).value || '',
          role: (row.querySelector('[data-b-test-role' + index + '-' + ii + ']') || {}).value || '',
          text: (row.querySelector('[data-b-test-text' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'pricing') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-price-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        var featsRaw = (row.querySelector('[data-b-price-feats' + index + '-' + ii + ']') || {}).value || '';
        block.items.push({
          name: (row.querySelector('[data-b-price-name' + index + '-' + ii + ']') || {}).value || '',
          price: (row.querySelector('[data-b-price-amt' + index + '-' + ii + ']') || {}).value || '',
          period: (row.querySelector('[data-b-price-period' + index + '-' + ii + ']') || {}).value || '',
          features: featsRaw.split('\n').map(function (s) { return s.trim(); }).filter(Boolean),
          cta_text: (row.querySelector('[data-b-price-cta' + index + '-' + ii + ']') || {}).value || '',
          featured: !!(row.querySelector('[data-b-price-feat' + index + '-' + ii + ']') || {}).checked,
        });
      });
    } else if (block.type === 'faq') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-faq-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          q: (row.querySelector('[data-b-faq-q' + index + '-' + ii + ']') || {}).value || '',
          a: (row.querySelector('[data-b-faq-a' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'team') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-team-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          name: (row.querySelector('[data-b-team-name' + index + '-' + ii + ']') || {}).value || '',
          role: (row.querySelector('[data-b-team-role' + index + '-' + ii + ']') || {}).value || '',
          bio: (row.querySelector('[data-b-team-bio' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'logos') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-logo-items="' + index + '"] .hs-landing-item-row').forEach(function (row, ii) {
        var nm = (row.querySelector('[data-b-logo-name' + index + '-' + ii + ']') || {}).value || '';
        if (nm) block.items.push({ name: nm });
      });
    } else if (block.type === 'video') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.video_url = (q('[data-b-video_url' + index + ']') || {}).value || '';
    } else if (block.type === 'newsletter') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
      block.cta_text = (q('[data-b-cta_text' + index + ']') || {}).value || '';
    } else if (block.type === 'timeline') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-tl-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          year: (row.querySelector('[data-b-tl-year' + index + '-' + ii + ']') || {}).value || '',
          title: (row.querySelector('[data-b-tl-title' + index + '-' + ii + ']') || {}).value || '',
          text: (row.querySelector('[data-b-tl-text' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'services') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-svc-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          icon: (row.querySelector('[data-b-item-icon' + index + '-' + ii + ']') || {}).value || 'fa-star',
          title: (row.querySelector('[data-b-svc-title' + index + '-' + ii + ']') || {}).value || '',
          price: (row.querySelector('[data-b-svc-price' + index + '-' + ii + ']') || {}).value || '',
          text: (row.querySelector('[data-b-svc-text' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'heading') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.subtitle = (q('[data-b-subtitle' + index + ']') || {}).value || '';
    } else if (block.type === 'text') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
    } else if (block.type === 'image') {
      block.image = (q('[data-b-image' + index + ']') || {}).value || '';
      block.caption = (q('[data-b-caption' + index + ']') || {}).value || '';
    } else if (block.type === 'hours') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-hours-items="' + index + '"] .hs-landing-item-row').forEach(function (row, ii) {
        block.items.push({
          day: (row.querySelector('[data-b-hours-day' + index + '-' + ii + ']') || {}).value || '',
          hours: (row.querySelector('[data-b-hours-time' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'map') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.address = (q('[data-b-address' + index + ']') || {}).value || '';
      block.embed_url = (q('[data-b-embed_url' + index + ']') || {}).value || '';
    } else if (block.type === 'banner') {
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
      block.cta_text = (q('[data-b-cta_text' + index + ']') || {}).value || '';
      block.cta_url = (q('[data-b-cta_url' + index + ']') || {}).value || '';
    } else if (block.type === 'quote') {
      block.quote = (q('[data-b-quote' + index + ']') || {}).value || '';
      block.author = (q('[data-b-author' + index + ']') || {}).value || '';
      block.role = (q('[data-b-role' + index + ']') || {}).value || '';
    } else if (block.type === 'download') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-dl-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          label: (row.querySelector('[data-b-dl-label' + index + '-' + ii + ']') || {}).value || '',
          url: (row.querySelector('[data-b-dl-url' + index + '-' + ii + ']') || {}).value || '',
          size: (row.querySelector('[data-b-dl-size' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'alert') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
    } else if (block.type === 'events') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-ev-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          date: (row.querySelector('[data-b-ev-date' + index + '-' + ii + ']') || {}).value || '',
          title: (row.querySelector('[data-b-ev-title' + index + '-' + ii + ']') || {}).value || '',
          location: (row.querySelector('[data-b-ev-loc' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'steps') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-step-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          title: (row.querySelector('[data-b-step-title' + index + '-' + ii + ']') || {}).value || '',
          text: (row.querySelector('[data-b-step-text' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'countdown') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
      block.countdown_date = (q('[data-b-countdown_date' + index + ']') || {}).value || '';
    } else if (block.type === 'columns') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-col-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          title: (row.querySelector('[data-b-col-title' + index + '-' + ii + ']') || {}).value || '',
          text: (row.querySelector('[data-b-col-text' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'badges') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-badge-items="' + index + '"] .hs-landing-item-row').forEach(function (row, ii) {
        var lb = (row.querySelector('[data-b-badge-label' + index + '-' + ii + ']') || {}).value || '';
        if (lb) block.items.push({ label: lb });
      });
    } else if (block.type === 'buttons') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-btn-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          text: (row.querySelector('[data-b-btn-text' + index + '-' + ii + ']') || {}).value || '',
          url: (row.querySelector('[data-b-btn-url' + index + '-' + ii + ']') || {}).value || '#',
          style: (row.querySelector('[data-b-btn-style' + index + '-' + ii + ']') || {}).value || 'primary',
        });
      });
    } else if (block.type === 'cards') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-card-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          icon: (row.querySelector('[data-b-card-icon' + index + '-' + ii + ']') || {}).value || 'fa-star',
          title: (row.querySelector('[data-b-card-title' + index + '-' + ii + ']') || {}).value || '',
          text: (row.querySelector('[data-b-card-text' + index + '-' + ii + ']') || {}).value || '',
          cta_text: (row.querySelector('[data-b-card-cta' + index + '-' + ii + ']') || {}).value || '',
          cta_url: (row.querySelector('[data-b-card-ctaurl' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'social') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-social-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          network: (row.querySelector('[data-b-social-net' + index + '-' + ii + ']') || {}).value || 'link',
          label: (row.querySelector('[data-b-social-label' + index + '-' + ii + ']') || {}).value || '',
          url: (row.querySelector('[data-b-social-url' + index + '-' + ii + ']') || {}).value || '#',
        });
      });
    } else if (block.type === 'trust' || block.type === 'icon_list') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-iconlist-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          icon: (row.querySelector('[data-b-ilist-icon' + index + '-' + ii + ']') || {}).value || 'fa-check',
          title: (row.querySelector('[data-b-ilist-title' + index + '-' + ii + ']') || {}).value || '',
          text: (row.querySelector('[data-b-ilist-text' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'callout') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
      block.cta_text = (q('[data-b-cta_text' + index + ']') || {}).value || '';
      block.cta_url = (q('[data-b-cta_url' + index + ']') || {}).value || '';
    } else if (block.type === 'media_text') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
      block.image = (q('[data-b-image' + index + ']') || {}).value || '';
      block.side = (q('[data-b-side' + index + ']') || {}).value || 'left';
      block.cta_text = (q('[data-b-cta_text' + index + ']') || {}).value || '';
      block.cta_url = (q('[data-b-cta_url' + index + ']') || {}).value || '';
    } else if (block.type === 'menu') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-menu-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          name: (row.querySelector('[data-b-menu-name' + index + '-' + ii + ']') || {}).value || '',
          desc: (row.querySelector('[data-b-menu-desc' + index + '-' + ii + ']') || {}).value || '',
          price: (row.querySelector('[data-b-menu-price' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'stats_bar') {
      block.items = [];
      blocksEl.querySelectorAll('[data-statsbar-items="' + index + '"] .hs-landing-item-row').forEach(function (row, ii) {
        block.items.push({
          value: (row.querySelector('[data-b-sbar-val' + index + '-' + ii + ']') || {}).value || '',
          label: (row.querySelector('[data-b-sbar-lbl' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'comparison') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.left_title = (q('[data-b-left_title' + index + ']') || {}).value || '';
      block.right_title = (q('[data-b-right_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-cmp-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          label: (row.querySelector('[data-b-cmp-label' + index + '-' + ii + ']') || {}).value || '',
          a: (row.querySelector('[data-b-cmp-a' + index + '-' + ii + ']') || {}).value || '',
          b: (row.querySelector('[data-b-cmp-b' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'contact_bar') {
      block.phone = (q('[data-b-phone' + index + ']') || {}).value || '';
      block.email = (q('[data-b-email' + index + ']') || {}).value || '';
      block.whatsapp = (q('[data-b-whatsapp' + index + ']') || {}).value || '';
      block.cta_text = (q('[data-b-cta_text' + index + ']') || {}).value || '';
      block.cta_url = (q('[data-b-cta_url' + index + ']') || {}).value || '';
    } else if (block.type === 'app_cta') {
      block.title = (q('[data-b-title' + index + ']') || {}).value || '';
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
      block.ios_url = (q('[data-b-ios' + index + ']') || {}).value || '#';
      block.android_url = (q('[data-b-android' + index + ']') || {}).value || '#';
    } else if (block.type === 'messengers') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-msg-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          channel: (row.querySelector('[data-b-msg-ch' + index + '-' + ii + ']') || {}).value || 'whatsapp',
          value: (row.querySelector('[data-b-msg-val' + index + '-' + ii + ']') || {}).value || '',
          label: (row.querySelector('[data-b-msg-label' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'slider') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.height = (q('[data-b-height' + index + ']') || {}).value || 'md';
      block.autoplay = !!(q('[data-b-autoplay' + index + ']') || {}).checked;
      block.interval = parseInt((q('[data-b-interval' + index + ']') || {}).value || '5000', 10) || 5000;
      block.arrows = !!(q('[data-b-arrows' + index + ']') || {}).checked;
      block.dots = !!(q('[data-b-dots' + index + ']') || {}).checked;
      block.items = [];
      blocksEl.querySelectorAll('[data-sitems="' + index + '"] .hs-landing-slide-item').forEach(function (row, ii) {
        block.items.push({
          image: (row.querySelector('[data-b-sld-img' + index + '-' + ii + ']') || {}).value || '',
          hue: parseInt((row.querySelector('[data-b-sld-hue' + index + '-' + ii + ']') || {}).value || '160', 10),
          title: (row.querySelector('[data-b-sld-title' + index + '-' + ii + ']') || {}).value || '',
          subtitle: (row.querySelector('[data-b-sld-sub' + index + '-' + ii + ']') || {}).value || '',
          cta_text: (row.querySelector('[data-b-sld-cta' + index + '-' + ii + ']') || {}).value || '',
          cta_url: (row.querySelector('[data-b-sld-ctaurl' + index + '-' + ii + ']') || {}).value || '#',
        });
      });
    } else if (block.type === 'accordion') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-acc-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          title: (row.querySelector('[data-b-acc-title' + index + '-' + ii + ']') || {}).value || '',
          text: (row.querySelector('[data-b-acc-text' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'tabs') {
      block.section_title = (q('[data-b-section_title' + index + ']') || {}).value || '';
      block.items = [];
      blocksEl.querySelectorAll('[data-tab-items="' + index + '"] .hs-landing-item-stack').forEach(function (row, ii) {
        block.items.push({
          title: (row.querySelector('[data-b-tab-title' + index + '-' + ii + ']') || {}).value || '',
          text: (row.querySelector('[data-b-tab-text' + index + '-' + ii + ']') || {}).value || '',
        });
      });
    } else if (block.type === 'marquee') {
      block.text = (q('[data-b-text' + index + ']') || {}).value || '';
      block.speed = (q('[data-b-speed' + index + ']') || {}).value || 'normal';
      block.items = [];
      blocksEl.querySelectorAll('[data-mq-items="' + index + '"] .hs-landing-item-row').forEach(function (row, ii) {
        var lb = (row.querySelector('[data-b-mq-label' + index + '-' + ii + ']') || {}).value || '';
        if (lb) block.items.push({ label: lb });
      });
    }
    return block;
  }

  function pullBlocksFromDom() {
    state.blocks = state.blocks.map(function (_, i) { return readBlockFromDom(i); });
  }

  function moveBlock(from, to) {
    if (from === to || from < 0 || to < 0 || from >= state.blocks.length || to >= state.blocks.length) {
      return;
    }
    pullBlocksFromDom();
    var item = state.blocks.splice(from, 1)[0];
    state.blocks.splice(to, 0, item);
    if (selectedBlockIdx === from) {
      selectedBlockIdx = to;
    } else if (from < selectedBlockIdx && to >= selectedBlockIdx) {
      selectedBlockIdx -= 1;
    } else if (from > selectedBlockIdx && to <= selectedBlockIdx) {
      selectedBlockIdx += 1;
    }
    renderBlocks();
    renderNavigator();
    updatePreview();
    syncHidden();
  }

  function updateBlockVisibilityUi(index) {
    var article = blocksEl.querySelector('[data-block-index="' + index + '"]');
    if (!article) return;
    var on = !!(article.querySelector('[data-b-on' + index + ']') || {}).checked;
    article.classList.toggle('hs-landing-block-off', !on);
    var body = article.querySelector('.hs-landing-block-body');
    if (body) body.hidden = !on;
    var txt = article.querySelector('[data-vis-text' + index + ']');
    if (txt) txt.textContent = on ? (MSG.block_visible || 'Visible') : (MSG.block_hidden || 'Hidden');
  }

  function initBlockDragDrop() {
    var dragFrom = null;
    blocksEl.querySelectorAll('[data-block-index]').forEach(function (article) {
      var handle = article.querySelector('[data-drag-handle]');
      if (handle) {
        handle.addEventListener('mousedown', function () { article.setAttribute('draggable', 'true'); });
        handle.addEventListener('touchstart', function () { article.setAttribute('draggable', 'true'); }, { passive: true });
      }
      article.addEventListener('dragstart', function (e) {
        dragFrom = parseInt(article.getAttribute('data-block-index'), 10);
        article.classList.add('is-dragging');
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('text/plain', String(dragFrom));
        }
      });
      article.addEventListener('dragend', function () {
        article.classList.remove('is-dragging');
        article.setAttribute('draggable', 'false');
        blocksEl.querySelectorAll('.is-drag-over').forEach(function (el) { el.classList.remove('is-drag-over'); });
        dragFrom = null;
      });
      article.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
        article.classList.add('is-drag-over');
      });
      article.addEventListener('dragleave', function () {
        article.classList.remove('is-drag-over');
      });
      article.addEventListener('drop', function (e) {
        e.preventDefault();
        article.classList.remove('is-drag-over');
        var from = dragFrom;
        if (from == null && e.dataTransfer) {
          from = parseInt(e.dataTransfer.getData('text/plain'), 10);
        }
        var to = parseInt(article.getAttribute('data-block-index'), 10);
        if (!isNaN(from) && !isNaN(to)) moveBlock(from, to);
      });
    });
  }

  function renderBlocks() {
    if (!state.blocks || !state.blocks.length) {
      blocksEl.innerHTML = '<div class="elb-empty-blocks"><i class="fa-solid fa-cubes"></i><p>' + esc(MSG.empty_blocks || MSG.no_blocks || 'No blocks yet') + '</p></div>';
      return;
    }
    blocksEl.innerHTML = (state.blocks || []).map(function (b, i) { return renderBlockEditor(b, i); }).join('');
    blocksEl.querySelectorAll('[data-remove]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        pullBlocksFromDom();
        var idx = parseInt(btn.getAttribute('data-remove'), 10);
        state.blocks.splice(idx, 1);
        renderBlocks();
        updatePreview();
        syncHidden();
      });
    });
    blocksEl.querySelectorAll('[data-move]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (btn.disabled) return;
        var idx = parseInt(btn.getAttribute('data-idx'), 10);
        var dir = btn.getAttribute('data-move');
        moveBlock(idx, dir === 'up' ? idx - 1 : idx + 1);
      });
    });
    blocksEl.querySelectorAll('[data-add-item]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        pullBlocksFromDom();
        var idx = parseInt(btn.getAttribute('data-add-item'), 10);
        if (!state.blocks[idx].items) state.blocks[idx].items = [];
        if (state.blocks[idx].items.length < 6) {
          state.blocks[idx].items.push({ icon: 'fa-star', title: '', text: '' });
        }
        selectedBlockIdx = idx;
        renderBlocks();
        updatePreview();
        syncHidden();
      });
    });
    blocksEl.querySelectorAll('[data-remove-feat]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (btn.disabled) return;
        pullBlocksFromDom();
        var parts = (btn.getAttribute('data-remove-feat') || '').split('-');
        var bIdx = parseInt(parts[0], 10);
        var iIdx = parseInt(parts[1], 10);
        if (isNaN(bIdx) || isNaN(iIdx) || !state.blocks[bIdx] || !state.blocks[bIdx].items) return;
        if (state.blocks[bIdx].items.length <= 1) return;
        state.blocks[bIdx].items.splice(iIdx, 1);
        selectedBlockIdx = bIdx;
        renderBlocks();
        updatePreview();
        syncHidden();
      });
    });
    blocksEl.querySelectorAll('[data-feat-icon-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var key = btn.getAttribute('data-feat-icon-toggle') || '';
        var grid = blocksEl.querySelector('[data-feat-icon-grid="' + key + '"]');
        if (!grid) return;
        var willOpen = grid.hidden;
        blocksEl.querySelectorAll('[data-feat-icon-grid]').forEach(function (g) { g.hidden = true; });
        grid.hidden = !willOpen;
      });
    });
    blocksEl.querySelectorAll('[data-add-gal]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        pullBlocksFromDom();
        var idx = parseInt(btn.getAttribute('data-add-gal'), 10);
        if (!state.blocks[idx].items) state.blocks[idx].items = [];
        if (state.blocks[idx].items.length < 6) {
          var hues = galleryHues(readFormMeta(), state.blocks[idx].items.length + 1);
          state.blocks[idx].items.push({ caption: 'Photo', image: '', hue: hues[state.blocks[idx].items.length] || 180 });
        }
        renderBlocks();
        syncHidden();
      });
    });
    function bindAddItem(attr, max, factory) {
      blocksEl.querySelectorAll('[' + attr + ']').forEach(function (btn) {
        btn.addEventListener('click', function () {
          pullBlocksFromDom();
          var idx = parseInt(btn.getAttribute(attr), 10);
          if (!state.blocks[idx].items) state.blocks[idx].items = [];
          if (state.blocks[idx].items.length < max) {
            state.blocks[idx].items.push(factory());
            selectedBlockIdx = idx;
            renderBlocks();
            updatePreview();
            syncHidden();
          }
        });
      });
    }
    function bindRemoveItem(attr, min) {
      blocksEl.querySelectorAll('[' + attr + ']').forEach(function (btn) {
        btn.addEventListener('click', function () {
          pullBlocksFromDom();
          var parts = (btn.getAttribute(attr) || '').split('-');
          var bIdx = parseInt(parts[0], 10);
          var iIdx = parseInt(parts[1], 10);
          if (isNaN(bIdx) || isNaN(iIdx) || !state.blocks[bIdx] || !state.blocks[bIdx].items) return;
          if (state.blocks[bIdx].items.length <= min) return;
          state.blocks[bIdx].items.splice(iIdx, 1);
          selectedBlockIdx = bIdx;
          renderBlocks();
          updatePreview();
          syncHidden();
        });
      });
    }
    bindAddItem('data-add-test', 6, function () { return { name: '', role: '', text: '' }; });
    bindRemoveItem('data-remove-test', 1);
    bindAddItem('data-add-price', 4, function () { return { name: 'Plan', price: '0', period: '/mo', features: [], cta_text: 'Choose', featured: false }; });
    bindRemoveItem('data-remove-price', 1);
    bindAddItem('data-add-faq', 10, function () { return { q: '', a: '' }; });
    bindRemoveItem('data-remove-faq', 1);
    bindAddItem('data-add-team', 8, function () { return { name: '', role: '', bio: '' }; });
    bindRemoveItem('data-remove-team', 1);
    bindAddItem('data-add-logo', 12, function () { return { name: 'Brand' }; });
    bindRemoveItem('data-remove-logo', 1);
    bindAddItem('data-add-tl', 8, function () { return { year: '', title: '', text: '' }; });
    bindRemoveItem('data-remove-tl', 1);
    bindAddItem('data-add-svc', 8, function () { return { icon: 'fa-star', title: '', text: '', price: '' }; });
    bindRemoveItem('data-remove-svc', 1);
    bindAddItem('data-add-hours', 7, function () { return { day: '', hours: '' }; });
    bindRemoveItem('data-remove-hours', 1);
    bindAddItem('data-add-dl', 8, function () { return { label: '', url: '#', size: '' }; });
    bindRemoveItem('data-remove-dl', 1);
    bindAddItem('data-add-ev', 10, function () { return { date: '', title: '', location: '' }; });
    bindRemoveItem('data-remove-ev', 1);
    bindAddItem('data-add-step', 6, function () { return { title: '', text: '' }; });
    bindRemoveItem('data-remove-step', 1);
    bindAddItem('data-add-col', 4, function () { return { title: '', text: '' }; });
    bindRemoveItem('data-remove-col', 1);
    bindAddItem('data-add-badge', 20, function () { return { label: 'Tag' }; });
    bindRemoveItem('data-remove-badge', 1);
    bindAddItem('data-add-sld', 12, function () {
      var hues = galleryHues(readFormMeta(), (state.blocks[selectedBlockIdx] && state.blocks[selectedBlockIdx].items ? state.blocks[selectedBlockIdx].items.length : 0) + 1);
      return { image: '', hue: hues[hues.length - 1] || 180, title: '', subtitle: '', cta_text: '', cta_url: '#' };
    });
    bindRemoveItem('data-remove-sld', 1);
    bindAddItem('data-add-acc', 12, function () { return { title: '', text: '' }; });
    bindRemoveItem('data-remove-acc', 1);
    bindAddItem('data-add-tab', 8, function () { return { title: '', text: '' }; });
    bindRemoveItem('data-remove-tab', 1);
    bindAddItem('data-add-mq', 20, function () { return { label: 'Tag' }; });
    bindRemoveItem('data-remove-mq', 1);
    blocksEl.querySelectorAll('[data-b-color-reset]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var idx = parseInt(btn.getAttribute('data-b-color-reset'), 10);
        var textInp = blocksEl.querySelector('[data-b-color-text' + idx + ']');
        var colorInp = blocksEl.querySelector('[data-b-color' + idx + ']');
        if (textInp) textInp.value = '';
        if (colorInp && state.color) colorInp.value = state.color;
        onBlockInput();
      });
    });
    blocksEl.querySelectorAll('[data-b-color-chip]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var idx = parseInt(btn.getAttribute('data-b-color-chip'), 10);
        var col = btn.getAttribute('data-color') || '';
        var textInp = blocksEl.querySelector('[data-b-color-text' + idx + ']');
        var colorInp = blocksEl.querySelector('[data-b-color' + idx + ']');
        if (textInp) textInp.value = col;
        if (colorInp) colorInp.value = col;
        onBlockInput();
      });
    });
    state.blocks.forEach(function (_, vi) {
      var colorInp = blocksEl.querySelector('[data-b-color' + vi + ']');
      if (!colorInp) return;
      colorInp.addEventListener('input', function () {
        var textInp = blocksEl.querySelector('[data-b-color-text' + vi + ']');
        if (textInp) textInp.value = colorInp.value;
        onBlockInput();
      });
    });
    ['tcolor', 'hcolor', 'btncolor', 'btntextcolor'].forEach(function (key) {
      state.blocks.forEach(function (_, vi) {
        var picker = blocksEl.querySelector('[data-b-' + key + vi + ']');
        var textInp = blocksEl.querySelector('[data-b-' + key + '-text' + vi + ']');
        if (picker) {
          picker.addEventListener('input', function () {
            if (textInp) textInp.value = picker.value;
            onBlockInput();
          });
        }
        if (textInp) {
          textInp.addEventListener('input', onBlockInput);
          textInp.addEventListener('change', onBlockInput);
        }
      });
    });
    blocksEl.querySelectorAll('input,textarea,select').forEach(function (el) {
      var isVisToggle = false;
      for (var ti = 0; ti < state.blocks.length; ti++) {
        if (el.hasAttribute('data-b-on' + ti)) { isVisToggle = true; break; }
      }
      if (!isVisToggle) {
        el.addEventListener('input', onBlockInput);
        el.addEventListener('change', onBlockInput);
      }
    });
    state.blocks.forEach(function (_, vi) {
      var visCb = blocksEl.querySelector('[data-b-on' + vi + ']');
      if (!visCb) return;
      visCb.addEventListener('change', function () {
        pullBlocksFromDom();
        updateBlockVisibilityUi(vi);
        updatePreview();
        syncHidden();
      });
    });
    initBlockDragDrop();
    initGalleryHandlers();
    blocksEl.querySelectorAll('[data-b-bg-clear]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var idx = btn.getAttribute('data-b-bg-clear') || '';
        setGalleryImage(idx + '-bg', '');
      });
    });
    state.blocks.forEach(function (_, vi) {
      var bgPicker = blocksEl.querySelector('[data-b-bg-color' + vi + ']');
      var bgText = blocksEl.querySelector('[data-b-bg-color-text' + vi + ']');
      if (bgPicker) {
        bgPicker.addEventListener('input', function () {
          if (bgText) bgText.value = bgPicker.value;
          onBlockInput();
        });
      }
      var bgOverlay = blocksEl.querySelector('[data-b-bg-overlay' + vi + ']');
      var bgOverlayVal = blocksEl.querySelector('[data-b-bg-overlay-val' + vi + ']');
      if (bgOverlay) {
        bgOverlay.addEventListener('input', function () {
          if (bgOverlayVal) bgOverlayVal.textContent = bgOverlay.value + '%';
          onBlockInput();
        });
      }
    });
    blocksEl.querySelectorAll('[data-block-index]').forEach(function (article) {
      article.addEventListener('click', function (e) {
        if (e.target.closest('button,input,textarea,select,label,a')) return;
        selectBlock(parseInt(article.getAttribute('data-block-index'), 10), true);
      });
    });
    blocksEl.querySelectorAll('[data-pick-icon]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var ic = btn.getAttribute('data-pick-icon');
        var key = btn.getAttribute('data-pick-for') || '';
        var hidden = blocksEl.querySelector('[data-b-item-icon' + key + ']');
        if (hidden) hidden.value = ic;
        var picker = btn.closest('[data-icon-picker]');
        if (picker) {
          picker.querySelectorAll('[data-pick-icon]').forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
          });
          var featBtn = picker.querySelector('.hs-landing-feat-icon-btn i');
          if (featBtn) {
            featBtn.className = faPrefix(currentIconStyle()) + ' ' + ic;
          }
          var grid = picker.querySelector('[data-feat-icon-grid]');
          if (grid) grid.hidden = true;
        }
        onBlockInput();
      });
    });
  }

  function applyIconSetToFeatures() {
    pullBlocksFromDom();
    var icons = iconsForSet(currentIconSetKey());
    state.blocks.forEach(function (block) {
      if (block.type !== 'features' || !block.items) return;
      block.items.forEach(function (item, i) {
        item.icon = icons[i % icons.length] || 'fa-star';
      });
    });
    renderBlocks();
    updatePreview();
    syncHidden();
  }

  function ensureGalleryModal() {
    if (galleryModalEl) return galleryModalEl;
    galleryModalEl = document.createElement('div');
    galleryModalEl.className = 'hs-landing-gal-modal';
    galleryModalEl.hidden = true;
    galleryModalEl.innerHTML = '<div class="hs-landing-gal-modal-backdrop" data-gal-modal-close></div>'
      + '<div class="hs-landing-gal-modal-panel" role="dialog" aria-modal="true">'
      + '<header class="hs-landing-gal-modal-head"><strong data-gal-modal-title>' + esc(MSG.gallery_picker_title || 'Select photo') + '</strong>'
      + '<button type="button" class="hs-landing-icon-btn" data-gal-modal-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button></header>'
      + '<div class="hs-landing-gal-modal-body"><div class="hs-landing-gal-modal-grid" data-gal-modal-grid></div>'
      + '<p class="hs-landing-gal-modal-empty hp-muted" data-gal-modal-empty hidden>' + esc(MSG.gallery_no_photos || 'No photos yet') + '</p></div>'
      + '<footer class="hs-landing-gal-modal-foot">'
      + '<button type="button" class="hs-btn hs-btn-primary" data-gal-modal-upload><i class="fa-solid fa-upload"></i> ' + esc(MSG.upload_photo || 'Upload photo') + '</button>'
      + '</footer></div>';
    root.appendChild(galleryModalEl);
    galleryFileInput = document.createElement('input');
    galleryFileInput.type = 'file';
    galleryFileInput.accept = 'image/jpeg,image/png,image/webp,image/gif';
    galleryFileInput.hidden = true;
    root.appendChild(galleryFileInput);
    galleryModalEl.querySelectorAll('[data-gal-modal-close]').forEach(function (el) {
      el.addEventListener('click', closeGalleryModal);
    });
    var uploadBtn = galleryModalEl.querySelector('[data-gal-modal-upload]');
    if (uploadBtn) {
      uploadBtn.addEventListener('click', function () { galleryFileInput.click(); });
    }
    galleryFileInput.addEventListener('change', function () {
      if (!galleryFileInput.files || !galleryFileInput.files[0] || !galleryPickTarget) return;
      uploadGalleryFile(galleryPickTarget, galleryFileInput.files[0]);
      galleryFileInput.value = '';
    });
    return galleryModalEl;
  }

  function closeGalleryModal() {
    if (galleryModalEl) galleryModalEl.hidden = true;
    galleryPickTarget = null;
  }

  function setGalleryImage(target, path) {
    if (/^\d+-s\d+$/.test(String(target))) {
      var sParts = String(target).split('-s');
      var sBi = sParts[0];
      var sIi = sParts[1];
      var sHidden = blocksEl.querySelector('[data-b-sld-img' + sBi + '-' + sIi + ']');
      var sThumb = blocksEl.querySelector('[data-sld-pick="' + target + '"]');
      var sClear = blocksEl.querySelector('[data-sld-clear="' + target + '"]');
      var sHueInp = blocksEl.querySelector('[data-b-sld-hue' + sBi + '-' + sIi + ']');
      if (sHidden) sHidden.value = path || '';
      if (sThumb) {
        sThumb.style.cssText = galleryThumbStyle({ image: path }, (sHueInp || {}).value || 160);
        sThumb.classList.toggle('has-image', !!path);
        sThumb.innerHTML = path ? '' : '<i class="fa-solid fa-image"></i>';
      }
      if (sClear) sClear.disabled = !path;
      pullBlocksFromDom();
      updatePreview();
      syncHidden();
      return;
    }
    if (String(target).indexOf('-img') >= 0) {
      var biImg = String(target).replace('-img', '');
      var hiddenImg = blocksEl.querySelector('[data-b-image' + biImg + ']');
      var thumbImg = blocksEl.querySelector('[data-gal-pick="' + target + '"]');
      if (hiddenImg) hiddenImg.value = path || '';
      if (thumbImg) {
        thumbImg.style.cssText = path ? 'background-image:url(' + galleryImageUrl(path) + ');background-size:cover;background-position:center' : '';
        thumbImg.classList.toggle('has-image', !!path);
        thumbImg.innerHTML = path ? '' : '<i class="fa-solid fa-image"></i>';
      }
      pullBlocksFromDom();
      updatePreview();
      syncHidden();
      return;
    }
    if (String(target).indexOf('-bg') >= 0) {
      var biBg = String(target).replace('-bg', '');
      var hiddenBg = blocksEl.querySelector('[data-b-bg-image' + biBg + ']');
      var thumbBg = blocksEl.querySelector('[data-gal-pick="' + target + '"]');
      var bgTypeSel = blocksEl.querySelector('[data-b-bg-type' + biBg + ']');
      if (hiddenBg) hiddenBg.value = path || '';
      if (path && bgTypeSel) bgTypeSel.value = 'image';
      if (thumbBg) {
        thumbBg.style.cssText = path ? 'background-image:url(' + galleryImageUrl(path) + ');background-size:cover;background-position:center' : '';
        thumbBg.classList.toggle('has-image', !!path);
        thumbBg.innerHTML = path ? '' : '<i class="fa-solid fa-image"></i>';
      }
      pullBlocksFromDom();
      updatePreview();
      syncHidden();
      return;
    }
    var parts = target.split('-');
    var bi = parts[0];
    var ii = parts[1];
    var hidden = blocksEl.querySelector('[data-b-gal-img' + bi + '-' + ii + ']');
    var thumb = blocksEl.querySelector('[data-gal-pick="' + target + '"]');
    var clearBtn = blocksEl.querySelector('[data-gal-clear="' + target + '"]');
    var hueInp = blocksEl.querySelector('[data-b-gal-hue' + bi + '-' + ii + ']');
    if (hidden) hidden.value = path || '';
    if (thumb) {
      var item = { image: path };
      thumb.style.cssText = galleryThumbStyle(item, (hueInp || {}).value || 160);
      thumb.classList.toggle('has-image', !!path);
      thumb.innerHTML = path ? '' : '<i class="fa-solid fa-image"></i>';
    }
    if (clearBtn) clearBtn.disabled = !path;
    pullBlocksFromDom();
    updatePreview();
    syncHidden();
  }

  function loadGalleryImages() {
    var modal = ensureGalleryModal();
    var grid = modal.querySelector('[data-gal-modal-grid]');
    var empty = modal.querySelector('[data-gal-modal-empty]');
    if (!grid) return;
    grid.innerHTML = '<p class="hp-muted">' + esc(MSG.gallery_uploading || 'Loading…') + '</p>';
    fetch((cfg.galleryApi || '') + '?action=list', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.ok || !res.images || !res.images.length) {
          grid.innerHTML = '';
          if (empty) empty.hidden = false;
          return;
        }
        if (empty) empty.hidden = true;
        grid.innerHTML = res.images.map(function (img) {
          return '<button type="button" class="hs-landing-gal-pick" data-gal-choose="' + esc(img.path) + '" title="' + esc(img.name) + '">'
            + '<img src="' + esc(img.url) + '" alt="' + esc(img.name) + '" loading="lazy"></button>';
        }).join('');
        grid.querySelectorAll('[data-gal-choose]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            if (!galleryPickTarget) return;
            setGalleryImage(galleryPickTarget, btn.getAttribute('data-gal-choose') || '');
            closeGalleryModal();
          });
        });
      })
      .catch(function () {
        grid.innerHTML = '';
        if (empty) {
          empty.textContent = MSG.gallery_upload_error || 'Failed to load';
          empty.hidden = false;
        }
      });
  }

  function openGalleryPicker(target) {
    galleryPickTarget = target;
    var modal = ensureGalleryModal();
    modal.hidden = false;
    loadGalleryImages();
  }

  function uploadGalleryFile(target, file) {
    var modal = ensureGalleryModal();
    var uploadBtn = modal.querySelector('[data-gal-modal-upload]');
    if (uploadBtn) uploadBtn.disabled = true;
    var fd = new FormData();
    fd.append('action', 'upload');
    fd.append('csrf', cfg.csrf || (form.querySelector('[name="csrf"]') || {}).value || '');
    fd.append('file', file);
    fetch(cfg.galleryApi || '', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (uploadBtn) uploadBtn.disabled = false;
        if (!res.ok || !res.path) {
          alert(MSG.gallery_upload_error || 'Upload failed');
          return;
        }
        setGalleryImage(target, res.path);
        loadGalleryImages();
        closeGalleryModal();
      })
      .catch(function () {
        if (uploadBtn) uploadBtn.disabled = false;
        alert(MSG.gallery_upload_error || 'Upload failed');
      });
  }

  function initGalleryHandlers() {
    blocksEl.querySelectorAll('[data-gal-pick]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openGalleryPicker(btn.getAttribute('data-gal-pick') || '');
      });
    });
    blocksEl.querySelectorAll('[data-sld-pick]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openGalleryPicker(btn.getAttribute('data-sld-pick') || '');
      });
    });
    blocksEl.querySelectorAll('[data-gal-clear]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (btn.disabled) return;
        setGalleryImage(btn.getAttribute('data-gal-clear') || '', '');
      });
    });
    blocksEl.querySelectorAll('[data-sld-clear]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (btn.disabled) return;
        setGalleryImage(btn.getAttribute('data-sld-clear') || '', '');
      });
    });
  }

  function applyPaletteToGalleries() {
    pullBlocksFromDom();
    var meta = readFormMeta();
    state.blocks.forEach(function (block) {
      if (block.type !== 'gallery' || !block.items) return;
      var hues = galleryHues(meta, block.items.length);
      block.items.forEach(function (item, i) {
        item.hue = hues[i];
      });
    });
    renderBlocks();
    updatePreview();
    syncHidden();
  }

  function onBlockInput() {
    pullBlocksFromDom();
    updatePreview();
    syncHidden();
  }

  function renderNavEditor() {
    navEl.innerHTML = (state.nav_links || []).map(function (link, i) {
      return '<div class="elb-nav-edit-row">'
        + '<label class="hs-landing-nav-on" title="' + esc(MSG.enabled || 'Show') + '"><input type="checkbox" data-nav-on' + i + ' ' + (link.on ? 'checked' : '') + '></label>'
        + '<div class="elb-fields-grid elb-nav-fields">'
        + field(MSG.nav_label, '<input type="text" data-nav-label' + i + ' value="' + esc(link.label) + '">', 'nav_label')
        + field(MSG.nav_url, '<input type="text" data-nav-url' + i + ' value="' + esc(link.url) + '">', 'nav_url')
        + '</div>'
        + '<button type="button" class="hs-landing-icon-btn hs-landing-icon-danger" data-nav-rm="' + i + '" title="' + esc(MSG.remove) + '"><i class="fa-solid fa-trash"></i></button>'
        + '</div>';
    }).join('')
      + '<button type="button" class="hs-btn hs-btn-ghost hs-landing-mini-btn" data-nav-add>+ ' + esc(MSG.add_nav) + '</button>';

    navEl.querySelectorAll('[data-nav-rm]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        pullNavFromDom();
        state.nav_links.splice(parseInt(btn.getAttribute('data-nav-rm'), 10), 1);
        renderNavEditor();
        updatePreview();
        syncHidden();
      });
    });
    var addBtn = navEl.querySelector('[data-nav-add]');
    if (addBtn) {
      addBtn.addEventListener('click', function () {
        pullNavFromDom();
        if (state.nav_links.length < 8) {
          state.nav_links.push({ label: 'Link', url: '#', on: true });
        }
        renderNavEditor();
        syncHidden();
      });
    }
    navEl.querySelectorAll('input').forEach(function (el) {
      el.addEventListener('input', function () { pullNavFromDom(); updatePreview(); syncHidden(); });
      el.addEventListener('change', function () { pullNavFromDom(); updatePreview(); syncHidden(); });
    });
  }

  function pullNavFromDom() {
    state.nav_links = (state.nav_links || []).map(function (_, i) {
      return {
        on: !!(navEl.querySelector('[data-nav-on' + i + ']') || {}).checked,
        label: (navEl.querySelector('[data-nav-label' + i + ']') || {}).value || '',
        url: (navEl.querySelector('[data-nav-url' + i + ']') || {}).value || '#',
      };
    });
  }

  /* ---- Preview render (mirrors PHP) ---- */
  function anchorFor(type) {
    var reg = cfg.blockTypes[type];
    return reg && reg.anchor ? reg.anchor : '';
  }

  function navLinksHtml(data, wrapClass) {
    var links = '';
    (data.nav_links || []).forEach(function (l) {
      if (!l.on || !l.label) return;
      links += '<a href="' + esc(l.url || '#') + '">' + esc(l.label) + '</a>';
    });
    if (!links) return '';
    return '<nav class="' + esc(wrapClass || 'nav-links nav-links-desktop') + '">' + links + '</nav>';
  }

  function navBurgerBtnHtml() {
    return '<button type="button" class="nav-burger-btn" aria-label="Menu" aria-expanded="false" data-nav-burger>'
      + '<i class="fa-solid fa-bars" aria-hidden="true"></i></button>';
  }

  function navDrawerHtml(data, ctaHtml) {
    var drawerLinks = navLinksHtml(data, 'nav-links nav-links-drawer');
    if (!drawerLinks && !ctaHtml) return '';
    return '<div class="nav-drawer" hidden data-nav-drawer>' + drawerLinks + ctaHtml + '</div>'
      + '<div class="nav-overlay" hidden data-nav-overlay></div>';
  }

  function messengerMeta(channel) {
    var ch = (cfg.messengerChannels || {})[channel] || {};
    return { icon: ch.icon || 'fa-comment', color: ch.color || 'var(--c)', label: ch.label || channel };
  }

  function messengerHref(channel, value) {
    value = (value || '').trim();
    if (!value) return '';
    if (/^https?:\/\//i.test(value)) return value;
    if (channel === 'whatsapp') return 'https://wa.me/' + value.replace(/\D/g, '');
    if (channel === 'telegram') {
      if (value.charAt(0) === '@') return 'https://t.me/' + value.slice(1);
      if (value.indexOf('t.me/') >= 0) return /^https?:\/\//i.test(value) ? value : 'https://' + value.replace(/^https?:\/\//, '');
      return 'https://t.me/' + value;
    }
    if (channel === 'viber') return 'viber://chat?number=' + encodeURIComponent(value.replace(/[^\d+]/g, ''));
    if (channel === 'messenger') {
      if (value.indexOf('m.me') >= 0 || value.indexOf('facebook.com') >= 0) return /^https?:\/\//i.test(value) ? value : 'https://' + value.replace(/^\//, '');
      return 'https://m.me/' + encodeURIComponent(value.replace(/^@/, ''));
    }
    if (channel === 'signal') return 'https://signal.me/#p/' + encodeURIComponent(value.replace(/[^\d+]/g, ''));
    if (channel === 'skype') return 'skype:' + encodeURIComponent(value) + '?chat';
    if (channel === 'line') {
      if (value.indexOf('line.me') >= 0) return /^https?:\/\//i.test(value) ? value : 'https://' + value.replace(/^\//, '');
      return 'https://line.me/R/ti/p/@' + value.replace(/^@/, '');
    }
    return value;
  }

  function messengerItemsFromData(data) {
    var items = [];
    Object.keys(cfg.messengerChannels || {}).forEach(function (key) {
      var raw = (data['msg_' + key] || '').trim();
      if (!raw) return;
      var url = messengerHref(key, raw);
      if (!url) return;
      var meta = messengerMeta(key);
      items.push({ channel: key, url: url, label: meta.label, color: meta.color, icon: meta.icon });
    });
    return items;
  }

  function messengerItemsFromBlock(block) {
    var items = [];
    (block.items || []).forEach(function (row) {
      var ch = row.channel || '';
      var val = (row.value || '').trim();
      if (!ch || !val) return;
      var url = messengerHref(ch, val);
      if (!url) return;
      var meta = messengerMeta(ch);
      items.push({
        channel: ch,
        url: url,
        label: (row.label || '').trim() || meta.label,
        color: meta.color,
        icon: meta.icon,
      });
    });
    return items;
  }

  function brandHtml(data) {
    var name = data.business_name || 'Business';
    var tagline = data.tagline || '';
    var style = data.nav_style || 'classic';
    var icon = (data.logo_icon || '').trim();
    var prefix = currentIconStyle() === 'regular' ? 'fa-regular' : 'fa-solid';
    var iconHtml = icon ? '<span class="brand-icon"><i class="' + prefix + ' ' + esc(icon) + '" aria-hidden="true"></i></span>' : '';
    var showTagline = style !== 'minimal' && style !== 'micro';
    return '<div class="brand">' + iconHtml + esc(name) + (showTagline && tagline ? '<span>' + esc(tagline) + '</span>' : '') + '</div>';
  }

  function navCtaHtml(data) {
    var text = (data.nav_cta_text || '').trim();
    if (!text) return '';
    var url = (data.nav_cta_url || '#contact').trim() || '#contact';
    return '<a class="nav-cta btn primary" href="' + esc(url) + '">' + esc(text) + '</a>';
  }

  function footerNavLinksHtml(data) {
    var html = '<div class="footer-nav">';
    (data.nav_links || []).forEach(function (l) {
      if (!l.on || !l.label) return;
      html += '<a href="' + esc(l.url || '#') + '">' + esc(l.label) + '</a>';
    });
    return html + '</div>';
  }

  function renderNavPreview(data) {
    var styles = cfg.navStyles || {};
    var style = data.nav_style || 'classic';
    if (!styles[style]) style = 'classic';
    var burger = data.nav_burger || 'mobile';
    if (!(cfg.burgerModes || {})[burger]) burger = 'mobile';
    var tagline = data.tagline || '';
    var linksHtml = navLinksHtml(data);
    var ctaHtml = navCtaHtml(data);
    var burgerBtn = burger !== 'off' ? navBurgerBtnHtml() : '';
    var drawerHtml = burger !== 'off' ? navDrawerHtml(data, ctaHtml) : '';
    var brand = brandHtml(data);
    var rowInner = '<div class="nav-inner">' + brand + linksHtml + ctaHtml + burgerBtn + '</div>';
    var announceBar = (style === 'announce' && tagline) ? '<div class="nav-announce"><div class="wrap">' + esc(tagline) + '</div></div>' : '';
    var innerBody;
    if (style === 'centered') {
      innerBody = '<div class="nav-inner nav-inner-centered"><div class="nav-brand-center">' + brand + '</div>'
        + (linksHtml ? '<div class="nav-links-row">' + linksHtml + '</div>' : '') + burgerBtn + '</div>';
    } else if (style === 'stacked') {
      innerBody = '<div class="nav-inner nav-inner-stacked">' + brand
        + (linksHtml ? '<div class="nav-links-row">' + linksHtml + '</div>' : '') + burgerBtn + '</div>';
    } else if (style === 'glass') {
      innerBody = '<div class="nav-glass-wrap"><div class="nav-inner">' + brand + linksHtml + ctaHtml + burgerBtn + '</div></div>';
    } else if (style === 'floating') {
      innerBody = '<div class="nav-floating-wrap"><div class="nav-inner">' + brand + linksHtml + ctaHtml + burgerBtn + '</div></div>';
    } else if (style === 'split') {
      innerBody = '<div class="nav-inner nav-inner-split">' + brand + '<div class="nav-split-links">' + linksHtml + ctaHtml + '</div>' + burgerBtn + '</div>';
    } else if (style === 'boxed') {
      innerBody = '<div class="nav-boxed-wrap"><div class="nav-inner">' + brand + linksHtml + ctaHtml + burgerBtn + '</div></div>';
    } else if (style === 'ribbon') {
      innerBody = '<div class="nav-ribbon-accent" aria-hidden="true"></div>' + rowInner;
    } else if (style === 'announce') {
      innerBody = announceBar + rowInner;
    } else {
      innerBody = rowInner;
    }
    var inner = '<header class="nav nav-' + esc(style) + ' nav-burger-' + esc(burger) + '">' + innerBody + drawerHtml + '</header>';
    return '<div class="elb-sec-shell elb-sec-shell-nav" data-elb-section data-elb-pick="nav">'
      + '<div class="elb-sec-bar"><i class="fa-solid fa-bars"></i><span>' + esc(MSG.nav_header || 'Header / menu') + '</span>'
      + '<span class="elb-sec-pick">' + esc(MSG.pick_in_preview || 'Click to edit') + '</span></div>'
      + inner + '</div>';
  }

  function renderHero(block, data) {
    if (!block.on) return '';
    var btn = '<a class="btn primary" href="' + esc(block.cta_url) + '"><i class="fa-solid fa-arrow-right"></i> ' + esc(block.cta_text) + '</a>';
    if (block.variant === 'centered') {
      return '<section class="hero hero-centered"><div class="wrap hero-centered-inner"><div class="badge"><i class="fa-solid fa-rocket"></i> '
        + esc(data.business_name) + '</div><h1>' + esc(block.title) + '</h1><p>' + esc(block.subtitle) + '</p>' + btn + '</div></section>';
    }
    if (block.variant === 'minimal') {
      return '<section class="hero hero-minimal"><div class="wrap"><h1>' + esc(block.title) + '</h1><p>' + esc(block.subtitle) + '</p>' + btn + '</div></section>';
    }
    return '<section class="hero"><div class="wrap hero-grid"><div><div class="badge"><i class="fa-solid fa-rocket"></i> '
      + esc(data.business_name) + '</div><h1>' + esc(block.title) + '</h1><p>' + esc(block.subtitle) + '</p>' + btn
      + '</div><div class="hero-card"><div><span>Status</span><strong>Live</strong></div><div><span>SSL</span><strong>Active</strong></div></div></div></section>';
  }

  function renderIconTag(icon, data) {
    var ic = esc(icon || 'fa-star');
    var prefix = faPrefix(data.icon_style || 'solid');
    return '<i class="' + prefix + ' ' + ic + '"></i>';
  }

  function renderFeatures(block, data) {
    if (!block.on) return '';
    var id = anchorFor('features');
    var items = '';
    (block.items || []).forEach(function (f) {
      var iconHtml = renderIconTag(f.icon, data);
      if (block.variant === 'list') {
        items += '<div class="feat-list-item"><div class="feat-icon">' + iconHtml + '</div><div><h3>'
          + esc(f.title) + '</h3><p>' + esc(f.text) + '</p></div></div>';
      } else {
        items += '<article class="feat' + (block.variant === 'cards' ? ' feat-card' : '') + '"><div class="feat-icon">' + iconHtml
          + '</div><h3>' + esc(f.title) + '</h3><p>' + esc(f.text) + '</p></article>';
      }
    });
    if (!items) return '';
    var inner = block.variant === 'list' ? '<div class="feat-list">' + items + '</div>' : '<div class="feat-grid">' + items + '</div>';
    return '<section class="features"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title) + '</h2>' + inner + '</div></section>';
  }

  function renderAbout(block, data) {
    if (!block.on) return '';
    var id = anchorFor('about');
    if (block.variant === 'centered') {
      return '<section class="about about-centered"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap about-centered-inner"><h2 class="sec-title">'
        + esc(block.title) + '</h2><p class="about-text">' + esc(block.text) + '</p></div></section>';
    }
    return '<section class="about"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap about-grid"><div><h2 class="sec-title">'
      + esc(block.title) + '</h2><p class="about-text">' + esc(block.text) + '</p></div><div class="about-card"><i class="fa-solid fa-building"></i><strong>'
      + esc(data.business_name) + '</strong><span>' + esc(data.tagline) + '</span></div></div></section>';
  }

  function renderGallery(block, data) {
    if (!block.on) return '';
    var id = anchorFor('gallery');
    var hues = galleryHues(data, (block.items || []).length || 4);
    var items = '';
    (block.items || []).forEach(function (g, i) {
      if (g.image) {
        var cap = g.caption ? '<figcaption>' + esc(g.caption) + '</figcaption>' : '';
        items += '<figure class="gal-item gal-item-photo"><img src="' + esc(galleryImageUrl(g.image)) + '" alt="' + esc(g.caption || 'Gallery') + '" loading="lazy">' + cap + '</figure>';
      } else {
        var hue = parseInt(g.hue, 10);
        if (isNaN(hue)) hue = hues[i] || 160;
        items += '<figure class="gal-item" style="background:linear-gradient(135deg,hsl(' + hue + ',65%,45%),hsl(' + ((hue + 40) % 360) + ',70%,55%))"><figcaption>'
          + esc(g.caption) + '</figcaption></figure>';
      }
    });
    if (!items) return '';
    var cls = block.variant === 'masonry' ? 'gal-masonry' : (block.variant === 'row' ? 'gal-row' : 'gal-grid');
    return '<section class="gallery"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title)
      + '</h2><div class="' + cls + '">' + items + '</div></div></section>';
  }

  function renderInfo(block) {
    if (!block.on) return '';
    var id = anchorFor('info');
    if (block.variant === 'quote') {
      return '<section class="info info-quote"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap"><blockquote>“' + esc(block.quote)
        + '”</blockquote>' + (block.author ? '<cite>— ' + esc(block.author) + '</cite>' : '') + '</div></section>';
    }
    if (block.variant === 'text') {
      return '<section class="info info-text"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap info-text-box"><h2 class="sec-title">'
        + esc(block.title) + '</h2><p>' + esc(block.text) + '</p></div></section>';
    }
    var stats = '';
    (block.stats || []).forEach(function (s) {
      stats += '<div class="stat"><strong>' + esc(s.value) + '</strong><span>' + esc(s.label) + '</span></div>';
    });
    return '<section class="info info-stats"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.title)
      + '</h2><div class="stat-grid">' + stats + '</div></div></section>';
  }

  function renderCta(block) {
    if (!block.on) return '';
    var id = anchorFor('cta');
    var btn = '<a class="btn primary" href="' + esc(block.cta_url) + '">' + esc(block.cta_text) + '</a>';
    if (block.variant === 'inline') {
      return '<section class="cta cta-inline"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap cta-inline-inner"><div><strong>'
        + esc(block.title) + '</strong><span>' + esc(block.text) + '</span></div>' + btn + '</div></section>';
    }
    if (block.variant === 'split') {
      return '<section class="cta cta-split"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap cta-split-grid"><div><h2 class="sec-title">'
        + esc(block.title) + '</h2><p>' + esc(block.text) + '</p></div><div>' + btn + '</div></div></section>';
    }
    return '<section class="cta cta-banner"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap cta-banner-inner"><h2>'
      + esc(block.title) + '</h2><p>' + esc(block.text) + '</p>' + btn + '</div></section>';
  }

  function renderTestimonials(block) {
    if (!block.on) return '';
    var id = anchorFor('testimonials');
    var items = block.items || [];
    if (!items.length) return '';
    if (block.variant === 'quote') {
      var f = items[0] || {};
      return '<section class="testimonials test-quote"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap test-quote-inner">'
        + (block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '')
        + '<blockquote>“' + esc(f.text) + '”</blockquote><cite>' + esc(f.name) + '</cite><span class="test-role">' + esc(f.role) + '</span></div></section>';
    }
    var html = '';
    items.forEach(function (item) {
      html += '<article class="test-card"><p>“' + esc(item.text) + '”</p><footer><strong>' + esc(item.name) + '</strong><span>' + esc(item.role) + '</span></footer></article>';
    });
    var inner = block.variant === 'grid' ? '<div class="test-grid">' + html + '</div>' : '<div class="test-cards">' + html + '</div>';
    return '<section class="testimonials"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title)
      + '</h2>' + inner + '</div></section>';
  }

  function renderPricing(block) {
    if (!block.on) return '';
    var id = anchorFor('pricing');
    var items = block.items || [];
    if (!items.length) return '';
    if (block.variant === 'table') {
      var rows = '';
      items.forEach(function (item) {
        rows += '<tr><td>' + esc(item.name) + '</td><td><strong>' + esc(item.price) + esc(item.period) + '</strong></td><td>' + esc((item.features || []).join(', ')) + '</td></tr>';
      });
      return '<section class="pricing pricing-table"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title)
        + '</h2><table class="price-table"><thead><tr><th>Plan</th><th>Price</th><th>Includes</th></tr></thead><tbody>' + rows + '</tbody></table></div></section>';
    }
    var cards = '';
    items.forEach(function (item) {
      var feats = '';
      (item.features || []).forEach(function (f) { feats += '<li><i class="fa-solid fa-check"></i> ' + esc(f) + '</li>'; });
      cards += '<article class="price-card' + (item.featured ? ' price-card-featured' : '') + '"><h3>' + esc(item.name) + '</h3><div class="price-amt"><strong>'
        + esc(item.price) + '</strong><span>' + esc(item.period) + '</span></div><ul class="price-feats">' + feats + '</ul><a class="btn primary" href="#contact">' + esc(item.cta_text) + '</a></article>';
    });
    var cls = block.variant === 'compact' ? 'price-compact' : 'price-cards';
    return '<section class="pricing"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title)
      + '</h2><div class="' + cls + '">' + cards + '</div></div></section>';
  }

  function renderFaq(block) {
    if (!block.on) return '';
    var id = anchorFor('faq');
    var html = '';
    (block.items || []).forEach(function (item) {
      if (block.variant === 'accordion') {
        html += '<details class="faq-acc"><summary>' + esc(item.q) + '</summary><p>' + esc(item.a) + '</p></details>';
      } else {
        html += '<div class="faq-item"><h3>' + esc(item.q) + '</h3><p>' + esc(item.a) + '</p></div>';
      }
    });
    var innerCls = block.variant === 'twocol' ? 'faq-twocol' : 'faq-list';
    return '<section class="faq"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title)
      + '</h2><div class="' + innerCls + '">' + html + '</div></div></section>';
  }

  function renderTeam(block) {
    if (!block.on) return '';
    var id = anchorFor('team');
    var html = '';
    (block.items || []).forEach(function (item) {
      var initial = (item.name || '?').charAt(0).toUpperCase();
      html += '<article class="team-member"><div class="team-avatar">' + esc(initial) + '</div><h3>' + esc(item.name)
        + '</h3><span class="team-role">' + esc(item.role) + '</span><p>' + esc(item.bio) + '</p></article>';
    });
    var cls = block.variant === 'list' ? 'team-list' : (block.variant === 'cards' ? 'team-cards' : 'team-grid');
    return '<section class="team"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title)
      + '</h2><div class="' + cls + '">' + html + '</div></div></section>';
  }

  function renderLogos(block) {
    if (!block.on) return '';
    var id = anchorFor('logos');
    var html = '';
    (block.items || []).forEach(function (item) {
      if (item.name) html += '<span class="logo-pill">' + esc(item.name) + '</span>';
    });
    var cls = block.variant === 'grid' ? 'logos-grid' : (block.variant === 'strip' ? 'logos-strip' : 'logos-row');
    return '<section class="logos"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap"><h2 class="sec-title logos-title">' + esc(block.section_title)
      + '</h2><div class="' + cls + '">' + html + '</div></div></section>';
  }

  function renderVideo(block) {
    if (!block.on) return '';
    var id = anchorFor('video');
    var embed = videoEmbedUrl(block.video_url);
    var inner = '';
    if (block.variant === 'link' && block.video_url) {
      inner = '<a class="video-link-card" href="' + esc(block.video_url) + '"><i class="fa-solid fa-circle-play"></i><span>' + esc(block.section_title || 'Watch') + '</span></a>';
    } else if (embed) {
      inner = '<div class="video-embed"><iframe src="' + esc(embed) + '" title="Video" loading="lazy" allowfullscreen></iframe></div>';
    } else if (block.video_url) {
      inner = '<a class="btn primary" href="' + esc(block.video_url) + '"><i class="fa-solid fa-circle-play"></i> ' + esc(block.section_title || 'Watch') + '</a>';
    } else return '';
    return '<section class="video-block"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap">'
      + (block.section_title && block.variant === 'embed' ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '') + inner + '</div></section>';
  }

  function renderNewsletter(block) {
    if (!block.on) return '';
    var id = anchorFor('newsletter');
    var form = '<form class="newsletter-form" onsubmit="return false"><input type="email" placeholder="email@example.com"><button type="submit" class="btn primary">' + esc(block.cta_text) + '</button></form>';
    var cls = 'newsletter newsletter-' + (block.variant || 'card');
    if (block.variant === 'banner') return '<section class="' + cls + '"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap newsletter-banner-inner"><div><strong>' + esc(block.title) + '</strong><span>' + esc(block.text) + '</span></div>' + form + '</div></section>';
    if (block.variant === 'inline') return '<section class="' + cls + '"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap newsletter-inline-inner"><div><strong>' + esc(block.title) + '</strong></div>' + form + '</div></section>';
    return '<section class="' + cls + '"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap newsletter-card"><h2 class="sec-title">' + esc(block.title) + '</h2><p>' + esc(block.text) + '</p>' + form + '</div></section>';
  }

  function renderTimeline(block) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      html += '<article class="tl-item"><span class="tl-year">' + esc(item.year) + '</span><h3>' + esc(item.title) + '</h3><p>' + esc(item.text) + '</p></article>';
    });
    if (!html) return '';
    var cls = block.variant === 'cards' ? 'timeline timeline-cards' : 'timeline timeline-vertical';
    return '<section class="' + cls + '"' + (anchorFor('timeline') ? ' id="' + anchorFor('timeline') + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title) + '</h2><div class="tl-list">' + html + '</div></div></section>';
  }

  function renderServices(block, data) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      html += '<article class="svc-item"><div class="svc-icon">' + renderIconTag(item.icon, data) + '</div><h3>' + esc(item.title) + '</h3>'
        + (item.price ? '<span class="svc-price">' + esc(item.price) + '</span>' : '') + '<p>' + esc(item.text) + '</p></article>';
    });
    if (!html) return '';
    var cls = block.variant === 'list' ? 'svc-list' : (block.variant === 'cards' ? 'svc-cards' : 'svc-grid');
    return '<section class="services"' + (anchorFor('services') ? ' id="' + anchorFor('services') + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title) + '</h2><div class="' + cls + '">' + html + '</div></div></section>';
  }

  function renderHeading(block) {
    if (!block.on || !block.title) return '';
    return '<section class="heading-block heading-' + esc(block.variant || 'center') + '"><div class="wrap"><h2 class="sec-title">' + esc(block.title) + '</h2>'
      + (block.subtitle ? '<p class="heading-sub">' + esc(block.subtitle) + '</p>' : '') + '</div></section>';
  }

  function renderTextBlock(block) {
    if (!block.on || !block.text) return '';
    return '<section class="text-block text-' + esc(block.variant || 'plain') + '"><div class="wrap text-inner">'
      + (block.title ? '<h2 class="sec-title">' + esc(block.title) + '</h2>' : '') + '<p>' + esc(block.text) + '</p></div></section>';
  }

  function renderImageBlock(block) {
    if (!block.on || !block.image) return '';
    var cap = block.caption ? '<figcaption>' + esc(block.caption) + '</figcaption>' : '';
    return '<section class="image-block image-' + esc(block.variant || 'contained') + '"><div class="wrap"><figure class="image-figure"><img src="' + esc(galleryImageUrl(block.image)) + '" alt="' + esc(block.caption || 'Image') + '" loading="lazy">' + cap + '</figure></div></section>';
  }

  function renderDivider(block) {
    if (!block.on) return '';
    return '<section class="divider divider-' + esc(block.variant || 'line') + '"><div class="wrap"><hr></div></section>';
  }

  function renderSpacer(block) {
    if (!block.on) return '';
    return '<section class="spacer spacer-' + esc(block.variant || 'md') + '" aria-hidden="true"></section>';
  }

  function renderHours(block) {
    if (!block.on) return '';
    var rows = '';
    (block.items || []).forEach(function (item) {
      if (block.variant === 'cards') rows += '<article class="hours-card"><strong>' + esc(item.day) + '</strong><span>' + esc(item.hours) + '</span></article>';
      else rows += '<div class="hours-row"><span>' + esc(item.day) + '</span><strong>' + esc(item.hours) + '</strong></div>';
    });
    if (!rows) return '';
    var inner = block.variant === 'cards' ? '<div class="hours-cards">' + rows + '</div>' : '<div class="hours-table">' + rows + '</div>';
    return '<section class="hours-block"' + (anchorFor('hours') ? ' id="' + anchorFor('hours') + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.title) + '</h2>' + inner + '</div></section>';
  }

  function renderMap(block) {
    if (!block.on) return '';
    var mapHtml = '';
    if (block.embed_url && block.variant === 'embed') mapHtml = '<div class="map-embed"><iframe src="' + esc(block.embed_url) + '" title="Map" loading="lazy"></iframe></div>';
    else if (block.address) mapHtml = '<div class="map-placeholder"><i class="fa-solid fa-map-location-dot"></i><p>' + esc(block.address) + '</p></div>';
    else return '';
    return '<section class="map-block"' + (anchorFor('map') ? ' id="' + anchorFor('map') + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.title) + '</h2>' + mapHtml + '</div></section>';
  }

  function renderBanner(block) {
    if (!block.on || !block.text) return '';
    var btn = block.cta_text ? '<a class="btn primary" href="' + esc(block.cta_url) + '">' + esc(block.cta_text) + '</a>' : '';
    return '<section class="promo-banner promo-' + esc(block.variant || 'solid') + '"><div class="wrap promo-inner"><span>' + esc(block.text) + '</span>' + btn + '</div></section>';
  }

  function renderQuoteBlock(block) {
    if (!block.on || !block.quote) return '';
    var cite = block.author ? '<cite>' + esc(block.author) + (block.role ? ' · ' + esc(block.role) : '') + '</cite>' : '';
    return '<section class="quote-block quote-' + esc(block.variant || 'centered') + '"><div class="wrap"><blockquote>“' + esc(block.quote) + '”</blockquote>' + cite + '</div></section>';
  }

  function renderDownload(block) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      if (!item.label) return;
      if (block.variant === 'list') html += '<a class="dl-item" href="' + esc(item.url) + '"><i class="fa-solid fa-file-arrow-down"></i> ' + esc(item.label) + (item.size ? '<span class="dl-size">' + esc(item.size) + '</span>' : '') + '</a>';
      else html += '<a class="btn primary dl-btn" href="' + esc(item.url) + '"><i class="fa-solid fa-download"></i> ' + esc(item.label) + '</a>';
    });
    if (!html) return '';
    var cls = block.variant === 'list' ? 'download-list' : 'download-buttons';
    return '<section class="download-block"' + (anchorFor('downloads') ? ' id="' + anchorFor('downloads') + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title) + '</h2><div class="' + cls + '">' + html + '</div></div></section>';
  }

  function renderAlert(block) {
    if (!block.on) return '';
    return '<section class="alert-block alert-' + esc(block.variant || 'info') + '"><div class="wrap alert-inner"><i class="fa-solid fa-circle-info"></i><div>'
      + (block.title ? '<strong>' + esc(block.title) + '</strong>' : '') + (block.text ? '<p>' + esc(block.text) + '</p>' : '') + '</div></div></section>';
  }

  function renderEvents(block) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      html += '<article class="event-item"><span class="event-date">' + esc(item.date) + '</span><div><h3>' + esc(item.title) + '</h3><span class="event-loc"><i class="fa-solid fa-location-dot"></i> ' + esc(item.location) + '</span></div></article>';
    });
    if (!html) return '';
    var cls = block.variant === 'cards' ? 'events-cards' : 'events-list';
    return '<section class="events-block"' + (anchorFor('events') ? ' id="' + anchorFor('events') + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title) + '</h2><div class="' + cls + '">' + html + '</div></div></section>';
  }

  function renderSteps(block) {
    if (!block.on) return '';
    var html = '';
    var n = 1;
    (block.items || []).forEach(function (item) {
      html += '<article class="step-item"><span class="step-num">' + n + '</span><div><h3>' + esc(item.title) + '</h3><p>' + esc(item.text) + '</p></div></article>';
      n++;
    });
    if (!html) return '';
    var cls = block.variant === 'horizontal' ? 'steps-horizontal' : 'steps-numbered';
    return '<section class="steps-block"' + (anchorFor('steps') ? ' id="' + anchorFor('steps') + '"' : '') + '><div class="wrap"><h2 class="sec-title">' + esc(block.section_title) + '</h2><div class="' + cls + '">' + html + '</div></div></section>';
  }

  function renderCountdown(block) {
    if (!block.on || !block.countdown_date) return '';
    var units = '<div class="cd-units" data-countdown="' + esc(block.countdown_date) + '"><div class="cd-unit"><strong data-cd-days>--</strong><span>Days</span></div>'
      + '<div class="cd-unit"><strong data-cd-hours>--</strong><span>Hrs</span></div><div class="cd-unit"><strong data-cd-mins>--</strong><span>Min</span></div>'
      + '<div class="cd-unit"><strong data-cd-secs>--</strong><span>Sec</span></div></div>';
    var cls = block.variant === 'inline' ? 'countdown countdown-inline' : 'countdown countdown-boxes';
    return '<section class="' + cls + '"' + (anchorFor('countdown') ? ' id="' + anchorFor('countdown') + '"' : '') + '><div class="wrap countdown-inner"><h2 class="sec-title">' + esc(block.title) + '</h2><p>' + esc(block.text) + '</p>' + units + '</div></section>';
  }

  function renderColumns(block) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      html += '<article class="col-item"><h3>' + esc(item.title) + '</h3><p>' + esc(item.text) + '</p></article>';
    });
    if (!html) return '';
    var cls = block.variant === 'three' ? 'cols-three' : 'cols-two';
    return '<section class="columns-block"><div class="wrap">' + (block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '') + '<div class="' + cls + '">' + html + '</div></div></section>';
  }

  function renderBadges(block) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      if (item.label) html += '<span class="badge-pill">' + esc(item.label) + '</span>';
    });
    if (!html) return '';
    var cls = block.variant === 'outline' ? 'badges-outline' : 'badges-pills';
    return '<section class="badges-block"><div class="wrap">' + (block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '') + '<div class="' + cls + '">' + html + '</div></div></section>';
  }

  function socialIcon(network) {
    var m = { facebook: 'fa-facebook-f', instagram: 'fa-instagram', linkedin: 'fa-linkedin-in', twitter: 'fa-x-twitter', x: 'fa-x-twitter', youtube: 'fa-youtube', tiktok: 'fa-tiktok', telegram: 'fa-telegram', whatsapp: 'fa-whatsapp', pinterest: 'fa-pinterest-p' };
    return m[network] || 'fa-link';
  }

  function btnClass(style) {
    if (style === 'secondary') return 'btn secondary';
    if (style === 'outline') return 'btn outline';
    if (style === 'ghost') return 'btn ghost';
    if (style === 'link') return 'btn link';
    return 'btn primary';
  }

  function renderButtons(block) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      if (!item.text) return;
      html += '<a class="' + btnClass(item.style) + '" href="' + esc(item.url || '#') + '">' + esc(item.text) + '</a>';
    });
    if (!html) return '';
    var v = block.variant || 'row';
    var title = block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '';
    return '<section class="buttons-block buttons-' + esc(v) + '"><div class="wrap">' + title + '<div class="btn-group btn-group-' + esc(v) + '">' + html + '</div></div></section>';
  }

  function renderCards(block, data) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      var icon = renderIconTag(item.icon, data);
      var cta = item.cta_text ? '<a class="btn primary" href="' + esc(item.cta_url || '#') + '">' + esc(item.cta_text) + '</a>' : '';
      html += '<article class="content-card"><div class="content-card-icon">' + icon + '</div><h3>' + esc(item.title) + '</h3><p>' + esc(item.text) + '</p>' + cta + '</article>';
    });
    if (!html) return '';
    var v = block.variant || 'grid';
    var title = block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '';
    return '<section class="cards-block cards-' + esc(v) + '"><div class="wrap">' + title + '<div class="content-cards content-cards-' + esc(v) + '">' + html + '</div></div></section>';
  }

  function renderSocial(block) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      html += '<a class="social-link social-' + esc(item.network) + '" href="' + esc(item.url || '#') + '"><i class="fa-brands ' + socialIcon(item.network) + '"></i><span>' + esc(item.label || item.network) + '</span></a>';
    });
    if (!html) return '';
    var v = block.variant || 'icons';
    var title = block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '';
    return '<section class="social-block social-' + esc(v) + '"' + (anchorFor('social') ? ' id="social"' : '') + '><div class="wrap">' + title + '<div class="social-links social-links-' + esc(v) + '">' + html + '</div></div></section>';
  }

  function renderMessengerButtons(items, variant) {
    var html = '';
    items.forEach(function (item) {
      html += '<a class="msg-btn msg-' + esc(item.channel) + '" href="' + esc(item.url) + '" target="_blank" rel="noopener" style="--msg-color:' + esc(item.color) + '">'
        + '<i class="fa-brands ' + esc(item.icon) + '"></i><span>' + esc(item.label) + '</span></a>';
    });
    return '<div class="msg-buttons msg-buttons-' + esc(variant || 'icons') + '">' + html + '</div>';
  }

  function renderSlider(block, data) {
    if (!block.on) return '';
    var variant = block.variant || 'fade';
    var height = block.height || 'md';
    var autoplay = block.autoplay !== false;
    var interval = Math.max(2000, Math.min(15000, parseInt(block.interval, 10) || 5000));
    var arrows = block.arrows !== false;
    var dots = block.dots !== false;
    var hues = galleryHues(data, (block.items || []).length || 4);
    var slides = '';
    var thumbs = '';
    (block.items || []).forEach(function (item, i) {
      if (i >= 12) return;
      var title = item.title || '';
      var sub = item.subtitle || '';
      var cta = item.cta_text || '';
      var ctaUrl = item.cta_url || '#';
      var image = item.image || '';
      var hue = parseInt(item.hue, 10);
      if (isNaN(hue)) hue = hues[i] || 160;
      var active = i === 0 ? ' is-active' : '';
      var bg = image
        ? 'background-image:url(' + esc(galleryImageUrl(image)) + ')'
        : 'background:linear-gradient(135deg,hsl(' + hue + ',65%,42%),hsl(' + ((hue + 40) % 360) + ',70%,55%))';
      var btn = cta ? '<a class="btn primary" href="' + esc(ctaUrl) + '">' + esc(cta) + '</a>' : '';
      var content = (title ? '<h2 class="slider-title">' + esc(title) + '</h2>' : '')
        + (sub ? '<p class="slider-sub">' + esc(sub) + '</p>' : '') + btn;
      slides += '<div class="slider-slide' + active + '" data-slide="' + i + '"><div class="slider-slide-bg" style="' + bg + '"></div>'
        + (content ? '<div class="slider-slide-content"><div class="wrap">' + content + '</div></div>' : '') + '</div>';
      if (variant === 'thumbnails') {
        var thumbBg = image
          ? 'background-image:url(' + esc(galleryImageUrl(image)) + ')'
          : 'background:linear-gradient(135deg,hsl(' + hue + ',65%,45%),hsl(' + ((hue + 30) % 360) + ',70%,55%))';
        thumbs += '<button type="button" class="slider-thumb' + active + '" data-thumb="' + i + '" style="' + thumbBg + '"></button>';
      }
    });
    if (!slides) return '';
    var secTitle = block.section_title ? '<h2 class="sec-title slider-sec-title">' + esc(block.section_title) + '</h2>' : '';
    var arrowPrev = arrows ? '<button type="button" class="slider-arrow slider-prev"><i class="fa-solid fa-chevron-left"></i></button>' : '';
    var arrowNext = arrows ? '<button type="button" class="slider-arrow slider-next"><i class="fa-solid fa-chevron-right"></i></button>' : '';
    var dotsHtml = dots ? '<div class="slider-dots" data-slider-dots></div>' : '';
    var thumbsHtml = variant === 'thumbnails' && thumbs ? '<div class="slider-thumbs">' + thumbs + '</div>' : '';
    return '<section class="slider-block slider-' + esc(variant) + ' slider-h-' + esc(height) + '" data-slider'
      + ' data-autoplay="' + (autoplay ? '1' : '0') + '" data-interval="' + interval + '"'
      + ' data-arrows="' + (arrows ? '1' : '0') + '" data-dots="' + (dots ? '1' : '0') + '">'
      + '<div class="wrap">' + secTitle + '</div>'
      + '<div class="slider-viewport"><div class="slider-track">' + slides + '</div>' + arrowPrev + arrowNext + '</div>'
      + thumbsHtml + dotsHtml + '</section>';
  }

  function renderAccordion(block) {
    if (!block.on) return '';
    var items = '';
    (block.items || []).forEach(function (item, i) {
      if (!item.title) return;
      items += '<details class="acc-item"' + (i === 0 ? ' open' : '') + '><summary>' + esc(item.title) + '</summary><p>' + esc(item.text) + '</p></details>';
    });
    if (!items) return '';
    var title = block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '';
    return '<section class="accordion-block accordion-' + esc(block.variant || 'simple') + '"><div class="wrap">' + title
      + '<div class="accordion-items">' + items + '</div></div></section>';
  }

  function renderTabs(block) {
    if (!block.on) return '';
    var tabs = '';
    var panels = '';
    var i = 0;
    (block.items || []).forEach(function (item) {
      if (!item.title) return;
      var active = i === 0 ? ' is-active' : '';
      tabs += '<button type="button" class="tabs-btn' + active + '" data-tab="' + i + '">' + esc(item.title) + '</button>';
      panels += '<div class="tabs-panel' + active + '" data-tab-panel="' + i + '">' + esc(item.text) + '</div>';
      ++i;
    });
    if (!tabs) return '';
    var title = block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '';
    return '<section class="tabs-block tabs-' + esc(block.variant || 'pills') + '" data-tabs><div class="wrap">' + title
      + '<div class="tabs-nav">' + tabs + '</div><div class="tabs-panels">' + panels + '</div></div></section>';
  }

  function renderMarquee(block) {
    if (!block.on) return '';
    var variant = block.variant || 'text';
    var speed = block.speed || 'normal';
    var inner = '';
    if (variant === 'badges') {
      (block.items || []).forEach(function (item) {
        if (item.label) inner += '<span class="marquee-badge">' + esc(item.label) + '</span>';
      });
      if (!inner) return '';
    } else {
      if (!block.text) return '';
      inner = '<span>' + esc(block.text) + '</span><span aria-hidden="true">' + esc(block.text) + '</span>';
    }
    return '<section class="marquee-block marquee-' + esc(variant) + ' marquee-speed-' + esc(speed) + '"><div class="marquee-track">' + inner + '</div></section>';
  }

  function renderMessengers(block) {
    if (!block.on) return '';
    var items = messengerItemsFromBlock(block);
    if (!items.length) return '';
    var v = block.variant || 'icons';
    var title = block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '';
    var id = anchorFor('messengers');
    return '<section class="messengers-block messengers-' + esc(v) + '"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap">'
      + title + renderMessengerButtons(items, v) + '</div></section>';
  }

  function renderFloatingMessengers(data) {
    if (!data.msg_floating) return '';
    var items = messengerItemsFromData(data);
    if (!items.length) return '';
    var styles = cfg.msgFloatStyles || {};
    var style = data.msg_style || 'stack';
    if (!styles[style]) style = 'stack';
    var positions = cfg.msgPositions || {};
    var pos = data.msg_position || 'right';
    if (!positions[pos]) pos = 'right';
    var buttons = '';
    items.forEach(function (item) {
      buttons += '<a class="msg-float-btn msg-' + esc(item.channel) + '" href="' + esc(item.url) + '" target="_blank" rel="noopener" title="' + esc(item.label) + '" style="--msg-color:' + esc(item.color) + '">'
        + '<i class="fa-brands ' + esc(item.icon) + '"></i>'
        + (style === 'bar' ? '<span>' + esc(item.label) + '</span>' : '') + '</a>';
    });
    var toggle = style === 'expand'
      ? '<button type="button" class="msg-float-toggle" aria-expanded="false" data-msg-toggle><i class="fa-solid fa-comments"></i><i class="fa-solid fa-xmark msg-float-close"></i></button>'
      : '';
    var floatHtml = '<div class="msg-float msg-float-' + esc(pos) + ' msg-float-' + esc(style) + '" data-msg-float>'
      + '<div class="msg-float-items">' + buttons + '</div>' + toggle + '</div>';
    return '<div class="elb-sec-shell elb-sec-shell-float" data-elb-section data-elb-pick="messengers">'
      + '<div class="elb-sec-bar elb-sec-bar-float"><i class="fa-solid fa-comments"></i><span>' + esc(MSG.messengers || 'Messengers') + '</span>'
      + '<span class="elb-sec-pick">' + esc(MSG.pick_in_preview || 'Click to edit') + '</span></div>'
      + floatHtml + '</div>';
  }

  function renderTrust(block, data) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      html += '<div class="trust-item"><div class="trust-icon">' + renderIconTag(item.icon, data) + '</div><strong>' + esc(item.title) + '</strong><span>' + esc(item.text) + '</span></div>';
    });
    if (!html) return '';
    var v = block.variant || 'row';
    var title = block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '';
    return '<section class="trust-block trust-' + esc(v) + '"><div class="wrap">' + title + '<div class="trust-items trust-items-' + esc(v) + '">' + html + '</div></div></section>';
  }

  function renderCallout(block) {
    if (!block.on) return '';
    var cta = block.cta_text ? '<a class="btn primary" href="' + esc(block.cta_url || '#') + '">' + esc(block.cta_text) + '</a>' : '';
    var icon = block.variant === 'success' ? 'fa-circle-check' : (block.variant === 'promo' ? 'fa-gift' : (block.variant === 'info' ? 'fa-circle-info' : 'fa-lightbulb'));
    return '<section class="callout-block callout-' + esc(block.variant || 'tip') + '"><div class="wrap"><div class="callout-inner"><i class="fa-solid ' + icon + '"></i><div>'
      + (block.title ? '<h3>' + esc(block.title) + '</h3>' : '') + '<p>' + esc(block.text) + '</p>' + cta + '</div></div></div></section>';
  }

  function renderMediaText(block) {
    if (!block.on) return '';
    var img = block.image ? '<img src="' + esc(block.image) + '" alt="">' : '<div class="media-placeholder"><i class="fa-solid fa-image"></i></div>';
    var cta = block.cta_text ? '<a class="btn primary" href="' + esc(block.cta_url || '#') + '">' + esc(block.cta_text) + '</a>' : '';
    var side = block.side === 'right' ? ' media-right' : '';
    return '<section class="media-text-block media-' + esc(block.variant || 'split') + side + '"><div class="wrap"><div class="media-text-grid"><div class="media-visual">' + img + '</div><div class="media-copy"><h2>' + esc(block.title) + '</h2><p>' + esc(block.text) + '</p>' + cta + '</div></div></div></section>';
  }

  function renderMenu(block) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      html += '<div class="menu-item"><div class="menu-item-head"><strong>' + esc(item.name) + '</strong><span class="menu-price">' + esc(item.price) + '</span></div><p>' + esc(item.desc) + '</p></div>';
    });
    if (!html) return '';
    var v = block.variant || 'list';
    var title = block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '';
    return '<section class="menu-block menu-' + esc(v) + '"' + (anchorFor('menu') ? ' id="menu"' : '') + '><div class="wrap">' + title + '<div class="menu-items menu-items-' + esc(v) + '">' + html + '</div></div></section>';
  }

  function renderStatsBar(block) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      html += '<div class="stats-bar-item"><strong>' + esc(item.value) + '</strong><span>' + esc(item.label) + '</span></div>';
    });
    if (!html) return '';
    return '<section class="stats-bar-block stats-bar-' + esc(block.variant || 'inline') + '"><div class="wrap"><div class="stats-bar-inner">' + html + '</div></div></section>';
  }

  function renderComparison(block) {
    if (!block.on) return '';
    var rows = '';
    (block.items || []).forEach(function (item) {
      rows += '<tr><td>' + esc(item.label) + '</td><td>' + esc(item.a) + '</td><td>' + esc(item.b) + '</td></tr>';
    });
    if (!rows) return '';
    var title = block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '';
    return '<section class="comparison-block comparison-' + esc(block.variant || 'table') + '"><div class="wrap">' + title
      + '<table class="comparison-table"><thead><tr><th></th><th>' + esc(block.left_title || 'A') + '</th><th>' + esc(block.right_title || 'B') + '</th></tr></thead><tbody>' + rows + '</tbody></table></div></section>';
  }

  function renderContactBar(block) {
    if (!block.on) return '';
    var links = '';
    if (block.phone) links += '<a href="tel:' + esc(String(block.phone).replace(/\s+/g, '')) + '"><i class="fa-solid fa-phone"></i> ' + esc(block.phone) + '</a>';
    if (block.email) links += '<a href="mailto:' + esc(block.email) + '"><i class="fa-solid fa-envelope"></i> ' + esc(block.email) + '</a>';
    if (block.whatsapp) links += '<a href="https://wa.me/' + esc(String(block.whatsapp).replace(/\D/g, '')) + '"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>';
    var cta = block.cta_text ? '<a class="btn primary" href="' + esc(block.cta_url || '#') + '">' + esc(block.cta_text) + '</a>' : '';
    return '<section class="contact-bar-block contact-bar-' + esc(block.variant || 'strip') + '"><div class="wrap"><div class="contact-bar-inner"><div class="contact-bar-links">' + links + '</div>' + cta + '</div></div></section>';
  }

  function renderIconList(block, data) {
    if (!block.on) return '';
    var html = '';
    (block.items || []).forEach(function (item) {
      html += '<div class="icon-list-item"><div class="icon-list-icon">' + renderIconTag(item.icon, data) + '</div><div><strong>' + esc(item.title) + '</strong><p>' + esc(item.text) + '</p></div></div>';
    });
    if (!html) return '';
    var v = block.variant || 'vertical';
    var title = block.section_title ? '<h2 class="sec-title">' + esc(block.section_title) + '</h2>' : '';
    return '<section class="icon-list-block icon-list-' + esc(v) + '"><div class="wrap">' + title + '<div class="icon-list-items icon-list-items-' + esc(v) + '">' + html + '</div></div></section>';
  }

  function renderAppCta(block) {
    if (!block.on) return '';
    var btns = '<a class="app-badge app-ios" href="' + esc(block.ios_url || '#') + '"><i class="fa-brands fa-apple"></i> App Store</a>'
      + '<a class="app-badge app-android" href="' + esc(block.android_url || '#') + '"><i class="fa-brands fa-google-play"></i> Google Play</a>';
    return '<section class="app-cta-block app-cta-' + esc(block.variant || 'badges') + '"' + (anchorFor('app') ? ' id="app"' : '') + '><div class="wrap"><div class="app-cta-inner"><div><h2>' + esc(block.title) + '</h2><p>' + esc(block.text) + '</p></div><div class="app-badges">' + btns + '</div></div></div></section>';
  }

  function renderContact(block) {
    if (!block.on) return '';
    var id = anchorFor('contact');
    var lines = '';
    if (block.phone) lines += '<a href="tel:' + esc(block.phone.replace(/\s+/g, '')) + '"><i class="fa-solid fa-phone"></i> ' + esc(block.phone) + '</a>';
    if (block.email) lines += '<a href="mailto:' + esc(block.email) + '"><i class="fa-solid fa-envelope"></i> ' + esc(block.email) + '</a>';
    if (block.address) lines += '<p class="addr"><i class="fa-solid fa-location-dot"></i> ' + esc(block.address) + '</p>';
    var btn = '<a class="btn primary" href="' + esc(block.cta_url) + '">' + esc(block.cta_text) + '</a>';
    if (block.variant === 'split') {
      return '<section class="contact contact-split"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap contact-split-grid"><div><h2 class="sec-title">'
        + esc(block.title) + '</h2><div class="contact-lines">' + lines + '</div></div><div>' + btn + '</div></div></section>';
    }
    if (block.variant === 'minimal') {
      return '<section class="contact contact-minimal"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap contact-minimal-inner"><h2>'
        + esc(block.title) + '</h2><div class="contact-lines">' + lines + '</div>' + btn + '</div></section>';
    }
    return '<section class="contact contact-card"' + (id ? ' id="' + id + '"' : '') + '><div class="wrap contact-box"><h2 class="sec-title light">'
      + esc(block.title) + '</h2><div class="contact-lines">' + lines + '</div>' + btn + '</div></section>';
  }

  function renderFooter(data) {
    var y = new Date().getFullYear();
    var style = data.footer_style || 'minimal';
    var name = data.business_name || '';
    var tagline = data.tagline || '';
    var extraText = data.footer_text || '';
    var social = '';
    if (data.social_facebook) social += '<a href="' + esc(data.social_facebook) + '" rel="noopener" target="_blank"><i class="fa-brands fa-facebook"></i></a>';
    if (data.social_instagram) social += '<a href="' + esc(data.social_instagram) + '" rel="noopener" target="_blank"><i class="fa-brands fa-instagram"></i></a>';
    if (data.social_linkedin) social += '<a href="' + esc(data.social_linkedin) + '" rel="noopener" target="_blank"><i class="fa-brands fa-linkedin"></i></a>';
    var navLinks = footerNavLinksHtml(data);
    var copy = '&copy; ' + y + (extraText ? ' · ' + esc(extraText) : '');
    if (style === 'columns') {
      return '<footer class="footer footer-columns"><div class="wrap footer-cols"><div><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p></div>'
        + '<div><strong>Links</strong>' + navLinks + '</div><div><strong>' + esc(String(y)) + '</strong><p>' + esc(extraText || name) + '</p></div></div></footer>';
    }
    if (style === 'centered') {
      return '<footer class="footer footer-centered"><div class="wrap"><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p>'
        + (extraText ? '<p class="footer-extra">' + esc(extraText) + '</p>' : '')
        + '<span>' + copy + '</span></div></footer>';
    }
    if (style === 'social') {
      return '<footer class="footer footer-social"><div class="wrap"><div class="footer-social-brand"><strong>' + esc(name) + '</strong>'
        + (social ? '<div class="footer-social-icons">' + social + '</div>' : '') + '</div><span>' + copy + ' ' + esc(name) + '</span></div></footer>';
    }
    if (style === 'dark') {
      return '<footer class="footer footer-dark"><div class="wrap footer-dark-inner"><div><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p></div>'
        + navLinks + '<span class="footer-copy">' + copy + '</span></div></footer>';
    }
    if (style === 'gradient') {
      return '<footer class="footer footer-gradient"><div class="wrap footer-gradient-inner"><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p>'
        + (social ? '<div class="footer-social-icons">' + social + '</div>' : '')
        + '<span class="footer-copy">' + copy + '</span></div></footer>';
    }
    if (style === 'mega') {
      return '<footer class="footer footer-mega"><div class="wrap footer-mega-grid"><div class="footer-mega-brand"><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p>'
        + (social ? '<div class="footer-social-icons">' + social + '</div>' : '') + '</div>'
        + '<div class="footer-mega-links"><strong>Menu</strong>' + navLinks + '</div>'
        + '<div class="footer-mega-meta"><strong>' + esc(String(y)) + '</strong><p>' + esc(extraText || name) + '</p></div></div>'
        + '<div class="wrap footer-mega-bar"><span>' + copy + '</span></div></footer>';
    }
    if (style === 'split') {
      return '<footer class="footer footer-split"><div class="wrap footer-split-inner"><div class="footer-split-left"><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p></div>'
        + '<div class="footer-split-right">' + navLinks + '<span>' + copy + '</span></div></div></footer>';
    }
    if (style === 'compact') {
      return '<footer class="footer footer-compact"><div class="wrap footer-compact-inner"><span class="footer-compact-brand">' + esc(name) + '</span>'
        + navLinks + '<span class="footer-compact-copy">' + copy + '</span></div></footer>';
    }
    if (style === 'branded') {
      return '<footer class="footer footer-branded"><div class="wrap footer-branded-inner"><div class="footer-watermark" aria-hidden="true">' + esc(name) + '</div>'
        + '<div class="footer-branded-content"><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p>'
        + (social ? '<div class="footer-social-icons">' + social + '</div>' : '')
        + '<span>' + copy + '</span></div></div></footer>';
    }
    if (style === 'contact') {
      return '<footer class="footer footer-contact"><div class="wrap footer-contact-inner"><div><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p></div>'
        + '<div class="footer-contact-links">' + navLinks
        + (social ? '<div class="footer-social-icons">' + social + '</div>' : '') + '</div>'
        + '<span class="footer-copy">' + copy + '</span></div></footer>';
    }
    if (style === 'wave') {
      return '<footer class="footer footer-wave"><div class="footer-wave-shape" aria-hidden="true"></div><div class="wrap footer-wave-inner"><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p>'
        + navLinks + (social ? '<div class="footer-social-icons">' + social + '</div>' : '')
        + '<span>' + copy + '</span></div></footer>';
    }
    if (style === 'micro') return '<footer class="footer footer-micro"><div class="wrap footer-micro-inner"><span>' + copy + '</span><span>' + esc(name) + '</span></div></footer>';
    if (style === 'tall') return '<footer class="footer footer-tall"><div class="wrap footer-tall-inner"><div class="footer-tall-brand"><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p>'
      + (social ? '<div class="footer-social-icons">' + social + '</div>' : '') + '</div>' + navLinks + '<span class="footer-copy">' + copy + '</span></div></footer>';
    if (style === 'newsletter') return '<footer class="footer footer-newsletter"><div class="wrap footer-newsletter-inner"><div><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p></div>'
      + '<form class="footer-newsletter-form" onsubmit="return false"><input type="email" placeholder="Email"><button type="submit" class="btn primary">Subscribe</button></form>'
      + '<span class="footer-copy">' + copy + '</span></div></footer>';
    if (style === 'double') return '<footer class="footer footer-double"><div class="wrap footer-double-top"><div><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p></div>'
      + navLinks + (social ? '<div class="footer-social-icons">' + social + '</div>' : '') + '</div><div class="wrap footer-double-bar"><span>' + copy + '</span></div></footer>';
    if (style === 'ribbon') return '<footer class="footer footer-ribbon"><div class="footer-ribbon-accent"></div><div class="wrap footer-ribbon-inner"><strong>' + esc(name) + '</strong>' + navLinks + '<span>' + copy + '</span></div></footer>';
    if (style === 'map_row') return '<footer class="footer footer-map-row"><div class="wrap footer-map-grid"><div class="footer-map-placeholder"><i class="fa-solid fa-map-location-dot"></i></div>'
      + '<div><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p>' + navLinks + '<span class="footer-copy">' + copy + '</span></div></div></footer>';
    if (style === 'minimal_dark') return '<footer class="footer footer-minimal-dark"><div class="wrap">' + copy + ' · ' + esc(name) + '</div></footer>';
    if (style === 'sticky_bar') return '<footer class="footer footer-sticky-bar"><div class="wrap footer-sticky-inner"><span class="footer-sticky-brand">' + esc(name) + '</span>' + navLinks + '<span>' + copy + '</span></div></footer>';
    if (style === 'tall_xl') return '<footer class="footer footer-tall-xl"><div class="wrap footer-tall-xl-inner"><div class="footer-tall-brand"><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p>'
      + (social ? '<div class="footer-social-icons">' + social + '</div>' : '') + '</div>' + navLinks + '<span class="footer-copy">' + copy + '</span></div></footer>';
    if (style === 'mega_tall') return '<footer class="footer footer-mega-tall"><div class="wrap footer-mega-tall-grid"><div class="footer-mega-brand"><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p>'
      + (social ? '<div class="footer-social-icons">' + social + '</div>' : '') + '</div><div class="footer-mega-links"><strong>Menu</strong>' + navLinks + '</div>'
      + '<div><strong>' + esc(String(y)) + '</strong><p>' + esc(extraText || name) + '</p></div><div class="footer-mega-cta"><span class="footer-copy">' + copy + '</span></div></div></footer>';
    if (style === 'hero') return '<footer class="footer footer-hero"><div class="wrap footer-hero-inner"><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p>'
      + navLinks + (social ? '<div class="footer-social-icons">' + social + '</div>' : '') + '<span class="footer-copy">' + copy + '</span></div></footer>';
    if (style === 'columns4') return '<footer class="footer footer-columns4"><div class="wrap footer-cols4"><div><strong>' + esc(name) + '</strong><p>' + esc(tagline) + '</p></div>'
      + '<div><strong>Links</strong>' + navLinks + '</div><div><strong>Social</strong>' + (social ? '<div class="footer-social-icons">' + social + '</div>' : '<p>—</p>') + '</div>'
      + '<div><strong>' + esc(String(y)) + '</strong><p>' + esc(extraText || name) + '</p></div></div><div class="wrap footer-cols4-bar"><span>' + copy + '</span></div></footer>';
    return '<footer class="footer footer-minimal"><div class="wrap">' + copy + ' ' + esc(name) + '</div></footer>';
  }

  var CHROME_CSS = '.nav-inner{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;width:100%;max-width:68rem;margin:0 auto;padding:0 1.25rem}'
    + '.nav-glass-wrap{max-width:68rem;margin:0 auto;padding:.45rem 1rem}.nav-glass .nav-glass-wrap{background:rgba(255,255,255,.55);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.65);border-radius:1rem;box-shadow:0 8px 32px rgba(15,23,42,.08)}'
    + '.nav-centered,.nav-stacked{padding:.65rem 0}.nav-inner-centered,.nav-inner-stacked{flex-direction:column;align-items:center;text-align:center;gap:.55rem}'
    + '.nav-brand-center .brand span{display:block;margin:.2rem 0 0}.nav-links-row{width:100%;display:flex;justify-content:center}'
    + '.nav-inner-stacked .brand{width:100%;text-align:center}.nav-inner-stacked .nav-links{justify-content:center;width:100%}'
    + '.nav-minimal{padding:.55rem 1.25rem}.nav-minimal .brand span{display:none}.nav-minimal .nav-links a{font-size:.82rem}'
    + '.nav-dark{background:linear-gradient(135deg,#0f172a,#1e293b);border-bottom:none}.nav-dark .brand,.nav-dark .brand span{color:#fff}.nav-dark .nav-links a{color:#e2e8f0}.nav-dark .nav-links a:hover{color:#fff}'
    + '.nav-gradient{background:linear-gradient(135deg,var(--c),var(--c2));border-bottom:none}.nav-gradient .brand,.nav-gradient .brand span{color:#fff}.nav-gradient .nav-links a{color:rgba(255,255,255,.92)}.nav-gradient .nav-cta{background:#fff;color:var(--c)!important}'
    + '.nav-pill .nav-links a{padding:.35rem .75rem;border-radius:999px;background:color-mix(in srgb,var(--c) 10%,#fff);color:var(--c)}.nav-pill .nav-links a:hover{background:color-mix(in srgb,var(--c) 18%,#fff)}'
    + '.nav-bordered{border-bottom:3px solid var(--c);box-shadow:0 4px 20px rgba(15,23,42,.06)}.nav-bordered .brand{font-size:1.15rem}'
    + '.nav-cta.btn{padding:.5rem 1rem;font-size:.85rem;white-space:nowrap;flex-shrink:0}.nav-cta{margin-left:auto}'
    + '.footer-dark{background:#0f172a;color:#e2e8f0;padding:2.5rem 0}.footer-dark strong{color:#fff}.footer-dark .footer-nav a{color:#94a3b8}.footer-dark-inner{display:grid;gap:1rem}'
    + '.footer-gradient{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;padding:2.5rem 0;text-align:center}.footer-gradient .footer-social-icons a{color:#fff}.footer-gradient-inner{display:flex;flex-direction:column;align-items:center;gap:.65rem}'
    + '.footer-mega{background:#f8fafc;border-top:1px solid #e2e8f0;padding:0}.footer-mega-grid{display:grid;grid-template-columns:1.2fr 1fr .8fr;gap:1.5rem;padding:2rem 1.25rem}.footer-mega-bar{border-top:1px solid #e2e8f0;padding:.75rem 1.25rem;text-align:center;color:#64748b;font-size:.82rem}'
    + '.footer-split{background:#fff;border-top:1px solid #e2e8f0;padding:2rem 0}.footer-split-inner{display:flex;flex-wrap:wrap;justify-content:space-between;gap:1.5rem;align-items:flex-start}.footer-split-right{text-align:right}'
    + '.footer-compact{background:#fff;border-top:1px solid #e2e8f0;padding:.85rem 0}.footer-compact-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;font-size:.82rem}.footer-compact-brand{font-weight:700;color:var(--c)}.footer-compact-copy{color:#94a3b8}'
    + '.footer-branded{position:relative;overflow:hidden;background:#0f172a;color:#e2e8f0;padding:3rem 0}.footer-watermark{position:absolute;inset:auto -5% -30% auto;font-size:clamp(4rem,18vw,12rem);font-weight:900;color:rgba(255,255,255,.04);line-height:1;pointer-events:none;white-space:nowrap}'
    + '.footer-branded-content{position:relative;z-index:1;text-align:center;display:flex;flex-direction:column;align-items:center;gap:.5rem}.footer-branded strong{color:#fff;font-size:1.1rem}'
    + '.footer-contact{background:#fff;border-top:3px solid var(--c);padding:2rem 0}.footer-contact-inner{display:grid;gap:1rem}.footer-contact-links{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem}'
    + '.footer-wave{position:relative;background:color-mix(in srgb,var(--c) 8%,#fff);padding:2.5rem 0 2rem;margin-top:2rem}.footer-wave-shape{position:absolute;top:-28px;left:0;right:0;height:28px;background:color-mix(in srgb,var(--c) 8%,#fff);clip-path:ellipse(55% 100% at 50% 100%)}'
    + '.footer-wave-inner{text-align:center;display:flex;flex-direction:column;align-items:center;gap:.5rem}.footer-copy{color:#94a3b8;font-size:.82rem}'
    + '.nav-burger-btn{display:none;border:0;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c);width:2.5rem;height:2.5rem;border-radius:.65rem;cursor:pointer;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;padding:0}'
    + '.nav-burger-mobile .nav-burger-btn,.nav-burger-always .nav-burger-btn{display:inline-flex}'
    + '.nav-burger-always .nav-links-desktop,.nav-burger-always .nav-inner>.nav-cta,.nav-burger-always .nav-links-row .nav-links{display:none}'
    + '.nav-drawer{position:fixed;top:0;right:0;width:min(18rem,88vw);height:100vh;background:#fff;box-shadow:-8px 0 32px rgba(15,23,42,.12);z-index:50;padding:4.5rem 1.25rem 1.5rem;transform:translateX(100%);transition:transform .25s ease;overflow-y:auto}'
    + '.nav-drawer:not([hidden]){transform:translateX(0)}.nav-drawer .nav-links{flex-direction:column;gap:.25rem;width:100%}'
    + '.nav-drawer .nav-links a{display:block;padding:.7rem .85rem;border-radius:.55rem;font-size:1rem}.nav-drawer .nav-cta{margin:1rem 0 0;width:100%;justify-content:center}'
    + '.nav-overlay{position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:40}body.nav-open{overflow:hidden}'
    + '.nav-dark .nav-burger-btn{background:rgba(255,255,255,.12);color:#fff}.nav-gradient .nav-burger-btn{background:rgba(255,255,255,.2);color:#fff}'
    + '.nav-micro{padding:.35rem 1rem;min-height:auto}.nav-micro .brand{font-size:.88rem}.nav-micro .nav-links a{font-size:.78rem;padding:.2rem 0}'
    + '.nav-tall{padding:1.35rem 0}.nav-tall .brand{font-size:1.35rem}.nav-tall .brand span{font-size:1rem;display:block;margin:.35rem 0 0}'
    + '.nav-floating-wrap{max-width:68rem;margin:.65rem auto;padding:0 1rem}.nav-floating .nav-floating-wrap .nav-inner{background:#fff;border-radius:1rem;box-shadow:0 12px 40px rgba(15,23,42,.1);border:1px solid #e2e8f0;padding:.75rem 1.25rem}'
    + '.nav-transparent{background:transparent;border-bottom:none;backdrop-filter:none}.nav-transparent .brand{color:var(--c)}'
    + '.nav-underline .nav-links a{border-bottom:2px solid transparent;padding-bottom:.15rem}.nav-underline .nav-links a:hover{border-bottom-color:var(--c);color:var(--c)}'
    + '.nav-inner-split .nav-split-links{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;border-left:1px solid #e2e8f0;padding-left:1rem;margin-left:auto}'
    + '.nav-announce{background:linear-gradient(90deg,var(--c),var(--c2));color:#fff;text-align:center;font-size:.78rem;font-weight:600;padding:.4rem 1rem}'
    + '.nav-mega{padding:1.1rem 0}.nav-mega .brand{font-size:1.2rem}.nav-mega .nav-links a{font-size:.95rem}'
    + '.brand{display:flex;align-items:center;gap:.55rem;flex-wrap:wrap}.brand-icon{width:2.25rem;height:2.25rem;border-radius:.65rem;display:grid;place-items:center;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c);font-size:1.05rem;flex-shrink:0}'
    + '.nav-dark .brand-icon,.nav-gradient .brand-icon,.nav-hero_bar .brand-icon{background:rgba(255,255,255,.15);color:#fff}'
    + '.nav-tall_xl{padding:2rem 0}.nav-tall_xl .brand{font-size:1.55rem}.nav-tall_xl .brand-icon{width:3rem;height:3rem;font-size:1.35rem;border-radius:.85rem}'
    + '.nav-hero_bar{background:linear-gradient(135deg,var(--c),var(--c2));border-bottom:none;padding:1.5rem 0}.nav-hero_bar .brand,.nav-hero_bar .brand span{color:#fff}.nav-hero_bar .nav-links a{color:rgba(255,255,255,.92)}'
    + '.nav-boxed-wrap{max-width:68rem;margin:.65rem auto;padding:0 1rem}.nav-boxed .nav-boxed-wrap .nav-inner{background:#fff;border-radius:1rem;box-shadow:0 12px 40px rgba(15,23,42,.1);border:1px solid #e2e8f0;padding:1rem 1.35rem}'
    + '.nav-ribbon{position:relative}.nav-ribbon-accent{position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--c),var(--c2))}'
    + '.nav-neon{box-shadow:0 0 0 1px color-mix(in srgb,var(--c) 35%,#fff),0 0 24px color-mix(in srgb,var(--c) 25%,transparent)}'
    + '.nav-corporate{border-bottom:4px double #e2e8f0;background:#fff}.nav-corporate .brand{font-size:1.1rem;letter-spacing:.02em}'
    + '.nav-icon_focus .brand-icon{width:3.25rem;height:3.25rem;font-size:1.5rem;border-radius:1rem}.nav-icon_focus .brand{font-size:1rem}'
    + '.nav-wide{padding:1.25rem 0}.nav-wide .nav-inner{max-width:80rem}.nav-wide .nav-links{gap:1.35rem}.nav-wide .nav-links a{font-size:.95rem}'
    + '.elb-has-bg{position:relative;overflow:hidden;isolation:isolate;background:none!important}.elb-block-bg{position:absolute;inset:0;width:100%;height:100%;background-size:cover;background-position:center;background-repeat:no-repeat;z-index:0}'
    + '.elb-block-bg-overlay{position:absolute;inset:0;background:rgba(15,23,42,var(--elb-bg-overlay,.4));z-index:1;pointer-events:none}.elb-block-inner{position:relative;z-index:2}'
    + 'section.elb-custom-colors .sec-title,section.elb-custom-colors h1,section.elb-custom-colors h2,section.elb-custom-colors h3,section.elb-custom-colors h4,section.elb-custom-colors .feat h3{color:var(--elb-heading)}'
    + 'section.elb-custom-colors p,section.elb-custom-colors li,section.elb-custom-colors blockquote,section.elb-custom-colors cite,section.elb-custom-colors .about-text,section.elb-custom-colors .text-inner p,section.elb-custom-colors .heading-sub,section.elb-custom-colors .feat p,section.elb-custom-colors .feat-list-item p,section.elb-custom-colors .test-card p,section.elb-custom-colors .test-card footer span,section.elb-custom-colors .price-feats,section.elb-custom-colors .price-feats li,section.elb-custom-colors .price-amt span,section.elb-custom-colors .faq-acc p,section.elb-custom-colors .faq-item p,section.elb-custom-colors .info-text-box p,section.elb-custom-colors .info-quote blockquote,section.elb-custom-colors .info-quote cite,section.elb-custom-colors .stat span,section.elb-custom-colors .col-item p,section.elb-custom-colors .event-loc,section.elb-custom-colors .cd-unit span,section.elb-custom-colors .image-figure figcaption,section.elb-custom-colors .quote-block cite,section.elb-custom-colors .acc-item p,section.elb-custom-colors .tabs-panel,section.elb-custom-colors .cta-banner-inner p,section.elb-custom-colors .cta-inline-inner p,section.elb-custom-colors .cta-split-grid p,section.elb-custom-colors .contact-box p,section.elb-custom-colors .contact-split-grid p,section.elb-custom-colors .hero p,section.elb-custom-colors .hero-card span,section.elb-custom-colors .promo-inner span,section.elb-custom-colors .svc-card p,section.elb-custom-colors .team-member p,section.elb-custom-colors .tl-item p{color:var(--elb-text)}'
    + 'section.elb-custom-colors .test-card footer strong,section.elb-custom-colors .col-item h3{color:var(--elb-heading)}'
    + 'section.elb-custom-colors .btn,section.elb-custom-colors a.btn{background:var(--elb-btn,var(--c));color:var(--elb-btn-text,#fff)}'
    + '.footer-micro{padding:.45rem 0;font-size:.72rem;color:#94a3b8}.footer-micro-inner{display:flex;justify-content:space-between;gap:.5rem;flex-wrap:wrap}'
    + '.footer-tall{padding:4rem 0;background:#f8fafc}.footer-tall-inner{display:grid;gap:1.5rem}.footer-tall-brand strong{font-size:1.25rem}'
    + '.footer-newsletter{padding:2.5rem 0;background:#fff;border-top:1px solid #e2e8f0}.footer-newsletter-inner{display:grid;gap:1rem}'
    + '.footer-newsletter-form{display:flex;flex-wrap:wrap;gap:.5rem}.footer-newsletter-form input{flex:1;min-width:12rem;padding:.65rem 1rem;border:1px solid #e2e8f0;border-radius:.65rem}'
    + '.footer-double{padding:0;background:#f8fafc}.footer-double-top{display:grid;grid-template-columns:1.2fr 1fr auto;gap:1.5rem;padding:2rem 1.25rem;align-items:start}'
    + '.footer-double-bar{border-top:1px solid #e2e8f0;padding:.75rem 1.25rem;text-align:center;color:#64748b;font-size:.82rem}'
    + '.footer-ribbon{position:relative;padding:2rem 0;background:#fff}.footer-ribbon-accent{position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--c),var(--c2))}'
    + '.footer-ribbon-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem}'
    + '.footer-map-row{padding:2rem 0;background:#fff}.footer-map-grid{display:grid;grid-template-columns:1fr 1.2fr;gap:1.5rem;align-items:center}'
    + '.footer-map-placeholder{aspect-ratio:16/10;border-radius:1rem;background:color-mix(in srgb,var(--c) 10%,#fff);display:grid;place-items:center;color:var(--c);font-size:2.5rem;border:1px solid #e2e8f0}'
    + '.footer-minimal-dark{background:#0f172a;color:#94a3b8;padding:.85rem 0;text-align:center;font-size:.82rem}'
    + '.footer-sticky-bar{position:sticky;bottom:0;z-index:5;background:rgba(255,255,255,.96);backdrop-filter:blur(8px);border-top:1px solid #e2e8f0;padding:.55rem 0;box-shadow:0 -4px 20px rgba(15,23,42,.06)}'
    + '.footer-sticky-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;font-size:.8rem}.footer-sticky-brand{font-weight:700;color:var(--c)}'
    + '.footer-tall-xl{padding:5.5rem 0;background:#f8fafc}.footer-tall-xl-inner{display:grid;gap:2rem}.footer-tall-xl .footer-tall-brand strong{font-size:1.45rem}'
    + '.footer-mega-tall{background:#0f172a;color:#e2e8f0;padding:0}.footer-mega-tall-grid{display:grid;grid-template-columns:1.3fr 1fr .9fr .8fr;gap:2rem;padding:4rem 1.25rem}.footer-mega-tall strong{color:#fff}'
    + '.footer-hero{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;padding:4rem 0;text-align:center}.footer-hero-inner{display:flex;flex-direction:column;align-items:center;gap:.75rem}.footer-hero .footer-social-icons a{color:#fff}'
    + '.footer-columns4{background:#f8fafc;border-top:1px solid #e2e8f0;padding:0}.footer-cols4{display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;padding:3rem 1.25rem}.footer-cols4-bar{border-top:1px solid #e2e8f0;padding:.85rem 1.25rem;text-align:center;color:#64748b;font-size:.82rem}'
    + '@media(max-width:800px){.footer-mega-grid,.footer-double-top,.footer-map-grid,.footer-mega-tall-grid,.footer-cols4{grid-template-columns:1fr}.footer-split-inner,.footer-contact-links{flex-direction:column;text-align:center}.footer-split-right{text-align:center}.nav-inner-split{flex-wrap:wrap}.nav-inner-split .nav-split-links{border-left:none;padding-left:0;margin-left:0;width:100%}'
    + '.nav-burger-mobile .nav-links-desktop,.nav-burger-mobile .nav-inner>.nav-cta,.nav-burger-mobile .nav-links-row .nav-links,.nav-burger-mobile .nav-split-links .nav-links{display:none}.nav-burger-mobile .nav-burger-btn{display:inline-flex}.nav-cta{margin-left:0;width:100%;justify-content:center}}';

  var SLIDER_CSS = '.slider-block{padding:0;overflow:hidden}.slider-sec-title{padding-top:1.25rem;margin-bottom:.5rem}'
    + '.slider-viewport{position:relative;overflow:hidden}.slider-track{position:relative}'
    + '.slider-h-sm .slider-slide{min-height:180px}.slider-h-md .slider-slide{min-height:240px}'
    + '.slider-h-lg .slider-slide{min-height:300px}.slider-h-xl .slider-slide{min-height:50vh}'
    + '.slider-slide{position:relative;display:none;min-height:inherit}.slider-slide.is-active{display:block}'
    + '.slider-fade .slider-slide.is-active{animation:sliderFade .6s ease}.slider-slide-bg{position:absolute;inset:0;background-size:cover;background-position:center}'
    + '.slider-slide-bg::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(15,23,42,.35),rgba(15,23,42,.55))}'
    + '.slider-slide-content{position:relative;z-index:1;display:flex;align-items:center;min-height:inherit;padding:1.5rem 0;color:#fff;text-align:center}'
    + '.slider-slide-content .wrap{width:100%}.slider-title{margin:0 0 .45rem;font-size:clamp(1.1rem,3vw,1.75rem);text-shadow:0 2px 12px rgba(0,0,0,.25)}'
    + '.slider-sub{margin:0 0 .75rem;font-size:.78rem;opacity:.92;max-width:28rem;margin-left:auto;margin-right:auto;line-height:1.5}'
    + '.slider-slide-content .btn.primary{background:#fff;color:var(--c);font-size:.72rem}'
    + '.slider-arrow{position:absolute;top:50%;transform:translateY(-50%);z-index:3;width:2rem;height:2rem;border:0;border-radius:999px;background:rgba(255,255,255,.92);color:var(--c);cursor:pointer;display:grid;place-items:center;font-size:.7rem}'
    + '.slider-prev{left:.5rem}.slider-next{right:.5rem}'
    + '.slider-dots{display:flex;justify-content:center;gap:.35rem;padding:.65rem 0 1rem}'
    + '.slider-dot{width:.45rem;height:.45rem;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0}.slider-dot.is-active{background:var(--c)}'
    + '.slider-cards .slider-track{display:flex;gap:.65rem;overflow-x:auto;scroll-snap-type:x mandatory;padding:1rem 1.25rem 1.25rem}'
    + '.slider-cards .slider-slide{flex:0 0 min(85%,14rem);scroll-snap-align:center;border-radius:.75rem;overflow:hidden;display:block}'
    + '.slider-cards .slider-slide.is-active{display:block}.slider-cards .slider-arrow{display:none}'
    + '.slider-hero .slider-slide-content{align-items:flex-end;text-align:left;padding-bottom:2rem}'
    + '.slider-thumbs{display:flex;gap:.35rem;justify-content:center;flex-wrap:wrap;padding:0 1.25rem 1rem}'
    + '.slider-thumb{width:3rem;height:2rem;border:2px solid transparent;border-radius:.35rem;background-size:cover;background-position:center;cursor:pointer;padding:0;opacity:.65}'
    + '.slider-thumb.is-active{border-color:var(--c);opacity:1}'
    + '@keyframes sliderFade{from{opacity:0}to{opacity:1}}';

  var WIDGET_CSS = '.accordion-block,.tabs-block{padding:1.25rem 0}'
    + '.accordion-items{display:flex;flex-direction:column;gap:.35rem}.acc-item{background:#fff;border:1px solid #e2e8f0;border-radius:.55rem;overflow:hidden;font-size:.72rem}'
    + '.acc-item summary{padding:.55rem .65rem;font-weight:700;cursor:pointer;list-style:none}.acc-item summary::-webkit-details-marker{display:none}'
    + '.acc-item p{margin:0;padding:0 .65rem .55rem;color:#64748b;line-height:1.5}'
    + '.accordion-bordered .acc-item{border-width:2px}.accordion-flush .acc-item{border:none;border-bottom:1px solid #e2e8f0;border-radius:0}'
    + '.tabs-nav{display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.65rem}.tabs-btn{padding:.35rem .65rem;border:1px solid #e2e8f0;border-radius:999px;background:#fff;color:#334155;font-weight:600;font-size:.68rem;cursor:pointer}'
    + '.tabs-btn.is-active{background:var(--c);color:#fff;border-color:var(--c)}.tabs-underline .tabs-btn{border:none;border-radius:0;border-bottom:2px solid transparent}'
    + '.tabs-underline .tabs-btn.is-active{border-bottom-color:var(--c);background:transparent;color:var(--c)}'
    + '.tabs-boxed .tabs-nav{background:#f8fafc;padding:.35rem;border-radius:.55rem}.tabs-panel{display:none;color:#64748b;font-size:.72rem;line-height:1.5}'
    + '.tabs-panel.is-active{display:block}.tabs-boxed .tabs-panel{background:#fff;border:1px solid #e2e8f0;border-radius:.55rem;padding:.65rem;margin-top:.35rem}'
    + '.marquee-block{overflow:hidden;padding:.65rem 0;background:color-mix(in srgb,var(--c) 8%,#fff);border-block:1px solid #e2e8f0}'
    + '.marquee-track{display:flex;gap:1.5rem;white-space:nowrap;animation:marqueeScroll 25s linear infinite;width:max-content}'
    + '.marquee-speed-slow .marquee-track{animation-duration:40s}.marquee-speed-fast .marquee-track{animation-duration:15s}'
    + '.marquee-track span{font-weight:600;color:#334155;font-size:.72rem}.marquee-badge{padding:.25rem .55rem;border-radius:999px;background:#fff;border:1px solid #e2e8f0;font-weight:700;font-size:.65rem;color:var(--c)}'
    + '@keyframes marqueeScroll{from{transform:translateX(0)}to{transform:translateX(-50%)}}';

  var MESSENGER_CSS = '.msg-buttons{display:flex;flex-wrap:wrap;gap:.65rem;align-items:center}'
    + '.msg-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1rem;border-radius:999px;background:var(--msg-color,#059669);color:#fff;text-decoration:none;font-weight:700;font-size:.88rem;box-shadow:0 8px 20px color-mix(in srgb,var(--msg-color,#059669) 35%,transparent)}'
    + '.msg-btn i{font-size:1.05rem}.msg-buttons-cards{flex-direction:column;align-items:stretch}'
    + '.msg-buttons-cards .msg-btn{border-radius:.85rem;justify-content:center;padding:.85rem 1rem}'
    + '.msg-buttons-bar{justify-content:center;padding:1rem;background:#f8fafc;border-radius:1rem;border:1px solid #e2e8f0}'
    + '.msg-float{position:fixed;z-index:90;display:flex;flex-direction:column;align-items:flex-end;gap:.5rem;pointer-events:none}'
    + '.msg-float-left{left:1rem;right:auto;align-items:flex-start;bottom:1.25rem}.msg-float-right{right:1rem;bottom:1.25rem}'
    + '.msg-float-items{display:flex;flex-direction:column;gap:.45rem;pointer-events:auto}'
    + '.msg-float-bar{left:0;right:0;bottom:0;flex-direction:row;align-items:stretch;padding:.5rem 1rem;background:rgba(255,255,255,.96);border-top:1px solid #e2e8f0;backdrop-filter:blur(8px)}'
    + '.msg-float-bar .msg-float-items{flex-direction:row;flex-wrap:wrap;justify-content:center;gap:.35rem;width:100%}'
    + '.msg-float-btn{display:grid;place-items:center;width:3rem;height:3rem;border-radius:999px;background:var(--msg-color,var(--c));color:#fff;text-decoration:none;box-shadow:0 10px 28px color-mix(in srgb,var(--msg-color,var(--c)) 40%,transparent);font-size:1.25rem}'
    + '.msg-float-bar .msg-float-btn{width:auto;height:auto;padding:.45rem .75rem;border-radius:999px;font-size:.78rem;display:inline-flex;gap:.35rem;align-items:center}'
    + '.msg-float-toggle{pointer-events:auto;width:3.25rem;height:3.25rem;border:0;border-radius:999px;background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;font-size:1.2rem;cursor:pointer;box-shadow:0 12px 32px color-mix(in srgb,var(--c) 40%,transparent);display:grid;place-items:center}'
    + '.msg-float-expand .msg-float-items{opacity:0;transform:translateY(.5rem);pointer-events:none;transition:opacity .2s ease,transform .2s ease}'
    + '.msg-float-expand.is-open .msg-float-items{opacity:1;transform:none;pointer-events:auto}'
    + '.msg-float-expand .msg-float-close{display:none}.msg-float-expand.is-open .msg-float-toggle .fa-comments{display:none}'
    + '.msg-float-expand.is-open .msg-float-close{display:block}.messengers-block{padding:2.5rem 0}.messengers-icons .msg-buttons{justify-content:center}';

  var PAGE_CSS = ':root{--c:BRAND;--c2:color-mix(in srgb,var(--c) 75%,#000)}*{box-sizing:border-box}body{margin:0;font-family:"DM Sans",system-ui,sans-serif;color:#0f172a;background:#f8fafc}'
    + '.nav{position:sticky;top:0;z-index:10;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 1.25rem;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);border-bottom:1px solid #e2e8f0;flex-wrap:wrap}'
    + '.brand{font-weight:700;font-size:1.05rem;color:var(--c)}.brand span{color:#64748b;font-weight:500;font-size:.85rem;margin-left:.5rem}'
    + '.nav-links{display:flex;flex-wrap:wrap;gap:.65rem 1rem}.nav-links a{color:#334155;text-decoration:none;font-weight:600;font-size:.88rem}'
    + '.hero{padding:2.5rem 1.25rem;background:linear-gradient(145deg,color-mix(in srgb,var(--c) 8%,#fff),#fff)}.wrap{max-width:68rem;margin:0 auto;padding:0 1.25rem}'
    + '.hero-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:1.5rem;align-items:center}.hero-centered-inner,.about-centered-inner{text-align:center}'
    + '.badge{display:inline-flex;gap:.4rem;align-items:center;padding:.35rem .75rem;border-radius:999px;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c);font-size:.78rem;font-weight:700;margin-bottom:.75rem}'
    + 'h1{margin:0 0 .5rem;font-size:1.5rem}h2.sec-title{margin:0 0 .75rem;font-size:1.15rem}'
    + '.hero p,.about-text,.text-inner p,.contact-box p,.cta-banner-inner p{color:var(--elb-text,#64748b);font-size:.88rem;line-height:1.55;margin:0 0 .75rem}'
    + '.hero h1,.sec-title,h2.sec-title{color:var(--elb-heading,#0f172a)}'
    + '.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1rem;border-radius:.6rem;font-weight:700;text-decoration:none;font-size:.82rem;background:var(--elb-btn,var(--c));color:var(--elb-btn-text,#fff)}'
    + '.hero-card{background:#fff;border:1px solid #e2e8f0;border-radius:.85rem;padding:1rem;font-size:.78rem}'
    + '.features,.about,.gallery,.info,.contact{padding:1.5rem 0}.feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem}'
    + '.feat,.feat-list-item{background:#fff;border:1px solid #e2e8f0;border-radius:.65rem;padding:.65rem;font-size:.75rem}'
    + '.feat-card{box-shadow:0 8px 20px rgba(15,23,42,.06);border:none}.feat-icon{color:var(--c);margin-bottom:.35rem}'
    + '.feat-list{display:flex;flex-direction:column;gap:.5rem}.feat-list-item{display:flex;gap:.5rem}'
    + '.about-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}.about-card{background:linear-gradient(145deg,var(--c),var(--c2));color:#fff;border-radius:.75rem;padding:1rem;text-align:center;font-size:.78rem}'
    + '.gal-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.4rem}.gal-masonry{columns:3;column-gap:.4rem}.gal-masonry .gal-item{margin-bottom:.4rem;break-inside:avoid}'
    + '.gal-row{display:flex;gap:.4rem;overflow-x:auto}.gal-row .gal-item{min-width:5rem;flex:0 0 5rem}'
    + '.gal-item{aspect-ratio:4/3;border-radius:.5rem;display:grid;place-items:end start;padding:.4rem;color:#fff;font-size:.65rem;font-weight:600}'
    + '.gal-item-photo{padding:0;overflow:hidden;position:relative;display:block}.gal-item-photo img{width:100%;height:100%;object-fit:cover;display:block;aspect-ratio:4/3}'
    + '.gal-item-photo figcaption{position:absolute;left:0;right:0;bottom:0;margin:0;padding:.4rem;background:linear-gradient(transparent,rgba(0,0,0,.55));text-shadow:none}'
    + '.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.4rem}.stat{background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;padding:.5rem;text-align:center;font-size:.7rem}'
    + '.stat strong{display:block;color:var(--c);font-size:.95rem}.info-text-box{background:#fff;border:1px solid #e2e8f0;border-radius:.65rem;padding:.75rem;font-size:.8rem}'
    + '.info-quote blockquote{margin:0;font-style:italic;font-size:.95rem}.contact-box,.contact-minimal-inner,.contact-split-grid{background:linear-gradient(135deg,var(--c),var(--c2));border-radius:.75rem;padding:1rem;color:#fff;text-align:center;font-size:.78rem}'
    + '.contact-split-grid{display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:center;background:#fff;color:#0f172a;border:1px solid #e2e8f0}'
    + '.contact-lines{display:flex;flex-wrap:wrap;gap:.5rem;justify-content:center;margin:.5rem 0}.contact-lines a{color:inherit;text-decoration:none}'
    + '.contact-box .btn{background:#fff;color:var(--c)}footer{padding:1rem;font-size:.7rem;color:#94a3b8;text-align:center}'
    + '.footer-social .wrap{display:flex;justify-content:space-between;align-items:center}.footer-social-icons a{color:var(--c);margin-right:.4rem}'
    + '.cta{padding:1.25rem 0}.cta-banner-inner,.cta-inline-inner{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;border-radius:.85rem;padding:1.25rem;text-align:center;font-size:.78rem}'
    + '.cta-inline-inner{display:flex;align-items:center;justify-content:space-between;gap:.75rem;text-align:left;flex-wrap:wrap}'
    + '.cta-split-grid{display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem}'
    + '.testimonials,.pricing,.faq,.team,.logos{padding:1.25rem 0}.test-cards,.test-grid,.price-cards,.price-compact,.team-grid,.team-cards,.logos-grid{display:grid;gap:.5rem;grid-template-columns:repeat(3,1fr)}'
    + '.test-card,.price-card,.team-member,.faq-acc,.faq-item{background:#fff;border:1px solid #e2e8f0;border-radius:.65rem;padding:.65rem;font-size:.72rem}'
    + '.price-card-featured{border-color:var(--c)}.price-amt strong{font-size:1.25rem;color:var(--c)}.price-feats{list-style:none;margin:.5rem 0;padding:0}'
    + '.team-avatar{width:2rem;height:2rem;border-radius:999px;background:color-mix(in srgb,var(--c) 15%,#fff);color:var(--c);display:grid;place-items:center;font-weight:700;margin-bottom:.35rem}'
    + '.logos-row,.logos-strip{display:flex;flex-wrap:wrap;gap:.4rem;justify-content:center}.logo-pill{padding:.35rem .65rem;border-radius:999px;border:1px solid #e2e8f0;font-size:.65rem;font-weight:700;color:#64748b}'
    + '.faq-twocol{display:grid;grid-template-columns:1fr 1fr;gap:.5rem}.test-quote-inner{text-align:center}'
    + '.video-block,.newsletter,.timeline,.services,.heading-block,.text-block,.image-block,.hours-block,.map-block,.download-block,.events-block,.steps-block,.countdown,.columns-block,.badges-block{padding:1.25rem 0}'
    + '.video-embed{aspect-ratio:16/9;border-radius:.65rem;overflow:hidden}.video-embed iframe{width:100%;height:100%;border:0}'
    + '.video-link-card{display:flex;gap:.5rem;align-items:center;padding:.75rem;background:#fff;border:1px solid #e2e8f0;border-radius:.65rem;text-decoration:none;color:#0f172a;font-size:.75rem;font-weight:700}'
    + '.newsletter-card,.newsletter-inline-inner,.newsletter-banner-inner{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;border-radius:.75rem;padding:1rem;font-size:.72rem}'
    + '.newsletter-form{display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.5rem}.newsletter-form input{padding:.45rem;border-radius:.45rem;border:1px solid #e2e8f0;min-width:8rem}'
    + '.tl-item{padding-left:.75rem;border-left:2px solid var(--c);font-size:.72rem;margin-bottom:.5rem}.tl-year{color:var(--c);font-weight:700;font-size:.65rem}'
    + '.svc-grid,.svc-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem}.svc-item{background:#fff;border:1px solid #e2e8f0;border-radius:.65rem;padding:.65rem;font-size:.72rem}'
    + '.svc-icon{color:var(--c)}.svc-price{color:var(--c);font-weight:700}'
    + '.heading-center,.heading-large{text-align:center}.heading-sub{color:#64748b;font-size:.78rem;margin:0}'
    + '.text-box .text-inner{background:#fff;border:1px solid #e2e8f0;border-radius:.65rem;padding:.75rem}.text-inner p{color:#64748b;font-size:.78rem;margin:0}'
    + '.image-figure img{width:100%;border-radius:.5rem}.image-figure figcaption{font-size:.65rem;color:#64748b;margin-top:.35rem}'
    + '.divider hr{border:none;height:1px;background:#e2e8f0;margin:.75rem 0}.divider-gradient hr{background:linear-gradient(90deg,transparent,var(--c),transparent)}'
    + '.spacer-sm{height:1rem}.spacer-md{height:2rem}.spacer-lg{height:3.5rem}'
    + '.hours-row{display:flex;justify-content:space-between;font-size:.72rem;padding:.35rem 0;border-bottom:1px solid #f1f5f9}'
    + '.hours-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:.4rem}.hours-card{background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;padding:.5rem;text-align:center;font-size:.68rem}'
    + '.map-embed{aspect-ratio:16/9;border-radius:.65rem;overflow:hidden}.map-embed iframe{width:100%;height:100%;border:0}'
    + '.map-placeholder{display:flex;gap:.5rem;align-items:center;padding:.75rem;background:#fff;border:1px solid #e2e8f0;border-radius:.65rem;font-size:.72rem}'
    + '.promo-inner{display:flex;align-items:center;justify-content:center;gap:.5rem;flex-wrap:wrap;padding:.65rem;font-size:.72rem}'
    + '.promo-solid .promo-inner{background:linear-gradient(90deg,var(--c),var(--c2));color:#fff}.promo-solid .btn{background:#fff;color:var(--c)}'
    + '.quote-block blockquote{font-style:italic;font-size:.85rem;margin:0}.quote-sidebar blockquote{border-left:3px solid var(--c);padding-left:.65rem}'
    + '.download-buttons{display:flex;flex-wrap:wrap;gap:.4rem}.dl-item{display:flex;gap:.35rem;align-items:center;padding:.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;text-decoration:none;color:#0f172a;font-size:.68rem}'
    + '.alert-inner{display:flex;gap:.5rem;padding:.65rem;border-radius:.5rem;border:1px solid #e2e8f0;background:#fff;font-size:.72rem}'
    + '.alert-info .alert-inner{background:color-mix(in srgb,var(--c) 8%,#fff)}.alert-info i{color:var(--c)}'
    + '.event-item{display:flex;gap:.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;padding:.5rem;font-size:.68rem;margin-bottom:.35rem}'
    + '.event-date{color:var(--c);font-weight:700;min-width:3rem}.step-item{display:flex;gap:.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;padding:.5rem;font-size:.68rem;margin-bottom:.35rem}'
    + '.step-num{width:1.5rem;height:1.5rem;border-radius:999px;background:var(--c);color:#fff;display:grid;place-items:center;font-size:.65rem;flex-shrink:0}'
    + '.cd-units{display:flex;gap:.4rem;justify-content:center;flex-wrap:wrap}.cd-unit{background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;padding:.5rem;min-width:3rem;text-align:center;font-size:.65rem}'
    + '.cd-unit strong{display:block;color:var(--c);font-size:.95rem}'
    + '.cols-two,.cols-three{display:grid;gap:.75rem}.cols-two{grid-template-columns:1fr 1fr}.cols-three{grid-template-columns:repeat(3,1fr)}'
    + '.col-item p{color:#64748b;font-size:.72rem;margin:0}'
    + '.badges-pills,.badges-outline{display:flex;flex-wrap:wrap;gap:.35rem;justify-content:center}'
    + '.badge-pill{padding:.3rem .6rem;border-radius:999px;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c);font-weight:700;font-size:.65rem}'
    + '@media(max-width:768px){.hero-grid,.about-grid{grid-template-columns:1fr}.feat-grid{grid-template-columns:repeat(2,1fr)}.stat-grid{grid-template-columns:repeat(2,1fr)}.gal-grid{grid-template-columns:repeat(2,1fr)}.gal-masonry{columns:2}.contact-split-grid,.cta-split-grid,.faq-twocol,.test-cards,.test-grid,.price-cards,.team-grid,.svc-grid,.svc-cards,.hours-cards,.cols-two,.cols-three{grid-template-columns:1fr}}'
    + '@media(max-width:480px){.feat-grid,.stat-grid,.gal-grid{grid-template-columns:1fr}.gal-masonry{columns:1}.nav{flex-direction:column;align-items:flex-start}}'
    + '.buttons-block,.cards-block,.social-block,.trust-block,.callout-block,.media-text-block,.menu-block,.stats-bar-block,.comparison-block,.contact-bar-block,.icon-list-block,.app-cta-block{padding:1.25rem 0}'
    + '.btn-group{display:flex;flex-wrap:wrap;gap:.4rem}.btn-group-center{justify-content:center}.btn-group-stacked{flex-direction:column}'
    + '.btn.secondary{background:color-mix(in srgb,var(--c) 18%,#fff);color:var(--c)}.btn.outline{background:transparent;border:2px solid var(--c);color:var(--c)}.btn.ghost{background:transparent;color:var(--c);box-shadow:none}.btn.link{background:transparent;color:var(--c);text-decoration:underline;box-shadow:none;padding:0}'
    + '.content-cards{display:grid;gap:.5rem}.content-cards-grid{grid-template-columns:repeat(3,1fr)}.content-card{background:#fff;border:1px solid #e2e8f0;border-radius:.65rem;padding:.65rem;font-size:.72rem}'
    + '.content-card-icon{color:var(--c);margin-bottom:.35rem}.social-links{display:flex;flex-wrap:wrap;gap:.4rem}.social-link{display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .6rem;border-radius:999px;border:1px solid #e2e8f0;font-size:.68rem;text-decoration:none;color:#334155}'
    + '.trust-items{display:flex;flex-wrap:wrap;gap:.5rem}.trust-item{text-align:center;flex:1;min-width:4rem;font-size:.68rem}.trust-icon{color:var(--c)}'
    + '.callout-inner{display:flex;gap:.5rem;padding:.75rem;border-radius:.65rem;border:1px solid #e2e8f0;background:#fff;font-size:.72rem}.callout-promo .callout-inner{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;border:none}'
    + '.media-text-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;align-items:center}.media-placeholder{display:grid;place-items:center;background:color-mix(in srgb,var(--c) 12%,#fff);border-radius:.5rem;aspect-ratio:4/3;color:var(--c)}'
    + '.menu-item{background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;padding:.55rem;font-size:.7rem;margin-bottom:.35rem}.menu-item-head{display:flex;justify-content:space-between}.menu-price{color:var(--c);font-weight:700}'
    + '.stats-bar-inner{display:flex;flex-wrap:wrap;gap:.5rem;justify-content:center}.stats-bar-item{text-align:center;font-size:.68rem}.stats-bar-item strong{display:block;color:var(--c)}'
    + '.comparison-table{width:100%;border-collapse:collapse;font-size:.68rem}.comparison-table th,.comparison-table td{padding:.4rem;border:1px solid #e2e8f0}'
    + '.contact-bar-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem;padding:.65rem;background:#fff;border:1px solid #e2e8f0;border-radius:.65rem;font-size:.7rem}'
    + '.icon-list-item{display:flex;gap:.4rem;background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;padding:.55rem;font-size:.7rem;margin-bottom:.35rem}.icon-list-icon{color:var(--c)}'
    + '.app-cta-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;padding:.75rem;background:#fff;border:1px solid #e2e8f0;border-radius:.65rem;font-size:.72rem}'
    + '.app-badges{display:flex;gap:.35rem}.app-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.4rem .65rem;border-radius:.5rem;background:#0f172a;color:#fff;text-decoration:none;font-size:.65rem;font-weight:700}'
    + '.messengers-block{padding:1.25rem 0}'
    + CHROME_CSS + MESSENGER_CSS + SLIDER_CSS + WIDGET_CSS;

  var PREVIEW_DEVICE_CSS = 'body.elb-vp-mobile .hero-grid,body.elb-vp-mobile .about-grid,body.elb-vp-mobile .feat-grid,body.elb-vp-mobile .stat-grid,body.elb-vp-mobile .gal-grid,body.elb-vp-mobile .test-cards,body.elb-vp-mobile .test-grid,body.elb-vp-mobile .price-cards,body.elb-vp-mobile .price-compact,body.elb-vp-mobile .team-grid,body.elb-vp-mobile .team-cards,body.elb-vp-mobile .logos-grid,body.elb-vp-mobile .faq-twocol,body.elb-vp-mobile .contact-split-grid,body.elb-vp-mobile .cta-split-grid{grid-template-columns:1fr!important}'
    + 'body.elb-vp-mobile .gal-masonry{columns:1!important}body.elb-vp-mobile .nav{flex-direction:column!important;align-items:flex-start!important}'
    + 'body.elb-vp-tablet .hero-grid,body.elb-vp-tablet .about-grid{grid-template-columns:1fr!important}'
    + 'body.elb-vp-tablet .feat-grid,body.elb-vp-tablet .stat-grid,body.elb-vp-tablet .gal-grid{grid-template-columns:repeat(2,1fr)!important}'
    + 'body.elb-vp-tablet .gal-masonry{columns:2!important}'
    + 'body.elb-vp-tablet .test-cards,body.elb-vp-tablet .test-grid,body.elb-vp-tablet .price-cards,body.elb-vp-tablet .price-compact,body.elb-vp-tablet .team-grid,body.elb-vp-tablet .team-cards{grid-template-columns:repeat(2,1fr)!important}'
    + 'body.elb-vp-tablet .faq-twocol{grid-template-columns:1fr!important}';

  var PREVIEW_BUILDER_CSS = 'body.elb-preview-mode a,body.elb-preview-mode .btn{pointer-events:none}'
    + '.elb-sec-shell{position:relative;cursor:pointer;pointer-events:auto}'
    + '.elb-sec-shell[data-elb-section]{cursor:pointer}'
    + '.elb-sec-shell:hover,.elb-sec-shell.is-elb-pick{outline:2px solid #db2777;outline-offset:-2px;z-index:1}'
    + '.elb-sec-bar{position:absolute;top:0;left:0;right:0;display:flex;align-items:center;gap:.35rem;padding:.32rem .55rem;background:#db2777;color:#fff;font-size:.62rem;font-weight:700;opacity:.92;z-index:3;line-height:1.2;pointer-events:auto;cursor:pointer;user-select:none}'
    + '.elb-sec-bar i{font-size:.72rem}'
    + '.elb-sec-pick{margin-left:auto;opacity:.95;font-weight:600;font-size:.58rem;pointer-events:auto}'
    + '.elb-sec-shell:hover .elb-sec-bar,.elb-sec-shell.is-elb-pick .elb-sec-bar{opacity:1}'
    + '.elb-sec-shell.is-elb-pick{outline:3px solid #db2777}'
    + '.elb-sec-shell-float{position:fixed;inset:0;pointer-events:none;outline:none!important;z-index:80}'
    + '.elb-sec-shell-float .elb-sec-bar-float{position:fixed;top:auto;bottom:5.5rem;right:.75rem;left:auto;width:auto;border-radius:.45rem;pointer-events:auto}'
    + '.elb-sec-shell-float .msg-float{pointer-events:auto}';

  var PREVIEW_BUILDER_JS = '(function(){document.body.classList.add("elb-preview-mode");function applyDevice(dev){document.body.classList.remove("elb-vp-desktop","elb-vp-tablet","elb-vp-mobile");document.body.classList.add("elb-vp-"+(dev||"desktop"));}function tickCd(){document.querySelectorAll("[data-countdown]").forEach(function(el){var t=new Date(el.getAttribute("data-countdown")+"T00:00:00").getTime()-Date.now();if(t<0)t=0;var d=Math.floor(t/864e5),h=Math.floor(t%864e5/36e5),m=Math.floor(t%36e5/6e4),s=Math.floor(t%6e4/1e3);var ds=el.querySelector("[data-cd-days]");if(ds){ds.textContent=d;el.querySelector("[data-cd-hours]").textContent=h;el.querySelector("[data-cd-mins]").textContent=m;el.querySelector("[data-cd-secs]").textContent=s;}});}tickCd();setInterval(tickCd,1000);function initSlider(root){var track=root.querySelector(".slider-track");if(!track)return;var slides=[].slice.call(track.querySelectorAll(".slider-slide"));if(!slides.length)return;var variant=root.className.match(/slider-(fade|slide|cards|hero|thumbnails)/);variant=variant?variant[1]:"fade";if(variant==="cards")return;var idx=0,timer=null;var interval=parseInt(root.getAttribute("data-interval")||"5000",10);var autoplay=root.getAttribute("data-autoplay")==="1";function go(n){idx=(n+slides.length)%slides.length;slides.forEach(function(s,i){s.classList.toggle("is-active",i===idx);});var thumbs=root.querySelectorAll(".slider-thumb");thumbs.forEach(function(t,i){t.classList.toggle("is-active",i===idx);});var dots=root.querySelector("[data-slider-dots]");if(dots){dots.querySelectorAll(".slider-dot").forEach(function(d,i){d.classList.toggle("is-active",i===idx);});}}function next(){go(idx+1);}function prev(){go(idx-1);}function start(){stop();if(autoplay)timer=setInterval(next,interval);}function stop(){if(timer){clearInterval(timer);timer=null;}}var prevBtn=root.querySelector(".slider-prev");var nextBtn=root.querySelector(".slider-next");if(prevBtn)prevBtn.addEventListener("click",function(ev){ev.stopPropagation();prev();start();});if(nextBtn)nextBtn.addEventListener("click",function(ev){ev.stopPropagation();next();start();});root.querySelectorAll(".slider-thumb").forEach(function(btn){btn.addEventListener("click",function(ev){ev.stopPropagation();go(parseInt(btn.getAttribute("data-thumb")||"0",10));start();});});var dotsWrap=root.querySelector("[data-slider-dots]");if(dotsWrap&&root.getAttribute("data-dots")==="1"){slides.forEach(function(_,i){var b=document.createElement("button");b.type="button";b.className="slider-dot"+(i===0?" is-active":"");b.addEventListener("click",function(ev){ev.stopPropagation();go(i);start();});dotsWrap.appendChild(b);});}root.addEventListener("mouseenter",stop);root.addEventListener("mouseleave",start);go(0);start();}document.querySelectorAll("[data-slider]").forEach(initSlider);document.querySelectorAll("[data-tabs]").forEach(function(root){root.querySelectorAll(".tabs-btn").forEach(function(btn){btn.addEventListener("click",function(ev){ev.stopPropagation();var id=btn.getAttribute("data-tab");root.querySelectorAll(".tabs-btn").forEach(function(b){b.classList.toggle("is-active",b===btn);});root.querySelectorAll(".tabs-panel").forEach(function(p){p.classList.toggle("is-active",p.getAttribute("data-tab-panel")===id);});});});});document.querySelectorAll("[data-nav-burger]").forEach(function(btn){var header=btn.closest(".nav");if(!header)return;var drawer=header.querySelector("[data-nav-drawer]");var overlay=header.querySelector("[data-nav-overlay]");if(!drawer||!overlay)return;function close(){drawer.hidden=true;overlay.hidden=true;btn.setAttribute("aria-expanded","false");document.body.classList.remove("nav-open");}function open(){drawer.hidden=false;overlay.hidden=false;btn.setAttribute("aria-expanded","true");document.body.classList.add("nav-open");}btn.addEventListener("click",function(ev){ev.stopPropagation();drawer.hidden?open():close();});overlay.addEventListener("click",close);drawer.querySelectorAll("a").forEach(function(a){a.addEventListener("click",close);});});document.querySelectorAll("[data-msg-float]").forEach(function(wrap){var btn=wrap.querySelector("[data-msg-toggle]");if(!btn)return;btn.addEventListener("click",function(ev){ev.stopPropagation();var open=wrap.classList.toggle("is-open");btn.setAttribute("aria-expanded",open?"true":"false");});});document.addEventListener("click",function(ev){var shell=ev.target.closest("[data-elb-section]");if(!shell)return;ev.preventDefault();ev.stopPropagation();var pick=shell.getAttribute("data-elb-pick");if(pick==="nav"){parent.postMessage({type:"hs-elb-pick-nav"},"*");return;}if(pick==="messengers"){parent.postMessage({type:"hs-elb-pick-messengers"},"*");return;}var idx=parseInt(shell.getAttribute("data-elb-idx"),10);if(!isNaN(idx))parent.postMessage({type:"hs-elb-pick",idx:idx},"*");},true);window.addEventListener("message",function(ev){var d=ev.data;if(!d)return;if(d.type==="hs-elb-device"){applyDevice(d.device);return;}if(d.type!=="hs-elb-highlight")return;var shells=document.querySelectorAll("[data-elb-section]");shells.forEach(function(s){if(d.idx<0){s.classList.remove("is-elb-pick");return;}s.classList.toggle("is-elb-pick",parseInt(s.getAttribute("data-elb-idx"),10)===d.idx);});var t=document.querySelector(\'[data-elb-idx="\'+d.idx+\'"]\');if(t)t.scrollIntoView({behavior:"smooth",block:"center"});});})();';

  function blockTypeLabel(type) {
    var reg = cfg.blockTypes[type] || {};
    return reg.label || type;
  }

  function wrapBlockSection(html, idx, type) {
    if (!html) return '';
    var reg = cfg.blockTypes[type] || {};
    return '<div class="elb-sec-shell" data-elb-section data-elb-idx="' + idx + '">'
      + '<div class="elb-sec-bar"><i class="fa-solid ' + esc(reg.icon || 'fa-cube') + '"></i><span>' + esc(blockTypeLabel(type)) + '</span>'
      + '<span class="elb-sec-pick">' + esc(MSG.pick_in_preview || 'Click to edit') + '</span></div>'
      + html + '</div>';
  }

  function previewViewportWidth() {
    if (elbDevice === 'mobile') return 375;
    if (elbDevice === 'tablet') return 768;
    return 1200;
  }

  function previewBodyClass() {
    return 'elb-preview-mode elb-vp-' + (elbDevice || 'desktop');
  }

  function notifyPreviewDevice() {
    try {
      if (frame.contentWindow) {
        frame.contentWindow.postMessage({ type: 'hs-elb-device', device: elbDevice || 'desktop' }, '*');
      }
    } catch (e) { /* ignore */ }
  }

  function syncPreviewFrameWidth() {
    var width = ELB_DEVICE_WIDTHS[elbDevice] || ELB_DEVICE_WIDTHS.desktop;
    if (elbDevice === 'desktop') {
      frame.style.width = '100%';
      frame.style.maxWidth = '100%';
    } else {
      frame.style.width = width + 'px';
      frame.style.maxWidth = '100%';
    }
  }

  function renderPreview(data) {
    var c = data.color || '#059669';
    var vp = previewViewportWidth();
    var body = renderNavPreview(data);
    (data.blocks || []).forEach(function (block, idx) {
      var html = '';
      if (block.type === 'hero') html = renderHero(block, data);
      else if (block.type === 'features') html = renderFeatures(block, data);
      else if (block.type === 'about') html = renderAbout(block, data);
      else if (block.type === 'gallery') html = renderGallery(block, data);
      else if (block.type === 'info') html = renderInfo(block);
      else if (block.type === 'contact') html = renderContact(block);
      else if (block.type === 'cta') html = renderCta(block);
      else if (block.type === 'testimonials') html = renderTestimonials(block);
      else if (block.type === 'pricing') html = renderPricing(block);
      else if (block.type === 'faq') html = renderFaq(block);
      else if (block.type === 'team') html = renderTeam(block);
      else if (block.type === 'logos') html = renderLogos(block);
      else if (block.type === 'video') html = renderVideo(block);
      else if (block.type === 'newsletter') html = renderNewsletter(block);
      else if (block.type === 'timeline') html = renderTimeline(block);
      else if (block.type === 'services') html = renderServices(block, data);
      else if (block.type === 'heading') html = renderHeading(block);
      else if (block.type === 'text') html = renderTextBlock(block);
      else if (block.type === 'image') html = renderImageBlock(block);
      else if (block.type === 'divider') html = renderDivider(block);
      else if (block.type === 'spacer') html = renderSpacer(block);
      else if (block.type === 'hours') html = renderHours(block);
      else if (block.type === 'map') html = renderMap(block);
      else if (block.type === 'banner') html = renderBanner(block);
      else if (block.type === 'quote') html = renderQuoteBlock(block);
      else if (block.type === 'download') html = renderDownload(block);
      else if (block.type === 'alert') html = renderAlert(block);
      else if (block.type === 'events') html = renderEvents(block);
      else if (block.type === 'steps') html = renderSteps(block);
      else if (block.type === 'countdown') html = renderCountdown(block);
      else if (block.type === 'columns') html = renderColumns(block);
      else if (block.type === 'badges') html = renderBadges(block);
      else if (block.type === 'buttons') html = renderButtons(block);
      else if (block.type === 'cards') html = renderCards(block, data);
      else if (block.type === 'social') html = renderSocial(block);
      else if (block.type === 'trust') html = renderTrust(block, data);
      else if (block.type === 'callout') html = renderCallout(block);
      else if (block.type === 'media_text') html = renderMediaText(block);
      else if (block.type === 'menu') html = renderMenu(block);
      else if (block.type === 'stats_bar') html = renderStatsBar(block);
      else if (block.type === 'comparison') html = renderComparison(block);
      else if (block.type === 'contact_bar') html = renderContactBar(block);
      else if (block.type === 'icon_list') html = renderIconList(block, data);
      else if (block.type === 'app_cta') html = renderAppCta(block);
      else if (block.type === 'messengers') html = renderMessengers(block);
      else if (block.type === 'slider') html = renderSlider(block, data);
      else if (block.type === 'accordion') html = renderAccordion(block);
      else if (block.type === 'tabs') html = renderTabs(block);
      else if (block.type === 'marquee') html = renderMarquee(block);
      body += wrapBlockSection(finalizeBlockHtml(html, block), idx, block.type);
    });
    body += renderFooter(data);
    body += renderFloatingMessengers(data);
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=' + vp + ',initial-scale=1">'
      + '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">'
      + '<style>' + PAGE_CSS.replace('BRAND', esc(c)) + PREVIEW_DEVICE_CSS + PREVIEW_BUILDER_CSS + '</style></head>'
      + '<body class="' + previewBodyClass() + '">' + body
      + '<script>' + PREVIEW_BUILDER_JS + '<\/script></body></html>';
  }

  function updatePreview() {
    var data = readFormMeta();
    var html = renderPreview(data);
    var token = ++previewLoadToken;
    frame.onload = function () {
      if (token !== previewLoadToken) return;
      frame.onload = null;
      afterPreviewLoad();
    };
    try {
      frame.removeAttribute('src');
      frame.srcdoc = html;
    } catch (e) {
      try {
        var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
        if (doc) {
          doc.open();
          doc.write(html);
          doc.close();
        }
      } catch (e2) { /* ignore */ }
    }
    setTimeout(function () {
      if (token === previewLoadToken) afterPreviewLoad();
    }, 80);
    setTimeout(function () {
      if (token === previewLoadToken) afterPreviewLoad();
    }, 280);
  }

  function syncChipActive(name) {
    root.querySelectorAll('[name="' + name + '"]').forEach(function (inp) {
      var chip = inp.closest('.hs-landing-theme-chip, .hs-landing-icon-set-chip, .hs-landing-style-chip');
      if (chip) chip.classList.toggle('is-active', inp.checked);
    });
  }

  function refreshLogoIconPickerActive() {
    var hidden = form.querySelector('[data-landing-logo-icon-input]');
    if (!hidden) return;
    var val = hidden.value || '';
    form.querySelectorAll('[data-logo-icon-pick]').forEach(function (btn) {
      btn.classList.toggle('is-active', (btn.getAttribute('data-logo-icon-pick') || '') === val);
    });
  }

  function initLogoIconPicker() {
    form.querySelectorAll('[data-logo-icon-pick]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var ic = btn.getAttribute('data-logo-icon-pick') || '';
        var hidden = form.querySelector('[data-landing-logo-icon-input]');
        if (hidden) hidden.value = ic;
        refreshLogoIconPickerActive();
        updatePreview();
      });
    });
    refreshLogoIconPickerActive();
  }

  form.querySelectorAll('[data-landing-sync]').forEach(function (el) {
    el.addEventListener('input', function () {
      if (el.name === 'theme') {
        syncChipActive('theme');
        var themes = cfg.themes || {};
        var col = form.querySelector('[name="color"]');
        if (col && themes[el.value]) col.value = themes[el.value].color;
      }
      if (el.name === 'icon_set') syncChipActive('icon_set');
      if (el.name === 'icon_style') {
        syncChipActive('icon_style');
        renderBlocks();
        var grid = form.querySelector('[data-landing-logo-icon-grid]');
        var prefix = currentIconStyle() === 'regular' ? 'fa-regular' : 'fa-solid';
        if (grid) {
          grid.querySelectorAll('[data-logo-icon-pick]').forEach(function (btn) {
            var ic = btn.getAttribute('data-logo-icon-pick');
            if (!ic) return;
            var i = btn.querySelector('i');
            if (i) i.className = prefix + ' ' + ic;
          });
        }
      }
      if (el.name === 'gallery_palette') applyPaletteToGalleries();
      updatePreview();
    });
    el.addEventListener('change', function () { updatePreview(); });
  });
  initLogoIconPicker();

  var applyIconsBtn = root.querySelector('[data-landing-apply-icons]');
  if (applyIconsBtn) applyIconsBtn.addEventListener('click', applyIconSetToFeatures);

  form.addEventListener('submit', function () {
    pullBlocksFromDom();
    pullNavFromDom();
    syncHidden();
  });

  var openSettingsSection = 'brand';

  function setOpenSettingsSection(id) {
    openSettingsSection = id || 'brand';
    var acc = root.querySelector('[data-landing-accordions]');
    if (!acc) return;
    acc.querySelectorAll('[data-landing-spoiler]').forEach(function (el) {
      var open = el.getAttribute('data-landing-spoiler') === openSettingsSection;
      el.classList.toggle('is-open', open);
      var btn = el.querySelector('[data-landing-spoiler-toggle]');
      if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    try { localStorage.setItem('hs_landing_settings_acc', openSettingsSection); } catch (e) { /* ignore */ }
    var openEl = acc.querySelector('[data-landing-spoiler="' + openSettingsSection + '"]');
    if (openEl) {
      requestAnimationFrame(function () {
        openEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      });
    }
  }

  function initSettingsAccordions() {
    var saved = '';
    try { saved = localStorage.getItem('hs_landing_settings_acc') || localStorage.getItem('hs_landing_screen') || ''; } catch (e) { /* ignore */ }
    if (saved === 'menu') saved = 'brand';
    if (saved && root.querySelector('[data-landing-spoiler="' + saved + '"]')) {
      openSettingsSection = saved;
    }
    setOpenSettingsSection(openSettingsSection);
  }

  var ELB_DEVICE_WIDTHS = { desktop: 1280, tablet: 768, mobile: 375 };

  function applyPreviewDeviceInFrame() {
    try {
      var doc = frame.contentDocument;
      if (doc && doc.body) {
        doc.body.classList.remove('elb-vp-desktop', 'elb-vp-tablet', 'elb-vp-mobile');
        doc.body.classList.add('elb-vp-' + (elbDevice || 'desktop'));
      }
    } catch (e) { /* ignore */ }
    notifyPreviewDevice();
    resizePreviewFrame();
  }

  function setElbDevice(device) {
    elbDevice = device || 'desktop';
    var wrap = root.querySelector('[data-elb-canvas-device]');
    var width = ELB_DEVICE_WIDTHS[elbDevice] || ELB_DEVICE_WIDTHS.desktop;
    if (wrap) {
      wrap.className = 'elb-canvas-device is-' + elbDevice;
      wrap.setAttribute('data-elb-canvas-device', elbDevice);
      wrap.setAttribute('data-elb-device-width', width + 'px');
      wrap.style.setProperty('--elb-device-width', width + 'px');
    }
    var bar = root.querySelector('[data-elb-canvas-toolbar]');
    if (bar) bar.setAttribute('data-elb-device-width', width + 'px');
    root.querySelectorAll('[data-elb-device]').forEach(function (btn) {
      btn.classList.toggle('is-active', btn.getAttribute('data-elb-device') === elbDevice);
    });
    try { localStorage.setItem('hs_landing_device', elbDevice); } catch (e) { /* ignore */ }
    syncPreviewFrameWidth();
    applyPreviewDeviceInFrame();
    var canvasWrap = root.querySelector('[data-elb-canvas-wrap]');
    if (canvasWrap && elbDevice !== 'desktop') {
      requestAnimationFrame(function () {
        if (wrap) wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      });
    }
  }

  function openElementsPanel() {
    setElbTab('elements');
    if (isMobileElb()) {
      root.querySelectorAll('[data-elb-dock]').forEach(function (b) {
        b.classList.toggle('is-active', b.getAttribute('data-elb-dock') === 'elements');
      });
    }
    var search = root.querySelector('[data-elb-widget-search]');
    if (search) {
      setTimeout(function () { search.focus(); }, 200);
    }
  }

  function isMobileElb() {
    return window.matchMedia('(max-width: 900px)').matches;
  }

  function savePanelState() {
    var left = root.querySelector('[data-elb-panel="left"]');
    var right = root.querySelector('[data-elb-panel="right"]');
    try {
      localStorage.setItem('hs_landing_panel_left', left && left.classList.contains('is-open') ? '1' : '0');
      localStorage.setItem('hs_landing_panel_right', right && right.classList.contains('is-open') ? '1' : '0');
      var activeTab = root.querySelector('[data-elb-ptab].is-active');
      localStorage.setItem('hs_landing_tab', activeTab ? (activeTab.getAttribute('data-elb-ptab') || 'elements') : 'elements');
    } catch (e) { /* ignore */ }
  }

  function updatePanelRevealButtons() {
    var left = root.querySelector('[data-elb-panel="left"]');
    var right = root.querySelector('[data-elb-panel="right"]');
    var revL = root.querySelector('[data-elb-reveal="left"]');
    var revR = root.querySelector('[data-elb-reveal="right"]');
    if (revL) revL.hidden = !!(left && left.classList.contains('is-open'));
    if (revR) revR.hidden = !!(right && right.classList.contains('is-open'));
  }

  function updateWorkspacePanelClasses() {
    var left = root.querySelector('[data-elb-panel="left"]');
    var right = root.querySelector('[data-elb-panel="right"]');
    var ws = root.querySelector('.elb-workspace');
    if (!ws) return;
    ws.classList.toggle('elb-left-closed', !(left && left.classList.contains('is-open')));
    ws.classList.toggle('elb-right-closed', !(right && right.classList.contains('is-open')));
    updatePanelRevealButtons();
  }

  function updateElbToggleUi() {
    var left = root.querySelector('[data-elb-panel="left"]');
    var right = root.querySelector('[data-elb-panel="right"]');
    root.querySelectorAll('[data-elb-toggle]').forEach(function (btn) {
      var side = btn.getAttribute('data-elb-toggle');
      var panel = side === 'left' ? left : right;
      btn.classList.toggle('is-active', !!(panel && panel.classList.contains('is-open')));
    });
    root.querySelectorAll('[data-elb-dock]').forEach(function (btn) {
      var mode = btn.getAttribute('data-elb-dock');
      var active = false;
      var elementsPane = root.querySelector('[data-elb-ppane="elements"]');
      var settingsPane = root.querySelector('[data-elb-ppane="settings"]');
      var structPane = root.querySelector('[data-elb-ppane="structure"]');
      if (mode === 'canvas') {
        active = !(left && left.classList.contains('is-open')) && !(right && right.classList.contains('is-open'));
      } else if (mode === 'elements') {
        active = !!(left && left.classList.contains('is-open') && elementsPane && elementsPane.classList.contains('is-active'));
      } else if (mode === 'settings') {
        active = !!(left && left.classList.contains('is-open') && settingsPane && settingsPane.classList.contains('is-active'));
      } else if (mode === 'edit') {
        active = !!(right && right.classList.contains('is-open'));
      } else if (mode === 'structure') {
        active = !!(left && left.classList.contains('is-open') && structPane && structPane.classList.contains('is-active'));
      }
      btn.classList.toggle('is-active', active);
    });
    updateWorkspacePanelClasses();
  }

  function setPanelOpen(panel, open) {
    if (!panel) return;
    panel.classList.toggle('is-open', !!open);
  }

  function openElbPanel(side, forceOpen) {
    var left = root.querySelector('[data-elb-panel="left"]');
    var right = root.querySelector('[data-elb-panel="right"]');
    var overlay = root.querySelector('[data-elb-overlay]');
    var mobile = isMobileElb();

    if (side === 'none') {
      setPanelOpen(left, false);
      setPanelOpen(right, false);
    } else if (side === 'left') {
      if (mobile) setPanelOpen(right, false);
      setPanelOpen(left, forceOpen !== false);
    } else if (side === 'right') {
      if (mobile) setPanelOpen(left, false);
      setPanelOpen(right, forceOpen !== false);
    } else if (side === 'both') {
      setPanelOpen(left, true);
      setPanelOpen(right, true);
    }

    var anyOpen = (left && left.classList.contains('is-open')) || (right && right.classList.contains('is-open'));
    if (overlay) overlay.hidden = !mobile || !anyOpen;
    savePanelState();
    updateElbToggleUi();
  }

  function toggleElbPanel(side) {
    var panel = root.querySelector('[data-elb-panel="' + side + '"]');
    if (!panel) return;
    var willOpen = !panel.classList.contains('is-open');
    if (willOpen) {
      openElbPanel(side, true);
      return;
    }
    setPanelOpen(panel, false);
    var overlay = root.querySelector('[data-elb-overlay]');
    var left = root.querySelector('[data-elb-panel="left"]');
    var right = root.querySelector('[data-elb-panel="right"]');
    var anyOpen = (left && left.classList.contains('is-open')) || (right && right.classList.contains('is-open'));
    if (overlay) overlay.hidden = !isMobileElb() || !anyOpen;
    savePanelState();
    updateElbToggleUi();
  }

  function setElbTab(tab) {
    tab = tab || 'elements';
    var left = root.querySelector('[data-elb-panel="left"]');
    if (left && !left.classList.contains('is-open')) {
      if (isMobileElb()) {
        openElbPanel('left', true);
      } else {
        left.classList.add('is-open');
        savePanelState();
      }
    }
    root.querySelectorAll('[data-elb-ptab]').forEach(function (btn) {
      btn.classList.toggle('is-active', btn.getAttribute('data-elb-ptab') === tab);
    });
    root.querySelectorAll('[data-elb-ppane]').forEach(function (pane) {
      pane.classList.toggle('is-active', pane.getAttribute('data-elb-ppane') === tab);
    });
    if (tab === 'structure') renderNavigator();
    if (tab === 'settings') setOpenSettingsSection(openSettingsSection || 'brand');
    try { localStorage.setItem('hs_landing_tab', tab); } catch (e) { /* ignore */ }
    updateElbToggleUi();
  }

  function syncBeforeSubmit() {
    pullBlocksFromDom();
    pullNavFromDom();
    syncHidden();
  }

  var TIPS_STORAGE_KEY = 'hs_landing_tips_seen';

  function openElbTips() {
    var tips = root.querySelector('[data-elb-tips]');
    if (!tips) return;
    tips.hidden = false;
    document.body.classList.add('elb-tips-open');
    var okBtn = tips.querySelector('.elb-tips-ok');
    if (okBtn) okBtn.focus();
  }

  function closeElbTips(persistDismiss) {
    var tips = root.querySelector('[data-elb-tips]');
    if (!tips) return;
    var dismiss = tips.querySelector('[data-elb-tips-dismiss]');
    if (persistDismiss && dismiss && dismiss.checked) {
      try { localStorage.setItem(TIPS_STORAGE_KEY, '1'); } catch (e) { /* ignore */ }
    }
    tips.hidden = true;
    document.body.classList.remove('elb-tips-open');
  }

  function initElbTips() {
    var tips = root.querySelector('[data-elb-tips]');
    if (!tips) return;
    var seen = false;
    try { seen = localStorage.getItem(TIPS_STORAGE_KEY) === '1'; } catch (e) { /* ignore */ }
    if (!seen) {
      setTimeout(function () { openElbTips(); }, 600);
    }
  }

  function initElbBuilder() {
    var savedDev = '';
    try { savedDev = localStorage.getItem('hs_landing_device') || ''; } catch (e) { /* ignore */ }
    if (savedDev) setElbDevice(savedDev);
    initElbTips();

    var addWidgetBtn = root.querySelector('[data-elb-add-widget]');
    if (addWidgetBtn) {
      addWidgetBtn.addEventListener('click', function (e) {
        e.preventDefault();
        openElementsPanel();
      });
    }

    var widgetSearch = root.querySelector('[data-elb-widget-search]');
    if (widgetSearch) {
      widgetSearch.addEventListener('input', function () {
        paletteFilter = (widgetSearch.value || '').trim().toLowerCase();
        renderPalette();
      });
    }

    window.addEventListener('message', function (ev) {
      if (!ev.data) return;
      if (ev.data.type === 'hs-elb-pick-nav') {
        handlePreviewPickNav();
        return;
      }
      if (ev.data.type === 'hs-elb-pick-messengers') {
        handlePreviewPickMessengers();
        return;
      }
      if (ev.data.type === 'hs-elb-pick') {
        handlePreviewPickBlock(parseInt(ev.data.idx, 10));
      }
    });

    bindCanvasToolbar();

    root.addEventListener('click', function (e) {
      var tipsOpen = e.target.closest('[data-elb-tips-open]');
      if (tipsOpen) {
        e.preventDefault();
        openElbTips();
        return;
      }
      var tipsClose = e.target.closest('[data-elb-tips-close]');
      if (tipsClose) {
        e.preventDefault();
        closeElbTips(true);
        return;
      }
      var ptab = e.target.closest('[data-elb-ptab]');
      if (ptab) {
        e.preventDefault();
        setElbTab(ptab.getAttribute('data-elb-ptab') || 'elements');
        return;
      }
      var reveal = e.target.closest('[data-elb-reveal]');
      if (reveal) {
        e.preventDefault();
        var revSide = reveal.getAttribute('data-elb-reveal');
        if (revSide) openElbPanel(revSide, true);
        return;
      }
      var toggle = e.target.closest('[data-elb-toggle]');
      if (toggle) {
        e.preventDefault();
        toggleElbPanel(toggle.getAttribute('data-elb-toggle') || 'left');
        return;
      }
      var closeBtn = e.target.closest('[data-elb-close]');
      if (closeBtn) {
        e.preventDefault();
        var closeSide = closeBtn.getAttribute('data-elb-close');
        if (closeSide === 'left' || closeSide === 'right') {
          toggleElbPanel(closeSide);
        } else {
          openElbPanel('none');
        }
        return;
      }
      var dock = e.target.closest('[data-elb-dock]');
      if (dock) {
        e.preventDefault();
        var mode = dock.getAttribute('data-elb-dock');
        if (mode === 'canvas') {
          openElbPanel('none');
        } else if (mode === 'elements') {
          setElbTab('elements');
        } else if (mode === 'settings') {
          setElbTab('settings');
        } else if (mode === 'edit') {
          openElbPanel('right', true);
        } else if (mode === 'structure') {
          setElbTab('structure');
        }
        return;
      }
      var spoilerToggle = e.target.closest('[data-landing-spoiler-toggle]');
      if (spoilerToggle) {
        e.preventDefault();
        var art = spoilerToggle.closest('[data-landing-spoiler]');
        if (art) setOpenSettingsSection(art.getAttribute('data-landing-spoiler') || 'brand');
        return;
      }
      var blockToggle = e.target.closest('.hs-landing-block-toggle');
      if (blockToggle) {
        e.preventDefault();
        e.stopPropagation();
        var blockArt = blockToggle.closest('[data-block-index]');
        if (blockArt) selectBlock(parseInt(blockArt.getAttribute('data-block-index'), 10), true);
        return;
      }
      var blockHead = e.target.closest('.hs-landing-block-head');
      if (blockHead && blocksEl && blocksEl.contains(blockHead)) {
        if (!e.target.closest('.hs-landing-block-actions,.hs-landing-vis-switch,.hs-landing-drag-handle')) {
          e.preventDefault();
          var blockArt2 = blockHead.closest('[data-block-index]');
          if (blockArt2) selectBlock(parseInt(blockArt2.getAttribute('data-block-index'), 10), true);
          return;
        }
      }
    });

    var overlay = root.querySelector('[data-elb-overlay]');
    if (overlay) {
      overlay.addEventListener('click', function () { openElbPanel('none'); });
    }

    root.querySelectorAll('[name="save_landing"],[name="publish_landing"]').forEach(function (btn) {
      btn.addEventListener('click', syncBeforeSubmit);
    });

    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        if (!isMobileElb()) {
          var overlayEl = root.querySelector('[data-elb-overlay]');
          if (overlayEl) overlayEl.hidden = true;
        } else if (!(root.querySelector('[data-elb-panel="left"]') || {}).classList.contains('is-open')
            && !(root.querySelector('[data-elb-panel="right"]') || {}).classList.contains('is-open')) {
          openElbPanel('none');
        }
        updateElbToggleUi();
      }, 120);
    });

    var savedTab = '';
    var savedLeft = '';
    var savedRight = '';
    try {
      savedTab = localStorage.getItem('hs_landing_tab') || 'elements';
      savedLeft = localStorage.getItem('hs_landing_panel_left') || '';
      savedRight = localStorage.getItem('hs_landing_panel_right') || '';
    } catch (e) { /* ignore */ }

    var leftPanel = root.querySelector('[data-elb-panel="left"]');
    var rightPanel = root.querySelector('[data-elb-panel="right"]');
    if (isMobileElb()) {
      openElbPanel('none');
    } else {
      setPanelOpen(leftPanel, savedLeft !== '0');
      setPanelOpen(rightPanel, savedRight !== '0');
      if (savedLeft === '' && savedRight === '') {
        setPanelOpen(leftPanel, true);
        setPanelOpen(rightPanel, true);
      }
    }
    if (root.querySelector('[data-elb-ppane="' + savedTab + '"]')) {
      setElbTab(savedTab);
    } else {
      setElbTab('elements');
    }
    updateElbToggleUi();

    root.querySelectorAll('[data-elb-toast]').forEach(function (toast) {
      setTimeout(function () { toast.remove(); }, 4500);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      var tips = root.querySelector('[data-elb-tips]');
      if (tips && !tips.hidden) closeElbTips(true);
    });
  }

  var _renderBlocksOrig = renderBlocks;
  renderBlocks = function () {
    _renderBlocksOrig();
    renderNavigator();
    if ((state.blocks || []).length && selectedBlockIdx >= state.blocks.length) {
      selectedBlockIdx = state.blocks.length - 1;
    }
  };

  renderPalette();
  renderNavEditor();
  renderBlocks();
  renderNavigator();
  initSettingsAccordions();
  initElbBuilder();
  syncHidden();
  updatePreview();
  if ((state.blocks || []).length && selectedBlockIdx >= 0) {
    var titleEl = root.querySelector('[data-elb-right-title]');
    if (titleEl) {
      var reg0 = cfg.blockTypes[(state.blocks[selectedBlockIdx] || {}).type] || {};
      titleEl.textContent = (MSG.edit_block || 'Edit') + ': ' + (reg0.label || (state.blocks[selectedBlockIdx] || {}).type || '');
    }
    var canvasHint = root.querySelector('[data-elb-canvas-hint]');
    if (canvasHint) canvasHint.hidden = false;
  }
})();