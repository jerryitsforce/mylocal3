<?php
namespace Branch8\Sales\Controller\Adminhtml\Order;

use Magento\Framework\View\Result\PageFactory;


class ViewDetailHistory extends \Magento\Backend\App\Action{

    const ADMIN_RESOURCE = 'Magento_Sales::actions_view';

    // protected $resultRedirectFactory;

    protected $resultPageFactory;

    protected $orderHistory;

    protected $orderFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        PageFactory $resultPageFactory,
        \Magento\Sales\Model\Order\Status\History $orderHistory,
        \Magento\Sales\Model\OrderFactory $orderFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->orderHistory = $orderHistory;
        $this->orderFactory =$orderFactory;
    }

    public function execute(){
        $historyId = $this->getRequest()->getParam('id', null);
        if(!$historyId){
            return $this->resultRedirectFactory->create()->setUrl('/');
        }
        $history = $this->orderHistory->load($historyId);
        $order = $this->orderFactory->create()->load($history->getParentId());
        $history->setOrder($order);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set('View Detail Comment History');
        $contentBlock = $resultPage->getLayout()->getBlock('sales.order.history.detail');
        $contentBlock->setData('historyDetail', $history);
        return $resultPage;
    }
}