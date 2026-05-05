<?php
namespace Branch8\Catalog\Observer;

use Magento\Store\Model\Store;

class CategoryStore0 implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct()
    {
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $category = $observer->getEvent()->getCategory();
        $category->setStoreId(Store::DEFAULT_STORE_ID);
    }
}