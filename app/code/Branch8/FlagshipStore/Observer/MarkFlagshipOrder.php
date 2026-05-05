<?php

namespace Branch8\FlagshipStore\Observer;

use Magento\Framework\Event\ObserverInterface;

class MarkFlagshipOrder  implements ObserverInterface
{
    protected $conn;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $connection
    )
    {
        $this->conn = $connection;
    }

    public function execute($observer){
        try {
            $processOrder = $observer->getEvent()->getOrder();
            $processOrder->setData('is_flagship_store_process_order', 1);
            $quote = $observer->getEvent()->getQuote();
            $processForOrderId = $quote->getProcessForOrderId();

            $sqlUpdate = 'update sales_order set is_flagship_store_process_order="'.$processForOrderId.'" where entity_id=' . $processOrder->getId();
            $this->conn->getConnection()->query($sqlUpdate);
        }catch (\Exception $e){
            
        }
    }
}