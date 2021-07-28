<?php
/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here. You can see a
 * list of the available settings in vendor/craftcms/cms/src/config/GeneralConfig.php.
 *
 * https://craftcms.com/docs/3.x/config/config-settings.html
 *
 * @see \craft\config\GeneralConfig
 */

use craft\helpers\App;

return [
    // Global settings
    '*' => [
        'aliases' => [
            '@web' => App::env('DEFAULT_SITE_URL'),
            '@webroot' => App::env('WEB_ROOT_PATH'),
        ],
        'autoLoginAfterAccountActivation' => true,
        'cpTrigger' => 'admin',
        'defaultSearchTermOptions' => [
            'subLeft' => true,
            'subRight' => true,
        ],
        'defaultWeekStartDay' => 1,
        'allowUpdates' => (bool)App::env('ALLOW_UPDATES'),
        'allowAdminChanges' => (bool)App::env('ALLOW_ADMIN_CHANGES'),
        'backupOnUpdate' => (bool)App::env('BACKUP_ON_UPDATE'),
        'devMode' => (bool)App::env('DEV_MODE'),
        'disallowRobots' => true,
        'errorTemplatePrefix' => '_errors/',
        'generateTransformsBeforePageLoad' => true,
        'loginPath' => 'account/login',
        'omitScriptNameInUrls' => true,
        'postLoginRedirect' => 'account',
        'runQueueAutomatically' => (bool)App::env('RUN_QUEUE_AUTOMATICALLY'),
        'securityKey' => App::env('SECURITY_KEY'),
        'useEmailAsUsername' => false,
    ],

    'dev' => [
        'disallowRobots' => true,
    ],
];
