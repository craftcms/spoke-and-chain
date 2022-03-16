<?php

namespace modules\demos;

use craft\helpers\App;
use modules\demos\widgets\Guide;

use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use yii\base\Event;
use craft\awss3\Volume as AwsVolume;
use craft\volumes\Local as LocalVolume;

class Module extends \yii\base\Module
{
    public function init()
    {
        Craft::setAlias('@modules/demos', __DIR__);

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\demos\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\demos\\controllers';
        }

        parent::init();

        $fsHandle = App::env('FS_HANDLE') ?? (App::env('S3_BUCKET') ? 'images' : 'imagesLocal');
        putenv("FS_HANDLE=$fsHandle");
        $_SERVER['FS_HANDLE'] = $fsHandle;
        $_ENV['FS_HANDLE'] = $fsHandle;

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['modules'] = __DIR__ . '/templates';
                //Craft::dd(__DIR__ . '/templates');
            }
        );
    }
}
