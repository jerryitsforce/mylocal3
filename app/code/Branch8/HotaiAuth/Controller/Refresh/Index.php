<?php
namespace Branch8\HotaiAuth\Controller\Refresh;

use AllowDynamicProperties;
use Branch8\HotaiAuth\Helper\HotaiLogin;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Exception;

#[AllowDynamicProperties] class Index extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        HotaiLogin $hotaiLoginHelper,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        HotaiAuthService $hotaiAuthService,
        \Magento\Framework\App\RequestInterface $request,
    ) {
        parent::__construct($context);
        $this->hotaiLoginHelper = $hotaiLoginHelper;
        $this->_context = $context;
        $this->customerSession = $customerSession;
        $this->hotaiAuthService = $hotaiAuthService;
        $this->request = $request;
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomerId();
            $userProfile = $this->hotaiAuthService->getUserProfile();
            $customerData = $this->hotaiLoginHelper->updateCustomerById($customerId, $userProfile);
            $this->hotaiLoginHelper->login($customerData);
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $homeUrl = $this->_url->getUrl('');
        $resultRedirect->setUrl($homeUrl);

        return $resultRedirect;
    }
}
