<?php
namespace modules;

use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\models\Address;
use craft\elements\Entry;
use craft\base\Element;
use craft\events\RegisterElementSourcesEvent;
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
 * Learn more about Yii module development in Yii’s documentation:
 * http://www.yiiframework.com/doc-2.0/guide-structure-modules.html
 */
class Module extends \yii\base\Module
{
    /**
     * Initializes the module.
     */
    public function init(): void
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

        // Add custom address validation rules
        Event::on(
            Address::class,
            Address::EVENT_DEFINE_RULES,
            function($event) {
                $event->rules[] = [[
                    'firstName',
                    'lastName',
                    'address1',
                    'city',
                    'countryId',
                    'zipCode'
                ], 'required'];
            }
        );

        // Include a reference to the main product image in the variant snapshot
        Event::on(
            Variant::class,
            Variant::EVENT_AFTER_CAPTURE_PRODUCT_SNAPSHOT,
            function($event) {
                /** @var Product $product */
                $product = $event->product;
                $attributes = $product->getAttributes(['mainImage']);

                if (!empty($attributes) &&
                    array_key_exists('mainImage', $attributes) &&
                    $mainImage = $attributes['mainImage']->one()
                ) {
                    $event->fieldData['mainImage'] = $mainImage->id;
                }
            }
        );

        // Clear our custom review cache when a new review is saved
        Event::on(
            Entry::class,
            Entry::EVENT_AFTER_SAVE,
            function($event) {
                if ($event->sender && $event->sender->section->handle === 'reviews') {
                    Craft::$app->getCache()->delete(Reviews::CACHE_KEY);
                }
            }
        );

        // Clear our custom review cache when a new review is deleted
        Event::on(
            Entry::class,
            Entry::EVENT_AFTER_DELETE,
            function($event) {
                if ($event->sender && $event->sender->section->handle === 'reviews') {
                    Craft::$app->getCache()->delete(Reviews::CACHE_KEY);
                }
            }
        );

        // Register our custom Reviews service
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;

                // Attach reviews services
                $variable->set('reviews', Reviews::class);
            }
        );

        if (Craft::$app->getRequest()->isCpRequest) {
            // Add our custom “Checkout Pages” source for content editors
            $singleHandles = [
              'checkout',
              'checkoutAddress',
              'checkoutShipping',
              'checkoutSummary',
              'checkoutSuccess',
            ];

            $sectionIds = [];

            // Get the ID for each single’s handle
            foreach ($singleHandles as $handle) {
                if ($section = Craft::$app->getSections()->getSectionByHandle($handle)) {
                    $sectionIds[] = $section->id;
                }
            }

            // Get each single’s corresponding entry ID
            $entryIdsBySectionId = Entry::find()
                ->select(['sectionId', 'elements.id'])
                ->sectionId($sectionIds)
                ->pairs();

            $entryIds = array_map(static function($sectionId) use ($entryIdsBySectionId) {
                return $entryIdsBySectionId[$sectionId];
            }, $sectionIds);

            Event::on(
                Element::class,
                Element::EVENT_REGISTER_SOURCES,
                static function(RegisterElementSourcesEvent $event) use ($entryIds) {
                    $insertAfter = 2;
                    $event->sources = array_merge(
                        array_slice($event->sources, 0, $insertAfter, true),
                        [
                            [
                                'key' => 'checkout',
                                'label' => Craft::t('site', 'Checkout Pages'),
                                'criteria' => [
                                    'id' => $entryIds,
                                    'fixedOrder' => true,
                                ],
                            ]
                        ],
                        array_slice($event->sources, $insertAfter)
                    );
                }
            );
        }
    }
}
