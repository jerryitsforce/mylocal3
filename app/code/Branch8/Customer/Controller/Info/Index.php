<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Controller\Info;

use Branch8\HotaiAuth\Service\HotaiAuthLoginService;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;

class Index extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Setting constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CustomerSession $customerSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $queryParams = [];
        foreach (HotaiAuthLoginService::ALLOWED_ADDITIONAL_PARAMS as $param) {
            $value = $this->getRequest()->getParam($param);
            if ($value) {
                $queryParams[$param] = $value;
            }
        }

        if (!$this->customerSession->isLoggedIn()) {
            $currentUrl = $this->_url->getCurrentUrl();
            $encodedReferer = base64_encode($currentUrl);
            return $this->_redirect('customer/account/login/referer/' . $encodedReferer);
        }

        $customer = $this->customerSession->getCustomer();
        if(!empty($customer->getData('nickname'))){
            return $this->_redirect('member/hotaipay?' . http_build_query($queryParams));
        }
        return $this->resultPageFactory->create();
    }
}

