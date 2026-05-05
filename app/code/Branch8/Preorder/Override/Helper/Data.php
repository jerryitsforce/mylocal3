<?php

namespace Branch8\Preorder\Override\Helper;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\Session as customerSession;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Filesystem\Driver\File;
use Webkul\MarketplacePreorder\Api\PreorderCompleteRepositoryInterface;
use Webkul\MarketplacePreorder\Api\PreorderItemsRepositoryInterface;
use Webkul\MarketplacePreorder\Api\PreorderSellerRepositoryInterface;
use Webkul\MarketplacePreorder\Model\ResourceModel\PreorderItems\CollectionFactory as PreorderItemsCollection;
use Webkul\MarketplacePreorder\Model\ResourceModel\PreorderSeller\CollectionFactory as PreorderSellerCollection;

class Data extends \Webkul\MarketplacePreorder\Helper\Data{
    /**
     * @var
     */
    private $jsonHelper;
    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;
    /**
     * @var \Branch8\Preorder\Helper\Data
     */
    protected $customHelperdata;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\ProductFactory $product
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param customerSession $customerSession
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
     * @param \Magento\Catalog\Model\Product\OptionFactory $option
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $localeResolver
     * @param \Magento\Sales\Model\OrderFactory $order
     * @param \Webkul\Marketplace\Model\ProductFactory $marketplaceProduct
     * @param \Webkul\Marketplace\Helper\Data $marketplaceHelper
     * @param \Webkul\MarketplacePreorder\Model\Source\PreorderType $preorderType
     * @param \Webkul\MarketplacePreorder\Model\Source\PreorderAction $preorderAction
     * @param \Webkul\MarketplacePreorder\Model\Source\PreorderEamil $preorderEmail
     * @param \Webkul\MarketplacePreorder\Model\Source\PreorderQty $preorderQty
     * @param \Webkul\MarketplacePreorder\Model\Source\PreorderSpecification $preorderSpecification
     * @param Configurable $configurable
     * @param PreorderItemsCollection $preorderItemsCollectionFactory
     * @param PreorderSellerCollection $preorderSellerCollectionFactory
     * @param PreorderSellerRepositoryInterface $sellerRepository
     * @param PreorderItemsRepositoryInterface $itemsRepository
     * @param PreorderCompleteRepositoryInterface $completeRepository
     * @param \Magento\Checkout\Model\CartFactory $cart
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZoneInterface
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeData
     * @param \Magento\Catalog\Model\Product $productModel
     * @param \Magento\Catalog\Model\Product\Action $productAction
     * @param Attribute $eavEntity
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param File $file
     * @param \Branch8\Preorder\Helper\Data $customHelperdata
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\ProductFactory $product,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        customerSession $customerSession,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Catalog\Model\Product\OptionFactory $option,
        \Magento\Framework\Stdlib\DateTime\Timezone $localeResolver,
        \Magento\Sales\Model\OrderFactory $order,
        \Webkul\Marketplace\Model\ProductFactory $marketplaceProduct,
        \Webkul\Marketplace\Helper\Data $marketplaceHelper,
        \Webkul\MarketplacePreorder\Model\Source\PreorderType $preorderType,
        \Webkul\MarketplacePreorder\Model\Source\PreorderAction $preorderAction,
        \Webkul\MarketplacePreorder\Model\Source\PreorderEamil $preorderEmail,
        \Webkul\MarketplacePreorder\Model\Source\PreorderQty $preorderQty,
        \Webkul\MarketplacePreorder\Model\Source\PreorderSpecification $preorderSpecification,
        Configurable $configurable,
        PreorderItemsCollection $preorderItemsCollectionFactory,
        PreorderSellerCollection $preorderSellerCollectionFactory,
        PreorderSellerRepositoryInterface $sellerRepository,
        PreorderItemsRepositoryInterface $itemsRepository,
        PreorderCompleteRepositoryInterface $completeRepository,
        \Magento\Checkout\Model\CartFactory $cart,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZoneInterface,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeData,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Catalog\Model\Product\Action $productAction,
        Attribute $eavEntity,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        File $file,
        \Branch8\Preorder\Helper\Data $customHelperdata
    ){
        parent::__construct($context, $product, $storeManager, $customerSession, $currency,
            $localeCurrency, $filesystem, $resource, $productCollection, $option, $localeResolver,
            $order, $marketplaceProduct, $marketplaceHelper, $preorderType, $preorderAction,
            $preorderEmail, $preorderQty, $preorderSpecification, $configurable,  $preorderItemsCollectionFactory,
            $preorderSellerCollectionFactory, $sellerRepository, $itemsRepository, $completeRepository,
            $cart, $searchCriteriaBuilder, $timeZoneInterface, $attributeData, $productModel,
        $productAction, $eavEntity, $jsonHelper, $productRepository, $stockRegistry, $file);
        $this->jsonHelper = $this->jsonHelper;
        $this->moduleManager = $context->getModuleManager();
        $this->customHelperdata = $customHelperdata;
    }

    /**
     * Check cart item qty && pre-order maximum qty,
     * only check for The start/end date modes
     * @param $item
     * @param $product
     * @return bool
     */
    public function getQtyCheck($item, $product){

        $productType = $product->getTypeId();
        if ($productType == 'configurable') {
            $configModel = $this->_configurable;
            $usedProductIds = $configModel->getUsedProductIds($product);
            foreach ($usedProductIds as $usedProductId) {
                if ($this->isPreorder($usedProductId)) {
                    $product = $this->usedProductIdPreorder($usedProductId);
                    if($product->getPreorderMode() == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE
                    && (int)$product->getPreorderUseQty() == 1){
                        $preorderQty = (int)$product->getWkMppreorderQty();
                        if ($preorderQty < $item->getQty()) {
                            return false;
                        }
                    }
                }
            }
        } else {
            $product = $this->_productFactory->create()->load($product->getId());
            $preorderQty = (int)$product->getWkMppreorderQty();
            if ($product->getPreorderMode() == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE
                && (int)$product->getPreorderUseQty() == 1
                && $preorderQty < $item->getQty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Override ext, add logic to check preorder for 3 modes(start/end date, xdays, shipdate)
     * @param $productId
     * @return bool
     */
    public function isPreorder($productId = '')
    {
        if ($productId == '' || $productId == 0) {
            return false;
        }

        $isProduct = false;
        $collection = $this->_productCollectionFactory->create();
        $collection->addFieldToFilter('entity_id', $productId);
        $collection->addAttributeToSelect('*');
        if ($collection->getSize()) {
            $product = $this->_productFactory->create()->load($productId);
            $isProduct = true;
        }
        if (!$isProduct) {
            return false;
        }
        $productType = $product->getTypeId();
        if (in_array($productType, ['bundle', 'grouped', 'configurable'])) {
            return false;
        }
        $stockStatus = $this->getProductStock($product);
        $sellerId = $this->getSellerIdByProductId($productId);
        if ($stockStatus != 1) {
            $preorderAction = $this->getSellerPreorderAction($sellerId);
            $status = $this->getPreorderProductSeller($preorderAction, $sellerId, $productId);
            /*
             * Custom validate for 3 modes
             */
            if ($status == 1) {
                $todayDate = $this->timeZoneInterface->date()->format('Y-m-d 00:00:00');
                $todayTime = strtotime($todayDate);
                $preorderMode = $product->getData('preorder_mode');
                if($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE) {
                    $preorderStartDate = $product->getData('preorder_start_date');
                    $preorderEndDate = $product->getData('preorder_end_date');

                    if(
                        ($todayTime >= strtotime($preorderStartDate) && $todayTime <= strtotime($preorderEndDate))
                        && ($product->getData('preorder_use_qty') && $product->getData('wk_mppreorder_qty') > 0)
                    ){
                        return true;
                    }
                    
                    return false;
                }else if($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::X_DAYS){
                    $preorderXDays = $product->getData('preorder_x_days');
                    $preorderEndDate = $product->getData('preorder_end_date');
                    if($todayTime <= strtotime($preorderEndDate)){
                        return true;
                    }
                    return false;
                }else if($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::SPECIFY_SHIPPING_DATE){
                    $preorderEndDate = $product->getData('preorder_end_date');
                    if($todayTime <= strtotime($preorderEndDate)){
                        return true;
                    }
                    return false;
                }
            }
            return false;
        }
        return false;
    }

    /**
     * Override Core function
     * @param $productId
     * @return string|\Webkul\MarketplacePreorder\Helper\html
     */
    public function getPreOrderInfoBlock($productId){
        return $this->customHelperdata->getPdpMessage($productId);
    }


    /**
     * Check Configurable Product is Preorder or Not.
     *
     * @param int $productId
     *
     * @return bool
     */
    public function isConfigPreorder($productId)
    {
        $isProduct = false;
        $collection = $this->_productCollectionFactory->create();
        $collection->addFieldToFilter('entity_id', $productId);
        $collection->addAttributeToSelect('*');
        if ($collection->getSize()) {
            $product = $this->_productFactory->create()->load($productId);
            $isProduct = true;
        }
        if ($isProduct) {
            $productType = $product->getTypeId();
            if ($productType == 'configurable') {
                $configModel = $this->_configurable;
                $usedProductIds = $configModel->getUsedProductIdsConfig($product);
                foreach ($usedProductIds as $usedProductId) {
                    if ($this->isPreorder($usedProductId)) {
                        return true;
                    }
                }
            }else if($productType == 'bundle'){
                $selectionCollection = $product->getTypeInstance(true)
                    ->getSelectionsCollection(
                        $product->getTypeInstance(true)->getOptionsIds($product),
                        $product
                    );
                foreach($selectionCollection as $_product){
                    $isPreorder = $this->isPreorder($_product->getId());
                    if($isPreorder){
                        return true;
                    }
                }
            }else if($productType == 'grouped'){
                $groupChilds = $product->getTypeInstance()->getAssociatedProducts($product);
                foreach($groupChilds as $_product){
                    $isPreorder = $this->isPreorder($_product->getId());
                    if($isPreorder){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Override core ext, core ext does not allow bundle, grouped on pre-order
     * @param $productId
     * @return bool
     */
    public function isChildProduct($productId = '')
    {
//        if ($productId == '') {
//            $productId = $this->_request->getParam('id');
//        }
//        $productModel = $this->_productFactory->create();
//        $product = $productModel->load($productId);
//        $productType = $product->getTypeID();
//        $productTypeArray = ['bundle', 'grouped'];
//        if (in_array($productType, $productTypeArray)) {
//            return true;
//        }

        return false;
    }

    /**
     * Always enable the qty field in add/ edit, using preorder_use_qty attribute to control the mppreorder_qty for every product,
     * instead of for all products of seller
     * @return bool
     */
    public function getPreorderQtyEnable()
    {
        $configuration = $this->getSellerConfiguration();
        if (!empty($configuration)) {
            if ($configuration['mppreorder_qty']==1) {
                return true;
            }
        }else{
            return true;
        }

        return false;
    }

    /**
     * getSellerPreorderSpecification used to get buyer preorder configuration,
     * We modify the core function, we set default to all user can buy the preorder,
     * if the config preorder_specific is not set, the default valus is all users.
     * It(preorder_specific) can be 0 if seller set, null or 1 are the same(all user)
     * @param  int $sellerId
     * @return boolean
     */
    public function getSellerPreorderSpecification($sellerId)
    {
        if ((int) $sellerId!==0 && $sellerId!==null && $sellerId!=="") {
            $configuration = $this->getSellerConfiguration($sellerId);
            if (!empty($configuration)) {
                if ($configuration['preorder_specific'] === 0) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            $specification = $this->getConfigData('mppreorder_specific');
            if ((int) $specification === 0) {
                return true;
            } else {
                return false;
            }
        }
    }

}