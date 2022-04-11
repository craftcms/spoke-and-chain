<?php
/**
 * Craft web bootstrap file
 */

// Set path constants
define('CRAFT_BASE_PATH', dirname(__DIR__));
define('CRAFT_VENDOR_PATH', CRAFT_BASE_PATH.'/vendor');

// Load Composer's autoloader
require_once CRAFT_VENDOR_PATH.'/autoload.php';

// Load dotenv?
if (class_exists('Dotenv\Dotenv') && file_exists(CRAFT_BASE_PATH.'/.env')) {
    Dotenv\Dotenv::create(CRAFT_BASE_PATH)->load();
}

define('CRAFT_STREAM_LOG', true);
define('CRAFT_ENVIRONMENT', getenv('CRAFT_ENVIRONMENT') ?: 'production');

if ($storagePath = getenv('CRAFT_STORAGE_PATH')) {
    define('CRAFT_STORAGE_PATH', $storagePath);
}

if ($ephemeral = getenv('CRAFT_EPHEMERAL')) {
    define('CRAFT_EPHEMERAL', filter_var($ephemeral, FILTER_VALIDATE_BOOL));
}

// Load and run Craft
/** @var craft\web\Application $app */
$app = require CRAFT_VENDOR_PATH.'/craftcms/cms/bootstrap/web.php';
$app->run();
