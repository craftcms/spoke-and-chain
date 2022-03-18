<?php
/**
 * Yii Application Config
 *
 * Edit this file at your own risk!
 *
 * The array returned by this file will get merged with
 * vendor/craftcms/cms/src/config/app.php and app.[web|console].php, when
 * Craft's bootstrap script is defining the configuration for the entire
 * application.
 *
 * You can define custom modules and system components, and even override the
 * built-in system components.
 *
 * If you want to modify the application config for *only* web requests or
 * *only* console requests, create an app.web.php or app.console.php file in
 * your config/ folder, alongside this one.
 */


use Bugsnag\Client;
use craft\helpers\App;
use MeadSteve\MonoSnag\BugsnagHandler;
use samdark\log\PsrTarget;

return [
    'modules' => [
        'demos' => \modules\demos\Module::class,
        'spoke' => \modules\Module::class,
    ],
    'bootstrap' => ['spoke', 'demos'],
    'components' => [
        'log' => [
            'targets' => App::env('BUGSNAG_API_KEY') ? [
                [
                    'class' => PsrTarget::class,
                    'logger' => (new Monolog\Logger('bugsnag'))
                        ->pushHandler(new BugsnagHandler(Client::make(App::env('BUGSNAG_API_KEY')))),
                ]
            ] : [],
        ],
    ]
];
