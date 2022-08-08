<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace modules\services;

use Craft;
use craft\commerce\Plugin;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use yii\base\BaseObject;
use yii\base\Exception;

/**
 * Class Reviews
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @package modules\services
 */
class Reviews extends BaseObject
{
    /**
     * @var string
     */
    public const CACHE_KEY = 'reviews:averageRatings';

    /**
     * Get the average review rating by the product ID.
     *
     * @param int|null $id
     * @return mixed|null
     * @throws Exception
     */
    public function getAverageRatingByProductId($id)
    {
        if (!$id) {
            return null;
        }

        $product = Plugin::getInstance()->getProducts()->getProductById($id);

        if (!$product) {
            throw new Exception(Craft::t('commerce', 'No product exists with the ID “{id}”.', ['id' => $id]));
        }

        if (!Craft::$app->getCache()->exists(self::CACHE_KEY)) {
            $this->_cacheAverageRatings();
        }

        $averageRatings = Craft::$app->getCache()->get(self::CACHE_KEY);

        return $averageRatings[$id] ?? null;
    }

    /**
     * Create the average ratings cache.
     */
    private function _cacheAverageRatings(): void
    {
        /** @var EntryQuery $query */
        $query = Craft::$app->getElements()->createElementQuery(Entry::class);
        $query->section('reviews');
        $query->with(['product']);
        $query->status('live');
        $reviews = $query->all();

        $reviewsByProductId = [];
        foreach ($reviews as $review) {
            $product = $review->product[0] ?? null;
            if (!$product) {
                continue;
            }

            if (!isset($reviewsByProductId[$product->id])) {
                $reviewsByProductId[$product->id] = ['total' => 0, 'reviews' => []];
            }

            /** @phpstan-ignore-next-line */
            $reviewsByProductId[$product->id]['total'] += $review->stars;
            $reviewsByProductId[$product->id]['reviews'][] = $review;
        }

        $averageRatingByProductId = [];
        foreach ($reviewsByProductId as $productId => $r) {
            $averageRatingByProductId[$productId] = round($r['total'] / count($r['reviews']), 1);
        }

        Craft::$app->getCache()->set(self::CACHE_KEY, $averageRatingByProductId, 86400 * 3);
    }
}
