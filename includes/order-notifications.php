<?php
declare(strict_types=1);

require_once __DIR__ . '/ecosystem-bridge.php';
require_once __DIR__ . '/support.php';
require_once __DIR__ . '/hosting-orders.php';
require_once __DIR__ . '/plans.php';
require_once __DIR__ . '/currency.php';

/**
 * Primary inbox for all payment / order receipts.
 * Always support@solaskinner.com (or host profile support_inbox_email).
 */
function hs_notify_admin_email(): string
{
    if (function_exists('hs_host_profile_value')) {
        $support = trim((string) (hs_host_profile_value('support_inbox_email') ?? ''));
        if ($support !== '' && filter_var($support, FILTER_VALIDATE_EMAIL)) {
            return strtolower($support);
        }
    }
    $file = HS_DATA_DIR . '/admin.config.php';
    if (is_readable($file)) {
        $cfg = require $file;
        if (is_array($cfg)) {
            $email = trim((string) ($cfg['notify_email'] ?? $cfg['email'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return strtolower($email);
            }
        }
    }
    $domain = function_exists('hs_default_primary_domain') ? hs_default_primary_domain() : 'solaskinner.com';
    if (str_contains(strtolower($domain), 'solaskinner')) {
        return 'support@solaskinner.com';
    }

    return 'hosting@' . $domain;
}

/**
 * CC list for every payment/order receipt (operator copies).
 *
 * @return list<string>
 */
function hs_notify_receipt_cc_emails(): array
{
    $cc = ['rbilohash@gmail.com'];
    $file = HS_DATA_DIR . '/admin.config.php';
    if (is_readable($file)) {
        $cfg = require $file;
        if (is_array($cfg) && !empty($cfg['notify_cc'])) {
            $extra = $cfg['notify_cc'];
            if (is_string($extra)) {
                $extra = preg_split('/[\s,;]+/', $extra) ?: [];
            }
            if (is_array($extra)) {
                foreach ($extra as $e) {
                    $e = strtolower(trim((string) $e));
                    if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL) && !in_array($e, $cc, true)) {
                        $cc[] = $e;
                    }
                }
            }
        }
    }
    // Never CC the primary To address twice
    $to = strtolower(hs_notify_admin_email());
    $cc = array_values(array_filter(
        $cc,
        static fn(string $e): bool => $e !== $to && filter_var($e, FILTER_VALIDATE_EMAIL)
    ));

    return $cc;
}

/**
 * Events that are payment receipts (always mail support@ + CC).
 *
 * @return list<string>
 */
function hs_notify_payment_events(): array
{
    return [
        'plan_activated',
        'plan_renew',
        'plan_renewed',
        'domain_activated',
        'domain_ordered',
        'invoice_paid',
        'payment_received',
        'service_paid',
    ];
}

/**
 * @param list<string> $cc
 */
function hs_notify_mail_headers(array $cc = [], string $replyTo = ''): string
{
    $domain = function_exists('hs_default_primary_domain') ? hs_default_primary_domain() : 'solaskinner.com';
    $from = 'SolaSkinner Hosting <noreply@' . $domain . '>';
    $headers = "MIME-Version: 1.0\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . 'From: ' . $from . "\r\n";
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers .= 'Reply-To: ' . $replyTo . "\r\n";
    } else {
        $headers .= 'Reply-To: ' . hs_notify_admin_email() . "\r\n";
    }
    if ($cc !== []) {
        $headers .= 'Cc: ' . implode(', ', $cc) . "\r\n";
    }

    return $headers;
}

function hs_notify_encode_subject(string $subject): string
{
    return '=?UTF-8?B?' . base64_encode($subject) . '?=';
}

/**
 * Send plain-text mail; returns whether PHP mail() accepted the message.
 *
 * @param list<string> $cc
 */
function hs_notify_send_mail(string $to, string $subject, string $body, array $cc = [], string $replyTo = ''): bool
{
    $to = strtolower(trim($to));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $headers = hs_notify_mail_headers($cc, $replyTo);

    return @mail($to, hs_notify_encode_subject($subject), $body, $headers);
}

/**
 * Send payment / order receipt to support@ + CC copies (and optionally client separately).
 *
 * @param array<string,mixed> $user
 * @param array<string,mixed> $payload
 */
function hs_notify_order_event(string $event, array $user, array $payload = []): void
{
    $lang = (string) ($payload['lang'] ?? 'uk');
    $pt = function_exists('hs_support_panel_strings') ? hs_support_panel_strings($lang) : [];
    $username = (string) ($user['username'] ?? 'user');
    $email = (string) ($user['email'] ?? '');
    $planId = (string) ($user['plan'] ?? 'starter');

    $isPayment = in_array($event, hs_notify_payment_events(), true)
        || !empty($payload['payment_provider'])
        || !empty($payload['payment_ref'])
        || (float) ($payload['price_nok'] ?? 0) > 0
        || (float) ($payload['domain_price_eur'] ?? 0) > 0;

    $subject = match ($event) {
        'plan_activated' => ($pt['notify_plan_subject'] ?? 'Hosting plan activated') . ' — ' . $username,
        'plan_renew', 'plan_renewed' => ($pt['notify_renew_subject'] ?? 'Hosting plan renewed') . ' — ' . $username,
        'domain_ordered' => ($pt['notify_domain_subject'] ?? 'New domain order') . ' — ' . (string) ($payload['domain'] ?? ''),
        'domain_activated' => ($pt['notify_domain_active_subject'] ?? 'Domain activated') . ' — ' . (string) ($payload['domain'] ?? ''),
        'invoice_paid', 'payment_received' => ($pt['notify_payment_subject'] ?? 'Payment received') . ' — ' . $username,
        default => ($pt['notify_order_subject'] ?? 'Hosting order') . ' — ' . $username,
    };
    if ($isPayment) {
        $subject = ($pt['notify_receipt_prefix'] ?? 'Payment receipt') . ': ' . $subject;
    }

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
        $lines[] = 'Price: ' . (function_exists('hs_format_nok_price')
            ? hs_format_nok_price((float) $payload['price_nok'], $lang)
            : ((string) $payload['price_nok'] . ' NOK'));
    }
    if (isset($payload['domain_price_eur']) && (float) $payload['domain_price_eur'] > 0) {
        $lines[] = 'Domain price (EUR): ' . (string) $payload['domain_price_eur'];
    }
    if (!empty($payload['payment_provider'])) {
        $lines[] = 'Payment provider: ' . (string) $payload['payment_provider'];
    }
    if (!empty($payload['payment_ref'])) {
        $lines[] = 'Payment ref: ' . (string) $payload['payment_ref'];
    }
    if (!empty($payload['months'])) {
        $lines[] = 'Months: ' . (string) $payload['months'];
    }
    if (!empty($payload['folder'])) {
        $lines[] = 'Folder: public_html/' . ltrim((string) $payload['folder'], '/');
    }
    $lines[] = 'Panel: ' . (function_exists('hs_absolute_url') ? hs_absolute_url(hs_panel_path()) : '');
    $bodyText = implode("\n", $lines);

    $orderType = match ($event) {
        'plan_activated', 'plan_renew', 'plan_renewed' => 'plan',
        'invoice_paid', 'payment_received' => 'invoice',
        default => 'domain',
    };
    $status = match ($event) {
        'domain_ordered' => 'pending',
        default => 'completed',
    };
    if (function_exists('hs_hosting_order_log')) {
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
    }

    // ——— Operator receipt: support@ + CC rbilohash@gmail.com ———
    $adminTo = hs_notify_admin_email();
    $cc = hs_notify_receipt_cc_emails();
    $mailSubject = '[SolaSkinner] ' . $subject;

    require_once __DIR__ . '/invoices.php';
    $invoice = function_exists('hs_invoice_from_event')
        ? hs_invoice_from_event($event, $user, $payload)
        : null;
    $operatorBody = $bodyText;
    if (is_array($invoice)) {
        $invUrl = hs_absolute_url(hs_panel_path('invoice-view.php'), ['id' => (string) ($invoice['id'] ?? '')]);
        $operatorBody .= "\n\n" . ($pt['invoice_email_line'] ?? 'Invoice') . ': '
            . (string) ($invoice['number'] ?? '') . "\n" . $invUrl;
    }
    $operatorBody .= "\n\n---\nReceipt copy: " . $adminTo
        . ($cc !== [] ? ' / Cc: ' . implode(', ', $cc) : '');

    hs_notify_send_mail($adminTo, $mailSubject, $operatorBody, $cc);

    // ——— Client confirmation (no internal CC) ———
    $clientSubject = match ($event) {
        'plan_activated' => $pt['notify_client_plan'] ?? 'Your hosting plan is active',
        'plan_renew', 'plan_renewed' => $pt['notify_client_renew'] ?? 'Your hosting plan was renewed',
        'domain_ordered' => $pt['notify_client_domain'] ?? 'Domain order received',
        'domain_activated' => $pt['notify_client_domain_live'] ?? 'Your domain is live',
        'invoice_paid', 'payment_received' => $pt['notify_client_payment'] ?? 'Payment received — thank you',
        default => $pt['notify_client_order'] ?? 'Order confirmation',
    };
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mailBody = $bodyText;
        if (is_array($invoice)) {
            $invUrl = hs_absolute_url(hs_panel_path('invoice-view.php'), ['id' => (string) ($invoice['id'] ?? '')]);
            $mailBody .= "\n\n" . ($pt['invoice_email_line'] ?? 'Invoice') . ': '
                . (string) ($invoice['number'] ?? '') . "\n" . $invUrl;
        }
        $subj = $clientSubject . (is_array($invoice) ? ' — ' . (string) ($invoice['number'] ?? '') : '');
        hs_notify_send_mail($email, $subj, $mailBody, [], hs_notify_admin_email());
    }

    if (function_exists('hs_ecosystem_messages_ready') && hs_ecosystem_messages_ready()) {
        $panelUrl = function_exists('hs_support_panel_url') ? hs_support_panel_url() : '';
        $htmlBody = '<p>' . nl2br(htmlspecialchars($bodyText, ENT_QUOTES, 'UTF-8')) . '</p>';
        if (function_exists('ecosystem_owner_messages_add')) {
            ecosystem_owner_messages_add([
                'subject' => '[Hosting/orders] ' . $subject,
                'body' => $htmlBody,
                'category' => 'billing',
                'from_user' => $username,
                'from_name' => function_exists('hs_support_client_display_name')
                    ? hs_support_client_display_name($user)
                    : $username,
                'from_role' => 'Hosting client',
                'from_email' => strtolower(trim($email)),
                'shop_url' => $panelUrl,
                'lang' => $lang,
                'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);
        }
    }
}
