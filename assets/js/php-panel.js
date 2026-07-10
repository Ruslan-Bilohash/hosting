(function () {
  'use strict';

  document.querySelectorAll('[data-php-scroll]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      var id = link.getAttribute('data-php-scroll');
      var el = id ? document.getElementById(id) : null;
      if (!el) {
        return;
      }
      e.preventDefault();
      if (el.tagName === 'DETAILS') {
        el.open = true;
      }
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  var enableAll = document.getElementById('php-ext-enable-all');
  if (enableAll) {
    enableAll.addEventListener('click', function () {
      document.querySelectorAll('#php-ext-form input[type="checkbox"]:not(:disabled)').forEach(function (cb) {
        cb.checked = true;
      });
    });
  }

  var search = document.getElementById('php-ext-search');
  if (search) {
    search.addEventListener('input', function () {
      var q = (search.value || '').trim().toLowerCase();
      document.querySelectorAll('.hs-php-ext-row').forEach(function (row) {
        var name = row.getAttribute('data-ext-name') || '';
        row.style.display = q === '' || name.indexOf(q) !== -1 ? '' : 'none';
      });
      document.querySelectorAll('.hs-php-ext-group-title').forEach(function (title) {
        var grid = title.nextElementSibling;
        if (!grid || !grid.classList.contains('hs-php-ext-grid')) {
          return;
        }
        var any = false;
        grid.querySelectorAll('.hs-php-ext-row').forEach(function (row) {
          if (row.style.display !== 'none') {
            any = true;
          }
        });
        title.style.display = any ? '' : 'none';
        grid.style.display = any ? '' : 'none';
      });
    });
  }
})();