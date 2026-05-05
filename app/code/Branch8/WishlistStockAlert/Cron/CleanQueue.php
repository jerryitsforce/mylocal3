<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/01/2026
 */

namespace Branch8\WishlistStockAlert\Cron;
use Branch8\WishlistStockAlert\Model\QueueManager;
use Branch8\WishlistStockAlert\Helper\Logger as CustomLogger;
/**
 *
 */

class CleanQueue
{
    /**
     * @var QueueManager
     */
    protected $queueManager;
    protected CustomLogger $logger;

    public function __construct(
        QueueManager $queueManager,
        CustomLogger $logger
    ) {
        $this->queueManager = $queueManager;
        $this->logger       = $logger;
    }
    /**
     * Clean old queue records
     */
    public function execute()
    {
        try {
            $deleted = $this->queueManager->cleanOld(15);
            $this->logger->info(
                '[WishlistRestock] Cleaned old queue: ' . $deleted . ' rows'
            );

        } catch (\Exception $e) {

            $this->logger->error(
                '[WishlistRestock] Queue cleanup failed: ' . $e->getMessage()
            );
        }
    }
}
