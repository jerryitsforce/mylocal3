<?php

namespace Branch8\LiveSearchResync\Cron;

use Magento\DataExporter\Model\FeedMetadataPool;
use Magento\SaaSCommon\Model\ResyncManagerPool;
use Psr\Log\LoggerInterface;
use Branch8\LiveSearchResync\Helper\Data;

class FullResync
{
    /**
     * @var ResyncManagerPool
     */
    private $resyncManagerPool;

    /**
     * @var FeedMetadataPool
     */
    private $feedMetadataPool;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Data
     */
    private $helper;

    public function __construct(
        ResyncManagerPool $resyncManagerPool,
        FeedMetadataPool $feedMetadataPool,
        LoggerInterface $logger,
        Data $helper
    ) {
        $this->resyncManagerPool = $resyncManagerPool;
        $this->feedMetadataPool = $feedMetadataPool;
        $this->logger = $logger;
        $this->helper = $helper;
    }

    public function execute()
    {
        $this->logInfo('Starting Daily Full SaaS Data Resync');

        try {
            $feeds = $this->feedMetadataPool->getAll();
            foreach ($feeds as $feedMetadata) {
                try {
                    $feedName = $feedMetadata->getFeedName();
                    
                    if ($this->resyncManagerPool->isResyncAvailable($feedName)) {
                        $this->logInfo("Resyncing feed: " . $feedName);
                        $manager = $this->resyncManagerPool->getResyncManager($feedName);
                        $manager->executeFullResync();
                        $this->logInfo("Finished resyncing feed: " . $feedName);
                    } else {
                        $this->logInfo("Resync not available for feed: " . $feedName);
                    }
                } catch (\Exception $e) {
                    $this->logError("Error during SaaS Data Resync for feed {$feedMetadata->getFeedName()}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->logError("Critical Error during SaaS Data Resync: " . $e->getMessage());
        }

        $this->logInfo('Completed Daily Full SaaS Data Resync');
    }

    /**
     * @param string $message
     * @return void
     */
    private function logInfo($message)
    {
        if ($this->helper->isLogTypeEnabled('system.log')) {
            $this->logger->info($message);
        }
    }

    /**
     * @param string $message
     * @return void
     */
    private function logError($message)
    {
        if ($this->helper->isLogTypeEnabled('exception.log')) {
            $this->logger->error($message);
        }
    }
}
