(function () {
  'use strict';

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

  /* ——— Language dropdown ——— */
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

  /* ——— Homepage domain search (real RDAP/WHOIS via domain-check.php) ——— */
  var domainSearchForm = document.querySelector('[data-hs-domain-search]');
  if (domainSearchForm) {
    var domainInput = domainSearchForm.querySelector('[data-hs-domain-input]');
    var domainBtn = domainSearchForm.querySelector('[data-hs-domain-btn]');
    var domainResult = document.querySelector('[data-hs-domain-result]');
    var checkUrl = domainSearchForm.getAttribute('data-check-url') || '';
    var msgAvailable = domainSearchForm.getAttribute('data-msg-available') || 'Available';
    var msgTaken = domainSearchForm.getAttribute('data-msg-taken') || 'Taken';
    var msgInvalid = domainSearchForm.getAttribute('data-msg-invalid') || 'Invalid domain';
    var msgError = domainSearchForm.getAttribute('data-msg-error') || 'Lookup failed';
    var msgChecking = domainSearchForm.getAttribute('data-msg-checking') || 'Checking…';
    var msgCta = domainSearchForm.getAttribute('data-msg-cta') || 'Register';
    var registerBase = domainSearchForm.getAttribute('data-register-base') || 'register.php';

    function renderDomainResult(html) {
      if (domainResult) domainResult.innerHTML = html;
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
      renderDomainResult('<span class="hp-muted">' + msgChecking + '</span>');
      var url = checkUrl + (checkUrl.indexOf('?') >= 0 ? '&' : '?') + 'domain=' + encodeURIComponent(q);
      fetch(url, { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || !data.ok) {
            var err = data && data.error === 'invalid' ? msgInvalid : msgError;
            renderDomainResult('<span style="color:#dc2626">' + err + '</span>');
            return;
          }
          var html = '<div><strong>' + data.domain + '</strong></div>';
          if (data.available) {
            html += '<span style="color:#059669;font-weight:600">' + msgAvailable + '</span>';
            if (data.price_label) html += ' — ' + data.price_label;
            html += '<div style="margin-top:.5rem"><a href="' + registerBase + '?domain=' + encodeURIComponent(data.domain) + '" class="hs-btn hs-btn-ghost" style="width:100%">' + msgCta + '</a></div>';
          } else {
            html += '<span style="color:#dc2626">' + msgTaken + '</span>';
          }
          renderDomainResult(html);
        })
        .catch(function () {
          renderDomainResult('<span style="color:#dc2626">' + msgError + '</span>');
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

  document.querySelectorAll('[data-copy-secret]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var secret = btn.getAttribute('data-secret') || '';
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

  document.querySelectorAll('[data-ssh-pass-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-target') || 'ssh-pass-value';
      var el = document.getElementById(id);
      if (!el) return;
      var secret = btn.getAttribute('data-secret') || el.textContent || '';
      var showLabel = btn.getAttribute('data-label-show') || 'Show';
      var hideLabel = btn.getAttribute('data-label-hide') || 'Hide';
      var hidden = el.getAttribute('data-ssh-hidden') === '1';
      if (hidden) {
        el.textContent = secret;
        el.removeAttribute('data-ssh-hidden');
        btn.textContent = hideLabel;
      } else {
        el.textContent = '•'.repeat(Math.min(16, Math.max(8, secret.length)));
        el.setAttribute('data-ssh-hidden', '1');
        btn.textContent = showLabel;
      }
    });
  });

  document.querySelectorAll('[data-ftp-pass-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-target') || 'ftp-pass-value';
      var el = document.getElementById(id);
      if (!el) return;
      var secret = btn.getAttribute('data-secret') || el.textContent || '';
      var showLabel = btn.getAttribute('data-label-show') || 'Show';
      var hideLabel = btn.getAttribute('data-label-hide') || 'Hide';
      var hidden = el.getAttribute('data-ftp-hidden') === '1';
      if (hidden) {
        el.textContent = secret;
        el.removeAttribute('data-ftp-hidden');
        btn.textContent = hideLabel;
      } else {
        el.textContent = '•'.repeat(Math.min(16, Math.max(8, secret.length)));
        el.setAttribute('data-ftp-hidden', '1');
        btn.textContent = showLabel;
      }
    });
  });

  var sshCopyAll = document.getElementById('ssh-copy-all');
  if (sshCopyAll) {
    sshCopyAll.addEventListener('click', function () {
      var passBtn = document.querySelector('[data-copy-secret="ssh-pass-value"]');
      var pass = passBtn ? passBtn.getAttribute('data-secret') : '';
      if (!pass) {
        var passEl = document.getElementById('ssh-pass-value');
        pass = passEl ? passEl.textContent : '';
      }
      var text = 'Host: ' + ((document.getElementById('ssh-host') || {}).textContent || '') + '\n'
        + 'Port: ' + ((document.getElementById('ssh-port') || {}).textContent || '') + '\n'
        + 'User: ' + ((document.getElementById('ssh-user') || {}).textContent || '') + '\n'
        + 'Password: ' + pass + '\n'
        + 'Command: ' + ((document.getElementById('ssh-cmd') || {}).textContent || '');
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

    var passToggle = accountRoot.querySelector('[data-account-pass-toggle]');
    if (passToggle) {
      passToggle.addEventListener('click', function () {
        var el = document.getElementById('account-pass-value');
        if (!el) return;
        var secret = passToggle.getAttribute('data-secret') || el.textContent || '';
        var showLabel = passToggle.getAttribute('data-label-show') || 'Show';
        var hideLabel = passToggle.getAttribute('data-label-hide') || 'Hide';
        var hidden = el.getAttribute('data-account-hidden') === '1';
        if (hidden) {
          el.textContent = secret;
          el.removeAttribute('data-account-hidden');
          passToggle.textContent = hideLabel;
        } else {
          el.textContent = '\u2022'.repeat(Math.min(16, Math.max(8, secret.length)));
          el.setAttribute('data-account-hidden', '1');
          passToggle.textContent = showLabel;
        }
      });
    }

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
      var passBtn = document.querySelector('[data-copy-secret][data-target="ftp-pass-value"], [data-copy-secret="ftp-pass-value"]');
      var pass = passBtn ? passBtn.getAttribute('data-secret') : '';
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
})();