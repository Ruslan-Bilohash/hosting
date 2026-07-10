<?php
declare(strict_types=1);

require_once __DIR__ . '/ecosystem-bridge.php';
require_once __DIR__ . '/support.php';
require_once __DIR__ . '/hosting-orders.php';
require_once __DIR__ . '/plans.php';
require_once __DIR__ . '/currency.php';

function hs_notify_admin_email(): string
{
    $file = HS_DATA_DIR . '/admin.config.php';
    if (is_readable($file)) {
        $cfg = require $file;
        if (is_array($cfg)) {
            $email = trim((string) ($cfg['notify_email'] ?? $cfg['email'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }
    }
    return 'hosting@' . hs_default_primary_domain();
}

/** @param array<string,mixed> $user */
function hs_notify_order_event(string $event, array $user, array $payload = []): void
{
    $lang = (string) ($payload['lang'] ?? 'uk');
    $pt = hs_support_panel_strings($lang);
    $username = (string) ($user['username'] ?? 'user');
    $email = (string) ($user['email'] ?? '');
    $planId = (string) ($user['plan'] ?? 'starter');

    $subject = match ($event) {
        'plan_activated' => ($pt['notify_plan_subject'] ?? 'Hosting plan activated') . ' — ' . $username,
        'domain_ordered' => ($pt['notify_domain_subject'] ?? 'New domain order') . ' — ' . (string) ($payload['domain'] ?? ''),
        'domain_activated' => ($pt['notify_domain_active_subject'] ?? 'Domain activated') . ' — ' . (string) ($payload['domain'] ?? ''),
        default => ($pt['notify_order_subject'] ?? 'Hosting order') . ' — ' . $username,
    };

    $lines = [
        'Event: ' . $event,
        'Client: ' . $username . ' <' . $email . '>',
        'Plan: ' . ($pt['plan_' . $planId] ?? $planId),
        'Time: ' . gmdate('Y-m-d H:i:s') . ' UTC',
    ];
    if (!empty($payload['domain'])) {
        $lines[] = 'Domain: ' . (string) $payload['domain'];
    }
    if (isset($payload['price_nok'])) {
        $lines[] = 'Price: ' . hs_format_nok_price((float) $payload['price_nok'], $lang);
    }
    if (!empty($payload['folder'])) {
        $lines[] = 'Folder: public_html/' . ltrim((string) $payload['folder'], '/');
    }
    $lines[] = 'Panel: ' . hs_absolute_url(hs_panel_path());
    $bodyText = implode("\n", $lines);

    $orderType = $event === 'plan_activated' ? 'plan' : 'domain';
    $status = match ($event) {
        'domain_ordered' => 'pending',
        default => 'completed',
    };
    hs_hosting_order_log([
        'type' => $orderType,
        'event' => $event,
        'user_id' => (string) ($user['id'] ?? ''),
        'username' => $username,
        'email' => $email,
        'plan' => $planId,
        'domain' => (string) ($payload['domain'] ?? ''),
        'price_nok' => (float) ($payload['price_nok'] ?? 0),
        'status' => $status,
    ]);

    $adminTo = hs_notify_admin_email();
    $mailSubject = '[BILOHASH Hosting] ' . $subject;
    $headers = "Content-Type: text/plain; charset=UTF-8\r\n"
        . 'From: BILOHASH Hosting <noreply@' . hs_default_primary_domain() . ">\r\n";
    @mail($adminTo, '=?UTF-8?B?' . base64_encode($mailSubject) . '?=', $bodyText, $headers);
    $clientSubject = match ($event) {
        'plan_activated' => $pt['notify_client_plan'] ?? 'Your hosting plan is active',
        'domain_ordered' => $pt['notify_client_domain'] ?? 'Domain order received',
        'domain_activated' => $pt['notify_client_domain_live'] ?? 'Your domain is live',
        default => $pt['notify_client_order'] ?? 'Order confirmation',
    };

    require_once __DIR__ . '/invoices.php';
    $invoice = hs_invoice_from_event($event, $user, $payload);
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mailBody = $bodyText;
        if ($invoice !== null) {
            $invUrl = hs_absolute_url(hs_panel_path('invoice-view.php'), ['id' => (string) ($invoice['id'] ?? '')]);
            $mailBody .= "\n\n" . ($pt['invoice_email_line'] ?? 'Invoice') . ': ' . (string) ($invoice['number'] ?? '') . "\n" . $invUrl;
        }
        @mail($email, '=?UTF-8?B?' . base64_encode($clientSubject . ($invoice ? ' — ' . ($invoice['number'] ?? '') : '')) . '?=', $mailBody, $headers);
    }

    if (hs_ecosystem_messages_ready()) {
        $panelUrl = hs_support_panel_url();
        $htmlBody = '<p>' . nl2br(htmlspecialchars($bodyText, ENT_QUOTES, 'UTF-8')) . '</p>';
        ecosystem_owner_messages_add([
            'subject' => '[Hosting/orders] ' . $subject,
            'body' => $htmlBody,
            'category' => 'billing',
            'from_user' => $username,
            'from_name' => hs_support_client_display_name($user),
            'from_role' => 'Hosting client',
            'from_email' => strtolower(trim($email)),
            'shop_url' => $panelUrl,
            'lang' => $lang,
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);
    }
}