<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\LimitPurchased\Model\QtyCondition;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Branch8\LimitPurchased\Model\CacheStorage\CacheProduct;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Phrase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\Bundle\Model\Product\Type as ProductTypeBundle;
use Magento\GroupedProduct\Model\Product\Type\Grouped as ProductTypeGrouped;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ProductTypeConfigurable;

/**
 * @inheritdoc
 */
class LimitPurchasedCondition implements IsProductSalableForRequestedQtyInterface
{
    const XML_PATH_LIMIT_PURCHASED_ORDER_STATUSES = 'limit_purchased/general/order_statuses';

    /**
     * @var ProductSalabilityErrorInterfaceFactory
     */
    private $productSalabilityErrorFactory;

    /**
     * @var ProductSalableResultInterfaceFactory
     */
    private $productSalableResultFactory;

    /**
     * @var CacheProduct
     */
    private $cacheProduct;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var ProductTypeConfigurable
     */
    private $configurableType;

    /**
     * @var ProductTypeBundle
     */
    private $bundleType;

    /**
     * @var ProductTypeGrouped
     */
    private $groupType;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    protected $cacheAttribute = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * IsCorrectQtyCondition constructor.
     *
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     * @param CacheProduct $cacheProduct
     * @param ProductRepository $productRepository
     * @param TimezoneInterface $timezone
     * @param HttpContext $httpContext
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param ProductTypeBundle $bundleType
     * @param ProductTypeGrouped $groupType
     * @param ProductTypeConfigurable $configurableType
     * @param CustomerSession $customerSession
     * @param ResourceConnection $resourceConnection
     * @param EavConfig $eavConfig
     */
    public function __construct(
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        ProductSalableResultInterfaceFactory $productSalableResultFactory,
        CacheProduct $cacheProduct,
        ProductRepository $productRepository,
        TimezoneInterface $timezone,
        HttpContext $httpContext,
        OrderItemRepositoryInterface $orderItemRepository,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        ProductTypeBundle $bundleType,
        ProductTypeGrouped $groupType,
        ProductTypeConfigurable $configurableType,
        CustomerSession $customerSession,
        ResourceConnection $resourceConnection,
        EavConfig $eavConfig,
        LoggerInterface $logger,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->productSalabilityErrorFactory = $productSalabilityErrorFactory;
        $this->productSalableResultFactory = $productSalableResultFactory;
        $this->cacheProduct = $cacheProduct;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->timezone = $timezone;
        $this->httpContext = $httpContext;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_scopeConfig = $scopeConfig;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->bundleType = $bundleType;
        $this->groupType = $groupType;
        $this->configurableType = $configurableType;
        $this->customerSession = $customerSession;
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId, float $requestedQty): ProductSalableResultInterface
    {
        try {
            // Smart Bypass Logic for AddToCart and Unchecked Items
            try {
                $action = $this->request->getFullActionName();
                $isAddAction = in_array($action, ['checkout_cart_add', 'catalogcustom_cart_add']);
                
                $bypassValidation = false;

                $isCalledByCartObserver = \Branch8\LimitPurchased\Observer\CheckCartLimit::$isExecuting;
                if (!$isCalledByCartObserver) {
                    $action = $this->request->getFullActionName();
                    $isUpdateQtyAction = in_array($action, [
                        'checkout_cart_updateitemqty', 
                        'checkout_cart_updatePost',
                        'splitcart_cart_updatePost'
                    ]);

                    if (!$isUpdateQtyAction) {
                        $quote = $this->checkoutSession->getQuote();
                        if ($quote && $quote->getId()) {
                            foreach ($quote->getAllItems() as $quoteItem) {
                                if ($quoteItem->getSku() == $sku) {
                                    if ($quoteItem->hasData('available_to_checkout') && !$quoteItem->getData('available_to_checkout')) {
                                        $bypassValidation = true;
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    if ($isAddAction && !$bypassValidation) {
                        $addedProductId = (int) $this->request->getParam('product');
                        if ($addedProductId) {
                            $thisProduct = $this->getProductFromCache($sku);
                            if ($thisProduct->getId() != $addedProductId) {
                                $isRelated = false;
                                $parents = $this->getParentProductIds($thisProduct->getId());
                                if (in_array($addedProductId, $parents)) {
                                    $isRelated = true;
                                }
                                if (!$isRelated) {
                                    $bypassValidation = true;
                                }
                            }
                        }
                    }
                }

                if ($bypassValidation) {
                    return $this->productSalableResultFactory->create(['errors' => []]);
                }
            } catch (\Exception $ex) {}

            $result = $this->getProductAttributeValueBySku($sku, 'limit_purchased_enable');
            if (empty($result) || !isset($result['value']) || !isset($result['entity_id'])) {
                return $this->productSalableResultFactory->create(['errors' => []]);
            }
            if ($result['value'] == 1) {
                /** @var ProductInterface $product */
                $product = $this->getProductFromCache($sku);

                $error_message = $this->isMaxSaleQuantityCheckFailed($product, $requestedQty);
                if (!empty($error_message)) {
                    return $this->createErrorResult(
                        'is_correct_qty-limit_purchased',
                        $error_message
                    );
                }
            }

            // Check parent product by configurableType
            $parents = $this->getParentProductIds($result['entity_id']);
            if(count($parents)){
                foreach($parents as $id){
                    /** @var ProductInterface $productParent */
                    $productParent = $this->getProductFromCache((int)$id);
                    $error_message = $this->isMaxSaleQuantityCheckFailed($productParent, $requestedQty);
                    if(!empty($error_message)){
                        return $this->createErrorResult(
                            'is_correct_qty-limit_purchased',
                            $error_message
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            return $this->createErrorResult(
                'is_correct_qty-limit_purchased',
                __($e->getMessage())
            );
        }

        return $this->productSalableResultFactory->create(['errors' => []]);
    }

    /**
     * Get list parent product ids by product
     *
     * @param $productId
     * @return array
     */
    public function getParentProductIds($productId){
        $parentsConfigurable = $this->configurableType->getParentIdsByChild($productId);
        if(count($parentsConfigurable)){
            return $parentsConfigurable;
        }

        $parentsBundle = $this->bundleType->getParentIdsByChild($productId);
        if(count($parentsBundle)){
            return $parentsBundle;
        }

        $parentsGroup = $this->groupType->getParentIdsByChild($productId);
        if(count($parentsGroup)){
            return $parentsGroup;
        }

        return [];
    }

    /**
     * Create Error Result Object
     *
     * @param string $code
     * @param Phrase $message
     * @return ProductSalableResultInterface
     */
    private function createErrorResult(string $code, $message): ProductSalableResultInterface
    {
        $errors = [
            $this->productSalabilityErrorFactory->create([
                'code' => $code,
                'message' => $message
            ])
        ];
        return $this->productSalableResultFactory->create(['errors' => $errors]);
    }


    /**
     * Check if max sale condition is satisfied
     *
     * @param ProductInterface $product
     * @param float $requestedQty
     * @return mixed
     * @throws \Exception
     */
    private function isMaxSaleQuantityCheckFailed(
        ProductInterface $product,
        float $requestedQty
    ): mixed
    {
        // Maximum Qty Allowed in Shopping Cart
        $error_message = '';
        
        $isEnabled = $this->_scopeConfig->isSetFlag(
            'limit_purchased/general/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $prefix = $isEnabled ? "\xE2\x80\x8B\xE2\x80\x8B\xE2\x80\x8B" : '';

        if(
            $product->getId() &&
            $product->getLimitPurchasedEnable()
        ){
            $now = $this->timezone->date()->getTimestamp();
            $start_time = $product->getLimitPurchasedStartTime() ? $this->timezone->date(new \DateTime($product->getLimitPurchasedStartTime()))->getTimestamp() : '';
            $end_time = $product->getLimitPurchasedEndTime() ? $this->timezone->date(new \DateTime($product->getLimitPurchasedEndTime()))->getTimestamp() : '';
            $isInsideWindow = ($start_time && $end_time && $start_time <= $now && $now <= $end_time)
                || ($start_time && !$end_time && $start_time <= $now)
                || (!$start_time && $end_time && $end_time >= $now)
                || (!$start_time && !$end_time);

            if ($isInsideWindow) {
                $isLoggedIn = $this->isLoggedIn();
                $currentGroupId = $this->getCustomerGroupId();
                $allowedGroupsRaw = $product->getLimitPurchasedCustomerGroup();

                if ($isLoggedIn && !empty($allowedGroupsRaw)) {
                    $limitPurchasedCustomerGroup = array_filter(explode(',', (string)$allowedGroupsRaw));
                    if (!count($limitPurchasedCustomerGroup) ||
                        !in_array($currentGroupId, $limitPurchasedCustomerGroup)) {
                        $error_message = $prefix . __('You are not in the group of customers who can make a purchase');
                    }
                } else {
                    $error_message = $prefix . __('This product is available for purchase by specific membership types only.');
                }
                
                if (!empty($error_message)) {
                    return $error_message;
                }

                if(!empty($product->getLimitPurchasedQty())){
                    $limitQty = (int)$product->getLimitPurchasedQty();
                    $qtyOrdered = $this->getQtyOrdered($product, (int) $this->getCustomerId());
                    $requestedQty += $qtyOrdered;
                    if ($requestedQty > $limitQty) {
                        $remainingQty = $limitQty - $qtyOrdered;
                        if($remainingQty < 0){
                            $remainingQty = 0;
                        }
                        $error_message = $prefix . __('You can purchase up to %1 of this product.', (int)$remainingQty);
                    }
                }
            }
        }

        return $error_message;
    }

    /**
     * Get Product by sku.  Uses cache.
     *
     * @param $sku
     * @return ProductInterface
     * @throws LocalizedException
     */
    private function getProductFromCache($sku): ProductInterface
    {
        if ($this->cacheProduct->get($sku)) {
            return $this->cacheProduct->get($sku);
        }

        try {
            if (is_numeric($sku)) {
                $product = $this->productRepository->getById((int)$sku);
            } else {
                $product = $this->productRepository->get($sku);
            }
        } catch (\Exception $e) {
            throw new NoSuchEntityException(
                __($e->getMessage())
            );
        }

        /* Avoid add to cache a new item */
        if ($product->getId()) {
            $this->cacheProduct->set($sku, $product);
        }
        return $product;
    }

    /**
     * Get Product by sku.  Uses cache.
     *
     * @param ProductInterface $product
     * @param int $customer_id
     * @return int
     * @throws LocalizedException
     */
    private function getQtyOrdered(ProductInterface $product, $customer_id): int
    {
        $sku = $product->getSku();
        if ($this->cacheProduct->getQtyOrdered($sku)) {
            return $this->cacheProduct->getQtyOrdered($sku);
        }
        $qtyOrdered = $this->getQtyOrderedFromOrderHistory($product, $customer_id);
        /* Avoid add to cache a new item */
        $this->cacheProduct->setQtyOrdered($sku, $qtyOrdered);

        return $qtyOrdered;
    }

    /**
     * Get QtyOrdered from order history by sku.
     *
     * @param ProductInterface $product
     * @param int $customer_id
     * @return int
     * @throws LocalizedException
     */
    private function getQtyOrderedFromOrderHistory($product, $customerId): int
    {
        if (!$customerId) {
            return 0; // Safety guard: ignore guest aggregation to prevent mixing guest orders
        }

        $limit_purchased_order_statuses = $this->_scopeConfig->getValue(self::XML_PATH_LIMIT_PURCHASED_ORDER_STATUSES);
        if (empty($limit_purchased_order_statuses)) {
            return 0;
        }

        $limit_purchased_order_statuses = explode(',', $limit_purchased_order_statuses);
        $limit_purchased_order_statuses = array_filter($limit_purchased_order_statuses);

        if (empty($limit_purchased_order_statuses)) {
            return 0;
        }
        
        $connection = $this->resourceConnection->getConnection();
        $orderTable = $this->resourceConnection->getTableName('sales_order');
        $itemTable = $this->resourceConnection->getTableName('sales_order_item');

        $select = $connection->select()
            ->from(['o' => $orderTable], [])
            ->joinInner(
                ['i' => $itemTable],
                'o.entity_id = i.order_id',
                ['total_qty' => new \Zend_Db_Expr('SUM(i.qty_ordered)')]
            )
            ->where('o.customer_id = ?', (int)$customerId)
            ->where('i.product_id = ?', (int)$product->getId())
            ->where('o.status IN (?)', $limit_purchased_order_statuses);

        if ($product->getLimitPurchasedStartTime()) {
            $select->where('o.created_at >= ?', $product->getLimitPurchasedStartTime());
        }
        if ($product->getLimitPurchasedEndTime()) {
            $select->where('o.created_at <= ?', $product->getLimitPurchasedEndTime());
        }

        $qtyOrdered = (int)$connection->fetchOne($select);

        return $qtyOrdered;
    }

    protected function getCustomerId(){
        if($this->httpContext->getValue('customer_id')){
            return $this->httpContext->getValue('customer_id');
        }
        return $this->customerSession->getCustomerId();
    }

    protected function getCustomerGroupId(){
        if($this->httpContext->getValue(CustomerContext::CONTEXT_GROUP)){
            return $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
        }
        return $this->customerSession->getCustomerGroupId();
    }

    /**
     * Check Is Logged In
     *
     * @return bool
     */
    protected function isLoggedIn()
    {
        if($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)){
            return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
        }
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get product attribute value by SKU without loading the product.
     *
     * @param string $sku
     * @param string $attributeCode
     * @param int|null $storeId
     * @return mixed|null
     * @throws LocalizedException
     */
    private function getProductAttributeValueBySku(string $sku, string $attributeCode, ?int $storeId = null)
    {
        if (isset($this->cacheAttribute[$sku][$attributeCode])) {
            return $this->cacheAttribute[$sku][$attributeCode];
        }
        $connection = $this->resourceConnection->getConnection();
        $catalogProductEntityTable = $connection->getTableName('catalog_product_entity');

        // Get attribute details
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);

        if (!$attribute->getId()) {
            return null; // Attribute not found
        }

        $attributeBackendTable = $attribute->getBackendTable();
        $linkField = 'row_id'; // Typically 'row_id' for M2.3+ EE, 'entity_id' for CE

        // Support numeric product ID (e.g. from cart item getProductId())
        // Cart item getSku() appends custom option suffixes like "-BB", so product ID is more reliable
        $isNumericId = is_numeric($sku);

        $select = $connection->select()
            ->from(['e' => $catalogProductEntityTable], 'e.entity_id');

        if ($isNumericId) {
            $select->where('e.entity_id = ?', (int)$sku);
        } else {
            $select->where('e.sku = ?', $sku);
        }

        $select->limit(1);

        if ($attribute->isScopeGlobal() || $storeId === null || $storeId === 0) {
            // Global scope or default store
            $select->joinLeft(
                ['vd' => $attributeBackendTable],
                "e.{$linkField} = vd.{$linkField} AND vd.store_id = 0 AND vd.attribute_id = :attribute_id",
                ['value']
            );
        } else {
            // Store-specific scope
            $select->joinLeft(
                ['vd' => $attributeBackendTable],
                "e.{$linkField} = vd.{$linkField} AND vd.store_id = 0 AND vd.attribute_id = :attribute_id",
                []
            )->joinLeft(
                ['vs' => $attributeBackendTable],
                "e.{$linkField} = vs.{$linkField} AND vs.store_id = :store_id AND vs.attribute_id = :attribute_id",
                ['value' => $connection->getCheckSql('vs.value_id IS NULL', 'vd.value', 'vs.value')]
            );
        }

        $bind = [
            ':attribute_id' => (int)$attribute->getId(),
        ];

        if (!$attribute->isScopeGlobal() && $storeId !== null && $storeId !== 0) {
            $bind[':store_id'] = $storeId;
        }

        return $this->cacheAttribute[$sku][$attributeCode] = $connection->fetchRow($select, $bind);
    }

    /**
     * Get Logger
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
