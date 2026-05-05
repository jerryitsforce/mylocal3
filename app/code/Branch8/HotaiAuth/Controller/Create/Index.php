<?php
namespace Branch8\HotaiAuth\Controller\Create;
use AllowDynamicProperties;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Magento\Customer\Controller\Account\Create;
use Magento\Customer\Model\Registration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

#[AllowDynamicProperties] class Index extends Create
{
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        Registration $registration,
        HotaiAuthService $hotaiAuthService
    )
    {
        $this->hotaiAuthService = $hotaiAuthService;
        parent::__construct($context, $customerSession, $resultPageFactory, $registration);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \JsonException
     */
    public function execute()
    {
        $hotaiUrl = $this->hotaiAuthService->getLoginUrl();

        $resultRedirect = $this->resultRedirectFactory->create();
        if (empty($hotaiUrl)) {
            $homeUrl = $this->_url->getUrl('');
            $resultRedirect->setUrl($homeUrl);
        } else {
            $resultRedirect->setUrl($hotaiUrl);
        }

        return $resultRedirect;
    }
}
