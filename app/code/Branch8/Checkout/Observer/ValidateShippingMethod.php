<?php

namespace Branch8\Checkout\Observer;

use Magento\Downloadable\Model\Product\Type;
use Magento\Framework\Exception\LocalizedException;
use Branch8\HotaiShipping\Helper\Data as HotaiShippingHelper;
class ValidateShippingMethod implements \Magento\Framework\Event\ObserverInterface{
    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $marketPlaceDataHelper;
    /**
     * @var HotaiShippingHelper
     */
    protected $hotaiShippingHelper;

    /**
     * @param \Webkul\Marketplace\Helper\Data $mpDataHelper
     * @param HotaiShippingHelper $hotaiShippingHelper
     */
    public function __construct(
        \Webkul\Marketplace\Helper\Data $mpDataHelper,
        HotaiShippingHelper $hotaiShippingHelper
    ){
        $this->marketPlaceDataHelper = $mpDataHelper;
        $this->hotaiShippingHelper = $hotaiShippingHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer){
        $product = $observer->getEvent()->getProduct();
        if(
            $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL ||
            $product->getTypeId() == Type::TYPE_DOWNLOADABLE ||
            (
                $product->getTypeId() == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD &&
                $product->getGiftcardType() == \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL
            )
        ){
            return;
        }
        $sellerId = (int)$this->marketPlaceDataHelper->getSellerIdByProductId($product->getId());
        $seller = $this->marketPlaceDataHelper->getSellerCollectionObj($sellerId)->getFirstItem();
        $sellerShippingMethods = explode(',', (string)$seller->getShippingMethods());

        $methodMapping = $this->hotaiShippingHelper->mappingMethod();
        $productShippingMethods = explode(',', (string)$product->getShippingMethod());
        $productMappingMethod = [];
        foreach ($productShippingMethods as $method) {
            if(isset($methodMapping[$method])){
                $productMappingMethod[] = $methodMapping[$method];
            }
        }
        if(empty(array_intersect($sellerShippingMethods, $productMappingMethod))) {
            throw new LocalizedException(__(\Branch8\SellerContactInformation\Helper\SellerShipping::SELLER_PRODUCT_DOESNOT_MATCH_SHIPPING_METHOD));
        }
    }

}