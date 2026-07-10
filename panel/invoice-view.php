<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/invoice-ui.php';

$id = trim((string) ($_GET['id'] ?? ''));
$invoice = $id !== '' ? hs_invoice_by_id($id) : null;
$userId = (string) ($user['id'] ?? '');
$isAdmin = $hs_is_platform_admin && !hs_impersonation_active();

if ($invoice === null || (($invoice['user_id'] ?? '') !== $userId && !$isAdmin)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

echo hs_invoice_render_html_document($invoice, $t, $lang);
exit;