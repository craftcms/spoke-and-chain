<?php

namespace modules\demos;

use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\App;
use craft\web\View;
use yii\base\Event;

/**
 * Class Module
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
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
