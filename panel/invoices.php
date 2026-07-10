<?php
declare(strict_types=1);

$panel_active = 'invoices';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/invoice-ui.php';

$page_title = $t['nav_invoices'] ?? 'Invoices';
$panel_tip_key = 'invoices';
$userId = (string) ($user['id'] ?? '');
$invoices = hs_invoices_for_user($userId);

ob_start();
?>
<?= hs_render_card(
    $t['nav_invoices'] ?? 'Invoices',
    '<p class="hp-muted">' . hs_h($t['invoice_lead'] ?? '') . '</p>'
    . hs_invoice_render_table($invoices, $t, $lang, false)
) ?>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';