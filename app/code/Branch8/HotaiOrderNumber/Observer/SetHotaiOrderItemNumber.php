<?php

namespace Branch8\HotaiOrderNumber\Observer;

use Branch8\HotaiOrderNumber\Model\Actions\HotaiGenerateIncrementIdForOrders;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetHotaiOrderItemNumber implements ObserverInterface
{
    private HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders;

    /**
     * @param HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders
     */
    public function __construct(
        HotaiGenerateIncrementIdForOrders $hotaiGenerateIncrementIdForOrders
    )
    {
        $this->hotaiGenerateIncrementIdForOrders = $hotaiGenerateIncrementIdForOrders;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         */
        $item = $observer->getData('data_object');
        if (empty($item->getData(HotaiGenerateIncrementIdForOrders::FIELD_NAME_HOTAI_CHILD_ORDER_ITEM_NUMBER))) {
            $number = $this->hotaiGenerateIncrementIdForOrders->generateForOrderItem(
                $item->getOrder(),
                $item
            );
            $item->setData(
                HotaiGenerateIncrementIdForOrders::FIELD_NAME_HOTAI_CHILD_ORDER_ITEM_NUMBER,
                $number
            );
            $data = [
                'item_id' => $item->getId(),
                HotaiGenerateIncrementIdForOrders::FIELD_NAME_HOTAI_CHILD_ORDER_ITEM_NUMBER => $number
            ];
            $connection = $item->getResource()->getConnection();
            $connection->insertOnDuplicate('sales_order_item',
                [$data],
                [HotaiGenerateIncrementIdForOrders::FIELD_NAME_HOTAI_CHILD_ORDER_ITEM_NUMBER]
            );
        }
    }
}
