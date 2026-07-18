<?php
declare(strict_types=1);

/** Load shared BILOHASH ecosystem includes from public_html/includes. */
function hs_ecosystem_include(string $file): bool
{
    $candidates = [
        __DIR__ . '/' . $file,
        dirname(__DIR__) . '/includes/' . $file,
        dirname(__DIR__, 2) . '/includes/' . $file,
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            require_once $path;
            return true;
        }
    }
    return false;
}

function hs_ecosystem_messages_ready(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $ready = hs_ecosystem_include('ecosystem-owner-messages.php');
        if ($ready) {
            require_once __DIR__ . '/ecosystem-support-shim.php';
            hs_ecosystem_include('ecosystem-message-thread.php');
            $ready = function_exists('ecosystem_message_threads_for_client')
                && function_exists('ecosystem_owner_messages_add');
        }
    } catch (Throwable $e) {
        error_log('hs_ecosystem_messages_ready: ' . $e->getMessage());
        $ready = false;
    }

    return (bool) $ready;
}
