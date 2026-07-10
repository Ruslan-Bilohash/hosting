(function () {
  'use strict';

  document.querySelectorAll('.hs-db-copy-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var secret = btn.getAttribute('data-secret');
      var targetId = btn.getAttribute('data-copy-target');
      var text = secret || '';
      if (!text && targetId) {
        var el = document.getElementById(targetId);
        if (el) text = el.textContent || '';
      }
      if (!text) return;
      var copiedLabel = btn.getAttribute('data-copied-label') || 'Copied';
      var done = function () {
        btn.classList.add('is-copied');
        setTimeout(function () { btn.classList.remove('is-copied'); }, 1500);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(function () {});
      } else {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (e) { /* ignore */ }
        document.body.removeChild(ta);
      }
    });
  });
})();