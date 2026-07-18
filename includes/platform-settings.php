<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function hs_platform_settings_file(): string
{
    return HS_DATA_DIR . '/platform.settings.json';
}

/** @return array<string, mixed> */
function hs_platform_settings(): array
{
    if (isset($GLOBALS['HS_PLATFORM_SETTINGS']) && is_array($GLOBALS['HS_PLATFORM_SETTINGS'])) {
        return $GLOBALS['HS_PLATFORM_SETTINGS'];
    }
    $raw = hs_read_json(hs_platform_settings_file());
    $GLOBALS['HS_PLATFORM_SETTINGS'] = is_array($raw) ? $raw : [];

    return $GLOBALS['HS_PLATFORM_SETTINGS'];
}

function hs_platform_setting(string $key, mixed $default = null): mixed
{
    $settings = hs_platform_settings();
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function hs_platform_setting_set(string $key, mixed $value): bool
{
    $settings = hs_platform_settings();
    $settings[$key] = $value;
    if (!hs_write_json(hs_platform_settings_file(), $settings)) {
        return false;
    }
    $GLOBALS['HS_PLATFORM_SETTINGS'] = $settings;

    return true;
}

function hs_platform_prelaunch_enabled(): bool
{
    $settings = hs_platform_settings();
    if (array_key_exists('prelaunch', $settings)) {
        return (bool) $settings['prelaunch'];
    }

    return defined('HS_PRELAUNCH') && HS_PRELAUNCH;
}

function hs_platform_set_prelaunch(bool $enabled): bool
{
    if (!hs_platform_setting_set('prelaunch', $enabled)) {
        return false;
    }
    if (function_exists('hs_sync_public_robots_txt')) {
        hs_sync_public_robots_txt();
    }

    return true;
}