<?php

namespace Branch8\MarketPlaceSeller\Override\Controller\Account;

use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Profiler;
use Magento\Framework\View\Result\PageFactory;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;

class Becomeseller extends \Webkul\Marketplace\Controller\Account\Becomeseller{

    protected $b8CustomerHelper;
    public function __construct(
        Context $context, PageFactory $resultPageFactory, \Magento\Customer\Model\Session $customerSession,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        MarketplaceHelper $marketplaceHelper = null, CustomerUrl $customerUrl = null
    ){
        parent::__construct($context, $resultPageFactory, $customerSession, $marketplaceHelper, $customerUrl);
        $this->b8CustomerHelper = $b8CustomerHelper;
    }

    public function dispatch(RequestInterface $request)
    {
        if($this->b8CustomerHelper->isSeller() && $this->b8CustomerHelper->getIsSellerCode() == \Webkul\Marketplace\Model\Seller::STATUS_ENABLED){// status = 1
            return $this->resultRedirectFactory->create()->setPath(
                '*/*/dashboard',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }else if ($this->b8CustomerHelper->isWaitForSeller()){
            //core action
            $this->_request = $request;
            $profilerKey = 'CONTROLLER_ACTION:' . $request->getFullActionName();
            Profiler::start($profilerKey);

            $result = null;
            if ($request->isDispatched() && !$this->_actionFlag->get('', self::FLAG_NO_DISPATCH)) {
                Profiler::start('action_body');
                $result = $this->execute();
                Profiler::stop('action_body');
            }
            Profiler::stop($profilerKey);
            return $result ?: $this->_response;

        }else{
            //reset session && redirect to login
            $this->_customerSession->logout();
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/login',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }

    }

}