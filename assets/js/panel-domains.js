(function () {
  'use strict';

  var form = document.querySelector('[data-hs-panel-domain-search]');
  if (!form) return;

  var sldInput = form.querySelector('[data-hs-domain-sld]');
  var btn = form.querySelector('[data-hs-domain-btn]');
  var resultsEl = form.querySelector('[data-hs-domain-results]');
  var tldAll = form.querySelector('[data-hs-tld-all]');
  var tldChecks = form.querySelectorAll('[data-hs-tld-chk]');
  var checkUrl = form.getAttribute('data-check-url') || '';
  var msgAvailable = form.getAttribute('data-msg-available') || 'Available';
  var msgTaken = form.getAttribute('data-msg-taken') || 'Taken';
  var msgInvalid = form.getAttribute('data-msg-invalid') || 'Invalid name';
  var msgError = form.getAttribute('data-msg-error') || 'Lookup failed';
  var msgChecking = form.getAttribute('data-msg-checking') || 'Checking…';
  var msgCta = form.getAttribute('data-msg-cta') || 'Register';
  var msgNoTlds = form.getAttribute('data-msg-no-tlds') || 'Select at least one zone';
  var registerBase = form.getAttribute('data-register-base') || 'register.php';
  var colDomain = form.getAttribute('data-col-domain') || 'Domain';
  var colStatus = form.getAttribute('data-col-status') || 'Status';
  var colPrice = form.getAttribute('data-col-price') || 'Price';
  var btnLabel = btn ? (btn.getAttribute('data-label') || btn.textContent || '') : '';

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function selectedTlds() {
    var out = [];
    tldChecks.forEach(function (cb) {
      if (cb.checked) out.push(cb.value);
    });
    return out;
  }

  function ensureSomeTlds() {
    var tlds = selectedTlds();
    if (tlds.length > 0) return tlds;
    // Auto-select defaults when none checked (never block a valid search).
    var defaults = ['com', 'shop', 'eu', 'lt', 'pl', 'net', 'org', 'de'];
    var picked = [];
    tldChecks.forEach(function (cb) {
      if (defaults.indexOf(cb.value) >= 0) {
        cb.checked = true;
        picked.push(cb.value);
      }
    });
    if (picked.length === 0) {
      tldChecks.forEach(function (cb, i) {
        if (i < 6) {
          cb.checked = true;
          picked.push(cb.value);
        }
      });
    }
    syncTldAll();
    return picked;
  }

  function syncTldAll() {
    if (!tldAll) return;
    var all = true;
    var any = false;
    tldChecks.forEach(function (cb) {
      if (cb.checked) any = true;
      else all = false;
    });
    tldAll.checked = all && any;
    tldAll.indeterminate = any && !all;
  }

  /** Parse "hosting.shop" / "www.hosting.co.uk" into sld + tld when possible. */
  function parseQuery(raw) {
    var q = String(raw || '').trim().toLowerCase();
    q = q.replace(/^https?:\/\//, '').replace(/\/.*$/, '').replace(/^www\./, '');
    if (!q) return null;
    if (q.indexOf('.') < 0) {
      return { mode: 'sld', sld: q, domain: '' };
    }
    // multi-part TLDs first
    var multi = ['co.uk', 'org.uk', 'ac.uk', 'gov.uk', 'com.au', 'net.au'];
    var i;
    for (i = 0; i < multi.length; i++) {
      var mt = multi[i];
      if (q.length > mt.length + 1 && q.slice(-(mt.length + 1)) === '.' + mt) {
        var sldM = q.slice(0, -(mt.length + 1));
        if (sldM && sldM.indexOf('.') < 0) {
          return { mode: 'full', sld: sldM, tld: mt, domain: q };
        }
      }
    }
    var parts = q.split('.');
    if (parts.length >= 2) {
      var tld = parts[parts.length - 1];
      var sld = parts[parts.length - 2];
      // e.g. hosting.shop
      if (parts.length === 2) {
        return { mode: 'full', sld: sld, tld: tld, domain: q };
      }
      // subdomain.example.com → check example.com as full domain
      return { mode: 'full', sld: parts[parts.length - 2], tld: tld, domain: q };
    }
    return { mode: 'sld', sld: q, domain: '' };
  }

  function checkTldChip(tld) {
    if (!tld) return;
    tldChecks.forEach(function (cb) {
      if (cb.value === tld) cb.checked = true;
    });
    syncTldAll();
  }

  if (tldAll) {
    tldAll.addEventListener('change', function () {
      tldChecks.forEach(function (cb) {
        cb.checked = tldAll.checked;
      });
      tldAll.indeterminate = false;
    });
  }
  tldChecks.forEach(function (cb) {
    cb.addEventListener('change', syncTldAll);
  });
  syncTldAll();

  function renderResults(html) {
    if (resultsEl) resultsEl.innerHTML = html;
  }

  function renderTable(rows) {
    if (!rows || rows.length === 0) {
      renderResults('<p class="hs-dom-results-err">' + esc(msgError) + '</p>');
      return;
    }
    var html = '<div class="hs-table-wrap"><table class="hs-table hs-dom-results-table"><thead><tr>'
      + '<th>' + esc(colDomain) + '</th><th>' + esc(colStatus) + '</th><th>' + esc(colPrice) + '</th><th></th></tr></thead><tbody>';
    rows.forEach(function (row) {
      var premium = !!row.premium;
      var status = row.available
        ? '<span class="hs-domain-ok">' + esc(msgAvailable) + '</span>'
          + (premium ? ' <span class="hs-domain-premium-badge">Premium</span>' : '')
        : '<span class="hs-domain-taken">' + esc(msgTaken) + '</span>';
      var price = row.price_label ? esc(row.price_label) : '—';
      var regSep = registerBase.indexOf('?') >= 0 ? '&' : '?';
      var action = row.available
        ? '<a href="' + esc(registerBase) + regSep + 'domain=' + encodeURIComponent(row.domain) + '" class="hs-btn hs-btn-ghost hs-btn-sm">'
          + esc(msgCta) + '</a>'
        : '<span class="hp-muted">—</span>';
      html += '<tr' + (premium ? ' class="is-premium"' : '') + '><td><code>' + esc(row.domain) + '</code></td><td>' + status + '</td><td><strong>' + price + '</strong></td><td>' + action + '</td></tr>';
    });
    html += '</tbody></table></div>';
    renderResults(html);
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!sldInput || !checkUrl) return;

    var raw = (sldInput.value || '').trim();
    if (!raw) return;

    var parsed = parseQuery(raw);
    if (!parsed) {
      renderResults('<p class="hs-dom-results-err">' + esc(msgInvalid) + '</p>');
      return;
    }

    var url;
    if (parsed.mode === 'full' && parsed.domain) {
      // Full FQDN: check that domain directly — no zone selection required.
      checkTldChip(parsed.tld);
      url = checkUrl + (checkUrl.indexOf('?') >= 0 ? '&' : '?')
        + 'domain=' + encodeURIComponent(parsed.domain);
    } else {
      var tlds = ensureSomeTlds();
      if (tlds.length === 0) {
        renderResults('<p class="hs-dom-results-err">' + esc(msgNoTlds) + '</p>');
        return;
      }
      url = checkUrl + (checkUrl.indexOf('?') >= 0 ? '&' : '?')
        + 'sld=' + encodeURIComponent(parsed.sld)
        + '&tlds=' + encodeURIComponent(tlds.join(','));
    }

    if (btn) {
      btn.disabled = true;
      btn.textContent = msgChecking;
    }
    renderResults('<p class="hp-muted">' + esc(msgChecking) + '</p>');

    fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) {
          var err = data && (data.error === 'invalid_sld' || data.error === 'invalid') ? msgInvalid : msgError;
          if (data && data.error === 'no_tlds') err = msgNoTlds;
          renderResults('<p class="hs-dom-results-err">' + esc(err) + '</p>');
          return;
        }

        // Single-domain check response
        if (data.domain && !Array.isArray(data.results)) {
          renderTable([{
            domain: data.domain,
            available: !!data.available,
            price_label: data.price_label || '',
            price: data.price || 0,
            premium: !!data.premium
          }]);
          return;
        }

        renderTable(data.results || []);
      })
      .catch(function () {
        renderResults('<p class="hs-dom-results-err">' + esc(msgError) + '</p>');
      })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
          btn.textContent = btnLabel;
        }
      });
  });
})();
