<?php

namespace Branch8\SalesOrderGrid\Observer;

use Amasty\RulesPro\Model\Indexer\PurchaseHistory;
use Branch8\SalesOrderGrid\Model\Indexer\SalesOrderSearchData;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Sales\Model\Order;
use Branch8\SalesOrderGrid\Helper\Logger as CustomLogger;

class SaveOrderSearchItems implements ObserverInterface
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    private CustomLogger $logger;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param CustomLogger $logger
     */
    public function __construct(IndexerRegistry $indexerRegistry, CustomLogger $logger)
    {
        $this->indexerRegistry = $indexerRegistry;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        if ($order && $order->getId()) {
            $indexer = $this->indexerRegistry->get(SalesOrderSearchData::INDEXER_ID);
            if (!$indexer->isScheduled()) {
                $indexer->reindexRow((int)$order->getId());
            }
        }
    }
}
