<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace sprig\components;

use Craft;
use craft\base\ElementInterface;
use craft\commerce\elements\db\ProductQuery;
use craft\commerce\elements\Product;
use craft\elements\Category;
use craft\elements\db\CategoryQuery;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use putyourlightson\sprig\base\Component;

/**
 * Class ProductFilter
 *
 * @package sprig\components
 * @property-read ElementInterface[] $products
 * @property-read array[] $filterUrlsByType
 * @property-read Category[]|null|array $materialFilters
 * @property-read Category[]|null|array $colorFilters
 * @property-read array $sortOptions
 * @property-read mixed $pushUrl
 * @property-read bool $isAllBikes
 * @property-read void $types
 */
class ProductFilter extends Component
{
    /**
     * @var string|null
     */
    public $type;

    /**
     * @var array|string
     */
    public $colors = [];

    /**
     * @var array|string
     */
    public $materials = [];

    /**
     * @var string
     */
    public $sort = '';

    /**
     * @var string
     */
    public $currentPushUrl;

    /**
     * @var bool
     */
    public $saveState = true;

    /**
     * @var string|int
     */
    public $elementId;

    /**
     * @inheritdoc
     */
    protected $_template = '_includes/components/filters/filter';

    /**
     * @var null|array
     */
    private $_types;

    /**
     * @var null|array
     */
    private $_materials;

    /**
     * @var null|array
     */
    private $_colors;

    /**
     * @var null|Entry
     */
    private $_landingEntry;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (is_string($this->colors)) {
            $this->colors = (!$this->colors) ? [] : explode('|', $this->colors);
        }

        if (is_string($this->materials)) {
            $this->materials = (!$this->materials) ? [] : explode('|', $this->materials);
        }

        $this->colors = array_filter($this->colors);
        $this->materials = array_filter($this->materials);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            [
                'type',
                'colors',
                'materials',
                'sort',
                'currentPushUrl',
                'saveState',
                'elementId',
            ], 'safe'
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $attributes = parent::attributes();

        $attributes[] = 'colorFilters';
        $attributes[] = 'isAllBikes';
        $attributes[] = 'materialFilters';
        $attributes[] = 'products';
        $attributes[] = 'pushUrl';
        $attributes[] = 'sortOptions';
        $attributes[] = 'types';
        $attributes[] = 'filterUrlsByType';

        return $attributes;
    }

    /**
     * @return array|Category[]|null
     */
    public function getTypes()
    {
        if ($this->_types != null) {
            return $this->_types;
        }

        /** @var CategoryQuery $query */
        $query = Craft::$app->getElements()->createElementQuery(Category::class);
        $query->group('bikeTypes');
        $this->_types = $query->all();

        return $this->_types;
    }

    /**
     * @return array|Category[]|null
     */
    public function getMaterialFilters()
    {
        if ($this->_materials != null) {
            return $this->_materials;
        }

        /** @var CategoryQuery $query */
        $query = Craft::$app->getElements()->createElementQuery(Category::class);
        $query->group('material');

        $this->_materials = $query->all();

        return $this->_materials;
    }

    /**
     * @return array|Category[]|null
     */
    public function getColorFilters()
    {
        if ($this->_colors != null) {
            return $this->_colors;
        }

        /** @var CategoryQuery $query */
        $query = Craft::$app->getElements()->createElementQuery(Category::class);
        $query->group('colors');

        $this->_colors = $query->all();

        return $this->_colors;
    }

    /**
     * @return ElementInterface[]
     */
    public function getProducts(): array
    {
        /** @var ProductQuery $query */
        $query = Craft::$app->getElements()->createElementQuery(Product::class);
        $query->type('bike');

        $colorProductIds = [];
        $materialProductIds = [];
        if (!empty($this->colors)) {
            $selectedColors = array_filter($this->getColorFilters(), function($c) {
                return in_array($c->slug, $this->colors);
            });
            $colorProductIds = Craft::$app->getElements()
                ->createElementQuery(Product::class)
                ->relatedTo(['or', ['targetElement' => $selectedColors, 'field' => 'colors']])
                ->ids();
        }

        if (!empty($this->materials)) {
            $selectedMaterials = array_filter($this->getMaterialFilters(), function($m) {
                return in_array($m->slug, $this->materials);
            });
            $materialProductIds = Craft::$app->getElements()
                ->createElementQuery(Product::class)
                ->relatedTo(['or', ['targetElement' => $selectedMaterials, 'field' => 'material']])
                ->ids();
        }

        if (!empty($colorProductIds) || !empty($materialProductIds)) {
            if (!empty($colorProductIds) && !empty($materialProductIds)) {
                $commonIds = array_intersect($colorProductIds, $materialProductIds);

                // If there are no common products we can't return anything
                if (empty($commonIds)) {
                    return [];
                }

                $query->id($commonIds);
            } else {
                $query->id(!empty($colorProductIds) ? $colorProductIds : $materialProductIds);
            }
        }

        if ($this->type) {
            $type = ArrayHelper::firstWhere($this->getTypes(), 'slug', $this->type);
            $query->relatedTo($type);
        }

        // Sort
        [$sort, $direction] = $this->sort ? explode('|', $this->sort) : ['date', 'desc'];
        if ($sort == 'price') {
            $query->orderBy('defaultPrice ' . $direction);
        } else {
            $query->orderBy('postDate ' . $direction);
        }

        return $query->all();
    }

    /**
     * Sort options for output in the sort select field.
     *
     * @return array
     */
    public function getSortOptions(): array
    {
        $sortOptions = [
            '' => Craft::t('site', 'Newest'),
            'date|asc' => Craft::t('site', 'Oldest'),
            'price|asc' => Craft::t('site', 'Price: Lowest'),
            'price|desc' => Craft::t('site', 'Price: Highest'),
        ];

        array_walk($sortOptions, function(&$item, $key) {
            $item = [
                'value' => $key,
                'label' => $item,
                'selected' => ($this->sort == $key)
            ];
        });

        return $sortOptions;
    }

    /**
     * All URLs for filter items by filter type.
     *
     * @return array[]
     * @throws \yii\base\Exception
     */
    public function getFilterUrlsByType(): array
    {
        $urls = [
            'types' => [],
            'materials' => [],
            'colors' => [],
        ];

        $landingEntry = $this->_getLandingEntry();

        $selectedType = null;
        foreach ($this->getTypes() as $type) {
            $urls['types'][$type->slug] = UrlHelper::siteUrl($type->slug == $this->type ? $landingEntry->uri : $type->uri, $this->_getUrlParams());
            $selectedType = $type->slug == $this->type ? $type : $selectedType;
        }

        $uri = $selectedType->uri ?? ($landingEntry->uri ?? '');
        foreach ($this->getMaterialFilters() as $material) {
            $urls['materials'][$material->slug] = UrlHelper::siteUrl($uri, $this->_getUrlParams('materials', $material->slug));
        }

        foreach ($this->getColorFilters() as $color) {
            $urls['colors'][$color->slug] = UrlHelper::siteUrl($uri, $this->_getUrlParams('colors', $color->slug));
        }

        return $urls;
    }

    /**
     * Returns the URL that should be used in push state.
     *
     * @return string
     * @throws \yii\base\Exception
     */
    public function getPushUrl()
    {
        $page = $this->type ? ArrayHelper::firstWhere($this->getTypes(), 'slug', $this->type) : $this->_getLandingEntry();
        return UrlHelper::siteUrl($page->uri, $this->_getUrlParams());
    }

    /**
     * Return whether we are selecting all bikes or a category subset.
     *
     * @return bool
     */
    public function getIsAllBikes(): bool
    {
        return $this->type ? true : false;
    }

    /**
     * Generate the URL query string parameters for use in generating filter URLS.
     *
     * @param null $key
     * @param null $value
     * @return array
     */
    private function _getUrlParams($key = null, $value = null)
    {
        $urlParams = [];
        if ($this->sort) {
            $urlParams['sort'] = $this->sort;
        }

        if (!empty($this->colors)) {
            $urlParams['colors'] = $this->colors;
            sort($urlParams['colors']);
        }

        if (!empty($this->materials)) {
            $urlParams['materials'] = $this->materials;
            sort($urlParams['materials']);
        }

        $valueKey = isset($urlParams[$key]) ? array_search($value, $urlParams[$key]) : false;
        if ($key && $value && $valueKey === false) {
            $urlParams[$key] = array_merge($urlParams[$key] ?? [], [$value]);
            sort($urlParams[$key]);
        } else if ($valueKey >= 0) {
            unset($urlParams[$key][$valueKey]);
        }

        foreach ($urlParams as $k => &$urlParam) {
            if (empty($urlParam)) {
                unset($urlParams[$k]);
                continue;
            }

            if (is_array($urlParam)) {
                $urlParam = implode('|', $urlParam);
            }
        }

        return $urlParams;
    }

    /**
     * Return the landing page entry.
     *
     * @return Entry|null
     */
    private function _getLandingEntry()
    {
        if ($this->_landingEntry != null) {
            return $this->_landingEntry;
        }

        $this->_landingEntry = Craft::$app->getElements()->createElementQuery(Entry::class)
            ->type('bikesLanding')
            ->one();

        return $this->_landingEntry;
    }
}