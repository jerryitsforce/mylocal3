<?php

namespace Branch8\Customer\Observer;

use Branch8\Customer\Helper\Data;
use Magento\Customer\Model\Url as CustomerUrl;
use Webkul\SellerSubAccount\Helper\Data as HelperData;
use Magento\Customer\Model\Session as CustomerSession;

class PreventSeller implements \Magento\Framework\Event\ObserverInterface{
    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;

    public $preventSellerFullActionData = [
        'sales/parentOrder/history',
        'downloadable/customer/products',
        'customer/account/index',
        'wishlist',
        'wishlist/index',
        'wishlist/index/index',
        'hotai_point/detail',
        'hotai_point/detail/index',
        'customer/address/new',
        'customer/address/edit',
        'customer/address/index',
        'customer/account/edit',
        'customer/account/editPost',
        'vault/cards/listaction',
        'giftcard/customer/index',
        'reward/customer/info',
        'giftregistry/index/index',
        'review/customer/index',
        'newsletter/manage/index',
        'family_bonus_pin/detail/index',
        'edenred/detail/index',
        'yoxi/detail/index',
        'rma/returns/history',
        'hotaipay/creditcard/listaction',
        'helpdesk/ticket/index',
        'affiliate/account/setting',
        'notification/index/index',
        'faq/index/index',
        'hotai_auth/groupapps/index'
    ];

    public $preventRewriteURLData = [
        'affiliate/account/setting'
    ];

    public $skipActions = [
        'customer/section/load',
        'customer/address/new',
        'customer/address/edit',
        'customer/address/formPost',
        'customer/address/form'
    ];

    /**
     * @var CustomerUrl
     */
    protected $customerUrl;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @param Data $b8CustomerHelper
     * @param CustomerUrl $customerUrl
     * @param HelperData $subAccountHelper
     * @param CustomerSession $customerSession
     */
    public function __construct(
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        CustomerUrl $customerUrl,
        HelperData $subAccountHelper,
        CustomerSession $customerSession
    ){
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->customerUrl = $customerUrl;
        $this->subAccountHelper = $subAccountHelper;
        $this->customerSession = $customerSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        // only My Account area
        $request = $observer->getEvent()->getRequest();
        $fullActionName = $request->getModuleName().'/'.$request->getControllerName().'/'.$request->getActionName();
        if ((str_starts_with($fullActionName, 'customer/') || str_starts_with($fullActionName, 'checkout/cart'))
            && !in_array($fullActionName, $this->skipActions)) {
            $this->customerSession->setStoreData(null);
            $this->customerSession->setCheckoutAddress(null);
            $this->customerSession->setStoreCheckoutData(null);
        }
        if(
            !$this->b8CustomerHelper->isSeller()
            && !$this->b8CustomerHelper->isWaitForSeller()
            && !$this->subAccountHelper->isSubAccount()
        ) {
            return;
        }
        $uriRequest = $_SERVER['REQUEST_URI'];
        $uriRequest = substr($uriRequest, 1);
        $explUri = explode('?', $uriRequest);
        $uriRequest = $explUri[0];
        $explUri = explode('#', $uriRequest);
        $uriRequest = $explUri[0];
        if(substr($uriRequest, -1) == '/') {
            $uriRequest = substr($uriRequest, 0, -1);
        }
        if(in_array($fullActionName, $this->preventSellerFullActionData) || in_array($uriRequest, $this->preventRewriteURLData)){
            $loginUrl = $this->customerUrl->getLoginUrl();
            return $observer->getControllerAction()
                ->getResponse()
                ->setRedirect($loginUrl);
        }
    }

}
