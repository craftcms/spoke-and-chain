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
use craft\helpers\StringHelper;
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
 * @property-read int $filterCount
 * @property-read int $productCount
 * @property-read void $types
 */
class ProductFilter extends Component
{
    /**
     * @var string|null
     */
    public ?string $type;

    /**
     * @var array|string
     */
    public string|array $colors = [];

    /**
     * @var array|string
     */
    public string|array $materials = [];

    /**
     * @var string
     */
    public string $sort = '';

    /**
     * @var string
     */
    public string $currentPushUrl = '';

    /**
     * @var bool
     */
    public bool $saveState = true;

    /**
     * @var string|int
     */
    public string|int|null $elementId = null;

    /**
     * @inheritdoc
     */
    protected ?string $_template = '_includes/components/filters/filter';

    /**
     * @var null|array
     */
    private ?array $_types = null;

    /**
     * @var null|array
     */
    private ?array $_materials = null;

    /**
     * @var null|array
     */
    private ?array $_colors = null;

    /**
     * @var null|Entry
     */
    private ?Entry $_landingEntry = null;

    /**
     * @var null|Product[]
     */
    private ?array $_products = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (is_string($this->colors)) {
            $this->colors = (!$this->colors) ? [] : explode('|', $this->colors);
        }

        if (is_array($this->colors) && !empty($this->colors)) {
            $this->colors = array_filter($this->colors);
            array_walk($this->colors, fn($c) => StringHelper::escape($c));
        }

        if (is_string($this->materials)) {
            $this->materials = (!$this->materials) ? [] : explode('|', $this->materials);
        }

        if (is_array($this->materials) && !empty($this->materials)) {
            $this->materials = array_filter($this->materials);
            array_walk($this->materials, fn($c) => StringHelper::escape($c));
        }
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
    public function attributes(): array
    {
        $attributes = parent::attributes();

        $attributes[] = 'colorFilters';
        $attributes[] = 'filterCount';
        $attributes[] = 'filterUrlsByType';
        $attributes[] = 'isAllBikes';
        $attributes[] = 'materialFilters';
        $attributes[] = 'productCount';
        $attributes[] = 'products';
        $attributes[] = 'pushUrl';
        $attributes[] = 'sortOptions';
        $attributes[] = 'types';

        return $attributes;
    }

    /**
     * @return array|Category[]|null
     */
    public function getTypes(): ?array
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
    public function getMaterialFilters(): ?array
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
    public function getColorFilters(): ?array
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
        if ($this->_products !== null) {
            return $this->_products;
        }

        /** @var ProductQuery $query */
        $query = Craft::$app->getElements()->createElementQuery(Product::class);
        $query->type('bike');

        // Eager loading
        $query->with(['bikeType', 'colors', 'mainImage']);

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

        $this->_products = $query->all();

        return $this->_products;
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
    public function getPushUrl(): string
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
     * @return int
     */
    public function getProductCount(): int
    {
        return count($this->getProducts());
    }

    /**
     * @return int
     */
    public function getFilterCount(): int
    {
        return ($this->type ? 1 : 0) + count($this->colors) + count($this->materials);
    }

    /**
     * Generate the URL query string parameters for use in generating filter URLS.
     *
     * @param null $key
     * @param null $value
     * @return array
     */
    private function _getUrlParams($key = null, $value = null): array
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
    private function _getLandingEntry(): ?Entry
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