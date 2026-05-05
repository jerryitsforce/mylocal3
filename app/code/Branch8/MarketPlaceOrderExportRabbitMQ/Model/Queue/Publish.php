<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\Queue;

use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Profile;
use Magento\Framework\MessageQueue\PublisherInterface;
use mysql_xdevapi\XSession;
use Psr\Log\LoggerInterface;

/**
 * Publish media gallery synchronization queue.
 */
class Publish
{
    /**
     * Media gallery synchronization queue topic name.
     */
    private const TOPIC_PREFIX = 'branch8.order.export.assynchronization';

    /**
     * @var PublisherInterface
     */
    private $publisher;
    private \Magento\Framework\Stdlib\DateTime\DateTime $date;
    private \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory $profileFactory;
    private \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile $resource;
    private LoggerInterface $logger;

    /**
     * @param PublisherInterface $publisher
     * @param \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory $profileFactory
     * @param \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile $profileResource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param LoggerInterface $logger
     */
    public function __construct(
        PublisherInterface                                                  $publisher,
        \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory        $profileFactory,
        \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile $profileResource,
        \Magento\Framework\Stdlib\DateTime\DateTime                         $date,
        LoggerInterface                                                     $logger
    )
    {
        $this->profileFactory = $profileFactory;
        $this->date = $date;
        $this->resource = $profileResource;
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * @param Profile $profileID
     * @return void
     */
    public function execute(Profile $profile): void
    {
        try {
            if (!$profile || !$profile->getProfileId()) {
                return;
            }
            $date = $this->date->gmtDate('Y-m-d H:i:s');
            $batches = $profile->setPublishAt($date)->createBatches()
                ->getBatches();
            if ($batches->getSize() <= 0) {
                return;
            }
            foreach ($batches as $batch) {
                $queueId = $this->getQueueID($profile->getProfileId());
                $data = json_encode([
                    'profile_id' => $profile->getProfileId(),
                    'publish_date' => $date,
                    'queueId' => $queueId,
                    'batchId' => $batch->getBatchId(),
                ]);
                $this->publisher->publish(
                    $this->getQueueID($batch->getBatchId()),
                    $data
                );
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'systemlog')){
                    $this->logger->info($data);
                }
            }
            $this->resource->save($profile);
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'exceptionlog')){
                $this->logger->critical($e->getMessage());
            }
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
