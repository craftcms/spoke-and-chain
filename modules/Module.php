<?php
namespace modules;

use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\models\Address;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\web\twig\variables\CraftVariable;
use modules\services\Reviews;
use yii\base\Event;

/**
 * Custom module class.
 *
 * This class will be available throughout the system via:
 * `Craft::$app->getModule('my-module')`.
 *
 * You can change its module ID ("my-module") to something else from
 * config/app.php.
 *
 * If you want the module to get loaded on every request, uncomment this line
 * in config/app.php:
 *
 *     'bootstrap' => ['my-module']
 *
 * Learn more about Yii module development in Yii's documentation:
 * http://www.yiiframework.com/doc-2.0/guide-structure-modules.html
 */
class Module extends \yii\base\Module
{
    /**
     * Initializes the module.
     */
    public function init()
    {
        // Set a @modules alias pointed to the modules/ directory
        Craft::setAlias('@modules', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\controllers';
        }

        parent::init();

        // Address validation rules
        Event::on(Address::class, Address::EVENT_DEFINE_RULES, function($event) {
            $event->rules[] = [[
                'firstName',
                'lastName',
                'address1',
                'city',
                'countryId',
                'zipCode'
            ], 'required'];
        });

        Event::on(Variant::class, Variant::EVENT_AFTER_CAPTURE_PRODUCT_SNAPSHOT, function($event) {
            /** @var Product $product */
            $product = $event->product;
            $attributes = $product->getAttributes(['mainImage']);

            if (!empty($attributes) && array_key_exists('mainImage', $attributes) && $mainImage = $attributes['mainImage']->one()) {
                $event->fieldData['mainImage'] = $mainImage->id;
            }
        });

        Event::on(Entry::class, Entry::EVENT_AFTER_SAVE, function($event) {
            if ($event->sender && $event->sender->section->handle == 'reviews') {
                Craft::$app->getCache()->delete(Reviews::CACHE_KEY);
            }
        });

        Event::on(Entry::class, Entry::EVENT_AFTER_DELETE, function($event) {
            if ($event->sender && $event->sender->section->handle == 'reviews') {
                Craft::$app->getCache()->delete(Reviews::CACHE_KEY);
            }
        });

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $e) {
            /** @var CraftVariable $variable */
            $variable = $e->sender;

            // Attach reviews services
            $variable->set('reviews', Reviews::class);
        });

        if (\Craft::$app->env === 'dev') {
            \Craft::$container->set(\craft\awss3\Volume::class, function($container, $params, $config) {
                if (empty($config['id'])) {
                    return new \craft\awss3\Volume($config);
                }

                return new \craft\volumes\Local([
                    'id' => $config['id'],
                    'uid' => $config['uid'],
                    'name' => $config['name'],
                    'handle' => $config['handle'],
                    'hasUrls' => $config['hasUrls'],
                    'url' => "@web/local-volumes/{$config['handle']}",
                    'path' => "@webroot/local-volumes/{$config['handle']}",
                    'sortOrder' => $config['sortOrder'],
                    'dateCreated' => $config['dateCreated'],
                    'dateUpdated' => $config['dateUpdated'],
                    'fieldLayoutId' => $config['fieldLayoutId'],
                ]);
            });
        }
    }
}
