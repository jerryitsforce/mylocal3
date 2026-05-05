<?php

namespace Branch8\MarketPlaceProductDiscussionSeller\Setup\Patch\Data;

use Branch8\MarketPlaceProductDiscussionCustomer\Model\SellerReplyDiscussionNotification;
use Magenest\NotificationBox\Helper\Helper;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

class AddProductDiscussionNotificationType implements DataPatchInterface
{
    /** @var string Notification icon directory url */
    const URL_ICON = 'notificationtype/icon';

    /** @var string default icon directory url */
    const URL_ICON_DEFAULT = '/view/adminhtml/web/images';

    /** @var Helper  */
    private $helper;

    /** @var StoreManagerInterface  */
    private $storeManagerInterface;

    /**
     * @var DirectoryList
     */
    protected $dir;

    /**
     * @var ComponentRegistrarInterface
     */
    protected $path;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Helper $helper
     * @param StoreManagerInterface $storeManagerInterface
     * @param DirectoryList $dir
     * @param ComponentRegistrarInterface $path
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Helper $helper,
        StoreManagerInterface $storeManagerInterface,
        DirectoryList $dir,
        ComponentRegistrarInterface $path
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->helper = $helper;
        $this->dir = $dir;
        $this->path = $path;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $rootPath = $this->path->getPath('module', 'Branch8_MarketPlaceProductDiscussionSeller');
        $rootPub = $this->dir->getPath('media');
        // Create and authorize the notificationtype/icon directory
        if (!file_exists($rootPub . '/' . self::URL_ICON)) {
            mkdir($rootPub . '/' . self::URL_ICON, 0777, true);
        }
        $filePath = $rootPath . self::URL_ICON_DEFAULT;
        $copyFileFullPath = $rootPub . '/' . self::URL_ICON;
        // Copy folder images to pub/media folder
        $this->helper->copyDirectory($filePath, $copyFileFullPath);
        $currentStore = $this->storeManagerInterface->getStore();
        $mediaUrl = $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . self::URL_ICON;
        // Add new notification types including News and Return/Exchange
        $data = [
            [
                'name' => 'Customer Notification When the Seller Replies',
                'description' => 'Customer Notification When the Seller Replies',
                'is_category' => 1,
                'default_type' => SellerReplyDiscussionNotification::CODE,
                'icon' => '[{
                                "name": "seller_reply_to_customer_discussion",
                                "type": "image/png",
                                "url": "' . $mediaUrl . '/customer_notification.jpeg",
                                "size":"1500"
                    }]'
            ]
        ];

        $this->moduleDataSetup->getConnection()->insertArray(
            $this->moduleDataSetup->getTable('magenest_notification_type'),
            ['name', 'description', 'is_category', 'default_type', 'icon'],
            $data
        );

        /**
         * Add new notification
         */
        $taiwanDateObj = new \DateTime();
        $createdAt = $taiwanDateObj->format("Y-m-d H:i:s");
        $selectGroup = $this->moduleDataSetup->getConnection()->select()
            ->from(['group' => 'customer_group'], ['customer_group_id'])
            ->where('customer_group_id <> 0');
        $groups = $this->moduleDataSetup->getConnection()->fetchCol($selectGroup);
        foreach($groups as &$_group){
            $_group = '"'.$_group.'"';
        }
        $notificationData = [
            'id' => NULL,
            'name' => 'Customer Notification When the Seller Replies',
            'is_active' => 1,
            'notification_type' => SellerReplyDiscussionNotification::CODE,
            'condition' => NULL,
            'image' => NULL,
            'store_view' => '["0","1"]',
            'customer_group' => '""',
            'description' => 'Sends a notification to the customer when the seller replies to their discussion or question.',
            'redirect_url' => NULL,
            'total_sent' => 0,
            'total_click' => 0,
            'send_time' => 'send_immediately',
            'schedule' => NULL,
            'schedule_to' => NULL,
            'is_sent' => 0,
            'time_sent' => NULL,
            'impression' => 0,
            'created_at' => $createdAt,
            'update_at' => $createdAt,
            'customer_levels' => '['.implode(',', $groups).']',
            'url_key' => NULL

        ];
        $this->moduleDataSetup->getConnection()->insert(
            $this->moduleDataSetup->getTable('magenest_notification'),
            $notificationData
        );

        $this->moduleDataSetup->endSetup();
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
