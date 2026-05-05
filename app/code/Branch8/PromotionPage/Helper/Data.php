<?php

namespace Branch8\PromotionPage\Helper;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\ConfigInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    /**
     * Product collection factory
     *
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var CacheInterface
     */
    protected $cache;
    const VIP_CATEGORY_CACHE_KEY = 'vip_category_product_ids';
    const SHOW_VIP_FILTER_PATH = 'product_alert/general_settings/show_vip_filter';

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    private $httpContext;

    /**
     * @var ConfigInterface
     */
    private $presentationConfig;

    /**
     * @var ParamsBuilder
     */
    private $imageParamsBuilder;

    private $cacheProductVipIds;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param CategoryRepository $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param CacheInterface $cache
     * @param ResourceConnection $resourceConnection
     * @param Context $httpContext
     * @param ConfigInterface $presentationConfig
     * @param ParamsBuilder $imageParamsBuilder
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        CollectionFactory     $productCollectionFactory,
        CategoryRepository    $categoryRepository,
        StoreManagerInterface $storeManager,
        CacheInterface        $cache,
        ResourceConnection    $resourceConnection,
        Context               $httpContext,
        ConfigInterface       $presentationConfig,
        ParamsBuilder         $imageParamsBuilder
    ) {
        parent::__construct($context);
        $this->categoryRepository = $categoryRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->cache = $cache;
        $this->httpContext = $httpContext;
        $this->resourceConnection = $resourceConnection;
        $this->presentationConfig = $presentationConfig;
        $this->imageParamsBuilder = $imageParamsBuilder;
    }

    /**
     * Check if the product is in the VIP category
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function isProductInVipCategory($product)
    {
        $categoryIds = $this->getProductIdMatchVipLabel();
        return in_array($product->getId(), $categoryIds);
    }

    /**
     * @return array
     */
    public function getProductVipLabelSettings()
    {
        $isLogin=$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        return [
            'productsIds' => join(',',(array)$this->getProductIdMatchVipLabel()),
            'displayVipFilter' => (bool)$this->scopeConfig->getValue(self::SHOW_VIP_FILTER_PATH) && $isLogin,
            'onlyVip' => __('Only Vip'),
            'vipFilterTitle' => __('VIP products')
        ];
    }
    /**
     * Get product IDs that match the VIP category
     *
     * @return array
     */
    public function getProductIdMatchVipLabel()
    {
        if ($this->cacheProductVipIds !== null) {
            return $this->cacheProductVipIds;
        }
        // Determine customer group
        if ($this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)) {
            $customerGroupId = $this->httpContext->getValue('customer_group');
        } else {
            return [];
        }
        $this->cacheProductVipIds = $this->queryVipProductIdsByCustomerGroup($customerGroupId);
        return $this->cacheProductVipIds;
    }

    /**
     * Query VIP product IDs by customer group
     *
     * @param int $customerGroupId
     * @return array
     */
    private function queryVipProductIdsByCustomerGroup($customerGroupId)
    {
        // Query index table
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('catalogrule_is_vip');

        $select = $connection->select()
            ->from($table, ['product_id'])
            ->where('customer_group_id = ?', (int)$customerGroupId);

        return $connection->fetchCol($select);
    }

    public function getAllProductIdsMatchPreOrder()
    {
        $cacheKey = 'preorder_product_ids';

        $cachedData = $this->cache->load($cacheKey);
        if ($cachedData) {
            return json_decode($cachedData, true);
        }

        try {
            $preOrderCollection = $this->productCollectionFactory->create();
            // Only select product IDs and use necessary filters
            $preOrderCollection->addAttributeToSelect('entity_id') // Only select product IDs
            ->addAttributeToFilter('wk_marketplace_preorder', ['notnull' => true])
                ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                ->setPageSize(1000); // Set a limit for large datasets if needed

            // Get all product IDs
            $productIds = $preOrderCollection->getAllIds();

            // Cache the result to improve performance for future requests
            $this->cache->save(json_encode($productIds), $cacheKey, [], 3600); // Cache for 1 hour

            return $productIds;
        } catch (\Exception $e) {
            return []; // Return an empty array if something goes wrong
        }
    }

    /**
     * @return string
     */
    public function getFirstVipCategoryUrl()
    {
        // Get the VIP category ID from configuration
        $vipCategoryId = $this->scopeConfig->getValue(
            'promotion_page/promotion_categories/vip_category',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$vipCategoryId) {
            return '';
        }

        try {
            // Get the category and its children
            $category = $this->categoryRepository->get($vipCategoryId, $this->_storeManager->getStore()->getId());
            $allCategoryIds = $category->getAllChildren(true);

            $customerGroupId = '';
            if ($this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)) {
                $customerGroupId = $this->httpContext->getValue('customer_group');
            }else{
                return '';
            }

            foreach ($allCategoryIds as $categoryId) {
                // Load each category
                $currentCategory = $this->categoryRepository->get($categoryId, $this->_storeManager->getStore()->getId());

                // Check if it has a catalog_price_rule_vip_id
                $vipRuleId = $currentCategory->getData('catalog_price_rule_vip_id');
                if ($vipRuleId) {
                    // Check if this rule applies to the current customer group by querying catalogrule_product
                    $connection = $this->resourceConnection->getConnection();
                    $select = $connection->select()
                        ->from('catalogrule_product', ['product_id']) // We just need to check if any product matches
                        ->where('rule_id = ?', $vipRuleId)
                        ->where('customer_group_id = ?', $customerGroupId)
                        ->limit(1); // Only check for the existence of a product

                    $hasMatchingProduct = $connection->fetchOne($select);

                    if ($hasMatchingProduct) {
                        // Return the URL of the first category that has a rule and matches the customer group
                        return $currentCategory->getUrl();
                    }
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return '';
        }

        return ''; // If no VIP category found that matches the customer group
    }

    /**
     * @return array
     */
    public function checkIsPreorder()
    {
        // Define a unique cache key
        $cacheKey = 'preorder_product_ids_check';

        // Check if cached data exists
        $cachedData = $this->cache->load($cacheKey);
        if ($cachedData) {
            return json_decode($cachedData, true); // Return the cached product IDs if available
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            /*$tbProduct = $connection->getTableName('catalog_product_entity');
            $tbProductInt = $connection->getTableName('catalog_product_entity_int');
            $tbEavAtt = $connection->getTableName('eav_attribute');
            $tbEavAttOptVal = $connection->getTableName('eav_attribute_option_value');

            // Actual value of the attribute_code instead of bind
            $attributeCode = 'index_stock_status'; // Replace this with the actual attribute code you need
            $preorderValue = 'Preorder'; // Include both Preorder and 預購

            // Building the query
            $select = $connection->select()->from(
                ['e' => $tbProduct],
                ['entity_id']
            )->joinLeft(
                ['ei' => $tbProductInt],
                "e.row_id = ei.row_id AND ei.store_id = 0",
                []
            )->joinLeft(
                ['ea' => $tbEavAtt],
                "ei.attribute_id = ea.attribute_id",
                []
            )->joinLeft(
                ['eaov' => $tbEavAttOptVal],
                "ei.value = eaov.option_id AND eaov.store_id = 0",
                ['value' => 'eaov.value']
            )->where(
                'ea.attribute_code = ?', $attributeCode
            )->where(
                'eaov.value = ?', $preorderValue // Check for both 'Preorder' and '預購'
            );*/
            $tbProduct = $connection->getTableName('catalog_product_preorder');
            $select = $connection->select()->from(
                ['e' => $tbProduct],
                ['product_id']
            )->where(
                'e.status = 1'
            );

            // Fetch all matching product IDs
            $productIds = $connection->fetchCol($select); // Fetch all matching entity_ids as an array

            // Cache the result to improve performance for future requests
            $this->cache->save(json_encode($productIds), $cacheKey, [], 3600); // Cache for 1 hour (3600 seconds)

            return $productIds; // Return the product IDs
        } catch (\Exception $e) {
            return []; // Return an empty array if something goes wrong
        }
    }

    /**
     * Get image information for a given image ID
     *
     * @param string $imageId
     * @return array
     */
    public function getInfoImage($imageId)
    {
        $viewImageConfig = $this->presentationConfig->getViewConfig()->getMediaAttributes(
            'Magento_Catalog',
            ImageHelper::MEDIA_TYPE_CONFIG_NODE,
            $imageId
        );

        return $this->imageParamsBuilder->build($viewImageConfig);
    }
}
