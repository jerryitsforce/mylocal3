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
class MailPublish
{
    /**
     * Media gallery synchronization queue topic name.
     */
    private const TOPIC_PREFIX = 'branch8.order.export.assynchronizationmailsent';

    /**
     * @var PublisherInterface
     */
    private $publisher;
    private \Magento\Framework\Stdlib\DateTime\DateTime $date;
    private \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory $profileFactory;
    private \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile $resource;
    private LoggerInterface $logger;

    /**
     * @param MailPublish $publisher
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
     * @param Profile $profile
     * @return void
     */
    public function execute(Profile $profile): void
    {
        try {
            if (!$profile || !$profile->getProfileId()) {
                return;
            }
            $date = $this->date->gmtDate('Y-m-d H:i:s');
            $data = json_encode([
                'profile_id' => $profile->getProfileId(),
                'publish_date' => $date,
            ]);
            $this->publisher->publish(self::TOPIC_PREFIX, $data);
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'exceptionlog')){
                $this->logger->critical($e->getMessage());
            }
        }
    }
}
