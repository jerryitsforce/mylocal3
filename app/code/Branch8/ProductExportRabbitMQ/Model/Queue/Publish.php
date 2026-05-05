<?php
declare(strict_types=1);
namespace Branch8\ProductExportRabbitMQ\Model\Queue;

use Branch8\ProductExportRabbitMQ\Model\Profile;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Publish media gallery synchronization queue.
 */
class Publish
{
    /**
     * Media gallery synchronization queue topic name.
     */
    private const TOPIC = 'branch8.product.export.synchronization';

    /**
     * @var PublisherInterface
     */
    private $publisher;
    private \Magento\Framework\Stdlib\DateTime\DateTime $date;
    private \Branch8\ProductExportRabbitMQ\Model\ProfileFactory $profileFactory;
    private \Branch8\ProductExportRabbitMQ\Model\ResourceModel\Profile $resource;
    private LoggerInterface $logger;

    /**
     * @param PublisherInterface $publisher
     * @param \Branch8\ProductExportRabbitMQ\Model\ProfileFactory $profileFactory
     * @param \Branch8\ProductExportRabbitMQ\Model\ResourceModel\Profile $profileResource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param LoggerInterface $logger
     */
    public function __construct(
        PublisherInterface                                                  $publisher,
        \Branch8\ProductExportRabbitMQ\Model\ProfileFactory        $profileFactory,
        \Branch8\ProductExportRabbitMQ\Model\ResourceModel\Profile $profileResource,
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
     * @param bool $isAdmin
     * @return void
     */
    public function execute(Profile $profile, bool $isAdmin = false): void
    {
        try {
            if (!$profile || !$profile->getId()) {
                return;
            }
            $date = $this->date->gmtDate('Y-m-d H:i:s');
            $profile->setPublishAt($date);
            $this->publisher->publish(
                self::TOPIC,
                json_encode(['profile_id' => $profile->getId(), 'publish_date' => $date, 'is_admin' => $isAdmin])
            );
            $this->resource->save($profile);
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_ProductExportRabbitMQ', 'exceptionlog')){
                $this->logger->critical($e->getMessage());
            }
        }
    }
}
