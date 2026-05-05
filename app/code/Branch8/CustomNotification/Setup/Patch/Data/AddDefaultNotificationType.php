<?php

namespace Branch8\CustomNotification\Setup\Patch\Data;

use Magenest\NotificationBox\Helper\Helper;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

class AddDefaultNotificationType implements DataPatchInterface
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
        $rootPath = $this->path->getPath('module', 'Branch8_CustomNotification');
        // Get media folder
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
                'name' => 'News',
                'description' => 'News',
                'is_category' => 1,
                'default_type' => 'news',
                'icon' => '[{
                                "name": "news",
                                "type": "image/svg+xml",
                                "url": "' . $mediaUrl . '/news.svg",
                                "size":"1024"
                    }]'
            ],
            [
                'name' => 'Return/Exchange',
                'description' => 'Return or Exchange',
                'is_category' => 1,
                'default_type' => 'return_exchange',
                'icon' => '[{
                                "name": "return_exchange",
                                "type": "image/png",
                                "url": "' . $mediaUrl . '/return-exchange.png",
                                "size":"1500"
                    }]'
            ]
        ];

        $this->moduleDataSetup->getConnection()->insertArray(
            $this->moduleDataSetup->getTable('magenest_notification_type'),
            ['name', 'description', 'is_category', 'default_type', 'icon'],
            $data
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
