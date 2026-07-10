(function () {
  'use strict';

  var root = document.querySelector('[data-hs-reg-wizard]');
  if (!root) return;

  var form = root.querySelector('form');
  var panels = root.querySelectorAll('[data-reg-step]');
  var stepItems = root.querySelectorAll('[data-reg-step-item]');
  var btnBack = root.querySelector('[data-reg-back]');
  var btnNext = root.querySelector('[data-reg-next]');
  var btnSubmit = root.querySelector('[data-reg-submit]');
  var total = panels.length;
  var current = parseInt(root.getAttribute('data-start-step') || '1', 10);
  if (current < 1 || current > total) current = 1;

  var typeRadios = form.querySelectorAll('input[name="account_type"]');
  var blockPersonal = form.querySelector('[data-reg-personal]');
  var blockBusiness = form.querySelector('[data-reg-business]');

  function accountType() {
    var checked = form.querySelector('input[name="account_type"]:checked');
    return checked ? checked.value : 'personal';
  }

  function setTypeBlocks() {
    var isBiz = accountType() === 'business';
    if (blockPersonal) blockPersonal.hidden = isBiz;
    if (blockBusiness) blockBusiness.hidden = !isBiz;
    var hintPersonal = root.querySelector('[data-reg-hint-personal]');
    var hintBusiness = root.querySelector('[data-reg-hint-business]');
    if (hintPersonal) hintPersonal.hidden = isBiz;
    if (hintBusiness) hintBusiness.hidden = !isBiz;
    form.querySelectorAll('[data-reg-business-only]').forEach(function (el) {
      el.disabled = !isBiz;
      if (isBiz) el.setAttribute('required', 'required');
      else {
        el.removeAttribute('required');
        if (el.type !== 'radio') el.value = '';
      }
    });
    ['first_name', 'last_name'].forEach(function (id) {
      var label = form.querySelector('label[for="' + id + '"]');
      var input = form.querySelector('#' + id);
      if (!label) return;
      var text = isBiz ? label.getAttribute('data-label-business') : label.getAttribute('data-label-personal');
      if (text) label.textContent = text + ' *';
      if (input) input.disabled = false;
    });
    form.querySelectorAll('[data-reg-type-card]').forEach(function (card) {
      var radio = card.querySelector('input[type="radio"]');
      card.classList.toggle('is-selected', radio && radio.checked);
    });
  }

  function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = String(s || '').trim();
    return d.innerHTML;
  }

  function buildSummary() {
    var box = root.querySelector('[data-reg-summary]');
    if (!box) return;
    var isBiz = accountType() === 'business';
    var plan = form.querySelector('input[name="plan"]:checked');
    var rows = [];
    function row(label, val) {
      val = String(val || '').trim();
      if (val) rows.push('<div class="hs-reg-summary-row"><span>' + escHtml(label) + '</span><strong>' + escHtml(val) + '</strong></div>');
    }
    var labels = root.getAttribute('data-summary-labels');
    var L = {};
    try { L = JSON.parse(labels || '{}'); } catch (e) {}
    var planTitle = '';
    if (plan && plan.closest) {
      var planEl = plan.closest('.hs-plan');
      if (planEl) {
        var h3 = planEl.querySelector('h3');
        if (h3) planTitle = h3.textContent.replace(/\s+/g, ' ').trim();
      }
    }
    row(L.plan || 'Plan', planTitle);
    row(L.type || 'Type', isBiz ? (L.business || 'Business') : (L.personal || 'Personal'));
    if (isBiz) {
      row(L.company || 'Company', form.querySelector('#company')?.value);
      row(L.vat || 'VAT', form.querySelector('#vat')?.value);
    }
    row(L.name || 'Name', (form.querySelector('#first_name')?.value || '') + ' ' + (form.querySelector('#last_name')?.value || ''));
    row(L.email || 'Email', form.querySelector('#email')?.value);
    row(L.phone || 'Phone', form.querySelector('#phone')?.value);
    row(L.username || 'Username', form.querySelector('#username')?.value);
    row(L.address || 'Address', [form.querySelector('#address')?.value, form.querySelector('#postal')?.value, form.querySelector('#city')?.value].filter(Boolean).join(', '));
    var domain = form.querySelector('#domain_wish')?.value;
    if (domain) row(L.domain || 'Domain', domain);
    box.innerHTML = rows.join('');
  }

  function showStep(n) {
    current = Math.max(1, Math.min(total, n));
    panels.forEach(function (p) {
      var step = parseInt(p.getAttribute('data-reg-step'), 10);
      p.hidden = step !== current;
      p.classList.toggle('is-active', step === current);
    });
    stepItems.forEach(function (item) {
      var step = parseInt(item.getAttribute('data-reg-step-item'), 10);
      item.classList.toggle('is-done', step < current);
      item.classList.toggle('is-active', step === current);
    });
    if (btnBack) btnBack.hidden = current <= 1;
    if (btnNext) btnNext.hidden = current >= total;
    if (btnSubmit) btnSubmit.hidden = current < total;
    root.setAttribute('data-current-step', String(current));
    if (current === total) buildSummary();
    var active = root.querySelector('[data-reg-step="' + current + '"]');
    if (active) {
      var focusable = active.querySelector('input:not([type="hidden"]):not([disabled]), select, textarea, button');
      if (focusable && typeof focusable.focus === 'function') {
        try { focusable.focus({ preventScroll: true }); } catch (e) { focusable.focus(); }
      }
    }
    window.scrollTo({ top: root.offsetTop - 24, behavior: 'smooth' });
  }

  function fieldsInStep(step) {
    var panel = root.querySelector('[data-reg-step="' + step + '"]');
    if (!panel) return [];
    return Array.prototype.slice.call(panel.querySelectorAll('input, select, textarea')).filter(function (el) {
      if (el.disabled || el.type === 'hidden' || el.type === 'radio' && !el.checked && el.name !== 'account_type') {
        if (el.type === 'radio') return false;
      }
      if (el.type === 'radio') return el.checked;
      return !el.disabled;
    });
  }

  function validateStep(step) {
    var panel = root.querySelector('[data-reg-step="' + step + '"]');
    if (!panel) return true;
    var invalid = null;
    var inputs = panel.querySelectorAll('input, select, textarea');
    inputs.forEach(function (el) {
      if (el.disabled || el.type === 'hidden') return;
      if (el.type === 'radio') {
        if (el.name === 'account_type' || el.name === 'plan') {
          var group = form.querySelector('input[name="' + el.name + '"]:checked');
          if (!group) invalid = el;
        }
        return;
      }
      if (el.hasAttribute('required') && !el.checkValidity()) {
        invalid = el;
      }
    });
    if (step === 2 && !form.querySelector('input[name="account_type"]:checked')) {
      invalid = form.querySelector('input[name="account_type"]');
    }
    if (step === 1 && !form.querySelector('input[name="plan"]:checked')) {
      invalid = form.querySelector('input[name="plan"]');
    }
    if (invalid) {
      invalid.reportValidity();
      invalid.focus();
      panel.classList.add('hs-reg-shake');
      setTimeout(function () { panel.classList.remove('hs-reg-shake'); }, 400);
      return false;
    }
    return true;
  }

  function syncPlanCards() {
    form.querySelectorAll('[data-hs-plan-card]').forEach(function (card) {
      var radio = card.querySelector('input[type="radio"][name="plan"]');
      card.classList.toggle('is-selected', !!(radio && radio.checked));
    });
  }

  form.querySelectorAll('input[name="plan"]').forEach(function (radio) {
    radio.addEventListener('change', syncPlanCards);
  });
  form.querySelectorAll('[data-hs-plan-card]').forEach(function (card) {
    card.addEventListener('click', function () {
      var radio = card.querySelector('input[type="radio"][name="plan"]');
      if (radio && !radio.checked) {
        radio.checked = true;
        syncPlanCards();
      }
    });
  });
  syncPlanCards();

  typeRadios.forEach(function (r) {
    r.addEventListener('change', setTypeBlocks);
  });
  form.querySelectorAll('[data-reg-type-card]').forEach(function (card) {
    card.addEventListener('click', function () {
      var radio = card.querySelector('input[type="radio"]');
      if (radio) {
        radio.checked = true;
        setTypeBlocks();
        form.querySelectorAll('[data-reg-type-card]').forEach(function (c) {
          c.classList.toggle('is-selected', c === card);
        });
      }
    });
  });

  if (btnNext) {
    btnNext.addEventListener('click', function () {
      if (!validateStep(current)) return;
      if (current === 2) setTypeBlocks();
      showStep(current + 1);
    });
  }
  if (btnBack) {
    btnBack.addEventListener('click', function () {
      showStep(current - 1);
    });
  }

  form.addEventListener('submit', function (e) {
    for (var s = 1; s <= total; s++) {
      if (!validateStep(s)) {
        e.preventDefault();
        showStep(s);
        return;
      }
    }
    setTypeBlocks();
  });

  setTypeBlocks();
  form.querySelectorAll('[data-reg-type-card]').forEach(function (card) {
    var radio = card.querySelector('input[type="radio"]');
    card.classList.toggle('is-selected', radio && radio.checked);
  });
  showStep(current);
})();