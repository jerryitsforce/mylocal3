<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceSellerPage\Controller\Seller;
use Webkul\Marketplace\Helper\Data as MpHelper;

/**
 * Marketplace Seller Profile controller.
 */
class Profile extends \Webkul\Marketplace\Controller\Seller\Profile
{
    /**
     * Marketplace Seller's Profile Page.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $helper = $this->helper;
        $seller = $this->helper->getProfileDetail(MpHelper::URL_TYPE_LOCATION);
        $shopName = $seller && $seller->getShopTitle() ? $seller->getShopTitle() : __('Hontai Shop');
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
                $this->_view->getPage()->getConfig()->getTitle()->set(__($shopName));
                return $resultPage;
            }
        }
        return $this->resultRedirectFactory->create()->setPath(
            'marketplace',
            ['_secure' => $this->getRequest()->isSecure()]
        );
    }
}
