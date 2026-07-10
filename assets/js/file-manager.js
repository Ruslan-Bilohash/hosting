(function () {
  'use strict';

  var root = document.getElementById('hs-file-manager');
  if (!root || !window.HS_FM) return;

  var cfg = window.HS_FM;
  var cm = null;
  var modalCb = null;

  var state = {
    path: '',
    parent: '',
    entries: [],
    selected: null,
    view: localStorage.getItem('hs_fm_view') || 'list',
    search: '',
    sidebarW: parseInt(localStorage.getItem('hs_fm_sidebar') || '240', 10) || 240,
    splitH: parseInt(localStorage.getItem('hs_fm_split') || '42', 10) || 42,
    tabs: [],
    activeTab: null,
    ctxPath: null,
    ctxDir: false,
    ctxArchive: false,
  };

  function t(key) {
    return (cfg.i18n && cfg.i18n[key]) || key;
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function apiUrl(action, params) {
    var u = new URL(cfg.api, window.location.origin);
    u.searchParams.set('action', action);
    if (params) {
      Object.keys(params).forEach(function (k) {
        if (params[k] !== undefined && params[k] !== null) u.searchParams.set(k, params[k]);
      });
    }
    return u.toString();
  }

  function fetchJson(url) {
    return fetch(url, { credentials: 'same-origin' }).then(function (r) {
      if (!r.ok) throw new Error('http_' + r.status);
      var ct = r.headers.get('content-type') || '';
      if (ct.indexOf('json') < 0) throw new Error('not_json');
      return r.json();
    });
  }

  function post(action, data) {
    var fd = new FormData();
    fd.append('action', action);
    fd.append('csrf', cfg.csrf);
    if (data) {
      Object.keys(data).forEach(function (k) {
        fd.append(k, data[k]);
      });
    }
    return fetch(cfg.api, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) {
      if (!r.ok) throw new Error('http_' + r.status);
      return r.json();
    });
  }

  function cmMode(lang, path) {
    var ext = (path || '').split('.').pop().toLowerCase();
    var map = {
      php: 'php', js: 'javascript', jsx: 'javascript', mjs: 'javascript',
      ts: 'javascript', tsx: 'javascript', css: 'css', scss: 'css', less: 'css',
      html: 'htmlmixed', htm: 'htmlmixed', vue: 'htmlmixed', twig: 'htmlmixed',
      json: { name: 'javascript', json: true }, xml: 'xml', svg: 'xml',
      sql: 'sql', md: 'markdown', yml: 'yaml', yaml: 'yaml', sh: 'shell', bash: 'shell',
    };
    if (ext === 'htaccess' || (path && path.indexOf('wp-config') >= 0)) return 'php';
    return map[ext] || map[lang] || 'text/plain';
  }

  function ensureEditor() {
    if (cm || !window.CodeMirror) return cm;
    var mount = root.querySelector('[data-fm-editor]');
    if (!mount) return null;
    if (!document.getElementById('hs-fm-cm-theme')) {
      var st = document.createElement('style');
      st.id = 'hs-fm-cm-theme';
      st.textContent = '.cm-s-hs-fm.CodeMirror{background:#1e1e1e;color:#d4d4d4}.cm-s-hs-fm .CodeMirror-gutters{background:#252526;border-right:1px solid #333}.cm-s-hs-fm .CodeMirror-linenumber{color:#858585}';
      document.head.appendChild(st);
    }
    cm = window.CodeMirror(mount, {
      value: '',
      lineNumbers: true,
      lineWrapping: true,
      theme: 'hs-fm',
      tabSize: 2,
      indentUnit: 2,
      extraKeys: {
        'Ctrl-S': function () { saveEditor(); return false; },
        'Cmd-S': function () { saveEditor(); return false; },
      },
    });
    cm.on('change', function () {
      var tab = getTab(state.activeTab);
      if (!tab || tab.mode !== 'editor') return;
      tab.dirty = cm.getValue() !== tab.saved;
      var saveBtn = root.querySelector('[data-fm-save]');
      if (saveBtn) saveBtn.disabled = !tab.dirty;
      renderTabs();
    });
    setTimeout(function () { if (cm) cm.refresh(); }, 50);
    return cm;
  }

  function toast(msg, type) {
    var el = root.querySelector('[data-fm-toast]');
    if (!el) return;
    el.textContent = msg;
    el.className = 'hs-fm-toast show' + (type ? ' hs-fm-toast-' + type : '');
    clearTimeout(toast._t);
    toast._t = setTimeout(function () {
      el.classList.remove('show');
    }, 3200);
  }

  function modalBackdrop() {
    return root.querySelector('[data-fm-modal-backdrop]');
  }

  function isModalOpen() {
    var backdrop = modalBackdrop();
    return !!(backdrop && !backdrop.hidden);
  }

  function showModal(title, value, okLabel, onOk, msg) {
    var backdrop = modalBackdrop();
    var titleEl = root.querySelector('[data-fm-modal-title]');
    var input = root.querySelector('[data-fm-modal-input]');
    var msgEl = root.querySelector('[data-fm-modal-msg]');
    var okBtn = root.querySelector('[data-fm-modal-ok]');
    if (!backdrop || !titleEl || !input || !okBtn) return;
    titleEl.textContent = title || t('fm_ok');
    input.value = value || '';
    var confirmMode = !!msg;
    input.hidden = confirmMode;
    if (msgEl) {
      msgEl.hidden = !confirmMode;
      msgEl.textContent = confirmMode ? msg : '';
    }
    okBtn.textContent = okLabel || t('fm_ok');
    modalCb = typeof onOk === 'function' ? onOk : null;
    backdrop.hidden = false;
    root.classList.add('hs-fm-modal-open');
    if (!confirmMode) {
      setTimeout(function () {
        input.focus();
        input.select();
      }, 50);
    } else if (okBtn.focus) {
      setTimeout(function () {
        okBtn.focus();
      }, 50);
    }
  }

  function hideModal() {
    var backdrop = modalBackdrop();
    if (backdrop) backdrop.hidden = true;
    root.classList.remove('hs-fm-modal-open');
    modalCb = null;
  }

  function iconClass(icon, dir) {
    if (dir) return 'fa-solid fa-folder';
    var map = {
      php: 'fa-file-code',
      js: 'fa-file-code',
      css: 'fa-file-code',
      html: 'fa-file-code',
      json: 'fa-file-code',
      image: 'fa-file-image',
      archive: 'fa-file-zipper',
      database: 'fa-database',
      text: 'fa-file-lines',
    };
    return 'fa-solid ' + (map[icon] || 'fa-file');
  }

  function breadcrumbs() {
    var parts = state.path ? state.path.split('/') : [];
    var html =
      '<button type="button" class="hs-fm-crumb" data-path=""><i class="fa-solid fa-house"></i> ' + esc(cfg.rootLabel) + '</button>';
    var acc = '';
    parts.forEach(function (p) {
      acc = acc ? acc + '/' + p : p;
      html += '<span class="hs-fm-crumb-sep">/</span><button type="button" class="hs-fm-crumb" data-path="' + esc(acc) + '">' + esc(p) + '</button>';
    });
    return html;
  }

  function renderList() {
    var list = root.querySelector('[data-fm-list]');
    if (!list) return;
    var q = state.search.toLowerCase();
    var rows = state.entries.filter(function (e) {
      return !q || e.name.toLowerCase().indexOf(q) >= 0;
    });
    if (state.path) {
      rows.unshift({ name: '..', path: state.parent || '', dir: true, parent: true });
    }
    if (rows.length === 0) {
      list.innerHTML = '<div class="hs-fm-empty"><i class="fa-solid fa-folder-open"></i><p>' + esc(t('fm_empty')) + '</p></div>';
      return;
    }
    if (state.view === 'grid') {
      list.innerHTML =
        '<div class="hs-fm-grid">' +
        rows
          .map(function (e) {
            var sel = state.selected === e.path ? ' selected' : '';
            return (
              '<button type="button" class="hs-fm-grid-item' +
              sel +
              '" data-entry="' +
              esc(e.path) +
              '" data-dir="' +
              (e.dir ? '1' : '0') +
              '" data-parent="' +
              (e.parent ? '1' : '0') +
              '" data-preview="' +
              (e.preview ? '1' : '0') +
              '" data-editable="' +
              (e.editable ? '1' : '0') +
              '" data-archive="' +
              (e.archive ? '1' : '0') +
              '"><i class="' +
              iconClass(e.icon, e.dir) +
              '"></i><span>' +
              esc(e.name) +
              '</span></button>'
            );
          })
          .join('') +
        '</div>';
      return;
    }
    list.innerHTML =
      '<table class="hs-fm-table"><thead><tr><th>' +
      esc(t('fm_name')) +
      '</th><th>' +
      esc(t('fm_size')) +
      '</th><th>' +
      esc(t('fm_perms')) +
      '</th><th>' +
      esc(t('fm_modified')) +
      '</th><th></th></tr></thead><tbody>' +
      rows
        .map(function (e) {
          var sel = state.selected === e.path ? ' class="selected"' : '';
          return (
            '<tr' +
            sel +
            ' data-entry="' +
            esc(e.path) +
            '" data-dir="' +
            (e.dir ? '1' : '0') +
            '" data-parent="' +
            (e.parent ? '1' : '0') +
            '" data-preview="' +
            (e.preview ? '1' : '0') +
            '" data-editable="' +
            (e.editable ? '1' : '0') +
            '" data-archive="' +
            (e.archive ? '1' : '0') +
            '"><td><i class="' +
            iconClass(e.icon, e.dir) +
            '"></i> ' +
            esc(e.name) +
            '</td><td>' +
            esc(e.size_label || '—') +
            '</td><td><code class="hs-fm-perm">' +
            esc(e.perms || '—') +
            '</code></td><td>' +
            esc(e.modified_label || '—') +
            '</td><td class="hs-fm-row-actions">' +
            (e.parent
              ? ''
              : '<button type="button" data-act="dl" title="' +
                esc(t('fm_download')) +
                '"><i class="fa-solid fa-download"></i></button><button type="button" data-act="dup" title="' +
                esc(t('fm_duplicate')) +
                '"><i class="fa-solid fa-copy"></i></button><button type="button" data-act="ren" title="' +
                esc(t('fm_rename')) +
                '"><i class="fa-solid fa-pen"></i></button><button type="button" data-act="del" title="' +
                esc(t('fm_delete')) +
                '"><i class="fa-solid fa-trash"></i></button>') +
            '</td></tr>'
          );
        })
        .join('') +
      '</tbody></table>';
  }

  function renderTree(nodes, depth) {
    depth = depth || 0;
    return (
      '<ul class="hs-fm-tree' +
      (depth ? '' : ' hs-fm-tree-root') +
      '">' +
      nodes
        .map(function (n) {
          var open = state.path === n.path || state.path.indexOf(n.path + '/') === 0;
          var hasKids = n.children && n.children.length;
          return (
            '<li class="hs-fm-tree-node' +
            (open ? ' open' : '') +
            '">' +
            (hasKids
              ? '<button type="button" class="hs-fm-tree-toggle" data-tree-toggle="' +
                esc(n.path) +
                '" aria-label="Toggle"><i class="fa-solid fa-chevron-right"></i></button>'
              : '<span class="hs-fm-tree-spacer"></span>') +
            '<button type="button" class="hs-fm-tree-item' +
            (state.path === n.path ? ' active' : '') +
            '" data-path="' +
            esc(n.path) +
            '"><i class="fa-solid fa-folder"></i> ' +
            esc(n.name) +
            '</button>' +
            (hasKids && open ? renderTree(n.children, depth + 1) : '') +
            '</li>'
          );
        })
        .join('') +
      '</ul>'
    );
  }

  function loadTree() {
    return fetchJson(apiUrl('tree'))
      .then(function (data) {
        if (data.ok && data.tree) {
          var el = root.querySelector('[data-fm-tree]');
          if (el) el.innerHTML = renderTree(data.tree);
        }
      })
      .catch(function () {
        /* tree is optional */
      });
  }

  function setWorkspace(open) {
    var pane = root.querySelector('[data-fm-pane]');
    var split = root.querySelector('[data-fm-resizer-split]');
    var listPane = root.querySelector('[data-fm-list-pane]');
    if (!pane) return;
    if (open) {
      pane.hidden = false;
      if (split) split.hidden = false;
      root.classList.add('hs-fm-workspace-open');
      applySplit();
    } else {
      pane.hidden = true;
      if (split) split.hidden = true;
      root.classList.remove('hs-fm-workspace-open');
      if (listPane) listPane.style.flex = '';
    }
  }

  function applySidebar() {
    var sb = root.querySelector('[data-fm-sidebar]');
    if (sb) sb.style.width = state.sidebarW + 'px';
  }

  function applySplit() {
    var listPane = root.querySelector('[data-fm-list-pane]');
    var pane = root.querySelector('[data-fm-pane]');
    if (!listPane || !pane || pane.hidden) return;
    listPane.style.flex = '0 0 ' + state.splitH + '%';
    pane.style.flex = '1 1 auto';
  }

  function renderTabs() {
    var bar = root.querySelector('[data-fm-tabs]');
    if (!bar) return;
    if (state.tabs.length === 0) {
      bar.innerHTML = '';
      return;
    }
    bar.innerHTML = state.tabs
      .map(function (tab) {
        var active = tab.path === state.activeTab ? ' active' : '';
        var dirty = tab.dirty ? ' dirty' : '';
        var mode = tab.mode === 'preview' ? ' <i class="fa-solid fa-image"></i>' : '';
        return (
          '<button type="button" class="hs-fm-tab' +
          active +
          dirty +
          '" data-tab="' +
          esc(tab.path) +
          '"><span>' +
          esc(tab.name) +
          mode +
          '</span><i class="fa-solid fa-xmark hs-fm-tab-close" data-tab-close="' +
          esc(tab.path) +
          '"></i></button>'
        );
      })
      .join('');
  }

  function getTab(path) {
    for (var i = 0; i < state.tabs.length; i++) {
      if (state.tabs[i].path === path) return state.tabs[i];
    }
    return null;
  }

  function syncEditorFromTab() {
    var tab = getTab(state.activeTab);
    var mount = root.querySelector('[data-fm-editor]');
    var preview = root.querySelector('[data-fm-preview]');
    var saveBtn = root.querySelector('[data-fm-save]');
    var chmodBtn = root.querySelector('[data-fm-chmod-btn]');
    if (!tab) {
      if (cm) cm.setValue('');
      if (preview) preview.hidden = true;
      if (mount) mount.hidden = true;
      if (saveBtn) saveBtn.disabled = true;
      if (chmodBtn) chmodBtn.hidden = true;
      return;
    }
    if (tab.mode === 'preview') {
      if (mount) mount.hidden = true;
      if (preview) preview.hidden = false;
      if (saveBtn) saveBtn.disabled = true;
      if (chmodBtn) chmodBtn.hidden = true;
      if (preview) preview.innerHTML = tab.previewHtml || '';
      return;
    }
    if (preview) preview.hidden = true;
    if (mount) mount.hidden = false;
    if (saveBtn) saveBtn.disabled = !tab.dirty;
    if (chmodBtn) {
      chmodBtn.hidden = false;
      chmodBtn.dataset.path = tab.path;
      chmodBtn.dataset.perms = tab.perms || '';
    }
    var editorInst = ensureEditor();
    if (editorInst) {
      editorInst.setOption('mode', cmMode(tab.language, tab.path));
      if (editorInst.getValue() !== (tab.content || '')) {
        editorInst.setValue(tab.content || '');
      }
      setTimeout(function () {
        editorInst.refresh();
      }, 10);
    }
  }

  function activateTab(path) {
    var tab = getTab(path);
    if (!tab) return;
    if (state.activeTab && state.activeTab !== path) {
      var cur = getTab(state.activeTab);
      if (cur && cur.mode === 'editor' && cm) {
        cur.content = cm.getValue();
      }
    }
    state.activeTab = path;
    renderTabs();
    syncEditorFromTab();
    setWorkspace(true);
  }

  function closeTab(path, force) {
    var tab = getTab(path);
    if (!tab) return;
    if (!force && tab.dirty && tab.mode === 'editor') {
      if (!confirm(t('fm_unsaved'))) return;
    }
    state.tabs = state.tabs.filter(function (x) {
      return x.path !== path;
    });
    if (state.activeTab === path) {
      state.activeTab = state.tabs.length ? state.tabs[state.tabs.length - 1].path : null;
    }
    renderTabs();
    if (state.tabs.length === 0) {
      setWorkspace(false);
    } else {
      syncEditorFromTab();
    }
  }

  function openPreview(path) {
    fetchJson(apiUrl('preview', { path: path }))
      .then(function (data) {
        if (!data.ok) {
          toast(t('fm_no_preview'), 'warn');
          return;
        }
        var html = '';
        if (data.svg && data.content) {
          var svg64 = btoa(unescape(encodeURIComponent(data.content)));
          html = '<img src="data:image/svg+xml;base64,' + svg64 + '" alt="">';
        } else {
          html = '<img src="data:' + data.mime + ';base64,' + data.data + '" alt="">';
        }
        var existing = getTab(path);
        if (existing) {
          existing.mode = 'preview';
          existing.previewHtml = html;
          existing.dirty = false;
        } else {
          state.tabs.push({
            path: path,
            name: path.split('/').pop(),
            mode: 'preview',
            previewHtml: html,
            dirty: false,
          });
        }
        activateTab(path);
      })
      .catch(function () {
        toast(t('fm_error'), 'err');
      });
  }

  function openEditor(path) {
    fetchJson(apiUrl('read', { path: path }))
      .then(function (data) {
        if (!data.ok) {
          toast(data.error === 'too_large' ? t('fm_too_large') : t('fm_error'), 'err');
          return;
        }
        if (data.binary) {
          toast(t('fm_binary'), 'warn');
          actDownload(path);
          return;
        }
        if (!ensureEditor()) {
          toast(t('fm_error'), 'err');
          return;
        }
        var existing = getTab(path);
        if (existing) {
          existing.mode = 'editor';
          existing.content = data.content || '';
          existing.saved = data.content || '';
          existing.language = data.language || 'plaintext';
          existing.perms = data.perms || '';
          existing.dirty = false;
        } else {
          state.tabs.push({
            path: path,
            name: path.split('/').pop(),
            mode: 'editor',
            content: data.content || '',
            saved: data.content || '',
            language: data.language || 'plaintext',
            perms: data.perms || '',
            dirty: false,
          });
        }
        activateTab(path);
        if (cm) {
          setTimeout(function () {
            cm.refresh();
            cm.focus();
          }, 50);
        }
      })
      .catch(function () {
        toast(t('fm_error'), 'err');
      });
  }

  function saveEditor() {
    var tab = getTab(state.activeTab);
    if (!tab || tab.mode !== 'editor' || !cm) return;
    var content = cm.getValue();
    post('write', { path: tab.path, content: content }).then(function (data) {
      if (data.ok) {
        tab.content = content;
        tab.saved = content;
        tab.dirty = false;
        root.querySelector('[data-fm-save]').disabled = true;
        toast(t('fm_saved'), 'ok');
        renderTabs();
        navigate(state.path);
      } else {
        toast(t('fm_error'), 'err');
      }
    });
  }

  function navigate(path) {
    state.path = path || '';
    state.selected = null;
    return fetchJson(apiUrl('list', { path: state.path }))
      .then(function (data) {
        if (!data.ok) {
          toast(t('fm_error'), 'err');
          return;
        }
        state.entries = data.entries || [];
        state.parent = data.parent || '';
        var bc = root.querySelector('[data-fm-bc]');
        if (bc) bc.innerHTML = breadcrumbs();
        var disk = root.querySelector('[data-fm-disk]');
        if (disk && data.disk_used_mb !== undefined) {
          disk.textContent = t('fm_disk') + ': ' + Math.round(data.disk_used_mb * 10) / 10 + ' MB';
        }
        renderList();
        loadTree();
      })
      .catch(function () {
        toast(t('fm_error'), 'err');
      });
  }

  function uploadFiles(files) {
    if (!files || !files.length) return;
    var bar = root.querySelector('[data-fm-upload-bar]');
    var fill = root.querySelector('[data-fm-upload-fill]');
    var label = root.querySelector('[data-fm-upload-label]');
    var total = files.length;
    var done = 0;
    if (bar) bar.hidden = false;

    function updateProgress(pct, name) {
      if (fill) fill.style.width = pct + '%';
      if (label) label.textContent = t('fm_uploading') + ' ' + name + ' (' + done + '/' + total + ')';
    }

    function uploadOne(file) {
      return new Promise(function (resolve) {
        var xhr = new XMLHttpRequest();
        var fd = new FormData();
        fd.append('action', 'upload');
        fd.append('csrf', cfg.csrf);
        fd.append('path', state.path);
        fd.append('file', file);
        xhr.upload.onprogress = function (e) {
          if (e.lengthComputable) {
            var pct = Math.round((done / total + e.loaded / e.total / total) * 100);
            updateProgress(pct, file.name);
          }
        };
        xhr.onload = function () {
          done++;
          updateProgress(Math.round((done / total) * 100), file.name);
          resolve();
        };
        xhr.onerror = function () {
          done++;
          toast(t('fm_error'), 'err');
          resolve();
        };
        xhr.open('POST', cfg.api);
        xhr.withCredentials = true;
        xhr.send(fd);
      });
    }

    var chain = Promise.resolve();
    Array.prototype.forEach.call(files, function (file) {
      chain = chain.then(function () {
        return uploadOne(file);
      });
    });
    chain.then(function () {
      if (bar) bar.hidden = true;
      toast(t('fm_uploaded'), 'ok');
      navigate(state.path);
    });
  }

  function actDelete(path) {
    showModal(t('fm_confirm_delete_title'), '', t('fm_delete'), function () {
      post('delete', { path: path }).then(function (data) {
        hideModal();
        if (data.ok) {
          toast(t('fm_deleted'), 'ok');
          closeTab(path, true);
          navigate(state.path);
        } else toast(t('fm_error'), 'err');
      });
    }, t('fm_confirm_delete'));
  }

  function actRename(path) {
    var name = path.split('/').pop();
    showModal(t('fm_rename_title'), name, t('fm_ok'), function (val) {
      if (!val || val === name) {
        hideModal();
        return;
      }
      post('rename', { path: path, name: val }).then(function (data) {
        hideModal();
        if (data.ok) {
          toast(t('fm_renamed'), 'ok');
          var tab = getTab(path);
          if (tab) {
            tab.path = data.path || val;
            tab.name = val;
            if (state.activeTab === path) state.activeTab = tab.path;
          }
          navigate(state.path);
          renderTabs();
        } else toast(t('fm_error'), 'err');
      });
    });
  }

  function actDuplicate(path) {
    post('duplicate', { path: path }).then(function (data) {
      if (data.ok) {
        toast(t('fm_duplicated'), 'ok');
        navigate(state.path);
      } else toast(t('fm_error'), 'err');
    });
  }

  function actChmod(path, perms) {
    showModal(t('fm_chmod_title'), perms || '0644', t('fm_ok'), function (val) {
      if (!val) {
        hideModal();
        return;
      }
      post('chmod', { path: path, mode: val }).then(function (data) {
        hideModal();
        if (data.ok) {
          toast(t('fm_saved'), 'ok');
          var tab = getTab(path);
          if (tab) tab.perms = data.perms || val;
          navigate(state.path);
        } else toast(t('fm_error'), 'err');
      });
    });
  }

  function actDownload(path) {
    window.location.href = apiUrl('download', { path: path });
  }

  function defaultZipName(path, isDir) {
    var name = path.split('/').pop() || 'archive';
    if (!isDir && /\.zip$/i.test(name)) {
      return name.replace(/\.zip$/i, '') + '-archive.zip';
    }
    return name + '.zip';
  }

  function actArchive(path, isDir) {
    showModal(t('fm_archive_title'), defaultZipName(path, isDir), t('fm_create'), function (val) {
      if (!val) {
        hideModal();
        return;
      }
      post('archive', { path: path, name: val }).then(function (data) {
        hideModal();
        if (data.ok) {
          toast(t('fm_archived'), 'ok');
          navigate(state.path);
        } else {
          toast(data.error === 'too_large' ? t('fm_archive_too_large') : t('fm_error'), 'err');
        }
      });
    });
  }

  function actExtract(path) {
    showModal(t('fm_extract_title'), '', t('fm_extract'), function () {
      post('extract', { path: path }).then(function (data) {
        hideModal();
        if (data.ok) {
          toast(t('fm_extracted'), 'ok');
          if (data.path) {
            var parts = data.path.split('/');
            navigate(parts.length > 1 ? parts.slice(0, -1).join('/') : '');
          } else {
            navigate(state.path);
          }
        } else {
          toast(data.error === 'not_archive' ? t('fm_not_archive') : t('fm_error'), 'err');
        }
      });
    }, t('fm_confirm_extract'));
  }

  function showCtx(x, y, path, isDir, isArchive) {
    var ctx = root.querySelector('[data-fm-ctx]');
    if (!ctx) return;
    state.ctxPath = path;
    state.ctxDir = isDir;
    state.ctxArchive = isArchive;
    var items;
    if (isArchive) {
      items = [
        { act: 'unzip', label: t('fm_extract'), icon: 'fa-box-open' },
        { act: 'dl', label: t('fm_download'), icon: 'fa-download' },
        { act: 'ren', label: t('fm_rename'), icon: 'fa-pen' },
        { act: 'dup', label: t('fm_duplicate'), icon: 'fa-copy' },
        { act: 'chmod', label: t('fm_chmod'), icon: 'fa-lock' },
        { act: 'del', label: t('fm_delete'), icon: 'fa-trash' },
      ];
    } else if (isDir) {
      items = [
        { act: 'open', label: t('fm_name'), icon: 'fa-folder-open' },
        { act: 'zip', label: t('fm_archive'), icon: 'fa-file-zipper' },
        { act: 'ren', label: t('fm_rename'), icon: 'fa-pen' },
        { act: 'dup', label: t('fm_duplicate'), icon: 'fa-copy' },
        { act: 'chmod', label: t('fm_chmod'), icon: 'fa-lock' },
        { act: 'del', label: t('fm_delete'), icon: 'fa-trash' },
      ];
    } else {
      items = [
        { act: 'open', label: t('fm_editor'), icon: 'fa-file-code' },
        { act: 'dl', label: t('fm_download'), icon: 'fa-download' },
        { act: 'zip', label: t('fm_archive'), icon: 'fa-file-zipper' },
        { act: 'ren', label: t('fm_rename'), icon: 'fa-pen' },
        { act: 'dup', label: t('fm_duplicate'), icon: 'fa-copy' },
        { act: 'chmod', label: t('fm_chmod'), icon: 'fa-lock' },
        { act: 'del', label: t('fm_delete'), icon: 'fa-trash' },
      ];
    }
    ctx.innerHTML = items
      .map(function (it) {
        return (
          '<button type="button" data-ctx="' +
          it.act +
          '"><i class="fa-solid ' +
          it.icon +
          '"></i> ' +
          esc(it.label) +
          '</button>'
        );
      })
      .join('');
    ctx.hidden = false;
    ctx.style.left = Math.min(x, window.innerWidth - 200) + 'px';
    ctx.style.top = Math.min(y, window.innerHeight - 280) + 'px';
  }

  function hideCtx() {
    var ctx = root.querySelector('[data-fm-ctx]');
    if (ctx) ctx.hidden = true;
  }

  function onEntryClick(row) {
    var path = row.getAttribute('data-entry');
    var isDir = row.getAttribute('data-dir') === '1';
    var isParent = row.getAttribute('data-parent') === '1';
    if (isParent) {
      navigate(path);
      return;
    }
    if (isDir) {
      navigate(path);
      return;
    }
    state.selected = path;
    renderList();
    if (row.getAttribute('data-preview') === '1') {
      openPreview(path);
    } else if (row.getAttribute('data-editable') === '1') {
      openEditor(path);
    } else {
      toast(t('fm_binary'), 'warn');
    }
  }

  function setupResizers() {
    var sbResizer = root.querySelector('[data-fm-resizer-sidebar]');
    var splitResizer = root.querySelector('[data-fm-resizer-split]');
    applySidebar();

    function drag(el, onMove, onEnd) {
      var startX, startY, moved;
      function mm(e) {
        moved = true;
        onMove(e.clientX - startX, e.clientY - startY);
      }
      function mu() {
        document.removeEventListener('mousemove', mm);
        document.removeEventListener('mouseup', mu);
        if (moved && onEnd) onEnd();
      }
      el.addEventListener('mousedown', function (e) {
        e.preventDefault();
        startX = e.clientX;
        startY = e.clientY;
        moved = false;
        document.addEventListener('mousemove', mm);
        document.addEventListener('mouseup', mu);
      });
    }

    if (sbResizer) {
      var startW = state.sidebarW;
      drag(
        sbResizer,
        function (dx) {
          state.sidebarW = Math.max(160, Math.min(420, startW + dx));
          applySidebar();
        },
        function () {
          localStorage.setItem('hs_fm_sidebar', String(state.sidebarW));
        }
      );
      sbResizer.addEventListener('mousedown', function () {
        startW = state.sidebarW;
      });
    }

    if (splitResizer) {
      var ws = root.querySelector('[data-fm-workspace]');
      drag(
        splitResizer,
        function (_, dy) {
          if (!ws) return;
          var rect = ws.getBoundingClientRect();
          var pct = ((dy / rect.height) * 100);
          state.splitH = Math.max(22, Math.min(72, state.splitH + pct));
          applySplit();
        },
        function () {
          localStorage.setItem('hs_fm_split', String(Math.round(state.splitH)));
        }
      );
    }
  }

  root.addEventListener('click', function (e) {
    if (isModalOpen()) {
      var backdrop = modalBackdrop();
      if (e.target.closest('[data-fm-modal-cancel]')) {
        hideModal();
        return;
      }
      var modalOk = e.target.closest('[data-fm-modal-ok]');
      if (modalOk && modalCb) {
        var input = root.querySelector('[data-fm-modal-input]');
        modalCb(input && !input.hidden ? input.value.trim() : true);
        return;
      }
      if (backdrop && (e.target === backdrop || !e.target.closest('.hs-fm-modal'))) {
        hideModal();
        return;
      }
      return;
    }
    hideCtx();
    var tabClose = e.target.closest('[data-tab-close]');
    if (tabClose) {
      e.stopPropagation();
      closeTab(tabClose.getAttribute('data-tab-close'));
      return;
    }
    var tabBtn = e.target.closest('[data-tab]');
    if (tabBtn && !tabClose) {
      activateTab(tabBtn.getAttribute('data-tab'));
      return;
    }
    var ctxBtn = e.target.closest('[data-ctx]');
    if (ctxBtn && state.ctxPath) {
      var act = ctxBtn.getAttribute('data-ctx');
      var p = state.ctxPath;
      var d = state.ctxDir;
      if (act === 'open') {
        if (d) navigate(p);
        else openEditor(p);
      }
      if (act === 'dl') actDownload(p);
      if (act === 'zip') actArchive(p, d);
      if (act === 'unzip') actExtract(p);
      if (act === 'ren') actRename(p);
      if (act === 'dup') actDuplicate(p);
      if (act === 'chmod') {
        var entry = state.entries.find(function (x) {
          return x.path === p;
        });
        actChmod(p, entry ? entry.perms : '0644');
      }
      if (act === 'del') actDelete(p);
      return;
    }
    var btn = e.target.closest('[data-path]');
    if (btn && btn.classList.contains('hs-fm-crumb')) {
      navigate(btn.getAttribute('data-path') || '');
      return;
    }
    if (btn && btn.classList.contains('hs-fm-tree-item')) {
      navigate(btn.getAttribute('data-path') || '');
      return;
    }
    var toggle = e.target.closest('[data-tree-toggle]');
    if (toggle) {
      var li = toggle.closest('.hs-fm-tree-node');
      if (li) li.classList.toggle('open');
      return;
    }
    var row = e.target.closest('[data-entry]');
    if (row) {
      var actEl = e.target.closest('[data-act]');
      var path = row.getAttribute('data-entry');
      if (actEl) {
        e.stopPropagation();
        var a = actEl.getAttribute('data-act');
        if (a === 'del') actDelete(path);
        if (a === 'ren') actRename(path);
        if (a === 'dl') actDownload(path);
        if (a === 'dup') actDuplicate(path);
        return;
      }
      onEntryClick(row);
      return;
    }
    var tb = e.target.closest('[data-fm-action]');
    if (!tb) return;
    var action = tb.getAttribute('data-fm-action');
    if (action === 'mkdir') {
      showModal(t('fm_new_folder_title'), '', t('fm_create'), function (val) {
        if (!val) {
          hideModal();
          return;
        }
        post('mkdir', { path: state.path, name: val }).then(function (d) {
          hideModal();
          if (d.ok) {
            toast(t('fm_created'), 'ok');
            navigate(state.path);
          } else toast(t('fm_error'), 'err');
        });
      });
    }
    if (action === 'newfile') {
      showModal(t('fm_new_file_title'), 'index.html', t('fm_create'), function (val) {
        if (!val) {
          hideModal();
          return;
        }
        post('create', { path: state.path, name: val }).then(function (d) {
          hideModal();
          if (d.ok) {
            toast(t('fm_created'), 'ok');
            navigate(state.path).then(function () {
              openEditor(d.path);
            });
          } else toast(t('fm_error'), 'err');
        });
      });
    }
    if (action === 'upload') root.querySelector('[data-fm-file-input]').click();
    if (action === 'refresh') navigate(state.path);
    if (action === 'view-list') {
      state.view = 'list';
      localStorage.setItem('hs_fm_view', 'list');
      root.querySelector('[data-fm-view-list]')?.classList.add('active');
      root.querySelector('[data-fm-view-grid]')?.classList.remove('active');
      renderList();
    }
    if (action === 'view-grid') {
      state.view = 'grid';
      localStorage.setItem('hs_fm_view', 'grid');
      root.querySelector('[data-fm-view-grid]')?.classList.add('active');
      root.querySelector('[data-fm-view-list]')?.classList.remove('active');
      renderList();
    }
    if (action === 'close-pane') {
      if (state.tabs.some(function (x) {
        return x.dirty;
      })) {
        if (!confirm(t('fm_unsaved'))) return;
      }
      state.tabs = [];
      state.activeTab = null;
      renderTabs();
      setWorkspace(false);
    }
    if (action === 'save') saveEditor();
    if (action === 'chmod') {
      var tab = getTab(state.activeTab);
      if (tab) actChmod(tab.path, tab.perms || '0644');
    }
  });

  root.addEventListener('contextmenu', function (e) {
    var row = e.target.closest('[data-entry]');
    if (!row || row.getAttribute('data-parent') === '1') return;
    e.preventDefault();
    showCtx(
      e.clientX,
      e.clientY,
      row.getAttribute('data-entry'),
      row.getAttribute('data-dir') === '1',
      row.getAttribute('data-archive') === '1'
    );
  });

  var searchInput = root.querySelector('[data-fm-search]');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      state.search = searchInput.value;
      renderList();
    });
  }

  var fileInput = root.querySelector('[data-fm-file-input]');
  if (fileInput) {
    fileInput.addEventListener('change', function () {
      uploadFiles(fileInput.files);
      fileInput.value = '';
    });
  }

  var drop = root.querySelector('[data-fm-drop]');
  if (drop) {
    ['dragenter', 'dragover'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) {
        e.preventDefault();
        root.classList.add('hs-fm-drag');
      });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) {
        e.preventDefault();
        root.classList.remove('hs-fm-drag');
      });
    });
    drop.addEventListener('drop', function (e) {
      uploadFiles(e.dataTransfer.files);
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isModalOpen()) {
      e.preventDefault();
      hideModal();
      return;
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 's' && state.activeTab) {
      e.preventDefault();
      saveEditor();
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'w' && state.activeTab) {
      e.preventDefault();
      closeTab(state.activeTab);
    }
  });

  document.addEventListener('click', hideCtx);

  root.querySelector('[data-fm-view-' + state.view + ']')?.classList.add('active');
  setupResizers();
  hideModal();
  hideCtx();
  navigate(cfg.startPath || '').catch(function () {});
})();