<?php
declare(strict_types=1);

require_once __DIR__ . '/invoices.php';

/** @param list<array<string,mixed>> $invoices */
function hs_invoice_render_table(
    array $invoices,
    array $t,
    string $lang,
    bool $admin = false,
    string $adminUserId = '',
    string $adminReturnUrl = ''
): string {
    if ($invoices === []) {
        return '<p class="hp-muted">' . hs_h($t['invoice_empty'] ?? 'No invoices yet.') . '</p>';
    }
    $rows = '';
    $confirm = hs_h($t['invoice_delete_confirm'] ?? 'Delete this invoice?');
    foreach ($invoices as $inv) {
        $id = (string) ($inv['id'] ?? '');
        $status = (string) ($inv['status'] ?? 'paid');
        $statusCls = $status === 'pending' ? 'hs-plan-status-pending' : 'hs-plan-status-active';
        $statusLabel = hs_invoice_status_label($status, $t);
        $pdfUrl = hs_url(hs_panel_path('invoice-pdf.php'), ['id' => $id]);
        $viewUrl = hs_url(hs_panel_path('invoice-view.php'), ['id' => $id]);
        $lineCount = count(is_array($inv['lines'] ?? null) ? $inv['lines'] : []);
        $typeCell = hs_h(hs_invoice_type_label($inv, $t));
        if ($lineCount > 1) {
            $typeCell .= ' <span class="hs-badge-muted" title="' . hs_h($t['invoice_lines_count'] ?? 'Line items') . '">'
                . hs_h((string) $lineCount) . '</span>';
        }
        $payUrl = hs_url(hs_panel_path('invoice-pay.php'), ['id' => $id]);
        $rows .= '<tr' . ($status === 'pending' ? ' class="hs-invoice-row-pending"' : '') . '>'
            . '<td><strong>' . hs_h((string) ($inv['number'] ?? '')) . '</strong></td>'
            . ($admin ? '<td>' . hs_h((string) ($inv['username'] ?? '')) . '</td>' : '')
            . '<td>' . $typeCell . '</td>'
            . '<td>' . hs_h(hs_format_date((string) ($inv['created_at'] ?? ''))) . '</td>'
            . '<td><strong>' . hs_h(hs_invoice_format_total($inv, $lang)) . '</strong></td>'
            . '<td><span class="hs-plan-status ' . $statusCls . '">' . hs_h($statusLabel) . '</span></td>'
            . '<td class="hs-invoice-actions" style="white-space:nowrap">'
            . '<a href="' . hs_h($viewUrl) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm" target="_blank" rel="noopener" title="' . hs_h($t['invoice_view'] ?? 'View') . '"><i class="fa-solid fa-eye"></i></a> '
            . '<a href="' . hs_h($pdfUrl) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm" title="PDF"><i class="fa-solid fa-file-pdf"></i></a>';
        // Client: pay button on unpaid invoices
        if (!$admin && $status === 'pending' && $id !== '') {
            $rows .= ' <a href="' . hs_h($payUrl) . '" class="hs-btn hs-btn-primary hp-dash-btn-sm hs-invoice-pay-btn">'
                . '<i class="fa-solid fa-credit-card"></i> '
                . hs_h($t['invoice_pay_btn'] ?? $t['panel_activate_pay_btn'] ?? 'Pay')
                . '</a>';
        }
        // Unpaid only — paid invoices cannot be deleted (client or admin).
        $canDelete = $id !== '' && hs_invoice_is_deletable($inv);
        if ($canDelete && !$admin) {
            $rows .= ' <form method="post" class="hs-invoice-delete-form" style="display:inline" data-stop-row '
                . 'onsubmit="return confirm(' . json_encode($confirm, JSON_UNESCAPED_UNICODE) . ');">'
                . hs_csrf_field()
                . '<input type="hidden" name="delete_invoice" value="1">'
                . '<input type="hidden" name="invoice_id" value="' . hs_h($id) . '">'
                . '<button type="submit" class="hs-btn hs-btn-ghost hp-dash-btn-sm hs-invoice-delete-btn" title="'
                . hs_h($t['invoice_delete'] ?? 'Delete') . '"><i class="fa-solid fa-trash"></i></button></form>';
        } elseif ($canDelete && $admin && $adminUserId !== '' && $adminReturnUrl !== '') {
            $rows .= ' <form method="post" class="hs-invoice-delete-form" style="display:inline" data-stop-row '
                . 'onsubmit="return confirm(' . json_encode($confirm, JSON_UNESCAPED_UNICODE) . ');">'
                . hs_csrf_field()
                . '<input type="hidden" name="delete_invoice" value="1">'
                . '<input type="hidden" name="invoice_id" value="' . hs_h($id) . '">'
                . '<input type="hidden" name="user_id" value="' . hs_h($adminUserId) . '">'
                . '<input type="hidden" name="return_url" value="' . hs_h($adminReturnUrl) . '">'
                . '<button type="submit" class="hs-btn hs-btn-ghost hp-dash-btn-sm hs-invoice-delete-btn" title="'
                . hs_h($t['invoice_delete'] ?? 'Delete') . '"><i class="fa-solid fa-trash"></i></button></form>';
        }
        $rows .= '</td></tr>';
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
    $seller = is_array($invoice['seller'] ?? null) && $invoice['seller'] !== []
        ? $invoice['seller']
        : hs_invoice_seller();
    // Refresh platform brand on Solaskinner (legacy invoices may still say BILOHASH)
    $liveSeller = hs_invoice_seller();
    $storedCo = strtolower((string) ($seller['company'] ?? ''));
    if (str_contains(strtolower((string) ($liveSeller['company'] ?? '')), 'solaskinner')
        && (str_contains($storedCo, 'bilohash') || $storedCo === '')) {
        $seller = array_merge($seller, [
            'company' => (string) $liveSeller['company'],
            'tagline' => (string) ($liveSeller['tagline'] ?? $seller['tagline'] ?? ''),
            'domain' => (string) ($liveSeller['domain'] ?? $seller['domain'] ?? ''),
            'email' => (string) ($liveSeller['email'] ?? $seller['email'] ?? ''),
            'web' => (string) ($liveSeller['web'] ?? $seller['web'] ?? ''),
        ]);
    }
    $linesHtml = '';
    foreach (is_array($invoice['lines'] ?? null) ? $invoice['lines'] : [] as $item) {
        $linesHtml .= '<tr><td>' . hs_h((string) ($item['desc'] ?? '')) . '</td>'
            . '<td>' . hs_h((string) ($item['qty'] ?? 1)) . '</td>'
            . '<td>' . hs_h(hs_format_nok_price((float) ($item['unit_nok'] ?? 0), $lang)) . '</td>'
            . '<td><strong>' . hs_h(hs_format_nok_price((float) ($item['total_nok'] ?? 0), $lang)) . '</strong></td></tr>';
    }
    $pdfUrl = hs_url(hs_panel_path('invoice-pdf.php'), ['id' => (string) ($invoice['id'] ?? '')]);
    $payUrl = hs_url(hs_panel_path('invoice-pay.php'), ['id' => (string) ($invoice['id'] ?? '')]);
    $status = (string) ($invoice['status'] ?? 'paid');
    $statusLabel = hs_invoice_status_label($status, $t);
    $payAction = $status === 'pending'
        ? ' · <a href="' . hs_h($payUrl) . '" class="inv-pay-link" style="font-weight:700;color:#ea580c">'
            . hs_h($t['invoice_pay_btn'] ?? $t['panel_activate_pay_btn'] ?? 'Pay') . '</a>'
        : '';

    return '<!DOCTYPE html><html lang="' . hs_h($lang) . '"><head><meta charset="utf-8"><title>'
        . hs_h((string) ($invoice['number'] ?? '')) . '</title>'
        . '<style>
        *{box-sizing:border-box}
        body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;max-width:720px;margin:2rem auto;padding:0 1rem;color:#111;background:#fff}
        .inv-head{display:flex;justify-content:space-between;gap:1.25rem;margin-bottom:1.75rem;flex-wrap:wrap}
        .inv-brand{font-size:1.45rem;font-weight:800;color:#059669;letter-spacing:-.02em}
        .inv-tagline{margin:.2rem 0 0;font-size:.9rem;color:#334155;font-weight:600}
        .inv-seller-meta{margin:.35rem 0 0;font-size:.82rem;color:#64748b;line-height:1.45}
        .inv-party-label{display:block;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b;margin-bottom:.35rem}
        .inv-bill-to{min-width:12rem;max-width:18rem}
        .inv-bill-to strong.name{display:block;font-size:1rem;color:#0f172a}
        .muted{color:#64748b;font-size:.88rem}
        table{width:100%;border-collapse:collapse;margin:1rem 0}
        th,td{padding:.55rem .65rem;border-bottom:1px solid #e5e7eb;text-align:left;font-size:.9rem;vertical-align:top}
        th{font-size:.72rem;text-transform:uppercase;color:#64748b}
        .inv-total{text-align:right;font-size:1.15rem;font-weight:700;margin-top:1rem}
        .inv-footer{margin-top:2rem;padding-top:1rem;border-top:1px solid #e5e7eb;font-size:.82rem;color:#64748b}
        .inv-actions{margin-top:1.5rem}
        @media print{
          @page{size:A4;margin:12mm}
          html,body{width:100%!important;max-width:none!important;margin:0!important;padding:0!important;background:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
          .inv-actions{display:none!important}
          /* Do NOT avoid breaks on the whole table — long invoices get clipped */
          .inv-head{break-inside:avoid;page-break-inside:avoid}
          .inv-footer,.inv-total{break-inside:avoid;page-break-inside:avoid}
          tr{break-inside:avoid;page-break-inside:avoid}
          thead{display:table-header-group}
          tfoot{display:table-footer-group}
        }
        </style></head><body>'
        . '<div class="inv-head">'
        . '<div class="inv-seller">'
        . '<div class="inv-party-label">' . hs_h($t['invoice_pdf_seller'] ?? 'Issued by') . '</div>'
        . '<div class="inv-brand">' . hs_h((string) ($seller['company'] ?? 'SolaSkinner Hosting')) . '</div>'
        . '<p class="inv-tagline">' . hs_h((string) ($seller['tagline'] ?? 'Hosting and domain services — solaskinner.com')) . '</p>'
        . '<p class="inv-seller-meta">'
        . hs_h((string) ($seller['web'] ?? '')) . '<br>'
        . hs_h((string) ($seller['email'] ?? ''))
        . '</p>'
        . '<p style="margin:1rem 0 0">' . hs_h($t['invoice_html_title'] ?? 'Invoice')
        . ' <strong>' . hs_h((string) ($invoice['number'] ?? '')) . '</strong></p>'
        . '<p class="muted">' . hs_h(hs_format_date((string) ($invoice['created_at'] ?? '')))
        . ' · ' . hs_h($statusLabel) . '</p>'
        . '</div>'
        . '<div class="inv-bill-to">'
        . '<span class="inv-party-label">' . hs_h($t['invoice_pdf_bill_to'] ?? 'Bill to') . '</span>'
        . '<strong class="name">' . hs_h((string) ($bill['name'] ?? '')) . '</strong>'
        . (!empty($bill['company']) ? '<span class="muted">' . hs_h((string) $bill['company']) . '</span><br>' : '')
        . '<span class="muted">' . hs_h((string) ($bill['email'] ?? '')) . '</span>'
        . '</div></div>'
        . '<table><thead><tr><th>' . hs_h($t['invoice_col_desc'] ?? 'Description') . '</th><th>' . hs_h($t['invoice_col_qty'] ?? 'Qty') . '</th>'
        . '<th>' . hs_h($t['invoice_col_unit'] ?? 'Unit') . '</th><th>' . hs_h($t['invoice_col_amount'] ?? 'Amount') . '</th></tr></thead><tbody>'
        . $linesHtml . '</tbody></table>'
        . '<div class="inv-total">' . hs_h($t['invoice_pdf_total'] ?? 'Total') . ': ' . hs_h(hs_invoice_format_total($invoice, $lang)) . '</div>'
        . '<div class="inv-footer">'
        . hs_h((string) ($seller['company'] ?? 'SolaSkinner Hosting')) . ' — '
        . hs_h((string) ($seller['tagline'] ?? 'Hosting and domain services — solaskinner.com'))
        . '</div>'
        . '<div class="inv-actions"><a href="' . hs_h($pdfUrl) . '">PDF</a> · <button onclick="window.print()">' . hs_h($t['invoice_print'] ?? 'Print') . '</button>'
        . $payAction . '</div>'
        . '</body></html>';
}