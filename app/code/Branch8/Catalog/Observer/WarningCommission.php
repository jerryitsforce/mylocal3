<?php

namespace Branch8\Catalog\Observer;

use Magento\Framework\Event\ObserverInterface;

class WarningCommission implements ObserverInterface{

    protected $b8CatalogHelper;

    public function __construct(
        \Branch8\Catalog\Helper\Data $b8CatalogHelper
    ){
        $this->b8CatalogHelper = $b8CatalogHelper;
    }

    public function execute($observer){
        $product = $observer->getEvent()->getProduct();
        $productData = $product->getData();
        $this->b8CatalogHelper->warningCommmisionRate($productData);

    }


}