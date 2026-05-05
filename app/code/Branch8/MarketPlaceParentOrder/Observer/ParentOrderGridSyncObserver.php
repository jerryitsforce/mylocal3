<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Observer;

use Magento\Framework\Event\ObserverInterface;


class ParentOrderGridSyncObserver implements ObserverInterface
{
    /**
     * Entity grid model.
     *
     * @var \Magento\Sales\Model\ResourceModel\GridInterface
     */
    protected $entityGrid;

    /**
     * Global configuration storage.
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $globalConfig;
    private string $keyId;

    /**
     * @param \Magento\Sales\Model\ResourceModel\GridInterface $entityGrid
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig
     * @param string $key
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\GridInterface   $entityGrid,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        string                                             $key = 'parent_id'
    )
    {
        $this->entityGrid = $entityGrid;
        $this->globalConfig = $globalConfig;
        $this->keyId = $key;
    }

    /**
     * Handles synchronous insertion of the new entity into
     * corresponding grid on certain events.
     *
     * Used in the next events:
     *
     *  - sales_parent_order_detail_save_after
     *  - sales_parent_order_address_save_after
     *
     * Works only if asynchronous grid indexing is disabled
     * in global settings.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->globalConfig->getValue('dev/grid/async_indexing')) {
            $this->entityGrid->refresh($observer->getDataObject()->getData($this->keyId), $this->keyId);
        }
    }
}
