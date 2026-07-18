(function () {
  'use strict';
  var cfg = window.HS_COOKIE_CONSENT || {};
  var KEY = cfg.storageKey || 'hs_cookie_consent';
  var TTL_MS = 365 * 24 * 60 * 60 * 1000;
  var strings = cfg.strings || {};
  var root = document.getElementById('hs-cookie-consent');
  var mgr = document.querySelector('[data-hs-cookie-manager]');

  function read() {
    try {
      var raw = localStorage.getItem(KEY);
      if (!raw) return null;
      var data = JSON.parse(raw);
      if (!data || !data.ts || Date.now() - data.ts > TTL_MS) return null;
      return data;
    } catch (e) {
      return null;
    }
  }

  function write(choice, opts) {
    opts = opts || {};
    try {
      localStorage.setItem(KEY, JSON.stringify({ choice: choice, ts: Date.now(), v: 2 }));
    } catch (e) { /* ignore */ }
    if (root && !opts.keepBanner) {
      root.hidden = true;
      root.setAttribute('aria-hidden', 'true');
    }
    if (typeof window.dispatchEvent === 'function') {
      try {
        window.dispatchEvent(new CustomEvent('hs-cookie-consent-change', { detail: { choice: choice } }));
      } catch (e2) { /* ignore */ }
    }
    refreshManagerUI();
  }

  function clearConsent() {
    try {
      localStorage.removeItem(KEY);
    } catch (e) { /* ignore */ }
  }

  function expireCookie(name) {
    var paths = ['/', location.pathname || '/'];
    var domains = [''];
    var host = location.hostname || '';
    if (host) {
      domains.push(host);
      if (host.indexOf('.') !== -1) {
        domains.push('.' + host.replace(/^www\./, ''));
      }
    }
    paths.forEach(function (p) {
      domains.forEach(function (d) {
        var base = encodeURIComponent(name) + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=' + p + '; SameSite=Lax';
        if (location.protocol === 'https:') base += '; Secure';
        if (d) {
          document.cookie = base + '; domain=' + d;
        } else {
          document.cookie = base;
        }
      });
    });
  }

  function deleteReadableCookies() {
    var raw = document.cookie || '';
    if (!raw) return 0;
    var names = raw.split(';').map(function (p) {
      return p.split('=')[0].trim();
    }).filter(Boolean);
    // Prefer site-related names; still attempt all readable (non-HttpOnly) cookies
    names.forEach(function (name) {
      expireCookie(name);
    });
    return names.length;
  }

  function deleteHsStorage() {
    var n = 0;
    function wipe(store) {
      if (!store) return;
      var keys = [];
      try {
        for (var i = 0; i < store.length; i++) {
          keys.push(store.key(i));
        }
      } catch (e) {
        return;
      }
      keys.forEach(function (k) {
        if (!k) return;
        // Keep nothing for consent reset — remove hs_* and consent key
        if (k === KEY || k.indexOf('hs_') === 0 || k.indexOf('HS_') === 0) {
          try {
            store.removeItem(k);
            n++;
          } catch (e2) { /* ignore */ }
        }
      });
    }
    wipe(window.localStorage);
    wipe(window.sessionStorage);
    return n;
  }

  function listStorage() {
    var rows = [];
    function add(kind, k, v) {
      var val = String(v == null ? '' : v);
      if (val.length > 80) val = val.slice(0, 77) + '…';
      rows.push({ kind: kind, key: k, value: val });
    }
    try {
      (document.cookie || '').split(';').forEach(function (part) {
        var p = part.trim();
        if (!p) return;
        var i = p.indexOf('=');
        var k = i === -1 ? p : p.slice(0, i);
        var v = i === -1 ? '' : decodeURIComponent(p.slice(i + 1));
        add(strings.mgr_list_cookie || 'Cookie', k, v);
      });
    } catch (e) { /* ignore */ }
    try {
      for (var i = 0; i < localStorage.length; i++) {
        var lk = localStorage.key(i);
        if (lk) add(strings.mgr_list_ls || 'localStorage', lk, localStorage.getItem(lk));
      }
    } catch (e2) { /* ignore */ }
    try {
      for (var j = 0; j < sessionStorage.length; j++) {
        var sk = sessionStorage.key(j);
        if (sk) add(strings.mgr_list_ss || 'sessionStorage', sk, sessionStorage.getItem(sk));
      }
    } catch (e3) { /* ignore */ }
    return rows;
  }

  function statusLabel(data) {
    if (!data || !data.choice) return strings.mgr_status_none || 'Not chosen yet';
    if (data.choice === 'all') return strings.mgr_status_all || 'All';
    return strings.mgr_status_essential || 'Essential only';
  }

  function showMsg(text, ok) {
    if (!mgr) return;
    var el = mgr.querySelector('[data-hs-cc-msg]');
    if (!el) return;
    el.hidden = !text;
    el.textContent = text || '';
    el.classList.toggle('is-ok', !!ok);
    el.classList.toggle('is-err', !ok && !!text);
  }

  function refreshManagerUI() {
    if (!mgr) return;
    var data = read();
    var statusEl = mgr.querySelector('[data-hs-cc-status]');
    if (statusEl) statusEl.textContent = statusLabel(data);
    var func = mgr.querySelector('[data-hs-cc-functional]');
    if (func) func.checked = !!(data && data.choice === 'all');
    var tbody = mgr.querySelector('[data-hs-cc-store-list]');
    if (tbody) {
      var rows = listStorage();
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="3">' + escapeHtml(strings.mgr_list_empty || 'Empty') + '</td></tr>';
      } else {
        tbody.innerHTML = rows.map(function (r) {
          return '<tr><td>' + escapeHtml(r.kind) + '</td><td><code>' + escapeHtml(r.key) + '</code></td><td><code>' + escapeHtml(r.value) + '</code></td></tr>';
        }).join('');
      }
    }
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function showBanner() {
    if (!root) return;
    root.hidden = false;
    root.setAttribute('aria-hidden', 'false');
  }

  function hideBanner() {
    if (!root) return;
    root.hidden = true;
    root.setAttribute('aria-hidden', 'true');
  }

  // Banner on first visit
  if (root) {
    if (!read()) {
      showBanner();
    }
    root.querySelector('[data-hs-cc-accept-all]')?.addEventListener('click', function () {
      write('all');
    });
    root.querySelector('[data-hs-cc-reject]')?.addEventListener('click', function () {
      write('essential');
    });
    root.querySelector('[data-hs-cc-settings]')?.addEventListener('click', function () {
      if (mgr) {
        mgr.scrollIntoView({ behavior: 'smooth', block: 'start' });
        hideBanner();
      } else if (cfg.cookiesUrl) {
        window.location.href = cfg.cookiesUrl;
      }
    });
  }

  // Manager on cookies.php
  if (mgr) {
    refreshManagerUI();
    mgr.querySelector('[data-hs-cc-mgr-accept-all]')?.addEventListener('click', function () {
      write('all');
      showMsg(strings.mgr_save_ok || 'Saved.', true);
    });
    mgr.querySelector('[data-hs-cc-mgr-essential]')?.addEventListener('click', function () {
      write('essential');
      showMsg(strings.mgr_save_ok || 'Saved.', true);
    });
    mgr.querySelector('[data-hs-cc-mgr-save]')?.addEventListener('click', function () {
      var func = mgr.querySelector('[data-hs-cc-functional]');
      write(func && func.checked ? 'all' : 'essential');
      showMsg(strings.mgr_save_ok || 'Saved.', true);
    });
    mgr.querySelector('[data-hs-cc-mgr-refresh]')?.addEventListener('click', function () {
      refreshManagerUI();
      showMsg('', true);
    });
    mgr.querySelector('[data-hs-cc-mgr-delete]')?.addEventListener('click', function () {
      deleteReadableCookies();
      deleteHsStorage();
      clearConsent();
      // Ensure consent key gone even if wipe skipped
      try { localStorage.removeItem(KEY); } catch (e) { /* ignore */ }
      refreshManagerUI();
      showBanner();
      showMsg(strings.mgr_delete_ok || 'Deleted.', true);
    });
  }
})();
