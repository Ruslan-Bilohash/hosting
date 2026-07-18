(function () {
  'use strict';

  // Panel client: #hs-support + HS_SUPPORT
  // Admin inbox:  #hs-admin-support + HS_ADMIN_SUPPORT
  var isAdmin = !!(window.HS_ADMIN_SUPPORT && document.getElementById('hs-admin-support'));
  var root = isAdmin
    ? document.getElementById('hs-admin-support')
    : document.getElementById('hs-support');
  var cfg = isAdmin ? window.HS_ADMIN_SUPPORT : window.HS_SUPPORT;
  if (!root || !cfg) return;

  var MSG = cfg.i18n || {};
  var templates = cfg.templates || [];
  var clients = Array.isArray(cfg.clients) ? cfg.clients : [];
  var threads = [];
  var activeId = '';
  var listFilter = '';
  var mobileMq = window.matchMedia('(max-width: 960px)');
  var editors = {};

  function appendCsrf(fd) {
    if (cfg.csrf) fd.append('csrf', cfg.csrf);
    return fd;
  }

  function showToast(text, ok) {
    var el = q('[data-support-toast]');
    if (!el) {
      setStatus(q('[data-support-inbox-status]'), text, ok);
      return;
    }
    el.innerHTML = '<i class="fa-solid ' + (ok ? 'fa-circle-check' : 'fa-circle-exclamation') + '"></i><span></span>';
    var span = el.querySelector('span');
    if (span) span.textContent = text || '';
    el.className = 'hs-admin-support-toast' + (ok ? ' is-ok' : ' is-err');
    el.hidden = false;
    el.classList.remove('hidden');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(function () {
      el.hidden = true;
      el.classList.add('hidden');
    }, 4200);
  }

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

  function fallbackTa(key) {
    var wrap = root.querySelector('[data-support-editor="' + key + '"]');
    return wrap ? wrap.querySelector('[data-support-fallback]') : null;
  }

  function mountEditors() {
    root.querySelectorAll('[data-support-editor]').forEach(function (wrap) {
      var key = wrap.getAttribute('data-support-editor');
      if (!key || editors[key]) return;
      var mount = wrap.querySelector('[data-support-quill-body]');
      var ta = wrap.querySelector('[data-support-fallback]');
      if (typeof Quill === 'undefined' || !mount) {
        if (mount) mount.style.display = 'none';
        if (ta) {
          ta.style.display = 'block';
          ta.classList.add('is-active');
        }
        return;
      }
      if (ta) ta.style.display = 'none';
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
    if (q) {
      var html = q.root.innerHTML;
      if (html === '<p><br></p>' || html === '<p></p>') return '';
      return html;
    }
    var ta = fallbackTa(key);
    return ta ? String(ta.value || '') : '';
  }

  function editorText(key) {
    var q = editors[key];
    if (q) return (q.getText() || '').trim();
    var ta = fallbackTa(key);
    return ta ? String(ta.value || '').trim() : '';
  }

  function setEditorContent(key, content) {
    var q = editors[key];
    if (q) {
      if (looksLikeHtml(content)) {
        q.root.innerHTML = sanitizeHtml(content);
      } else {
        q.setText(String(content || ''));
      }
      return;
    }
    var ta = fallbackTa(key);
    if (ta) {
      if (looksLikeHtml(content)) {
        var tmp = document.createElement('div');
        tmp.innerHTML = sanitizeHtml(content);
        ta.value = (tmp.textContent || '').trim();
      } else {
        ta.value = String(content || '');
      }
    }
  }

  function clearEditor(key) {
    setEditorContent(key, '');
  }

  function parseJsonResponse(r) {
    return r.text().then(function (text) {
      try {
        return JSON.parse(text || '{}');
      } catch (e) {
        return { ok: false, error: r.status === 401 || r.status === 403 ? 'auth' : ('HTTP ' + r.status) };
      }
    });
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
    var compose = q('[data-support-compose]');
    if (compose) setElHidden(compose, true);
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
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
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
        .then(parseJsonResponse)
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

  function hideCompose() {
    var compose = q('[data-support-compose]');
    if (compose) setElHidden(compose, true);
  }

  function showCompose() {
    hideDetail();
    var compose = q('[data-support-compose]');
    var empty = q('[data-support-detail-empty]');
    if (empty) setElHidden(empty, true);
    if (compose) setElHidden(compose, false);
    setThreadOpen(true);
    clearEditor('compose');
    var subj = q('[data-support-compose-subject]');
    var email = q('[data-support-compose-email]');
    if (subj) subj.value = '';
    if (email) email.value = '';
    var val = q('[data-client-picker-value]');
    var input = q('[data-client-picker-input]');
    var selected = q('[data-client-picker-selected]');
    if (val) val.value = '';
    if (input) input.value = '';
    if (selected) {
      selected.innerHTML = '';
      setElHidden(selected, true);
    }
  }

  function hideDetail() {
    var detail = q('[data-support-detail]');
    if (detail) setElHidden(detail, true);
  }

  function renderThreadActions(thread) {
    var wrap = q('[data-support-thread-actions]');
    if (!wrap || !isAdmin) return;
    wrap.innerHTML = ''
      + '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-support-mark-read>'
      + '<i class="fa-solid fa-envelope-open"></i> ' + esc(MSG.mark_read || 'Mark read') + '</button>'
      + '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-support-archive>'
      + '<i class="fa-solid fa-box-archive"></i> ' + esc(MSG.archive || 'Archive') + '</button>';
    var markBtn = wrap.querySelector('[data-support-mark-read]');
    var archBtn = wrap.querySelector('[data-support-archive]');
    if (markBtn) {
      markBtn.addEventListener('click', function () {
        postAdminAction('mark_read', thread && thread.id);
      });
    }
    if (archBtn) {
      archBtn.addEventListener('click', function () {
        postAdminAction('archive', thread && thread.id);
      });
    }
  }

  function renderClientMeta(thread) {
    var wrap = q('[data-support-client-meta]');
    if (!wrap || !isAdmin) return;
    if (!thread) {
      wrap.innerHTML = '';
      setElHidden(wrap, true);
      return;
    }
    var parts = [];
    if (thread.client_name || thread.from_name) {
      parts.push('<span><i class="fa-solid fa-user"></i> ' + esc(thread.client_name || thread.from_name) + '</span>');
    }
    if (thread.client_email || thread.from_email) {
      parts.push('<span><i class="fa-solid fa-envelope"></i> ' + esc(thread.client_email || thread.from_email) + '</span>');
    }
    if (thread.username) {
      parts.push('<span><code>@' + esc(thread.username) + '</code></span>');
    }
    if (thread.category) {
      parts.push('<span class="hs-admin-support-cat">' + esc(thread.category) + '</span>');
    }
    wrap.innerHTML = parts.join('');
    setElHidden(wrap, parts.length === 0);
  }

  function postAdminAction(action, messageId) {
    if (!messageId || !cfg.apiMessages) return;
    var fd = appendCsrf(new FormData());
    fd.append(action, '1');
    fd.append('message_id', messageId);
    fetch(cfg.apiMessages, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(parseJsonResponse)
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || MSG.inbox_error);
        showToast(action === 'archive' ? (MSG.archive_ok || 'Archived') : (MSG.mark_read_ok || 'Marked read'), true);
        if (action === 'archive') {
          closeThreadView();
          loadInbox();
        } else {
          loadInbox(messageId);
        }
      })
      .catch(function (err) {
        showToast(err.message || MSG.inbox_error, false);
      });
  }

  function openThread(id) {
    activeId = id;
    hideCompose();
    fetch(cfg.apiMessages + '?id=' + encodeURIComponent(id), { credentials: 'same-origin' })
      .then(function (r) { return parseJsonResponse(r); })
      .then(function (data) {
        if (!data.ok || !data.thread) return;
        var detail = q('[data-support-detail]');
        var empty = q('[data-support-detail-empty]');
        if (empty) setElHidden(empty, true);
        if (detail) setElHidden(detail, false);
        var subjEl = q('[data-support-detail-subject]');
        if (subjEl) subjEl.textContent = data.thread.subject || '';
        renderClientMeta(data.thread);
        renderThreadActions(data.thread);
        renderThread(data.thread);
        renderList();
        setThreadOpen(true);
        if (isMobileView()) {
          var detailWrap = q('[data-support-detail-wrap]');
          if (detailWrap) detailWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
  }

  function filteredThreads() {
    if (!listFilter) return threads;
    var f = listFilter.toLowerCase();
    return threads.filter(function (th) {
      var hay = [
        th.subject, th.preview, th.client_name, th.from_name, th.client_email,
        th.from_email, th.username, th.category, th.id
      ].join(' ').toLowerCase();
      return hay.indexOf(f) >= 0;
    });
  }

  function renderList() {
    var list = q('[data-support-list]');
    var empty = q('[data-support-list-empty]');
    if (!list) return;
    var rows = filteredThreads();
    if (rows.length === 0) {
      list.innerHTML = '';
      if (empty) setElHidden(empty, false);
      return;
    }
    if (empty) setElHidden(empty, true);
    list.innerHTML = rows.map(function (th) {
      var isUnread = !!(th.client_unread || th.admin_unread);
      var unread = isUnread ? '<span class="hs-support-item-badge">' + esc(MSG.inbox_unread || 'New') + '</span>' : '';
      var clientLine = '';
      if (isAdmin && (th.client_name || th.from_name || th.username || th.client_email)) {
        clientLine = '<span class="hs-support-item-client">'
          + esc(th.client_name || th.from_name || th.username || th.client_email || '')
          + '</span>';
      }
      return '<button type="button" class="hs-support-item' + (th.id === activeId ? ' active' : '') + (isUnread ? ' is-unread' : '') + '" data-thread-id="' + esc(th.id) + '">'
        + '<span class="hs-support-item-subj">' + esc(th.subject || '') + unread + '</span>'
        + clientLine
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
    fetch(cfg.apiMessages, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return parseJsonResponse(r).then(function (data) { data.__status = r.status; return data; }); })
      .then(function (data) {
        if (!data.ok) {
          var err = data.error === 'module_missing' ? (MSG.module_missing || 'Support module unavailable') : (data.error || MSG.inbox_error || 'Could not load');
          if (data.error === 'auth' || data.__status === 401 || data.__status === 403) {
            err = MSG.inbox_error || 'Please log in again';
          }
          setStatus(q('[data-support-inbox-status]'), err, false);
          threads = [];
          renderList();
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
      var fd = appendCsrf(new FormData());
      fd.append('message_id', activeId);
      fd.append('body', body);
      if (files && files.files) {
        Array.prototype.forEach.call(files.files, function (f) {
          fd.append('attachments[]', f);
        });
      }
      fetch(cfg.apiMessages, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(parseJsonResponse)
        .then(function (data) {
          if (!data.ok) throw new Error(data.error || MSG.inbox_sent_error);
          var okMsg = MSG.inbox_sent_ok || 'Reply sent.';
          if (isAdmin && data.mail_sent === true && MSG.inbox_sent_ok_mail) {
            okMsg = String(MSG.inbox_sent_ok_mail).replace('{from}', cfg.supportFrom || '');
          } else if (isAdmin && data.mail_sent === false && MSG.inbox_sent_ok_nomail) {
            okMsg = MSG.inbox_sent_ok_nomail;
          }
          if (isAdmin) showToast(okMsg, true);
          else setStatus(q('[data-support-inbox-status]'), okMsg, true);
          clearEditor('reply');
          if (files) files.value = '';
          if (data.thread) renderThread(data.thread);
          loadInbox(activeId);
        })
        .catch(function (err) {
          if (isAdmin) showToast(err.message || MSG.inbox_sent_error, false);
          else setStatus(q('[data-support-inbox-status]'), err.message || MSG.inbox_sent_error, false);
        });
    });
  }

  // Admin: compose + client picker + list filter
  if (isAdmin) {
    root.querySelectorAll('[data-support-compose-open], [data-support-compose-open-empty]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        showCompose();
      });
    });
    var composeCancel = q('[data-support-compose-cancel]');
    if (composeCancel) {
      composeCancel.addEventListener('click', function () {
        hideCompose();
        closeThreadView();
      });
    }
    var listFilterEl = q('[data-support-list-filter]');
    if (listFilterEl) {
      listFilterEl.addEventListener('input', function () {
        listFilter = String(listFilterEl.value || '').trim();
        renderList();
      });
    }

    // Client picker
    (function initClientPicker() {
      var box = q('[data-client-picker]');
      if (!box) return;
      var input = box.querySelector('[data-client-picker-input]');
      var listEl = box.querySelector('[data-client-picker-list]');
      var valueEl = box.querySelector('[data-client-picker-value]');
      var selectedEl = box.querySelector('[data-client-picker-selected]');
      var hintEl = box.querySelector('[data-client-picker-hint]');
      var emailEl = q('[data-support-compose-email]');

      function renderOptions(query) {
        if (!listEl) return;
        var qstr = String(query || '').toLowerCase().trim();
        var rows = clients.filter(function (c) {
          if (!qstr) return true;
          return String(c.search || c.label || '').toLowerCase().indexOf(qstr) >= 0;
        }).slice(0, 40);
        if (rows.length === 0) {
          listEl.innerHTML = '<li class="hs-admin-client-picker-empty">' + esc(MSG.compose_none || 'No clients match') + '</li>';
        } else {
          listEl.innerHTML = rows.map(function (c) {
            return '<li class="hs-admin-client-picker-opt" role="option" tabindex="0" data-id="' + esc(c.id) + '" data-email="' + esc(c.email || '') + '" data-label="' + esc(c.label || c.name || c.username || '') + '">'
              + '<div class="hs-admin-client-picker-opt-main"><strong>' + esc(c.name || c.username || c.email || c.id) + '</strong> '
              + (c.username ? '<code>@' + esc(c.username) + '</code>' : '') + '</div>'
              + '<div class="hs-admin-client-picker-opt-meta">' + esc([c.email, c.plan_label || c.plan, c.status].filter(Boolean).join(' · ')) + '</div>'
              + '</li>';
          }).join('');
        }
        setElHidden(listEl, false);
        if (input) input.setAttribute('aria-expanded', 'true');
        listEl.querySelectorAll('[data-id]').forEach(function (li) {
          li.addEventListener('click', function () {
            pickClient(li.getAttribute('data-id'), li.getAttribute('data-email'), li.getAttribute('data-label'));
          });
        });
      }

      function pickClient(id, email, label) {
        if (valueEl) valueEl.value = id || '';
        if (emailEl && email) emailEl.value = email;
        if (input) input.value = '';
        if (selectedEl) {
          selectedEl.innerHTML = '<div class="hs-admin-client-chip"><i class="fa-solid fa-user-check"></i> <span>'
            + esc(label || email || id) + '</span>'
            + '<button type="button" class="hs-admin-client-chip-clear" data-client-picker-clear aria-label="Clear">&times;</button></div>';
          setElHidden(selectedEl, false);
          var clear = selectedEl.querySelector('[data-client-picker-clear]');
          if (clear) {
            clear.addEventListener('click', function () {
              if (valueEl) valueEl.value = '';
              selectedEl.innerHTML = '';
              setElHidden(selectedEl, true);
            });
          }
        }
        if (listEl) setElHidden(listEl, true);
        if (input) input.setAttribute('aria-expanded', 'false');
      }

      if (input) {
        input.addEventListener('focus', function () { renderOptions(input.value); });
        input.addEventListener('input', function () { renderOptions(input.value); });
      }
      document.addEventListener('click', function (ev) {
        if (!box.contains(ev.target) && listEl) {
          setElHidden(listEl, true);
          if (input) input.setAttribute('aria-expanded', 'false');
        }
      });
    })();

    var composeForm = q('[data-support-compose-form]');
    if (composeForm) {
      composeForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var emailEl = q('[data-support-compose-email]');
        var subjEl = q('[data-support-compose-subject]');
        var userEl = q('[data-client-picker-value]');
        var toEmail = emailEl ? String(emailEl.value || '').trim() : '';
        var subject = subjEl ? String(subjEl.value || '').trim() : '';
        var body = editorHtml('compose');
        if (!toEmail || toEmail.indexOf('@') < 0) {
          showToast(MSG.compose_need_email || 'Enter a valid recipient email.', false);
          return;
        }
        if (!subject) {
          showToast(MSG.compose_need_subject || 'Enter a subject.', false);
          return;
        }
        if (!editorText('compose')) {
          showToast(MSG.fill_required || 'Message is required.', false);
          return;
        }
        var fd = appendCsrf(new FormData());
        fd.append('compose_new', '1');
        fd.append('to_email', toEmail);
        fd.append('subject', subject);
        fd.append('body', body);
        fd.append('user_id', userEl ? String(userEl.value || '') : '');
        var submitBtn = composeForm.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        fetch(cfg.apiMessages, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(parseJsonResponse)
          .then(function (data) {
            if (!data.ok) throw new Error(data.error || MSG.compose_error);
            var okMsg = MSG.compose_ok || 'Message sent.';
            if (data.mail_sent === true && MSG.compose_ok_mail) {
              okMsg = String(MSG.compose_ok_mail)
                .replace('{from}', cfg.supportFrom || data.from || '')
                .replace('{to}', data.to || toEmail);
            } else if (data.mail_sent === false && MSG.compose_ok_nomail) {
              okMsg = MSG.compose_ok_nomail;
            }
            showToast(okMsg, true);
            hideCompose();
            loadInbox(data.id || '');
          })
          .catch(function (err) {
            showToast(err.message || MSG.compose_error, false);
          })
          .finally(function () {
            if (submitBtn) submitBtn.disabled = false;
          });
      });
    }
  }

  root.querySelectorAll('[data-support-panel]').forEach(function (p) {
    var show = p.getAttribute('data-support-panel') === 'inbox';
    setElHidden(p, !show);
  });

  function bootEditors(attempt) {
    attempt = attempt || 0;
    if (typeof Quill === 'undefined' && attempt < 25) {
      setTimeout(function () { bootEditors(attempt + 1); }, 120);
      return;
    }
    mountEditors();
  }
  bootEditors(0);

  loadInbox();
})();