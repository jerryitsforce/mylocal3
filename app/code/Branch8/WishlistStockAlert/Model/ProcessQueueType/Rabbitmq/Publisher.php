<?php
declare(strict_types=1);

namespace Branch8\WishlistStockAlert\Model\ProcessQueueType\Rabbitmq;

use Branch8\WishlistStockAlert\Model\Config\ProcessQueueType;
use Branch8\WishlistStockAlert\Model\QueueManager;
use Magento\Framework\MessageQueue\PublisherInterface;
use Branch8\WishlistStockAlert\Helper\Logger as CustomLogger;
/**
 *
 */
class Publisher
{
    /**
     * Media gallery synchronization queue topic name.
     */
    private const TOPIC_PREFIX = 'branch8.wishlist.alert.when.product.instock';

    /**
     * @var PublisherInterface
     */
    private $publisher;
    private \Magento\Framework\Stdlib\DateTime\DateTime $date;
    private QueueManager $queueManager;
    private \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile $resource;
    private CustomLogger $logger;

    /**
     * @param PublisherInterface $publisher
     * @param QueueManager $queueManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param CustomLogger $logger
     */
    public function __construct(
        PublisherInterface                          $publisher,
        QueueManager                                $queueManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        CustomLogger                                $logger
    )
    {
        $this->queueManager = $queueManager;
        $this->date = $date;
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * @param array $job
     * @return void
     */
    public function execute(array $job): void
    {
        try {
            if (!$job || !$job['queue_id']) {
                return;
            }
            $date = $this->date->gmtDate('Y-m-d H:i:s');
            $topicId = $this->getQueueID($job['queue_id']);
            $this->publisher->publish(
                $topicId,
                json_encode(['queue_id' => $job['queue_id'], 'date' => $date])
            );
            $this->queueManager->markSentBy($job, [
                'queue_type' => ProcessQueueType::RABITTMQ,
                'queue_code' => $topicId
            ]);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * @param $profileID
     * @return string
     */
    private function getQueueID($profileID)
    {
        return sprintf('%s%s', self::TOPIC_PREFIX, $profileID % 3);
    }
}
