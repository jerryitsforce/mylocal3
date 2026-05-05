<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Observer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Dispatcher for the `RmaIndexGridRemove` event.
 */
class RmaIndexGridRemoveObserver implements ObserverInterface
{
    private ResourceConnection $resource;

    public function __construct(
        ResourceConnection $resource,
    )
    {
        $this->resource = $resource;
    }

    /**
     * Handle the `RmaIndexGridRemove` event.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if ($observer->getDataObject() && $observer->getDataObject()->getId()) {
            $this->resource->getConnection()->delete('marketplace_rma_grid', ['id IN (?)' => $observer->getDataObject()->getId()]);
        };
    }
}
