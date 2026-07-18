(function () {
  'use strict';

  var form = document.querySelector('[data-hs-panel-order-form]');
  if (!form) return;

  var estimateEl = document.querySelector('[data-hs-order-estimate-total]');
  var noteEl = document.querySelector('[data-hs-order-estimate-note]');

  function orderType() {
    var r = form.querySelector('input[name="order_type"]:checked');
    return r ? r.value : 'hosting';
  }

  function planNok() {
    var r = form.querySelector('input[name="plan"]:checked');
    if (!r) return 0;
    return parseFloat(r.getAttribute('data-price-nok') || '0') || 0;
  }

  function addonsNok() {
    var sum = 0;
    form.querySelectorAll('input[name="plan_services[]"]:checked').forEach(function (cb) {
      sum += parseFloat(cb.getAttribute('data-price-nok') || '0') || 0;
    });
    return sum;
  }

  function domainsEur() {
    var sum = 0;
    document.querySelectorAll('[data-hs-domain-cart-picks] [data-price-eur]').forEach(function (row) {
      sum += parseFloat(row.getAttribute('data-price-eur') || '0') || 0;
    });
    return sum;
  }

  function formatNok(n) {
    try {
      return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'NOK', maximumFractionDigits: 0 }).format(n);
    } catch (e) {
      return Math.round(n) + ' NOK';
    }
  }

  function formatEur(n) {
    try {
      return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'EUR' }).format(n);
    } catch (e) {
      return n.toFixed(2) + ' €';
    }
  }

  function toggleSections() {
    var type = orderType();
    var domainOnly = type === 'domain';
    var needsDomain = type === 'domain' || type === 'bundle';
    var plansWrap = form.querySelector('[data-order-plans-wrap]');
    var addonsWrap = form.querySelector('[data-reg-addons-wrap]');
    var note = form.querySelector('[data-order-domain-note]');
    var domainAdd = form.querySelector('[data-order-domain-add]');
    var domainPlan = form.querySelector('#plan-domain-only');
    if (plansWrap) plansWrap.hidden = domainOnly;
    if (addonsWrap) addonsWrap.hidden = domainOnly;
    if (note) note.hidden = !domainOnly;
    if (domainAdd) domainAdd.style.display = needsDomain ? '' : 'none';
    if (domainPlan) {
      domainPlan.disabled = !domainOnly;
      form.querySelectorAll('input[name="plan"]').forEach(function (inp) {
        if (inp.id === 'plan-domain-only') return;
        inp.disabled = domainOnly;
      });
    }
    form.querySelectorAll('[data-order-type-card]').forEach(function (card) {
      var radio = card.querySelector('input[type="radio"]');
      card.classList.toggle('is-selected', !!(radio && radio.checked));
    });
    recalc();
  }

  function recalc() {
    if (!estimateEl) return;
    var type = orderType();
    var host = 0;
    var dom = domainsEur();
    if (type === 'hosting' || type === 'bundle') {
      host = planNok() + addonsNok();
    }
    // Bundle: domains often included (0 at pay) but show catalog for transparency
    var parts = [];
    if (type !== 'domain' && host > 0) {
      parts.push(formatNok(host));
    }
    if ((type === 'domain' || type === 'bundle') && dom > 0) {
      if (type === 'bundle') {
        parts.push(formatEur(dom) + ' domains*');
      } else {
        parts.push(formatEur(dom));
      }
    }
    estimateEl.textContent = parts.length ? parts.join(' + ') : '—';
    if (noteEl && type === 'bundle' && dom > 0) {
      noteEl.textContent = noteEl.getAttribute('data-bundle-hint')
        || 'Domain catalog price shown; final payment may include promo/bundle rules after update.';
    }
  }

  form.addEventListener('change', function (e) {
    if (e.target && (e.target.name === 'order_type' || e.target.name === 'plan' || e.target.name === 'plan_services[]')) {
      if (e.target.name === 'order_type') toggleSections();
      else recalc();
    }
  });

  document.querySelectorAll('button[name="reset_panel_order"]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      var msg = btn.getAttribute('data-confirm') || 'Clear cart?';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  toggleSections();
  recalc();
})();
