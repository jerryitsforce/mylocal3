<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Api\ChatNotificationServiceInterface;

/**
 * Patch to initialize Chat Notification template.
 */
class CreateChatNotificationTemplate implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        $notiTable = $this->moduleDataSetup->getTable('magenest_notification');
        $typeTable = $this->moduleDataSetup->getTable('magenest_notification_type');
        // Ensure "Chat" type exists
        $selectType = $connection->select()->from($typeTable)->where('default_type = ?', ChatNotificationServiceInterface::TYPE_CHAT);
        $type = $connection->fetchRow($selectType);
        if (!$type) {
            $connection->insert($typeTable, [
                'name' => 'Seller Chat Replied',
                'description' => 'Seller Chat Replies',
                'default_type' => ChatNotificationServiceInterface::TYPE_CHAT,
                'is_category' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'is_personal' => 1
            ]);
        }
        // Ensure "Chat" Template exists
        $selectNotification = $connection->select()->from($notiTable)->where('notification_type = ?',
            ChatNotificationServiceInterface::TYPE_CHAT);
        $notification = $connection->fetchRow($selectNotification);

        if (!$notification) {
            $connection->insert($notiTable, [
                'name' => 'Chat Template',
                'is_active' => 1,
                'notification_type' => ChatNotificationServiceInterface::TYPE_CHAT,
                'description' => 'Template for chat notifications',
                'store_view' => '0',
                'customer_group' => '0',
                'is_sent' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
