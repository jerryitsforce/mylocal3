<?php

namespace Branch8\Sales\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Controller\Adminhtml\Dashboard\AjaxBlock;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class ProductChart extends AjaxBlock
{

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param LayoutFactory $layoutFactory
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context, $resultRawFactory, $layoutFactory);
        $this->_resultPageFactory = $resultPageFactory;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Add Custom Tab'));

        $block = $resultPage->getLayout()
            ->createBlock(\Branch8\Sales\Block\Dashboard\Tab\Products\CustomChart::class)
            ->setTemplate('Branch8_Sales::chart.phtml')
            ->toHtml();

        $this->getResponse()->setBody($block);
    }
}
