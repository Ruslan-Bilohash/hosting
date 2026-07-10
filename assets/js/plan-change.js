(function () {
  'use strict';

  var cfg = window.HS_PLAN_CHANGE;
  if (!cfg || !cfg.api) return;

  var modal = document.querySelector('[data-hs-plan-modal]');
  var backdrop = document.querySelector('[data-hs-plan-modal-backdrop]');
  var grid = document.querySelector('[data-hs-plan-modal-grid]');
  var closeBtns = document.querySelectorAll('[data-hs-plan-modal-close]');
  var confirmBtn = document.querySelector('[data-hs-plan-modal-confirm]');
  var statusEl = document.querySelector('[data-hs-plan-modal-status]');
  var selected = '';
  var prefPlan = '';
  var plans = [];
  var loading = false;

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function openModal(pref) {
    if (!modal || !backdrop) return;
    prefPlan = pref || '';
    selected = '';
    if (confirmBtn) confirmBtn.disabled = true;
    modal.hidden = false;
    backdrop.hidden = false;
    document.body.style.overflow = 'hidden';
    loadPlans();
  }

  function closeModal() {
    if (!modal || !backdrop) return;
    modal.hidden = true;
    backdrop.hidden = true;
    document.body.style.overflow = '';
    selected = '';
    if (confirmBtn) confirmBtn.disabled = true;
  }

  function setStatus(msg, isError) {
    if (!statusEl) return;
    statusEl.textContent = msg || '';
    statusEl.className = 'hs-plan-modal-status' + (isError ? ' is-error' : msg ? ' is-ok' : '');
  }

  function renderPlans() {
    if (!grid) return;
    grid.innerHTML = '';
    plans.forEach(function (p) {
      var card = document.createElement('article');
      card.className = 'hs-plan-modal-card'
        + (p.is_current ? ' is-current' : '')
        + (p.is_popular ? ' is-popular' : '')
        + (!p.can_select ? ' is-disabled' : '');
      var feats = (p.features || []).slice(0, 4).map(function (f) {
        return '<li><i class="fa-solid fa-check"></i> ' + esc(f) + '</li>';
      }).join('');
      var badge = p.is_current
        ? '<span class="hs-plan-modal-badge">' + esc(cfg.i18n.current || 'Current') + '</span>'
        : (p.is_popular ? '<span class="hs-plan-modal-badge popular">' + esc(cfg.i18n.popular || 'Popular') + '</span>' : '');
      var priceExtra = '';
      if (!p.is_current && p.diff_label) {
        priceExtra = '<span class="hs-plan-modal-diff">' + esc(cfg.i18n.diff || 'Due now') + ': ' + esc(p.diff_label) + '</span>';
      }
      var blocked = p.downgrade_blocked
        ? '<p class="hs-plan-modal-blocked">' + esc((cfg.i18n.downgrade || '').replace('{used}', p.sites_used).replace('{limit}', p.sites)) + '</p>'
        : '';
      card.innerHTML = badge
        + '<h4>' + esc(p.name) + '</h4>'
        + '<p class="hp-muted">' + esc(p.desc) + '</p>'
        + '<div class="hs-plan-modal-price"><strong>' + esc(p.price_label) + '</strong><span>' + esc(cfg.i18n.per_month || '/mo') + '</span></div>'
        + priceExtra
        + '<ul class="hs-plan-features">' + feats + '</ul>'
        + blocked;
      if (p.can_select) {
        card.setAttribute('role', 'button');
        card.tabIndex = 0;
        card.dataset.planId = p.id;
        card.addEventListener('click', function () { selectPlan(p.id); });
        card.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            selectPlan(p.id);
          }
        });
      }
      grid.appendChild(card);
    });
  }

  function selectPlan(id) {
    selected = id;
    if (!grid) return;
    grid.querySelectorAll('.hs-plan-modal-card').forEach(function (el) {
      el.classList.toggle('is-selected', el.dataset.planId === id);
    });
    if (confirmBtn) confirmBtn.disabled = false;
    setStatus('');
  }

  function loadPlans() {
    if (loading || !grid) return;
    loading = true;
    grid.innerHTML = '<p class="hp-muted hs-plan-modal-loading"><i class="fa-solid fa-spinner fa-spin"></i> ' + esc(cfg.i18n.loading || 'Loading…') + '</p>';
    fetch(cfg.api, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) throw new Error('load');
        plans = data.plans || [];
        renderPlans();
        if (prefPlan) selectPlan(prefPlan);
      })
      .catch(function () {
        grid.innerHTML = '<p class="hs-plan-modal-error">' + esc(cfg.i18n.load_error || 'Could not load plans') + '</p>';
      })
      .finally(function () { loading = false; });
  }

  function confirmChange() {
    if (!selected || loading) return;
    loading = true;
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + esc(cfg.i18n.changing || '…');
    }
    fetch(cfg.api, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ csrf: cfg.csrf, plan: selected }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) {
          setStatus(data.message || cfg.i18n.error || 'Error', true);
          if (confirmBtn) confirmBtn.disabled = false;
          return;
        }
        setStatus(data.message || cfg.i18n.success || 'OK', false);
        setTimeout(function () {
          window.location.reload();
        }, 900);
      })
      .catch(function () {
        setStatus(cfg.i18n.error || 'Error', true);
        if (confirmBtn) confirmBtn.disabled = false;
      })
      .finally(function () {
        loading = false;
        if (confirmBtn) {
          confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i> ' + esc(cfg.i18n.confirm || 'Confirm');
        }
      });
  }

  document.querySelectorAll('[data-hs-plan-change-open]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      openModal(btn.getAttribute('data-plan-pref') || '');
    });
  });
  closeBtns.forEach(function (btn) {
    btn.addEventListener('click', closeModal);
  });
  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (confirmBtn) confirmBtn.addEventListener('click', confirmChange);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal && !modal.hidden) closeModal();
  });
})();