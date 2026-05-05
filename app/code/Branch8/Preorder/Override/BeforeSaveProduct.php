<?php

namespace Branch8\Preorder\Override;

class BeforeSaveProduct extends \Webkul\MarketplacePreorder\Observer\BeforeSaveProduct{

    /**
     * Add more condition to validate available pre-order date
     * @param \Magento\Framework\Event\Observer $observer
     * @return array|void
     * @throws \Magento\Framework\Validator\Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer){
        $helper = $this->_preorderHelper;
        $productId = -1;
        $preorderProductId = $helper->getPreorderCompleteProductId();
        /**
         * get data for Product Update Approval Required: NO
         */
        if($observer->getEvent()->getName() == 'mp_product_save_before'){
            $eventData = $observer->getEvent()->getData();
            $data['product'] = $eventData[0];
        }else {
            $data = $this->_request->getParams();
        }
        if(!isset($data['product'])){
            return;
        }
        if ($helper->getMarketPlaceHelper()->isSellerGroupModuleInstalled()
            ||
            $helper->getConfigPathValue('mpsellergroup/general_settings/status')
        ) {
            return $data;
        }
        $sellerId = 0;
        if (array_key_exists('id', $data)) {
            $productId = $data['id'];
        }
        if (!array_key_exists('is_admin', $data)) { // in case update product
            if ($productId == $preorderProductId) {
                $error = "You can not update 'Complete PreOrder' Product";
                throw new \Magento\Framework\Validator\Exception(__($error));
            }
        }
        $today = date('m/d/y');
        $attributeOptions = $this->_preorderHelper->getPreorderAttribute('simple');
        $enabledId = -1;
        foreach ($attributeOptions as $attributeOption) {
            if ($attributeOption['label']=='Enable') {
                $enabledId = $attributeOption['value'];
            }
        }
        if (is_array($data) && is_array($data['product']) &&
            array_key_exists("wk_marketplace_preorder", $data['product'])
            && array_key_exists("wk_marketplace_availability", $data['product'])
            && $data['product']['wk_marketplace_preorder'] == $enabledId
            && $data['product']['preorder_mode'] == 1 /*Only Mode start-end date have to use available date*/
        ) {
            $today = date('m/d/y');
            if (strtotime($data['product']['wk_marketplace_availability']) < strtotime($today)) {
                throw new \Magento\Framework\Validator\Exception(__('Preorder Availability date should be of future'));
            }
        }

        if (is_array($data) && is_array($data['product']) &&
            array_key_exists("assign_seller", $data['product'])) {
            if (isset($data['product']['seller_id']) && !empty($data['product']['seller_id'])) {
                $sellerId = $data['product']['seller_id'];
            }
        }
    }
}