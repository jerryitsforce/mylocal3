<?php
namespace Branch8\GiftToFriend\Controller\Index;

use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderRepository;

class ConfirmSuccess extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $parentOrderCollectionFactory;
    
    /** @var ParentOrderRepository $parentOrderRepository */
    protected $parentOrderRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       CollectionFactory $parentOrderCollectionFactory,
        ParentOrderRepository $parentOrderRepository
    )
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->parentOrderCollectionFactory = $parentOrderCollectionFactory;
        $this->parentOrderRepository = $parentOrderRepository;
    }
    /**
     * View page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->_pageFactory->create();

        $giftCode = addslashes($this->getRequest()->getParam('code', ''));

        if((string)$giftCode == ''){
            $this->messageManager->addErrorMessage(__('Gift code is required.'));
            return $this->_redirect('/')->sendResponse();
        }

        $order = $this->parentOrderCollectionFactory->create()
            ->addFieldToFilter('gift_code', $giftCode)
            ->getFirstItem();

        if(!$order->getId()){
            $this->messageManager->addErrorMessage(__('No order found with the provided gift code.'));
            return $this->_redirect('/')->sendResponse();
        }

        if(!$order->getData('is_gift_confirmed')){
            return $this->_redirect('gift-order', ['_query' => ['code' => $giftCode]])->sendResponse();
        }

        $parentOrder = $this->parentOrderRepository->getByIncrementId($order->getIncrementId());
        // var_dump($parentOrder->getData());
        // die();
        $layout = $result->getLayout();
        $contentBlock = $layout->getBlock('gift.recipient.confirmation.success');
        $contentBlock->setData('orderDetail', $order);
        $contentBlock->setData('parentOrder', $parentOrder);
        
        $result->getConfig()->getTitle()->set(__('接收禮物'));

        return $result;
    }
}
