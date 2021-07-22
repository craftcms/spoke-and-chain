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
        ],
        'allowAdminChanges' => true,
        'autoLoginAfterAccountActivation' => true,
        'cpTrigger' => 'admin',
        'defaultSearchTermOptions' => [
            'subLeft' => true,
            'subRight' => true,
        ],
        'defaultWeekStartDay' => 1,
        'devMode' => false,
        'disallowRobots' => true,
        'errorTemplatePrefix' => '_errors/',
        'generateTransformsBeforePageLoad' => true,
        'loginPath' => 'account/login',
        'omitScriptNameInUrls' => true,
        'postLoginRedirect' => 'account',
        'securityKey' => App::env('SECURITY_KEY'),
        'useEmailAsUsername' => true,
    ],

    'dev' => [
        'disallowRobots' => true,
    ],
];
