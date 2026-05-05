<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Adminhtml\ProductVersion;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory as ResultPageFactory;

class Index extends Action
{
    /**
     * @var ResultPageFactory
     */
    private ResultPageFactory $resultPageFactory;

    /**
     * Index constructor,
     *
     * @param Context $context
     * @param ResultPageFactory $resultPageFactory
     */
    public function __construct(Context $context, ResultPageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Product Version Management'));
        return $resultPage;
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::marketplace_product_version');
    }
}

