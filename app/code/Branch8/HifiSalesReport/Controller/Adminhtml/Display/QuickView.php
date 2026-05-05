<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Controller\Adminhtml\Display;

use Magento\Framework\App\Action\HttpGetActionInterface;

class QuickView extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $resultPage->getConfig()->getTitle()->prepend(__('Hifi Quick View'));

        return $resultPage;
    }
}
