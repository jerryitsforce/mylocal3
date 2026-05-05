<?php

namespace Branch8\BestSeller\Block\Widget;

use Branch8\GA4\Helper\ProductHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Helper\Stock;
use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\PageBuilder\Model\Catalog\Sorting;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory as BestSellersCollectionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Widget\Helper\Conditions;

/**
 * Best Seller Products Widget
 */
class BestSellerProduct extends ProductsList
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
        $this->productHelper = $productHelper;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
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
        $requestUri = $this->getRequest()->getRequestUri();
        $isAdmin = false;
        if (str_contains($requestUri, 'admin/pagebuilder/stage/preview') || str_contains($requestUri, 'htposcms/pagebuilder/stage/preview')) {
            $isAdmin = true;
        }

        return [
            'CATALOG_PRODUCTS_LIST_BEST_SELLER_WIDGET',
            $this->getPriceCurrency()->getCurrency()->getCode(),
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
            $this->json->serialize($this->httpContext->getValue('tax_rates')),
            (int)$this->getRequest()->getParam($this->getData('page_var_name'), 1),
            $this->getProductsPerPage(),
            $this->getProductsCount(),
            $conditions,
            (int)$this->getRequest()->getParam('isAjax', 0),
            $isAdmin,
            $this->getTemplate(),
            $this->getTitle()
        ];
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
        $productIds = $this->getProductParentIds();
        if (empty($productIds)) {
            return $this->productCollectionFactory->create()->addIdFilter([-1]);
        }
        $productIds = array_unique($productIds);

        /** @var $collection Collection */
        $collection = $this->productCollectionFactory->create()->addIdFilter($productIds);

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

        $collection->getSelect()->order(new \Zend_Db_Expr("FIELD(e.entity_id, ".implode(",",$productIds).")"));
        $conditions = $this->getConditions();
        $conditions->collectValidatedAttributes($collection);
        $this->sqlBuilder->attachConditionToCollection($collection, $conditions);

        /**
         * Prevent retrieval of duplicate records. This may occur when multiselect product attribute matches
         * several allowed values from condition simultaneously
         */
        $collection->distinct(true);

        return $collection;
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
     *
     * @return array
     */
    private function getProductParentIds()
    {
        $pastDays = $this->hasData('past_days') ? $this->getData('past_days') : 60;
        $conversionRate = $this->hasData('conversion_rate') ? $this->getData('conversion_rate') : 0.95;
        $connection = $this->resourceConnection->getConnection();
        $storeId = $this->getStoreId();
        $tblBestSeller = $connection->getTableName('sales_bestsellers_aggregated_daily');
        $tblViewed = $connection->getTableName('report_viewed_product_aggregated_daily');
        $collection = $connection->fetchAll("
SELECT sbd.product_id, SUM(sbd.qty_ordered) AS `qty_ordered`, rvd.views_num
FROM `{$tblBestSeller}` as sbd
LEFT JOIN (
    SELECT product_id, SUM(views_num) as views_num
    FROM `{$tblViewed}`
    WHERE period >= DATE_SUB(CURDATE(), INTERVAL {$pastDays} DAY)
      AND store_id = '{$storeId}'
      AND rating_pos <= 5
    GROUP BY product_id
) as rvd ON sbd.product_id = rvd.product_id
WHERE sbd.store_id = '{$storeId}'
  AND qty_ordered/COALESCE(views_num, 1) < {$conversionRate}
  AND sbd.period >= DATE_SUB(CURDATE(), INTERVAL {$pastDays} DAY)
  AND sbd.rating_pos <= 5
GROUP BY sbd.product_id
ORDER BY qty_ordered DESC");

        $productIds = [];
        foreach ($collection as $product) {
            $productId = $product['product_id'];

            $parentIdsGroup  = $this->grouped->getParentIdsByChild($productId);
            $parentIdsConfig = $this->configurable->getParentIdsByChild($productId);
            if (!empty($parentIdsGroup)) {
                foreach ($parentIdsGroup as $parentId)
                    $productIds[] = $parentId;
            } elseif (!empty($parentIdsConfig)) {
                foreach ($parentIdsConfig as $parentId)
                    $productIds[] = $parentId;
            } else {
                $productIds[] = $productId;
            }
        }

        return $productIds;
    }

    /**
     * Get Store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }


    public function getGA4Settings()
    {
        return new \Magento\Framework\DataObject(
            [
                'item_list_id' => $this->getData('item_list_id'),
                'item_list_name' => $this->getData('item_list_name'),
                'promotion_id' => $this->getData('promotion_id'),
                'promotion_name' => $this->getData('promotion_name'),
            ]
        );
    }

    public function getUrlAdditionalParams()
    {
        $additionalParams = [];
        if ($this->getData('item_list_id')) {
            $additionalParams['item_list_id'] = $this->getData('item_list_id');
        }
        if ($this->getData('item_list_name')) {
            $additionalParams['item_list_name'] = $this->getData('item_list_name');
        }
        if ($this->getData('promotion_id')) {
            $additionalParams['promotion_id'] = $this->getData('promotion_id');
        }
        if ($this->getData('promotion_name')) {
            $additionalParams['promotion_name'] = $this->getData('promotion_name');
        }
        return $additionalParams ? ['_query' => $additionalParams] : [];
    }

    public function getGA4ItemJson($_item, $index = 1)
    {
        return $this->productHelper->getGA4ItemJson($_item, $index);
    }
}
