(function () {
  'use strict';

  var box = document.querySelector('[data-hs-dom-pending-poll]');
  if (!box) return;

  var checkUrl = box.getAttribute('data-check-url') || '';
  var csrf = box.getAttribute('data-csrf') || '';
  var msgChecking = box.getAttribute('data-msg-checking') || 'Checking…';
  var msgLive = box.getAttribute('data-msg-live') || 'Domain is live!';
  var msgEl = box.querySelector('[data-hs-dom-pending-msg]');
  var intervalMs = 90000;
  var timer = null;

  function setMsg(text) {
    if (msgEl) msgEl.textContent = text;
  }

  function poll() {
    if (!checkUrl) return;
    setMsg(msgChecking);
    fetch(checkUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ csrf: csrf }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) {
          setMsg('');
          return;
        }
        if (data.activated && data.activated.length > 0) {
          setMsg(msgLive);
          window.setTimeout(function () { window.location.reload(); }, 1500);
          return;
        }
        if (data.pending && data.pending.length > 0) {
          var liveAny = data.pending.some(function (p) { return p.last_check_live; });
          setMsg(liveAny ? msgLive : '');
        } else {
          window.location.reload();
        }
      })
      .catch(function () {
        setMsg('');
      });
  }

  poll();
  timer = window.setInterval(poll, intervalMs);
  window.addEventListener('beforeunload', function () {
    if (timer) window.clearInterval(timer);
  });
})();