(function () {
  'use strict';

  var cfg = window.HS_PERF_PANEL;
  if (!cfg || !cfg.api) return;

  var root = document.querySelector('[data-hs-perf-panel]');
  if (!root) return;

  var findingsTargets = document.querySelectorAll('[data-hs-perf-findings]');
  var adviceTargets = document.querySelectorAll('[data-hs-perf-advice]');
  var statusTargets = document.querySelectorAll('[data-hs-perf-scan-status]');
  var running = false;

  function setStatus(msg) {
    statusTargets.forEach(function (el) {
      el.textContent = msg || '';
      el.hidden = !msg;
    });
  }

  function runScan() {
    if (running) return;
    running = true;
    setStatus(cfg.i18n.scanning || 'Scanning…');
    document.body.classList.add('hs-perf-scanning');

    fetch(cfg.api, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ csrf: cfg.csrf }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || 'fail');
        if (data.findings_html) {
          findingsTargets.forEach(function (el) {
            el.innerHTML = data.findings_html;
          });
        }
        if (data.advice_html) {
          adviceTargets.forEach(function (el) {
            el.innerHTML = data.advice_html;
          });
        }
        setStatus(cfg.i18n.scan_done || 'Done');
        setTimeout(function () { setStatus(''); }, 2500);
      })
      .catch(function () {
        setStatus(cfg.i18n.scan_error || 'Scan failed');
      })
      .finally(function () {
        running = false;
        document.body.classList.remove('hs-perf-scanning');
      });
  }

  document.querySelectorAll('[data-hs-perf-scan-run]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (btn.tagName === 'BUTTON' && btn.type === 'submit') {
        e.preventDefault();
      }
      runScan();
    });
  });

  if (cfg.auto_scan) {
    setTimeout(runScan, 400);
  }
})();