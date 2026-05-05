<?php
declare(strict_types=1);

namespace Branch8\SplitCart\Helper;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use \Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use \Magento\Shipping\Model\Config\Source\Allmethods;
use Webkul\MpApi\Api\SellerManagementInterface;

/**
 * Class Data
 * @package Branch8\SplitCart\Helper
 */
class Data extends AbstractHelper
{
    const TYPE_PREORDER = 'preorder';
    const TYPE_PREORDER_NORMAL = 'preorder_normal';

    const TYPE_PREORDER_FROZEN = 'preorder_frozen';

    const TYPE_PREORDER_REFRIGERATED = 'preorder_refrigerated';

    const TYPE_PREORDER_VIRTUAL = 'preorder_virtual';


    const TYPE_VIRTUAL = 'virtual';

    const TYPE_NORMAL = 'normal';

    const TYPE_REFRIGERATED = 'refrigerated';

    const TYPE_FROZEN = 'frozen';
    /**
     * @var HelperData
     */
    protected $helper;

    protected $_options;


    /**
    * @var Allmethods
    */
    protected $shippingAllMethods;
    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $preorderHelper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    protected $cartSplited = [];

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var SellerManagementInterface
     */
    private SellerManagementInterface $sellerManagement;
    /**
     * @var \Branch8\Sales\Model\Product\Attribute\Source\PreservationStatus
     */
    protected $preservationStatusSource;

    protected $marketplaceHelperData;

    protected $flagshipSalesHelper;

    protected $sellerIds = [];

    /**
     * @param Context $context
     * @param HelperData|null $helper
     * @param Allmethods $shippingAllMethods
     * @param \Webkul\MarketplacePreorder\Helper\Data $preorderHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param SellerManagementInterface $sellerManagement
     * @param \Branch8\Sales\Model\Product\Attribute\Source $preservationStatusSource
     */
    public function __construct(
        Context $context,
        HelperData $helper = null,
        Allmethods $shippingAllMethods,
        \Webkul\MarketplacePreorder\Helper\Data $preorderHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        StoreManagerInterface $storeManager,
        SellerManagementInterface $sellerManagement,
        \Branch8\Sales\Model\Product\Attribute\Source\PreservationStatus $preservationStatusSource,
        \Webkul\Marketplace\Helper\Data $marketplaceHelperData,
        \Branch8\FlagshipStore\Helper\Sales $flagshipSalesHelper
    ) {
        parent::__construct($context);
        $this->helper = $helper ?: \Magento\Framework\App\ObjectManager::getInstance()->create(HelperData::class);
        $this->shippingAllMethods = $shippingAllMethods;
        $this->preorderHelper = $preorderHelper;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->sellerManagement = $sellerManagement;
        $this->preservationStatusSource = $preservationStatusSource;
        $this->marketplaceHelperData = $marketplaceHelperData;
        $this->flagshipSalesHelper = $flagshipSalesHelper;
    }

    /**
     * Return the seller data by product id.
     *
     * @param int $productId
     * @return null|array
     */
    public function getSellerByProductId($productId)
    {
        if($productId){
            $sellerId = $this->helper->getSellerIdByProductId($productId);
            if($sellerId){
                $seller = $this->helper->getSellerCollectionObj($sellerId);
                return $seller->getData()[0];
            }
        }
        return null;
    }

    /**
     * Return the seller data by seller id.
     *
     * @param int $sellerId
     * @return null|array
     */
    public function getSellerBySellerId($sellerId)
    {
        if ($sellerId) {
            try {
                $storeId = $this->storeManager->getStore()->getStoreId();
            } catch (\Exception $e) {
                $storeId = Store::DEFAULT_STORE_ID;
            }
            $sellers = $this->sellerManagement->getSeller($sellerId, $storeId);
            if ($sellers->getTotalCount() > 0) {
                return $sellers->getItems()[0];
            } else {
                $sellers = $this->sellerManagement->getSeller($sellerId, Store::DEFAULT_STORE_ID);
                if ($sellers->getTotalCount() > 0) {
                    return $sellers->getItems()[0];
                }
            }
        }
        return null;
    }

    /**
     * Return the full seller shop url
     *
     * @param string $shopUrl
     * @return string
     */
    public function getSellerShopUrl($shopUrl)
    {
        if($shopUrl){
            return $this->helper->getRewriteUrl('marketplace/seller/profile/shop/'.$shopUrl);
        }
        return '';
    }

    /**
     * get all shipping methods
     *
     * @return array
     */
    public function getAllOptions()
    {
        return $this->shippingAllMethods->toOptionArray(true);
    }

    /**
     * get all values of shipping methods
     *
     * @return array
     */
    public function getAllShippingMethods()
    {
        $options = $this->getAllOptions();
        $allShippingMethods = [];
        foreach($options as $subOptions) {
            if($subOptions['value']){
               foreach($subOptions['value'] as $option){
                    if($option['value'] !== 'instore_pickup') {
                        $allShippingMethods[]=$option['value'];
                    }
               }
            }
        }
        return $allShippingMethods;
    }

     /**
     * Get Shipping Method Label by method value
     *
     * @param string $methodVal
     * @return string
     */
    public function getOptionLabelByValue($methodVal)
    {
        $options = $this->getAllOptions();
        foreach($options as $subOptions) {
            if($subOptions['value']){
                $checkOtion = false;
               foreach($subOptions['value'] as $option){
                   if($option['value'] == $methodVal){
                    $checkOtion = true;
                   }
               }
               if($checkOtion) {
                return $subOptions['label'];
               }
            }
        }
        return '';
    }

    public function getProductCartType($product){
        $productType = $product->getTypeId();
        if ($productType == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            //FE show Parent first, so get by parent
            $splitType = $this->getSimpleCartType($product);
        } else if ($productType == BundleType::TYPE_CODE) {
            $isBundlePreorder = $this->isPreorderBundleProduct($product);
            $preservationStatus = $product->getPreservationStatus();
            if ($isBundlePreorder) {
                $reservationStatus = (string)$product->getPreservationStatus();
                if ($reservationStatus != '') {
                    $splitType = self::TYPE_PREORDER . '_' . $reservationStatus;
                } else if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL) {
                    $splitType = self::TYPE_PREORDER_VIRTUAL;
                } else {
                    $splitType = self::TYPE_PREORDER_NORMAL;
                }
            } else if ((string)$preservationStatus !== '') {
                $splitType = $preservationStatus;
            } else {
                $splitType = self::TYPE_NORMAL;
            }
        } else {
            $splitType = $this->getSimpleCartType($product);
        }
        return $splitType;
    }

    public function isPreorderBundleProduct($product)
    {
        $productId = $product->getId();
        $sellerId = $this->preorderHelper->getSellerIdByProductId($productId);
        $preorderAction = $this->preorderHelper->getSellerPreorderAction($sellerId);
        $status = $this->preorderHelper->getPreorderProductSeller($preorderAction, $sellerId, $productId);
        if ($status == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $allItems
     * @param \Magento\Quote\Model\Quote\Item $currentItem
     * @return void
     */
    public function getSplitType($allItems, $currentItem){
        $splitType = self::TYPE_NORMAL;
        $product = $currentItem->getProduct();
        $productType = $product->getTypeId();
        if($productType == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
            $child = $currentItem->getChildren();
            //every configurable has only one child
            $splitType = $this->getSimpleSplitType($child[0]);
        }else if($productType == BundleType::TYPE_CODE){
            $isBundlePreorder = $this->isPreorderBundleSplitCartOrder($currentItem);
            $preservationStatus = $currentItem->getProduct()->getPreservationStatus();
            if($isBundlePreorder){
                $reservationStatus = (string)$product->getPreservationStatus();
                if($reservationStatus != ''){
                    $splitType = self::TYPE_PREORDER.'_'.$reservationStatus;
                }else if($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL){
                    $splitType = self::TYPE_PREORDER_VIRTUAL;
                }else {
                    $splitType = self::TYPE_PREORDER_NORMAL;
                }
            }else if((string)$preservationStatus !== ''){
                $splitType = $preservationStatus;
            }else{
                $splitType = self::TYPE_NORMAL;
            }
        }else{
            $splitType = $this->getSimpleSplitType($currentItem);
        }
        return $splitType;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $items
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string
     */
    public function getConfigurableType($items, $item){
        foreach($items as $_item){
            if(!$_item->getParentItem()){
                continue;
            }
            if($_item->getParentItemId() == $item->getId()){
                $type = $this->getSimpleSplitType($_item);
                break;
            }
        }
        return $type;
    }
    public function isPreorderBundleSplitCartOrder($item){
        $productId = $item->getProductId();
        $sellerId = $this->preorderHelper->getSellerIdByProductId($productId);
        $preorderAction = $this->preorderHelper->getSellerPreorderAction($sellerId);
        $status = $this->preorderHelper->getPreorderProductSeller($preorderAction, $sellerId, $productId);
        if ($status == 1) {
            return true;
        } else {
            return false;
        }
    }

     /**
     * @param $item
     * @return string
     */
    public function getSimpleSplitType($item)
    {
        $product = $item->getProduct();
        return $this->getSimpleCartType($product);
    }

    public function getSimpleCartType($product, $IsVirtual = false){
        if( $this->preorderHelper->isPreorder($product->getId())){
            $reservationStatus = (string)$product->getPreservationStatus();
            if($reservationStatus != ''){
                return self::TYPE_PREORDER.'_'.$reservationStatus;
            }else if($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL){
                return self::TYPE_PREORDER_VIRTUAL;
            }else {
                return self::TYPE_PREORDER_NORMAL;
            }
        }else if((string)$product->getPreservationStatus() !== ''){
            return $product->getPreservationStatus();
        }else if($product->getIsVirtual() || $IsVirtual){
            return self::TYPE_VIRTUAL;
        }else{
            return self::TYPE_NORMAL;
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $allItems
     * @param string $type
     * @param int $sellerId
     * @return array
     */
    public function getItemsCartType($type, $sellerId){
        $cartItems = [];
        $allItems = $this->checkoutSession->getQuote()->getAllItems();
        $items = $allItems;
        foreach($items as $item){
            $productId = $item->getProductId();
            $productSellerId = $this->marketplaceHelperData->getSellerIdByProductId($productId);
            if($productSellerId != $sellerId){
                continue;
            }
            $itemType = $this->getSplitType($allItems, $item);
            if($itemType == $type){
                $cartItems[] = $item;
            }
        }
        return $cartItems;
    }

    /**
     * The $items is all cart items
     * @param $items
     * @return array
     */
    public function splitCartToSubCart($items){
//        if(!empty($this->cartSplited)){
//            return $this->cartSplited;
//        }
//        $itemsFollowSellers = [];
//        foreach($items as $item){
//            $allItems[$item->getId()] = $item;
//        }
//        foreach($items as $_item){
//            $type = $this->getSplitType($allItems, $_item);
//            $proId = $_item->getProduct()->getId();
//            $sellerId = (int) $this->helper->getSellerIdByProductId($proId);
//            $itemsFollowSellers[$sellerId][$type][] = $_item;
//        }
//        $this->cartSplited = $itemsFollowSellers;
//        return $this->cartSplited;


//        if(!empty($this->cartSplited)){
//            return $this->cartSplited;
//        }

        $allItemWithKeys = [];
        foreach($items as $item) {
            $allItemWithKeys[$item->getId()] = $item;

        }
        $allItems = $allItemWithKeys;
        $itemsFollowSellers = [];
        foreach($items as $_item){
            $type = $this->getSplitType($allItems, $_item);
            $proId = $_item->getProductId();
            $sid = $this->flagshipSalesHelper->getSellerIdFromProductId($proId);
            $flagShipStoreId = null;
            if(!isset($this->sellerIds[$sid])){
                $flagShipStoreId = $this->flagshipSalesHelper->getFlagshipStoreFromSellerId($sid);
                $this->sellerIds[$sid] = $flagShipStoreId;
            }else{
                $flagShipStoreId = $this->sellerIds[$sid];
            }

            if($flagShipStoreId){
                $sellerId = \Branch8\FlagshipStore\Helper\Sales::CART_FLAGSHIP_STORE_PREFIX.$flagShipStoreId;
            }else{
                $sellerId = (int) $sid;
            }
            $itemsFollowSellers[$sellerId][$type][] = $_item;
        }
        $this->cartSplited = $itemsFollowSellers;
        return $this->cartSplited;

    }

    public function getPreservationMapping($source)
    {
        
        $source = is_string($source) ? str_replace('preorder_', '', $source) : $source;
        $source = is_string($source) ? str_replace('normal', '', $source) : $source;
        foreach($this->preservationStatusSource->getAllOptions() as $_source){
            if($_source['value'] == $source){
                return $_source['label'];
            }
        }
        return 'Ticket';
    }

    public function isAvailableCheckoutAllCartType($items){
        $availableCnt = 0;
        foreach($items as $_item){
            if($_item->getAvailableToCheckout()){
                $availableCnt ++;
            }
        }
        return $availableCnt == count($items);
    }
}
