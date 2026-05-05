<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model;

use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Api\ChatNotificationServiceInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for handling chat notifications for customers.
 */
class ChatNotificationService implements ChatNotificationServiceInterface
{
    private const QUEUE_TABLE = 'marketplace_chat_notification_queue';
    private const NOTIFICATION_TABLE = 'magenest_customer_notification';
    private const DEFAULT_NOTIFICATION_ID = 1; // Assuming this is a basic notification template

    private const XML_PATH_ENABLE = 'buyer_seller_chat/customer_notification_box/enable';
    private const XML_PATH_DELAY = 'buyer_seller_chat/customer_notification_box/delay';
    private const XML_PATH_CLEANUP_DAYS = 'buyer_seller_chat/customer_notification_box/cleanup_days';

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private \Magento\Framework\MessageQueue\PublisherInterface $publisher;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    private Emulation $emulation;

    /**
     * @param ResourceConnection $resource
     * @param Json $json
     * @param LoggerInterface $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param Emulation $emulation
     */
    public function __construct(
        ResourceConnection                                 $resource,
        Json                                               $json,
        LoggerInterface                                    $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        Emulation                                          $emulation,
    )
    {
        $this->resource = $resource;
        $this->json = $json;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->publisher = $publisher;
        $this->connection = $this->resource->getConnection();
        $this->emulation = $emulation;
    }

    /**
     * @inheritDoc
     */
    public function schedule(
        int    $customerId,
        string $conversationId,
        string $message,
        string $senderName,
        ?int   $sellerId = null,
        int    $storeId = 0
    ): void
    {
        try {
            if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE)) {
                return;
            }

            $tableName = $this->connection->getTableName(self::QUEUE_TABLE);

            // Find existing pending notification for this customer and conversation
            $select = $this->connection->select()
                ->from($tableName)
                ->where('customer_id = ?', $customerId)
                ->where('conversation_id = ?', $conversationId)
                ->where('status = ?', Status::STATUS_PENDING);

            $row = $this->connection->fetchRow($select);

            if ($row) {
                // Append message to existing record
                $messages = $this->json->unserialize($row['messages'] ?: '[]');
                $messages[] = [
                    'message' => $message,
                    'sender' => $senderName,
                    'time' => date('Y-m-d H:i:s')
                ];

                $this->connection->update(
                    $tableName,
                    [
                        'messages' => $this->json->serialize($messages),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'sender' => $senderName,
                        'seller_id' => $sellerId,
                        'store_id' => $storeId,
                    ],
                    ['id = ?' => $row['id']]
                );
            } else {
                // Create new record
                $messages = [
                    [
                        'message' => $message,
                        'sender' => $senderName,
                        'time' => date('Y-m-d H:i:s')
                    ]
                ];

                // Bundle messages for configured delay
                $delay = (int)$this->scopeConfig->getValue(self::XML_PATH_DELAY) ?: 300;
                $scheduledAt = date('Y-m-d H:i:s', time() + $delay);

                $this->connection->insert(
                    $tableName,
                    [
                        'customer_id' => $customerId,
                        'conversation_id' => $conversationId,
                        'seller_id' => $sellerId,
                        'store_id' => $storeId,
                        'sender' => $senderName,
                        'messages' => $this->json->serialize($messages),
                        'scheduled_at' => $scheduledAt,
                        'status' => Status::STATUS_PENDING
                    ]
                );
            }

            // Record saved, will be processed by Cron
        } catch (\Exception $e) {
            $this->logger->error('Error scheduling chat notification: ' . $e->getMessage());
        }
    }


    /**
     * @inheritDoc
     */
    public function sendNotification(int $queueId): void
    {
        try {
            $queueTable = $this->connection->getTableName(self::QUEUE_TABLE);
            $notificationTable = $this->connection->getTableName(self::NOTIFICATION_TABLE);
            $magestNotificationTable = $this->connection->getTableName('magenest_notification');
            $select = $this->connection->select()
                ->from($queueTable)
                ->where('id = ?', $queueId)
                ->where('status = ?', Status::STATUS_PUSHING_QUEUE);
            $row = $this->connection->fetchRow($select);
            if (!$row) {
                return;
            }
            // Find notification template
            $selectTemplate = $this->connection->select()
                ->from($magestNotificationTable, 'id')
                ->where('notification_type = ?', self::TYPE_CHAT)
                ->order('id ASC')
                ->limit(1);
            $notificationId = $this->connection->fetchOne($selectTemplate);
            if (!$notificationId) {
                $this->logger->error('Empty notification template: ' . $queueId);
                return;
            }
            $messages = $this->json->unserialize($row['messages'] ?: '[]');
            if (empty($messages)) {
                return;
            }
            list($description, $link) = $this->buildDescriptionAndLink($row, $messages);
            $this->connection->insert($notificationTable, [
                'notification_id' => (int)$notificationId,
                'customer_id' => (string)$row['customer_id'],
                'status' => 0,
                'star' => 0,
                'notification_type' => self::TYPE_CHAT,
                'description' => $description->render(),
                'redirect_url' => $link,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->connection->update(
                $queueTable,
                ['status' => Status::STATUS_DONE, 'updated_at' => date('Y-m-d H:i:s')],
                ['id = ?' => $row['id']]
            );
        } catch (\Exception $e) {
            $this->logger->error('Error sending chat notification: ' . $e->getMessage());
            if (isset($row['id'])) {
                $this->connection->update(
                    $this->connection->getTableName(self::QUEUE_TABLE),
                    ['status' => Status::STATUS_FAILURE, 'updated_at' => date('Y-m-d H:i:s')],
                    ['id = ?' => $row['id']]
                );
            }
        }
    }

    /**
     * @param $row
     * @param $mesages
     * @return array
     */
    private function buildDescriptionAndLink($row, $mesages = [])
    {
        $this->emulation->startEnvironmentEmulation($row['store_id'], \Magento\Framework\App\Area::AREA_FRONTEND);
        $description = __("Seller \"%1\" has replied to your inquiry, go check it out", $row['sender']);
        /**
         * @var $url UrlInterface
         */
        $url = ObjectManager::getInstance()->get(UrlInterface::class);
        $link = $url->getUrl('sales/parentOrder/history') . '?openChat=true&conversation_id=' . $row['conversation_id'];
        $this->emulation->stopEnvironmentEmulation();
        return [$description,$link];
    }

    /**
     * @inheritDoc
     */
    public function processQueue(): int
    {
        try {
            if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE)) {
                return 0;
            }
            $queueTable = $this->connection->getTableName(self::QUEUE_TABLE);
            $select = $this->connection->select()
                ->from($queueTable, 'id')
                ->where('status = ?', Status::STATUS_PENDING)
                ->where('scheduled_at <= ?', date('Y-m-d H:i:s'))
                ->limit(100);

            $ids = $this->connection->fetchCol($select);
            $count = count($ids);

            if ($count > 0) {
                // Bulk update status before pushing to queue to avoid duplicate processing
                $this->connection->update(
                    $queueTable,
                    ['status' => Status::STATUS_PUSHING_QUEUE, 'updated_at' => date('Y-m-d H:i:s')],
                    ['id IN (?)' => $ids]
                );

                foreach ($ids as $id) {
                    $this->publisher->publish('branch8.chat.customer.notification.send', (int)$id);
                }
            }

            return $count;
        } catch (\Exception $e) {
            $this->logger->error('Error processing chat notification queue: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * @inheritDoc
     */
    public function cleanUp(): int
    {
        try {
            $days = (int)$this->scopeConfig->getValue(self::XML_PATH_CLEANUP_DAYS) ?: 7;
            $tableName = $this->connection->getTableName(self::QUEUE_TABLE);
            $cleanupDate = date('Y-m-d H:i:s', strtotime("-" . $days . " days"));

            return $this->connection->delete(
                $tableName,
                [
                    'status IN (?)' => [Status::STATUS_DONE, Status::STATUS_FAILURE, 'sent', 'cancelled'],
                    'updated_at < ?' => $cleanupDate
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error('Error cleaning up chat notification queue: ' . $e->getMessage());
            return 0;
        }
    }
}
