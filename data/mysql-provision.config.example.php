<?php
/**
 * MySQL admin for creating client databases (one isolated DB per hosting client).
 * Copy to mysql-provision.config.php — never commit the real file.
 *
 * Local server (default): host=localhost, client_host=localhost
 * Remote MySQL VPS:       host=203.0.113.10, client_host=203.0.113.10 (or mysql.example.com)
 * Hostinger shared:       name_prefix=u762384583_ (account prefix from hPanel)
 */
return [
    // Server where PHP connects to run CREATE DATABASE (localhost or remote IP/hostname)
    'host' => 'localhost',
    'port' => 3306,
    // MySQL user with CREATE DATABASE, CREATE USER, GRANT (not the CMS app user)
    'user' => 'root',
    'pass' => 'CHANGE_ME',
    // Prefix inside database name: hs_demo, hs_demo_a1b2c3
    'db_prefix' => 'hs_',
    // Optional hosting-account prefix (Hostinger: u762384583_)
    'name_prefix' => '',
    // Host shown to client apps in config.php (localhost if apps run on same server)
    'client_host' => 'localhost',
    // MySQL user host for GRANT: localhost (same server) or % (remote connections)
    'grant_host' => 'localhost',
    // Hostinger shared: use CMS database until dedicated CREATE DATABASE user is available
    'mode' => 'dedicated', // dedicated | shared
    'shared_database' => 'u762384583_hosting_cms',
    'shared_user' => 'u762384583_hosting_cms',
    'shared_pass' => '',
];