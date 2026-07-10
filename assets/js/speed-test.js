(function () {
  'use strict';

  var root = document.querySelector('[data-hs-speed-lab]');
  if (!root || !window.HS_SPEED_LAB) return;

  var cfg = window.HS_SPEED_LAB;
  var MSG = cfg.i18n || {};
  var urlInput = document.getElementById('hs-speed-url');
  var runBtn = root.querySelector('[data-hs-speed-run]');
  var stepsEl = root.querySelector('[data-hs-speed-steps]');
  var resultsEl = root.querySelector('[data-hs-speed-results]');
  var metricsEl = root.querySelector('[data-hs-speed-metrics]');
  var waterfallEl = root.querySelector('[data-hs-speed-waterfall]');
  var barsEl = root.querySelector('[data-hs-speed-bars]');
  var tipsList = root.querySelector('[data-hs-speed-tips-list]');
  var metaEl = root.querySelector('[data-hs-speed-meta]');
  var running = false;

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function animateScore(el, target, duration) {
    var start = 0;
    var t0 = null;
    function frame(ts) {
      if (!t0) t0 = ts;
      var p = Math.min(1, (ts - t0) / duration);
      var val = Math.round(start + (target - start) * p);
      el.textContent = String(val);
      if (p < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  function setRing(gauge, score, color) {
    var ring = gauge.querySelector('[data-ring-fill]');
    if (!ring) return;
    var r = 52;
    var c = 2 * Math.PI * r;
    ring.style.stroke = color || '#059669';
    ring.style.strokeDasharray = c;
    ring.style.strokeDashoffset = c - (Math.max(0, Math.min(100, score)) / 100) * c;
  }

  function renderGauges(report) {
    ['desktop', 'mobile'].forEach(function (kind) {
      var gauge = root.querySelector('[data-hs-speed-gauge="' + kind + '"]');
      if (!gauge || !report[kind]) return;
      var data = report[kind];
      var scoreEl = gauge.querySelector('[data-score]');
      var gradeEl = gauge.querySelector('[data-grade]');
      var labelEl = gauge.querySelector('[data-grade-label]');
      if (scoreEl) animateScore(scoreEl, data.score || 0, 900);
      if (gradeEl) gradeEl.textContent = data.letter || '';
      if (labelEl) labelEl.textContent = data.label || '';
      gauge.className = 'hs-speed-gauge hs-speed-tier-' + (data.tier || 'fair');
      setRing(gauge, data.score || 0, data.color);
    });
  }

  function metricCard(icon, label, value, sub) {
    return '<article class="hs-speed-metric"><span class="hs-speed-metric-icon"><i class="fa-solid ' + icon + '"></i></span>'
      + '<div><span class="hs-speed-metric-label">' + esc(label) + '</span>'
      + '<strong class="hs-speed-metric-value">' + esc(value) + '</strong>'
      + (sub ? '<span class="hs-speed-metric-sub">' + esc(sub) + '</span>' : '')
      + '</div></article>';
  }

  function renderMetrics(report) {
    if (!metricsEl || !report.metrics) return;
    var m = report.metrics;
    metricsEl.innerHTML = metricCard('fa-bolt', MSG.ttfb || 'TTFB', Math.round(m.ttfb_ms) + ' ' + (MSG.ms || 'ms'), '')
      + metricCard('fa-clock', MSG.load || 'Load', Math.round(m.load_ms) + ' ' + (MSG.ms || 'ms'), (report.runs || 3) + ' runs avg')
      + metricCard('fa-weight-hanging', MSG.size || 'Size', m.size_kb + ' ' + (MSG.kb || 'KB'), 'HTTP ' + m.http_code)
      + metricCard('fa-network-wired', MSG.http || 'Protocol', m.http_version || '—', '')
      + metricCard('fa-compress', MSG.compression || 'Compression', (m.compression || 'none').toUpperCase(), '')
      + metricCard('fa-signal', MSG.dns || 'DNS', Math.round(m.dns_ms) + ' ' + (MSG.ms || 'ms'), MSG.connect + ' ' + Math.round(m.connect_ms));
  }

  function renderWaterfall(report) {
    if (!waterfallEl || !barsEl || !report.metrics) return;
    var m = report.metrics;
    var max = Math.max(m.load_ms || 1, 1);
    var parts = [
      { key: 'dns', label: MSG.dns || 'DNS', ms: m.dns_ms, cls: 'dns' },
      { key: 'connect', label: MSG.connect || 'Connect', ms: m.connect_ms, cls: 'connect' },
      { key: 'ttfb', label: MSG.ttfb || 'TTFB', ms: Math.max(0, m.ttfb_ms - m.dns_ms - m.connect_ms), cls: 'ttfb' },
      { key: 'download', label: MSG.download || 'Download', ms: m.download_ms, cls: 'download' },
    ];
    var html = '';
    parts.forEach(function (p) {
      var pct = Math.max(4, Math.round(((p.ms || 0) / max) * 100));
      html += '<div class="hs-speed-bar-row"><span>' + esc(p.label) + '</span>'
        + '<div class="hs-speed-bar-track"><div class="hs-speed-bar-fill hs-speed-bar-' + p.cls + '" style="width:' + pct + '%"></div></div>'
        + '<span class="hs-speed-bar-ms">' + Math.round(p.ms || 0) + ' ' + esc(MSG.ms || 'ms') + '</span></div>';
    });
    barsEl.innerHTML = html;
    waterfallEl.hidden = false;
  }

  function renderTips(report) {
    if (!tipsList || !report.tips) return;
    tipsList.innerHTML = '';
    report.tips.forEach(function (tip) {
      var li = document.createElement('li');
      li.className = 'hs-speed-tip hs-speed-tip-' + (tip.level || 'info');
      var text = MSG[tip.key] || tip.key;
      if (tip.detail) text += ' — ' + tip.detail;
      li.innerHTML = '<i class="fa-solid fa-circle-' + (tip.level === 'ok' ? 'check' : tip.level === 'bad' ? 'xmark' : 'info') + '"></i> ' + esc(text);
      tipsList.appendChild(li);
    });
  }

  function renderMeta(report) {
    if (!metaEl) return;
    var when = report.tested_at ? new Date(report.tested_at).toLocaleString() : '';
    metaEl.textContent = (MSG.last_test || 'Last test') + ': ' + when + (report.url ? ' · ' + report.url : '');
  }

  function setStep(active) {
    if (!stepsEl) return;
    stepsEl.hidden = false;
    stepsEl.querySelectorAll('.hs-speed-step').forEach(function (el) {
      var step = el.getAttribute('data-step');
      el.classList.toggle('is-active', step === active);
      el.classList.toggle('is-done', ['dns', 'connect', 'download', 'analyze'].indexOf(step) < ['dns', 'connect', 'download', 'analyze'].indexOf(active));
    });
  }

  function sleep(ms) {
    return new Promise(function (r) { setTimeout(r, ms); });
  }

  async function runTest() {
    if (running) return;
    running = true;
    if (runBtn) {
      runBtn.disabled = true;
      runBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + esc(MSG.running || 'Testing…');
    }
    root.classList.add('is-running');
    try {
      setStep('dns');
      await sleep(400);
      setStep('connect');
      await sleep(350);
      setStep('download');
      var res = await fetch(cfg.api, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          csrf: cfg.csrf,
          url: urlInput && urlInput.value.trim() !== ''
            ? urlInput.value.trim()
            : ((cfg.initial && cfg.initial.default_url) || ''),
        }),
      });
      setStep('analyze');
      var data = await res.json();
      if (!data.ok) throw new Error(data.error || 'fail');
      renderGauges(data.report);
      renderMetrics(data.report);
      renderWaterfall(data.report);
      renderTips(data.report);
      renderMeta(data.report);
      resultsEl.classList.add('is-visible');
    } catch (e) {
      alert(MSG.error || 'Speed test failed');
    } finally {
      running = false;
      if (stepsEl) stepsEl.hidden = true;
      root.classList.remove('is-running');
      if (runBtn) {
        runBtn.disabled = false;
        runBtn.innerHTML = '<i class="fa-solid fa-play"></i> ' + esc(MSG.run || 'Run');
      }
    }
  }

  if (runBtn) runBtn.addEventListener('click', runTest);

  if (cfg.initial && cfg.initial.report && Object.keys(cfg.initial.report).length > 0) {
    renderGauges(cfg.initial.report);
    renderMetrics(cfg.initial.report);
    renderWaterfall(cfg.initial.report);
    renderTips(cfg.initial.report);
    renderMeta(cfg.initial.report);
    resultsEl.classList.add('is-visible');
  }
})();