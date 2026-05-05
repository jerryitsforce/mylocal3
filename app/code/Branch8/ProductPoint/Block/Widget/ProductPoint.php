<?php

namespace Branch8\ProductPoint\Block\Widget;

use Branch8\GA4\Helper\ProductHelper;
use Branch8\HotaiPoint\Helper\Data as HotaiPointHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Model\Rule;
use Branch8\PointMoneyCollect\Helper\Data as PointHelper;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutFactory;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Widget\Helper\Conditions;
use Magento\Framework\HTTP\Client\CurlFactory;

/**
 * Point Products Widget
 */
class ProductPoint extends ProductsList
{
    /**
     * Json Serializer Instance
     *
     * @var Json
     */
    private $json;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var Grouped
     */
    protected $grouped;

    /**
     * @var Configurable
     */
    protected $configurable;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @var mixed
     */
    protected mixed $filterRange = null;

    /**
     * @var mixed
     */
    protected mixed $cloneCollection = null;

    /**
     * @var mixed
     */
    protected mixed $categoryAll = null;

    /**
     * @var float
     */
    private  $pointConvertRate = 1;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var HotaiPointHelper
     */
    protected HotaiPointHelper $hotaiPointHelper;
    private ProductHelper $productHelper;

    /**
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param Visibility $catalogProductVisibility
     * @param HttpContext $httpContext
     * @param SqlBuilder $sqlBuilder
     * @param Rule $rule
     * @param Conditions $conditionsHelper
     * @param Grouped $grouped
     * @param Configurable $configurable
     * @param ResourceConnection $resourceConnection
     * @param CurlFactory $curlFactory
     * @param array $data
     * @param Json|null $json
     * @param LayoutFactory|null $layoutFactory
     * @param EncoderInterface|null $urlEncoder
     * @param CategoryRepositoryInterface|null $categoryRepository
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        HttpContext $httpContext,
        SqlBuilder $sqlBuilder,
        Rule $rule,
        Conditions $conditionsHelper,
        Grouped $grouped,
        Configurable $configurable,
        ResourceConnection $resourceConnection,
        CurlFactory $curlFactory,
        PointHelper $pointHelper,
        HotaiPointHelper $hotaiPointHelper,
        ProductHelper $productHelper,
        array $data = [],
        Json $json = null,
        LayoutFactory $layoutFactory = null,
        EncoderInterface $urlEncoder = null,
        CategoryRepositoryInterface $categoryRepository = null
    ) {
        $this->grouped = $grouped;
        $this->configurable = $configurable;
        $this->resourceConnection = $resourceConnection;
        $this->curlFactory = $curlFactory;
        $this->hotaiPointHelper = $hotaiPointHelper;
        $this->productHelper = $productHelper;
        $this->pointConvertRate =  $pointHelper->getPointConvertRate();
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
        $this->categoryRepository = $categoryRepository ?? ObjectManager::getInstance()
            ->get(CategoryRepositoryInterface::class);
        parent::__construct(
            $context,
            $productCollectionFactory,
            $catalogProductVisibility,
            $httpContext,
            $sqlBuilder,
            $rule,
            $conditionsHelper,
            $data,
            $json,
            $layoutFactory,
            $urlEncoder,
            $categoryRepository
        );
    }

    /**
     * Get key pieces for caching block content
     *
     * @return array
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     * @throws NoSuchEntityException
     */
    public function getCacheKeyInfo()
    {
        $conditions = $this->getData('conditions')
            ? $this->getData('conditions')
            : $this->getData('conditions_encoded');

        return [
            'CATALOG_PRODUCTS_LIST_POINT_WIDGET',
            $this->getPriceCurrency()->getCurrency()->getCode(),
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
            $this->httpContext->getValue('customer_id'),
            $this->json->serialize($this->httpContext->getValue('tax_rates')),
            (int)$this->getRequest()->getParam($this->getData('page_var_name'), 1),
            $this->getProductsPerPage(),
            $this->getProductsCount(),
            $conditions,
            (int)$this->getRequest()->getParam('isAjax', 0),
            $this->getTemplate(),
            $this->getTitle()
        ];
    }

    /**
     * Prepare and return product collection
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     */
    protected function _beforeToHtml()
    {
        if (!$this->getData('isAjax')) {
            return AbstractBlock::_beforeToHtml();
        }
        return parent::_beforeToHtml();
    }

    /**
     * Prepare and return product collection
     *
     * @return Collection
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     * @throws LocalizedException
     */
    public function createCollection()
    {
        $pointUser = $this->hotaiPointHelper->getHotaiPoint();
        /*if (!$pointUser) {
            return $this->productCollectionFactory->create()->addIdFilter([-1]);
        }*/
        $pointRange = $this->getPointRange();
        $pointFilter = [];
        array_multisort(array_column($pointRange, 'to'), SORT_ASC, $pointRange);
        foreach ($pointRange as $range) {
            if ($pointUser >= $range['from'] && $pointUser <= $range['to']) {
                $rec1 = explode('~', $range['rec1']);
                $rec2 = explode('~', $range['rec2']);
                $rec3 = explode('~', $range['rec3']);
                $pointFilter[1]['from'] = $rec1[0];
                $pointFilter[1]['to'] = $rec1[1] + $pointUser;
                $pointFilter[1]['title'] = $rec1[1];
                $pointFilter[2]['from'] = $rec2[0] + $pointUser;
                $pointFilter[2]['to'] = $rec2[1] + $pointUser;
                $pointFilter[2]['title'] = $rec2[1];
                $pointFilter[3]['from'] = $rec3[0] + $pointUser;
                $pointFilter[3]['to'] = $rec3[1] + $pointUser;
                $pointFilter[3]['title'] = $rec3[1];
                break;
            }
        }
        $this->filterRange = $pointFilter;

        /** @var $collection Collection */
        $collection = $this->productCollectionFactory->create();

        if ($this->getData('store_id') !== null) {
            $collection->setStoreId($this->getData('store_id'));
        }

        $collection->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());

        /**
         * Change sorting attribute to entity_id because created_at can be the same for products fastly created
         * one by one and sorting by created_at is indeterministic in this case.
         */
        $collection = $this->_addProductAttributesAndPrices($collection)
            ->addStoreFilter()
            ->addAttributeToFilter(
                array(
                    array('attribute' => 'hide_product_on_search', 'null' => true),
                    array('attribute' => 'hide_product_on_search', 'eq' => 0),
                ),
                '',
                'left'
            )
            ->addAttributeToSelect(['livesearch_instock', 'index_stock_status'])
            ->setPageSize($this->getPageSize())
            ->setCurPage($this->getRequest()->getParam($this->getData('page_var_name'), 1));
        $conditions = $this->getConditions();
        $conditions->collectValidatedAttributes($collection);
        $this->sqlBuilder->attachConditionToCollection($collection, $conditions);

        /**
         * Prevent retrieval of duplicate records. This may occur when multiselect product attribute matches
         * several allowed values from condition simultaneously
         */
        $collection->distinct(true);
        $this->cloneCollection = clone $collection;

        return $collection;
    }

    /**
     * Get collection by range point
     *
     * @param Collection $collection
     * @param array $filter
     * @return Collection
     */
    public function getCollectionByRangePoint($filter)
    {

        $collection = clone $this->cloneCollection;
        $collection->getSelect()
            ->where('price_index.min_price >= ' . ($filter['from'] * $this->pointConvertRate))
            ->where('price_index.min_price <= ' . ($filter['to'] * $this->pointConvertRate))
            ->order('price_index.min_price DESC');

        return $collection->getItems();
    }

    /**
     * Get currency of product
     *
     * @return PriceCurrencyInterface
     * @deprecated
     * @see Constructor injection
     */
    private function getPriceCurrency()
    {
        if ($this->priceCurrency === null) {
            $this->priceCurrency = ObjectManager::getInstance()
                ->get(PriceCurrencyInterface::class);
        }
        return $this->priceCurrency;
    }

    /**
     * Retrieve config for exclude attributes.
     *
     * @return array
     */
    public function getPointRange(): array
    {
        $items = [];
        $configs = (string)$this->_scopeConfig->getValue('hotai_point/pagebuilder/point_range');
        if (!empty($configs) && $configs !== '[]') {
            foreach ($this->json->unserialize($configs) as $item) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Get Store Id
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * Get Point Filter Range
     *
     * @return mixed
     */
    public function getFilterRange()
    {
        return $this->filterRange;
    }

    /**
     * Get View All Url
     *
     * @param $filter
     * @return string
     * @throws NoSuchEntityException
     */
    public function getViewAllUrl($filter)
    {
        if (!$this->categoryAll) {
            $categoryId = $this->_scopeConfig->getValue('b8brand/general/product_category');
            $category = $this->categoryRepository->get($categoryId, $this->_storeManager->getStore()->getId());
            $this->categoryAll = $category;
        }

        return $this->categoryAll->getUrl().'?price='.$filter;
    }

    public function getGA4ItemJson($_item, $index = 1)
    {
        return $this->productHelper->getGA4ItemJson($_item, $index);
    }
}
