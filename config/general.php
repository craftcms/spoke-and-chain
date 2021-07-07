<?php
/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here. You can see a
 * list of the available settings in vendor/craftcms/cms/src/config/GeneralConfig.php.
 *
 * @see \craft\config\GeneralConfig
 */

use craft\helpers\App;

return [
    // Global settings
    '*' => [

        // https://craftcms.com/docs/3.x/config/config-settings.html#allowadminchanges
        'allowAdminChanges' => true,

        // https://craftcms.com/docs/3.x/config/config-settings.html#autologinafteraccountactivation
        'autoLoginAfterAccountActivation' => true,

        // https://craftcms.com/docs/3.x/config/config-settings.html#cptrigger
        'cpTrigger' => 'admin',

        // Dev Mode (see https://craftcms.com/guides/what-dev-mode-does)
        'devMode' => false,

        // https://craftcms.com/docs/3.x/config/config-settings.html#defaultweekstartday
        'defaultWeekStartDay' => 1,

        // https://craftcms.com/docs/3.x/config/config-settings.html#disallowrobots
        'disallowRobots' => true,

        // https://craftcms.com/docs/3.x/config/config-settings.html#errortemplateprefix
        'errorTemplatePrefix' => '_errors/',

        // https://craftcms.com/docs/3.x/config/config-settings.html#generatetransformsbeforepageload
        'generateTransformsBeforePageLoad' => true,

        // https://craftcms.com/docs/3.x/config/config-settings.html#loginpath
        'loginPath' => 'account/login',

        // https://craftcms.com/docs/3.x/config/config-settings.html#omitscriptnameinurls
        'omitScriptNameInUrls' => true,

        // https://craftcms.com/docs/3.x/config/config-settings.html#postloginredirect
        'postLoginRedirect' => 'account',

        // https://craftcms.com/docs/3.x/config/config-settings.html#securitykey
        'securityKey' => App::env('SECURITY_KEY'),

        // https://craftcms.com/docs/3.x/config/config-settings.html#useemailasusername
        'useEmailAsUsername' => true,

        'aliases' => [
            '@web' => App::env('DEFAULT_SITE_URL'),
        ],
    ],

    // Dev environment settings
    'local' => [
        'devMode' => true,
        'disallowRobots' => true,
    ],

    'production' => [
    ],
];
