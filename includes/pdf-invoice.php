<?php
declare(strict_types=1);

/** Minimal PDF 1.4 generator (Helvetica, Latin). */
function hs_pdf_latin(string $text): string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
    if ($converted === false) {
        $converted = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $converted);
}

/** @param list<string> $lines */
function hs_pdf_simple_document(string $title, array $lines): string
{
    $stream = "BT\n/F1 16 Tf\n50 800 Td\n(" . hs_pdf_latin($title) . ") Tj\n";
    $stream .= "/F1 10 Tf\n0 -24 Td\n";
    $y = 776;
    foreach ($lines as $line) {
        $safe = hs_pdf_latin($line);
        if ($y < 60) {
            break;
        }
        $stream .= "50 " . $y . " Td\n(" . $safe . ") Tj\n0 -14 Td\n";
        $y -= 14;
    }
    $stream .= "ET";
    $len = strlen($stream);

    $objects = [];
    $objects[] = "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n";
    $objects[] = "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n";
    $objects[] = "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources<< /Font<< /F1 5 0 R >> >> >>endobj\n";
    $objects[] = "4 0 obj<< /Length {$len} >>stream\n{$stream}\nendstream\nendobj\n";
    $objects[] = "5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }
    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xrefPos}\n%%EOF";
    return $pdf;
}

/** @param array<string,mixed> $invoice */
function hs_invoice_pdf_bytes(array $invoice, array $t, string $lang): string
{
    $lines = [];
    $lines[] = ($t['invoice_pdf_title'] ?? 'INVOICE') . ' ' . (string) ($invoice['number'] ?? '');
    $lines[] = ($t['invoice_pdf_date'] ?? 'Date') . ': ' . date('Y-m-d', strtotime((string) ($invoice['created_at'] ?? 'now')));
    $lines[] = ($t['invoice_pdf_status'] ?? 'Status') . ': ' . (string) ($invoice['status'] ?? '');
    $lines[] = '';
    $bill = is_array($invoice['billing'] ?? null) ? $invoice['billing'] : [];
    $lines[] = ($t['invoice_pdf_bill_to'] ?? 'Bill to') . ':';
    $lines[] = (string) ($bill['name'] ?? '');
    if (!empty($bill['company'])) {
        $lines[] = (string) $bill['company'];
    }
    if (!empty($bill['vat'])) {
        $lines[] = 'VAT: ' . (string) $bill['vat'];
    }
    $addr = trim(((string) ($bill['address'] ?? '')) . ', ' . ((string) ($bill['postal'] ?? '')) . ' ' . ((string) ($bill['city'] ?? '')));
    if ($addr !== ',') {
        $lines[] = $addr;
    }
    $lines[] = (string) ($bill['email'] ?? '');
    $lines[] = '';
    $lines[] = ($t['invoice_pdf_items'] ?? 'Items') . ':';
    foreach (is_array($invoice['lines'] ?? null) ? $invoice['lines'] : [] as $item) {
        $desc = (string) ($item['desc'] ?? '');
        $total = hs_format_nok_price((float) ($item['total_nok'] ?? 0), $lang);
        $lines[] = '- ' . $desc . ' : ' . $total;
    }
    $lines[] = '';
    $lines[] = ($t['invoice_pdf_total'] ?? 'Total') . ': ' . hs_format_nok_price((float) ($invoice['total_nok'] ?? 0), $lang);
    $lines[] = '';
    $lines[] = 'BILOHASH Hosting — ' . hs_default_primary_domain();
    $lines[] = ($t['invoice_pdf_footer'] ?? 'Thank you for your business.');

    return hs_pdf_simple_document((string) ($invoice['number'] ?? 'Invoice'), $lines);
}