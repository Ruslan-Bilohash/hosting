<?php
declare(strict_types=1);

/**
 * CLI: php scripts/migrate-to-mysql-cli.php
 * Imports JSON runtime data into MySQL (idempotent).
 */
$root = dirname(__DIR__);
require_once $root . '/config.php';
require_once $root . '/includes/database.php';
require_once $root . '/includes/storage.php';
require_once $root . '/includes/db-migrate.php';

if (!hs_is_mysql_installed()) {
    fwrite(STDERR, "MySQL not installed. Run install.php first.\n");
    exit(1);
}

hs_db_ensure_schema();
$result = hs_mysql_migrate_from_json(true);

foreach ($result['migrated'] as $line) {
    echo "MIGRATED: {$line}\n";
}
foreach ($result['skipped'] as $line) {
    echo "SKIPPED: {$line}\n";
}
foreach ($result['errors'] as $line) {
    fwrite(STDERR, "ERROR: {$line}\n");
}

exit($result['ok'] ? 0 : 1);