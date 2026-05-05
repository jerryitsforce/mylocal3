<?php

namespace Branch8\SellerContactInformation\Helper;
use \Magento\Shipping\Model\Config;
class SellerShipping extends \Magento\Framework\App\Helper\AbstractHelper{

    protected $_storeManager;

    const SELLER_PRODUCT_DOESNOT_MATCH_SHIPPING_METHOD = 'This product has a shipping method not supported by the seller.';

    const CART_VALIDATE_SELLER_PRODUCT_SHIPPING_METHOD_ERROR = 'The product\'s delivery method must be supported by the seller.';

    protected $_deliveryModelConfig;
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Config $deliveryModelConfig
    ){

        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_deliveryModelConfig = $deliveryModelConfig;
    }

    public function getAllCarriers(){
        $store = $this->_storeManager->getStore();
        $deliveryMethods = $this->_deliveryModelConfig->getActiveCarriers($store->getId());
        $methods = array();
        foreach ($deliveryMethods as $method) {
            if($method->getCarrierCode() == 'splitship'){
                continue;
            }
            $methods[] = [
                'label' => $method->getConfigData('title'),
                'code' => $method->getCarrierCode(),
                'price' => $method->getConfigData('price'),
                'price_threshold' => $method->getConfigData('price_threshold'),
                'refrigerated_price' => $method->getConfigData('refrigerated_price'),
                'refrigerated_price_threshold' => $method->getConfigData('refrigerated_price_threshold'),
                'frozen_price' => $method->getConfigData('frozen_price'),
                'frozen_price_threshold' => $method->getConfigData('frozen_price_threshold')
            ];
        }

        return $methods;
    }

}