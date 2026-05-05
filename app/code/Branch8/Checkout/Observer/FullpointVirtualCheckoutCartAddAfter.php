<?php
namespace Branch8\Checkout\Observer;

class FullpointVirtualCheckoutCartAddAfter implements \Magento\Framework\Event\ObserverInterface
{
    protected $registry;

    protected $request;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request
    )
    {
        $this->registry = $registry;
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $fullpointAddCartRequest = (int)$this->request->getParam('fullpoint_virtual');
        if($fullpointAddCartRequest){
            $product = $observer->getData('product');
            $this->registry->register('remain_qty_after_fullpoint_checkout', $product->getOldQtyInCart());
        
        }
    }
}