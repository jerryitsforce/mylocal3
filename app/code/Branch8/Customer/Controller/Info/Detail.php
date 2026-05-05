<?php

declare(strict_types=1);

namespace Branch8\Customer\Controller\Info;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page as ResultPage;

class Detail extends Action
{
    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * Detail constructor.
     *
     * @param Context $context
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context         $context,
        CustomerSession $customerSession
    ) {
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if (!$this->customerSession->getCustomerId()) {
            $currentUrl = $this->_url->getCurrentUrl();
            $encodedReferer = base64_encode($currentUrl);
            return $this->_redirect('customer/account/login/referer/' . $encodedReferer);
        }

        /** @var ResultPage $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Member Information'));

        return $resultPage;
    }
}
