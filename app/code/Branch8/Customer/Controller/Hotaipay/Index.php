<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Controller\Hotaipay;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Branch8\HotaiPay\Model\CreditCard\Session as CreditCardSession;
use \Magento\Framework\Registry;

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
     * @var Registry
     */
    protected $registry;

    /**
     * @var CreditCardSession
     */
    protected $creditCardSession;

    /**
     * Setting constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        CreditCardSession $creditCardSession,
        CustomerSession $customerSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        $this->creditCardSession = $creditCardSession;
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
        $this->registry->register('params', $this->getRequest()->getParams());
        $this->checkParams();

        return $this->resultPageFactory->create();
    }

    /**
     * checkParams
     *
     * @return void
     */
    private function checkParams()
    {
        $params = $this->getRequest()->getParams();
        if (isset($params['StatusDesc'])) {
            if (strtoupper($params['StatusDesc']) == 'SUCCESS') {
                $this->creditCardSession->unsCreditCardList();
            }
        }
    }
}

