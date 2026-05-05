<?php

namespace Branch8\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Branch8\HotaiCore\Model\Product\VirtualProductType;

class SetOrderItemProductOptions implements ObserverInterface{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConn
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ){
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        $order = $observer->getEvent()->getOrder();
        foreach ($order->getAllVisibleItems() as $item) {
            $options = $item->getProductOptions();
            $options['PointMoneyConfigType'] = $item->getProduct()->getPointMoneyConfigType();
            $options[VirtualProductType::ATTRIBUTE_CODE] = $item->getProduct()->getVirtualProductType();
            $item->setProductOptions($options);
            $item->save();
        }
    }

}