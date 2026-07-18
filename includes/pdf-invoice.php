<?php
declare(strict_types=1);

/**
 * Minimal PDF 1.4 generator (Helvetica / Latin-1).
 * Multi-page A4, correct text matrix, long-line wrapping.
 */

function hs_pdf_latin(string $text): string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
    if ($converted === false) {
        $converted = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }

    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $converted);
}

/**
 * Wrap a Latin-1 string to approx $maxChars (Helvetica ~0.5em width).
 *
 * @return list<string>
 */
function hs_pdf_wrap_line(string $text, int $maxChars = 90): array
{
    $text = trim($text);
    if ($text === '') {
        return [''];
    }
    if (strlen($text) <= $maxChars) {
        return [$text];
    }
    $out = [];
    $words = preg_split('/\s+/', $text) ?: [$text];
    $buf = '';
    foreach ($words as $w) {
        $trial = $buf === '' ? $w : $buf . ' ' . $w;
        if (strlen($trial) <= $maxChars) {
            $buf = $trial;
            continue;
        }
        if ($buf !== '') {
            $out[] = $buf;
        }
        if (strlen($w) > $maxChars) {
            while (strlen($w) > $maxChars) {
                $out[] = substr($w, 0, $maxChars);
                $w = substr($w, $maxChars);
            }
            $buf = $w;
        } else {
            $buf = $w;
        }
    }
    if ($buf !== '') {
        $out[] = $buf;
    }

    return $out !== [] ? $out : [''];
}

/**
 * Build one page content stream from lines (already Latin-escaped).
 *
 * @param list<string> $pageLines raw UTF-8 lines for this page
 */
function hs_pdf_page_stream(array $pageLines, string $headerTitle = ''): string
{
    $x = 50;
    $yTop = 800;
    $lineH = 13;
    $fontSize = 10;
    $titleSize = 16;

    $ops = "BT\n";
    $y = $yTop;

    if ($headerTitle !== '') {
        $ops .= "/F1 {$titleSize} Tf\n";
        $ops .= sprintf("1 0 0 1 %.2F %.2F Tm\n(%s) Tj\n", $x, $y, hs_pdf_latin($headerTitle));
        $y -= 22;
        $ops .= "/F1 {$fontSize} Tf\n";
    } else {
        $ops .= "/F1 {$fontSize} Tf\n";
    }

    foreach ($pageLines as $line) {
        $wrapped = hs_pdf_wrap_line($line, 92);
        foreach ($wrapped as $seg) {
            $ops .= sprintf("1 0 0 1 %.2F %.2F Tm\n(%s) Tj\n", $x, $y, hs_pdf_latin($seg));
            $y -= $lineH;
        }
    }
    $ops .= "ET\n";

    return $ops;
}

/**
 * @param list<string> $lines UTF-8 text lines
 */
function hs_pdf_simple_document(string $title, array $lines): string
{
    // Paginate: ~52 body lines per A4 page after title
    $maxBodyLines = 52;
    $pagesRaw = [];
    $buf = [];
    $count = 0;
    foreach ($lines as $line) {
        $wrapped = hs_pdf_wrap_line($line, 92);
        foreach ($wrapped as $seg) {
            if ($count >= $maxBodyLines && $buf !== []) {
                $pagesRaw[] = $buf;
                $buf = [];
                $count = 0;
            }
            $buf[] = $seg;
            $count++;
        }
    }
    if ($buf !== []) {
        $pagesRaw[] = $buf;
    }
    if ($pagesRaw === []) {
        $pagesRaw[] = [''];
    }

    $pageCount = count($pagesRaw);
    $streams = [];
    foreach ($pagesRaw as $i => $pageLines) {
        $header = $i === 0 ? $title : ($title . ' (' . ($i + 1) . '/' . $pageCount . ')');
        // pageLines already wrapped; pass as-is without re-wrapping in stream builder
        $streams[] = hs_pdf_page_stream_raw($pageLines, $header);
    }

    return hs_pdf_assemble_pages($streams);
}

/**
 * @param list<string> $pageLines already wrapped plain text
 */
function hs_pdf_page_stream_raw(array $pageLines, string $headerTitle): string
{
    $x = 50.0;
    $y = 800.0;
    $lineH = 13.0;
    $ops = "BT\n";
    $ops .= "/F1 16 Tf\n";
    $ops .= sprintf("1 0 0 1 %.2F %.2F Tm\n(%s) Tj\n", $x, $y, hs_pdf_latin($headerTitle));
    $y -= 24;
    $ops .= "/F1 10 Tf\n";
    foreach ($pageLines as $seg) {
        if ($y < 50) {
            // Safety: should not happen if paginated correctly
            break;
        }
        $ops .= sprintf("1 0 0 1 %.2F %.2F Tm\n(%s) Tj\n", $x, $y, hs_pdf_latin($seg));
        $y -= $lineH;
    }
    $ops .= "ET\n";

    return $ops;
}

/**
 * @param list<string> $contentStreams
 */
function hs_pdf_assemble_pages(array $contentStreams): string
{
    $nPages = count($contentStreams);
    if ($nPages < 1) {
        $contentStreams = ["BT\n/F1 10 Tf\n1 0 0 1 50 800 Tm\n() Tj\nET\n"];
        $nPages = 1;
    }

    // Object layout:
    // 1 Catalog
    // 2 Pages
    // 3 Font
    // For each page i (0-based): pageObj = 4+2*i, contentObj = 5+2*i
    $objects = [];
    $objects[1] = "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n";

    $kids = [];
    for ($i = 0; $i < $nPages; $i++) {
        $pageObj = 4 + 2 * $i;
        $kids[] = $pageObj . ' 0 R';
    }
    $objects[2] = '2 0 obj<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $nPages . " >>endobj\n";
    $objects[3] = "3 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n";

    for ($i = 0; $i < $nPages; $i++) {
        $pageObj = 4 + 2 * $i;
        $contentObj = 5 + 2 * $i;
        $stream = $contentStreams[$i];
        $len = strlen($stream);
        $objects[$pageObj] = "{$pageObj} 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] "
            . "/Contents {$contentObj} 0 R /Resources<< /Font<< /F1 3 0 R >> >> >>endobj\n";
        $objects[$contentObj] = "{$contentObj} 0 obj<< /Length {$len} >>stream\n{$stream}endstream\nendobj\n";
    }

    ksort($objects, SORT_NUMERIC);
    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    $maxObj = 3 + 2 * $nPages;
    for ($i = 1; $i <= $maxObj; $i++) {
        $offsets[$i] = strlen($pdf);
        $pdf .= $objects[$i] ?? "{$i} 0 obj<<>>endobj\n";
    }
    $xrefPos = strlen($pdf);
    $pdf .= 'xref' . "\n0 " . ($maxObj + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $maxObj; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= 'trailer<< /Size ' . ($maxObj + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xrefPos}\n%%EOF";

    return $pdf;
}

/** @param array<string,mixed> $invoice */
function hs_invoice_pdf_bytes(array $invoice, array $t, string $lang): string
{
    $lines = [];
    $seller = is_array($invoice['seller'] ?? null) && $invoice['seller'] !== []
        ? $invoice['seller']
        : (function_exists('hs_invoice_seller') ? hs_invoice_seller() : []);
    if (function_exists('hs_invoice_seller')) {
        $live = hs_invoice_seller();
        $storedCo = strtolower((string) ($seller['company'] ?? ''));
        if (str_contains(strtolower((string) ($live['company'] ?? '')), 'solaskinner')
            && (str_contains($storedCo, 'bilohash') || $storedCo === '')) {
            $seller = array_merge($seller, [
                'company' => (string) ($live['company'] ?? 'SolaSkinner Hosting'),
                'tagline' => (string) ($live['tagline'] ?? ''),
                'email' => (string) ($live['email'] ?? ''),
                'web' => (string) ($live['web'] ?? ''),
            ]);
        }
    }

    $company = (string) ($seller['company'] ?? 'SolaSkinner Hosting');
    $lines[] = $company;
    if (!empty($seller['tagline'])) {
        $lines[] = (string) $seller['tagline'];
    }
    if (!empty($seller['web']) || !empty($seller['email'])) {
        $lines[] = trim((string) ($seller['web'] ?? '') . '  ' . (string) ($seller['email'] ?? ''));
    }
    $lines[] = '';
    $lines[] = ($t['invoice_pdf_title'] ?? 'INVOICE') . ' ' . (string) ($invoice['number'] ?? '');
    $lines[] = ($t['invoice_pdf_date'] ?? 'Date') . ': '
        . date('Y-m-d', strtotime((string) ($invoice['created_at'] ?? 'now')));
    $status = (string) ($invoice['status'] ?? '');
    if (function_exists('hs_invoice_status_label')) {
        $status = hs_invoice_status_label($status, $t);
    }
    $lines[] = ($t['invoice_pdf_status'] ?? 'Status') . ': ' . $status;
    $lines[] = '';

    $bill = is_array($invoice['billing'] ?? null) ? $invoice['billing'] : [];
    $lines[] = ($t['invoice_pdf_bill_to'] ?? 'Bill to') . ':';
    if ((string) ($bill['name'] ?? '') !== '') {
        $lines[] = (string) $bill['name'];
    }
    if (!empty($bill['company'])) {
        $lines[] = (string) $bill['company'];
    }
    if (!empty($bill['vat'])) {
        $lines[] = 'VAT / Org: ' . (string) $bill['vat'];
    }
    $addrParts = array_filter([
        trim((string) ($bill['address'] ?? '')),
        trim(((string) ($bill['postal'] ?? '')) . ' ' . ((string) ($bill['city'] ?? ''))),
        trim((string) ($bill['country'] ?? '')),
    ], static fn(string $p): bool => $p !== '');
    foreach ($addrParts as $ap) {
        $lines[] = $ap;
    }
    if ((string) ($bill['email'] ?? '') !== '') {
        $lines[] = (string) $bill['email'];
    }
    if ((string) ($bill['phone'] ?? '') !== '') {
        $lines[] = (string) $bill['phone'];
    }
    $lines[] = '';
    $lines[] = ($t['invoice_pdf_items'] ?? 'Items') . ':';
    $lines[] = str_repeat('-', 72);

    $items = is_array($invoice['lines'] ?? null) ? $invoice['lines'] : [];
    if ($items === []) {
        $lines[] = '- —';
    }
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $desc = trim((string) ($item['desc'] ?? ''));
        $qty = (float) ($item['qty'] ?? 1);
        $unit = function_exists('hs_format_nok_price')
            ? hs_format_nok_price((float) ($item['unit_nok'] ?? 0), $lang)
            : (string) ($item['unit_nok'] ?? 0);
        $total = function_exists('hs_format_nok_price')
            ? hs_format_nok_price((float) ($item['total_nok'] ?? 0), $lang)
            : (string) ($item['total_nok'] ?? 0);
        $qtyLabel = (abs($qty - 1.0) < 0.001) ? '' : (' x' . rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.'));
        $lines[] = '- ' . $desc . $qtyLabel;
        $lines[] = '    ' . $unit . ($qtyLabel !== '' ? '  →  ' : '  ') . $total;
    }

    $lines[] = str_repeat('-', 72);
    $totalAll = function_exists('hs_format_nok_price')
        ? hs_format_nok_price((float) ($invoice['total_nok'] ?? 0), $lang)
        : (string) ($invoice['total_nok'] ?? 0);
    $lines[] = ($t['invoice_pdf_total'] ?? 'Total') . ': ' . $totalAll;
    $lines[] = '';
    $lines[] = $company . (function_exists('hs_default_primary_domain') ? ' — ' . hs_default_primary_domain() : '');
    $lines[] = (string) ($t['invoice_pdf_footer'] ?? 'Thank you for your business.');

    $docTitle = (string) ($invoice['number'] ?? ($t['invoice_pdf_title'] ?? 'Invoice'));

    return hs_pdf_simple_document($docTitle, $lines);
}
