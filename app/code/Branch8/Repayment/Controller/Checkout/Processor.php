<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Branch8\Repayment\Controller\Checkout;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\Registry;
use \Magento\Checkout\Model\Session;
use \Magento\Framework\Controller\ResultInterface;
use Branch8\Repayment\Helper\Data as RepaymentHelper;
use Branch8\HotaiPay\Model\CreditCard\Session as CreditCardSession;

class Processor extends AbstractAccount
{
    protected $orderLoader;
    private $registry;
    protected $checkoutSession;
    protected $repaymentHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    
     /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    
    /**
     * @var CreditCardSession
     */
    protected $creditCardSession;

    /**
     * Initialize repayment checkout processor controller.
     *
     * @param Context $context Action context.
     * @param Registry $registry Magento registry.
     * @param PageFactory $resultPageFactory Result page factory.
     * @param Session $checkoutSession Checkout session.
     * @param RepaymentHelper $repaymentHelper Repayment helper.
     * @param \Magento\Customer\Model\Session $customerSession Customer session.
     * @param CreditCardSession $creditCardSession Credit card session.
     */
    public function __construct(
        Context $context,
        Registry $registry,
        PageFactory $resultPageFactory,
        Session $checkoutSession,
        RepaymentHelper $repaymentHelper,
        \Magento\Customer\Model\Session $customerSession,
        CreditCardSession $creditCardSession
    ) {
        $this->_customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->creditCardSession = $creditCardSession;
        parent::__construct($context, $customerSession, $resultPageFactory);
        $this->registry = $registry;
        $this->checkoutSession = $checkoutSession;
        $this->repaymentHelper = $repaymentHelper;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {

        $orderId = $this->getRequest()->getParam('order');
        $this->registry->register('parent_order_id', $orderId);
        $this->registry->register('params', $this->getRequest()->getParams());
        $this->checkParams();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Repay'));
        return $resultPage;
    }

    /**
     * Check callback parameters and clear card list when success.
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
