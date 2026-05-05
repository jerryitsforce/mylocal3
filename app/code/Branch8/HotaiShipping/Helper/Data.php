<?php

namespace Branch8\HotaiShipping\Helper;

use Magento\Downloadable\Model\Product\Type;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Shipping\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Branch8\SplitCart\Helper\Data as SplitCartHelper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper{

    private $state;

    const SHIPPING_CART_TYPE_NORMAL = 'normal';

    const SHIPPING_CART_TYPE_REFRIGERATED = 'refrigerated';

    const SHIPPING_CART_TYPE_FROZEN = 'frozen';

    const SHIPPING_CART_TYPE_VIRTUAL = 'virtual';

    /**
     * @var \Magento\Shipping\Model\Shipping
     */
    protected $shipping;
    /**
     * @var RateRequestFactory
     */
    protected $rateRequestFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;
    /**
     * @var TimezoneInterface
     */
    protected $timezone;
    /**
     * @var Config
     */
    protected $_deliveryModelConfig;

    protected $splitCartHelper;

    /**
     * @param Context $context
     * @param \Magento\Shipping\Model\Shipping $shipping
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param RateRequestFactory $rateRequestFactory
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param TimezoneInterface $timezone
     * @param Config $deliveryModelConfig
     */
    public function __construct(
        Context $context,
        \Magento\Shipping\Model\Shipping $shipping,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        TimezoneInterface $timezone,
        Config $deliveryModelConfig,
        splitCartHelper $splitCartHelper
    ){
        $this->shipping = $shipping;
        $this->rateRequestFactory = $rateRequestFactory;
        $this->_storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
        $this->priceHelper = $priceHelper;
        $this->timezone = $timezone;
        $this->_deliveryModelConfig = $deliveryModelConfig;
        $this->splitCartHelper = $splitCartHelper;

    }

    /**
     * Mapping all cart type to shipping cart type to calculate shipping fee
     * Preorder is the same not preorder if they are the same preservation status
     *
     * @param $type
     * @return string
     */
    public function mappingCartTypeForShipping($type){
        $realType = self::SHIPPING_CART_TYPE_NORMAL;
        switch ($type) {
            case SplitCartHelper::TYPE_NORMAL:
                $realType = self::SHIPPING_CART_TYPE_NORMAL;
                break;
            case SplitCartHelper::TYPE_VIRTUAL:
                $realType = self::SHIPPING_CART_TYPE_VIRTUAL;
                break;
            case SplitCartHelper::TYPE_REFRIGERATED:
                $realType = self::SHIPPING_CART_TYPE_REFRIGERATED;
                break;
            case SplitCartHelper::TYPE_FROZEN:
                $realType = self::SHIPPING_CART_TYPE_FROZEN;
                break;
            case SplitCartHelper::TYPE_PREORDER_REFRIGERATED:
                $realType = self::SHIPPING_CART_TYPE_REFRIGERATED;
                break;
            case SplitCartHelper::TYPE_PREORDER_FROZEN:
                $realType = self::SHIPPING_CART_TYPE_FROZEN;
                break;
            case SplitCartHelper::TYPE_PREORDER_VIRTUAL:
                $realType = self::SHIPPING_CART_TYPE_VIRTUAL;
                break;
            case SplitCartHelper::TYPE_PREORDER_NORMAL:
                $realType = self::SHIPPING_CART_TYPE_NORMAL;
                break;
            default:
                $realType = self::SHIPPING_CART_TYPE_NORMAL;
                break;

        }
        return $realType;
    }

    public function getProductShippingEstData($product, $productCartType){
        $arrCarrierCodes = $this->mappingMethod();
        $productCartTypeMapping = $this->mappingCartTypeForShipping($productCartType);
        $carriesShippingFee = [];
        if($productCartTypeMapping == self::SHIPPING_CART_TYPE_VIRTUAL){
            foreach($arrCarrierCodes as $carrierCode){
                $shippingFee = 0;
                $carriesShippingFee[$carrierCode] = [
                    'sort_order' => $this->getCarrierSortOrder($carrierCode),
                    'title' => $this->getCarrierTitle($carrierCode),
                    'threshold' => 0,
                    'shipping_fee' => $shippingFee,
                    'shipping_fee_formated' => $this->priceHelper->currency($shippingFee, true, false)
                ];
            }
            return $carriesShippingFee;
        }
        $productShippingMethod = $product->getShippingMethod();
        $carrierCodes = array_keys($arrCarrierCodes);
        $allShippingMethods = explode(',', $productShippingMethod);
        $carrierCodes = array_intersect($carrierCodes, $allShippingMethods);
        if(empty($carrierCodes)){
            return [];
        }

        $isLargeItem = $product->getLargeItem();
        $configKey = $this->getShippingPriceByType($productCartTypeMapping);
        foreach($carrierCodes as $carrierCode) {
            $carrierMethodCode = $arrCarrierCodes[$carrierCode];
            $threshold = $this->getThreshold($carrierMethodCode, $configKey);
            $shippingFee = $this->getShippingPrice($carrierMethodCode, $configKey);
            if($isLargeItem){
                $shippingFee = $shippingFee * 2;
            }
            $shippingFeeFormated = $this->priceHelper->currency($shippingFee, true, false);
            $threshold = $this->priceHelper->currency($threshold, true, false);
            $carriesShippingFee[$carrierMethodCode]= [
                'sort_order' => $this->getCarrierSortOrder($carrierMethodCode),
                'title' => $this->getCarrierTitle($carrierMethodCode),
                'threshold' => $threshold,
                'shipping_fee' => $shippingFee,
                'shipping_fee_formated' => $shippingFeeFormated
            ];

            if($carrierCode == 'hotai_delivery_hotai_delivery') {
                $carriesShippingFee[$carrierMethodCode]['title'] = $this->geTemperatureLayerLabel($productCartType) . $carriesShippingFee[$carrierMethodCode]['title'];
            }
        }

        //sort $carriesShippingFee by sort_order key in ascending order
        usort($carriesShippingFee, function($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        return $carriesShippingFee;
    }

    public function geTemperatureLayerLabel($productCartType)
    {
        switch ($productCartType) {
            case SplitCartHelper::TYPE_NORMAL:
                return __('Normal Temperature');
            case SplitCartHelper::TYPE_REFRIGERATED:
                return __('Refrigerated');
            case SplitCartHelper::TYPE_FROZEN:
                return __('Frozen');
            default:
                return '';

        }
    }

    /**
     * @param $subCartType
     * @param $arrCarrierCodes
     * @param $allSubCartItems
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSubCartFreeShippingData($subCartType, $arrCarrierCodes, $allSubCartItems){
        $items = [];
        $isAdmin = $this->getState()->getAreaCode() === Area::AREA_ADMINHTML;
        foreach ($allSubCartItems as $item){
            if(!$isAdmin && (!$item->getAvailableToCheckout() || $item->getParentItemId())){
                continue;
            }
            $items[] = $item;
        }
        $cartType = $this->mappingCartTypeForShipping($subCartType);
        if($cartType == self::SHIPPING_CART_TYPE_VIRTUAL){
            $carriesShippingFee = [];
            foreach($arrCarrierCodes as $carrierCode){
                $shippingFee = 0;
                $carriesShippingFee[$carrierCode]= [
                    'title' => $this->getCarrierTitle($carrierCode),
                    'threshold' => 0,
                    'shipping_fee' => $shippingFee,
                    'origin_shipping_fee' => $shippingFee,
                    'shipping_fee_with_large_item' => $shippingFee,
                    'shipping_fee_formated' => $this->priceHelper->currency($shippingFee, true, false)
                ];
            }
            return [
                'sub_cart_total' => 0,
                'carries_shipping_fee' => $carriesShippingFee,
                'fresshipping_messages' => [],
                'carrier_count' => count($arrCarrierCodes),
                'match_num' => count($arrCarrierCodes)
            ];
        }

        /**
         * If seller support many carriers but all sub cart items only has one method, we have to get the intersection method
         */
        $allShippingMethods = $this->getIntersectionShippingMethods($items);
        foreach($arrCarrierCodes as $index => $_carrierCode){
            if(!in_array($_carrierCode, $allShippingMethods)) {
                unset($arrCarrierCodes[$index]);
            }
        }

        /**
         * Remove inactive method
         */
        foreach($arrCarrierCodes as $key => $carrierCode) {
            if (!$this->isCarrierActive($carrierCode)) {
                unset($arrCarrierCodes[$key]);
            }
        }
        if(empty($arrCarrierCodes)){
            return [];
        }

        $subCartTotal = 0;
        $hasLargeItem = false;
        //1. Get subtotal for order
        foreach($items as $_item){
            $rowInclTax = $_item->getRowTotalInclTax();
            $subCartTotal += $rowInclTax;

            /**
             * Check large item, if product are configurable, bundle, using child product to check
             * other product, use this product directly
             */
            if($hasLargeItem){
                /**
                 * If has one of large item, do not check large item again, pls put this code at the end of forearch
                 */
                continue;
            }
            if(in_array($_item->getProductType(), ['bundle'])){
                $childItems = $_item->getChildren();
                foreach($childItems as $childItem){
                    $childProduct = $childItem->getProduct();
                    if($childProduct && $childProduct->getLargeItem()){
                        $hasLargeItem = true;
                    }
                }
            }else{
                $product = $_item->getProduct();
                if($product && $product->getLargeItem()){
                    $hasLargeItem = true;
                }
            }

        }


        //2. Get threshold for every Home delivery and Convenience store pickup && Calculate shipping fee
        $carriesShippingFee = [];
        $configKey = $this->getShippingPriceByType($cartType);
        foreach($arrCarrierCodes as $carrierCode){
            if(!$this->isCarrierActive($carrierCode)){
                continue;
            }
            $threshold = $this->getThreshold($carrierCode, $configKey);
            $shippingFeeOrigin = $this->getShippingPrice($carrierCode, $configKey);
            $shippingFee = $shippingFeeOrigin;
            $shippingFeeWithLargeItem = $shippingFee;
            if($hasLargeItem){
                $shippingFee = $shippingFee * 2;
                $shippingFeeWithLargeItem = $shippingFee;
            }
            $shippingFeeFormated = $this->priceHelper->currency($shippingFee, true, false);
            if(count($items) == 0){
                $shippingFee = '0';
                $shippingFeeFormated = 'N/A';
            }else if($subCartTotal >= $threshold){
                $shippingFee = 0;
                $shippingFeeFormated = $this->priceHelper->currency($shippingFee, true, false);
            }

            $carriesShippingFee[$carrierCode] = [
                'title' => $this->getCarrierTitle($carrierCode),
                'threshold' => $threshold,
                'shipping_fee' => $shippingFee,
                'origin_shipping_fee' => $shippingFeeOrigin,
                'shipping_fee_with_large_item' => $shippingFeeWithLargeItem,
                'shipping_fee_formated' => $shippingFeeFormated,
            ];
        }

        //3. Get message
        $matchNum = 0;
        $carrierCount = count($carriesShippingFee);
        $freeShippingMessages = [];
        foreach($carriesShippingFee as $carrierCode => $carrierFee) {
            if ($subCartTotal >= $carrierFee['threshold']) {
                $matchNum++;
                $freeShippingMessages['reached'] = __('已達%1免運門檻', $carrierFee['title']);
            } else {
                $freeShippingMessages['not-reached'] = __('未達%1免運門檻', $carrierFee['title']);
            }
        }
        if($matchNum == $carrierCount){
            $freeShippingMessages['reached'] = __('已達免運門檻');
        } else if($matchNum == 0){
            $freeShippingMessages['not-reached'] = __('未達免運門檻');
        }
        //5. Return data
        return [
            'sub_cart_total' => $subCartTotal,
            'carries_shipping_fee' => $carriesShippingFee,
            'fresshipping_messages' => $freeShippingMessages,
            'carrier_count' => $carrierCount,
            'match_num' => $matchNum
        ];
    }

    /**
     * @param $cartType
     * @return string
     */
    protected function getShippingPriceByType($cartType){
        $configKey = 'price';

        switch($cartType){
            case self::SHIPPING_CART_TYPE_NORMAL:
                $configKey = 'price';
                break;
            case self::SHIPPING_CART_TYPE_REFRIGERATED:
                $configKey = 'refrigerated_price';
                break;
            case self::SHIPPING_CART_TYPE_FROZEN:
                $configKey = 'frozen_price';
                break;
        }
        return $configKey;
    }

    /**
     * @param $carrierCode
     * @param $configKey
     * @return bool
     */
    public function isSpecialShipping($carrierCode, $configKey){
        $isEnableConfigKey = $configKey.'_special_enable';
        $isEnable = $this->scopeConfig->getValue('carriers/'.$carrierCode.'/'.$isEnableConfigKey, ScopeInterface::SCOPE_WEBSITE);
        if($isEnable){
            $startDateConfigKey = $configKey.'_special_start_date';
            $fromDate = $this->scopeConfig->getValue('carriers/'.$carrierCode.'/'.$startDateConfigKey, ScopeInterface::SCOPE_WEBSITE);
            $endDateConfigKey = $configKey.'_special_end_date';
            $endDate = $this->scopeConfig->getValue('carriers/'.$carrierCode.'/'.$endDateConfigKey, ScopeInterface::SCOPE_WEBSITE);
            $currentDate = $this->timezone->date()->format('Y-m-d H:i:s');
            if(strtotime($currentDate) >= strtotime($fromDate) && strtotime($currentDate) <= strtotime($endDate)){
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * @param $carrierCode
     * @param $configKey
     * @return mixed
     */
    public function getShippingPrice($carrierCode, $configKey){
        $price = $this->scopeConfig->getValue('carriers/'.$carrierCode.'/'.$configKey, ScopeInterface::SCOPE_WEBSITE);
        if($this->isSpecialShipping($carrierCode, $configKey)){
            $configKey = 'special_'.$configKey;
            $price = $this->scopeConfig->getValue('carriers/'.$carrierCode.'/'.$configKey, ScopeInterface::SCOPE_WEBSITE);
        }
        return $price;
    }

    /**
     * @param $carrierCode
     * @param $configKey
     * @return mixed
     */
    public function getThreshold($carrierCode, $configKey){

        if($this->isSpecialShipping($carrierCode, $configKey)){
            $configKey = $configKey.'_threshold';
            $configKey = 'special_'.$configKey;
        }else{
            $configKey = $configKey.'_threshold';
        }

        $threshold = $this->scopeConfig->getValue('carriers/'.$carrierCode.'/'.$configKey, ScopeInterface::SCOPE_WEBSITE);
        return $threshold;
    }

    /**
     * @param $carrierCode
     * @return mixed
     */
    public function getCarrierTitle($carrierCode){
        return $this->scopeConfig->getValue('carriers/'.$carrierCode.'/title', ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @param $carrierCode
     * @return int
     */
    public function getCarrierSortOrder($carrierCode){
        $order = $this->scopeConfig->getValue('carriers/'.$carrierCode.'/sort_order', ScopeInterface::SCOPE_WEBSITE) ?? 0;
        return (int)$order;
    }


    public function getCarrierTitleByCarrierMethoCode($carrierMethodCode){
        $arrCarrierCodes = $this->mappingMethod();
        foreach($arrCarrierCodes as $key => $carrierCode){
            if($key === $carrierMethodCode) {
                return $this->getCarrierTitle($carrierCode);
            }
        }
        return '';
    }

    /**
     * @param $carrierCode
     * @return mixed
     */
    protected function isCarrierActive($carrierCode){
        return $this->scopeConfig->getValue('carriers/'.$carrierCode.'/active', ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Because current shipping method design, every carrier is only has one shipping method
     * @return void
     */
    public function mappingMethod(){
        $store = $this->_storeManager->getStore();
        $deliveryCarriers = $this->_deliveryModelConfig->getActiveCarriers($store->getId());
        $methods = array();
        foreach ($deliveryCarriers as $carrier) {
            $carrierMethods = $carrier->getAllowedMethods();
            foreach($carrierMethods as $methodCode => $carrierName){
                $methods[$carrier->getCarrierCode().'_'.$methodCode] = $carrier->getCarrierCode();
            }

        }
        return $methods;
    }

    public function getShippingMethods(){
        $store = $this->_storeManager->getStore();
        $deliveryCarriers = $this->_deliveryModelConfig->getActiveCarriers($store->getId());
        $methods = array();
        foreach ($deliveryCarriers as $carrier) {
            $carrierMethods = $carrier->getAllowedMethods();
            foreach($carrierMethods as $methodCode => $carrierName){
                $methods[] = $carrier->getCarrierCode().'_'.$methodCode;
            }

        }
        return $methods;
    }

    public function mappingAllMethods(){
        $store = $this->_storeManager->getStore();
        $deliveryCarriers = $this->_deliveryModelConfig->getAllCarriers($store->getId());
        $methods = array();
        foreach ($deliveryCarriers as $carrier) {
            $carrierMethods = $carrier->getAllowedMethods();
            foreach($carrierMethods as $methodCode => $carrierName){
                $methods[$carrier->getCarrierCode().'_'.$methodCode] = $carrier->getCarrierCode();
            }

        }
        return $methods;
    }

    /**
     * @return mixed
     */
    private function getState()
    {
        if ($this->state === null) {
            $this->state = ObjectManager::getInstance()->get(State::class);
        }
        return $this->state;
    }

    public function getIntersectionShippingMethods($items){
        $intersectionShippingMethods = [];
        $isAdmin = $this->getState()->getAreaCode() === Area::AREA_ADMINHTML;
        foreach($items as $_item){
            if(!$_item->getAvailableToCheckout() && !$isAdmin){
                continue;
            }
            if(in_array($_item->getProductType(), [\Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL, Type::TYPE_DOWNLOADABLE])){
                continue;
            }
            $product = $_item->getProduct();
            $shippingMethods = explode(',', (string)$product->getShippingMethod());
            if(empty($intersectionShippingMethods)){
                $intersectionShippingMethods = $shippingMethods;
            }else {
                $intersectionShippingMethods = array_intersect($intersectionShippingMethods, $shippingMethods);
            }
        }


        $mappingShippingMethod = $this->mappingMethod();
        $allShippingMethods = [];
        foreach($intersectionShippingMethods as $shippingMethod) {
            if(isset($mappingShippingMethod[$shippingMethod])) {
                $allShippingMethods[] = $mappingShippingMethod[$shippingMethod];
            }
        }
        return $allShippingMethods;
    }

    public function getProductShippingMethod($product){
        if($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
            return ['Please select options'];
        }
        if($product->getIsVirtual()){
            return [];
        }
        $productCartType = $this->splitCartHelper->getProductCartType($product);
        $shippingMethod = (string)$product->getShippingMethod();
        if(!$shippingMethod){
            return [];
        }
        $estShipping = $this->getProductShippingEstData($product, $productCartType);
        if(empty($estShipping)){
            return [];
        }
        //render
        $renderData = [];
        foreach($estShipping as &$_shipping){
            $renderData[] = $_shipping['title'] . ' '.sprintf(__('at the price  %s, over  %s free shipping'), $_shipping['shipping_fee_formated'],$_shipping['threshold']);
        }
        return $renderData;
    }
}
