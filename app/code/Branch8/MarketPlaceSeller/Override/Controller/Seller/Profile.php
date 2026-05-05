<?php

namespace Branch8\MarketPlaceSeller\Override\Controller\Seller;

class Profile extends \Webkul\Marketplace\Controller\Seller\Profile
{
    public function execute()
    {
        $helper = $this->helper;
        if (!$helper->getSellerProfileDisplayFlag()) {
            $this->getRequest()->initForward();
            $this->getRequest()->setActionName('noroute');
            $this->getRequest()->setDispatched(false);

            return false;
        }
        $shopUrl = $helper->getProfileUrl();
        if (!$shopUrl) {
            $shopUrl = $this->getRequest()->getParam('shop');
        }

        if ($shopUrl) {
            $data = $helper->getSellerDataByShopUrl($shopUrl);
            if ($data->getSize()) {
                $resultPage = $this->_resultPageFactory->create();
                return $resultPage;
            }else{
                $sellerrQuery = $helper->getSellerCollection();
                $sellerrQuery->addFieldToFilter('shop_url', $shopUrl);
                $sellerrQuery->addFieldToFilter('store_id', $helper->getCurrentStoreId());
                // If seller data doesn't exist for current store
                if (!$sellerrQuery->getSize()) {
                    $sellerrQuery = $helper->getSellerCollection();
                    $sellerrQuery->addFieldToFilter('shop_url', $shopUrl);
                    $sellerrQuery->addFieldToFilter('store_id', 0);
                }
                $sellerrQuery = $helper->joinCustomer($sellerrQuery);
                $seller = $sellerrQuery->getFirstItem();
                if($seller && $seller->getIsSeller() != \Webkul\Marketplace\Model\Seller::STATUS_ENABLED){
                    $this->getRequest()->initForward();
                    $this->getRequest()->setActionName('noroute');
                    $this->getRequest()->setDispatched(false);

                    return false;
                }
            }
        }

        return $this->resultRedirectFactory->create()->setPath(
            'marketplace',
            ['_secure' => $this->getRequest()->isSecure()]
        );
    }
}