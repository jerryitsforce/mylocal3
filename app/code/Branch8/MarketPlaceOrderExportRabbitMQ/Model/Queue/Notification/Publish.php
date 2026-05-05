<?php
declare(strict_types=1);
namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\Queue\Notification;

use Branch8\MarketplaceOrderExportRabbitMQ\Model\ProfileNotification;
use Branch8\MarketplaceOrderExportRabbitMQ\Model\ProfileNotificationFactory;
use Branch8\MarketplaceOrderExportRabbitMQ\Model\ResourceModel\ProfileNotification as ProfileNotificationResource;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Publish media gallery synchronization queue.
 */
class Publish
{
    /**
     * Media gallery synchronization queue topic name.
     */
    private const TOPIC = 'branch8.order.notification.synchronization';

    /**
     * @var PublisherInterface
     */
    private $publisher;
    private DateTime $date;
    private ProfileNotificationFactory $profileFactory;
    private ProfileNotificationResource $resource;
    private LoggerInterface $logger;

    /**
     * @param PublisherInterface $publisher
     * @param ProfileNotificationFactory $profileFactory
     * @param ProfileNotificationResource $profileResource
     * @param DateTime $date
     * @param LoggerInterface $logger
     */
    public function __construct(
        PublisherInterface $publisher,
        ProfileNotificationFactory $profileFactory,
        ProfileNotificationResource $profileResource,
        DateTime $date,
        LoggerInterface $logger
    )
    {
        $this->profileFactory = $profileFactory;
        $this->date = $date;
        $this->resource = $profileResource;
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * @param ProfileNotification $profile
     * @return void
     */
    public function execute(ProfileNotification $profile): void
    {
        try {
            if (!$profile || !$profile->getId()) {
                return;
            }
            $date = $this->date->gmtDate('Y-m-d H:i:s');
            $profile->setPublishAt($date);
            $this->publisher->publish(
                self::TOPIC,
                json_encode(['profile_id' => $profile->getId(), 'publish_date' => $date])
            );
            $this->resource->save($profile);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
