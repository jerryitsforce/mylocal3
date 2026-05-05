<?php

declare(strict_types=1);

namespace HotaiConnected\CheckoutManagement\Controller\Adminhtml\CheckoutArea;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Create extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('HotaiConnected_FinancialReconciliation::checkout_area_insert');
        $resultPage->getConfig()->getTitle()->prepend('建立月結帳批次');

        return $resultPage;
    }

    /**
     * Check if user has permissions to access this controller
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::finance_automation') 
            && $this->_authorization->isAllowed('HotaiConnected_FinancialReconciliation::checkout_area_insert');
    }
} 