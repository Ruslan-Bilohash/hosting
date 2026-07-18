(function () {
  'use strict';

  /* Allow DOM updates when a host CSP enables Trusted Types without a policy. */
  try {
    if (window.trustedTypes && typeof window.trustedTypes.createPolicy === 'function') {
      if (!window.trustedTypes.defaultPolicy) {
        window.trustedTypes.createPolicy('default', {
          createHTML: function (s) { return s; },
          createScript: function (s) { return s; },
          createScriptURL: function (s) { return s; }
        });
      }
    }
  } catch (e) { /* ignore */ }

  var burger = document.querySelector('[data-hs-burger]');
  var sidebar = document.querySelector('[data-hs-sidebar]');
  var overlay = document.querySelector('[data-hs-overlay]');
  var closeBtn = document.querySelector('[data-hs-close]');

  function setOpen(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('open', open);
    if (overlay) overlay.classList.toggle('show', open);
    document.body.style.overflow = open ? 'hidden' : '';
  }

  if (burger) burger.addEventListener('click', function () { setOpen(true); });
  if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
  if (overlay) overlay.addEventListener('click', function () { setOpen(false); });

  window.addEventListener('resize', function () {
    if (window.innerWidth >= 1024) setOpen(false);
  });

  document.querySelectorAll('.hs-app-tile').forEach(function (tile) {
    tile.addEventListener('click', function () {
      var input = tile.querySelector('input[type="radio"]');
      if (input) input.checked = true;
    });
  });

  /* ——— Accordion sidebar (one group open) ——— */
  var accRoot = document.querySelector('[data-hp-accordion]');
  if (accRoot) {
    var groups = accRoot.querySelectorAll('[data-hp-acc-group]');
    var initial = document.body.getAttribute('data-hp-acc-open') || '';

    function openGroup(slug, save) {
      groups.forEach(function (g) {
        var on = g.getAttribute('data-hp-acc-group') === slug;
        g.classList.toggle('is-open', on);
        var btn = g.querySelector('.hp-acc-trigger');
        if (btn) btn.setAttribute('aria-expanded', on ? 'true' : 'false');
      });
      if (save && slug) {
        try { localStorage.setItem('hp-acc-open', slug); } catch (e) {}
      }
    }

    if (initial) {
      openGroup(initial, false);
    } else {
      try {
        var saved = localStorage.getItem('hp-acc-open');
        if (saved) openGroup(saved, false);
      } catch (e) {}
    }

    accRoot.querySelectorAll('.hp-acc-trigger').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var g = btn.closest('[data-hp-acc-group]');
        if (!g) return;
        var slug = g.getAttribute('data-hp-acc-group');
        var isOpen = g.classList.contains('is-open');
        if (isOpen) {
          openGroup('', true);
        } else {
          openGroup(slug, true);
        }
      });
    });
  }

  /* ——— Domain selector ——— */
  var domainRoot = document.querySelector('[data-hp-domain]');
  if (domainRoot) {
    var domainBtn = domainRoot.querySelector('[data-hp-domain-btn]');
    var domainMenu = domainRoot.querySelector('[data-hp-domain-menu]');
    function setDomainOpen(open) {
      domainRoot.classList.toggle('open', open);
      if (domainMenu) domainMenu.hidden = !open;
    }
    if (domainBtn) {
      domainBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        setDomainOpen(domainMenu && domainMenu.hidden);
      });
    }
    document.addEventListener('click', function (e) {
      if (!domainRoot.contains(e.target)) setDomainOpen(false);
    });
  }

  /* ——— Search ——— */
  var searchRoot = document.querySelector('[data-hp-search]');
  if (searchRoot) {
    var searchInput = searchRoot.querySelector('[data-hp-search-input]');
    var searchDrop = searchRoot.querySelector('[data-hp-search-drop]');
    var searchEmpty = searchRoot.querySelector('[data-hp-search-empty]');
    var searchLinks = searchRoot.querySelectorAll('[data-hp-search-item]');

    function filterSearch() {
      var q = (searchInput.value || '').trim().toLowerCase();
      var any = false;
      searchLinks.forEach(function (link) {
        var label = (link.getAttribute('data-hp-search-item') || '').toLowerCase();
        var show = q === '' || label.indexOf(q) !== -1;
        link.hidden = !show;
        if (show) any = true;
      });
      if (searchEmpty) searchEmpty.hidden = any || q === '';
      if (searchDrop) searchDrop.hidden = q === '';
    }

    if (searchInput) {
      searchInput.addEventListener('input', filterSearch);
      searchInput.addEventListener('focus', filterSearch);
    }
    document.addEventListener('click', function (e) {
      if (!searchRoot.contains(e.target) && searchDrop) searchDrop.hidden = true;
    });
  }

  /* ——— Language dropdown (header) ——— */
  document.querySelectorAll('[data-hp-lang]').forEach(function (langRoot) {
    var langBtn = langRoot.querySelector('[data-hp-lang-btn]');
    var langMenu = langRoot.querySelector('[data-hp-lang-menu]');
    function setLangOpen(open) {
      langRoot.classList.toggle('open', open);
      if (langMenu) langMenu.hidden = !open;
      if (langBtn) langBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if (langBtn) {
      langBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        document.querySelectorAll('[data-hp-lang].open').forEach(function (other) {
          if (other !== langRoot) {
            other.classList.remove('open');
            var m = other.querySelector('[data-hp-lang-menu]');
            var b = other.querySelector('[data-hp-lang-btn]');
            if (m) m.hidden = true;
            if (b) b.setAttribute('aria-expanded', 'false');
          }
        });
        setLangOpen(langMenu && langMenu.hidden);
      });
    }
    document.addEventListener('click', function (e) {
      if (!langRoot.contains(e.target)) setLangOpen(false);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setLangOpen(false);
    });
  });

  /* ——— Floating language widget (chat-style) ——— */
  (function () {
    var root = document.querySelector('[data-hs-lang-float]');
    if (!root) return;
    var panel = root.querySelector('[data-hs-lang-float-panel]');
    var fab = root.querySelector('[data-hs-lang-float-toggle]');
    var minimizeBtn = root.querySelector('[data-hs-lang-float-minimize]');
    var COOKIE = 'hs_lang_picked';
    var YEAR = 400 * 24 * 60 * 60 * 1000;

    function cookiePath() {
      return '/';
    }

    function markPicked() {
      try {
        document.cookie = COOKIE + '=1; path=' + cookiePath() + '; max-age=' + Math.floor(YEAR / 1000) + '; samesite=lax' + (location.protocol === 'https:' ? '; secure' : '');
      } catch (e) { /* ignore */ }
      try {
        localStorage.setItem(COOKIE, '1');
      } catch (e2) { /* ignore */ }
      root.setAttribute('data-picked', '1');
      root.classList.add('is-dismissed');
    }

    function hasPicked() {
      if (root.getAttribute('data-picked') === '1') return true;
      try {
        if (localStorage.getItem(COOKIE) === '1') return true;
      } catch (e) { /* ignore */ }
      return document.cookie.split(';').some(function (c) {
        return c.trim().indexOf(COOKIE + '=1') === 0;
      });
    }

    function setOpen(open) {
      open = !!open;
      root.classList.toggle('is-open', open);
      if (panel) {
        panel.hidden = !open;
        if (open) panel.removeAttribute('hidden');
      }
      if (fab) fab.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        root.classList.remove('is-dismissed');
      } else if (hasPicked()) {
        root.classList.add('is-dismissed');
      }
    }

    // First visit: open panel by default; after pick: hide until footer opens
    if (hasPicked()) {
      root.classList.add('is-dismissed');
      setOpen(false);
    } else {
      root.classList.remove('is-dismissed');
      setOpen(true);
    }

    if (fab) {
      fab.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setOpen(!(panel && !panel.hidden));
      });
    }
    if (minimizeBtn) {
      minimizeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setOpen(false);
      });
    }

    document.querySelectorAll('[data-hs-lang-float-open]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        root.classList.remove('is-dismissed');
        setOpen(true);
        if (fab) fab.focus();
      });
    });

    // Any language pick link marks choice (header dropdown + float)
    document.querySelectorAll('[data-hs-lang-pick]').forEach(function (link) {
      link.addEventListener('click', function () {
        markPicked();
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && root.classList.contains('is-open')) {
        setOpen(false);
      }
    });
  })();

  function hsTldExtraIsHidden(el) {
    return !!(el && (el.hidden || el.classList.contains('is-collapsed') || el.classList.contains('hidden')));
  }

  function hsSetTldExtraExpanded(el, expanded) {
    if (!el) return;
    el.hidden = !expanded;
    el.classList.toggle('is-collapsed', !expanded);
    el.classList.toggle('hidden', !expanded);
    if (expanded) {
      el.removeAttribute('hidden');
    } else {
      el.setAttribute('hidden', '');
    }
  }

  function hsUpdateTldToggleBtn(btn, expanded, escFn) {
    if (!btn) return;
    btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    var icon = expanded ? 'fa-minus' : 'fa-plus';
    var label = expanded
      ? (btn.getAttribute('data-label-less') || 'Fewer zones')
      : (btn.getAttribute('data-label-more') || 'More zones');
    btn.innerHTML = '<i class="fa-solid ' + icon + '" aria-hidden="true"></i> ' + escFn(label);
  }

  function hsCollectTypewriterTlds(root) {
    var tlds = [];
    if (!root) return tlds;
    var extra = root.querySelector('[data-hs-tld-extra]');
    var extraHidden = hsTldExtraIsHidden(extra);
    root.querySelectorAll('[data-hs-hero-tld]').forEach(function (cb) {
      if (extraHidden && extra && extra.contains(cb)) return;
      var chip = cb.closest('.hs-hero-tld-chip');
      var nameEl = chip && chip.querySelector('.hs-hero-tld-chip-name');
      if (nameEl) {
        var label = (nameEl.textContent || '').replace(/^\./, '').trim().toLowerCase();
        if (label && tlds.indexOf(label) < 0) tlds.push(label);
        return;
      }
      var val = (cb.value || '').replace(/^\./, '').trim().toLowerCase();
      if (val && tlds.indexOf(val) < 0) tlds.push(val);
    });
    root.querySelectorAll('.hs-hero-tld-chip-name').forEach(function (el) {
      if (extraHidden && extra && extra.contains(el)) return;
      var label = (el.textContent || '').replace(/^\./, '').trim().toLowerCase();
      if (label && tlds.indexOf(label) < 0) tlds.push(label);
    });
    return tlds;
  }

  function hsInitDomainTypewriter(input, rootEl) {
    if (!input) return;
    var wrap = input.closest('.hs-domain-typewriter-wrap');
    var textEl = wrap && wrap.querySelector('[data-hs-typewriter-text]');
    if (!wrap || !textEl) return;
    if ((input.value || '').trim()) return;

    var root = rootEl || wrap.closest('[data-hs-domain-mockup], [data-hs-domain-search], form');
    var basesRaw = (root && root.getAttribute('data-typewriter-bases')) || '';
    var bases = basesRaw.split(',').map(function (s) {
      return String(s || '').trim().toLowerCase().replace(/\.$/, '');
    }).filter(Boolean);
    if (bases.length === 0) {
      bases = [String((root && root.getAttribute('data-typewriter-base')) || 'solaskinner').trim().toLowerCase().replace(/\.$/, '')];
    }

    var tldsRaw = (root && root.getAttribute('data-typewriter-tlds')) || '';
    var tlds = tldsRaw.split(',').map(function (s) {
      return String(s || '').trim().toLowerCase().replace(/^\./, '');
    }).filter(Boolean);
    if (tlds.length === 0) tlds = hsCollectTypewriterTlds(root);
    if (tlds.length === 0) tlds = ['com', 'eu', 'lt', 'pl', 'de'];

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var baseIdx = 0;
    var tldIdx = 0;
    var typedTld = '';
    var timer = null;
    var running = false;

    function currentPrefix() {
      return bases[baseIdx] + '.';
    }

    function paint() {
      textEl.textContent = '';
      textEl.appendChild(document.createTextNode(currentPrefix()));
      var tldSpan = document.createElement('span');
      tldSpan.className = 'hs-typewriter-tld';
      tldSpan.textContent = typedTld;
      textEl.appendChild(tldSpan);
    }

    function wait(ms, fn) {
      clearTimeout(timer);
      timer = setTimeout(fn, ms);
    }

    function deleteStep() {
      if (!running) return;
      if (typedTld.length > 0) {
        typedTld = typedTld.slice(0, -1);
        paint();
        wait(42, deleteStep);
        return;
      }
      tldIdx = (tldIdx + 1) % tlds.length;
      if (tldIdx === 0) baseIdx = (baseIdx + 1) % bases.length;
      wait(320, typeStep);
    }

    function typeStep() {
      if (!running) return;
      var target = tlds[tldIdx];
      if (!target) {
        tldIdx = (tldIdx + 1) % Math.max(tlds.length, 1);
        wait(320, typeStep);
        return;
      }
      if (typedTld.length < target.length) {
        typedTld += target.charAt(typedTld.length);
        paint();
        wait(72, typeStep);
        return;
      }
      wait(2000, deleteStep);
    }

    function start() {
      if (reducedMotion) {
        typedTld = tlds[0];
        paint();
        return;
      }
      running = true;
      wrap.classList.remove('is-active');
      typedTld = '';
      baseIdx = 0;
      tldIdx = 0;
      paint();
      wait(480, typeStep);
    }

    function stop() {
      running = false;
      clearTimeout(timer);
      wrap.classList.add('is-active');
    }

    input.addEventListener('focus', stop);
    input.addEventListener('input', function () {
      var has = !!(input.value || '').trim();
      wrap.classList.toggle('has-value', has);
      if (has) stop();
    });
    input.addEventListener('blur', function () {
      if (!(input.value || '').trim()) {
        wrap.classList.remove('has-value', 'is-active');
        start();
      }
    });

    start();
  }

  /* ——— Homepage domain mockup → /domain ——— */
  var domainMockup = document.querySelector('[data-hs-domain-mockup]');
  if (domainMockup) {
    var mockInput = domainMockup.querySelector('[data-hs-domain-mockup-input]');
    var mockBtn = domainMockup.querySelector('[data-hs-domain-mockup-btn]');
    var mockTarget = domainMockup.getAttribute('data-domain-page') || '/domain';
    function goDomainPage() {
      var q = (mockInput && mockInput.value || '').trim();
      var url = mockTarget;
      if (q) {
        url += (url.indexOf('?') >= 0 ? '&' : '?') + 'sld=' + encodeURIComponent(q);
      }
      window.location.href = url;
    }
    if (mockBtn) mockBtn.addEventListener('click', goDomainPage);
    if (mockInput) {
      mockInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          goDomainPage();
        }
      });
    }
    hsInitDomainTypewriter(mockInput, domainMockup);
  }

  /* ——— Domain search page (real RDAP/WHOIS via domain-check.php) ——— */
  var domainSearchForm = document.querySelector('[data-hs-domain-search]');
  if (domainSearchForm) {
    var isPageForm = domainSearchForm.classList.contains('hs-domain-search-form--page');
    var isPanelForm = domainSearchForm.classList.contains('hs-domain-search-form--panel');
    var isFullSearchForm = isPageForm || isPanelForm;
    var isPanelMode = domainSearchForm.getAttribute('data-panel-mode') === '1';
    var domainRoot = domainSearchForm.closest('.hs-domain-search-page, .hs-panel-domain-search');
    var domainInput = domainSearchForm.querySelector('[data-hs-domain-input]');
    var domainBtn = domainSearchForm.querySelector('[data-hs-domain-btn]');
    var resultsWrap = domainRoot ? domainRoot.querySelector('[data-hs-domain-results-wrap]') : null;
    var domainResult = domainRoot
      ? domainRoot.querySelector('[data-hs-domain-result]')
      : (function () {
          var card = domainSearchForm.closest('.hs-hero-card, .hs-domain-search-card');
          return card ? card.querySelector('[data-hs-domain-result]') : document.querySelector('[data-hs-domain-result]');
        })();
    var checkUrl = domainSearchForm.getAttribute('data-check-url') || '';
    var msgAvailable = domainSearchForm.getAttribute('data-msg-available') || 'Available';
    var msgTaken = domainSearchForm.getAttribute('data-msg-taken') || 'Taken';
    var msgInvalid = domainSearchForm.getAttribute('data-msg-invalid') || 'Invalid domain';
    var msgError = domainSearchForm.getAttribute('data-msg-error') || 'Lookup failed';
    var msgChecking = domainSearchForm.getAttribute('data-msg-checking') || 'Checking…';
    var msgCta = domainSearchForm.getAttribute('data-msg-cta') || 'Register';
    var msgPickedLabel = domainSearchForm.getAttribute('data-msg-picked-label') || 'Your domain';
    var msgBundleCta = domainSearchForm.getAttribute('data-msg-bundle-cta') || msgCta;
    var msgDomainCta = domainSearchForm.getAttribute('data-msg-domain-cta') || 'Domain only';
    var msgRegisterThis = domainSearchForm.getAttribute('data-msg-register-this') || 'Register this domain';
    var msgRegisterSelected = domainSearchForm.getAttribute('data-msg-register-selected') || 'Register selected';
    var msgSelectedCount = domainSearchForm.getAttribute('data-msg-selected-count') || '{count} domains selected';
    var msgSelectedOne = domainSearchForm.getAttribute('data-msg-selected-one') || '1 domain selected';
    var msgCartTotal = domainSearchForm.getAttribute('data-msg-cart-total') || 'Total';
    var msgNoTlds = domainSearchForm.getAttribute('data-msg-no-tlds') || 'Select at least one zone';
    var msgNorid = domainSearchForm.getAttribute('data-msg-norid') || '';
    var colDomain = domainSearchForm.getAttribute('data-col-domain') || 'Domain';
    var colStatus = domainSearchForm.getAttribute('data-col-status') || 'Status';
    var colPrice = domainSearchForm.getAttribute('data-col-price') || 'Price';
    var registerBase = domainSearchForm.getAttribute('data-register-base') || 'register.php';
    var msgShowMore = domainSearchForm.getAttribute('data-msg-show-more') || 'Show more domains';
    var extraTldsRaw = domainSearchForm.getAttribute('data-extra-tlds') || '';
    var extraTldsPool = extraTldsRaw ? extraTldsRaw.split(',').map(function (v) { return v.trim(); }).filter(Boolean) : [];
    var tldChecks = domainSearchForm.querySelectorAll('[data-hs-hero-tld]');
    var lastSld = '';
    var mergedResults = [];
    var fetchedExtra = false;
    var domainLoadingTimer = null;

    var tldToggle = domainSearchForm.querySelector('[data-hs-tld-toggle]');
    var tldExtra = domainSearchForm.querySelector('[data-hs-tld-extra]');

    function escHtml(s) {
      var d = document.createElement('div');
      d.textContent = String(s || '');
      return d.innerHTML;
    }

    if (tldToggle && tldExtra) {
      hsUpdateTldToggleBtn(tldToggle, !hsTldExtraIsHidden(tldExtra), escHtml);
      tldToggle.addEventListener('click', function () {
        var expanded = tldToggle.getAttribute('aria-expanded') === 'true';
        var nextExpanded = !expanded;
        hsSetTldExtraExpanded(tldExtra, nextExpanded);
        hsUpdateTldToggleBtn(tldToggle, nextExpanded, escHtml);
        tldChecks = domainSearchForm.querySelectorAll('[data-hs-hero-tld]');
      });
    }

    function renderPickedCard(domain, opts) {
      opts = opts || {};
      var glow = opts.glow !== false && opts.status !== 'taken';
      var cls = 'hs-domain-picked' + (glow ? ' is-glow' : '') + (opts.status === 'taken' ? ' is-taken' : '');
      var status = '';
      if (opts.status === 'available') {
        status = '<span class="hs-domain-picked-status is-ok">' + escHtml(msgAvailable) + '</span>';
      } else if (opts.status === 'taken') {
        status = '<span class="hs-domain-picked-status is-taken">' + escHtml(msgTaken) + '</span>';
      }
      var check = glow ? '<span class="hs-domain-picked-check" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>' : '';
      return '<div class="' + cls + '" data-hs-domain-picked data-initial-domain="' + escHtml(domain) + '">'
        + '<span class="hs-domain-picked-icon" aria-hidden="true"><i class="fa-solid fa-globe"></i></span>'
        + '<div class="hs-domain-picked-body">'
        + '<span class="hs-domain-picked-label">' + escHtml(msgPickedLabel) + '</span>'
        + '<strong class="hs-domain-picked-name">' + escHtml(domain) + '</strong>'
        + (opts.price ? '<span class="hs-domain-picked-price">' + escHtml(opts.price) + '</span>' : '')
        + status
        + '</div>'
        + check
        + '</div>';
    }

    function stopDomainSearchLoading() {
      if (domainLoadingTimer) {
        clearInterval(domainLoadingTimer);
        domainLoadingTimer = null;
      }
    }

    function parseCheckSteps() {
      var raw = domainSearchForm.getAttribute('data-check-steps') || '';
      if (!raw) return [msgChecking];
      try {
        var steps = JSON.parse(raw);
        if (Array.isArray(steps) && steps.length) {
          return steps.map(function (s) { return String(s || '').trim(); }).filter(Boolean);
        }
      } catch (e) { /* ignore */ }
      return [msgChecking];
    }

    function renderDomainSearchLoading() {
      var steps = parseCheckSteps();
      var agents = [
        { icon: 'fa-magnifying-glass' },
        { icon: 'fa-database' },
        { icon: 'fa-brain' }
      ];
      var agentsHtml = agents.map(function (a, i) {
        return '<div class="hs-domain-agent' + (i === 0 ? ' is-active is-thinking' : '') + '" data-hs-domain-agent="' + i + '">'
          + '<span class="hs-domain-agent-ring" aria-hidden="true"></span>'
          + '<span class="hs-domain-agent-avatar" aria-hidden="true"><i class="fa-solid ' + a.icon + '"></i></span>'
          + '<span class="hs-domain-agent-dots" aria-hidden="true"><span></span><span></span><span></span></span>'
          + '</div>';
      }).join('');
      var stepsHtml = steps.map(function (s, i) {
        return '<li class="hs-domain-agent-step' + (i === 0 ? ' is-active' : '') + '" data-hs-domain-step="' + i + '">'
          + '<span class="hs-domain-agent-step-icon" aria-hidden="true"><i class="fa-solid fa-circle"></i></span>'
          + '<span class="hs-domain-agent-step-text">' + escHtml(s) + '</span>'
          + '</li>';
      }).join('');

      return '<div class="hs-domain-search-loading' + (isFullSearchForm ? ' hs-domain-search-loading--page' : '') + '" data-hs-domain-loading role="status" aria-live="polite" aria-busy="true">'
        + '<div class="hs-domain-agent-stage">'
        + '<div class="hs-domain-agents" aria-hidden="true">'
        + '<div class="hs-domain-agent-links">'
        + '<span class="hs-domain-agent-link hs-domain-agent-link--a"></span>'
        + '<span class="hs-domain-agent-link hs-domain-agent-link--b"></span>'
        + '</div>'
        + agentsHtml
        + '</div>'
        + '<p class="hs-domain-agent-status" data-hs-domain-loading-status>' + escHtml(steps[0]) + '</p>'
        + '<ol class="hs-domain-agent-steps">' + stepsHtml + '</ol>'
        + '<div class="hs-domain-agent-progress" aria-hidden="true"><span class="hs-domain-agent-progress-bar"></span></div>'
        + '</div></div>';
    }

    function startDomainSearchLoading() {
      stopDomainSearchLoading();
      if (!domainResult) return;
      var root = domainResult.querySelector('[data-hs-domain-loading]');
      if (!root) return;
      var steps = parseCheckSteps();
      var statusEl = root.querySelector('[data-hs-domain-loading-status]');
      var stepEls = root.querySelectorAll('[data-hs-domain-step]');
      var agentEls = root.querySelectorAll('[data-hs-domain-agent]');
      var progressBar = root.querySelector('.hs-domain-agent-progress-bar');
      var idx = 0;

      function applyStep(i) {
        if (statusEl) {
          statusEl.classList.remove('is-changing');
          void statusEl.offsetWidth;
          statusEl.textContent = steps[i];
          statusEl.classList.add('is-changing');
        }
        stepEls.forEach(function (el, j) {
          el.classList.toggle('is-active', j === i);
          el.classList.toggle('is-done', j < i);
        });
        agentEls.forEach(function (el, j) {
          var active = j === (i % agentEls.length);
          el.classList.toggle('is-active', active);
          el.classList.toggle('is-thinking', active);
        });
        if (progressBar) {
          progressBar.style.width = Math.min(96, ((i + 1) / steps.length) * 100) + '%';
        }
      }

      applyStep(0);
      domainLoadingTimer = setInterval(function () {
        idx = (idx + 1) % steps.length;
        applyStep(idx);
      }, 1350);
    }

    function renderDomainResult(html) {
      stopDomainSearchLoading();
      if (domainResult) domainResult.innerHTML = html;
      if (resultsWrap) {
        if (html && String(html).trim() !== '') {
          resultsWrap.hidden = false;
        }
      }
      var showMoreBtn = domainResult && domainResult.querySelector('[data-hs-domain-show-more]');
      if (showMoreBtn) {
        showMoreBtn.addEventListener('click', loadMoreDomains);
      }
      bindDomainPickHandlers();
    }

    function renderRecentSearches(items) {
      if (!isFullSearchForm || !domainRoot) return;
      var slot = domainRoot.querySelector('[data-hs-domain-recent-slot]');
      if (!slot || !Array.isArray(items) || items.length === 0) {
        if (slot) slot.innerHTML = '';
        return;
      }
      var title = domainSearchForm.getAttribute('data-recent-title') || 'Recent searches';
      var hint = domainSearchForm.getAttribute('data-recent-hint') || '';
      var availLabel = domainSearchForm.getAttribute('data-recent-available') || 'Available';
      var base = domainSearchForm.getAttribute('data-domain-page-url') || '/domain';
      var html = '<aside class="hs-domain-recent" data-hs-domain-recent aria-label="' + escHtml(title) + '">'
        + '<div class="hs-domain-recent-head"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>' + escHtml(title) + '</span></div>';
      if (hint) {
        html += '<p class="hs-domain-recent-hint hp-muted">' + escHtml(hint) + '</p>';
      }
      html += '<div class="hs-domain-recent-list" role="list">';
      items.forEach(function (row) {
        var q = String(row.query || '').trim();
        if (!q) return;
        var sep = base.indexOf('?') >= 0 ? '&' : '?';
        var href = base + sep + 'sld=' + encodeURIComponent(q);
        var badge = (row.available_count > 0)
          ? '<span class="hs-domain-recent-badge is-ok" title="' + escHtml(availLabel) + '">' + escHtml(String(row.available_count)) + '</span>'
          : '';
        html += '<a href="' + escHtml(href) + '" class="hs-domain-recent-chip" role="listitem" data-hs-recent-query="' + escHtml(q) + '">'
          + '<span class="hs-domain-recent-query">' + escHtml(q) + '</span>' + badge + '</a>';
      });
      html += '</div></aside>';
      slot.innerHTML = html;
    }

    function selectedTlds() {
      var out = [];
      tldChecks.forEach(function (cb) {
        if (cb.checked) out.push(cb.value);
      });
      return out;
    }

    function registerUrl(domains, orderType) {
      var regSep = registerBase.indexOf('?') >= 0 ? '&' : '?';
      var order = orderType || 'domain';
      if (!Array.isArray(domains)) domains = [domains];
      domains = domains.map(function (d) { return String(d || '').trim(); }).filter(Boolean);
      if (domains.length === 0) return registerBase;
      if (isPanelMode) {
        return registerBase + regSep + 'domain=' + encodeURIComponent(domains[0]);
      }
      if (domains.length === 1) {
        return registerBase + regSep + 'domain=' + encodeURIComponent(domains[0]) + '&order=' + encodeURIComponent(order);
      }
      return registerBase + regSep + 'domains=' + encodeURIComponent(domains.join(',')) + '&order=' + encodeURIComponent(order);
    }

    function selectedDomainRows() {
      if (!domainResult) return [];
      var picks = domainResult.querySelectorAll('[data-hs-domain-pick]:checked');
      var out = [];
      picks.forEach(function (cb) {
        out.push({
          domain: cb.value,
          price: parseFloat(cb.getAttribute('data-price') || '0') || 0,
          price_label: cb.getAttribute('data-price-label') || ''
        });
      });
      return out;
    }

    function formatCartTotal(rows) {
      var sum = 0;
      rows.forEach(function (row) { sum += row.price; });
      if (sum <= 0) return '';
      var first = rows[0];
      if (first && first.price_label && String(first.price_label).indexOf('€') >= 0) {
        return '€' + sum.toFixed(2).replace('.', ',');
      }
      return sum.toFixed(2);
    }

    function updateDomainCartBar() {
      if (!domainResult) return;
      var bar = domainResult.querySelector('[data-hs-domain-cart-bar]');
      if (!bar) return;
      var rows = selectedDomainRows();
      if (rows.length === 0) {
        bar.hidden = true;
        return;
      }
      bar.hidden = false;
      var countEl = bar.querySelector('[data-hs-domain-cart-count]');
      var totalEl = bar.querySelector('[data-hs-domain-cart-total]');
      var btn = bar.querySelector('[data-hs-domain-cart-register]');
      var countLabel = rows.length === 1
        ? msgSelectedOne
        : msgSelectedCount.replace('{count}', String(rows.length));
      if (countEl) countEl.textContent = countLabel;
      var totalLabel = formatCartTotal(rows);
      if (totalEl) {
        totalEl.textContent = totalLabel ? (msgCartTotal + ': ' + totalLabel) : '';
      }
      if (btn) {
        btn.onclick = function () {
          window.location.href = registerUrl(rows.map(function (r) { return r.domain; }), 'domain');
        };
      }
    }

    function bindDomainPickHandlers() {
      if (!domainResult) return;
      domainResult.querySelectorAll('[data-hs-domain-pick]').forEach(function (cb) {
        cb.addEventListener('change', updateDomainCartBar);
      });
      updateDomainCartBar();
    }

    function renderBatchResults(rows, opts) {
      opts = opts || {};
      rows = rows.slice().sort(function (a, b) {
        if (!!a.available === !!b.available) return String(a.domain).localeCompare(String(b.domain));
        return a.available ? -1 : 1;
      });
      var listCls = 'hs-hero-domain-list' + (isFullSearchForm ? ' hs-hero-domain-list--page' : '');
      var html = '<div class="' + listCls + '">';
      rows.forEach(function (row) {
        var available = !!row.available;
        var premium = !!row.premium;
        var rowCls = 'hs-hero-domain-row' + (isFullSearchForm ? ' hs-hero-domain-row--page' : '') + (available ? ' is-available' : ' is-taken') + (premium ? ' is-premium' : '');
        html += '<article class="' + rowCls + '">';
        if (available && !row.registry_manual && isFullSearchForm) {
          html += '<label class="hs-domain-row-pick" title="' + escHtml(msgRegisterThis) + '">'
            + '<input type="checkbox" data-hs-domain-pick value="' + escHtml(row.domain) + '"'
            + ' data-price="' + escHtml(String(row.price || 0)) + '"'
            + ' data-price-label="' + escHtml(row.price_label || '') + '"'
            + ' data-premium="' + (premium ? '1' : '0') + '">'
            + '<span class="hs-domain-row-pick-ui" aria-hidden="true"><i class="fa-solid fa-check"></i></span>'
            + '</label>';
        }
        html += '<div class="hs-hero-domain-row-main">'
          + '<strong class="hs-hero-domain-row-name">' + escHtml(row.domain)
          + (premium ? ' <span class="hs-domain-premium-badge">Premium</span>' : '')
          + '</strong>'
          + '<span class="hs-hero-domain-row-price">' + escHtml(row.price_label || '') + '</span>'
          + '</div>'
          + '<span class="hs-hero-domain-row-status' + (available ? ' is-ok' : ' is-taken') + '">'
          + escHtml(available ? msgAvailable : msgTaken)
          + '</span>';
        if (available && !row.registry_manual) {
          html += '<div class="hs-hero-domain-row-actions">'
            + '<a href="' + escHtml(registerUrl(row.domain, 'domain')) + '" class="hs-btn hs-btn-primary hs-btn-sm">' + escHtml(msgRegisterThis) + '</a>';
          if (!isPanelMode) {
            html += '<a href="' + escHtml(registerUrl(row.domain, 'bundle')) + '" class="hs-btn hs-btn-ghost hs-btn-sm">' + escHtml(msgBundleCta) + '</a>';
          }
          html += '</div>';
        }
        html += '</article>';
      });
      html += '</div>';
      if (isFullSearchForm) {
        html += '<div class="hs-domain-cart-bar" data-hs-domain-cart-bar hidden>'
          + '<div class="hs-domain-cart-bar-meta">'
          + '<span data-hs-domain-cart-count></span>'
          + '<span class="hs-domain-cart-bar-total" data-hs-domain-cart-total></span>'
          + '</div>'
          + '<button type="button" class="hs-btn hs-btn-primary hs-domain-cart-bar-btn" data-hs-domain-cart-register>'
          + '<i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> ' + escHtml(msgRegisterSelected)
          + '</button></div>';
      }
      if (opts.showMoreBtn) {
        html += '<div class="hs-domain-search-more-wrap">'
          + '<button type="button" class="hs-btn hs-btn-ghost hs-domain-search-more-btn" data-hs-domain-show-more>'
          + '<i class="fa-solid fa-layer-group" aria-hidden="true"></i> ' + escHtml(msgShowMore)
          + '</button></div>';
      }
      return html;
    }

    function mergeResultRows(existing, incoming) {
      var map = {};
      existing.forEach(function (row) {
        map[String(row.domain || '').toLowerCase()] = row;
      });
      incoming.forEach(function (row) {
        var key = String(row.domain || '').toLowerCase();
        if (key) map[key] = row;
      });
      return Object.keys(map).sort().map(function (k) { return map[k]; });
    }

    function loadMoreDomains() {
      if (!lastSld || !checkUrl || extraTldsPool.length === 0 || fetchedExtra) return;
      var btn = domainResult && domainResult.querySelector('[data-hs-domain-show-more]');
      if (btn) {
        btn.disabled = true;
        btn.textContent = msgChecking;
      }
      var url = checkUrl + (checkUrl.indexOf('?') >= 0 ? '&' : '?')
        + 'sld=' + encodeURIComponent(lastSld)
        + '&tlds=' + encodeURIComponent(extraTldsPool.join(','));
      fetch(url, { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || !data.ok || !Array.isArray(data.results)) {
            if (btn) {
              btn.disabled = false;
              btn.innerHTML = '<i class="fa-solid fa-layer-group" aria-hidden="true"></i> ' + escHtml(msgShowMore);
            }
            return;
          }
          fetchedExtra = true;
          mergedResults = mergeResultRows(mergedResults, data.results);
          renderDomainResult(renderBatchResults(mergedResults, { showMoreBtn: false }));
        })
        .catch(function () {
          if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-layer-group" aria-hidden="true"></i> ' + escHtml(msgShowMore);
          }
        });
    }

    domainSearchForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!domainInput || !checkUrl) return;
      var q = (domainInput.value || '').trim();
      if (!q) return;
      if (domainBtn) {
        domainBtn.disabled = true;
        domainBtn.textContent = msgChecking;
      }
      fetchedExtra = false;
      mergedResults = [];
      if (resultsWrap) resultsWrap.hidden = false;
      renderDomainResult(renderDomainSearchLoading());
      startDomainSearchLoading();

      var url;
      // Normalize full domain input (strip protocol/www/path)
      var qNorm = q.replace(/^https?:\/\//i, '').replace(/\/.*$/, '').replace(/^www\./i, '').trim().toLowerCase();
      if (qNorm.indexOf('.') >= 0) {
        lastSld = '';
        // Full FQDN like hosting.shop — check directly, zones not required
        url = checkUrl + (checkUrl.indexOf('?') >= 0 ? '&' : '?') + 'domain=' + encodeURIComponent(qNorm);
      } else {
        lastSld = qNorm || q;
        var tlds = selectedTlds();
        if (tlds.length === 0) {
          // Auto-select featured chips instead of blocking the search
          tldChecks.forEach(function (cb) {
            if (cb.hasAttribute('checked') || cb.defaultChecked) cb.checked = true;
          });
          tlds = selectedTlds();
        }
        if (tlds.length === 0) {
          tldChecks.forEach(function (cb, i) {
            if (i < 5) cb.checked = true;
          });
          tlds = selectedTlds();
        }
        if (tlds.length === 0) {
          stopDomainSearchLoading();
          renderDomainResult('<span style="color:#dc2626">' + escHtml(msgNoTlds) + '</span>');
          if (domainBtn) {
            domainBtn.disabled = false;
            var btnTxt = domainBtn.querySelector('.hs-domain-search-submit-text');
            if (btnTxt) btnTxt.textContent = domainBtn.getAttribute('data-label') || '';
            else domainBtn.textContent = domainBtn.getAttribute('data-label') || '';
          }
          return;
        }
        url = checkUrl + (checkUrl.indexOf('?') >= 0 ? '&' : '?')
          + 'sld=' + encodeURIComponent(lastSld)
          + '&tlds=' + encodeURIComponent(tlds.join(','));
      }

      fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (r) {
          return r.text().then(function (text) {
            var data = null;
            try {
              data = text ? JSON.parse(text) : null;
            } catch (parseErr) {
              data = null;
            }
            if (!r.ok || !data) {
              var statusErr = msgError;
              if (r.status === 429) statusErr = msgError;
              if (r.status === 504 || r.status === 502 || r.status === 503) statusErr = msgError;
              return { __httpError: true, status: r.status, data: data, message: statusErr };
            }
            return data;
          });
        })
        .then(function (data) {
          if (!data || data.__httpError || !data.ok) {
            var err = msgError;
            if (data && (data.error === 'invalid' || data.error === 'invalid_sld')) err = msgInvalid;
            if (data && data.error === 'no_tlds') err = msgNoTlds;
            if (data && data.__httpError && data.data && data.data.error === 'rate_limit') err = msgError;
            renderDomainResult('<span style="color:#dc2626">' + escHtml(err) + '</span>');
            return;
          }
          var html;
          if (Array.isArray(data.results)) {
            if (data.results.length === 0) {
              renderDomainResult('<span style="color:#dc2626">' + escHtml(msgError) + '</span>');
              return;
            }
            mergedResults = data.results.slice();
            var canMore = isFullSearchForm && extraTldsPool.length > 0 && lastSld !== '';
            html = renderBatchResults(mergedResults, { showMoreBtn: canMore });
          } else if (data.available) {
            html = renderPickedCard(data.domain, { status: 'available', price: data.price_label || '' });
            html += '<div class="hs-domain-picked-actions">'
              + '<a href="' + escHtml(registerUrl(data.domain, 'domain')) + '" class="hs-btn hs-btn-primary" style="width:100%">' + escHtml(msgRegisterThis) + '</a>';
            if (!isPanelMode) {
              html += '<a href="' + escHtml(registerUrl(data.domain, 'bundle')) + '" class="hs-btn hs-btn-ghost" style="width:100%">' + escHtml(msgBundleCta) + '</a>';
            }
            html += '</div>';
          } else {
            html = renderPickedCard(data.domain, { status: 'taken', glow: false });
          }
          renderDomainResult(html);
          if (Array.isArray(data.recent)) {
            renderRecentSearches(data.recent);
          }
        })
        .catch(function () {
          renderDomainResult('<span style="color:#dc2626">' + escHtml(msgError) + '</span>');
        })
        .finally(function () {
          if (domainBtn) {
            domainBtn.disabled = false;
            domainBtn.textContent = domainBtn.getAttribute('data-label') || domainBtn.textContent;
          }
        });
    });
    if (domainBtn && !domainBtn.getAttribute('data-label')) {
      domainBtn.setAttribute('data-label', domainBtn.textContent || '');
    }

    if (domainSearchForm.getAttribute('data-hs-domain-autosearch') === '1' && domainInput && (domainInput.value || '').trim()) {
      if (typeof domainSearchForm.requestSubmit === 'function') {
        domainSearchForm.requestSubmit();
      } else {
        domainSearchForm.dispatchEvent(new Event('submit', { cancelable: true }));
      }
    }

    if (!isPanelForm) {
      hsInitDomainTypewriter(domainInput, domainSearchForm);
    } else if (domainInput) {
      var panelWrap = domainInput.closest('.hs-domain-typewriter-wrap');
      if (panelWrap) panelWrap.classList.add('is-active');
    }
  }

  /* ——— Copy text / input value ——— */
  document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-copy-target');
      var el = id ? document.getElementById(id) : null;
      if (!el) return;
      var text = (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') ? (el.value || '') : (el.textContent || '');
      if (!text) return;
      function done() { flashCopyButton(btn); }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(done);
      } else {
        done();
      }
    });
    if (!btn.getAttribute('data-default-html')) {
      btn.setAttribute('data-default-html', btn.innerHTML);
    }
  });

  /* ——— PHP version cards ——— */
  document.querySelectorAll('.hs-php-ver-card input[type="radio"]').forEach(function (radio) {
    function syncCards() {
      document.querySelectorAll('.hs-php-ver-card').forEach(function (card) {
        var input = card.querySelector('input[type="radio"]');
        card.classList.toggle('is-active', !!(input && input.checked));
      });
    }
    radio.addEventListener('change', syncCards);
    syncCards();
  });

  function flashCopyButton(btn) {
    var copiedLabel = btn.getAttribute('data-copied-label');
    if (!copiedLabel) return;
    var defaultHtml = btn.getAttribute('data-default-html');
    if (!defaultHtml) {
      defaultHtml = btn.innerHTML;
      btn.setAttribute('data-default-html', defaultHtml);
    }
    btn.innerHTML = copiedLabel;
    window.setTimeout(function () { btn.innerHTML = defaultHtml; }, 1600);
  }

  function hsCsrfToken() {
    var meta = document.querySelector('meta[name="hs-csrf"]');
    return meta ? meta.getAttribute('content') || '' : '';
  }

  function hsSecretFromEl(el, btn) {
    if (!el) return '';
    if (el.getAttribute('data-secret-hidden') === '1') return '';
    var text = (el.textContent || '').trim();
    if (!text || /^[\u2022•]+$/.test(text)) return '';
    return text;
  }

  function hsMaskSecret(el, secret) {
    if (!el) return;
    el.textContent = '\u2022'.repeat(Math.min(16, Math.max(8, secret.length || 12)));
    el.setAttribute('data-secret-hidden', '1');
  }

  function hsRevealSecret(btn, onSecret) {
    var url = btn.getAttribute('data-reveal-url') || '';
    var promptText = btn.getAttribute('data-prompt') || 'Enter your current password';
    var current = window.prompt(promptText);
    if (!current) return;
    btn.disabled = true;
    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ csrf: hsCsrfToken(), current_pass: current })
    }).then(function (res) { return res.json().then(function (data) { return { res: res, data: data }; }); })
      .then(function (pack) {
        btn.disabled = false;
        if (!pack.data || !pack.data.ok) {
          window.alert((pack.data && pack.data.message) || 'Could not reveal password.');
          return;
        }
        onSecret(pack.data.password || '');
      })
      .catch(function () {
        btn.disabled = false;
        window.alert('Network error. Try again.');
      });
  }

  document.querySelectorAll('[data-copy-secret]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.getAttribute('data-copy-secret') || btn.getAttribute('data-target') || '';
      var el = targetId ? document.getElementById(targetId) : null;
      var secret = btn.getAttribute('data-secret') || hsSecretFromEl(el, btn) || btn._hsSecret || '';
      if (!secret) return;
      function done() { flashCopyButton(btn); }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(secret).then(done).catch(done);
      } else {
        done();
      }
    });
    if (!btn.getAttribute('data-default-html')) {
      btn.setAttribute('data-default-html', btn.innerHTML);
    }
  });

  document.querySelectorAll('[data-secret-reveal]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-target') || 'account-pass-value';
      var el = document.getElementById(id);
      if (!el) return;
      var showLabel = btn.getAttribute('data-label-show') || 'Show';
      var hideLabel = btn.getAttribute('data-label-hide') || 'Hide';
      var hidden = el.getAttribute('data-secret-hidden') === '1';
      var secret = btn._hsSecret || hsSecretFromEl(el, btn);

      if (!hidden && secret) {
        hsMaskSecret(el, secret);
        btn.textContent = showLabel;
        var copyBtn = btn.parentElement ? btn.parentElement.querySelector('[data-copy-secret="' + id + '"]') : null;
        if (copyBtn) copyBtn.disabled = true;
        return;
      }

      function showValue(value) {
        secret = value;
        btn._hsSecret = value;
        el.textContent = value;
        el.removeAttribute('data-secret-hidden');
        btn.textContent = hideLabel;
        var copyBtn = btn.parentElement ? btn.parentElement.querySelector('[data-copy-secret="' + id + '"]') : null;
        if (copyBtn) {
          copyBtn.disabled = false;
          copyBtn._hsSecret = value;
        }
      }

      if (secret) {
        showValue(secret);
        return;
      }

      if (btn.getAttribute('data-flash') === '1') {
        secret = hsSecretFromEl(el, btn);
        if (secret) {
          showValue(secret);
          return;
        }
      }

      hsRevealSecret(btn, showValue);
    });
  });

  var sshCopyAll = document.getElementById('ssh-copy-all');
  if (sshCopyAll) {
    sshCopyAll.addEventListener('click', function () {
      var passEl = document.getElementById('ssh-pass-value');
      var passBtn = document.querySelector('[data-copy-secret="ssh-pass-value"]');
      var pass = (passBtn && passBtn._hsSecret) || hsSecretFromEl(passEl, passBtn);
      var text = 'Host: ' + ((document.getElementById('ssh-host') || {}).textContent || '') + '\n'
        + 'Hostname: ' + ((document.getElementById('ssh-hostname') || {}).textContent || '') + '\n'
        + 'Port: ' + ((document.getElementById('ssh-port') || {}).textContent || '') + '\n'
        + 'User: ' + ((document.getElementById('ssh-user') || {}).textContent || '') + '\n'
        + 'Folder: ' + ((document.getElementById('ssh-folder') || {}).textContent || '') + '\n'
        + 'Password: ' + pass + '\n'
        + 'Command: ' + ((document.getElementById('ssh-cmd') || {}).textContent || '') + '\n'
        + 'Cd: ' + ((document.getElementById('ssh-cd-cmd') || {}).textContent || '');
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text.trim()).then(function () { flashCopyButton(sshCopyAll); }).catch(function () { flashCopyButton(sshCopyAll); });
      }
    });
    if (!sshCopyAll.getAttribute('data-default-html')) {
      sshCopyAll.setAttribute('data-default-html', sshCopyAll.innerHTML);
    }
  }

  var accountRoot = document.querySelector('[data-hs-account]');
  if (accountRoot) {
    accountRoot.querySelectorAll('[data-account-eye]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = btn.getAttribute('data-account-eye');
        var input = id ? document.getElementById(id) : null;
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        var icon = btn.querySelector('i');
        if (icon) {
          icon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        }
      });
    });

    var strengthInput = accountRoot.querySelector('[data-account-strength]');
    var strengthBar = accountRoot.querySelector('[data-account-strength-bar]');
    var strengthFill = accountRoot.querySelector('[data-account-strength-fill]');
    if (strengthInput && strengthBar && strengthFill) {
      strengthInput.addEventListener('input', function () {
        var v = strengthInput.value || '';
        if (!v) {
          strengthBar.hidden = true;
          return;
        }
        strengthBar.hidden = false;
        var score = 0;
        if (v.length >= 8) score++;
        if (v.length >= 12) score++;
        if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
        if (/\d/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        strengthBar.classList.remove('is-weak', 'is-mid', 'is-strong');
        if (score <= 2) strengthBar.classList.add('is-weak');
        else if (score <= 4) strengthBar.classList.add('is-mid');
        else strengthBar.classList.add('is-strong');
      });
    }

    var matchInput = accountRoot.querySelector('[data-account-match]');
    var newInput = accountRoot.querySelector('#account-new-pass');
    var matchHint = accountRoot.querySelector('[data-account-match-hint]');
    if (matchInput && newInput && matchHint) {
      function syncMatch() {
        var confirm = matchInput.value || '';
        if (!confirm) {
          matchHint.hidden = true;
          return;
        }
        matchHint.hidden = false;
        var ok = confirm === (newInput.value || '');
        matchHint.textContent = ok
          ? (matchHint.getAttribute('data-ok') || 'Passwords match')
          : (matchHint.getAttribute('data-bad') || 'Passwords do not match');
        matchHint.classList.toggle('is-ok', ok);
        matchHint.classList.toggle('is-bad', !ok);
      }
      matchInput.addEventListener('input', syncMatch);
      newInput.addEventListener('input', syncMatch);
    }
  }

  var ftpCopyAll = document.getElementById('ftp-copy-all');
  if (ftpCopyAll) {
    ftpCopyAll.addEventListener('click', function () {
      var ids = ['ftp-host', 'ftp-hostname', 'ftp-port', 'ftp-user', 'ftp-path', 'ftp-pass-value'];
      var lines = [];
      ids.forEach(function (id) {
        var el = document.getElementById(id);
        if (el && (el.textContent || el.value)) {
          lines.push((el.previousElementSibling && el.previousElementSibling.textContent) ? '' : '');
          lines.push(id.replace('ftp-', '') + ': ' + (el.textContent || el.value));
        }
      });
      var passEl = document.getElementById('ftp-pass-value');
      var passBtn = document.querySelector('[data-copy-secret="ftp-pass-value"]');
      var pass = (passBtn && passBtn._hsSecret) || hsSecretFromEl(passEl, passBtn);
      var text = 'Host: ' + (document.getElementById('ftp-host') || {}).textContent + '\n'
        + 'Hostname: ' + (document.getElementById('ftp-hostname') || {}).textContent + '\n'
        + 'Port: ' + (document.getElementById('ftp-port') || {}).textContent + '\n'
        + 'User: ' + (document.getElementById('ftp-user') || {}).textContent + '\n'
        + 'Path: ' + (document.getElementById('ftp-path') || {}).textContent + '\n'
        + 'Password: ' + (pass || (document.getElementById('ftp-pass-value') || {}).textContent);
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text.trim()).then(function () { flashCopyButton(ftpCopyAll); }).catch(function () { flashCopyButton(ftpCopyAll); });
      }
    });
    if (!ftpCopyAll.getAttribute('data-default-html')) {
      ftpCopyAll.setAttribute('data-default-html', ftpCopyAll.innerHTML);
    }
  }

  document.querySelectorAll('[data-perm-preset]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var mode = btn.getAttribute('data-perm-preset') || '';
      var input = document.getElementById('chmod-mode-input');
      if (input) input.value = mode;
      document.querySelectorAll('[data-perm-preset]').forEach(function (b) {
        b.classList.toggle('is-active', b === btn);
      });
    });
  });

  /* ——— Site migrate tab (websites → Migrate) ——— */
  (function initMigrateTab() {
    var form = document.querySelector('[data-hs-migrate-form]');
    if (!form) return;
    var method = form.querySelector('[data-migrate-method]');
    var urlWrap = form.querySelector('[data-migrate-url-wrap]');
    var urlInput = form.querySelector('[data-migrate-url]');
    var ftpWrap = form.querySelector('[data-migrate-ftp]');

    function syncMethod() {
      var m = method ? method.value : 'website';
      var isFtp = m === 'ftp';
      if (urlWrap) urlWrap.hidden = isFtp;
      if (ftpWrap) ftpWrap.hidden = !isFtp;
      if (urlInput) urlInput.required = !isFtp;
    }

    if (method) {
      method.addEventListener('change', syncMethod);
      syncMethod();
    }
  })();

  /* ——— Site copy tab (websites → Copy) ——— */
  (function initSiteCopyTab() {
    var form = document.querySelector('[data-hs-site-copy]');
    if (!form || !window.HS_SITE_COPY_META) return;
    var meta = window.HS_SITE_COPY_META;
    var fromSel = form.querySelector('[data-copy-from]');
    var toInp = form.querySelector('[data-copy-to]');
    var srcPath = form.querySelector('[data-copy-src-path]');
    var destPrefix = form.querySelector('[data-copy-dest-prefix]');
    var destFull = form.querySelector('[data-copy-dest-full]');

    function slugPreview() {
      var raw = (toInp && toInp.value) ? toInp.value.toLowerCase().replace(/[^a-z0-9-]/g, '') : 'my-copy';
      return raw || 'my-copy';
    }

    function syncPreview() {
      var slug = (fromSel && fromSel.value) || '';
      var row = meta[slug] || {};
      if (srcPath) srcPath.textContent = row.src || '';
      if (destPrefix) destPrefix.textContent = row.prefix || '';
      if (destFull) destFull.textContent = (row.prefix || '') + slugPreview() + '/';
    }

    if (fromSel) fromSel.addEventListener('change', syncPreview);
    if (toInp) {
      toInp.addEventListener('input', syncPreview);
      if (!toInp.value) toInp.value = '';
    }
    syncPreview();
  })();

  /* ——— Public site header: full-screen mobile menu (burger ↔ close) ——— */
  (function () {
    var header = document.querySelector('[data-hs-public-header]');
    if (!header) return;
    var burger = header.querySelector('[data-hs-public-burger]');
    var backdrop = header.querySelector('[data-hs-public-backdrop]');
    var nav = header.querySelector('[data-hs-public-nav]');

    function setNavOpen(open) {
      open = !!open;
      header.classList.toggle('is-nav-open', open);
      document.body.classList.toggle('hs-nav-open', open);
      if (burger) {
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        var openLabel = burger.getAttribute('data-label-open') || 'Menu';
        var closeLabel = burger.getAttribute('data-label-close') || 'Close';
        burger.setAttribute('aria-label', open ? closeLabel : openLabel);
      }
      if (backdrop) {
        if (open) {
          backdrop.hidden = false;
          backdrop.removeAttribute('hidden');
        } else {
          backdrop.hidden = true;
          backdrop.setAttribute('hidden', '');
        }
      }
      document.body.style.overflow = open ? 'hidden' : '';
    }

    if (burger) {
      burger.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setNavOpen(!header.classList.contains('is-nav-open'));
      });
    }
    if (backdrop) {
      backdrop.addEventListener('click', function () {
        setNavOpen(false);
      });
    }
    if (nav) {
      nav.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
          if (window.innerWidth < 961) setNavOpen(false);
        });
      });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && header.classList.contains('is-nav-open')) {
        setNavOpen(false);
        if (burger) burger.focus();
      }
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth >= 961) setNavOpen(false);
    });

    function onScroll() {
      if (!header.classList.contains('is-nav-open')) {
        header.classList.toggle('is-scrolled', window.scrollY > 8);
      }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    var hash = window.location.hash.replace('#', '');
    if (hash && nav) {
      nav.querySelectorAll('a[href*="#' + hash + '"]').forEach(function (a) {
        a.classList.add('is-active');
      });
    }
  })();

  /* ——— Homepage screenshot lightbox (hero, panel, landing builder) ——— */
  (function () {
    var triggers = document.querySelectorAll('[data-hs-shot-zoom]');
    if (!triggers.length) return;

    var lightbox = document.createElement('div');
    lightbox.className = 'hs-shot-lightbox';
    lightbox.hidden = true;
    lightbox.innerHTML = '<button type="button" class="hs-shot-lightbox-backdrop" data-hs-shot-close aria-label="Close"></button>'
      + '<figure class="hs-shot-lightbox-panel">'
      + '<button type="button" class="hs-shot-lightbox-close" data-hs-shot-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>'
      + '<img src="" alt="" data-hs-shot-lightbox-img>'
      + '</figure>';
    document.body.appendChild(lightbox);

    var lbImg = lightbox.querySelector('[data-hs-shot-lightbox-img]');
    var lastFocus = null;

    function shotSrc(img) {
      if (!img) return '';
      var pic = img.closest('picture');
      if (pic) {
        var src = pic.querySelector('source[type="image/webp"]');
        if (src && src.srcset) return src.srcset.split(',')[0].trim().split(/\s+/)[0];
      }
      return img.currentSrc || img.src || '';
    }

    function openShot(trigger) {
      var img = trigger.querySelector('img');
      if (!img || !lbImg) return;
      lastFocus = document.activeElement;
      lbImg.src = shotSrc(img);
      lbImg.alt = img.alt || '';
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
      var closeBtn = lightbox.querySelector('.hs-shot-lightbox-close');
      if (closeBtn) closeBtn.focus();
    }

    function closeShot() {
      lightbox.hidden = true;
      document.body.style.overflow = '';
      if (lbImg) {
        lbImg.removeAttribute('src');
        lbImg.alt = '';
      }
      if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
    }

    triggers.forEach(function (trigger) {
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openShot(trigger);
      });
    });
    lightbox.querySelectorAll('[data-hs-shot-close]').forEach(function (el) {
      el.addEventListener('click', closeShot);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !lightbox.hidden) closeShot();
    });
  })();
})();