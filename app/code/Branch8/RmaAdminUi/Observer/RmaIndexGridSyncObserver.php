<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Dispatcher for the `RmaIndexGridSync` event.
 */
class RmaIndexGridSyncObserver implements ObserverInterface
{
    private IndexerRegistry $indexerRegistry;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
    )
    {
        $this->indexerRegistry = $indexerRegistry;
    }
    /**
     * Handle the `RmaIndexGridSync` event.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if ($observer->getDataObject() && $observer->getDataObject()->getId()) {
            $this->indexerRegistry->get('marketplace_rma_grid')->reindexRow($observer->getDataObject()->getId());
        };
    }
}
