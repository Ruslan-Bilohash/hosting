<?php
declare(strict_types=1);

/**
 * Copy to admin.config.php and set a strong password hash.
 * Default demo (when file missing): admin / admin
 *
 * php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
 */
return [
    'user' => 'admin',
    'password_hash' => '',
    'role' => 'super',
];