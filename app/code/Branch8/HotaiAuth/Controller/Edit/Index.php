<?php
namespace Branch8\HotaiAuth\Controller\Edit;
use AllowDynamicProperties;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Controller\Account\Edit;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

#[AllowDynamicProperties] class Index extends Edit
{
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        CustomerRepositoryInterface $customerRepository,
        DataObjectHelper $dataObjectHelper,
        HotaiAuthService $hotaiAuthService
    )
    {
        $this->hotaiAuthService = $hotaiAuthService;
        parent::__construct($context, $customerSession, $resultPageFactory, $customerRepository, $dataObjectHelper);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \JsonException
     */
    public function execute()
    {
        $hotaiUrl = $this->hotaiAuthService->getModifierUrl();

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
