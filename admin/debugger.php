<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-debugger.php';

hs_admin_require();
$admin_active = 'debugger';
$page_title = $t['admin_debugger_title'] ?? 'Site debugger';

// Actions (POST)
$flash = '';
$flashType = 'info';
$report = null;
$viewId = trim((string) ($_GET['report'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hs_csrf_verify($_POST['csrf'] ?? null)) {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'run') {
        @set_time_limit(120);
        $report = hs_debug_run_full(true);
        $save = !empty($_POST['save']);
        if ($save) {
            $res = hs_debug_report_save($report);
            if (!empty($res['ok'])) {
                $flash = ($t['admin_debugger_saved'] ?? 'Report saved') . ': ' . ($res['id'] ?? '');
                $flashType = 'success';
                $viewId = (string) ($res['id'] ?? '');
            } else {
                $flash = ($t['admin_debugger_save_fail'] ?? 'Save failed') . ': ' . ($res['error'] ?? '');
                $flashType = 'error';
            }
        } else {
            $flash = $t['admin_debugger_ran'] ?? 'Full scan finished (not saved). Click Save to store.';
            $flashType = !empty($report['summary']['ok']) ? 'success' : 'warn';
        }
    } elseif ($action === 'import_logs') {
        $imp = hs_debug_import_error_logs();
        $flash = ($t['admin_debugger_imported'] ?? 'Imported from error_log') . ': ' . (int) $imp['imported'];
        $flashType = 'success';
    } elseif ($action === 'clear_errors') {
        hs_debug_errors_clear();
        $flash = $t['admin_debugger_errors_cleared'] ?? 'Stored errors cleared.';
        $flashType = 'success';
    } elseif ($action === 'delete_report') {
        $id = trim((string) ($_POST['report_id'] ?? ''));
        hs_debug_report_delete($id);
        $flash = $t['admin_debugger_report_deleted'] ?? 'Report deleted.';
        $flashType = 'success';
        if ($viewId === $id) {
            $viewId = '';
        }
    } elseif ($action === 'save_current' && is_string($_POST['report_json'] ?? null)) {
        $decoded = json_decode((string) $_POST['report_json'], true);
        if (is_array($decoded)) {
            $res = hs_debug_report_save($decoded);
            $flash = !empty($res['ok'])
                ? (($t['admin_debugger_saved'] ?? 'Report saved') . ': ' . ($res['id'] ?? ''))
                : (($t['admin_debugger_save_fail'] ?? 'Save failed'));
            $flashType = !empty($res['ok']) ? 'success' : 'error';
            if (!empty($res['id'])) {
                $viewId = (string) $res['id'];
                $report = $decoded;
            }
        }
    }
}

if ($viewId !== '' && $report === null) {
    $report = hs_debug_report_load($viewId);
}

// Download
if (isset($_GET['download']) && $viewId !== '') {
    $rep = hs_debug_report_load($viewId);
    if ($rep !== null) {
        $fmt = (string) ($_GET['download'] ?? 'json');
        if ($fmt === 'txt') {
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $viewId . '.txt"');
            echo hs_debug_report_to_text($rep);
            exit;
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $viewId . '.json"');
        echo json_encode($rep, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

$errors = array_reverse(hs_debug_errors_load());
$reports = hs_debug_reports_list();
$apiUrl = hs_admin_url('debugger-api.php');

ob_start();
?>
<div class="hs-admin-page hs-admin-debugger">
  <div class="hs-dbg-head">
    <div>
      <h1 style="margin:0 0 .35rem"><i class="fa-solid fa-bug"></i> <?= hs_h($page_title) ?></h1>
      <p class="hp-muted" style="margin:0"><?= hs_h($t['admin_debugger_lead'] ?? 'Full site health: structure, PHP files, public URLs, APIs, error store, and downloadable reports.') ?></p>
    </div>
    <div class="hs-dbg-actions">
      <form method="post" class="hs-dbg-inline-form">
        <?= hs_csrf_field() ?>
        <input type="hidden" name="action" value="run">
        <input type="hidden" name="save" value="1">
        <button type="submit" class="hs-btn hs-btn-primary" data-hs-dbg-run>
          <i class="fa-solid fa-play"></i> <?= hs_h($t['admin_debugger_run_save'] ?? 'Run full scan & save') ?>
        </button>
      </form>
      <form method="post" class="hs-dbg-inline-form">
        <?= hs_csrf_field() ?>
        <input type="hidden" name="action" value="run">
        <button type="submit" class="hs-btn hs-btn-ghost">
          <i class="fa-solid fa-stethoscope"></i> <?= hs_h($t['admin_debugger_run'] ?? 'Run scan only') ?>
        </button>
      </form>
    </div>
  </div>

  <?php if ($flash !== ''): ?>
    <div class="hs-alert hs-alert-<?= hs_h($flashType === 'success' ? 'success' : ($flashType === 'error' ? 'error' : 'warn')) ?>" style="margin:1rem 0">
      <?= hs_h($flash) ?>
    </div>
  <?php endif; ?>

  <div class="hs-dbg-grid">
    <section class="hs-dbg-card">
      <h2><i class="fa-solid fa-bolt"></i> <?= hs_h($t['admin_debugger_quick'] ?? 'Quick actions') ?></h2>
      <div class="hs-dbg-quick">
        <form method="post"><?= hs_csrf_field() ?><input type="hidden" name="action" value="import_logs">
          <button class="hs-btn hs-btn-ghost" type="submit"><i class="fa-solid fa-file-import"></i> <?= hs_h($t['admin_debugger_import_logs'] ?? 'Import error_log') ?></button>
        </form>
        <form method="post" onsubmit="return confirm('Clear stored errors?');"><?= hs_csrf_field() ?><input type="hidden" name="action" value="clear_errors">
          <button class="hs-btn hs-btn-ghost" type="submit"><i class="fa-solid fa-broom"></i> <?= hs_h($t['admin_debugger_clear_errors'] ?? 'Clear error store') ?></button>
        </form>
        <a class="hs-btn hs-btn-ghost" href="<?= hs_h(hs_admin_url('tools.php')) ?>"><i class="fa-solid fa-screwdriver-wrench"></i> <?= hs_h($t['admin_tools_title'] ?? 'API & tools') ?></a>
        <a class="hs-btn hs-btn-ghost" href="<?= hs_h(hs_admin_url('server-health-api.php')) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-heart-pulse"></i> Health API</a>
      </div>
      <p class="hp-muted" style="margin:.75rem 0 0;font-size:.88rem">
        API: <code><?= hs_h($apiUrl) ?></code> (POST JSON, admin session)
      </p>
    </section>

    <section class="hs-dbg-card">
      <h2><i class="fa-solid fa-folder-open"></i> <?= hs_h($t['admin_debugger_reports'] ?? 'Saved reports') ?> (<?= count($reports) ?>)</h2>
      <?php if ($reports === []): ?>
        <p class="hp-muted"><?= hs_h($t['admin_debugger_no_reports'] ?? 'No reports yet. Run a full scan & save.') ?></p>
      <?php else: ?>
        <div class="hs-table-wrap">
          <table class="hs-table hs-dbg-table">
            <thead><tr>
              <th>ID</th><th>When</th><th>Status</th><th>ms</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
              <?php
                $rid = (string) ($r['id'] ?? '');
                $ok = !empty($r['ok']);
              ?>
              <tr class="<?= $viewId === $rid ? 'is-active' : '' ?>">
                <td><a href="<?= hs_h(hs_admin_url('debugger.php', ['report' => $rid])) ?>"><code><?= hs_h($rid) ?></code></a></td>
                <td><?= hs_h((string) ($r['created_at'] ?? '')) ?></td>
                <td><span class="hs-dbg-badge <?= $ok ? 'is-ok' : 'is-fail' ?>"><?= $ok ? 'OK' : 'ISSUES' ?></span></td>
                <td><?= (int) ($r['duration_ms'] ?? 0) ?></td>
                <td class="hs-dbg-row-actions">
                  <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('debugger.php', ['report' => $rid, 'download' => 'json'])) ?>">JSON</a>
                  <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('debugger.php', ['report' => $rid, 'download' => 'txt'])) ?>">TXT</a>
                  <form method="post" style="display:inline" onsubmit="return confirm('Delete report?');">
                    <?= hs_csrf_field() ?>
                    <input type="hidden" name="action" value="delete_report">
                    <input type="hidden" name="report_id" value="<?= hs_h($rid) ?>">
                    <button type="submit" class="hs-btn hs-btn-ghost hp-dash-btn-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <?php if (is_array($report)): ?>
    <?php
      $sum = is_array($report['summary'] ?? null) ? $report['summary'] : [];
      $allOk = !empty($sum['ok']);
    ?>
    <section class="hs-dbg-card hs-dbg-report">
      <div class="hs-dbg-report-head">
        <h2><i class="fa-solid fa-clipboard-check"></i> <?= hs_h($t['admin_debugger_last_report'] ?? 'Report') ?>
          <code><?= hs_h((string) ($report['id'] ?? '')) ?></code>
          <span class="hs-dbg-badge <?= $allOk ? 'is-ok' : 'is-fail' ?>"><?= $allOk ? 'PASS' : 'FAIL' ?></span>
        </h2>
        <div class="hs-dbg-row-actions">
          <?php if ($viewId !== ''): ?>
            <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('debugger.php', ['report' => $viewId, 'download' => 'json'])) ?>">Download JSON</a>
            <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('debugger.php', ['report' => $viewId, 'download' => 'txt'])) ?>">Download TXT</a>
          <?php else: ?>
            <form method="post" class="hs-dbg-inline-form">
              <?= hs_csrf_field() ?>
              <input type="hidden" name="action" value="save_current">
              <input type="hidden" name="report_json" value="<?= hs_h(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') ?>">
              <button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-floppy-disk"></i> <?= hs_h($t['admin_debugger_save'] ?? 'Save report') ?></button>
            </form>
          <?php endif; ?>
        </div>
      </div>
      <p class="hp-muted" style="margin:0 0 1rem">
        <?= hs_h((string) ($report['created_at'] ?? '')) ?>
        · PHP <?= hs_h((string) ($report['php'] ?? '')) ?>
        · v<?= hs_h((string) ($report['version'] ?? '')) ?>
        · <?= (int) ($report['duration_ms'] ?? 0) ?> ms
      </p>
      <div class="hs-dbg-summary">
        <div class="hs-dbg-stat <?= ((int) ($sum['structure_fail'] ?? 0)) === 0 ? 'is-ok' : 'is-fail' ?>">
          <strong><?= (int) ($sum['structure_fail'] ?? 0) ?>/<?= (int) ($sum['structure_total'] ?? 0) ?></strong>
          <span>Structure fails</span>
        </div>
        <div class="hs-dbg-stat <?= ((int) ($sum['syntax_fail'] ?? 0)) === 0 ? 'is-ok' : 'is-fail' ?>">
          <strong><?= (int) ($sum['syntax_fail'] ?? 0) ?>/<?= (int) ($sum['syntax_total'] ?? 0) ?></strong>
          <span>PHP syntax fails</span>
        </div>
        <div class="hs-dbg-stat <?= ((int) ($sum['http_fail'] ?? 0)) === 0 ? 'is-ok' : 'is-fail' ?>">
          <strong><?= (int) ($sum['http_fail'] ?? 0) ?>/<?= (int) ($sum['http_total'] ?? 0) ?></strong>
          <span>HTTP fails</span>
        </div>
        <div class="hs-dbg-stat <?= ((int) ($sum['api_fail'] ?? 0)) === 0 ? 'is-ok' : 'is-fail' ?>">
          <strong><?= (int) ($sum['api_fail'] ?? 0) ?>/<?= (int) ($sum['api_total'] ?? 0) ?></strong>
          <span>API fails</span>
        </div>
        <div class="hs-dbg-stat">
          <strong><?= (int) ($sum['errors_stored'] ?? 0) ?></strong>
          <span>Stored errors</span>
        </div>
      </div>

      <details class="hs-dbg-details" open>
        <summary>HTTP checks</summary>
        <div class="hs-table-wrap">
          <table class="hs-table hs-dbg-table">
            <thead><tr><th></th><th>URL</th><th>Status</th><th>ms</th><th>Len</th><th>Detail</th></tr></thead>
            <tbody>
            <?php foreach ((array) ($report['http'] ?? []) as $row): ?>
              <tr>
                <td><?= !empty($row['ok']) ? '✅' : '❌' ?></td>
                <td><code><?= hs_h((string) ($row['url'] ?? '')) ?></code></td>
                <td><?= (int) ($row['status'] ?? 0) ?></td>
                <td><?= (int) ($row['ms'] ?? 0) ?></td>
                <td><?= (int) ($row['len'] ?? 0) ?></td>
                <td><?= hs_h((string) ($row['detail'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </details>

      <details class="hs-dbg-details" open>
        <summary>APIs & services</summary>
        <div class="hs-table-wrap">
          <table class="hs-table hs-dbg-table">
            <thead><tr><th></th><th>Check</th><th>Detail</th></tr></thead>
            <tbody>
            <?php foreach ((array) ($report['apis'] ?? []) as $row): ?>
              <tr>
                <td><?= !empty($row['ok']) ? '✅' : '❌' ?></td>
                <td><?= hs_h((string) ($row['label'] ?? $row['id'] ?? '')) ?></td>
                <td><code><?= hs_h((string) ($row['detail'] ?? '')) ?></code></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </details>

      <details class="hs-dbg-details">
        <summary>Structure (<?= (int) ($sum['structure_fail'] ?? 0) ?> fails)</summary>
        <div class="hs-table-wrap">
          <table class="hs-table hs-dbg-table">
            <thead><tr><th></th><th>Path</th><th>Detail</th><th>Size</th></tr></thead>
            <tbody>
            <?php foreach ((array) ($report['structure'] ?? []) as $row): ?>
              <?php if (!empty($row['ok'])) {
                  continue;
              } ?>
              <tr>
                <td>❌</td>
                <td><code><?= hs_h((string) ($row['path'] ?? '')) ?></code></td>
                <td><?= hs_h((string) ($row['detail'] ?? '')) ?></td>
                <td><?= (int) ($row['size'] ?? 0) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php
              $structFails = array_filter((array) ($report['structure'] ?? []), static fn($r) => empty($r['ok']));
            if ($structFails === []): ?>
              <tr><td colspan="4" class="hp-muted">All required paths OK</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </details>

      <details class="hs-dbg-details">
        <summary>PHP syntax scan (<?= (int) ($sum['syntax_fail'] ?? 0) ?> fails / <?= (int) ($sum['syntax_total'] ?? 0) ?> files)</summary>
        <div class="hs-table-wrap">
          <table class="hs-table hs-dbg-table">
            <thead><tr><th></th><th>Path</th><th>Detail</th></tr></thead>
            <tbody>
            <?php
              $shown = 0;
            foreach ((array) ($report['syntax'] ?? []) as $row):
                if (!empty($row['ok'])) {
                    continue;
                }
                $shown++;
                ?>
              <tr>
                <td>❌</td>
                <td><code><?= hs_h((string) ($row['path'] ?? '')) ?></code></td>
                <td><?= hs_h((string) ($row['detail'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($shown === 0): ?>
              <tr><td colspan="3" class="hp-muted">No syntax issues detected</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </details>
    </section>
  <?php endif; ?>

  <section class="hs-dbg-card">
    <h2><i class="fa-solid fa-triangle-exclamation"></i> <?= hs_h($t['admin_debugger_error_store'] ?? 'Error store') ?> (<?= count($errors) ?>)</h2>
    <p class="hp-muted" style="margin:0 0 .75rem"><?= hs_h($t['admin_debugger_error_store_lead'] ?? 'Imported from error_log files and kept for operator review (last 500).') ?></p>
    <?php if ($errors === []): ?>
      <p class="hp-muted"><?= hs_h($t['admin_debugger_no_errors'] ?? 'No stored errors. Import logs or wait for new failures.') ?></p>
    <?php else: ?>
      <div class="hs-table-wrap">
        <table class="hs-table hs-dbg-table">
          <thead><tr><th>Level</th><th>When</th><th>Source</th><th>Message</th></tr></thead>
          <tbody>
          <?php foreach (array_slice($errors, 0, 80) as $e): ?>
            <tr>
              <td><span class="hs-dbg-badge <?= ($e['level'] ?? '') === 'fatal' || ($e['level'] ?? '') === 'error' ? 'is-fail' : 'is-warn' ?>"><?= hs_h((string) ($e['level'] ?? 'error')) ?></span></td>
              <td><?= hs_h((string) ($e['ts'] ?? '')) ?></td>
              <td><code><?= hs_h((string) ($e['source'] ?? '')) ?></code></td>
              <td class="hs-dbg-msg"><?= hs_h((string) ($e['message'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';
