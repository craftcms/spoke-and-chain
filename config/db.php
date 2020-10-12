<?php
/**
 * Database Configuration
 *
 * All of your system's database connection settings go in here. You can see a
 * list of the available settings in vendor/craftcms/cms/src/config/DbConfig.php.
 *
 * @see craft\config\DbConfig
 */

use craft\db\Connection;
use craft\helpers\App;

return [
    'dsn' => App::env('DB_DSN') ?: null,
    'driver' => Connection::DRIVER_MYSQL,
    'server' => App::env('DB_SERVER'),
    'port' => App::env('DB_PORT'),
    'database' => App::env('DB_DATABASE'),
    'user' => App::env('DB_USER'),
    'password' => App::env('DB_PASSWORD')
];
