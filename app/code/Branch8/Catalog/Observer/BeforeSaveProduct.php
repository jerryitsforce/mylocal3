<?php

namespace Branch8\Catalog\Observer;

use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\Store;
use Magento\User\Model\UserFactory;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\Marketplace\Model\Product;
use Webkul\SellerSubAccount\Helper\Data as SellerSubAccountHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class BeforeSaveProduct implements ObserverInterface{

    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_Catalog::TrackingProductStatusChange';

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var AuthSession
     */
    private AuthSession $authSession;

    /**
     * @var MarketplaceHelper
     */
    private MarketplaceHelper $marketplaceHelper;

    /**
     * @var SellerSubAccountHelper
     */
    private SellerSubAccountHelper $sellerSubAccountHelper;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var UserFactory
     */
    protected UserFactory $userFactory;

    /**
     * @var CustomerFactory
     */
    protected CustomerFactory $customerModel;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;

    /**
     * @var \Magento\Framework\App\State
     */
    protected \Magento\Framework\App\State $state;

    private VersionManager $versionManager;

    private MarketplaceProductManagement $marketplaceProductManagement;

    protected $mkConfigHelper;

    protected $helperImages;

    public function __construct(
        CategoryRepository $categoryRepository,
        LoggerInterface $logger,
        UserContextInterface $userContext,
        AuthSession $authSession,
        MarketplaceHelper $marketplaceHelper,
        SellerSubAccountHelper $sellerSubAccountHelper,
        RequestInterface $request,
        UserFactory $userFactory,
        CustomerFactory $customerModel,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        ResourceConnection $resourceConnection,
        \Magento\Framework\App\State $state,
        VersionManager $versionManager,
        MarketplaceProductManagement $marketplaceProductManagement,
        \Branch8\MarketplaceProduct\Helper\Config $mkConfigHelper,
        \Branch8\MarketplaceProduct\Helper\Images $helperImages
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
        $this->userContext = $userContext;
        $this->authSession = $authSession;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->sellerSubAccountHelper = $sellerSubAccountHelper;
        $this->request = $request;
        $this->userFactory = $userFactory;
        $this->customerModel = $customerModel;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->resourceConnection = $resourceConnection;
        $this->state = $state;
        $this->versionManager = $versionManager;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->mkConfigHelper = $mkConfigHelper;
        $this->helperImages = $helperImages;
    }

    /**
     * @param $sku
     * @return array|string|string[]|null
     */
    private function cleanQuote($sku)
    {
        return preg_replace('/["\']/', '', $sku);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer){
        /**
         * @var $product \Magento\Catalog\Model\Product
         */
        $product = $observer->getEvent()->getProduct();

        /** Validate Commission Percent */
        $commissionPercent = $product->getData('commission_percent');
        if(!$this->mkConfigHelper->isAllowNegativeGrossProfit() && (int)$commissionPercent < 0){
            throw new \Magento\Framework\Exception\LocalizedException(__('Gross profit cannot be negative right now.'));
        }

        $productStatus = $product->getStatus();
        if($productStatus == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED){
            $product->setData('is_hidden', \Branch8\Catalog\Model\Source\HiddenType::NOT_HIDDEN);
        } elseif (!$product->getData('is_hidden')) {
            $product->setData('is_hidden', \Branch8\Catalog\Model\Source\HiddenType::NOT_HIDDEN);
        }

        if ($product->getTypeId() == 'virtual') {
            $shippingMethod[] = 'electronic';
            $product->setData('shipping_method', $shippingMethod);
        }
        $sku = $product->getData('sku');
        if ($sku){
            if (str_contains($sku, 'HOTAI')) {
                $check = explode('-', $sku);
                if (strlen($check[0]) < 6) {
                    $sku = 'HOTAI' . time() . '-' . $sku;
                }
            } else {
                $sku = 'HOTAI' . time() . '-' . $sku;
            }
            $product->setData('sku', $sku);
        }
        $sellerProductId = null;
        if ($product->isObjectNew()) {
            $product->setSku($this->cleanQuote($sku));
            if($this->state->getAreaCode() === \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE){/** Admin login */
                $postData = $this->request->getParams();
                if (isset($postData['product']['assign_seller']['seller_id']) && !empty($postData['product']['assign_seller']['seller_id'])) {
                    $sellerProductId = $postData['product']['assign_seller']['seller_id'];
                }
            }else if($this->marketplaceHelper->isSeller()){/** Is seller login */
                $sellerProductId = $this->marketplaceHelper->getCustomerId();
            }else if($this->sellerSubAccountHelper->isSubAccount()){/** Sub account */
                $currentLoggedinId = $this->sellerSubAccountHelper->getCustomerId();
                $subAccount = $this->sellerSubAccountHelper->_subAccountRepository->getByCustomerId($currentLoggedinId);
                $sellerProductId = $subAccount->getSellerId();
            }else if(in_array($this->state->getAreaCode(), [
                \Magento\Framework\App\Area::AREA_WEBAPI_REST,
                \Magento\Framework\App\Area::AREA_WEBAPI_SOAP,
                \Magento\Framework\App\Area::AREA_GRAPHQL
            ])){/** API */
                $sellerProductId = null;
            }
        }else{
            $productId = $product->getId();
            $sellerProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
            if ($sellerProduct && $sellerProduct->getData('seller_id')) {
                $sellerProductId = $sellerProduct->getData('seller_id');
            }
        }
        /** Process product image base64 */
        $productData = $product->getData();
        $productData = $this->helperImages->processPostImagesInTextArea($productData, $sellerProductId);
        $product->setData($productData);

        if($product->getData('salable_qty') === null && $product->getCustomAttribute('salable_qty')) {
            $product->setData('salable_qty', $product->getCustomAttribute('salable_qty')->getValue());
        }

        if($product->getData('salable_qty') !== null){
            $currentSalableQty = $this->getSalableQty($product->getSku());
            if ((float)$product->getData('salable_qty') != (float)$currentSalableQty) {
                if((int)$product->getData('salable_qty') != 0 || $currentSalableQty > 0){
                    $qty = (int)$product->getData('salable_qty') - $this->getReservedQuantityBySku($product->getSku());
                    if($qty < 0){
                        $qty = 0;
                    }
                    $stockData = $product->getData('stock_data');
                    if(isset($stockData['qty']) || is_null($stockData)){
                        $stockData['qty'] = (int)$qty;
                        $stockData['is_in_stock'] = (int)$qty > 0 ? 1 : 0;
                        $product->setData('stock_data', $stockData);
                    }
                } else {
                    // Fix issue stock status is OSS after approve save draf product on staging HTGO2-2568
                    if($product->getData('quantity_and_stock_status') !== null){
                        $quantity_and_stock_status = $product->getData('quantity_and_stock_status');
                        $qty = 0;
                        if(isset($quantity_and_stock_status['qty'])){
                            $qty = (int)$quantity_and_stock_status['qty'];
                            $stockData = $product->getData('stock_data');
                            if(isset($stockData['qty']) || is_null($stockData)){
                                $stockData['qty'] = $qty;
                                $stockData['is_in_stock'] = $qty > 0 ? 1 : 0;
                                $product->setData('stock_data', $stockData);
                            }
                        }
                    }
                }
            }
        }

        // Set search tag
        $this->setSearchTag($product);
        $controller = $this->request->getRouteName();
        if ($controller != 'marketplacectrl' ) {
            $user = $this->getUpdatedByUser();
            $key = key($user);
            $lastUpdatedUser = $product->getData('admin_user_updated');
            $sellerName = '';
            if ($key === 'admin') {
                $sellerName = $this->userFactory->create()->load($user[$key])->getUserName();
            } elseif ($key === 'seller') {
                $customer = $this->customerModel->create()->load($user[$key]);
                $sellerName = $customer->getData('prefix') . " " . $customer->getFirstname() . ' ' . $customer->getLastname();
            }
            if ($sellerName && $sellerName != $lastUpdatedUser) {
                // Set the custom attribute value
                try {
                    $product->setData('admin_user_updated', $sellerName);
                } catch (\Exception $e) {
                    $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                }
            } elseif (!$sellerName && $lastUpdatedUser) {
                // Ensure we don't lose the old user if current session is empty
                $product->setData('admin_user_updated', $lastUpdatedUser);
            }
        }
        /**
         * If the name changed or meta title, meta description empty
         * Set meta title, meta description  = name
         */
        $orginName = $product->getOrigData('name');
        if($product->getName() != $orginName){
            if($product->getData('meta_title') == ''){
                $product->setData('meta_title', $product->getName());
            }
            if($product->getData('meta_description') == ''){
                $product->setData('meta_description', $product->getName());
            }
            $orgMetaTitle = $product->getOrigData('meta_title');
            if($product->getData('meta_title') == $orgMetaTitle){
                $product->setData('meta_title', $product->getName());
            }

            $orgMetaDescription = $product->getOrigData('meta_description');
            if($product->getData('meta_description') == $orgMetaDescription){
                $product->setData('meta_description', $product->getName());
            }
        }

        /**
         * Remove tag, close tag from product name
         */
        $productName = $product->getData('name');
        $productName = strip_tags($productName);
        $product->setData('name', $productName);

        /**
         * Fix data upper and lower of point
         */
        $point_money_config_type = $product->getData('point_money_config_type');
        if($point_money_config_type == \Branch8\PointMoneyConfig\Helper\Common::TYPE_RATIO_LIMIT){
            $upper_limit = (int)$product->getData('point_money_config_free_ratio_upper_redeem_limit_value');
            $lower_limit = (int)$product->getData('point_money_config_free_ratio_lower_redeem_limit_value');
            if($upper_limit > 0){
                $product->setData('point_money_config_free_ratio_lower_redeem_limit_value', 0);
            }
            if($lower_limit > 0){
                $product->setData('point_money_config_free_ratio_upper_redeem_limit_value', 0);
            }
        }
        $this->storeFirstEnabledDate($product);
        $product->setStoreId(Store::DEFAULT_STORE_ID);

    }

    protected function storeFirstEnabledDate($product)
    {
        if ($product->getStatus() == Status::STATUS_ENABLED && !$product->getOrigData('first_enabled_date')) {

            try {
                $sellerProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $product->getId());
                if ($sellerProduct && !$sellerProduct->getData('is_approved')) {
                    return;
                }
            } catch (NoSuchEntityException $e) {
                $this->logger->critical($e);
            }

            if (!$this->versionManager->isPreviewVersion()) {
                $product->setData('first_enabled_date', date('Y-m-d H:i:s'));
            }
        }

    }


    /**
     * Set search tag
     *
     * @param ProductInterface $product
     * @return bool
     * @throws NoSuchEntityException
     */
    protected function setSearchTag($product)
    {
        $mainCategoryId = $product->getData('main_category');
        $removedMainCategory = null;
        $searchTags = [];
        if ((int)$mainCategoryId == 0) {
            return false;
        }
        $mainCategory = $this->categoryRepository->get($mainCategoryId) ?? null;
        if (!$mainCategory){
            return false;
        }
        if ($product->getData('removed_main_category_id')) {
            $removedMainCategory = $this->categoryRepository->get($product->getData('removed_main_category_id')) ?? null;
        }

        if (!$product->getData('search_tag')) {
            $searchTags[] = $mainCategory->getName();
        } else {
            $searchTags = explode(',', (string)$product->getData('search_tag'));
            if (!str_contains($product->getData('search_tag'), $mainCategory->getName())) {
                if ($removedMainCategory && $removedMainCategory->getId()) {
                    $searchTags[] = str_replace($removedMainCategory->getName(), $mainCategory->getName(), $product->getData('search_tag'));
                } else {
                    array_push($searchTags, $product->getData('search_tag'), $mainCategory->getName());
                }
            }
        }
        if ($searchTags) {
            $finalSearchTags = implode(',', array_unique(array_filter($searchTags)));
            $product->setData('search_tag', join(',', array_unique(explode(',', trim($finalSearchTags)))));
        }
        return true;
    }

    /**
     * Get the ID of the user who updated the data.
     *
     * @return array
     */
    private function getUpdatedByUser(): array
    {
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
            $userId = $this->userContext->getUserId();
            if ($userId) {
                return ['admin' => (int)$userId];
            }
        }
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $userId = $this->userContext->getUserId();
            if ($userId) {
                return ['seller' => (int)$userId];
            }
        }

        $user = $this->authSession->getUser();
        if (!empty($user)) {
            return ['admin' => (int)$user->getId()];
        }

        $isPartner = $this->marketplaceHelper->isSeller();
        if ($isPartner == 1) {
            $sellerId = $this->sellerSubAccountHelper->getCustomerId();
            if (!$sellerId) {
                $sellerId = $this->marketplaceHelper->getCustomerId();
            }
            return ['seller' => (int)$sellerId];
        }

        return ['system' => null];
    }

    private function getReservedQuantityBySku(string $sku): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('inventory_reservation');

        $select = $connection->select()
            ->from(['ir' => $tableName], [])
            ->columns(['reserved_qty' => new \Zend_Db_Expr('SUM(ir.quantity)')]) // Sum the quantity
            ->where('ir.sku = ?', $sku)
            ->group('ir.sku');

        $reservedQty = $connection->fetchOne($select);

        return $reservedQty !== false ? (int)$reservedQty : 0;
    }

    private function getSalableQty($sku): int
    {
        if ($sku) {
            $salableQty = $this->getSalableQuantityDataBySku->execute($sku);
            if(isset($salableQty[0]['qty'])){
                return (int)$salableQty[0]['qty'];
            }
        }

        return 0;
    }

}
