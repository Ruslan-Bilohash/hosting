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

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!sldInput || !checkUrl) return;

    var sld = (sldInput.value || '').trim();
    if (!sld) return;

    var tlds = selectedTlds();
    if (tlds.length === 0) {
      renderResults('<p class="hs-dom-results-err">' + esc(msgNoTlds) + '</p>');
      return;
    }

    if (btn) {
      btn.disabled = true;
      btn.textContent = msgChecking;
    }
    renderResults('<p class="hp-muted">' + esc(msgChecking) + '</p>');

    var url = checkUrl + (checkUrl.indexOf('?') >= 0 ? '&' : '?')
      + 'sld=' + encodeURIComponent(sld)
      + '&tlds=' + encodeURIComponent(tlds.join(','));

    fetch(url, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) {
          var err = data && (data.error === 'invalid_sld' || data.error === 'invalid') ? msgInvalid : msgError;
          if (data && data.error === 'no_tlds') err = msgNoTlds;
          renderResults('<p class="hs-dom-results-err">' + esc(err) + '</p>');
          return;
        }

        var rows = data.results || [];
        if (rows.length === 0) {
          renderResults('<p class="hs-dom-results-err">' + esc(msgError) + '</p>');
          return;
        }

        var html = '<div class="hs-table-wrap"><table class="hs-table hs-dom-results-table"><thead><tr>'
          + '<th>' + esc(colDomain) + '</th><th>' + esc(colStatus) + '</th><th>' + esc(colPrice) + '</th><th></th></tr></thead><tbody>';
        rows.forEach(function (row) {
          var status = row.available
            ? '<span class="hs-domain-ok">' + esc(msgAvailable) + '</span>'
            : '<span class="hs-domain-taken">' + esc(msgTaken) + '</span>';
          var price = row.price_label ? esc(row.price_label) : '—';
          var regSep = registerBase.indexOf('?') >= 0 ? '&' : '?';
          var action = row.available
            ? '<a href="' + esc(registerBase) + regSep + 'domain=' + encodeURIComponent(row.domain) + '" class="hs-btn hs-btn-ghost hs-btn-sm">'
              + esc(msgCta) + '</a>'
            : '<span class="hp-muted">—</span>';
          html += '<tr><td><code>' + esc(row.domain) + '</code></td><td>' + status + '</td><td>' + price + '</td><td>' + action + '</td></tr>';
        });
        html += '</tbody></table></div>';
        renderResults(html);
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