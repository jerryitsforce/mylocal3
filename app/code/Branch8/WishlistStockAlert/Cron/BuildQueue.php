<?php
declare(strict_types=1);

namespace Branch8\WishlistStockAlert\Cron;

use Branch8\WishlistStockAlert\Model\ConfigData;
use Branch8\WishlistStockAlert\Model\QueueManager;
use Magento\Store\Model\StoreManagerInterface;
use Branch8\WishlistStockAlert\Helper\Logger as CustomLogger;

class BuildQueue
{
    protected $productRepository;

    protected CustomLogger $logger;
    private ConfigData $configData;
    private StoreManagerInterface $storeManger;
    private QueueManager $queueManager;

    /**
     * @param QueueManager $queueManager
     * @param ConfigData $configData
     * @param StoreManagerInterface $storeManger
     * @param CustomLogger $logger
     */
    public function __construct(
        QueueManager          $queueManager,
        ConfigData            $configData,
        StoreManagerInterface $storeManger,
        CustomLogger          $logger
    )
    {
        $this->queueManager = $queueManager;
        $this->logger = $logger;
        $this->configData = $configData;
        $this->storeManger = $storeManger;
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            foreach ($this->storeManger->getStores() as $store) {
                $enabled = (bool)$this->configData->getConfigValue('enabled', $store->getId());
                $isStoreEnable = filter_var($store->getIsActive(),FILTER_VALIDATE_BOOLEAN);
                if ($store->getId() === 0 || !$enabled || !$isStoreEnable) { //admin store
                    continue;
                }
                $this->queueManager->generateAndEnqueue((int)$store->getId());
            }
        } catch (\Exception $e) {
            $this->logger->error('Stock Alert Cron (MSI) Error: ' . $e->getMessage());
        }
    }
}
