<?php
declare(strict_types=1);

require_once __DIR__ . '/invoices.php';

/** @param list<array<string,mixed>> $invoices */
function hs_invoice_render_table(array $invoices, array $t, string $lang, bool $admin = false): string
{
    if ($invoices === []) {
        return '<p class="hp-muted">' . hs_h($t['invoice_empty'] ?? 'No invoices yet.') . '</p>';
    }
    $rows = '';
    foreach ($invoices as $inv) {
        $id = (string) ($inv['id'] ?? '');
        $status = (string) ($inv['status'] ?? 'paid');
        $statusCls = $status === 'pending' ? 'hs-plan-status-pending' : 'hs-plan-status-active';
        $pdfUrl = hs_url(hs_panel_path('invoice-pdf.php'), ['id' => $id]);
        $viewUrl = hs_url(hs_panel_path('invoice-view.php'), ['id' => $id]);
        $rows .= '<tr>'
            . '<td><strong>' . hs_h((string) ($inv['number'] ?? '')) . '</strong></td>'
            . ($admin ? '<td>' . hs_h((string) ($inv['username'] ?? '')) . '</td>' : '')
            . '<td>' . hs_h(hs_invoice_type_label($inv, $t)) . '</td>'
            . '<td>' . hs_h(hs_format_date((string) ($inv['created_at'] ?? ''))) . '</td>'
            . '<td><strong>' . hs_h(hs_invoice_format_total($inv, $lang)) . '</strong></td>'
            . '<td><span class="hs-plan-status ' . $statusCls . '">' . hs_h($status) . '</span></td>'
            . '<td class="hs-invoice-actions">'
            . '<a href="' . hs_h($viewUrl) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm" target="_blank" rel="noopener"><i class="fa-solid fa-eye"></i></a> '
            . '<a href="' . hs_h($pdfUrl) . '" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-file-pdf"></i> PDF</a>'
            . '</td></tr>';
    }
    $head = '<th>' . hs_h($t['invoice_col_number'] ?? '#') . '</th>'
        . ($admin ? '<th>' . hs_h($t['account_username'] ?? 'Client') . '</th>' : '')
        . '<th>' . hs_h($t['invoice_col_type'] ?? 'Type') . '</th>'
        . '<th>' . hs_h($t['invoice_col_date'] ?? 'Date') . '</th>'
        . '<th>' . hs_h($t['invoice_col_amount'] ?? 'Amount') . '</th>'
        . '<th>' . hs_h($t['invoice_col_status'] ?? 'Status') . '</th><th></th>';
    return '<div class="hs-table-wrap"><table class="hs-table hs-invoice-table"><thead><tr>' . $head . '</tr></thead><tbody>' . $rows . '</tbody></table></div>';
}

/** @param array<string,mixed> $invoice */
function hs_invoice_render_html_document(array $invoice, array $t, string $lang): string
{
    $bill = is_array($invoice['billing'] ?? null) ? $invoice['billing'] : [];
    $linesHtml = '';
    foreach (is_array($invoice['lines'] ?? null) ? $invoice['lines'] : [] as $item) {
        $linesHtml .= '<tr><td>' . hs_h((string) ($item['desc'] ?? '')) . '</td>'
            . '<td>' . hs_h((string) ($item['qty'] ?? 1)) . '</td>'
            . '<td>' . hs_h(hs_format_nok_price((float) ($item['unit_nok'] ?? 0), $lang)) . '</td>'
            . '<td><strong>' . hs_h(hs_format_nok_price((float) ($item['total_nok'] ?? 0), $lang)) . '</strong></td></tr>';
    }
    $pdfUrl = hs_url(hs_panel_path('invoice-pdf.php'), ['id' => (string) ($invoice['id'] ?? '')]);
    return '<!DOCTYPE html><html lang="' . hs_h($lang) . '"><head><meta charset="utf-8"><title>'
        . hs_h((string) ($invoice['number'] ?? '')) . '</title>'
        . '<style>
        body{font-family:system-ui,sans-serif;max-width:720px;margin:2rem auto;padding:0 1rem;color:#111}
        .inv-head{display:flex;justify-content:space-between;gap:1rem;margin-bottom:2rem}
        .inv-brand{font-size:1.4rem;font-weight:800;color:#059669}
        table{width:100%;border-collapse:collapse;margin:1rem 0}
        th,td{padding:.55rem .65rem;border-bottom:1px solid #e5e7eb;text-align:left;font-size:.9rem}
        th{font-size:.72rem;text-transform:uppercase;color:#64748b}
        .inv-total{text-align:right;font-size:1.15rem;font-weight:700;margin-top:1rem}
        .inv-actions{margin-top:1.5rem}
        @media print{.inv-actions{display:none}}
        </style></head><body>'
        . '<div class="inv-head"><div><div class="inv-brand">BILOHASH Hosting</div>'
        . '<p>' . hs_h($t['invoice_html_title'] ?? 'Invoice') . ' <strong>' . hs_h((string) ($invoice['number'] ?? '')) . '</strong></p>'
        . '<p class="hp-muted">' . hs_h(hs_format_date((string) ($invoice['created_at'] ?? ''))) . '</p></div>'
        . '<div><strong>' . hs_h($t['invoice_pdf_bill_to'] ?? 'Bill to') . '</strong><br>'
        . hs_h((string) ($bill['name'] ?? '')) . '<br>'
        . (!empty($bill['company']) ? hs_h((string) $bill['company']) . '<br>' : '')
        . hs_h((string) ($bill['email'] ?? '')) . '</div></div>'
        . '<table><thead><tr><th>' . hs_h($t['invoice_col_desc'] ?? 'Description') . '</th><th>' . hs_h($t['invoice_col_qty'] ?? 'Qty') . '</th>'
        . '<th>' . hs_h($t['invoice_col_unit'] ?? 'Unit') . '</th><th>' . hs_h($t['invoice_col_amount'] ?? 'Amount') . '</th></tr></thead><tbody>'
        . $linesHtml . '</tbody></table>'
        . '<div class="inv-total">' . hs_h($t['invoice_pdf_total'] ?? 'Total') . ': ' . hs_h(hs_invoice_format_total($invoice, $lang)) . '</div>'
        . '<div class="inv-actions"><a href="' . hs_h($pdfUrl) . '">PDF</a> · <button onclick="window.print()">' . hs_h($t['invoice_print'] ?? 'Print') . '</button></div>'
        . '</body></html>';
}