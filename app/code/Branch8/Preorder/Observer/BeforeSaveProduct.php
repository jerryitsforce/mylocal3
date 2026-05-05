<?php

namespace Branch8\Preorder\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class BeforeSaveProduct implements ObserverInterface{
    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $preorderHelperData;
    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;
    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $_preorderHelper;

    /**
     * @var RequestInterface
     */
    protected $_request;

    protected $customerSession;

    protected $conn;

    /**
     * @param \Webkul\MarketplacePreorder\Helper\Data $preorderHelperData
     * @param RequestInterface $request
     * @param TimezoneInterface $timezoneInterface
     * @param \Webkul\MarketplacePreorder\Helper\Data $preorderHelper
     */
    public function __construct(
        \Webkul\MarketplacePreorder\Helper\Data $preorderHelperData,
        RequestInterface $request,
        TimezoneInterface $timezoneInterface,
        \Webkul\MarketplacePreorder\Helper\Data $preorderHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        $this->preorderHelperData = $preorderHelperData;
        $this->_request = $request;
        $this->timezoneInterface = $timezoneInterface;
        $this->_preorderHelper = $preorderHelper;
        $this->customerSession = $customerSession;
        $this->conn = $resourceConnection->getConnection();
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Magento\Framework\Validator\Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer){

        $product = $observer->getProduct();
        if(!$product){
            return;
        }
        // if($product->getId()){
        //     /** Get Seller ID */
        //     $sellerId = $this->getSellerIdFromProductId($product->getId());
        // }else{
        //     $extenstionAttr = $product->getExtensionAttributes();
        //     $sellerId = $extenstionAttr->getSellerId();
        // }
        // if(!$sellerId){
        //     throw new \Magento\Framework\Validator\Exception(__('Missing Seller ID.'));
        // }

        // $preorderAction = $this->preorderHelperData->getSellerPreorderAction($sellerId);

        $wk_marketplace_preorder = $product->getData('wk_marketplace_preorder');
        /** 
         * Now only action=1(Per product)
         * 
         * */
        $isPreorderMatch = 0;
        $preorderAction = 1;
        if($preorderAction == 1){
            $attributeOptions = $this->_preorderHelper->getPreorderAttribute('simple');
            $enabledId = -1;
            foreach ($attributeOptions as $attributeOption) {
                if ($attributeOption['label']=='Enable') {
                    $enabledId = $attributeOption['value'];
                }
            }
            /*this way is of v4.0.0, it very bad, in 4.0.2 is better, WE SHOULD UPDATE THIS WAY BECAUSE V4.0.2 USE OTHER WAY FOR CASE PER PRODUCT*/
            if ($wk_marketplace_preorder == $enabledId) {
                $isPreorderMatch = 1;
            }
        }
        if($isPreorderMatch){
            $preorder_mode = $product->getData('preorder_mode');
            $preorder_start_date = $product->getData('preorder_start_date');
            $preorder_end_date = $product->getData('preorder_end_date');
            $preorder_ship_date = $product->getData('preorder_ship_date');
            $wk_marketplace_availability = $product->getData('wk_marketplace_availability');
            $preorder_x_days = $product->getData('preorder_x_days');
            $preorder_use_qty = $product->getData('preorder_use_qty');
            $wk_mppreorder_qty = $product->getData('wk_mppreorder_qty');

            if($preorder_mode == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE){
                //validate start/ end date
                if(trim($preorder_start_date) == ''){
                    throw new \Magento\Framework\Validator\Exception(__('Pre-order Start date is required.'));
                }
                $startDate = $this->convertDate($preorder_start_date);

                if(trim($preorder_end_date) == ''){
                    throw new \Magento\Framework\Validator\Exception(__('Pre-order End date is required.'));
                }

                $endDate = $this->convertDate($preorder_end_date);
                if(strtotime($startDate) >= strtotime($endDate)){
                    throw new \Magento\Framework\Validator\Exception(__('Pre-order Start date must be less than End date.'));
                }
                //validate available/ end date
                $availableDate = $this->convertDate($wk_marketplace_availability);
                if(trim($wk_marketplace_availability) == ''){
                    throw new \Magento\Framework\Validator\Exception(__('Pre-order Available date is required.'));
                }
                
                if(strtotime($endDate) > strtotime($availableDate)){
                    throw new \Magento\Framework\Validator\Exception(__('Pre-order End date must be less than Available date.'));
                }
                
                if((int)$preorder_use_qty == 1 && $wk_mppreorder_qty === null){
                    throw new \Magento\Framework\Validator\Exception(__('Please input valid Maximum qty for Pre-order.'));
                }
            }else if($preorder_mode == \Branch8\Preorder\Model\Source\PreorderMode::X_DAYS){
                if(trim($preorder_end_date) == ''){
                    throw new \Magento\Framework\Validator\Exception(__('Pre-order End date is required.'));
                }
                $endDate = $this->convertDate($preorder_end_date);
                if($endDate == ''){
                    throw new \Magento\Framework\Validator\Exception(__('Please set End date for PreOrder X-Days mode.'));
                }
                $todayDate = $this->timezoneInterface->date()->format('Y-m-d 00:00:00');
                if(strtotime($todayDate) > strtotime($endDate)){
                    throw new \Magento\Framework\Validator\Exception(__('Pre-order End date must be in the future.'));
                }
                if((int)$preorder_x_days == 0){
                    throw new \Magento\Framework\Validator\Exception(__('Pre-order XDays field must be great than 0 for XDays mode.'));
                }
            }else if($preorder_mode == \Branch8\Preorder\Model\Source\PreorderMode::SPECIFY_SHIPPING_DATE){
                $endDate = $this->convertDate($preorder_end_date);
                if(trim($preorder_end_date) == ''){
                    throw new \Magento\Framework\Validator\Exception(__('Please set End date for PreOrder Specify Shipping Date mode.'));
                }
                $todayDate = $this->timezoneInterface->date()->format('Y-m-d 00:00:00');
                if(strtotime($todayDate) > strtotime($endDate)){
                    throw new \Magento\Framework\Validator\Exception(__('Pre-order End date must be in the future.'));
                }
                $shipDate = $this->convertDate($preorder_ship_date);
                if(trim($preorder_ship_date) == ''){
                    throw new \Magento\Framework\Validator\Exception(__('Please set Ship Date for PreOrder Specify Shipping Date mode.'));
                }
                if(strtotime($todayDate) > strtotime($shipDate)){
                    throw new \Magento\Framework\Validator\Exception(__('Pre-order Ship date must be in the future.'));
                }
            }
        }


    }

    /**
     * @param string $date
     * @return string
     */
    private function convertDate($date){
        try {
            $time = strtotime($date);
            return date('Y-m-d', $time) . ' 00:00:00';
        }catch(\Exception $e){
            return '';
        }
    }

    public function getSellerIdFromProductId($pid)
    {
        $query = $this->conn->select()
            ->from(['mp_product' => 'marketplace_product'], ['seller_id'])
            ->where('mageproduct_id=?', $pid)
            ->limit(1);
        return $this->conn->fetchOne($query);
    }
}
