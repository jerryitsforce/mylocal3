<?php
namespace Branch8\Checkout\Observer;
use Magento\Quote\Model\Quote\PaymentFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;

class FullpointVirtualQuoteItemSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    protected $registry;

    protected $request;

    protected $_conn;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->registry = $registry;
        $this->request = $request;
        $this->_conn = $resourceConnection->getConnection();
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $item = $observer->getEvent()->getItem();
        $fullpointAddCartRequest = (int)$this->request->getParam('fullpoint_virtual');
        $itemProductId = $item->getProductId();
        $requestProductId = $this->request->getParam('product');
        $fullpointItemIdReg = $this->registry->registry('fullpoint_item_id');
        $remainQtyAfterFullpointCheckout = $this->registry->registry('remain_qty_after_fullpoint_checkout');
        if($item->isObjectNew() && $fullpointAddCartRequest && $itemProductId == $requestProductId){
            $itemId = $item->getId();
            if($this->registry->registry('fullpoint_item_id')){
                return;
            }
            $this->registry->register('fullpoint_item_id', $itemId);
            $remainQtyAfterFullpointCheckout = $this->registry->registry('remain_qty_after_fullpoint_checkout');
            $this->_conn->update('quote_item', ['remain_qty_after_fullpoint_checkout' => $remainQtyAfterFullpointCheckout], 'item_id='.$itemId);
        }
    }
}