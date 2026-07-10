<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/invoices.php';
require_once dirname(__DIR__) . '/includes/pdf-invoice.php';

$id = trim((string) ($_GET['id'] ?? ''));
$invoice = $id !== '' ? hs_invoice_by_id($id) : null;
$userId = (string) ($user['id'] ?? '');
$isAdmin = $hs_is_platform_admin && !hs_impersonation_active();

if ($invoice === null || (($invoice['user_id'] ?? '') !== $userId && !$isAdmin)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$pdf = hs_invoice_pdf_bytes($invoice, $t, $lang);
$filename = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($invoice['number'] ?? 'invoice')) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;