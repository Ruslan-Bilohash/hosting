<?php
declare(strict_types=1);

/** Minimal shims so ecosystem-message-thread.php works without license-cabinet.php (avoids pricing redeclare). */
if (!function_exists('license_cabinet_format_ts')) {
    function license_cabinet_format_ts(string $ts): string
    {
        if ($ts === '') {
            return '';
        }
        $t = strtotime($ts);
        if ($t === false) {
            return $ts;
        }
        return gmdate('Y-m-d H:i', $t) . ' UTC';
    }
}