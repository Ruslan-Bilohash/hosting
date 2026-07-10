<?php
declare(strict_types=1);

define('HS_VERSION', '2.6.0');
define('HS_VERSION_DATE', '2026-07-10');

function hs_version(): string
{
    return HS_VERSION;
}

function hs_version_label(): string
{
    return 'v' . HS_VERSION;
}