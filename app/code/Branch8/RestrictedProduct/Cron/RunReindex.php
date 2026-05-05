<?php
namespace Branch8\RestrictedProduct\Cron;

use Branch8\RestrictedProduct\Model\Indexer\RestrictedProduct;
use Magento\Framework\Indexer\IndexerRegistry;

class RunReindex
{
    /**
     * @var RestrictedProduct
     */
    protected $restrictedProduct;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        RestrictedProduct $restrictedProduct,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->restrictedProduct = $restrictedProduct;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $this->logger->info("Cron: Starting Branch8 Restricted Product Full Reindex");
            $this->restrictedProduct->executeFull();
            $this->logger->info("Cron: Finished Branch8 Restricted Product Full Reindex");
        } catch (\Exception $e) {
            $this->logger->error("Cron Error: Branch8 Restricted Product Reindex Failed. " . $e->getMessage());
        }
    }
}
