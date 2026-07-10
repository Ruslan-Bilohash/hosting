(function () {
  'use strict';

  var root = document.getElementById('hs-support');
  if (!root || !window.HS_SUPPORT) return;

  var cfg = window.HS_SUPPORT;
  var MSG = cfg.i18n || {};
  var templates = cfg.templates || [];
  var threads = [];
  var activeId = '';
  var mobileMq = window.matchMedia('(max-width: 960px)');
  var editors = {};

  var toolbarOptions = [
    ['bold', 'italic', 'underline'],
    ['link'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['clean'],
  ];

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function looksLikeHtml(s) {
    return /<[a-z][\s\S]*>/i.test(String(s || ''));
  }

  function sanitizeHtml(html) {
    var tmp = document.createElement('div');
    tmp.innerHTML = String(html || '');
    tmp.querySelectorAll('script,style,iframe,object,embed,form').forEach(function (n) {
      n.remove();
    });
    tmp.querySelectorAll('a').forEach(function (a) {
      var href = a.getAttribute('href') || '';
      if (/^\s*javascript:/i.test(href)) {
        a.removeAttribute('href');
      } else {
        a.setAttribute('target', '_blank');
        a.setAttribute('rel', 'noopener noreferrer');
      }
    });
    return tmp.innerHTML;
  }

  function mountEditors() {
    if (typeof Quill === 'undefined') return;
    root.querySelectorAll('[data-support-editor]').forEach(function (wrap) {
      var key = wrap.getAttribute('data-support-editor');
      if (!key || editors[key]) return;
      var mount = wrap.querySelector('[data-support-quill-body]');
      if (!mount) return;
      var quill = new Quill(mount, {
        theme: 'snow',
        modules: {
          toolbar: {
            container: toolbarOptions,
            handlers: {
              link: function (value) {
                if (!value) {
                  this.quill.format('link', false);
                  return;
                }
                var url = window.prompt(MSG.link_prompt || 'Enter link URL:', 'https://');
                if (url) {
                  this.quill.format('link', url);
                }
              },
            },
          },
        },
        placeholder: wrap.getAttribute('data-placeholder') || '',
      });
      editors[key] = quill;
    });
  }

  function editorHtml(key) {
    var q = editors[key];
    if (!q) return '';
    var html = q.root.innerHTML;
    if (html === '<p><br></p>' || html === '<p></p>') return '';
    return html;
  }

  function editorText(key) {
    var q = editors[key];
    if (!q) return '';
    return (q.getText() || '').trim();
  }

  function setEditorContent(key, content) {
    var q = editors[key];
    if (!q) return;
    if (looksLikeHtml(content)) {
      q.root.innerHTML = sanitizeHtml(content);
    } else {
      q.setText(String(content || ''));
    }
  }

  function clearEditor(key) {
    setEditorContent(key, '');
  }

  function renderBodyHtml(body) {
    if (!body) return '';
    if (looksLikeHtml(body)) {
      return '<div class="hs-support-rich">' + sanitizeHtml(body) + '</div>';
    }
    return '<div class="hs-support-plain">' + esc(body) + '</div>';
  }

  function q(sel) {
    return root.querySelector(sel);
  }

  function isMobileView() {
    return mobileMq.matches;
  }

  function setThreadOpen(open) {
    root.classList.toggle('is-thread-open', !!(open && isMobileView()));
  }

  function closeThreadView() {
    setThreadOpen(false);
    activeId = '';
    setElHidden(q('[data-support-detail]'), true);
    var empty = q('[data-support-detail-empty]');
    if (empty) setElHidden(empty, isMobileView());
    renderList();
  }

  function setListLoading(on) {
    setElHidden(q('[data-support-list-loading]'), !on);
  }

  function setStatus(el, text, ok) {
    if (!el) return;
    if (!text) {
      el.hidden = true;
      return;
    }
    el.textContent = text;
    el.className = 'hs-support-status' + (ok ? ' is-ok' : ' is-err');
    el.hidden = false;
  }

  function setElHidden(el, hidden) {
    if (!el) return;
    el.classList.toggle('hidden', hidden);
    el.hidden = hidden;
  }

  function switchTab(id) {
    root.querySelectorAll('[data-support-tab]').forEach(function (btn) {
      btn.classList.toggle('is-active', btn.getAttribute('data-support-tab') === id);
      btn.setAttribute('aria-selected', btn.getAttribute('data-support-tab') === id ? 'true' : 'false');
    });
    root.querySelectorAll('[data-support-panel]').forEach(function (p) {
      setElHidden(p, p.getAttribute('data-support-panel') !== id);
    });
    if (id !== 'inbox') setThreadOpen(false);
  }

  root.querySelectorAll('[data-support-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      switchTab(btn.getAttribute('data-support-tab'));
      if (btn.getAttribute('data-support-tab') === 'inbox') loadInbox();
    });
  });

  var goNewBtn = q('[data-support-go-new]');
  if (goNewBtn) {
    goNewBtn.addEventListener('click', function () {
      switchTab('new');
    });
  }

  var backBtn = q('[data-support-back]');
  if (backBtn) {
    backBtn.addEventListener('click', function () {
      closeThreadView();
      var listWrap = q('[data-support-list-wrap]');
      if (listWrap) listWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  }

  function onViewportChange() {
    var detail = q('[data-support-detail]');
    var empty = q('[data-support-detail-empty]');
    if (!isMobileView()) {
      setThreadOpen(false);
      if (empty && detail && detail.classList.contains('hidden')) setElHidden(empty, false);
    } else if (!activeId) {
      setThreadOpen(false);
      if (empty) setElHidden(empty, true);
    } else if (detail && !detail.classList.contains('hidden')) {
      setThreadOpen(true);
      if (empty) setElHidden(empty, true);
    }
  }

  if (mobileMq.addEventListener) mobileMq.addEventListener('change', onViewportChange);
  else if (mobileMq.addListener) mobileMq.addListener(onViewportChange);

  var tplSel = q('[data-support-template]');
  if (tplSel) {
    tplSel.addEventListener('change', function () {
      var id = tplSel.value;
      if (!id) return;
      var tpl = templates.find(function (x) { return x.id === id; });
      if (!tpl) return;
      var cat = q('[data-support-category]');
      var subj = q('[data-support-subject]');
      if (cat) cat.value = tpl.category || 'support';
      if (subj) subj.value = tpl.subject || '';
      setEditorContent('draft', tpl.body || '');
      setEditorContent('body', tpl.body || '');
    });
  }

  root.querySelectorAll('[data-support-agent]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var cat = q('[data-support-category]');
      if (cat) cat.value = btn.getAttribute('data-category') || 'support';
      setEditorContent('draft', btn.getAttribute('data-draft') || '');
      runAi(btn.getAttribute('data-agent') || 'general');
    });
  });

  var aiBtn = q('[data-support-ai-btn]');
  if (aiBtn) {
    aiBtn.addEventListener('click', function () {
      runAi('general');
    });
  }

  function runAi(agent) {
    var draft = editorText('draft');
    var site = (q('[data-support-site]') || {}).value || '';
    var btn = aiBtn;
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-brain"></i> ' + esc(MSG.thinking || 'Thinking…');
    }
    fetch(cfg.apiAi, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ agent: agent, draft: draft, site_slug: site, lang: cfg.lang }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || MSG.ai_error);
        if (data.subject && q('[data-support-subject]')) q('[data-support-subject]').value = data.subject;
        if (data.body) setEditorContent('body', data.body);
      })
      .catch(function (e) {
        alert(e.message || MSG.ai_error);
      })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> ' + esc(MSG.ai_compose || 'Improve with AI');
        }
      });
  }

  var sendBtn = q('[data-support-send]');
  if (sendBtn) {
    sendBtn.addEventListener('click', function () {
      var subject = (q('[data-support-subject]') || {}).value || '';
      var body = editorHtml('body');
      if (!subject.trim() || !editorText('body')) {
        setStatus(q('[data-support-new-status]'), MSG.fill_required || 'Subject and message are required.', false);
        return;
      }
      sendBtn.disabled = true;
      fetch(cfg.apiOwner, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          subject: subject.trim(),
          body: body,
          category: (q('[data-support-category]') || {}).value || 'support',
          site_slug: (q('[data-support-site]') || {}).value || '',
          from_email: (cfg.client && cfg.client.support_email) || (q('[data-support-email]') || {}).value || '',
          lang: cfg.lang,
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.ok) throw new Error(data.error || MSG.sent_error);
          setStatus(q('[data-support-new-status]'), MSG.sent_ok, true);
          clearEditor('draft');
          clearEditor('body');
          switchTab('inbox');
          loadInbox(data.id || '');
        })
        .catch(function (e) {
          setStatus(q('[data-support-new-status]'), e.message || MSG.sent_error, false);
        })
        .finally(function () {
          sendBtn.disabled = false;
        });
    });
  }

  function fileUrl(msgId, postId, attId) {
    return cfg.apiMessageFile + '?message_id=' + encodeURIComponent(msgId)
      + '&post_id=' + encodeURIComponent(postId) + '&att_id=' + encodeURIComponent(attId);
  }

  function renderThread(thread) {
    var wrap = q('[data-support-thread]');
    if (!wrap) return;
    var posts = (thread && thread.thread) || [];
    var html = '';
    posts.forEach(function (post) {
      var isOwner = post.author === 'owner';
      var author = isOwner ? (MSG.inbox_author_owner || 'BILOHASH') : (post.author_name || MSG.inbox_author_you || 'You');
      html += '<div class="hs-support-bubble' + (isOwner ? ' is-owner' : '') + '">'
        + '<div class="hs-support-bubble-head"><span>' + esc(author) + '</span><span>' + esc(post.ts_label || '') + '</span></div>';
      if (post.body) html += '<div class="hs-support-bubble-body">' + renderBodyHtml(post.body) + '</div>';
      (post.attachments || []).forEach(function (att) {
        var url = fileUrl(thread.id, post.id, att.id);
        if ((att.mime || '').indexOf('image/') === 0) {
          html += '<a href="' + esc(url) + '" target="_blank" rel="noopener"><img src="' + esc(url) + '" alt="" class="hs-support-img" loading="lazy"></a>';
        } else {
          html += '<a href="' + esc(url) + '" target="_blank" rel="noopener">' + esc(att.name || 'file') + '</a>';
        }
      });
      html += '</div>';
    });
    wrap.innerHTML = html;
    wrap.scrollTop = wrap.scrollHeight;
  }

  function openThread(id) {
    activeId = id;
    fetch(cfg.apiMessages + '?id=' + encodeURIComponent(id), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok || !data.thread) return;
        var detail = q('[data-support-detail]');
        var empty = q('[data-support-detail-empty]');
        if (empty) setElHidden(empty, true);
        if (detail) setElHidden(detail, false);
        var subjEl = q('[data-support-detail-subject]');
        if (subjEl) subjEl.textContent = data.thread.subject || '';
        renderThread(data.thread);
        renderList();
        setThreadOpen(true);
        if (isMobileView()) {
          var detailWrap = q('[data-support-detail-wrap]');
          if (detailWrap) detailWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
  }

  function renderList() {
    var list = q('[data-support-list]');
    var empty = q('[data-support-list-empty]');
    if (!list) return;
    if (threads.length === 0) {
      list.innerHTML = '';
      if (empty) setElHidden(empty, false);
      return;
    }
    if (empty) setElHidden(empty, true);
    list.innerHTML = threads.map(function (th) {
      var unread = th.client_unread ? '<span class="hs-support-item-badge">' + esc(MSG.inbox_unread || 'New') + '</span>' : '';
      return '<button type="button" class="hs-support-item' + (th.id === activeId ? ' active' : '') + '" data-thread-id="' + esc(th.id) + '">'
        + '<span class="hs-support-item-subj">' + esc(th.subject || '') + unread + '</span>'
        + '<span class="hs-support-item-prev">' + esc(th.preview || '') + '</span></button>';
    }).join('');
    list.querySelectorAll('[data-thread-id]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openThread(btn.getAttribute('data-thread-id'));
      });
    });
  }

  function updateUnreadBadge(n) {
    var badge = q('[data-support-unread]');
    if (!badge) return;
    if (n > 0) {
      badge.textContent = String(n);
      setElHidden(badge, false);
    } else {
      setElHidden(badge, true);
    }
  }

  function loadInbox(selectId) {
    setListLoading(true);
    fetch(cfg.apiMessages, { credentials: 'same-origin' })
      .then(function (r) {
        if (!r.ok) {
          return r.json().catch(function () { return { ok: false, error: 'HTTP ' + r.status }; });
        }
        return r.json();
      })
      .then(function (data) {
        if (!data.ok) {
          var err = data.error === 'module_missing' ? (MSG.module_missing || 'Support module unavailable') : (data.error || MSG.inbox_error || 'Could not load');
          setStatus(q('[data-support-inbox-status]'), err, false);
          return;
        }
        threads = data.threads || [];
        updateUnreadBadge(data.unread || 0);
        renderList();
        if (selectId) {
          openThread(selectId);
        } else if (threads.length > 0 && !isMobileView()) {
          openThread(activeId || threads[0].id);
        } else if (!activeId || isMobileView()) {
          closeThreadView();
        }
      })
      .catch(function () {
        setStatus(q('[data-support-inbox-status]'), MSG.inbox_error || 'Network error', false);
      })
      .finally(function () {
        setListLoading(false);
      });
  }

  var replyForm = q('[data-support-reply-form]');
  if (replyForm) {
    replyForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!activeId) return;
      var body = editorHtml('reply');
      if (!editorText('reply')) {
        setStatus(q('[data-support-inbox-status]'), MSG.fill_required || 'Message is required.', false);
        return;
      }
      var files = q('[data-support-reply-files]');
      var fd = new FormData();
      fd.append('message_id', activeId);
      fd.append('body', body);
      if (files && files.files) {
        Array.prototype.forEach.call(files.files, function (f) {
          fd.append('attachments[]', f);
        });
      }
      fetch(cfg.apiMessages, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.ok) throw new Error(data.error || MSG.inbox_sent_error);
          setStatus(q('[data-support-inbox-status]'), MSG.inbox_sent_ok, true);
          clearEditor('reply');
          if (files) files.value = '';
          if (data.thread) renderThread(data.thread);
          loadInbox(activeId);
        })
        .catch(function (err) {
          setStatus(q('[data-support-inbox-status]'), err.message || MSG.inbox_sent_error, false);
        });
    });
  }

  root.querySelectorAll('[data-support-panel]').forEach(function (p) {
    var show = p.getAttribute('data-support-panel') === 'inbox';
    setElHidden(p, !show);
  });

  mountEditors();

  loadInbox();
})();