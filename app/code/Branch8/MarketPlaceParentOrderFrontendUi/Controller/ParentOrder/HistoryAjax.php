<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Sales\Controller\OrderInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Controller\AbstractAccount;

class HistoryAjax extends AbstractAccount implements OrderInterface, HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Magento\Framework\Controller\Result\JsonFactory
     */
    protected $pageJsonFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Magento\Framework\Controller\Result\JsonFactory $pageJsonFactory
     */
    public function __construct(
        Context     $context,
        PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $pageJsonFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->pageJsonFactory = $pageJsonFactory;
        parent::__construct($context);
    }

    /**
     * Customer order history
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $layout = $resultPage->getLayout();
        $orderBlock = $layout->getBlock('sales.parent.order.history');
        $orderBlockHtml = $orderBlock->toHtml();
        $orderOthersBlock = $layout->getBlock('order.others');
        $orderOthersBlockHtml = $orderOthersBlock->toHtml();

        $jsonPage = $this->pageJsonFactory->create();
        $jsonPage->setData([
            'orderBlock' => $orderBlockHtml,
            'orderOthers' => $orderOthersBlockHtml
        ]);

        return $jsonPage;
    }
}
