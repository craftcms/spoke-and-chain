<?php

namespace modules\demos\widgets;

use Craft;
use craft\base\Widget;

/**
 * Adds a custom “Guide” widget that feels at home in the dashboard.
 * @property-read mixed  $bodyHtml
 * @property-read string $title
 */
class Guide extends Widget
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Guide');
    }

    /**
     * @inheritdoc
     */
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function icon()
    {
        return Craft::getAlias('@appicons/book.svg');
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        $view = Craft::$app->getView();
        $iconsDir = Craft::getAlias('@appicons');

        return $view->renderTemplate('modules/widgets/guide.twig', [
            'bookIcon' => file_get_contents($iconsDir . '/book.svg'),
        ]);
    }
}
