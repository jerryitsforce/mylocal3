<?php

namespace Branch8\Checkout\Observer;

class GetAvailableCheckout implements \Magento\Framework\Event\ObserverInterface
{

    protected $collectionFactory;
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function execute($observer)
    {
        $request = $observer->getEvent()->getRequest();
        $itemId = (int)$request->getParam('id');
        $item = $this->collectionFactory->create()
            ->addFieldToFilter('item_id', $itemId)
            ->getFirstItem();
        $request->setParam('available_to_checkout', $item->getAvailableToCheckout());
    }

}