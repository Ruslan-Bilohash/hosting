<?php
declare(strict_types=1);

require_once __DIR__ . '/panel-domains.php';

/** Client-owned domain for mail (not the platform domain solaskinner.com). */
function hs_client_owned_mail_domain(array $settings): string
{
    $platform = strtolower(hs_default_primary_domain());
    $active = strtolower(trim((string) hs_active_domain($settings)));
    if ($active !== '' && $active !== $platform) {
        return $active;
    }
    foreach (hs_user_domain_choices($settings) as $d) {
        $d = strtolower(trim($d));
        if ($d !== '' && $d !== $platform) {
            return $d;
        }
    }

    return '';
}

/** @param array<string,mixed>|null $settings */
function hs_mail_resolve_domain(?string $domain, ?array $settings): string
{
    if ($settings !== null) {
        $owned = hs_client_owned_mail_domain($settings);
        if ($owned !== '') {
            return $owned;
        }
    }

    return $domain !== null && $domain !== '' ? $domain : hs_default_primary_domain();
}

/** Incoming mail host (IMAP/POP3) for client domains on this server. */
function hs_mail_incoming_host(?string $domain = null): string
{
    $custom = hs_host_profile_value('mail_incoming_host');
    if ($custom !== null && $custom !== '') {
        return $custom;
    }
    $domain = $domain ?? hs_default_primary_domain();
    return 'mail.' . $domain;
}

/** Outgoing SMTP host — same as incoming on cPanel/Namecheap. */
function hs_mail_outgoing_host(?string $domain = null): string
{
    $custom = hs_host_profile_value('mail_outgoing_host');
    if ($custom !== null && $custom !== '') {
        return $custom;
    }
    return hs_mail_incoming_host($domain);
}

/**
 * Full mail client settings for a domain (IMAP/SMTP/POP3, MX, webmail).
 *
 * @return array{
 *   domain:string,
 *   mx:string,
 *   webmail_url:string,
 *   webmail_roundcube_url:?string,
 *   webmail_legacy_url:?string,
 *   incoming:list<array{proto:string,host:string,port:string,encryption:string}>,
 *   outgoing:list<array{proto:string,host:string,port:string,encryption:string}>
 * }
 */
/** @param array<string,mixed>|null $settings */
function hs_mail_service_settings(?string $domain = null, ?array $settings = null): array
{
    $owned = $settings !== null ? hs_client_owned_mail_domain($settings) : '';
    $domain = hs_mail_resolve_domain($domain, $settings);
    $incomingHost = hs_mail_incoming_host($domain);
    $outgoingHost = hs_mail_outgoing_host($domain);
    $roundcube = function_exists('hs_webmail_roundcube_url') ? hs_webmail_roundcube_url($owned) : null;

    return [
        'domain' => $domain,
        'mx' => hs_email_mx_label($domain),
        'webmail_url' => hs_webmail_url($domain),
        'webmail_roundcube_url' => $roundcube,
        'webmail_legacy_url' => hs_webmail_legacy_url(),
        'incoming' => [
            [
                'proto' => 'IMAP',
                'host' => $incomingHost,
                'port' => hs_host_profile_value('imap_port') ?? '993',
                'encryption' => 'SSL/TLS',
            ],
            [
                'proto' => 'POP3',
                'host' => $incomingHost,
                'port' => hs_host_profile_value('pop3_port') ?? '995',
                'encryption' => 'SSL/TLS',
            ],
        ],
        'outgoing' => [
            [
                'proto' => 'SMTP',
                'host' => $outgoingHost,
                'port' => hs_host_profile_value('smtp_port_ssl') ?? '465',
                'encryption' => 'SSL',
            ],
            [
                'proto' => 'SMTP',
                'host' => $outgoingHost,
                'port' => hs_host_profile_value('smtp_port_tls') ?? '587',
                'encryption' => 'TLS/STARTTLS',
            ],
        ],
    ];
}

/** @param list<array{proto:string,host:string,port:string,encryption:string}> $rows */
function hs_mail_render_server_table(array $rows, array $t): string
{
    if ($rows === []) {
        return '';
    }
    $protoLabel = $t['account_mail_col_proto'] ?? 'Protocol';
    $hostLabel = $t['account_mail_col_host'] ?? 'Server';
    $portLabel = $t['account_mail_col_port'] ?? 'Port';
    $encLabel = $t['account_mail_col_encryption'] ?? 'Encryption';

    $body = '';
    foreach ($rows as $row) {
        $body .= '<tr><td><strong>' . hs_h((string) ($row['proto'] ?? '')) . '</strong></td>'
            . '<td><code>' . hs_h((string) ($row['host'] ?? '')) . '</code></td>'
            . '<td><code>' . hs_h((string) ($row['port'] ?? '')) . '</code></td>'
            . '<td>' . hs_h((string) ($row['encryption'] ?? '')) . '</td></tr>';
    }

    return '<div class="hs-table-wrap hs-account-mail-table"><table class="hs-table hs-table-compact"><thead><tr>'
        . '<th>' . hs_h($protoLabel) . '</th><th>' . hs_h($hostLabel) . '</th>'
        . '<th>' . hs_h($portLabel) . '</th><th>' . hs_h($encLabel) . '</th>'
        . '</tr></thead><tbody>' . $body . '</tbody></table></div>';
}