<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/01/2026
 */

namespace Branch8\WishlistStockAlert\Model\ProcessQueueType\Rabbitmq;

use Branch8\WishlistStockAlert\Helper\Email;
use Branch8\WishlistStockAlert\Model\ConfigData;
use Branch8\WishlistStockAlert\Model\QueueManager;
use Branch8\WishlistStockAlert\Helper\Logger as CustomLogger;
use Psr\Log\LoggerInterface;

class Consumer
{
    /**
     * @var QueueManager
     */
    private QueueManager $queueManager;
    private LoggerInterface $logger;
    private CustomLogger $customLogger;
    /**
     * @var ConfigData
     */
    private ConfigData $config;
    /**
     * @var Email
     */
    private Email $emailHelper;

    /**
     * @param ConfigData $config
     * @param LoggerInterface $logger
     * @param CustomLogger $customLogger
     * @param Email $emailHelper
     * @param QueueManager $queueManager
     */
    public function __construct(
        ConfigData      $config,
        LoggerInterface $logger,
        CustomLogger    $customLogger,
        Email           $emailHelper,
        QueueManager    $queueManager,
    )
    {
        $this->emailHelper = $emailHelper;
        $this->config = $config;
        $this->queueManager = $queueManager;
        $this->logger = $logger;
        $this->customLogger = $customLogger;
    }

    /**
     * @param string $json
     * @return void
     */
    public function execute(string $json)
    {
        if (!$this->config->enabled()) {
            return;
        }
        try {
            $data = @json_decode($json, TRUE);
            if (empty($data)
                || empty($data['queue_id'])
                || empty($job = $this->queueManager->getQueue((int)$data['queue_id']))
            ) {
                if (isset($job)) {
                    if ($this->customLogger->isEnable('Branch8_WishlistStockAlert', 'branch8_wishlist_alert_stock')) {
                        $this->logger->info(json_encode($job));
                    }
                }
                return;
            }
        } catch (\Exception $e) {
            if ($this->customLogger->isEnable('Branch8_WishlistStockAlert', 'branch8_wishlist_alert_stock')) {
                $this->logger->critical($e->getMessage());
            }
            return;
        }
        try {
            $this->emailHelper->sendStockAlert($job);
            $this->queueManager->markSent($job);
        } catch (\Exception $e) {
            if ($this->customLogger->isEnable('Branch8_WishlistStockAlert', 'branch8_wishlist_alert_stock')) {
                $this->logger->critical($e->getMessage());
            }
            $this->queueManager->markFailed($job['queue_id'], $e->getMessage(), $e->getTraceAsString());
        }
    }
}
