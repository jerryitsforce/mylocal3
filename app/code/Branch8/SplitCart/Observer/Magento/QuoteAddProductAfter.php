<?php
namespace Branch8\SplitCart\Observer\Magento;

use Magento\Framework\Registry;

class QuoteAddProductAfter implements \Magento\Framework\Event\ObserverInterface
{
    protected $registry;

    public function __construct(
        Registry $registry
    )
    {
        $this->registry = $registry;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $items = $observer->getData('items');
        if(!is_array($items) || !$this->registry->registry('split_cart_adding_remaining_items_to_cart')){
            return;
        }
        foreach($items as &$_item){
            $_item->setData('available_to_checkout', 0);
        }
    }
}